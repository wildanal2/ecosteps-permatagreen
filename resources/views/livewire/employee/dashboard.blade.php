<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};

new #[Layout('components.layouts.app.header')]
    #[Title('Dashboard Karyawan')]
    class extends Component {
};

?>

<flux:main container class="">
    <div class="flex flex-col gap-6 p-6">
        {{-- Header --}}
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                Halo, <span class="text-gray-900 font-bold">Ryan</span> (Permata Pusat)
            </h2>

            {{-- Alert --}}
            <div class="mt-3 rounded-xl bg-red-100 text-red-700 px-4 py-3 flex items-center gap-2">
                <flux:icon.exclamation-triangle class="w-5 h-5" />
                <span>Langkah hari ini tidak terdeteksi</span>
            </div>
        </div>

        {{-- Grid Statistik Atas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">CO₂e yang dihindari:</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-globe-hemisphere-east text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">1.28 kg</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Akumulasi Langkah (1–10 November 2025):</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-footprints text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">72.000 langkah</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Streak:</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-fire text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">5 hari streak</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Pohon:</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-tree-evergreen text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">2 pohon</p>
                </div>
            </div>
        </div>

        {{-- Grid Bawah --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Walk Track --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Walk Track</h3>
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Berjalan Sebagian</span>
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Setiap langkah yang kamu ambil bukan hanya menambah poin, tapi juga mengurangi jejak karbon.
                </p>

                {{-- Progress --}}
                <div class="w-full bg-gray-100 rounded-full h-3 mb-3 overflow-hidden">
                    <div class="bg-yellow-400 h-3 rounded-full" style="width: 10%;"></div>
                </div>
                <p class="text-sm text-gray-700 mb-4">
                    🏃 Langkah hari ini <span class="font-semibold">1000</span>/10.000 langkah
                </p>

                <flux:modal.trigger name="upload-harian">
                    <button class="w-full bg-blue-600 text-white py-2 rounded-xl font-semibold hover:bg-blue-700 transition">
                        Kirim Laporan Hari Ini
                        <i class="ph-fill ph-footprints"></i>
                    </button>
                </flux:modal.trigger>

                <a
                    href="{{ route('riwayat') }}"
                    wire:navigate
                    class="w-full mt-2 bg-gray-100 py-2 rounded-xl text-gray-700 font-medium hover:bg-gray-200 transition flex items-center justify-center gap-2">
                    <i class="ph-fill ph-clock-counter-clockwise"></i>
                    Lihat Riwayat Laporanmu
                </a>
            </div>

            {{-- Rekap Mingguan --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Lihat Rekap Mingguanmu</h3>
                <p class="text-sm text-gray-600 mb-4">
                    Pantau progres langkah dan dampak hijau yang sudah kamu ciptakan minggu ini!
                </p>

                {{-- Chart Placeholder --}}
                <div class="h-40 flex items-end justify-between mb-4">
                    @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $day)
                        <div class="flex flex-col items-center w-6">
                            <div class="w-5 rounded-t-lg bg-blue-500" style="height: {{ rand(20,90) }}%;"></div>
                            <span class="text-xs mt-1 text-gray-500">{{ $day }}</span>
                        </div>
                    @endforeach
                </div>

                <a
                    href="{{ route('riwayat') }}"
                    wire:navigate
                    class="w-full bg-gray-100 py-2 rounded-xl text-gray-700 font-medium hover:bg-gray-200 transition flex items-center justify-center gap-2">
                    <i class="ph-fill ph-clock-counter-clockwise"></i>
                    Lihat Riwayat Laporanmu
                </a>
            </div>
        </div>
    </div>

    <livewire:employee.report-upload-component />

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('open-url', (event) => {
                window.open(event.url, '_blank');
            });

            Livewire.on('close-modal', (event) => {
                window.dispatchEvent(new CustomEvent('modal-close', { detail: event.name }));
            });
        });
    </script>
</flux:main>
