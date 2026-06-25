<?php

use App\Livewire\Pages\Auth\ConfirmPassword;
use App\Livewire\Pages\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', Login::class)
        ->name('login');
});

Route::middleware('auth')->group(function () {
    Route::get('confirm-password', ConfirmPassword::class)
        ->name('password.confirm');
});
