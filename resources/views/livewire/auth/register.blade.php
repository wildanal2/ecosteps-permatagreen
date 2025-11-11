<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, Rule};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

new #[Layout('components.layouts.auth')]
    #[Title('Daftar Akun')]
    class extends Component {

    #[Rule('required|string|max:255')]
    public string $name = '';

    #[Rule('required|email|max:255|unique:users,email')]
    public string $email = '';

    #[Rule('required|string|min:6')]
    public string $password = '';

    #[Rule('required|string')]
    public string $branch = '';

    #[Rule('required|string')]
    public string $directorate = '';

    #[Rule('required|string')]
    public string $transport = '';

    #[Rule('required|numeric|min:0')]
    public ?float $distance = null;

    #[Rule('required|in:WFO,WFH')]
    public string $work_mode = 'WFO';

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
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal :min karakter.',
            'branch.required' => 'Cabang wajib dipilih.',
            'directorate.required' => 'Direktorat wajib dipilih.',
            'transport.required' => 'Kendaraan wajib dipilih.',
            'distance.numeric' => 'Jarak harus berupa angka.',
            'distance.min' => 'Jarak tidak boleh negatif.',
            'work_mode.required' => 'Mode kerja wajib dipilih.',
        ];
    }

    public function validationAttributes()
    {
        return [
            'name' => 'Nama Lengkap',
            'email' => 'Email Corporate',
            'password' => 'Password',
            'branch' => 'Cabang',
            'directorate' => 'Direktorat',
            'transport' => 'Kendaraan',
            'distance' => 'Jarak',
            'work_mode' => 'Mode Kerja',
        ];
    }

    public function register()
    {
        $validated = $this->validate();

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        Auth::login($user);

        return redirect()->route('dashboard');
    }
};

?>

<div class="w-full p-8 max-w-md lg:max-w-full bg-white lg:bg-gray-50 rounded-2xl shadow-sm lg:shadow-none">
    {{-- Logo --}}
    <div class="flex justify-center lg:justify-start mb-6">
        <img src="{{ asset('assets/images/permata-logo.png') }}" alt="Permata Bank" class="h-8 lg:h-12">
    </div>

    {{-- Header --}}
    <div class="text-center lg:text-left mb-8">
        <h1 class="text-2xl font-semibold text-gray-800">Daftar & Mulai Langkah Hijau Kamu</h1>
        <p class="text-sm text-gray-500 mt-2">
            Isi data di bawah ini untuk memulai perjalanan kecilmu menuju kebiasaan sehat dan ramah lingkungan.
        </p>
    </div>

    <form wire:submit.prevent="register" class="flex flex-col gap-5">
        {{-- Nama Lengkap --}}
        <div>
            <flux:input wire:model.blur="name" name="name" label="Nama Lengkap" placeholder="Masukkan nama lengkap" autofocus />
        </div>

        {{-- Email Corporate --}}
        <div>
            <flux:input wire:model.blur="email" name="email" type="email" label="Email Corporate" placeholder="ex: robbi@permatabank.co.id" />
        </div>

        {{-- Password --}}
        <div>
            <flux:input wire:model.blur="password" name="password" type="password" label="Password" placeholder="***************" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Cabang --}}
            <div>
                <flux:select wire:model.live="branch" name="branch" label="Cabang" placeholder="Pilih cabang">
                    <flux:select.option value="jakarta">Jakarta</flux:select.option>
                    <flux:select.option value="bandung">Bandung</flux:select.option>
                    <flux:select.option value="surabaya">Surabaya</flux:select.option>
                </flux:select>
            </div>

            {{-- Direktorat --}}
            <div>
                <flux:select wire:model.live="directorate" name="directorate" label="Direktorat" placeholder="Pilih direktorat">
                    <flux:select.option value="retail">Retail Banking</flux:select.option>
                    <flux:select.option value="corporate">Corporate Banking</flux:select.option>
                    <flux:select.option value="it">Information Technology</flux:select.option>
                </flux:select>
            </div>

            {{-- Kendaraan --}}
            <div>
                <flux:select wire:model.live="transport" name="transport" label="Kendaraan ke Kantor" placeholder="Pilih jenis kendaraan">
                    <flux:select.option value="mobil">Mobil</flux:select.option>
                    <flux:select.option value="motor">Motor</flux:select.option>
                    <flux:select.option value="sepeda">Sepeda</flux:select.option>
                    <flux:select.option value="transportasi_umum">Transportasi Umum</flux:select.option>
                    <flux:select.option value="jalan_kaki">Jalan Kaki</flux:select.option>
                </flux:select>
            </div>

            {{-- Jarak Rumah ke Kantor --}}
            <div>
                <flux:input wire:model.blur="distance" name="distance" type="number" step="0.1" min="0" label="Jarak rumah ke kantor (km)" placeholder="Masukkan jarak (km)" />
            </div>
        </div>
        {{-- Mode Kerja --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Mode Kerja Saat ini</label>
            <div class="flex items-center gap-4">
                <label class="flex items-center gap-2">
                    <input wire:model.live="work_mode" type="radio" name="work_mode" value="WFO" class="text-emerald-600 focus:ring-emerald-500">
                    <span class="text-gray-700 text-sm">WFO</span>
                </label>
                <label class="flex items-center gap-2">
                    <input wire:model.live="work_mode" type="radio" name="work_mode" value="WFH" class="text-emerald-600 focus:ring-emerald-500">
                    <span class="text-gray-700 text-sm">WFH</span>
                </label>
            </div>
        </div>

        {{-- Submit --}}
        <flux:button type="submit" variant="primary" color="blue">
            Daftar Sekarang
        </flux:button>
    </form>

    <div class="text-center text-sm text-gray-500 mt-6">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="text-blue-600 font-medium hover:underline">Login Sekarang</a>
    </div>
</div>
