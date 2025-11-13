<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

// IMPORTANT: Define custom routes BEFORE Fortify loads
require __DIR__.'/auth.php';

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

// Employee Routes (user_level = 1)
Route::middleware(['auth', 'verified', 'check.user.level'])->group(function () {
    Volt::route('dashboard', 'employee.dashboard')->name('dashboard');
    Volt::route('riwayat', 'employee.riwayat')->name('riwayat');
    Volt::route('user/profile', 'settings.profile-karyawan')->name('employee.profile');
    Volt::route('user/password', 'settings.password-karyawan')->name('employee.password');
    Volt::route('user/appearance', 'settings.appearance-karyawan')->name('employee.appearance');

    Volt::route('user/two-factor', 'settings.two-factor-karyawan')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('employee.two-factor');
});

// Admin Routes (user_level = 2)
Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified', 'check.user.level'])->group(function () {
    Volt::route('dashboard', 'admin.dashboard')->name('dashboard');
    Volt::route('data-peserta', 'admin.data-peserta')->name('data-peserta');
    Volt::route('data-peserta/{id}', 'admin.detail-peserta')->name('detail-peserta');
    Volt::route('verifikasi-bukti', 'admin.verifikasi-bukti')->name('verifikasi-bukti');
    Volt::route('leaderboard', 'admin.leaderboard')->name('leaderboard');
});

Route::middleware(['auth', 'check.user.level'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
    Volt::route('settings/password', 'settings.password')->name('user-password.edit');
    Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

    Volt::route('settings/two-factor', 'settings.two-factor')
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
