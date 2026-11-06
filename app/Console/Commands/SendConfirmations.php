<?php

namespace App\Console\Commands;

use App\Mail\TransactionConfirmation;
use App\Models\EmailConfirmation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendConfirmations extends Command
{
    protected $signature = 'notifications:send {--limit=100}';

    protected $description = 'Envía confirmaciones pendientes y reintenta las fallidas.';

    public function handle(): int
    {
        if (! app()->environment('testing') && in_array(config('mail.default'), ['log', 'array'], true)) {
            $this->warn('Configura un proveedor de correo real en .env. Las confirmaciones siguen pendientes.');

            return self::SUCCESS;
        }
        if (config('mail.default') === 'smtp' && config('mail.mailers.smtp.host') === 'smtp.gmail.com'
            && (! config('mail.mailers.smtp.username') || ! config('mail.mailers.smtp.password'))) {
            $this->warn('Completa MAIL_USERNAME y MAIL_PASSWORD con tu Gmail y su contraseña de aplicación. Los correos siguen pendientes.');

            return self::SUCCESS;
        }
        $ids = EmailConfirmation::whereNull('sent_at')->where('attempts', '<', 10)
            ->where(fn ($query) => $query->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('id')->limit(max(1, min(1000, (int) $this->option('limit'))))->pluck('id');
        foreach ($ids as $id) {
            DB::transaction(function () use ($id) {
                $message = EmailConfirmation::whereKey($id)->lockForUpdate()->first();
                if (! $message || $message->sent_at || $message->attempts >= 10 || $message->next_attempt_at?->isFuture()) {
                    return;
                }
                try {
                    Mail::to($message->recipient)->send(new TransactionConfirmation($message->payload));
                    $message->sent_at = now();
                    $message->next_attempt_at = null;
                } catch (Throwable) {
                    $message->next_attempt_at = now()->addMinutes(min(60, 2 ** $message->attempts));
                    $this->warn('No se pudo enviar la confirmación #'.$id.'. Se reintentará automáticamente.');
                }
                $message->attempts++;
                $message->save();
            });
        }

        return self::SUCCESS;
    }
}
