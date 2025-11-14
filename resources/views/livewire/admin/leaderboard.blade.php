<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\{User, UserStatistic};
use Illuminate\Support\Facades\DB;
use App\Exports\LeaderboardExport;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('components.layouts.app')]
    #[Title('Leaderboard')]
    class extends Component {
    use WithPagination;

    public $searchParticipants = '';
    public $searchDirectorates = '';

    public function with(): array
    {
        // Subquery untuk ranking semua peserta
        $rankedSubquery = User::where('user_level', 1)
            ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id')
            ->select('users.*', DB::raw('ROW_NUMBER() OVER (ORDER BY user_statistics.total_langkah DESC) as rank'));

        // Query dari subquery dengan filter
        $participantsQuery = DB::table(DB::raw("({$rankedSubquery->toSql()}) as ranked_users"))
            ->mergeBindings($rankedSubquery->getQuery())
            ->orderBy('rank');

        if ($this->searchParticipants) {
            $participantsQuery->where('name', 'like', '%' . $this->searchParticipants . '%');
        }

        // Leaderboard Direktorat dengan ranking
        $directoratesSubquery = UserStatistic::join('users', 'user_statistics.user_id', '=', 'users.id')
            ->where('users.user_level', 1)
            ->select(
                'users.directorate',
                DB::raw('SUM(user_statistics.total_langkah) as total_langkah'),
                DB::raw('SUM(user_statistics.total_co2e_kg) as total_co2e_kg'),
                DB::raw('COUNT(DISTINCT users.id) as jumlah_peserta')
            )
            ->groupBy('users.directorate');

        $directoratesQuery = DB::table(DB::raw("({$directoratesSubquery->toSql()}) as sub"))
            ->mergeBindings($directoratesSubquery->getQuery())
            ->select('*', DB::raw('ROW_NUMBER() OVER (ORDER BY total_langkah DESC) as rank'))
            ->orderByDesc('total_langkah');

        if ($this->searchDirectorates) {
            $directoratesQuery->where('directorate', 'like', '%' . $this->searchDirectorates . '%');
        }

        // Load statistics untuk participants
        $participants = $participantsQuery->paginate(10, ['*'], 'participantsPage');
        $participantIds = $participants->pluck('id');
        $statistics = UserStatistic::whereIn('user_id', $participantIds)->get()->keyBy('user_id');
        
        foreach ($participants as $participant) {
            $participant->statistics = $statistics->get($participant->id);
            $participant->directorate = \App\Enums\Directorate::tryFrom($participant->directorate);
        }

        return [
            'participants' => $participants,
            'directorates' => $directoratesQuery->paginate(10, ['*'], 'directoratesPage'),
        ];
    }

    public function updatingSearchParticipants()
    {
        $this->resetPage('participantsPage');
    }

    public function updatingSearchDirectorates()
    {
        $this->resetPage('directoratesPage');
    }

    public function exportData()
    {
        return Excel::download(new LeaderboardExport(), 'leaderboard-' . date('Y-m-d_H-i-s') . '.xlsx');
    }
};

?>

<div>
    <div class="flex justify-between py-5 mb-5">
        <div>
            <flux:heading size="xl">Leaderboard - Langkah Hijau Permata Banker</flux:heading>
            <flux:text class="mt-2 max-w-4xl">
                Lihat peringkat langkah para Permata Banker dan kontribusi terhadap pengurangan emisi CO₂e. Setiap langkah yang anda ambil membawa dampak nyata bagi bumi dan direktorat anda.
            </flux:text>
        </div>

        <div>
            <flux:button wire:click="exportData" variant="outline" icon="arrow-down-tray">Unduh Data</flux:button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Leaderboard Seluruh Peserta -->
        <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6 space-y-6">
            <div>
                <flux:heading size="lg">Leaderboard Seluruh Peserta</flux:heading>
                <flux:subheading>Peringkat peserta berdasarkan total langkah yang telah dicapai.</flux:subheading>
            </div>

            <flux:input wire:model.live.debounce.300ms="searchParticipants" placeholder="Cari nama peserta..." icon="magnifying-glass" />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Peringkat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Direktorat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Langkah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">CO₂e Dihindari</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Runtutan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($participants as $participant)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    @if($participant->rank == 1)
                                        🥇 {{ $participant->rank }}
                                    @elseif($participant->rank == 2)
                                        🥈 {{ $participant->rank }}
                                    @elseif($participant->rank == 3)
                                        🥉 {{ $participant->rank }}
                                    @else
                                        {{ $participant->rank }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $participant->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $participant->directorate?->label() ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($participant->statistics->total_langkah ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($participant->statistics->total_co2e_kg ?? 0, 2, ',', '.') }} kg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $participant->statistics->current_streak ?? 0 }} hari</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">Tidak ada data peserta</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $participants->links() }}
            </div>
        </div>

        <!-- Leaderboard Direktorat -->
        <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6 space-y-6">
            <div>
                <flux:heading size="lg">Leaderboard Direktorat</flux:heading>
                <flux:subheading>Peringkat direktorat berdasarkan total langkah dari seluruh peserta.</flux:subheading>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Peringkat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Direktorat</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Total Langkah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">CO₂e Dihindari</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Jumlah Peserta</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($directorates as $directorate)
                            @php
                                $directorateEnum = \App\Enums\Directorate::tryFrom($directorate->directorate);
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-zinc-900 dark:text-zinc-100">
                                    @if($directorate->rank == 1)
                                        🥇 {{ $directorate->rank }}
                                    @elseif($directorate->rank == 2)
                                        🥈 {{ $directorate->rank }}
                                    @elseif($directorate->rank == 3)
                                        🥉 {{ $directorate->rank }}
                                    @else
                                        {{ $directorate->rank }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $directorateEnum?->label() ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($directorate->total_langkah ?? 0, 0, ',', '.') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($directorate->total_co2e_kg ?? 0, 2, ',', '.') }} kg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($directorate->jumlah_peserta ?? 0, 0, ',', '.') }} peserta</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">Tidak ada data direktorat</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $directorates->links() }}
            </div>
        </div>
    </div>
</div>
