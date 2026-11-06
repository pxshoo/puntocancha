<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Club;
use App\Models\Court;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class BookingCheckoutService
{
    public function __construct(private WebpayService $webpay, private MarketplaceService $marketplace) {}

    public function start(array $data): BookingPayment
    {
        if (auth()->user()?->role === 'player') {
            $data['customer_email'] = auth()->user()->email;
        }

        return DB::transaction(function () use ($data) {
            $club = $this->marketplace->publishedClubs()->where('slug', $data['club_slug'])->lockForUpdate()->firstOrFail();
            if (! $this->webpay->ready($club)) {
                throw ValidationException::withMessages(['payment' => 'El club aún no ha habilitado Webpay.']);
            }
            $hash = hash_hmac('sha256', $club->id.':'.strtolower($data['customer_email']).':'.$data['request_key'], config('app.key'));
            $existing = BookingPayment::where('request_hash', $hash)->first();
            if ($existing) {
                return $existing;
            }
            $court = Court::withoutGlobalScope('club')->where('club_id', $club->id)->whereKey($data['court_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            $start = CarbonImmutable::parse($data['date'].' '.$data['start_time']);
            $end = $start->addMinutes($club->slot_minutes);
            $opening = CarbonImmutable::parse($data['date'].' '.$club->opens_at);
            $closing = CarbonImmutable::parse($data['date'].' '.$club->closes_at);
            if (! $start->isFuture() || $start->lt($opening) || $end->gt($closing) || (int) $opening->diffInMinutes($start) % $club->slot_minutes !== 0) {
                throw ValidationException::withMessages(['start_time' => 'Selecciona un turno futuro dentro del horario del club.']);
            }
            if (Booking::withoutGlobalScope('club')->where('club_id', $club->id)->where('court_id', $court->id)->where('status', '!=', 'canceled')
                ->where('start_time', '<', $end)->where('end_time', '>', $start)->exists()) {
                throw ValidationException::withMessages(['start_time' => 'Este turno ya está reservado o tiene un pago en curso.']);
            }
            $total = (int) ceil((float) app(BookingPricingService::class)->quote($court, $club, $start, $end));
            if ($total !== (int) $data['quoted_price']) {
                throw ValidationException::withMessages(['quoted_price' => 'La tarifa cambió. Vuelve a seleccionar el turno.']);
            }
            $minimum = min($total, (int) ($club->minimum_deposit ?? $court->minimum_deposit));
            if ($data['payment_choice'] === 'deposit' && $minimum < 1) {
                throw ValidationException::withMessages(['payment_choice' => 'Esta cancha requiere el pago total.']);
            }
            $booking = new Booking;
            $booking->forceFill([
                'club_id' => $club->id, 'court_id' => $court->id,
                'user_id' => auth()->user()?->role === 'player' ? auth()->id() : null,
                'player_id' => auth()->user()?->role === 'player' ? auth()->id() : null,
                'customer_name' => auth()->user()?->role === 'player' ? auth()->user()->name : $data['customer_email'],
                'customer_phone' => auth()->user()?->role === 'player' ? auth()->user()->phone : '',
                'customer_email' => $data['customer_email'], 'start_time' => $start, 'end_time' => $end,
                'total_price' => $total, 'deposit_paid' => 0, 'status' => 'pending_payment', 'payment_method' => 'webpay',
                'payment_terms' => $club->booking_terms, 'cancellation_policy' => $club->cancellation_policy,
            ])->saveQuietly();
            $payment = new BookingPayment;
            $payment->forceFill([
                'id' => (string) Str::uuid(), 'club_id' => $club->id, 'booking_id' => $booking->id,
                'request_hash' => $hash, 'buy_order' => 'PC'.Str::upper(Str::random(24)),
                'session_id' => Str::random(40), 'mode' => $club->webpay_mode,
                'amount' => $data['payment_choice'] === 'deposit' ? $minimum : $total,
                'choice' => $data['payment_choice'], 'status' => 'initialized', 'expires_at' => now()->addMinutes(20),
            ])->save();
            if ($club->webpay_mode === 'payment_link') {
                $payment->forceFill(['status' => 'manual_review', 'gateway_url' => $club->booking_payment_link])->save();

                return $payment;
            }
            $gateway = $this->webpay->create($payment);
            $payment->forceFill(['token' => $gateway['token'], 'token_hash' => hash('sha256', $gateway['token']), 'gateway_url' => $gateway['url']])->save();

            return $payment;
        });
    }

    public function reviewLink(BookingPayment $payment, array $data): void
    {
        abort_unless(auth()->user()?->role === 'club_admin' && auth()->user()->club_id === $payment->club_id, 403);
        DB::transaction(function () use ($payment, $data) {
            Club::whereKey($payment->club_id)->lockForUpdate()->firstOrFail();
            $locked = BookingPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->mode !== 'payment_link' || $locked->status !== 'manual_review') {
                throw ValidationException::withMessages(['payment' => 'Esta solicitud ya fue revisada.']);
            }
            $booking = Booking::withoutGlobalScope('club')->where('club_id', $locked->club_id)->whereKey($locked->booking_id)->lockForUpdate()->firstOrFail();
            if ($data['action'] === 'reject') {
                $locked->forceFill(['status' => 'failed'])->save();
                $booking->forceFill(['status' => 'canceled'])->saveQuietly();

                return;
            }
            if ((int) $data['amount'] !== $locked->amount || $booking->status !== 'pending_payment') {
                throw ValidationException::withMessages(['amount' => 'El monto debe coincidir y la reserva debe seguir pendiente.']);
            }
            if (BookingPayment::where('club_id', $locked->club_id)->where('status', 'paid')->where('authorization_code', $data['authorization'])->exists()) {
                throw ValidationException::withMessages(['authorization' => 'Esta autorización ya fue registrada.']);
            }
            $locked->forceFill(['status' => 'paid', 'authorization_code' => $data['authorization']])->save();
            $booking->forceFill(['status' => 'confirmed', 'deposit_paid' => $locked->amount])->saveQuietly();
            app(ConfirmationService::class)->booking($booking);
        });
    }

    public function settle(BookingPayment $payment, bool $commit = true): BookingPayment
    {
        if ($payment->status !== 'initialized') {
            return $payment;
        }
        $data = $this->webpay->result($payment, $commit);
        if (($data['buy_order'] ?? null) !== $payment->buy_order || ($data['session_id'] ?? null) !== $payment->session_id
            || ! is_numeric($data['amount'] ?? null) || (float) $data['amount'] !== (float) $payment->amount) {
            throw new RuntimeException('La respuesta de Webpay no corresponde a este pago.');
        }

        return DB::transaction(function () use ($payment, $data) {
            Club::whereKey($payment->club_id)->lockForUpdate()->firstOrFail();
            $locked = BookingPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'initialized') {
                return $locked;
            }
            $booking = Booking::withoutGlobalScope('club')->where('club_id', $locked->club_id)->whereKey($locked->booking_id)->lockForUpdate()->firstOrFail();
            if (($data['status'] ?? null) === 'AUTHORIZED' && ($data['response_code'] ?? null) === 0) {
                $booking->forceFill(['deposit_paid' => $locked->amount, 'status' => 'confirmed'])->saveQuietly();
                $locked->forceFill(['status' => 'paid', 'authorization_code' => $data['authorization_code'] ?? null])->save();
                app(ConfirmationService::class)->booking($booking, $locked->mode !== 'production');
            } elseif (in_array($data['status'] ?? null, ['FAILED', 'REVERSED', 'NULLIFIED'], true)
                || (($data['status'] ?? null) === 'INITIALIZED' && $locked->expires_at->isPast())) {
                $booking->forceFill(['status' => 'canceled'])->saveQuietly();
                $locked->forceFill(['status' => 'failed'])->save();
            }

            return $locked;
        });
    }
}
