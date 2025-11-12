<?php

use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\VerifyEmailController;

// Custom Volt login route (override Fortify GET)
Volt::route('login', 'auth.login')
    ->middleware('guest')
    ->name('login');

// Disable Fortify POST login by creating dummy route
Route::post('login', fn() => abort(404))->middleware('guest');

// Custom email verification route with logging
Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Custom Volt register route (override Fortify GET)
Volt::route('register', 'auth.register')
    ->middleware('guest')
    ->name('register');

// Disable Fortify POST register by creating dummy route
Route::post('register', fn() => abort(404))->middleware('guest');

// Email verification notice (must be after verification.verify)
Volt::route('email/verify', 'auth.verify-email-notice')
    ->middleware('guest')
    ->name('verification.notice');
