<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ClubBrandingController;
use App\Http\Controllers\ClubLogoController;
use App\Http\Controllers\ClubSettingsController;
use App\Http\Controllers\ClubWeatherController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PlayerBookingController;
use App\Http\Controllers\PlayerExplorarController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubscriptionCheckoutController;
use App\Http\Controllers\WebpayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/contratar', [SubscriptionCheckoutController::class, 'select'])->name('subscription.select');
Route::get('/legal/{document}', LegalController::class)->whereIn('document', ['terms', 'privacy'])->name('legal.show');
Route::match(['get', 'post'], '/suscripcion/webpay/retorno', [SubscriptionCheckoutController::class, 'callback'])->middleware('throttle:60,1')->name('subscription.return');
Route::middleware(['auth', 'club.access'])->group(function () {
    Route::get('/suscripcion/contratar', [SubscriptionCheckoutController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/suscripcion/pagar', [SubscriptionCheckoutController::class, 'store'])->middleware('throttle:10,1')->name('subscription.store');
    Route::get('/suscripcion/{payment}/resultado', [SubscriptionCheckoutController::class, 'result'])->name('subscription.result');
    Route::post('/suscripcion/{payment}/consultar', [SubscriptionCheckoutController::class, 'refresh'])->middleware('throttle:10,1')->name('subscription.refresh');
    Route::post('/suscripcion/{payment}/continuar', [SubscriptionCheckoutController::class, 'resume'])->middleware('throttle:10,1')->name('subscription.resume');
});
Route::get('/clubes/{club:slug}/reservas', PublicBookingController::class)->name('public.bookings');
Route::get('/media/clubes/{club:slug}/logo/{version}', ClubLogoController::class)->whereUuid('version')->name('club.logo');
Route::get('/explorar', [PlayerExplorarController::class, 'index'])->name('player.explore');
Route::get('/club/{slug}/clima', ClubWeatherController::class)->middleware('throttle:60,1')->name('player.weather');
Route::get('/club/{slug}', [PlayerExplorarController::class, 'showClub'])->name('player.club');
Route::post('/pagos/webpay', [WebpayController::class, 'store'])->middleware('throttle:10,1')->name('webpay.store');
Route::match(['get', 'post'], '/pagos/webpay/retorno', [WebpayController::class, 'callback'])->middleware('throttle:60,1')->name('webpay.return');
Route::get('/pagos/webpay/{payment}/comprobante', [WebpayController::class, 'receipt'])->middleware('signed')->name('webpay.receipt');
Route::post('/pagos/webpay/{payment}/consultar', [WebpayController::class, 'refresh'])->middleware(['signed', 'throttle:10,1'])->name('webpay.refresh');
Route::middleware(['auth', 'player'])->group(function () {
    Route::get('/mis-reservas', [PlayerExplorarController::class, 'myBookings'])->name('player.bookings');
    Route::post('/reservas/jugador', [PlayerBookingController::class, 'store'])->middleware('throttle:20,1')->name('player.book');
});
Route::middleware(['auth', 'club.access'])->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/paso-1', [OnboardingController::class, 'step1'])->name('step1');
    Route::post('/paso-1', [OnboardingController::class, 'saveStep1'])->name('save-step1');
    Route::get('/paso-2', [OnboardingController::class, 'step2'])->name('step2');
    Route::post('/completar', [OnboardingController::class, 'complete'])->name('complete');
});

Route::get('/dashboard', [AnalyticsController::class, 'index'])->middleware(['auth', 'verified', 'club.access'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'club.access'])->group(function () {
    Route::get('/configuracion', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/configuracion/marca', [ClubBrandingController::class, 'edit'])->name('settings.branding.edit');
    Route::put('/configuracion/marca', [ClubBrandingController::class, 'update'])->name('settings.branding.update');
    Route::get('/club/configuracion/datos', [ClubSettingsController::class, 'edit'])->name('club.settings.edit');
    Route::put('/club/configuracion/datos', [ClubSettingsController::class, 'update'])->name('club.settings.update');
    Route::get('/canchas', [CourtController::class, 'index'])->name('courts.index');
    Route::get('/canchas/nueva', [CourtController::class, 'create'])->name('courts.create');
    Route::post('/canchas', [CourtController::class, 'store'])->name('courts.store');
    Route::get('/canchas/{court}/editar', [CourtController::class, 'edit'])->name('courts.edit');
    Route::put('/canchas/{court}', [CourtController::class, 'update'])->name('courts.update');
    Route::get('/pos', [POSController::class, 'index'])->name('pos.index');
    Route::post('/pos', [POSController::class, 'store'])->name('pos.store');
    Route::post('/pos/{sale}/cobrar', [POSController::class, 'settle'])->name('pos.settle');
    Route::get('/inventario', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventario/nuevo', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventario', [InventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventario/categorias', [InventoryController::class, 'category'])->name('inventory.categories.store');
    Route::get('/inventario/{product}/editar', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventario/{product}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::post('/inventario/{product}/stock', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('/reservas', [BookingController::class, 'index'])->name('bookings.index');
    Route::post('/reservas', [BookingController::class, 'store'])->name('bookings.store');
});

require __DIR__.'/auth.php';

Route::get('/reportes/excel', ReportExportController::class)->middleware(['auth', 'verified', 'club.access', 'throttle:10,1'])->name('reports.excel');
