<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title, On};
use App\Models\{DailyReport, ManualVerificationLog};
use Illuminate\Support\Facades\Storage;
use App\Enums\{VerifiedBy, StatusVerifikasi};
use App\Services\ReportCalculationService;

new #[Layout('components.layouts.app-with-header')] #[Title('Daily Report Admin')] class extends Component {
    public $reports = [];
    public $page = 1;
    public $hasMore = true;
    public $perPage = 50;

    public $validSteps = [];
    public $appNames = [];

    public function mount()
    {
        $this->loadReports();
    }

    public function loadReports()
    {
        $skip = ($this->page - 1) * $this->perPage;

        $newReports = DailyReport::with('user')
            ->whereNotNull('bukti_screenshot')
            ->orderBy('tanggal_laporan', 'desc')
            ->orderBy('created_at', 'desc')
            ->skip($skip)
            ->take($this->perPage)
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'tanggal_laporan' => $report->tanggal_laporan instanceof \Carbon\Carbon ? $report->tanggal_laporan->toDateString() : $report->tanggal_laporan,
                    'bukti_screenshot' => $report->bukti_screenshot,
                    'langkah' => $report->langkah,
                    'verified_id' => $report->verified_id,
                    'updated_at' => $report->updated_at instanceof \Carbon\Carbon ? $report->updated_at->toDateTimeString() : $report->updated_at,
                    'user' => [
                        'name' => $report->user->name,
                    ],
                ];
            })
            ->toArray();

        $this->reports = array_merge($this->reports, $newReports);
        $this->hasMore = count($newReports) === $this->perPage;
    }

    public function loadMore()
    {
        $this->page++;
        $this->loadReports();
    }

    public function groupedReports()
    {
        $grouped = [];
        foreach ($this->reports as $report) {
            $date = $report['tanggal_laporan'];
            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $report;
        }
        return $grouped;
    }

    public function approveReport($reportId)
    {
        $this->validate(
            [
                "validSteps.{$reportId}" => 'required|integer|min:0|max:100000',
                "appNames.{$reportId}" => 'nullable|string|max:255',
            ],
            [
                "validSteps.{$reportId}.required" => 'Jumlah langkah wajib diisi',
                "validSteps.{$reportId}.integer" => 'Jumlah langkah harus berupa angka',
                "validSteps.{$reportId}.min" => 'Jumlah langkah minimal 0',
                "validSteps.{$reportId}.max" => 'Jumlah langkah maksimal 100.000',
                "appNames.{$reportId}.max" => 'Nama aplikasi maksimal 255 karakter',
            ]
        );

        $report = DailyReport::find($reportId);
        if ($report) {
            $report->refresh();

            if ($report->status_verifikasi === StatusVerifikasi::DIVERIFIKASI && $report->verified_by) {
                $verifiedBy = $report->verified_id == VerifiedBy::SISTEM ? 'sistem OCR' : 'Admin lain';
                flash()->warning("Laporan sudah diverifikasi oleh {$verifiedBy} Anda tetap merubahnya");
                // return;
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

            // Update report in existing array
            foreach ($this->reports as $key => $rep) {
                if ($rep['id'] === $reportId) {
                    $this->reports[$key]['verified_id'] = VerifiedBy::ADMIN;
                    $this->reports[$key]['langkah'] = $this->validSteps[$reportId];
                    break;
                }
            }

            unset($this->validSteps[$reportId], $this->appNames[$reportId]);

            flash()->success('Laporan berhasil diverifikasi');
            $this->js("Flux.modal('verify-modal-{$reportId}').close()");
        }
    }
};

?>

<x-slot:navbar>
    <flux:navbar.item href="{{ route('admin.daily-report') }}" :current="request()->routeIs('admin.daily-report')" wire:navigate>Gallery Report</flux:navbar.item>
    <flux:navbar.item href="{{ route('admin.daily-report-list') }}" :current="request()->routeIs('admin.daily-report-list')">Data List Participant</flux:navbar.item>
</x-slot:navbar>

