<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\{User, DailyReport, UserStatistic};
use App\Enums\{StatusVerifikasi, Directorate, EmissionFactor, TreeCo2Absorption};
use Illuminate\Support\Facades\DB;

new #[Layout('components.layouts.app')]
    #[Title('Dashboard Admin')]
    class extends Component {

    public function with(): array
    {
        return [
            'totalUsers' => User::where('user_level', 1)->count(),
            'totalSteps' => DailyReport::sum('langkah'),
            'totalCO2' => UserStatistic::sum('total_co2e_kg'),
            'totalTrees' => UserStatistic::sum('total_co2e_kg') / TreeCo2Absorption::default()->getValue(),
            'dailyActivity' => DailyReport::select(
                    DB::raw('DATE(tanggal_laporan) as date'),
                    DB::raw('SUM(langkah) as total_steps'),
                    DB::raw('ROUND(SUM(langkah) * 0.75 / 1000 * ' . EmissionFactor::default()->getValue() . ', 2) as co2e')
                )
                ->where('tanggal_laporan', '>=', now()->subDays(7))
                ->groupBy(DB::raw('DATE(tanggal_laporan)'))
                ->orderBy('date')
                ->get(),
            'directoratePerformance' => User::select(
                    'users.directorate',
                    DB::raw('SUM(user_statistics.total_langkah) as total_steps'),
                    DB::raw('SUM(user_statistics.total_co2e_kg) as co2e'),
                    DB::raw('ROUND(SUM(user_statistics.total_co2e_kg) / ' . TreeCo2Absorption::default()->getValue() . ', 2) as trees')
                )
                ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id')
                ->where('users.user_level', 1)
                ->whereNotNull('users.directorate')
                ->groupBy('users.directorate')
                ->orderByDesc('total_steps')
                ->get(),
            'topIndividuals' => User::select(
                    'users.name',
                    'users.directorate',
                    'user_statistics.total_co2e_kg',
                    'user_statistics.total_langkah',
                    'user_statistics.current_streak',
                    DB::raw('ROUND(user_statistics.total_co2e_kg / ' . TreeCo2Absorption::default()->getValue() . ', 2) as trees')
                )
                ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id')
                ->where('users.user_level', 1)
                ->orderByDesc('user_statistics.total_langkah')
                ->take(10)
                ->get(),
        ];
    }
};

?>

<div>
    <div class="py-5 mb-5">
        <flux:heading size="xl">Dashboard Admin - Permata Green Steps</flux:heading>
        <flux:text class="mt-2">
            Pantau progres seluruh peserta dalam membangun kebiasaan hijau melalui langkah-langkah kecil yang berdampak besar.
        </flux:text>
    </div>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Peserta</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mt-2">{{ number_format($totalUsers) }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">peserta</p>
                    </div>
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-users text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Total CO₂e Dihindari</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mt-2">{{ number_format($totalCO2, 1) }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">kg CO₂e</p>
                    </div>
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-globe-hemisphere-east text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Total Langkah</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mt-2">{{ number_format($totalSteps) }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">langkah</p>
                    </div>
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-footprints text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Estimasi Pohon</p>
                        <h2 class="text-3xl font-bold text-zinc-900 dark:text-zinc-100 mt-2">{{ number_format($totalTrees, 2) }}</h2>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">pohon</p>
                    </div>
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-tree-evergreen text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Activity -->
        <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
            <div class="mb-4">
                <flux:heading size="lg">Tren Aktivitas & Dampak Lingkungan</flux:heading>
                <flux:subheading>Lihat bagaimana langkah dan pengurangan emisi berkembang dari waktu ke waktu.</flux:subheading>
            </div>
            <div style="height: 300px;" wire:ignore>
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Treemap + Kinerja -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Treemap Direktorat -->
            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="mb-4">
                    <flux:heading size="lg">Treemap Direktorat</flux:heading>
                    <flux:subheading>Visualisasi performa direktorat teratas.</flux:subheading>
                </div>
                <div style="height: 500px;" wire:ignore>
                    <canvas id="treemapChart"></canvas>
                </div>
            </div>

            <!-- Tabel Kinerja -->
            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="mb-4">
                    <flux:heading size="lg">Kinerja Tiap Direktorat</flux:heading>
                    <flux:subheading>Peringkat direktorat berdasarkan total langkah.</flux:subheading>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Direktorat</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Langkah</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">CO₂e</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Pohon</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($directoratePerformance as $index => $perf)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $index + 1 }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $perf->directorate?->label() ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($perf->total_steps) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($perf->co2e, 1) }} kg</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($perf->trees, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top 10 Table -->
        <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
            <div class="mb-4">
                <flux:heading size="lg">Top 10 Individu Teraktif</flux:heading>
                <flux:subheading>Peserta dengan total langkah tertinggi.</flux:subheading>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Peringkat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Direktorat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Langkah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">CO₂e Dihindari</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Est. Pohon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Streak</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($topIndividuals as $index => $individual)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    @if($index + 1 == 1)
                                        🥇 {{ $index + 1 }}
                                    @elseif($index + 1 == 2)
                                        🥈 {{ $index + 1 }}
                                    @elseif($index + 1 == 3)
                                        🥉 {{ $index + 1 }}
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $individual->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $individual->directorate?->label() ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($individual->total_langkah) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($individual->total_co2e_kg, 2) }} kg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($individual->trees, 2) }} pohon</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $individual->current_streak }} hari</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@once
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap@2.3.1"></script>
@endonce

