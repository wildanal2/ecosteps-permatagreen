<?php

use Livewire\Volt\Volt;

// Custom Volt register route (override Fortify)
Volt::route('register', 'auth.register')
    ->middleware('guest')
    ->name('register');
