<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\DailyReport;
use Illuminate\Support\Carbon;

new #[Layout('components.layouts.app.header')]
    #[Title('Riwayat Laporan')]
    class extends Component {
    use WithPagination;

    public $search = '';
    public $weekOffset = 0;

    public function with(): array
    {
        $userId = auth()->id();

        // Hitung minggu berdasarkan offset
        $startOfWeek = now()->subWeeks($this->weekOffset)->startOfWeek();
        $endOfWeek = now()->subWeeks($this->weekOffset)->endOfWeek();

        // Generate 7 hari dalam minggu
        $weekDates = collect();
        for ($i = 0; $i < 7; $i++) {
            $weekDates->push($startOfWeek->copy()->addDays($i)->format('Y-m-d'));
        }

        // Ambil data laporan untuk minggu ini
        $reportsData = DailyReport::where('user_id', $userId)
            ->whereBetween('tanggal_laporan', [$startOfWeek, $endOfWeek])
            ->when($this->search, fn($q) => $q->whereRaw("to_char(tanggal_laporan, 'YYYY-MM-DD') LIKE ?", ['%' . $this->search . '%']))
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->tanggal_laporan)->format('Y-m-d'));

        // Map setiap tanggal dengan data atau null
        $reports = $weekDates->map(fn($date) => $reportsData->get($date));

        // Chart data (sesuai minggu yang ditampilkan)
        $chartLabels = collect();
        $chartSteps = collect();
        $chartDates = collect();
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $chartLabels->push($date->format('D'));
            $chartDates->push($date->format('d M'));
            $chartSteps->push($reportsData->get($date->format('Y-m-d'))?->langkah ?? 0);
        }

        return [
            'reports' => $reports,
            'chartLabels' => $chartLabels,
            'chartDates' => $chartDates,
            'chartSteps' => $chartSteps,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
        ];
    }

    public function nextWeek()
    {
        $this->weekOffset++;
        $this->updateChart();
    }

    public function prevWeek()
    {
        if ($this->weekOffset > 0) {
            $this->weekOffset--;
            $this->updateChart();
        }
    }

    private function updateChart()
    {
        $userId = auth()->id();
        $startOfWeek = now()->subWeeks($this->weekOffset)->startOfWeek();
        $endOfWeek = now()->subWeeks($this->weekOffset)->endOfWeek();

        $reportsData = DailyReport::where('user_id', $userId)
            ->whereBetween('tanggal_laporan', [$startOfWeek, $endOfWeek])
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->tanggal_laporan)->format('Y-m-d'));

        $chartLabels = [];
        $chartDates = [];
        $chartSteps = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i);
            $chartLabels[] = $date->format('D');
            $chartDates[] = $date->format('d M');
            $chartSteps[] = $reportsData->get($date->format('Y-m-d'))?->langkah ?? 0;
        }

        $this->dispatch('chart-update', [
            'labels' => $chartLabels,
            'dates' => $chartDates,
            'steps' => $chartSteps
        ]);
    }
};

?>

