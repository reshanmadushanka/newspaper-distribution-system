<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.users.index')
        : redirect()->route('login');
});

Route::middleware('auth')->get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

Route::prefix('invoices')->name('invoices.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Invoice\InvoiceController::class, 'index'])->name('index');
    Route::get('/{invoice}', [\App\Http\Controllers\Invoice\InvoiceController::class, 'show'])->name('show');
    Route::post('/{invoice}/confirm', [\App\Http\Controllers\Invoice\InvoiceController::class, 'confirm'])->name('confirm');
    Route::post('/{invoice}/cancel', [\App\Http\Controllers\Invoice\InvoiceController::class, 'cancel'])->name('cancel');
    Route::post('/{invoice}/print', [\App\Http\Controllers\Invoice\InvoiceController::class, 'print'])->name('print');
});

Route::prefix('dispatch')->name('dispatch.')->group(function () {
    Route::get('/create', [\App\Http\Controllers\Invoice\DispatchController::class, 'create'])->name('create');
    Route::post('/forecast', [\App\Http\Controllers\Invoice\DispatchController::class, 'forecast'])->name('forecast');
    Route::post('/generate', [\App\Http\Controllers\Invoice\DispatchController::class, 'generate'])->name('generate');
    Route::get('/{invoice}/edit', [\App\Http\Controllers\Invoice\DispatchController::class, 'edit'])->name('edit');
    Route::put('/{invoice}', [\App\Http\Controllers\Invoice\DispatchController::class, 'update'])->name('update');
    Route::post('/{invoice}/confirm', [\App\Http\Controllers\Invoice\DispatchController::class, 'confirm'])->name('confirm');
    Route::post('/deliveries/{delivery}/dispatch', [\App\Http\Controllers\Invoice\DispatchController::class, 'markAsDispatched'])->name('deliveries.dispatch');
    Route::post('/deliveries/{delivery}/deliver', [\App\Http\Controllers\Invoice\DispatchController::class, 'markAsDelivered'])->name('deliveries.deliver');
});

require __DIR__.'/auth.php';
