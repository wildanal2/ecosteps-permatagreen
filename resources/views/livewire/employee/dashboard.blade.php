<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\{UserStatistic, DailyReport};
use Carbon\Carbon;

new #[Layout('components.layouts.app.header')]
    #[Title('Dashboard Karyawan')]
    class extends Component {

    protected $listeners = [
        'refresh-dashboard' => '$refresh',
        // 'echo:daily-report-updated' => 'handleReportUpdate'
    ];

    public function handleReportUpdate($data)
    {
        $this->dispatch('$refresh');
    }

    public function with(): array
    {
        $user = auth()->user();

        // Ambil statistik user
        $stats = UserStatistic::firstOrCreate(
            ['user_id' => $user->id],
            [
                'total_langkah' => 0,
                'total_co2e_kg' => 0,
                'total_pohon' => 0,
                'current_streak' => 0,
                'last_update' => now(),
            ]
        );

        // Ambil laporan hari ini
        $todayReport = DailyReport::where('user_id', $user->id)
            ->whereDate('tanggal_laporan', today())
            ->first();

        // Hitung progress hari ini
        $todaySteps = $todayReport?->langkah ?? 0;
        $targetSteps = 10000;
        $progressPercent = min(($todaySteps / $targetSteps) * 100, 100);

        // Chart data (7 hari terakhir)
        $chartDates = collect();
        for ($i = 6; $i >= 0; $i--) {
            $chartDates->push(now()->subDays($i));
        }
        
        $chartReports = DailyReport::where('user_id', $user->id)
            ->whereBetween('tanggal_laporan', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->get()
            ->keyBy(fn($r) => Carbon::parse($r->tanggal_laporan)->format('Y-m-d'));
        
        $chartLabels = $chartDates->map(fn($date) => $date->format('D'));
        $chartSteps = $chartDates->map(fn($date) => $chartReports->get($date->format('Y-m-d'))?->langkah ?? 0);

        return [
            'user' => $user,
            'stats' => $stats,
            'todayReport' => $todayReport,
            'todaySteps' => $todaySteps,
            'targetSteps' => $targetSteps,
            'progressPercent' => $progressPercent,
            'chartLabels' => $chartLabels,
            'chartSteps' => $chartSteps,
            'hasReportedToday' => $todayReport !== null,
        ];
    }
};

?>

<flux:main container class="">
    <div class="flex flex-col gap-6 p-6">
        {{-- Header --}}
        <div>
            <h2 class="text-xl font-semibold text-gray-800">
                Halo, <span class="text-gray-900 font-bold">{{ $user->name }}</span> ({{ $user->branch }})
            </h2>

            {{-- Alert --}}
            @if(!$hasReportedToday)
                <div class="mt-3 rounded-xl bg-yellow-100 text-yellow-700 px-4 py-3 flex items-center gap-2">
                    <flux:icon.exclamation-triangle class="w-5 h-5" />
                    <span>Anda belum mengirim laporan langkah hari ini</span>
                </div>
            @elseif($todayReport && $todayReport->status_verifikasi->value === 1)
                <div class="mt-3 rounded-xl bg-blue-100 text-blue-700 px-4 py-3 flex items-center gap-2" wire:poll.5s="$refresh">
                    <flux:icon.information-circle class="w-5 h-5" />
                    <span>Laporan Anda sedang dalam proses verifikasi</span>
                </div>
            @elseif($todayReport && $todayReport->status_verifikasi->value === 3)
                <div class="mt-3 rounded-xl bg-red-100 text-red-700 px-4 py-3 flex items-center gap-2">
                    <flux:icon.x-circle class="w-5 h-5" />
                    <span>Laporan Anda ditolak. Silakan upload ulang dengan bukti yang valid</span>
                </div>
            @endif
        </div>

        {{-- Grid Statistik Atas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">CO₂e yang dihindari:</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-globe-hemisphere-east text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($stats->total_co2e_kg, 2) }} kg</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Akumulasi Langkah:</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-footprints text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($stats->total_langkah) }} langkah</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Streak:</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-fire text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">{{ $stats->current_streak }} hari streak</p>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-4 shadow-sm border border-gray-100">
                <p class="text-sm text-gray-600 mb-3">Pohon:</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="flex items-center justify-center p-1 border border-gray-100 rounded shadow-sm">
                        <i class="ph-fill ph-tree-evergreen text-blue-600 bg-blue-200 rounded p-0.5 text-2xl flex items-center justify-center"></i>
                    </span>
                    <p class="text-lg font-semibold text-gray-900">{{ number_format($stats->total_pohon, 0) }} pohon</p>
                </div>
            </div>
        </div>

        {{-- Grid Bawah --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Walk Track --}}
            <div class="rounded-2xl bg-white p-5 shadow-sm border border-gray-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Walk Track</h3>
                    @if($todaySteps == 0)
                        <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded-full">Belum Mulai</span>
                    @elseif($todaySteps >= $targetSteps)
                        <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">Target Tercapai</span>
                    @else
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">Berjalan Sebagian</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Setiap langkah yang kamu ambil bukan hanya menambah poin, tapi juga mengurangi jejak karbon.
                </p>

                {{-- Progress --}}
                <div class="w-full bg-gray-100 rounded-full h-3 mb-3 overflow-hidden">
                    <div class="{{ $todaySteps >= $targetSteps ? 'bg-green-500' : 'bg-yellow-400' }} h-3 rounded-full transition-all" style="width: {{ $progressPercent }}%;"></div>
                </div>
                <p class="text-sm text-gray-700 mb-4">
                    Langkah hari ini <span class="font-semibold">{{ number_format($todaySteps) }}</span>/{{ number_format($targetSteps) }} langkah 🏃
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

                {{-- Chart --}}
                <canvas id="dashboardChart" class="w-full mb-4" style="max-height: 160px;"></canvas>

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
                Flux.modals().close();
            });
        });
    </script>
</flux:main>

@script
<script>
let dashboardChart = null;

function initDashboardChart() {
    const ctx = document.getElementById('dashboardChart');
    if (!ctx) return;
    
    if (dashboardChart) {
        dashboardChart.destroy();
    }
    
    const isDark = document.documentElement.classList.contains('dark');
    
    dashboardChart = new Chart(ctx, {
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

initDashboardChart();

$wire.on('$refresh', () => {
    setTimeout(() => initDashboardChart(), 100);
});
</script>
@endscript
