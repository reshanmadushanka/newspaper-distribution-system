<?php

use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SystemInvoiceController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Newspaper\NewspaperController;
use App\Http\Controllers\Shop\ShopController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::patch('/locale', [LocaleController::class, 'update'])->name('locale.update');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('users', UserController::class)->except(['show'])->middleware('permission:manage users');
        Route::resource('roles', RoleController::class)->except(['show'])->middleware('permission:manage roles');
        Route::resource('permissions', PermissionController::class)->except(['show'])->middleware('permission:manage permissions');
        Route::resource('shops', ShopController::class)->except(['show'])->middleware('permission:manage shops');
        Route::resource('newspapers', NewspaperController::class)->except(['show'])->middleware('permission:manage newspapers');
        Route::get('invoices/print/batch', [InvoiceController::class, 'batchPrint'])->name('invoices.print.batch')->middleware('permission:manage invoices');
        Route::patch('invoices/mark-printed/batch', [InvoiceController::class, 'markManyAsPrinted'])->name('invoices.mark-printed.batch')->middleware('permission:manage invoices');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'])->middleware('permission:manage invoices');
        Route::delete('invoices/{invoice}/items/{item}', [InvoiceController::class, 'deleteItem'])->name('invoices.items.destroy')->middleware('permission:manage invoices');
        Route::get('invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf')->middleware('permission:manage invoices');
        Route::get('invoices/{id}/pdf-view', [InvoiceController::class, 'streamPdf'])->name('invoices.pdf-view')->middleware('permission:manage invoices');
        Route::patch('invoices/{id}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid')->middleware('permission:manage invoices');
        Route::patch('invoices/{id}/mark-printed', [InvoiceController::class, 'markAsPrinted'])->name('invoices.mark-printed')->middleware('permission:manage invoices');
        Route::post('invoices/auto-generate/preview', [InvoiceController::class, 'autoGeneratePreview'])->name('invoices.auto-generate-preview')->middleware('permission:manage invoices');
        Route::post('invoices/auto-generate', [InvoiceController::class, 'autoGenerate'])->name('invoices.auto-generate')->middleware('permission:manage invoices');
        Route::get('invoices/auto-generate/progress', [InvoiceController::class, 'autoGenerateProgress'])->name('invoices.auto-generate-progress')->middleware('permission:manage invoices');
        Route::post('invoices/auto-generate/clear', [InvoiceController::class, 'autoGenerateClear'])->name('invoices.auto-generate-clear')->middleware('permission:manage invoices');
        Route::get('reports/daily-sales', [InvoiceController::class, 'dailySales'])->name('reports.daily-sales')->middleware('permission:view daily sales');
        Route::get('reports/daily-sales/pdf', [InvoiceController::class, 'dailySalesPdf'])->name('reports.daily-sales.pdf')->middleware('permission:view daily sales');

        // System Invoices Routes (Super Admin creating invoices for admins)
        Route::prefix('system-invoices')->name('system-invoices.')->middleware('permission:manage system invoices')->group(function (): void {
            Route::get('/', [SystemInvoiceController::class, 'index'])->name('index');
            Route::get('/create', [SystemInvoiceController::class, 'create'])->name('create');
            Route::post('/', [SystemInvoiceController::class, 'store'])->name('store');
            Route::get('/{id}', [SystemInvoiceController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [SystemInvoiceController::class, 'edit'])->name('edit');
            Route::put('/{id}', [SystemInvoiceController::class, 'update'])->name('update');
            Route::delete('/{id}', [SystemInvoiceController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/mark-as-paid', [SystemInvoiceController::class, 'markAsPaid'])->name('mark-as-paid');
            Route::patch('/{id}/mark-as-pending', [SystemInvoiceController::class, 'markAsPending'])->name('mark-as-pending');
        });

        // Admin Payment Routes (Admin viewing their invoices)
        Route::prefix('payments')->name('payments.')->group(function (): void {
            Route::get('/pending', [AdminPaymentController::class, 'pendingInvoices'])->name('pending');
            Route::get('/invoice/{id}', [AdminPaymentController::class, 'viewInvoice'])->name('invoice-detail');
            Route::patch('/invoice/{id}', [AdminPaymentController::class, 'updatePaymentStatus'])->name('update-status');
            Route::get('/history', [AdminPaymentController::class, 'paymentHistory'])->name('history');
            Route::get('/invoice/{id}/pdf', [AdminPaymentController::class, 'downloadInvoicePdf'])->name('invoice-pdf');
        });
    });
});
