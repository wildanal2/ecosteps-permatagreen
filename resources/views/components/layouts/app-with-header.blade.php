<x-layouts.app.sidebar-header :title="$title ?? null">
    <x-slot:navbar>
        {{ $navbar ?? '' }}
    </x-slot:navbar>
    
    <flux:main >
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar-header>
