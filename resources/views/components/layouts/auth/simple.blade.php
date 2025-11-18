<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gray-50 dark:bg-zinc-900 flex flex-col">

        <div class="flex flex-col lg:flex-row w-full min-h-screen">
            {{-- Kiri: Form --}}
            <div class="flex flex-col justify-center items-center w-full lg:w-1/2 px-6 py-10">
                {{ $slot }}
            </div>

            {{-- Kanan: Gambar hero --}}
            <div class="hidden lg:block w-1/2 relative">
                <img src="{{ asset('assets/images/register-hero.jpg') }}"
                     alt="Running Illustration"
                     class="absolute inset-0 w-full h-full object-cover" />
            </div>
        </div>

        @fluxScripts
    </body>
</html>
