<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\ConfirmationService;
use Illuminate\Auth\Events\Login;
use Throwable;

class RecordLoginConfirmation
{
    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }
        try {
            app(ConfirmationService::class)->login($event->user, request()->ip(), request()->userAgent());
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