<div>
    <div class="flex flex-col gap-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-zinc-100">
                Daily Report
            </h2>
            <p class="text-sm text-gray-600 dark:text-zinc-400">
                Galeri laporan harian karyawan
            </p>
        </div>

        {{-- Grouped Reports by Date --}}
        @foreach ($this->groupedReports() as $date => $dateReports)
            <div>
                @php
                    $carbonDate = \Carbon\Carbon::parse($date);
                    $today = now()->toDateString();
                    $yesterday = now()->subDay()->toDateString();

                    if ($date === $today) {
                        $label = 'Hari Ini (' . $carbonDate->format('d M Y') . ')';
                    } elseif ($date === $yesterday) {
                        $label = 'Kemarin (' . $carbonDate->format('d M Y') . ')';
                    } else {
                        $label = $carbonDate->format('d M Y');
                    }
                @endphp
                <h3 class="text-lg font-semibold text-gray-800 dark:text-zinc-100 mb-4">
                    {{ $label }}
                </h3>
                <div class="grid grid-cols-4 md:grid-cols-8 lg:grid-cols-12 auto-rows-[180px] gap-4">
                    @foreach ($dateReports as $index => $report)
                        @php
                            $patterns = [
                                'col-span-2 row-span-2',
                                'col-span-2 row-span-1',
                                'col-span-2 row-span-1',
                                'col-span-2 row-span-2',
                                'col-span-2 row-span-1',
                                'col-span-2 row-span-1',
                            ];
                            $class = $patterns[$index % 6];
                        @endphp
                        <div
                            class="group relative {{ $class }} rounded-xl overflow-hidden bg-gray-100 dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 hover:shadow-lg transition">
                            <img src="{{ $report['bukti_screenshot'] }}" class="w-full h-full object-cover" alt="Report">
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent">
                                <div class="absolute top-2 right-2 flex flex-col gap-1 items-end">
                                    <span
                                        class="inline-block px-2 py-1 text-[0.6rem] font-semibold rounded-full {{ $report['verified_id']->value === App\Enums\VerifiedBy::SISTEM->value ? 'bg-green-500' : 'bg-blue-500' }} text-white"
                                        style="min-width: 0; width: auto; max-width: 100%;">
                                        {{ $report['verified_id']->value == App\Enums\VerifiedBy::SISTEM->value ? 'SISTEM' : 'ADMIN' }}
                                    </span>
                                    <flux:modal.trigger name="verify-modal-{{ $report['id'] }}">
                                        <flux:button variant="primary" icon="backspace" size="xs" color="emerald">Verifikasi</flux:button>
                                    </flux:modal.trigger>
                                </div>
                                <a href="{{ $report['bukti_screenshot'] }}" data-fancybox="gallery-{{ $date }}"
                                    data-caption="{{ $report['user']['name'] }} - {{ number_format($report['langkah'], 0, ',', '.') }} langkah">
                                    <div class="absolute bottom-0 left-0 right-0 p-3 text-white">
                                        <p class="text-xs font-medium truncate">{{ $report['user']['name'] }}</p>
                                        <p class="text-lg font-bold">
                                            {{ number_format($report['langkah'], 0, ',', '.') }}
                                        </p>
                                        <p class="text-xs">langkah ·
                                            {{ \Carbon\Carbon::parse($report['updated_at'])->format('H:i') }} WIB</p>
                                    </div>
                                </a>
                            </div>
                            <flux:modal name="verify-modal-{{ $report['id'] }}" class="max-w-7xl"
                                :dismissible="false">
                                @php
                                    $fullReport = DailyReport::with('user')->find($report['id']);
                                @endphp
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Verifikasi Laporan Aktivitas</flux:heading>
                                        <flux:subheading>{{ $report['user']['name'] }} -
                                            {{ \Carbon\Carbon::parse($report['tanggal_laporan'])->format('d M Y') }}
                                        </flux:subheading>
                                    </div>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div class="space-y-4">
                                            <div>
                                                <flux:label>Bukti Screenshot</flux:label>
                                                <div
                                                    class="mt-2 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                                    <img src="{{ $report['bukti_screenshot'] }}" alt="Bukti"
                                                        class="w-full h-auto">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div
                                                class="grid grid-cols-2 gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Nama Peserta</p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                        {{ $report['user']['name'] }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Tanggal Laporan
                                                    </p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                        {{ \Carbon\Carbon::parse($report['tanggal_laporan'])->format('d M Y') }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Langkah Saat Ini
                                                    </p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                        {{ number_format($report['langkah'], 0, ',', '.') }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Diverifikasi Oleh</p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                                        {{ $report['verified_id']->value == App\Enums\VerifiedBy::SISTEM->value ? 'SISTEM OCR' : 'ADMIN' }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div wire:key="validSteps-{{ $report['id'] }}">
                                                <flux:label>Jumlah Langkah Valid <span class="text-red-500">*</span>
                                                </flux:label>
                                                <flux:input wire:model.blur="validSteps.{{ $report['id'] }}" type="number"
                                                    min="0" placeholder="Masukkan jumlah langkah yang valid"
                                                    class="mt-1" />
                                                @error('validSteps.' . $report['id'])
                                                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div wire:key="appNames-{{ $report['id'] }}">
                                                <flux:label>Nama Aplikasi <span
                                                        class="text-zinc-400 text-xs">(Opsional)</span></flux:label>
                                                <flux:input wire:model.blur="appNames.{{ $report['id'] }}" type="text"
                                                    placeholder="Contoh: Google Fit, Samsung Health" class="mt-1" />
                                                @error('appNames.' . $report['id'])
                                                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Pastikan data
                                                    yang
                                                    Anda masukkan sudah benar sebelum melakukan verifikasi.</p>
                                                <flux:button wire:click="approveReport({{ $report['id'] }})"
                                                    icon="check" variant="primary" class="w-full">
                                                    Verifikasi Ulang
                                                </flux:button>
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
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        {{-- Load More Button --}}
        @if ($hasMore)
            <div class="flex justify-center">
                <flux:button wire:click="loadMore" icon="arrow-path" size="sm">Muat Lebih Banyak</flux:button>
            </div>
        @endif

        {{-- Empty State --}}
        @if (count($reports) === 0)
            <div
                class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-100 dark:border-zinc-700 p-12 text-center">
                <p class="text-gray-600 dark:text-zinc-400">Belum ada laporan harian</p>
            </div>
        @endif
    </div>
</div>
