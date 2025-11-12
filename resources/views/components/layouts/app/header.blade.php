<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-[#f7f7f7] dark:bg-zinc-800">
        <flux:header container class=" bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <img src="{{ asset('assets/images/permata-logo.png') }}" alt="Permata Bank" class="h-8">
            </a>

            @if(auth()->user()->user_level == 2)
                {{-- Menu Admin --}}
                <flux:navbar class="-mb-px max-lg:hidden">
                    <flux:navbar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navbar.item>
                    <flux:navbar.item icon="users" :href="route('admin.data-peserta')" :current="request()->routeIs('admin.data-peserta')" wire:navigate>{{ __('Data Peserta') }}</flux:navbar.item>
                    <flux:navbar.item icon="check-badge" :href="route('admin.verifikasi-bukti')" :current="request()->routeIs('admin.verifikasi-bukti')" wire:navigate>{{ __('Verifikasi Bukti') }}</flux:navbar.item>
                    <flux:navbar.item icon="trophy" :href="route('admin.leaderboard')" :current="request()->routeIs('admin.leaderboard')" wire:navigate>{{ __('Leaderboard') }}</flux:navbar.item>
                </flux:navbar>
            @else
                {{-- Menu Karyawan --}}
                <flux:navbar class="-mb-px max-lg:hidden">
                    <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navbar.item>
                    <flux:navbar.item icon="clock" :href="route('riwayat')" :current="request()->routeIs('riwayat')" wire:navigate>{{ __('Riwayat') }}</flux:navbar.item>
                </flux:navbar>
            @endif

            <flux:spacer />

            <!-- Desktop User Menu -->
            <flux:dropdown position="top" align="end">
                <flux:profile
                    class="cursor-pointer"
                    :initials="auth()->user()->initials()"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <!-- Mobile Menu -->
        <flux:sidebar stashable sticky class="lg:hidden border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="ms-2 me-5 flex items-center space-x-2 rtl:space-x-reverse lg:ms-0" wire:navigate>
                <img src="{{ asset('assets/images/permata-logo.png') }}" alt="Permata Bank" class="h-8">
            </a>

            <flux:navlist variant="outline">
                @if(auth()->user()->user_level == 2)
                    {{-- Menu Admin --}}
                    <flux:navlist.group :heading="__('Menu')">
                        <flux:navlist.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                        <flux:navlist.item icon="users" :href="route('admin.data-peserta')" :current="request()->routeIs('admin.data-peserta')" wire:navigate>{{ __('Data Peserta') }}</flux:navlist.item>
                        <flux:navlist.item icon="check-badge" :href="route('admin.verifikasi-bukti')" :current="request()->routeIs('admin.verifikasi-bukti')" wire:navigate>{{ __('Verifikasi Bukti') }}</flux:navlist.item>
                        <flux:navlist.item icon="trophy" :href="route('admin.leaderboard')" :current="request()->routeIs('admin.leaderboard')" wire:navigate>{{ __('Leaderboard') }}</flux:navlist.item>
                    </flux:navlist.group>
                @else
                    {{-- Menu Karyawan --}}
                    <flux:navlist.group :heading="__('Menu')">
                        <flux:navlist.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                        <flux:navlist.item icon="clock" :href="route('riwayat')" :current="request()->routeIs('riwayat')" wire:navigate>{{ __('Riwayat') }}</flux:navlist.item>
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