<script>
(function() {
    if (!window.activityChartInstance) {
        window.activityChartInstance = null;
    }

    function initActivityChart() {
        const ctx = document.getElementById('activityChart');
        if (ctx) {
            // Destroy existing chart instance
            if (window.activityChartInstance) {
                window.activityChartInstance.destroy();
            }

            window.activityChartInstance = new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @js($dailyActivity->map(fn($d) => \Carbon\Carbon::parse($d->date)->format('d M'))),
                datasets: [
                    {
                        label: 'Jumlah Langkah',
                        data: @js($dailyActivity->pluck('total_steps')),
                        backgroundColor: 'rgba(47, 43, 255, 1.0)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'CO₂e (kg)',
                        type: 'line',
                        data: @js($dailyActivity->pluck('co2e')),
                        borderWidth: 3,
                        borderColor: 'rgb(34,197,94)',
                        backgroundColor: 'rgba(34,197,94,0.1)',
                        tension: 0.4,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    if (context.datasetIndex === 0) {
                                        label += context.parsed.y.toLocaleString('id-ID');
                                    } else {
                                        label += context.parsed.y.toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' kg';
                                    }
                                }
                                return label;
                            },
                            afterLabel: function(context) {
                                if (context.datasetIndex === 1) {
                                    const trees = (context.parsed.y / {{ TreeCo2Absorption::default()->getValue() }}).toFixed(2);
                                    return 'Setara: ' + trees + ' pohon';
                                }
                                return null;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Jumlah Langkah'
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID');
                            }
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'CO₂e (kg)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1});
                            }
                        }
                    },
                }
            }
        });
        }
    }

    // Initialize chart
    initActivityChart();

    // Re-initialize after Livewire navigation
    document.addEventListener('livewire:navigated', initActivityChart);
})();
</script>

<script>
(function() {
    if (!window.treemapChartInstance) {
        window.treemapChartInstance = null;
    }

    function initTreemapChart() {
        const ctx = document.getElementById('treemapChart');
        if (ctx) {
            if (window.treemapChartInstance) {
                window.treemapChartInstance.destroy();
            }

            const rawData = {!! json_encode($directoratePerformance->map(function($perf) {
                return [
                    'label' => $perf->directorate?->label() ?? '-',
                    'value' => (int)$perf->total_steps,
                    'co2e' => (float)$perf->co2e,
                    'trees' => (float)$perf->trees
                ];
            })->values()) !!};
            console.log(rawData);

            const colors = [
                'rgba(0, 73, 70, 0.9)',
                'rgba(16, 185, 129, 0.9)',
                'rgba(34, 197, 94, 0.9)',
                'rgba(74, 222, 128, 0.9)',
                'rgba(134, 239, 172, 0.9)',
                'rgba(187, 247, 208, 0.8)',
                'rgba(220, 252, 231, 0.8)',
                'rgba(5, 150, 105, 0.8)',
                'rgba(6, 95, 70, 0.8)',
                'rgba(4, 120, 87, 0.8)'
            ];

            window.treemapChartInstance = new Chart(ctx.getContext('2d'), {
                type: 'treemap',
                data: {
                    datasets: [{
                        label: 'Direktorat Performance',
                        tree: rawData,
                        key: 'value',
                        backgroundColor: (ctx) => {
                            if (ctx.type !== 'data') return 'transparent';
                            return colors[ctx.dataIndex % colors.length];
                        },
                        borderWidth: 2,
                        borderColor: 'white',
                        spacing: 1,
                        labels: {
                            display: true,
                            align: 'center',
                            position: 'middle',
                            color: 'white',
                            font: {
                                size: 11,
                                weight: 'bold'
                            },
                            formatter: (ctx) => {
                                if (ctx.type !== 'data') return '';
                                const label = ctx.raw._data.label;
                                const words = label.split(' ');
                                
                                // Pecah label menjadi multi-line untuk keterbacaan
                                if (words.length > 2) {
                                    return [words.slice(0, 2).join(' '), words.slice(2).join(' ')];
                                } else if (words.length === 2) {
                                    return [words[0], words[1]];
                                }
                                return label;
                            }
                        }
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
                            callbacks: {
                                title: (context) => {
                                    return context[0].raw._data.label || 'Unknown';
                                },
                                label: (context) => {
                                    const data = context.raw._data;
                                    if (!data) return [];
                                    return [
                                        'Langkah: ' + (data.value || 0).toLocaleString('id-ID'),
                                        'CO₂e: ' + (data.co2e || 0).toLocaleString('id-ID', {minimumFractionDigits: 1, maximumFractionDigits: 1}) + ' kg',
                                        'Pohon: ' + (data.trees || 0).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2})
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    initTreemapChart();
    document.addEventListener('livewire:navigated', initTreemapChart);
})();
</script>
