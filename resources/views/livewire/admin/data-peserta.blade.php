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
            'participants' => User::where('user_level', 1)
                ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                ->with('statistics')
                ->paginate(10)
        ];
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function export()
    {
        return Excel::download(new ParticipantsExport, 'data-peserta-' . date('Y-m-d') . '.xlsx');
    }
};

?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Pantau Semangat Hijau para Peserta</h1>
            <p class="text-gray-600 mt-1">
                Di halaman ini, anda bisa melihat seluruh peserta, memeriksa progres mereka, dan mengelola data
                aktivitas berjalan.
            </p>
        </div>
        <div class="flex items-center gap-3 mt-4 md:mt-0">
            <flux:button wire:click="export" variant="outline" icon="arrow-down-tray">Unduh Data</flux:button>
            <flux:button icon="plus">Tambah Peserta</flux:button>
        </div>
    </div>

    <!-- Card Container -->
    <div class="bg-white rounded-2xl shadow-lg p-6">
        <h2 class="text-xl font-semibold mb-1">Data Peserta</h2>
        <p class="text-gray-500 mb-4">
            Lihat progres setiap peserta dalam menjaga kebiasaan berjalan dan kontribusi hijau mereka.
        </p>

        <!-- Search Bar -->
        <div class="mb-4">
            <input type="text" wire:model.live="search" placeholder="Cari peserta..."
                class="w-full md:w-1/2 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" />
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-100 text-left text-gray-700">
                        <th class="p-3">Nama</th>
                        <th class="p-3">Direktorat</th>
                        <th class="p-3">Total Jarak</th>
                        <th class="p-3">CO₂e Dihindari</th>
                        <th class="p-3">Est. Pohon</th>
                        <th class="p-3">Jumlah Streak</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($participants as $participant)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3">{{ $participant->name }}</td>
                            <td class="p-3">{{ $participant->directorate ?? '-' }}</td>
                            <td class="p-3">{{ number_format(($participant->statistics->total_langkah ?? 0) * 0.0008, 1) }} km</td>
                            <td class="p-3">{{ number_format($participant->statistics->total_co2e_kg ?? 0, 1) }} kg</td>
                            <td class="p-3">{{ number_format($participant->statistics->total_pohon ?? 0, 0) }} pohon</td>
                            <td class="p-3">{{ $participant->statistics->current_streak ?? 0 }} hari</td>
                            <td class="p-3 text-center">
                                <flux:button size="sm" variant="filled">Lihat Detail</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-3 text-center text-gray-500">Tidak ada data peserta</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $participants->links() }}
        </div>
    </div>
</div>
