<x-layouts.app.sidebar-header :title="$title ?? null">
    <flux:main >
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
