<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use App\Models\{DailyReport, ManualVerificationLog};
use App\Enums\{StatusVerifikasi, VerifiedBy};
use App\Services\ReportCalculationService;

new #[Layout('components.layouts.app')]
    #[Title('Verifikasi Bukti')]
    class extends Component {
    use WithPagination;

    public $filterStatus = 'all';
    public $search = '';

    public $validSteps = [];
    public $appNames = [];

    public function with(): array
    {
        $query = DailyReport::with(['user'])
            ->orderByDesc('manual_verification_requested_at')
            ->where('manual_verification_requested', true);

        if ($this->filterStatus !== 'all') {
            $query->where('status_verifikasi', (int)$this->filterStatus);
        }

        if ($this->search) {
            $query->whereHas('user', fn($q) => $q->where('name', 'like', '%' . $this->search . '%'));
        }

        return [
            'reports' => $query->paginate(15),
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function approveReport($reportId)
    {
        $this->validate([
            "validSteps.{$reportId}" => 'required|integer|min:0|max:50000',
            "appNames.{$reportId}" => 'nullable|string|max:255',
        ]);

        $report = DailyReport::find($reportId);
        if ($report) {
            // Refresh data dari database untuk hindari race condition
            $report->refresh();

            // Cek apakah sudah diverifikasi (baik oleh OCR atau admin lain)
            if ($report->status_verifikasi === StatusVerifikasi::DIVERIFIKASI && $report->verified_by) {
                $verifiedBy = $report->verified_id == VerifiedBy::SISTEM ? 'sistem OCR' : 'Admin lain';
                flash()->warning("Laporan sudah diverifikasi oleh {$verifiedBy}");
                return;
            }

            // Cek apakah request manual verification sudah dibatalkan
            if (!$report->manual_verification_requested) {
                flash()->info('Request verifikasi manual sudah dibatalkan');
                return;
            }

            ManualVerificationLog::create([
                'report_id' => $report->id,
                'image_url' => $report->bukti_screenshot,
                'valid_step' => $this->validSteps[$reportId],
                'app_name' => $this->appNames[$reportId] ?? null,
                'status_verifikasi' => StatusVerifikasi::DIVERIFIKASI,
                'validated_by' => auth()->id(),
            ]);

            $report->update([
                'ocr_result' => json_encode(['steps' => (int) ($this->validSteps[$reportId] ?? 0)]),
                'status_verifikasi' => StatusVerifikasi::DIVERIFIKASI,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'verified_id' => VerifiedBy::ADMIN,
                'manual_verification_requested' => false,
                'manual_verification_requested_at' => null,
            ]);

            app(ReportCalculationService::class)->recalculate($report->id, $this->validSteps[$reportId]);

            flash()->success('Laporan berhasil diverifikasi');
            unset($this->validSteps[$reportId], $this->appNames[$reportId]);
        }
    }

    public function rejectReport($reportId)
    {
        $this->validate([
            "validSteps.{$reportId}" => 'nullable|integer|min:0',
            "appNames.{$reportId}" => 'nullable|string|max:255',
        ]);

        $report = DailyReport::find($reportId);
        if ($report) {
            ManualVerificationLog::create([
                'report_id' => $report->id,
                'image_url' => $report->bukti_screenshot,
                'valid_step' => $this->validSteps[$reportId] ?? 0,
                'app_name' => $this->appNames[$reportId] ?? null,
                'status_verifikasi' => StatusVerifikasi::DITOLAK,
                'validated_by' => auth()->id(),
            ]);

            $report->update([
                'status_verifikasi' => StatusVerifikasi::DITOLAK,
                'verified_id' => VerifiedBy::ADMIN,
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'manual_verification_requested' => false,
                'manual_verification_requested_at' => null
            ]);

            flash()->warning('Laporan ditolak');
            unset($this->validSteps[$reportId], $this->appNames[$reportId]);
        }
    }
};

?>

<div>
    <div class="py-5 mb-5">
        <flux:heading size="xl">Verifikasi Bukti Aktivitas</flux:heading>
        <flux:text class="mt-2">
            Kelola dan validasi laporan aktivitas PermataBankers untuk memastikan setiap langkah benar-benar tercatat dan berdampak bagi lingkungan
        </flux:text>
    </div>

    <div class="bg-white dark:bg-zinc-800 border border-gray-100 dark:border-zinc-700 rounded-lg shadow p-6 space-y-6">
        <div>
            <flux:heading size="lg">Daftar Bukti Aktivitas yang Diajukan</flux:heading>
            <flux:subheading>Lihat seluruh laporan aktivitas yang dikirim oleh peserta.</flux:subheading>
        </div>

        <div class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <flux:input wire:model.live="search" placeholder="Cari nama peserta..." icon="magnifying-glass" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Nama Peserta</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Direktorat</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Langkah</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">CO₂e Dihindari</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Bukti Foto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Waktu Request</th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-800 divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($reports as $report)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $report->user->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $report->user->directorate->label() }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $report->tanggal_laporan->format('d M Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($report->langkah, 0, ',', '.') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ number_format($report->co2e_reduction_kg ?? 0, 2, ',', '.') }} kg</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($report->bukti_screenshot)
                                    <a href="{{ $report->bukti_screenshot }}" target="_blank" class="text-blue-600 hover:text-blue-800">
                                        <flux:icon.photo class="w-5 h-5" />
                                    </a>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-zinc-100">{{ $report->manual_verification_requested_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                <flux:modal.trigger name="verification-modal-{{ $report->id }}">
                                    <flux:button size="sm" variant="filled">Verifikasi</flux:button>
                                </flux:modal.trigger>
                            </td>
                        </tr>

                        <flux:modal name="verification-modal-{{ $report->id }}" class="max-w-7xl" :dismissible="false">
                            <div class="space-y-6">
                                <div>
                                    <flux:heading size="lg">Verifikasi Laporan Aktivitas</flux:heading>
                                    <flux:subheading>{{ $report->user->name }} - {{ $report->tanggal_laporan->format('d M Y') }}</flux:subheading>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <flux:label>Bukti Screenshot</flux:label>
                                            @if($report->bukti_screenshot)
                                                <div class="mt-2 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                                    <img src="{{ $report->bukti_screenshot }}" alt="Bukti" class="w-full h-auto">
                                                </div>
                                            @else
                                                <div class="mt-2 p-8 border border-zinc-200 dark:border-zinc-700 rounded-lg text-center text-zinc-400">
                                                    Tidak ada bukti screenshot
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="space-y-4">
                                        <div class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                                            <p class="text-sm text-orange-700 dark:text-orange-400 font-medium">⚠️ Request Verifikasi Manual</p>
                                            <p class="text-xs text-orange-600 dark:text-orange-500 mt-1">Diajukan pada {{ $report->manual_verification_requested_at->format('d M Y H:i:s') }} WIB</p>
                                        </div>

                                        <div class="grid grid-cols-2 gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Nama Peserta</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->user->name }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Direktorat</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->user->directorate->label() }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Tanggal Laporan</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->tanggal_laporan->format('d M Y') }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Langkah Saat Ini</p>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($report->langkah, 0, ',', '.') }}</p>
                                            </div>
                                        </div>
                                        @if($report->ocr_result)
                                            @php
                                                $ocr = json_decode($report->ocr_result, true);
                                                $previousImage = $report->ocrProcessLogs()->where('img_url', '!=', $report->bukti_screenshot)->latest()->first();
                                            @endphp
                                            <div>
                                                <flux:label>Hasil OCR Sebelumnya</flux:label>
                                                <div class="mt-2 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg
                                                            text-xs font-mono text-zinc-700 dark:text-zinc-300
                                                            max-h-32 overflow-y-auto space-y-1">
                                                    @if($previousImage)
                                                        <div class="mb-2 pb-2 border-b border-zinc-200 dark:border-zinc-700">
                                                            <div class="flex items-center justify-between">
                                                                <strong class="text-blue-600 dark:text-blue-400">📷 Gambar Sebelumnya:</strong>
                                                                <a href="{{ $previousImage->img_url }}" target="_blank" class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded text-xs hover:bg-blue-200 dark:hover:bg-blue-800">Lihat</a>
                                                            </div>
                                                            <div class="text-zinc-500 dark:text-zinc-400 mt-1">Diproses: {{ $previousImage->created_at->format('d M Y H:i:s') }}</div>
                                                        </div>
                                                    @endif
                                                    @foreach($ocr as $key => $value)
                                                        <div><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> {{ $value }}</div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div>
                                            <flux:label>Jumlah Langkah Valid <span class="text-red-500">*</span></flux:label>
                                            <flux:input wire:model="validSteps.{{ $report->id }}" type="number" min="0" placeholder="Masukkan jumlah langkah yang valid" class="mt-1" />
                                            @error("validSteps.{$report->id}")
                                                <flux:error>{{ $message }}</flux:error>
                                            @enderror
                                        </div>

                                        <div>
                                            <flux:label>Nama Aplikasi <span class="text-zinc-400 text-xs">(Opsional)</span></flux:label>
                                            <flux:input wire:model="appNames.{{ $report->id }}" type="text" placeholder="Contoh: Google Fit, Samsung Health" class="mt-1" />
                                            @error("appNames.{$report->id}")
                                                <flux:error>{{ $message }}</flux:error>
                                            @enderror
                                        </div>

                                        <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Pastikan data yang Anda masukkan sudah benar sebelum melakukan verifikasi.</p>
                                            <div class="flex gap-3">
                                                <flux:button wire:click="rejectReport({{ $report->id }})"
                                                    icon="x-mark"
                                                    variant="danger" class="flex-1">
                                                    Tolak
                                                </flux:button>
                                                <flux:button wire:click="approveReport({{ $report->id }})"
                                                    icon="check"
                                                    variant="primary" class="flex-1">
                                                    Verifikasi
                                                </flux:button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex justify-end pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                    <flux:modal.close>
                                        <flux:button variant="ghost">Batal</flux:button>
                                    </flux:modal.close>
                                </div>
                            </div>
                        </flux:modal>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">Tidak ada data laporan yang harus di Verifikasi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $reports->links() }}
        </div>
    </div>
</div>
