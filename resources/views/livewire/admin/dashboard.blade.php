<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\{User, DailyReport, UserStatistic};
use App\Enums\StatusVerifikasi;
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
            'totalTrees' => round(UserStatistic::sum('total_co2e_kg') * 77), // estimasi konversi pohon
            'dailyActivity' => DailyReport::select(
                    DB::raw('DATE(tanggal_laporan) as date'),
                    DB::raw('SUM(langkah) as total_steps'),
                    DB::raw('SUM(langkah) * 0.0004 as co2e')
                )
                ->where('tanggal_laporan', '>=', now()->subDays(7))
                ->groupBy(DB::raw('DATE(tanggal_laporan)'))
                ->orderBy('date')
                ->get(),
            'directoratePerformance' => User::select(
                    'users.directorate',
                    DB::raw('SUM(user_statistics.total_langkah) as total_steps'),
                    DB::raw('SUM(user_statistics.total_co2e_kg) as co2e'),
                    DB::raw('CAST(SUM(user_statistics.total_co2e_kg) * 77 AS INTEGER) as trees')
                )
                ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id')
                ->where('users.user_level', 1)
                ->whereNotNull('users.directorate')
                ->groupBy('users.directorate')
                ->orderByDesc('total_steps')
                ->take(10)
                ->get(),
            'topIndividuals' => User::select(
                    'users.name',
                    'users.directorate',
                    'user_statistics.total_co2e_kg',
                    'user_statistics.total_langkah',
                    'user_statistics.current_streak',
                    DB::raw('CAST(user_statistics.total_co2e_kg * 77 AS INTEGER) as trees')
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

<div class="p-8 max-w-[1400px] mx-auto">
    <!-- Header Title -->
    <h1 class="text-2xl font-semibold mb-2">Permata Green Steps - Dashboard Admin</h1>
    <p class="text-gray-600 mb-6">
        Pantau progres seluruh peserta dalam membangun kebiasaan hijau melalui langkah-langkah kecil yang berdampak besar.
    </p>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-gray-600">Total Peserta</p>
            <h2 class="text-3xl font-bold">{{ number_format($totalUsers) }} peserta</h2>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-gray-600">Total CO₂e</p>
            <h2 class="text-3xl font-bold">{{ number_format($totalCO2, 1) }} kg CO₂e</h2>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-gray-600">Total Langkah</p>
            <h2 class="text-3xl font-bold">{{ number_format($totalSteps) }} langkah</h2>
        </div>

        <div class="bg-white rounded-xl shadow p-6">
            <p class="text-gray-600">Konversi Pohon</p>
            <h2 class="text-3xl font-bold">{{ number_format($totalTrees) }} pohon</h2>
        </div>
    </div>

    <!-- Chart Activity -->
    <div class="bg-white rounded-xl shadow p-6 mb-8">
        <h2 class="text-xl font-semibold mb-2">Tren Aktivitas & Dampak Lingkungan</h2>
        <p class="text-gray-600 mb-4">Lihat bagaimana langkah dan pengurangan emisi berkembang dari waktu ke waktu.</p>
        <div style="height: 300px;" wire:ignore>
            <canvas id="activityChart"></canvas>
        </div>
    </div>

    <!-- Heatmap + Kinerja -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Heatmap Cabang -->
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-semibold mb-4">Heatmap Cabang</h2>
            <div class="grid grid-cols-3 gap-2 text-white text-center font-medium">
                @php
                    $branches = $directoratePerformance->take(6);
                    $colors = ['bg-green-900', 'bg-green-700', 'bg-green-800', 'bg-green-500', 'bg-green-600', 'bg-green-500'];
                @endphp
                @foreach($branches as $index => $branch)
                    <div class="{{ $colors[$index] ?? 'bg-green-600' }} p-4 rounded">{{ $branch->directorate }}</div>
                @endforeach
            </div>
        </div>

        <!-- Tabel Kinerja -->
        <div class="bg-white rounded-xl shadow p-6 overflow-x-auto">
            <h2 class="text-xl font-semibold mb-4">Kinerja Tiap Direktorat</h2>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b">
                        <th class="py-2">No</th>
                        <th>Direktorat</th>
                        <th>Total Langkah</th>
                        <th>CO₂e Dihindari</th>
                        <th>Est. Pohon</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($directoratePerformance as $index => $perf)
                        <tr class="border-b">
                            <td class="py-2">{{ $index + 1 }}</td>
                            <td>{{ $perf->directorate }}</td>
                            <td>{{ number_format($perf->total_steps) }}</td>
                            <td>{{ number_format($perf->co2e, 1) }} kg</td>
                            <td>{{ number_format($perf->trees) }} pohon</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top 10 Table -->
    <div class="bg-white rounded-xl shadow p-6 overflow-x-auto mb-10">
        <h2 class="text-xl font-semibold mb-4">Top 10 Individu, Langkah Teraktif Minggu Ini</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="border-b">
                    <th class="py-2">Peringkat</th>
                    <th>Nama</th>
                    <th>Direktorat</th>
                    <th>CO₂e Dihindari</th>
                    <th>Est. Pohon</th>
                    <th>Jumlah Streak</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topIndividuals as $index => $individual)
                    <tr class="border-b">
                        <td class="py-2">{{ $index + 1 }}</td>
                        <td>{{ $individual->name }}</td>
                        <td>{{ $individual->directorate ?? '-' }}</td>
                        <td>{{ number_format($individual->total_co2e_kg, 1) }} kg</td>
                        <td>{{ number_format($individual->trees) }} pohon</td>
                        <td>{{ $individual->current_streak }} hari</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('activityChart');
    if (ctx) {
        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: @js($dailyActivity->map(fn($d) => \Carbon\Carbon::parse($d->date)->format('d M'))),
                datasets: [
                    {
                        label: 'Jumlah Langkah',
                        data: @js($dailyActivity->pluck('total_steps')),
                        backgroundColor: 'rgba(56, 189, 248, 0.6)',
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
});
</script>
