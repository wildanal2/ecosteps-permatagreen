<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

new #[Layout('components.layouts.auth')]
    #[Title('Login')]
    class extends Component {

    #[Rule('required|email')]
    public string $email = '';

    #[Rule('required|string')]
    public string $password = '';

    public function login()
    {
        $this->validate();

        $throttleKey = strtolower($this->email).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam '.RateLimiter::availableIn($throttleKey).' detik.',
            ]);
        }

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], true)) {
            RateLimiter::hit($throttleKey, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        // Cek apakah email sudah diverifikasi
        if (config('app.email_verification_required', false) && !Auth::user()->hasVerifiedEmail()) {
            $email = Auth::user()->email;
            Auth::logout();
            session()->put('unverified_email', $email);
            session()->put('verification_email', $email);
            return redirect()->route('verification.notice');
        }

        session()->regenerate();

        $user = Auth::user();
        $redirectRoute = $user->user_level == 2
            ? route('admin.dashboard')
            : route('dashboard');

        return redirect()->intended($redirectRoute);
    }
};

?>

<div class="flex items-center justify-center min-h-screen lg:min-h-0 lg:block">
    <div class="w-full p-8 max-w-md lg:max-w-full bg-white dark:bg-zinc-800 lg:bg-gray-50 lg:dark:bg-zinc-900 rounded-2xl shadow-sm lg:shadow-none">

        {{-- Header --}}
        <div class="flex flex-col items-start gap-2 text-left mb-5">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Permata Bank" class="h-18 mb-2">
            <h1 class="text-3xl font-semibold text-gray-900 dark:text-zinc-100">Move for Elephants</h1>
            <p class="text-sm text-gray-500 dark:text-zinc-400">Challenge 1 bulan untuk seluruh Permata Bankers guna membangun kebiasaan sehat dan ramah lingkungan.</p>
        </div>

        {{-- Session Status --}}
        @if(session('status'))
            <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-sm text-green-800 dark:text-green-200">{{ session('status') }}</p>
            </div>
        @endif

        <form wire:submit.prevent="login" class="flex flex-col gap-5">
            {{-- Email --}}
            <div>
                <flux:input wire:model.blur="email" name="email" type="email" label="Email Corporate" placeholder="ex: robbi@permatabank.co.id" autofocus />
            </div>

            {{-- Password --}}
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="text-sm font-medium text-gray-700 dark:text-zinc-300">Password</label>
                </div>
                <flux:input wire:model.blur="password" name="password" type="password" placeholder="***************" viewable/>
                @error('password') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
            </div>

            {{-- Button --}}
            <flux:button type="submit" variant="primary" color="blue" class="w-full">
                Masuk Sekarang
            </flux:button>
        </form>

        {{-- Footer --}}
        <div class="mt-6 space-y-3">
            <div class="text-sm text-center text-gray-700 dark:text-zinc-300">
                Belum punya akun?
                <a href="{{ route('register') }}" class="text-[#0061FE] hover:underline font-medium">
                    Daftar Sekarang
                </a>
            </div>
            <div class="pt-3 border-t border-gray-200 dark:border-zinc-700">
                <a href="{{ route('password.request') }}" class="flex items-center justify-center gap-2 text-sm text-gray-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Lupa Password?
                </a>
            </div>
        </div>
        <x-platform-footer />
    </div>
</div>
