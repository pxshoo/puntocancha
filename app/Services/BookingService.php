<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function createBooking(array $data): Booking
    {
        if (auth()->user()?->role === 'player') {
            return $this->createPlayerBooking($data);
        }

        return DB::transaction(function () use ($data) {
            Club::whereKey(auth()->user()?->club_id)->lockForUpdate()->firstOrFail();
            $court = Court::where('id', $data['court_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! $court->is_active) {
                throw new Exception('La cancha está inactiva y no admite nuevas reservas.');
            }

            $startTime = Carbon::parse($data['start_time']);
            $endTime = Carbon::parse($data['end_time']);

            // Validar que no se superpongan reservas
            $isOverlapping = Booking::where('court_id', $court->id)
                ->where('status', '!=', 'canceled')
                ->where(function ($query) use ($startTime, $endTime) {
                    $query->where('start_time', '<', $endTime)
                        ->where('end_time', '>', $startTime);
                })
                ->exists();

            if ($isOverlapping) {
                throw new Exception("El horario seleccionado para {$court->name} ya está ocupado.");
            }

            return Booking::create([
                'court_id' => $court->id,
                'user_id' => $data['user_id'] ?? null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'total_price' => $data['total_price'],
                'deposit_paid' => $data['deposit_paid'] ?? 0,
                'status' => $data['status'] ?? 'pending',
                'payment_method' => $data['payment_method'] ?? 'cash',
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    private function createPlayerBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $player = User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
            $existing = Booking::withoutGlobalScope('club')->where('player_id', $player->id)->where('request_key', $data['request_key'])->first();
            if ($existing) {
                return $existing;
            }
            $service = app(MarketplaceService::class);
            $club = $service->publishedClubs()->where('slug', $data['club_slug'])->lockForUpdate()->firstOrFail();
            if (app(WebpayService::class)->ready($club)) {
                throw ValidationException::withMessages(['payment' => 'Completa esta reserva mediante Webpay.']);
            }
            $court = Court::withoutGlobalScope('club')->where('club_id', $club->id)->whereKey($data['court_id'])->where('is_active', true)->lockForUpdate()->firstOrFail();
            $start = CarbonImmutable::parse($data['date'].' '.$data['start_time']);
            $end = $start->addMinutes($club->slot_minutes);
            $opening = CarbonImmutable::parse($data['date'].' '.$club->opens_at);
            $closing = CarbonImmutable::parse($data['date'].' '.$club->closes_at);
            if (! $start->isFuture() || $start->lt($opening) || $end->gt($closing)
                || (int) $opening->diffInMinutes($start) % $club->slot_minutes !== 0) {
                throw ValidationException::withMessages(['start_time' => 'Elige un turno futuro dentro de los horarios publicados.']);
            }
            $overlap = Booking::withoutGlobalScope('club')->where('club_id', $club->id)->where('court_id', $court->id)
                ->where('status', '!=', 'canceled')->where('start_time', '<', $end)->where('end_time', '>', $start)->exists();
            if ($overlap) {
                throw ValidationException::withMessages(['start_time' => 'Este turno acaba de ser reservado. Selecciona otro horario.']);
            }
            $price = $service->slotPrice($court->default_hourly_rate, $club->slot_minutes);
            if (number_format((float) $data['quoted_price'], 2, '.', '') !== $price) {
                throw ValidationException::withMessages(['quoted_price' => 'La tarifa cambió. Revisa el nuevo precio y selecciona el turno nuevamente.']);
            }
            $booking = new Booking;
            $booking->forceFill([
                'club_id' => $club->id, 'court_id' => $court->id, 'user_id' => $player->id, 'player_id' => $player->id,
                'request_key' => $data['request_key'], 'customer_name' => $player->name,
                'customer_phone' => $player->phone, 'customer_email' => $player->email,
                'start_time' => $start, 'end_time' => $end,
                'total_price' => $price,
                'deposit_paid' => 0, 'status' => 'confirmed', 'payment_method' => 'pay_at_club',
                'payment_terms' => $club->booking_terms, 'cancellation_policy' => $club->cancellation_policy,
            ]);
            // The tenant is derived from the locked public court, never from player input.
            $booking->saveQuietly();

            return $booking;
        }, 3);
    }
}
