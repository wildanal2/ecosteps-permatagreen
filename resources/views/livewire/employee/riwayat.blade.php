<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app.header')]
    #[Title('Riwayat Laporan')]
    class extends Component {
    //
};

?>

<flux:main container>
    <div class="flex flex-col gap-6 p-6">

        {{-- Trend Aktivitas --}}
        <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1">Trend Aktivitas</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Pantau grafik perkembangan aktivitas berjalan dan pengurangan emisi dari waktu ke waktu.
            </p>

            {{-- Chart Placeholder --}}
            <div class="h-48 flex items-end justify-between">
                @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                    <div class="flex flex-col items-center w-6">
                        <div class="w-6 rounded-t-lg bg-blue-500 dark:bg-blue-400 transition-all" style="height: {{ rand(20,90) }}%;"></div>
                        <span class="text-xs mt-2 text-gray-500 dark:text-gray-400">{{ $day }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Search + Button --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <input
                type="text"
                placeholder="Cari Tanggal..."
                class="w-full md:w-1/3 rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 placeholder-gray-400 px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
            <flux:button icon="plus" variant="primary" color="blue">
                Tambah Laporan
            </flux:button>
        </div>

        {{-- List Laporan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ([
                ['date' => '5 Nov 2025', 'status' => 'Proses Verifikasi', 'color' => 'text-amber-500', 'badge' => 'bg-amber-100 dark:bg-amber-900/40'],
                ['date' => '4 Nov 2025', 'status' => 'Diverifikasi', 'color' => 'text-emerald-500', 'badge' => 'bg-emerald-100 dark:bg-emerald-900/40'],
                ['date' => '3 Nov 2025', 'status' => 'Tidak Valid', 'color' => 'text-red-500', 'badge' => 'bg-red-100 dark:bg-red-900/40'],
                ['date' => '2 Nov 2025', 'status' => 'Diverifikasi', 'color' => 'text-emerald-500', 'badge' => 'bg-emerald-100 dark:bg-emerald-900/40'],
            ] as $report)
                <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-4 flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ $report['date'] }}</p>
                        <span class="text-xs px-2 py-1 rounded-full {{ $report['badge'] }} {{ $report['color'] }} font-medium">
                            {{ $report['status'] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-4 text-center text-sm text-gray-800 dark:text-gray-200 mb-4">
                        <div>
                            <p class="font-semibold">1000</p>
                            <p class="text-gray-500 dark:text-gray-400">Langkah</p>
                        </div>
                        <div>
                            <p class="font-semibold">1 kg</p>
                            <p class="text-gray-500 dark:text-gray-400">CO₂e</p>
                        </div>
                        <div>
                            <p class="font-semibold">10 pts</p>
                            <p class="text-gray-500 dark:text-gray-400">Poin</p>
                        </div>
                        <div>
                            <p class="font-semibold">2</p>
                            <p class="text-gray-500 dark:text-gray-400">Pohon</p>
                        </div>
                    </div>

                    <button class="w-full border border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400 py-2 rounded text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/30 flex items-center justify-center gap-2 transition">
                        <i class="ph-fill ph-file-image"></i>
                        Lihat Foto
                    </button>
                </div>
            @endforeach
        </div>
    </div>
</flux:main>
