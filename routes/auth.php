<?php

use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Newspaper\NewspaperController;
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
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('permission:manage invoices');
        Route::get('invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf')->middleware('permission:manage invoices');
        Route::get('invoices/{id}/pdf-view', [InvoiceController::class, 'streamPdf'])->name('invoices.pdf-view')->middleware('permission:manage invoices');
        Route::patch('invoices/{id}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid')->middleware('permission:manage invoices');
        Route::get('reports/daily-sales', [InvoiceController::class, 'dailySales'])->name('reports.daily-sales')->middleware('permission:view daily sales');
        Route::get('reports/daily-sales/pdf', [InvoiceController::class, 'dailySalesPdf'])->name('reports.daily-sales.pdf')->middleware('permission:view daily sales');
    });
});
