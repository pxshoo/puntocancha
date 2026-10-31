<?php

use App\Http\Middleware\EnsureClubAccess;
use App\Http\Middleware\EnsureOnboardingIsComplete;
use App\Http\Middleware\EnsurePlayer;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->preventRequestForgery(except: ['pagos/webpay/retorno']);
        $middleware->alias(['club.access' => EnsureClubAccess::class, 'player' => EnsurePlayer::class]);
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->role === 'player' ? route('player.explore') : route('dashboard'));
        $middleware->web(append: [EnsureOnboardingIsComplete::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
