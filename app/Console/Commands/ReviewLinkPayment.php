<?php

namespace App\Console\Commands;

use App\Models\SubscriptionPayment;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ReviewLinkPayment extends Command
{
    protected $signature = 'billing:review-link {order?} {--amount=} {--authorization=} {--reject}';

    protected $description = 'Lista solicitudes o registra una venta previamente verificada en Transbank.';

    public function handle(SubscriptionCheckoutService $service): int
    {
        if (! $this->argument('order')) {
            $rows = SubscriptionPayment::with('club')->whereIn('mode', ['payment_link', 'payment_link_test'])->where('status', 'manual_review')->latest()->limit(100)->get()
                ->map(fn ($payment) => [$payment->buy_order, $payment->club->name, $payment->plan_name, $payment->amount])->all();
            $this->table(['Referencia', 'Club', 'Solicitud', 'Monto CLP'], $rows);

            return self::SUCCESS;
        }
        if (! $this->option('reject') && (! ctype_digit((string) $this->option('amount')) || ! preg_match('/^[a-zA-Z0-9-]{1,50}$/', (string) $this->option('authorization')))) {
            $this->error('Indica --amount y --authorization como aparecen en la venta aprobada en Transbank.');

            return self::FAILURE;
        }
        try {
            $payment = $service->reviewLink($this->argument('order'), (int) $this->option('amount'), (string) $this->option('authorization'), (bool) $this->option('reject'));
            $this->info($payment->status === 'paid' ? 'Venta registrada como verificada.' : 'Solicitud cerrada sin activar plan.');

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->error(collect($exception->errors())->flatten()->implode(' '));

            return self::FAILURE;
        } catch (ModelNotFoundException) {
            $this->error('No existe esa solicitud de pago por enlace.');

            return self::FAILURE;
        }
    }
}
