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

        // Chart data (7 hari terakhir)
        $chartDates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $chartDates->push(now()->subDays($i));
        }
        
        $chartReports = DailyReport::where('user_id', $userId)
            ->whereBetween('tanggal_laporan', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->tanggal_laporan)->format('Y-m-d'));
        
        $chartLabels = $chartDates->map(fn($date) => $date->format('D'));
        $chartSteps = $chartDates->map(fn($date) => $chartReports->get($date->format('Y-m-d'))?->langkah ?? 0);

        return [
            'reports' => $reports,
            'chartLabels' => $chartLabels,
            'chartSteps' => $chartSteps,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
        ];
    }

    public function nextWeek()
    {
        $this->weekOffset++;
    }

    public function prevWeek()
    {
        if ($this->weekOffset > 0) {
            $this->weekOffset--;
        }
    }
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

            {{-- Chart --}}
            <canvas id="activityChart" class="w-full" style="max-height: 200px;"></canvas>
        </div>

        {{-- Search + Button --}}
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <input
                type="text"
                wire:model.live="search"
                placeholder="Cari Tanggal..."
                class="w-full md:w-1/3 rounded-xl border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 placeholder-gray-400 px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
            />
            <flux:button icon="plus" variant="primary" color="blue" href="#">
                Tambah Laporan
            </flux:button>
        </div>

        {{-- Navigasi Minggu --}}
        <div class="flex items-center justify-between">
            <button wire:click="nextWeek" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600">
                ← Minggu Lalu
            </button>
            <span class="text-gray-700 dark:text-gray-300 font-medium">
                {{ $startOfWeek->format('d M') }} - {{ $endOfWeek->format('d M Y') }}
            </span>
            <button wire:click="prevWeek" @if($weekOffset == 0) disabled @endif class="px-4 py-2 bg-gray-200 dark:bg-gray-700 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                Minggu Depan →
            </button>
        </div>

        {{-- List Laporan --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($reports as $index => $report)
                @if($report)
                    @php
                        $statusConfig = match($report->status_verifikasi->value) {
                            1 => ['text' => 'Proses Verifikasi', 'color' => 'text-amber-500', 'badge' => 'bg-amber-100 dark:bg-amber-900/40'],
                            2 => ['text' => 'Diverifikasi', 'color' => 'text-emerald-500', 'badge' => 'bg-emerald-100 dark:bg-emerald-900/40'],
                            3 => ['text' => 'Tidak Valid', 'color' => 'text-red-500', 'badge' => 'bg-red-100 dark:bg-red-900/40'],
                            default => ['text' => 'Proses Verifikasi', 'color' => 'text-amber-500', 'badge' => 'bg-amber-100 dark:bg-amber-900/40'],
                        };
                    @endphp
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-4 flex flex-col justify-between">
                        <div class="flex items-center justify-between mb-3">
                            <p class="text-gray-900 dark:text-gray-100 font-semibold">{{ Carbon::parse($report->tanggal_laporan)->format('d M Y') }}</p>
                            <span class="text-xs px-2 py-1 rounded-full {{ $statusConfig['badge'] }} {{ $statusConfig['color'] }} font-medium">
                                {{ $statusConfig['text'] }}
                            </span>
                        </div>

                        <div class="grid grid-cols-4 text-center text-sm text-gray-800 dark:text-gray-200 mb-4">
                            <div>
                                <p class="font-semibold">{{ number_format($report->langkah) }}</p>
                                <p class="text-gray-500 dark:text-gray-400">Langkah</p>
                            </div>
                            <div>
                                <p class="font-semibold">{{ number_format($report->co2e_reduction_kg, 2) }} kg</p>
                                <p class="text-gray-500 dark:text-gray-400">CO₂e</p>
                            </div>
                            <div>
                                <p class="font-semibold">{{ number_format($report->poin) }} pts</p>
                                <p class="text-gray-500 dark:text-gray-400">Poin</p>
                            </div>
                            <div>
                                <p class="font-semibold">{{ number_format($report->pohon, 1) }}</p>
                                <p class="text-gray-500 dark:text-gray-400">Pohon</p>
                            </div>
                        </div>

                        @if($report->bukti_screenshot)
                            <button onclick="window.open('{{ Storage::url($report->bukti_screenshot) }}', '_blank')" class="w-full border border-blue-500 text-blue-600 dark:text-blue-400 dark:border-blue-400 py-2 rounded text-sm font-medium hover:bg-blue-50 dark:hover:bg-blue-900/30 flex items-center justify-center gap-2 transition">
                                <i class="ph-fill ph-file-image"></i>
                                Lihat Foto
                            </button>
                        @else
                            <div class="w-full border border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 py-2 rounded text-sm font-medium text-center">
                                Tidak ada foto
                            </div>
                        @endif
                    </div>
                @else
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-sm p-8 text-center">
                        <i class="ph ph-file-x text-4xl text-gray-400 dark:text-gray-600 mb-2"></i>
                        <p class="text-gray-600 dark:text-gray-400 font-medium">Tidak ada data</p>
                        <p class="text-gray-500 dark:text-gray-500 text-xs mt-1">{{ $startOfWeek->copy()->addDays($index)->format('d M Y') }}</p>
                    </div>
                @endif
            @endforeach
        </div>
    </div>
</flux:main>

@script
<script>
let activityChart = null;

function initChart() {
    const ctx = document.getElementById('activityChart');
    if (!ctx) return;
    
    if (activityChart) {
        activityChart.destroy();
    }
    
    const isDark = document.documentElement.classList.contains('dark');
    
    activityChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: @json($chartLabels),
            datasets: [{
                label: 'Langkah',
                data: @json($chartSteps),
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
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: isDark ? '#9ca3af' : '#6b7280'
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

initChart();

$wire.on('$refresh', () => {
    setTimeout(() => initChart(), 100);
});
</script>
@endscript
