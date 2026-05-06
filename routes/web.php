<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.users.index')
        : redirect()->route('login');
});

Route::middleware('auth')->get('/dashboard', fn () => Inertia::render('Dashboard'))->name('dashboard');

require __DIR__.'/auth.php';
