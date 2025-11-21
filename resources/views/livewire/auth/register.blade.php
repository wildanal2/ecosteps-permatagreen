<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Enums\Directorate;

new #[Layout('components.layouts.auth')] #[Title('Daftar Akun')] class extends Component {
    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|email|max:255|unique:users,email|ends_with:permatabank.co.id|regex:/^[A-Za-z0-9._%+-]+@permatabank\.co\.id$/i')]
    public string $email = '';

    #[Rule('required|string|min:6')]
    public string $password = '';

    #[Rule('required|integer|min:1')]
    public int $directorate = 0;

    public function updated($property)
    {
        $this->validateOnly($property);
    }

    public function messages()
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap maksimal :max karakter.',
            'email.required' => 'Email corporate wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'email.regex' => 'Email harus menggunakan domain @permatabank.co.id.',
            'email.ends_with' => 'Email harus menggunakan domain @permatabank.co.id.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal :min karakter.',
            'directorate.required' => 'Direktorat wajib dipilih.',
            'directorate.min' => 'Direktorat wajib dipilih.',
        ];
    }

    public function validationAttributes()
    {
        return [
            'name' => 'Nama Lengkap',
            'email' => 'Email Corporate',
            'password' => 'Password',
            'directorate' => 'Direktorat',
        ];
    }

    public function register()
    {
        $validated = $this->validate();

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        if (config('fortify.features') && in_array('emailVerification', array_keys(config('fortify.features')))) {
            $user->sendEmailVerificationNotification();
            session()->put('registered_email', $user->email);
            session()->put('verification_email', $user->email);
            return redirect()->route('verification.notice');
        }

        Auth::login($user);
        return redirect()->route('dashboard');
    }
};

?>


<div class="flex items-center justify-center min-h-screen lg:min-h-0 lg:block w-full">
    <div
        class="w-full p-8 max-w-md lg:max-w-full bg-white dark:bg-zinc-800 lg:bg-gray-50 lg:dark:bg-zinc-900 rounded-2xl shadow-sm lg:shadow-none">

        {{-- Header --}}
        <div class="flex flex-col items-start gap-2 text-left mb-5">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Permata Bank" class="h-18 mb-2">
            <h1 class="text-3xl font-semibold text-gray-900 dark:text-zinc-100">Daftar & Mulai Langkah Hijau Kamu</h1>
            <p class="text-sm text-gray-500">
                Isi data di bawah ini untuk memulai perjalanan kecilmu menuju kebiasaan sehat dan ramah lingkungan.
            </p>
        </div>

        <form wire:submit.prevent="register" class="flex flex-col gap-5">
            {{-- Nama Lengkap --}}
            <div>
                <flux:input wire:model.blur="name" name="name" label="Nama Lengkap"
                    placeholder="Masukkan nama lengkap" autofocus />
            </div>

            {{-- Email Corporate --}}
            <div>
                <flux:input wire:model.blur="email" name="email" type="email" label="Email Corporate"
                    placeholder="ex: robbi@permatabank.co.id" />
            </div>

            {{-- Password --}}
            <div>
                <flux:input wire:model.blur="password" name="password" type="password" label="Password"
                    placeholder="***************" />
            </div>

            {{-- Direktorat --}}
            <div>
                <flux:select wire:model.live="directorate" name="directorate" label="Direktorat"
                    placeholder="Pilih direktorat">
                    @foreach (App\Enums\Directorate::cases() as $dir)
                        <flux:select.option value="{{ $dir->value }}">{{ $dir->label() }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Submit --}}
            <flux:button type="submit" variant="primary" color="blue" class="w-full">
                Daftar Sekarang
            </flux:button>
        </form>

        {{-- Footer --}}
        <div class="mt-6 space-y-3">
            <div class="text-sm text-center text-gray-700 dark:text-zinc-300">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="text-blue-600 font-medium hover:underline">Login Sekarang</a>
            </div>
        </div>
        <x-platform-footer />
    </div>
</div>
