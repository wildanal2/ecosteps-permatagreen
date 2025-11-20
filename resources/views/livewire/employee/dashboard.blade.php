<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use App\Models\{UserStatistic, DailyReport};
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

new #[Layout('components.layouts.app.header')]
    #[Title('Dashboard Karyawan')]
    class extends Component {

    public $previousStatus = null;
    public $showAllDirectorates = false;

    protected $listeners = [
        'refresh-dashboard' => '$refresh',
        'chart-updated' => '$refresh',
        // 'echo:daily-report-updated' => 'handleReportUpdate'
    ];

    public function handleReportUpdate($data)
    {
        $this->dispatch('chart-updated');
    }

    public function requestManualVerification()
    {
        $todayReport = DailyReport::where('user_id', auth()->id())
            ->whereDate('tanggal_laporan', today())
            ->first();

        if ($todayReport && $todayReport->status_verifikasi->value === 3) {
            $todayReport->update([
                'status_verifikasi' => \App\Enums\StatusVerifikasi::PENDING,
                'manual_verification_requested' => true,
                'manual_verification_requested_at' => now(),
            ]);

            flash()->info('Permintaan verifikasi manual telah diajukan. Mohon tunggu admin untuk memverifikasi.');
        }
    }

    public function getChartData()
    {
        $user = auth()->user();
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
            'labels' => $chartLabels->values(),
            'steps' => $chartSteps->values()
        ];
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

        // Detect status change from pending (1) to approved (2) or rejected (3)
        $currentStatus = $todayReport?->status_verifikasi?->value;
        if ($this->previousStatus === 1 && in_array($currentStatus, [2, 3])) {
            $this->dispatch('status-changed');
        }
        $this->previousStatus = $currentStatus;

        // Leaderboard data
        $leaderboard = UserStatistic::with('user')
            ->join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->orderByDesc('user_statistics.total_langkah')
            ->select('user_statistics.*')
            ->take(10)
            ->get();

        // User ranking
        $userRank = UserStatistic::join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->where('user_statistics.total_langkah', '>', $stats->total_langkah)
            ->count() + 1;

        // User above and below
        $userAbove = UserStatistic::with('user')
            ->join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->where('user_statistics.total_langkah', '>', $stats->total_langkah)
            ->orderBy('user_statistics.total_langkah')
            ->select('user_statistics.*')
            ->first();

        $userBelow = UserStatistic::with('user')
            ->join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->where('user_statistics.total_langkah', '<', $stats->total_langkah)
            ->orderByDesc('user_statistics.total_langkah')
            ->select('user_statistics.*')
            ->first();

        // Top Direktorat
        $allDirectorates = UserStatistic::join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->where('users.directorate', '!=', 0)
            ->selectRaw('users.directorate, SUM(user_statistics.total_langkah) as total_langkah, SUM(user_statistics.total_co2e_kg) as total_co2e_kg')
            ->groupBy('users.directorate')
            ->orderByDesc('total_langkah')
            ->get();

        $topDirectorates = $this->showAllDirectorates ? $allDirectorates : $allDirectorates->take(10);
        $totalDirectorates = $allDirectorates->count();

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
            'leaderboard' => $leaderboard,
            'userRank' => $userRank,
            'userAbove' => $userAbove,
            'userBelow' => $userBelow,
            'topDirectorates' => $topDirectorates,
            'totalDirectorates' => $totalDirectorates,
        ];
    }
};

?>