<flux:main container>
    <div class="flex flex-col gap-6 p-6">

        {{-- Trend Aktivitas --}}
        <div class="rounded-2xl border border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm p-6">
            <div class="flex items-center justify-between mb-1">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-zinc-100">Trend Aktivitas</h2>
                <span class="text-xs text-gray-500 dark:text-zinc-400">{{ $startOfWeek->format('d M') }} - {{ $endOfWeek->format('d M Y') }}</span>
            </div>
            <p class="text-sm text-gray-600 dark:text-zinc-400 mb-4">
                Pantau grafik perkembangan aktivitas berjalan dan pengurangan emisi dari waktu ke waktu.
            </p>

            {{-- Chart --}}
            <canvas id="activityChart" class="w-full" style="max-height: 200px;"></canvas>
        </div>

        {{-- Search + Button --}}
        {{-- <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Cari Tanggal..."
                class="w-full md:w-1/3 rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 placeholder-gray-400 px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
        </div> --}}

        {{-- Navigasi Minggu --}}
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 sm:gap-4 my-4">
            <button
                wire:click="nextWeek"
                wire:loading.attr="disabled"
                class="flex items-center justify-center px-3 py-2 sm:px-4 sm:py-2 bg-gray-100 dark:bg-zinc-700 rounded-md shadow transition-all hover:bg-gray-200 dark:hover:bg-zinc-600 disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:cursor-not-allowed w-full sm:w-auto">
                <flux:icon.chevron-left class="w-4 h-4 mr-1" />
                <span class="font-medium text-gray-700 dark:text-zinc-300 text-sm sm:text-base">Minggu Lalu</span>
            </button>
            <span class="px-3 py-2 sm:px-5 sm:py-2 rounded-xl font-semibold text-sm sm:text-base bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 shadow-sm text-gray-800 dark:text-zinc-100 select-none text-center w-full sm:w-auto">
                {{ $startOfWeek->format('d M') }} <span class="text-gray-400 dark:text-zinc-400">-</span> {{ $endOfWeek->format('d M Y') }}
            </span>
            <button
                wire:click="prevWeek"
                wire:loading.attr="disabled"
                @if($weekOffset == 0) disabled @endif
                class="flex items-center justify-center px-3 py-2 sm:px-4 sm:py-2 bg-gray-100 dark:bg-zinc-700 rounded-md shadow transition-all hover:bg-gray-200 dark:hover:bg-zinc-600 disabled:opacity-40 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:cursor-not-allowed w-full sm:w-auto">
                <span class="font-medium text-gray-700 dark:text-zinc-300 text-sm sm:text-base">Minggu Depan</span>
                <flux:icon.chevron-right class="w-4 h-4 ml-1" />
            </button>
        </div>

        {{-- List Laporan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($reports as $index => $report)
                @if($report)
                    @php
                        $statusConfig = match($report->status_verifikasi->value) {
                            1 => ['text' => 'Proses Verifikasi', 'color' => 'text-amber-500', 'badge' => 'bg-amber-100 dark:bg-amber-900/40'],
                            2 => ['text' => 'Diverifikasi', 'color' => 'text-emerald-500', 'badge' => 'bg-emerald-100 dark:bg-emerald-900/40'],
                            3 => ['text' => 'Tidak Valid', 'color' => 'text-red-500', 'badge' => 'bg-red-100 dark:bg-red-900/40'],
                            default => ['text' => 'Proses Verifikasi', 'color' => 'text-amber-500', 'badge' => 'bg-amber-100 dark:bg-amber-900/40'],
                        };
                        $isToday = Carbon::parse($report->tanggal_laporan)->isToday();
                    @endphp
                    <div class="rounded-2xl border {{ $isToday ? 'border-[#004646]' : 'border-gray-200 dark:border-zinc-700' }} bg-white dark:bg-zinc-800 {{ $isToday ? 'shadow-[0_0_20px_rgba(0,70,70,0.4)]' : 'shadow-sm' }} p-4 flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <p class="text-gray-900 dark:text-zinc-100 font-semibold mb-0">
                                    {{ Carbon::parse($report->tanggal_laporan)->format('d M Y') }}
                                </p>
                                @if($isToday)
                                    <span class="inline-block px-1.5 py-0.5 rounded-full bg-[#004646] text-white text-[0.7rem] font-semibold align-middle">
                                        Hari ini
                                    </span>
                                @endif
                            </div>
                            <span class="text-xs px-2 py-1 rounded-full {{ $statusConfig['badge'] }} {{ $statusConfig['color'] }} font-medium">
                                {{ $statusConfig['text'] }}
                            </span>
                        </div>

                        <div class="grid grid-cols-3 text-center text-sm text-gray-800 dark:text-zinc-200 mb-4">
                            <div>
                                <p class="font-semibold">{{ number_format($report->langkah) }}</p>
                                <p class="text-gray-500 dark:text-gray-400">Langkah</p>
                            </div>
                            <div>
                                <p class="font-semibold">{{ number_format($report->co2e_reduction_kg, 2) }} kg</p>
                                <p class="text-gray-500 dark:text-gray-400">CO₂e</p>
                            </div>
                            <!-- <div>
                                <p class="font-semibold">{{ number_format($report->poin) }} pts</p>
                                <p class="text-gray-500 dark:text-gray-400">Poin</p>
                            </div> -->
                            <div>
                                <p class="font-semibold">{{ number_format($report->pohon, 1) }}</p>
                                <p class="text-gray-500 dark:text-gray-400">Pohon</p>
                            </div>
                        </div>

                        @if($report->bukti_screenshot)
                            <flux:modal name="photo-{{ $report->id }}" class="w-full max-w-5xl">
                                <div class="grid grid-cols-2 gap-4 p-4">
                                    <div class="flex items-center justify-center">
                                        <img src="{{ $report->bukti_screenshot }}" alt="Bukti Screenshot" class="w-auto max-h-[80vh] rounded-lg cursor-pointer hover:opacity-90 transition">
                                    </div>
                                    <div class="space-y-3 p-3">
                                        @if($report->status_verifikasi->value == 2)
                                            <div class="shadow p-3 rounded border border-gray-50 flex flex-col">
                                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Hasil Analisa Sistem</h3>
                                                @php $ocr = json_decode($report->ocr_result, true); @endphp
                                                @if($ocr)
                                                    <div class="space-y-2 text-sm">
                                                        @if(isset($ocr['steps']))<div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Steps:</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($ocr['steps']) }}</span></div>@endif
                                                    </div>
                                                @else
                                                    <p class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada data analisa</p>
                                                @endif
                                            </div>
                                        @else
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Hasil Analisa Sistem</h3>
                                            <p class="text-gray-500 dark:text-gray-400 text-sm">Data Tidak valid</p>
                                        @endif
                                    </div>
                                </div>
                            </flux:modal>

                            <flux:modal.trigger name="photo-{{ $report->id }}" class="w-full border border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400 py-2 rounded text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/30 flex items-center justify-center gap-2 transition">
                                <i class="ph-fill ph-file-image"></i>
                                Lihat Foto
                            </flux:modal.trigger>
                        @else
                            <div class="w-full border border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 py-2 rounded text-sm font-medium text-center">
                                Tidak ada foto
                            </div>
                        @endif
                    </div>
                @else
                    @php
                        $currentDate = $startOfWeek->copy()->addDays($index);
                        $isFuture = $currentDate->isFuture();
                        $isToday = $currentDate->isToday();
                    @endphp
                    <div class="rounded-2xl border {{ $isToday ? 'border-[#004646]' : 'border-gray-200 dark:border-zinc-700' }} bg-white dark:bg-zinc-800 {{ $isToday ? 'shadow-[0_0_10px_rgba(0,70,70,0.4)]' : 'shadow-sm' }} p-8 text-center">
                        <i class="ph {{ $isFuture ? 'ph-clock' : 'ph-file-x' }} text-4xl text-gray-400 dark:text-zinc-600 mb-2"></i>
                        @if($isToday)
                            <br>
                            <p class="inline-block px-3 py-1 rounded-full bg-[#004646] text-white text-xs font-semibold">
                                Hari ini
                            </p>
                        @endif
                        <p class="text-gray-600 dark:text-zinc-400 font-medium flex items-center justify-center gap-2">
                            {{ $isFuture ? 'Coming Soon' : 'Tidak ada data' }}
                        </p>
                        <p class="text-gray-500 dark:text-zinc-500 text-xs mt-1">{{ $currentDate->format('d M Y') }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    <x-platform-footer />


</flux:main>

@script
<script>
let activityChart = null;
let currentChartDates = @json($chartDates);

function createChart(labels, dates, steps) {
    const ctx = document.getElementById('activityChart');
    if (!ctx) return;

    if (activityChart) {
        activityChart.destroy();
    }

    const isDark = document.documentElement.classList.contains('dark');
    currentChartDates = dates;

    console.log('Update chart dengan data:', dates);

    activityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Langkah',
                data: steps,
                backgroundColor: isDark ? 'rgba(96, 165, 250, 0.8)' : 'rgba(59, 130, 246, 0.8)',
                borderColor: isDark ? 'rgb(96, 165, 250)' : 'rgb(59, 130, 246)',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: isDark ? 'rgba(31, 41, 55, 0.9)' : 'rgba(255, 255, 255, 0.9)',
                    titleColor: isDark ? '#f3f4f6' : '#1f2937',
                    bodyColor: isDark ? '#f3f4f6' : '#1f2937',
                    borderColor: isDark ? '#4b5563' : '#e5e7eb',
                    borderWidth: 1,
                    callbacks: {
                        title: function(context) {
                            return currentChartDates[context[0].dataIndex];
                        },
                        label: function(context) {
                            return 'Langkah: ' + Math.floor(context.parsed.y).toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280',
                        callback: function(value) {
                            return Math.floor(value).toLocaleString('id-ID');
                        }
                    },
                    grid: {
                        color: isDark ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)'
                    }
                },
                x: {
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

createChart(@json($chartLabels), @json($chartDates), @json($chartSteps));

$wire.on('chart-update', (event) => {
    createChart(event[0].labels, event[0].dates, event[0].steps);
});
</script>
@endscript
