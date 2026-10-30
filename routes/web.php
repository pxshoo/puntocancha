<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/clubes/{club:slug}/reservas', PublicBookingController::class)->name('public.bookings');
Route::middleware('auth')->prefix('onboarding')->name('onboarding.')->group(function () {
    Route::get('/paso-1', [OnboardingController::class, 'step1'])->name('step1');
    Route::post('/paso-1', [OnboardingController::class, 'saveStep1'])->name('save-step1');
    Route::get('/paso-2', [OnboardingController::class, 'step2'])->name('step2');
    Route::post('/completar', [OnboardingController::class, 'complete'])->name('complete');
});

Route::get('/dashboard', [AnalyticsController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
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