<flux:main container class="">
    <div class="flex flex-col gap-6 p-6">
        {{-- Header --}}
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-zinc-100">
                Selamat datang di Walk for Elephant, <span class="text-gray-900 dark:text-zinc-100 font-bold">{{ $user->name }}</span>
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400 mb-4">
                Lihat progres langkah anda hari ini dan sejauh mana anda berkontribusi dalam gerakan hijau.
            </p>

            {{-- Alert --}}
            @if(!$hasReportedToday)
                <div class="mt-3 rounded-xl bg-yellow-100 text-yellow-700 px-4 py-3 flex items-center gap-2">
                    <flux:icon.exclamation-triangle class="w-5 h-5" />
                    <span>Anda belum mengirim laporan langkah hari ini</span>
                </div>
            @elseif($todayReport && $todayReport->status_verifikasi->value === 1)
                <div class="mt-3 rounded-xl bg-blue-100 text-blue-700 px-4 py-3 flex items-center gap-2" wire:poll.5s="$refresh">
                    <flux:icon.information-circle class="w-5 h-5" />
                    <span>Laporan Anda sedang dalam proses verifikasi {{ $todayReport->manual_verification_requested ? "oleh Admin":"sistem" }}</span>
                </div>
            @elseif($todayReport && $todayReport->status_verifikasi->value === 3)
                <div class="mt-3 rounded-xl flex justify-between items-center bg-red-100 text-red-700 px-4 py-3">
                    <div class="flex items-center gap-2 mb-2">
                        <flux:icon.x-circle class="w-5 h-5" />
                        <span>Langkah anda gagal terverifikasi</span>
                    </div>
                    @if(!$todayReport->manual_verification_requested)
                        <button
                            wire:click="requestManualVerification"
                            size="sm"
                            class="bg-red-600 text-white px-4 py-2 rounded-lg text-xs font-medium hover:bg-red-700 transition">
                            Ajukan Verifikasi Manual
                        </button>
                    @else
                        <p class="mt-2 text-sm text-red-600">✓ Verifikasi manual telah diajukan</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Grid Atas --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            {{-- Progres Harian --}}
            <div class="rounded-2xl bg-white dark:bg-zinc-800 p-5 shadow-sm border border-gray-100 dark:border-zinc-700">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-zinc-100">Progres Harian</h3>
                    @if($todaySteps == 0)
                        <span class="text-xs bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 px-2 py-1 rounded-full">Belum Mulai</span>
                    @elseif($todaySteps >= $targetSteps)
                        <span class="text-xs bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300 px-2 py-1 rounded-full">Target Tercapai</span>
                    @else
                        <span class="text-xs bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 px-2 py-1 rounded-full">Berjalan Sebagian</span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 dark:text-zinc-400 mb-4">
                    Setiap langkah yang anda ambil bukan hanya menambah poin, tapi juga mengurangi jejak karbon
                </p>

                {{-- Progress --}}
                <div class="relative w-full rounded-md h-10 mb-4 overflow-hidden">
                    <div class="hidden absolute h-10 transition-all" style="width: {{ $progressPercent }}%; background: {{ $todaySteps >= $targetSteps ? 'repeating-linear-gradient(90deg, #004646 0px, #004646 8px, transparent 8px, transparent 10px)' : 'repeating-linear-gradient(90deg, #facc15 0px, #facc15 8px, transparent 8px, transparent 10px)' }};"></div>
                    <div class="absolute inset-0 flex items-center justify-center z-10">
                        <p class="text-xs text-gray-800 dark:text-zinc-300 font-medium px-2 py-1 rounded-md border border-gray-50
                                backdrop-blur-md bg-white/45 dark:bg-white/10 shadow-md">
                            Langkah hari ini
                            <span class="font-bold text-lg mx-1">
                                {{ number_format($todaySteps, 0, ',', '.') }}
                            </span>
                            /{{ number_format($targetSteps, 0, ',', '.') }} langkah 🏃
                        </p>

                    </div>
                </div>

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
            <div class="rounded-2xl bg-white dark:bg-zinc-800 p-5 shadow-sm border border-gray-100 dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-zinc-100 mb-2">Lihat Rekap Mingguan Anda</h3>
                <p class="text-sm text-gray-600 dark:text-zinc-400 mb-4">
                    Pantau progres langkah dan dampak hijau yang sudah anda ciptakan minggu ini!
                </p>

                {{-- Chart --}}
                <div class="w-full mb-4" style="height: 160px;" wire:ignore>
                    <canvas id="dashboardChart"></canvas>
                </div>
            </div>
        </div>

        {{-- Grid Bawah: Leaderboard --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- LEFT CARD: User Ranking --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-zinc-700">
                <div class="flex flex-col items-center">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 w-full">
                        <div class="flex flex-col items-center justify-center">
                            <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] mb-4 flex items-center justify-center">
                                <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-12 h-12 flex items-center justify-center">
                                    <i class="ph-fill ph-trophy text-[#004946] text-3xl"></i>
                                </div>
                            </div>

                            @if($user->profile_photo)
                                <img src="{{ Storage::disk('s3')->url($user->profile_photo) }}" class="w-20 h-20 rounded-lg mb-3 object-cover">
                            @else
                                <div class="w-20 h-20 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-2xl font-bold mb-3">
                                    {{ $user->initials() }}
                                </div>
                            @endif

                            <h2 class="text-xl font-semibold text-center">{{ Str::limit($user->name, 25) }}</h2>
                            <span class="mt-2 px-4 py-1 border border-[#004946] dark:border-green-600 bg-[#f1fffa] dark:bg-green-900/20 rounded-lg text-gray-700 dark:text-green-300 text-sm">
                                Peringkat #{{ $userRank }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-3 justify-center mt-6 sm:mt-0">
                            @if($userAbove)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-800 dark:border dark:border-zinc-700 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        @if($userAbove->user->profile_photo)
                                            <img src="{{ Storage::disk('s3')->url($userAbove->user->profile_photo) }}" class="w-10 h-10 rounded-full object-cover">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold">
                                                {{ $userAbove->user->initials() }}
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-medium text-sm">{{ Str::limit($userAbove->user->name, 15) }}</p>
                                            <span class="px-2 py-0.5 border border-gray-300 rounded-full text-xs">
                                                {{ number_format($userAbove->total_langkah, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-green-700 font-semibold text-xl">#{{ $userRank - 1 }}</p>
                                </div>
                            @endif

                            @if($userBelow)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-zinc-800 dark:border dark:border-zinc-700 rounded-xl">
                                    <div class="flex items-center gap-3">
                                        @if($userBelow->user->profile_photo)
                                            <img src="{{ Storage::disk('s3')->url($userBelow->user->profile_photo) }}" class="w-10 h-10 rounded-full object-cover">
                                        @else
                                            <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold">
                                                {{ $userBelow->user->initials() }}
                                            </div>
                                        @endif
                                        <div>
                                            <p class="font-medium text-sm text-gray-900 dark:text-zinc-100">{{ Str::limit($userBelow->user->name, 15) }}</p>
                                            <span class="px-2 py-0.5 border border-gray-300 rounded-full text-xs">
                                                {{ number_format($userBelow->total_langkah, 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-green-700 dark:text-green-400 font-semibold text-xl">#{{ $userRank + 1 }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6 w-full">
                        <div class="flex items-center gap-3 p-4 border-[2px] border-gray-50 dark:border dark:border-zinc-700 rounded-xl ">
                            <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                                <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                                    <i class="ph-fill ph-fire text-[#004946] text-2xl"></i>
                                </div>
                            </div>
                            <p class="font-medium text-sm">{{ $stats->current_streak }} hari berturut-turut</p>
                        </div>

                        <div class="flex items-center gap-3 p-4 border-[2px] border-gray-50 dark:border dark:border-zinc-700 rounded-xl ">
                            <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                                <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                                    <i class="ph-fill ph-globe-hemisphere-east text-[#004946] text-2xl"></i>
                                </div>
                            </div>
                            <p class="font-medium text-sm text-gray-900 dark:text-zinc-100">{{ number_format($stats->total_co2e_kg, 2, ',', '.') }} kg CO₂e dihindari</p>
                        </div>

                        <div class="flex items-center gap-3 p-4 border-[2px] border-gray-50 dark:border dark:border-zinc-700 rounded-xl ">
                            <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                                <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                                    <i class="ph-fill ph-footprints text-[#004946] text-2xl"></i>
                                </div>
                            </div>
                            <p class="font-medium text-sm text-gray-900 dark:text-zinc-100">{{ number_format($stats->total_langkah, 0, ',', '.') }} langkah</p>
                        </div>

                        <div class="flex items-center gap-3 p-4 border-[2px] border-gray-50 dark:border dark:border-zinc-700 rounded-xl ">
                            <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                                <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                                    <i class="ph-fill ph-tree-evergreen text-[#004946] text-2xl"></i>
                                </div>
                            </div>
                            <p class="font-medium text-sm text-gray-900 dark:text-zinc-100">{{ number_format($stats->total_pohon, 0, ',', '.') }} pohon</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- RIGHT CARD: TOP 10 --}}
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-zinc-700">
                <h2 class="text-2xl font-semibold mb-4 text-gray-900 dark:text-zinc-100">10 Permatabankers Teratas</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-gray-700 dark:text-zinc-300">
                        <thead>
                            <tr class="border-b text-sm text-gray-500 dark:text-zinc-400">
                                <th class="py-2">Peringkat</th>
                                <th>Nama</th>
                                <th>Total Langkah</th>
                                <th>CO₂e</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            @foreach($leaderboard as $index => $leader)
                                <tr class="border-b dark:border-zinc-700">
                                    <td class="py-2">#{{ $index + 1 }}</td>
                                    <td>{{ Str::limit($leader->user->name, 20) }}</td>
                                    <td>{{ number_format($leader->total_langkah, 0, ',', '.') }}</td>
                                    <td>{{ number_format($leader->total_co2e_kg, 1, ',', '.') }} kg</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Card: Top Direktorat --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 border border-gray-100 dark:border-zinc-700">
            <h2 class="text-2xl font-semibold mb-4 text-gray-900 dark:text-zinc-100">{{ $showAllDirectorates ? 'Semua' : '10' }} Direktorat Teratas</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-gray-700 dark:text-zinc-300">
                    <thead>
                        <tr class="border-b text-sm text-gray-500 dark:text-zinc-400">
                            <th class="py-2">Peringkat</th>
                            <th>Direktorat</th>
                            <th>Total Langkah</th>
                            <th>CO₂e</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        @foreach($topDirectorates as $index => $directorate)
                            <tr class="border-b dark:border-zinc-700">
                                <td class="py-2">#{{ $index + 1 }}</td>
                                <td>{{ \App\Enums\Directorate::from($directorate->directorate)->label() }}</td>
                                <td>{{ number_format($directorate->total_langkah, 0, ',', '.') }}</td>
                                <td>{{ number_format($directorate->total_co2e_kg, 1, ',', '.') }} kg</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($totalDirectorates > 10)
                <button wire:click="$toggle('showAllDirectorates')" class="w-full mt-4 bg-gray-100 dark:bg-zinc-700 py-2 rounded-xl text-gray-700 dark:text-zinc-300 font-medium hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                    {{ $showAllDirectorates ? 'Tampilkan Lebih Sedikit' : 'Tampilkan Semua (' . $totalDirectorates . ')' }}
                </button>
            @endif
        </div>
    </div>

    <livewire:employee.report-upload-component />

    <x-platform-footer />

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

function initDashboardChart(labels, steps) {
    const ctx = document.getElementById('dashboardChart');
    if (!ctx) return;

    const isDark = document.documentElement.classList.contains('dark');

    if (dashboardChart) {
        dashboardChart.data.labels = labels;
        dashboardChart.data.datasets[0].data = steps;
        dashboardChart.update();
        return;
    }

    dashboardChart = new Chart(ctx, {
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
                legend: { display: false },
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
                        color: isDark ? '#9ca3af' : '#6b7280',
                        callback: function(value) {
                            return value.toLocaleString('id-ID');
                        }
                    },
                    grid: { color: isDark ? 'rgba(75, 85, 99, 0.3)' : 'rgba(229, 231, 235, 0.8)' }
                },
                x: {
                    ticks: { color: isDark ? '#9ca3af' : '#6b7280' },
                    grid: { display: false }
                }
            }
        }
    });
}

let isUpdating = false;

initDashboardChart(@json($chartLabels), @json($chartSteps));

Livewire.on('chart-updated', async () => {
    if (isUpdating) return;
    isUpdating = true;
    const data = await $wire.getChartData();
    initDashboardChart(data.labels, data.steps);
    setTimeout(() => isUpdating = false, 100);
});

Livewire.on('status-changed', async () => {
    if (isUpdating) return;
    isUpdating = true;
    const data = await $wire.getChartData();
    initDashboardChart(data.labels, data.steps);
    setTimeout(() => isUpdating = false, 100);
});

Livewire.hook('message.processed', async (message, component) => {
    if (isUpdating) return;
    isUpdating = true;
    const data = await $wire.getChartData();
    initDashboardChart(data.labels, data.steps);
    setTimeout(() => isUpdating = false, 100);
});
</script>
@endscript
