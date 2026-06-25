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
    if (auth()->user()->role !== 'owner' && auth()->user()->branch_id && $invoice->branch_id !== auth()->user()->branch_id) {
        abort(403, 'Unauthorized branch access.');
    }
    $invoice->load(['customer', 'orders']);
    return view('orders.ticket', compact('invoice'));
})->middleware(['auth'])->name('orders.ticket');

Route::get('/orders/{invoice}/receipt', function (Invoice $invoice) {
    if (auth()->user()->role !== 'owner' && auth()->user()->branch_id && $invoice->branch_id !== auth()->user()->branch_id) {
        abort(403, 'Unauthorized branch access.');
    }
    $invoice->load(['customer', 'orders']);
    return view('orders.receipt', compact('invoice'));
})->middleware(['auth'])->name('orders.receipt');

require __DIR__.'/auth.php';
