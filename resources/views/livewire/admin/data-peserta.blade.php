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

    public function with(): array
    {
        return [
            'participants' => User::where('user_level', 1)->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))->with('statistics')->paginate(10),
        ];
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
        <div class="bg-white dark:bg-zinc-800 border border-gray-100 rounded-lg shadow p-6 space-y-6">
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
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Total Langkah</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                CO₂e Dihindari</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Est. Pohon</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Jumlah Streak</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($participants as $participant)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $participant->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ $participant->directorate->label() ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ number_format(($participant->statistics->total_langkah ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($participant->statistics->total_co2e_kg ?? 0, 1, ',', '.') }} kg</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">
                                    {{ number_format($participant->statistics->total_pohon ?? 0, 0, ',', '.') }} pohon</td>
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
