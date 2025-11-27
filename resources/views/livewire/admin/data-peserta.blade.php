<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\User;
use App\Exports\ParticipantsExport;
use Maatwebsite\Excel\Facades\Excel;

new #[Layout('components.layouts.app')] #[Title('Data Peserta')] class extends Component {
    use WithPagination;

    public $search = '';
    public $sortBy = '';
    public $sortDirection = 'desc';

    public function with(): array
    {
        $query = User::where('user_level', 1)
            ->when($this->search, fn($q) => 
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%'])
            );

        if ($this->sortBy) {
            $query->leftJoin('user_statistics', 'users.id', '=', 'user_statistics.user_id')
                ->select('users.*')
                ->orderByRaw('COALESCE(user_statistics.' . $this->sortBy . ', 0) ' . $this->sortDirection);
        } else {
            $query->with('statistics');
        }

        return [
            'participants' => $query->with('statistics')->paginate(10),
        ];
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

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function export()
    {
        return Excel::download(new ParticipantsExport(), 'data-peserta-' . date('Y-m-d') . '.xlsx');
    }
};

?>

<div>
    <div class="flex justify-between py-5 mb-5">
        <div>
            <flux:heading size="xl">Pantau Semangat Hijau para Peserta</flux:heading>
            <flux:text class="mt-2">
                Di halaman ini, anda bisa melihat seluruh peserta, memeriksa progres mereka, dan mengelola data
                aktivitas
                berjalan.
            </flux:text>
        </div>

        <div>
            <flux:button wire:click="export" variant="outline" icon="arrow-down-tray">Unduh Data</flux:button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto space-y-6">
        <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6 space-y-6">
            <div>
                <flux:heading size="lg">Data Peserta</flux:heading>
                <flux:subheading>Lihat progres setiap peserta dalam menjaga kebiasaan berjalan dan kontribusi hijau
                    mereka.</flux:subheading>
            </div>

            <flux:input wire:model.live="search" placeholder="Cari peserta..." icon="magnifying-glass" />

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Nama</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Direktorat</th>
                            <th wire:click="sort('total_langkah')"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                Total Langkah
                                @if($sortBy === 'total_langkah')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('total_co2e_kg')"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                CO₂e Dihindari
                                @if($sortBy === 'total_co2e_kg')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('total_pohon')"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                Est. Pohon
                                @if($sortBy === 'total_pohon')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('current_streak')"
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800">
                                Jumlah Streak
                                @if($sortBy === 'current_streak')
                                    <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($participants as $participant)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    <a href="{{ route('admin.detail-peserta', $participant->id) }}" class="text-[#004444] hover:text-[#006666] dark:text-[#00aa88] dark:hover:text-[#00cc99] font-medium hover:underline">
                                    {{ $participant->name }}</td>
                                    </a>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $participant->directorate->label() ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ number_format(($participant->statistics->total_langkah ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($participant->statistics->total_co2e_kg ?? 0, 1, ',', '.') }} kg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($participant->statistics->total_pohon ?? 0, 2, ',', '.') }} pohon</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $participant->statistics->current_streak ?? 0 }} hari</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    <flux:button href="{{ route('admin.detail-peserta', $participant->id) }}" size="sm" variant="filled">Lihat Detail</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7"
                                    class="px-6 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">Tidak ada
                                    data peserta</td>
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
