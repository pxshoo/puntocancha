<?php

namespace App\Console\Commands;

use App\Models\BookingPayment;
use App\Services\BookingCheckoutService;
use Illuminate\Console\Command;
use Throwable;

class ReconcileWebpay extends Command
{
    protected $signature = 'webpay:reconcile';

    protected $description = 'Verifica pagos pendientes y libera turnos vencidos sin pago autorizado';

    public function handle(BookingCheckoutService $checkout): int
    {
        $failures = 0;
        BookingPayment::where('status', 'initialized')->where('expires_at', '<=', now())->chunkById(100, function ($payments) use ($checkout, &$failures): void {
            foreach ($payments as $payment) {
                try {
                    $checkout->settle($payment, false);
                } catch (Throwable) {
                    $failures++;
                }
            }
        });
        $this->info($failures ? "Pagos pendientes de verificación: {$failures}. Los turnos se mantienen protegidos." : 'Verificación completada.');

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
