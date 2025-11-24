<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\User;
use App\Models\DailyReport;

new #[Layout('components.layouts.app')] #[Title('Detail Peserta')] class extends Component {
    use WithPagination;

    public $userId;
    public $participant;
    public $searchDate = '';

    public function mount($id)
    {
        $this->userId = $id;
        $this->participant = User::with('statistics')->findOrFail($id);
    }

    public function with(): array
    {
        // Get participant rank
        $rank = User::where('user_level', 1)
            ->join('user_statistics', 'users.id', '=', 'user_statistics.user_id')
            ->where('user_statistics.total_langkah', '>', $this->participant->statistics->total_langkah ?? 0)
            ->count() + 1;

        return [
            'rank' => $rank,
            'reports' => DailyReport::where('user_id', $this->userId)
                ->when($this->searchDate, fn($q) => $q->whereDate('tanggal_laporan', 'like', '%' . $this->searchDate . '%')
                    ->orWhere('tanggal_laporan', 'like', '%' . $this->searchDate . '%'))
                ->orderBy('tanggal_laporan', 'desc')
                ->paginate(10)
        ];
    }

    public function updatingSearchDate()
    {
        $this->resetPage();
    }
};

?>

<div>
    <div class="flex justify-between py-5 mb-5">
        <div>
            <flux:heading size="xl">Detail Peserta</flux:heading>
            <flux:text class="mt-2">
                Informasi lengkap dan riwayat aktivitas peserta.
            </flux:text>
        </div>

        <div>
            <flux:button href="{{ route('admin.data-peserta') }}" variant="outline" icon="arrow-left">Kembali</flux:button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto space-y-6">
        <div class="bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 p-6 rounded-2xl border border-gray-100 shadow-md w-full">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-2xl font-semibold">{{ $participant->name }}</h2>
            </div>

            <!-- Statistik Utama -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Ranking -->
                <div class="border dark:border-gray-700 rounded-xl p-4 flex items-center gap-3">
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-medal text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ranking Peserta:</p>
                        <p class="font-medium">
                            @if($rank == 1)
                                🥇 #{{ $rank }}
                            @elseif($rank == 2)
                                🥈 #{{ $rank }}
                            @elseif($rank == 3)
                                🥉 #{{ $rank }}
                            @else
                                #{{ $rank }}
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Total CO2e -->
                <div class="border dark:border-gray-700 rounded-xl p-4 flex items-center gap-3">
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-globe-hemisphere-east text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total CO₂e:</p>
                        <p class="font-medium">{{ number_format($participant->statistics->total_co2e_kg ?? 0, 1, ',', '.') }} kg CO₂e</p>
                    </div>
                </div>

                <!-- Total Langkah -->
                <div class="border dark:border-gray-700 rounded-xl p-4 flex items-center gap-3">
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-footprints text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Langkah:</p>
                        <p class="font-medium">{{ number_format($participant->statistics->total_langkah ?? 0, 0, ',', '.') }} langkah</p>
                    </div>
                </div>

                <!-- Konversi Pohon -->
                <div class="border dark:border-gray-700 rounded-xl p-4 flex items-center gap-3">
                    <div class="bg-white p-1.5 rounded-xl border-[2px] border-[#ededed] flex items-center justify-center">
                        <div class="bg-[#c1fdc6] border border-[#ededed] rounded-lg w-8 h-8 flex items-center justify-center">
                            <i class="ph-fill ph-tree-evergreen text-[#004946] text-2xl"></i>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Konversi Pohon:</p>
                        <p class="font-medium">{{ number_format($participant->statistics->total_pohon ?? 0, 2, ',', '.') }} pohon</p>
                    </div>
                </div>
            </div>

            <!-- Detail Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                <div>
                    <p class="font-medium text-gray-800 dark:text-gray-200">Email</p>
                    <p>{{ $participant->email }}</p>
                </div>

                <div>
                    <p class="font-medium text-gray-800 dark:text-gray-200">Direktorat</p>
                    <p>{{ $participant->directorate->label() ?? '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Riwayat Aktivitas -->
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-md p-4 md:p-6 w-full">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                <div class="relative w-full md:w-1/3">
                    <input
                        type="text"
                        wire:model.live="searchDate"
                        placeholder="Cari tanggal..."
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 py-2 focus:ring-2 focus:ring-emerald-500 outline-none placeholder-gray-400 dark:placeholder-gray-500"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Total Jarak</th>
                            <th class="py-3 px-4">CO₂e Dihindari</th>
                            <th class="py-3 px-4">Est. Pohon</th>
                            <th class="py-3 px-4">Foto</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-800 dark:text-gray-200">
                        @forelse($reports as $report)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/60 border-b border-gray-100 dark:border-gray-700">
                                <td class="py-3 px-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d F Y') }}</td>
                                <td class="py-3 px-4">{{ number_format($report->langkah, 0, ',', '.') }} km</td>
                                <td class="py-3 px-4">{{ number_format($report->co2e_reduction_kg, 1, ',', '.') }} kg</td>
                                <td class="py-3 px-4">{{ number_format($report->pohon, 2, ',', '.') }} pohon</td>
                                <td class="py-3 px-4">
                                    @if($report->bukti_screenshot)
                                        <a href="{{ $report->bukti_screenshot }}" data-fancybox="gallery-{{ $report->id }}" data-caption="Bukti Screenshot - {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d F Y') }}" class="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Lihat Foto
                                        </a>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <flux:modal.trigger name="photo-{{ $report->id }}">
                                            <flux:button size="sm" variant="outline" class="rounded-md font-medium">Detail</flux:button>
                                        </flux:modal.trigger>
                                    </div>
                                </td>
                            </tr>

                            <flux:modal name="photo-{{ $report->id }}" class="w-full max-w-5xl">
                                <div class="grid grid-cols-2 gap-4 p-4">
                                    <div class="flex items-center justify-center">
                                        <img src="{{ $report->bukti_screenshot }}" alt="Bukti Screenshot" class="w-full h-auto rounded-lg">
                                    </div>
                                    <div class="space-y-3 p-3">
                                        <div class="shadow p-3 rounded border border-gray-50 flex flex-col">
                                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">Hasil Analisa Sistem</h3>
                                            @php $ocr = json_decode($report->ocr_result, true); @endphp
                                            @if($ocr)
                                                <div class="space-y-2 text-sm">
                                                    @if(isset($ocr['steps']))<div class="flex justify-between"><span class="text-gray-600 dark:text-gray-400">Steps:</span><span class="font-medium text-gray-900 dark:text-gray-100">{{ number_format($ocr['steps']) }}</span></div>@endif
                                                </div>
                                            @else
                                                <p class="text-gray-500 dark:text-gray-400 text-sm">Tidak ada data analisa</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </flux:modal>
                        @empty
                            <tr>
                                <td colspan="6" class="py-3 px-4 text-center text-gray-500 dark:text-gray-400">
                                    Belum ada aktivitas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
</div>
