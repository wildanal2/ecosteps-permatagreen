<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\{User, UserStatistic};
use Illuminate\Support\Facades\DB;
use App\Exports\LeaderboardExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\Directorate;

new #[Layout('components.layouts.app-with-header')]
    #[Title('Leaderboard Per Direktorat')]
    class extends Component {
    use WithPagination;

    public $selectedDirectorate = null;
    public $search = '';
    public $sortBy = 'total_langkah';
    public $sortDirection = 'desc';

    public function mount()
    {
        $this->selectedDirectorate = request()->query('directorate');
    }

    public function with(): array
    {
        $orderColumn = $this->sortBy === 'name' ? 'users.name' : 'user_statistics.' . $this->sortBy;
        
        $query = User::where('user_level', 1)
            ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id');

        if ($this->selectedDirectorate !== null && $this->selectedDirectorate !== '') {
            $query->where('users.directorate', $this->selectedDirectorate);
        }

        if ($this->search) {
            $query->where('users.name', 'like', '%' . $this->search . '%');
        }

        // Summary statistics
        $summary = (clone $query)->select(
            DB::raw('SUM(user_statistics.total_langkah) as total_langkah'),
            DB::raw('SUM(user_statistics.total_co2e_kg) as total_co2e_kg'),
            DB::raw('COUNT(DISTINCT users.id) as jumlah_peserta')
        )->first();

        $rankedSubquery = $query->select('users.*', DB::raw("ROW_NUMBER() OVER (ORDER BY {$orderColumn} {$this->sortDirection}) as rank"));

        $participants = DB::table(DB::raw("({$rankedSubquery->toSql()}) as ranked_users"))
            ->mergeBindings($rankedSubquery->getQuery())
            ->orderBy('rank')
            ->paginate(15);

        $participantIds = $participants->pluck('id');
        $statistics = UserStatistic::whereIn('user_id', $participantIds)->get()->keyBy('user_id');

        foreach ($participants as $participant) {
            $participant->statistics = $statistics->get($participant->id);
            $participant->directorate = Directorate::tryFrom($participant->directorate);
        }

        return [
            'participants' => $participants,
            'directorates' => Directorate::cases(),
            'summary' => $summary,
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSelectedDirectorate()
    {
        $this->resetPage();
    }

    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function exportData()
    {
        $query = User::where('user_level', 1)->with('statistics');

        if ($this->selectedDirectorate !== null && $this->selectedDirectorate !== '') {
            $query->where('directorate', $this->selectedDirectorate);
        }

        $users = $query->get();
        
        $data = $users->map(function($user) {
            return (object)[
                'name' => $user->name,
                'email' => $user->email,
                'directorate' => $user->directorate,
                'total_langkah' => $user->statistics->total_langkah ?? 0,
                'total_co2e_kg' => $user->statistics->total_co2e_kg ?? 0,
                'current_streak' => $user->statistics->current_streak ?? 0,
            ];
        })->sortByDesc('total_langkah');

        $directorateName = $this->selectedDirectorate !== null && $this->selectedDirectorate !== '' 
            ? Directorate::tryFrom($this->selectedDirectorate)?->label() 
            : 'Semua';
        
        return Excel::download(
            new LeaderboardExport($data, $directorateName), 
            'leaderboard-' . str_replace(' ', '-', strtolower($directorateName)) . '-' . date('Y-m-d_H-i-s') . '.xlsx'
        );
    }
};

?>

<x-slot:navbar>
    <flux:navbar.item href="{{ route('admin.leaderboard') }}" :current="request()->routeIs('admin.leaderboard')" wire:navigate>Leaderboard</flux:navbar.item>
    <flux:navbar.item href="{{ route('admin.leaderboard.direktorat') }}" :current="request()->routeIs('admin.leaderboard.direktorat')">Leaderboard Direktorat</flux:navbar.item>
</x-slot:navbar>

<div>
    <div class="flex justify-between py-5 mb-5">
        <div>
            <flux:heading size="xl">Leaderboard Per Direktorat</flux:heading>
            <flux:text class="mt-2 max-w-4xl">
                Lihat peringkat langkah per direktorat. Pilih direktorat untuk melihat detail peserta.
            </flux:text>
        </div>

        <div>
            <flux:button wire:click="exportData" variant="outline" icon="arrow-down-tray">Unduh Data</flux:button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto space-y-6">
        <!-- Selector Direktorat -->
         <flux:field>
            <flux:label>Direktorat</flux:label>
            <flux:select wire:model.live="selectedDirectorate" placeholder="Semua Direktorat">
                <option value="">Semua Direktorat</option>
                @foreach($directorates as $directorate)
                    @if($directorate->value !== 0)
                        <option value="{{ $directorate->value }}">{{ $directorate->label() }}</option>
                    @endif
                @endforeach
            </flux:select>
        </flux:field>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Total Langkah</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($summary->total_langkah ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">CO₂e Dihindari</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($summary->total_co2e_kg ?? 0, 2, ',', '.') }} kg</div>
            </div>
            <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6">
                <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-1">Jumlah Peserta</div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ number_format($summary->jumlah_peserta ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6 space-y-6">
            <div>
                <flux:heading size="lg">Leaderboard Peserta</flux:heading>
                <flux:subheading>Peringkat peserta berdasarkan total langkah yang telah dicapai.</flux:subheading>
            </div>

            <flux:input wire:model.live.debounce.300ms="search" placeholder="Cari nama peserta..." icon="magnifying-glass" />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Peringkat</th>
                            <th wire:click="sort('name')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                Nama
                                @if($sortBy === 'name')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Direktorat</th>
                            <th wire:click="sort('total_langkah')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                Total Langkah
                                @if($sortBy === 'total_langkah')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('total_co2e_kg')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                CO₂e Dihindari
                                @if($sortBy === 'total_co2e_kg')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('current_streak')" class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                Runtutan
                                @if($sortBy === 'current_streak')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <a href="{{ route('admin.detail-peserta', $participant->id) }}" class="text-[#004444] hover:text-[#006666] dark:text-[#00aa88] dark:hover:text-[#00cc99] font-medium hover:underline">
                                        {{ $participant->name }}
                                    </a>
                                </td>
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
    </div>
</div>
