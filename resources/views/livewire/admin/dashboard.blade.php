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
        $today = now()->toDateString();

        return [
            'totalUsers' => User::where('user_level', 1)->count(),
            'todaySteps' => DailyReport::whereDate('tanggal_laporan', $today)->sum('langkah'),
            'totalCO2' => UserStatistic::sum('total_co2e_kg'),
            'pendingReports' => DailyReport::where('status_verifikasi', StatusVerifikasi::PENDING)->count(),
            'recentReports' => DailyReport::with('user')
                ->latest('tanggal_laporan')
                ->take(5)
                ->get(),
            'weeklyData' => DailyReport::select(
                    DB::raw('DATE(tanggal_laporan) as date'),
                    DB::raw('SUM(langkah) as total_steps')
                )
                ->where('tanggal_laporan', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
    }
};

?>

<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($totalUsers) }}</div>
                <svg class="size-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            </div>
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Total Pengguna</div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($todaySteps) }}</div>
                <svg class="size-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Langkah Hari Ini</div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($totalCO2, 2) }} kg</div>
                <svg class="size-8 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Total CO2e Dikurangi</div>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div class="text-3xl font-bold text-gray-900 dark:text-white">{{ number_format($pendingReports) }}</div>
                <svg class="size-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">Menunggu Verifikasi</div>
        </div>
    </div>

    <!-- Charts & Recent Reports -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Weekly Activity Chart -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">Aktivitas 7 Hari Terakhir</h3>
            <div class="space-y-3">
                @php
                    $maxSteps = $weeklyData->max('total_steps') ?: 1;
                @endphp
                @forelse($weeklyData as $data)
                    <div class="space-y-1">
                        <div class="flex justify-between text-sm text-gray-700 dark:text-gray-300">
                            <span>{{ \Carbon\Carbon::parse($data->date)->format('d M') }}</span>
                            <span class="font-semibold">{{ number_format($data->total_steps) }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-2 rounded-full bg-blue-500" style="width: {{ ($data->total_steps / $maxSteps) * 100 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">Belum ada data aktivitas</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Reports -->
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">Laporan Terbaru</h3>
            <div class="space-y-3">
                @forelse($recentReports as $report)
                    <div class="flex items-center justify-between border-b pb-3 last:border-0 dark:border-gray-700">
                        <div class="flex-1">
                            <div class="font-semibold text-gray-900 dark:text-white">{{ $report->user->name }}</div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ number_format($report->langkah) }} langkah • {{ $report->tanggal_laporan->format('d M Y') }}
                            </div>
                        </div>
                        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ match($report->status_verifikasi) {
                            App\Enums\StatusVerifikasi::DIVERIFIKASI => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            App\Enums\StatusVerifikasi::DITOLAK => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            default => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200'
                        } }}">
                            {{ $report->status_verifikasi->label() }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-600 dark:text-gray-400">Belum ada laporan</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
