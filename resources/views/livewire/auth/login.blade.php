<x-layouts.auth>
    <div class="flex items-center justify-center min-h-screen lg:min-h-0 lg:block">
        <div class="w-full p-8 max-w-md lg:max-w-full bg-white lg:bg-gray-50 rounded-2xl shadow-sm lg:shadow-none">

        {{-- Header --}}
        <div class="flex flex-col items-center gap-2 text-center mb-5">
            <img src="{{ asset('assets/images/permata-logo.png') }}" alt="Permata Bank" class="h-8 mb-2">
            <h1 class="text-2xl font-semibold text-gray-900">Selamat Datang</h1>
            <p class="text-sm text-gray-500">Ayo mulai perjalanan hijau dengan langkah kecilmu hari ini.</p>
        </div>

        {{-- Session Status --}}
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            {{-- Email --}}
            <flux:input name="email" type="email" label="Email Corporate" placeholder="ex: robbi@permatabank.co.id" required autofocus />

            {{-- Password --}}
            <flux:input name="password" type="password" label="Password" placeholder="***************" required />

            {{-- Button --}}
            <button type="submit"
                    class="w-full rounded-xl bg-[#0061FE] hover:bg-[#004bd1] text-white font-medium py-2.5 transition-colors">
                Masuk Sekarang
            </button>
        </form>

        {{-- Footer --}}
        <div class="text-sm text-center text-gray-700 mt-3">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-[#0061FE] hover:underline font-medium">
                Daftar Sekarang
            </a>
        </div>
    </div>
    </div>
</x-layouts.auth>
