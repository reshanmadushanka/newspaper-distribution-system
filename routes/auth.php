<?php

use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Newspaper\NewspaperController;
use App\Http\Controllers\Newspaper\NewspaperPriceController;
use App\Http\Controllers\Shop\ShopController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('users', UserController::class)->except(['show'])->middleware('permission:manage users');
        Route::resource('roles', RoleController::class)->except(['show'])->middleware('permission:manage roles');
        Route::resource('permissions', PermissionController::class)->except(['show'])->middleware('permission:manage permissions');
        Route::resource('shops', ShopController::class)->except(['show'])->middleware('permission:manage shops');
        Route::resource('newspapers', NewspaperController::class)->except(['show'])->middleware('permission:manage newspapers');
        Route::get('newspapers/{newspaper}/prices', [NewspaperPriceController::class, 'index'])->name('newspapers.prices.index')->middleware('permission:manage newspapers');
        Route::post('newspapers/{newspaper}/prices', [NewspaperPriceController::class, 'store'])->name('newspapers.prices.store')->middleware('permission:manage newspapers');
        Route::put('newspaper-prices/{price}', [NewspaperPriceController::class, 'update'])->name('newspaper-prices.update')->middleware('permission:manage newspapers');
        Route::delete('newspaper-prices/{price}', [NewspaperPriceController::class, 'destroy'])->name('newspaper-prices.destroy')->middleware('permission:manage newspapers');
        Route::get('newspapers/{newspaper}/prices/current', [NewspaperPriceController::class, 'getCurrentPrice'])->name('newspapers.prices.current');
        Route::post('newspapers/{newspaper}/prices/lookup', [NewspaperPriceController::class, 'getPriceForDate'])->name('newspapers.prices.lookup');
    });
});

