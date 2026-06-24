<?php

use Illuminate\Support\Facades\Route;
use App\Models\Invoice;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::get('/orders/{invoice}/ticket', function (Invoice $invoice) {
    $invoice->load(['customer', 'orders']);
    return view('orders.ticket', compact('invoice'));
})->middleware(['auth'])->name('orders.ticket');

require __DIR__.'/auth.php';
