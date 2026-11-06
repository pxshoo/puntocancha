<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Club;
use App\Models\Court;
use App\Models\EmailConfirmation;
use App\Models\Sale;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ConfirmationService
{
    public function login(User $user, ?string $ip, ?string $agent): void
    {
        $this->record($user->club_id, 'login:'.$user->id.':'.Str::uuid(), $user->email, [
            'title' => 'Nuevo inicio de sesión',
            'message' => 'Se inició sesión en tu cuenta de PuntoCancha. Si no fuiste tú, cambia tu contraseña.',
            'club' => $user->club?->name ?? 'Cuenta de jugador', 'demo' => false,
            'details' => ['Cuenta' => $user->email, 'Fecha y hora de Chile' => now('America/Santiago')->format('d/m/Y H:i:s'),
                'Dirección IP' => $ip ?? 'No disponible', 'Dispositivo' => mb_substr($agent ?? 'No disponible', 0, 300)],
            'actions' => [['label' => 'Revisar mi cuenta', 'url' => route('profile.edit')]],
        ]);
    }

    public function booking(Booking $booking, bool $demo = false): void
    {
        if ($booking->status !== 'confirmed') {
            return;
        }
        $club = Club::findOrFail($booking->club_id);
        $court = Court::withoutGlobalScope('club')->where('club_id', $club->id)->findOrFail($booking->court_id);
        $payment = BookingPayment::where('club_id', $club->id)->where('booking_id', $booking->id)->where('status', 'paid')->first();
        $paymentDetails = $payment ? ['Medio de pago' => 'Webpay', 'Orden Webpay' => $payment->buy_order,
            'Código de autorización' => $payment->authorization_code ?? 'No disponible',
            'Monto aprobado' => $this->money($payment->amount)] : [];
        $actions = [['label' => 'Cómo llegar a la cancha', 'url' => $club->mapUrl($court)]];
        if ($payment) {
            $actions[] = ['label' => 'Ver comprobante Webpay', 'url' => URL::signedRoute('webpay.receipt', ['payment' => $payment->id])];
        }
        if ($contact = $club->whatsappUrl('Hola, tengo la reserva #'.$booking->id.' y quisiera consultar cómo llegar.')) {
            $actions[] = ['label' => 'Contactar al club', 'url' => $contact];
        }
        $this->record($club->id, 'booking:'.$booking->id, $booking->customer_email, [
            'title' => 'Reserva confirmada', 'message' => 'Tu cancha está reservada. Estos son los detalles de tu partido.',
            'club' => $club->name, 'demo' => $payment ? $payment->mode === 'integration' : $demo, 'policy' => $booking->cancellation_policy,
            'instructions' => array_values(array_filter([$booking->payment_terms, 'Presenta tu número de reserva en recepción.', $club->phone ? 'Contacto del club: '.$club->phone : null])),
            'actions' => $actions,
            'details' => ['Reserva' => '#'.$booking->id, 'Cancha' => $court->name,
                'Inicio (hora de Chile)' => $booking->start_time->format('d/m/Y H:i'),
                'Término' => $booking->end_time->format('H:i'),
                'Ubicación' => ($court->address ?: $club->address).', '.($court->city ?: $club->city),
                'Total' => $this->money($booking->total_price), 'Abono registrado' => $this->money($booking->deposit_paid),
                'Saldo pendiente' => $this->money(max(0, (float) $booking->total_price - (float) $booking->deposit_paid))] + $paymentDetails,
        ]);
    }

    public function subscription(SubscriptionPayment $payment): void
    {
        if ($payment->status !== 'paid') {
            return;
        }
        $user = User::where('club_id', $payment->club_id)->findOrFail($payment->user_id);
        $this->record($payment->club_id, 'subscription:'.$payment->id, $user->email, [
            'title' => $payment->mode === 'payment_link_test' ? 'Prueba real de cobro confirmada' : 'Compra de plan confirmada',
            'message' => $payment->mode === 'payment_link_test' ? 'Registramos tu pago real de $100. Esta prueba no activa un plan.' : 'El pago de tu suscripción fue aprobado.',
            'club' => $payment->club->name, 'demo' => $payment->mode === 'integration',
            'details' => ['Orden' => $payment->buy_order, 'Plan' => $payment->plan_name,
                'Período' => $payment->billing_cycle === 'annual' ? 'Anual' : 'Mensual', 'Total pagado' => $this->money($payment->amount),
                'Medio de pago' => 'Webpay', 'Código de autorización' => $payment->authorization_code ?? 'No disponible'],
            'actions' => [['label' => 'Ver comprobante de mi plan', 'url' => route('subscription.result', $payment)]],
        ]);
    }

    public function sale(Sale $sale): void
    {
        if ($sale->status !== 'paid') {
            return;
        }
        $recipient = $sale->customer_email;
        if (! $recipient && $sale->booking_id) {
            $recipient = Booking::withoutGlobalScope('club')->where('club_id', $sale->club_id)->find($sale->booking_id)?->customer_email;
        }
        $club = Club::findOrFail($sale->club_id);
        $items = $sale->items()->withoutGlobalScope('club')->where('club_id', $sale->club_id)->get();
        $this->record($sale->club_id, 'sale:'.$sale->id, $recipient, [
            'title' => 'Compra confirmada', 'message' => 'Registramos el pago de tu compra.',
            'club' => $club->name, 'demo' => false, 'details' => [
                'Venta' => '#'.$sale->id, 'Total pagado' => $this->money($sale->total_amount),
                'Medio de pago' => ['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'pos' => 'Tarjeta POS'][$sale->payment_method] ?? $sale->payment_method,
            ],
            'items' => $items->map(fn ($item) => ['name' => $item->product_name, 'quantity' => $item->quantity, 'total' => $this->money($item->subtotal)])->all(),
        ]);
    }

    public function accessChanged(Club $club): void
    {
        $status = match ($club->access_override) {
            'allow' => ['Acceso a tu club habilitado', 'La administración habilitó el acceso de tu club a PuntoCancha. Puedes continuar con tu cuenta habitual.'],
            'deny' => ['Acceso a tu club suspendido', 'El acceso de tu club fue suspendido. Contacta a soporte para revisar tu cuenta.'],
            default => ['Estado de acceso actualizado', 'El acceso de tu club vuelve a depender de la vigencia de su suscripción.'],
        };
        $event = (string) Str::uuid();
        foreach (User::where('club_id', $club->id)->where('role', 'club_admin')->get() as $owner) {
            $this->record($club->id, 'access:'.$event.':'.$owner->id, $owner->email, [
                'title' => $status[0], 'message' => $status[1], 'club' => $club->name, 'demo' => false,
                'details' => ['Club' => $club->name, 'Cuenta' => $owner->email,
                    'Fecha y hora de Chile' => now('America/Santiago')->format('d/m/Y H:i')],
                'actions' => [['label' => $club->access_override === 'deny' ? 'Contactar a soporte' : 'Entrar a mi club',
                    'url' => $club->access_override === 'deny' ? 'https://wa.me/56931403167' : route('dashboard')]],
            ]);
        }
    }

    private function record(?int $clubId, string $key, ?string $recipient, array $payload): void
    {
        if (! $recipient || ! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            return;
        }
        EmailConfirmation::firstOrCreate(['event_key' => $key], ['club_id' => $clubId, 'recipient' => $recipient, 'payload' => $payload]);
    }

    private function money(mixed $amount): string
    {
        return '$'.number_format((float) $amount, 0, ',', '.').' CLP';
    }
}
