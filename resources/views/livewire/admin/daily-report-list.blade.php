<?php

use Livewire\Volt\Component;
use Livewire\Attributes\{Layout, Title};
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\{DailyReport, ManualVerificationLog, User};
use App\Enums\{VerifiedBy, StatusVerifikasi};
use App\Services\ReportCalculationService;
use PhpOffice\PhpSpreadsheet\IOFactory;

new #[Layout('components.layouts.app-with-header')] #[Title('Daily Report Admin')] class extends Component {
    use WithPagination, WithFileUploads;

    public $search = '';
    public $dateRange = '';
    public $filterVerified = '';
    public $sortBy = 'tanggal_laporan';
    public $sortDirection = 'desc';
    public $validSteps = [];
    public $appNames = [];
    
    public $uploadDate;
    public $excelFile;
    public $previewData = [];
    public $dataChecked = false;

    public function mount()
    {
        $today = now()->format('Y-m-d');
        $this->dateRange = $today . ' to ' . $today;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingDateRange()
    {
        $this->resetPage();
    }

    public function updatingFilterVerified()
    {
        $this->resetPage();
    }

    public function sort($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function with()
    {
        $query = DailyReport::with(['user'])->orderBy('updated_at', 'desc');

        if ($this->search) {
            $query->whereHas('user', function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($this->search) . '%']);
            });
        }

        if ($this->dateRange) {
            $dates = explode(' to ', $this->dateRange);
            $dateFrom = $dates[0] ?? now()->toDateString();
            $dateTo = $dates[1] ?? $dateFrom;
            $query->whereBetween('tanggal_laporan', [$dateFrom, $dateTo]);
        }

        if ($this->filterVerified !== '') {
            $query->where('verified_id', $this->filterVerified);
        }

        if ($this->sortBy === 'name') {
            $query->join('users', 'daily_reports.user_id', '=', 'users.id')
                ->select('daily_reports.*')
                ->orderBy('users.name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortBy, $this->sortDirection);
        }

        return [
            'reports' => $query->paginate(20),
        ];
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

            unset($this->validSteps[$reportId], $this->appNames[$reportId]);

            flash()->success('Laporan berhasil diverifikasi');
            $this->js("Flux.modal('verify-modal-{$reportId}').close()");
        }
    }

    public function validateAndPreview()
    {
        $this->validate([
            'uploadDate' => 'required|date',
            'excelFile' => 'required|file|mimes:xlsx,xls|max:10240',
        ], [
            'uploadDate.required' => 'Tanggal laporan wajib diisi',
            'uploadDate.date' => 'Format tanggal tidak valid',
            'excelFile.required' => 'File Excel wajib diupload',
            'excelFile.mimes' => 'File harus berformat Excel (.xlsx atau .xls)',
            'excelFile.max' => 'Ukuran file maksimal 10MB',
        ]);

        try {
            $spreadsheet = IOFactory::load($this->excelFile->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();
            
            $expectedHeaders = ['No.', 'Nama', 'NPK', 'Kota', 'No HP', 'Email', 'Jumlah Langkah', 'Jarak (KM)'];
            $actualHeaders = [];
            for ($col = 1; $col <= 8; $col++) {
                $actualHeaders[] = trim($sheet->getCellByColumnAndRow($col, 1)->getValue());
            }
            
            if ($actualHeaders !== $expectedHeaders) {
                $this->addError('excelFile', 'Format header tidak sesuai. Header harus: No., Nama, NPK, Kota, No HP, Email, Jumlah Langkah, Jarak (KM)');
                return;
            }
            
            $this->previewData = [];
            $highestRow = $sheet->getHighestRow();
            
            for ($row = 2; $row <= $highestRow; $row++) {
                $no = $sheet->getCellByColumnAndRow(1, $row)->getValue();
                $nama = $sheet->getCellByColumnAndRow(2, $row)->getValue();
                $npk = $sheet->getCellByColumnAndRow(3, $row)->getValue();
                $kota = $sheet->getCellByColumnAndRow(4, $row)->getValue();
                $nohp = $sheet->getCellByColumnAndRow(5, $row)->getValue();
                $email = $sheet->getCellByColumnAndRow(6, $row)->getValue() ?? '';
                $langkah = $sheet->getCellByColumnAndRow(7, $row)->getValue() ?? 0;
                $jarakkm = $sheet->getCellByColumnAndRow(8, $row)->getValue() ?? 0;
                
                if (empty($nama) && empty($langkah)) {
                    continue;
                }
                
                $this->previewData[] = [
                    'no' => $no,
                    'nama' => $nama,
                    'npk' => $npk,
                    'kota' => $kota,
                    'nohp' => $nohp,
                    'email' => $email,
                    'langkah' => $langkah,
                    'jarakkm' => $jarakkm,
                    'status' => '',
                ];
            }
            
            if (empty($this->previewData)) {
                $this->addError('excelFile', 'Tidak ada data yang ditemukan dalam file Excel');
                return;
            }
            $this->resetUpload();
            $this->js("Flux.modal('upload-excel-modal').close(); Flux.modal('preview-excel-modal').show();");
            
        } catch (\Exception $e) {
            $this->addError('excelFile', 'Gagal membaca file Excel: ' . $e->getMessage());
        }
    }

    public function checkData()
    {
        foreach ($this->previewData as $key => $row) {
            $user = User::where('email', $row['email']??'')->first();
            
            if (!$user) {
                $this->previewData[$key]['status'] = 'skip';
                continue;
            }
            
            $existingReport = DailyReport::where('user_id', $user->id)
                ->whereDate('tanggal_laporan', $this->uploadDate)
                ->first();
            
            if ($existingReport) {
                if ($row['langkah'] > $existingReport->langkah) {
                    $this->previewData[$key]['status'] = 'update';
                } else {
                    $this->previewData[$key]['status'] = 'tidak perlu diupdate';
                }
                $this->previewData[$key]['langkah_existing'] = $existingReport->langkah ?? 0;
            } else {
                $this->previewData[$key]['status'] = 'insert';
            }
        }
        
        // Sort by status priority: insert, update, tidak perlu diupdate, skip
        $statusOrder = ['insert' => 1, 'update' => 2, 'tidak perlu diupdate' => 3, 'skip' => 4];
        usort($this->previewData, function($a, $b) use ($statusOrder) {
            $orderA = $statusOrder[$a['status']] ?? 999;
            $orderB = $statusOrder[$b['status']] ?? 999;
            return $orderA <=> $orderB;
        });
        
        $this->dataChecked = true;
        flash()->success('Data berhasil dianalisa');
    }

    public function getStatusCounts()
    {
        $counts = ['skip' => 0, 'update' => 0, 'insert' => 0, 'tidak perlu diupdate' => 0];
        foreach ($this->previewData as $row) {
            if (isset($row['status']) && isset($counts[$row['status']])) {
                $counts[$row['status']]++;
            }
        }
        return $counts;
    }

    public function resetUpload()
    {
        $this->dataChecked = false; 
    }

    public function saveData()
    {
        $inserted = 0;
        $updated = 0;
        
        foreach ($this->previewData as $row) {
            if ($row['status'] === 'skip' || $row['status'] === 'tidak perlu diupdate') {
                continue;
            }

            $user = User::where('email', $row['email'])->first();
            if (!$user) continue;

            if ($row['status'] === 'insert') {
                $report = DailyReport::create([
                    'user_id' => $user->id,
                    'tanggal_laporan' => $this->uploadDate,
                    'langkah' => $row['langkah'] ?? 0,
                    'bukti_screenshot' => 'https://i.imghippo.com/files/ZqyA5776VTM.png',
                    'status_verifikasi' => StatusVerifikasi::DIVERIFIKASI,
                    'ocr_result' => json_encode(['steps' => (int)$row['langkah']??0]),
                    'count_document' => 1,
                    'verified_id' => VerifiedBy::ADMIN,
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                    'manual_verification_requested' => false,
                ]);

                app(ReportCalculationService::class)->recalculate($report->id, $row['langkah'] ?? 0);
                $inserted++;
            } elseif ($row['status'] === 'update') {
                $existingReport = DailyReport::where('user_id', $user->id)
                    ->whereDate('tanggal_laporan', $this->uploadDate)
                    ->first();
                    
                if ($existingReport) {
                    $existingReport->update([
                        'langkah' => $row['langkah'] ?? 0,
                        'bukti_screenshot' => 'https://i.imghippo.com/files/ZqyA5776VTM.png',
                        'status_verifikasi' => StatusVerifikasi::DIVERIFIKASI,
                        'ocr_result' => json_encode(['steps' => (int)$row['langkah']??0]),
                        'count_document' => 1,
                        'verified_id' => VerifiedBy::ADMIN,
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                        'manual_verification_requested' => false,
                    ]);
                    
                    app(ReportCalculationService::class)->recalculate($existingReport->id, $row['langkah']);
                    $updated++;
                }
            }
        }

        $this->js("Flux.modal('confirm-save-modal').close(); Flux.modal('preview-excel-modal').close();");
        flash()->success("Data berhasil disimpan! Data Baru: {$inserted}, Diperbarui: {$updated}");
        $this->resetUpload();
        $this->resetPage();
    }
};

?>

<div>
    <div class="flex flex-col gap-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-xl font-semibold text-gray-800 dark:text-zinc-100">
                    Daily Report
                </h2>
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    Daftar laporan harian karyawan
                </p>
            </div>
            <flux:modal.trigger name="upload-excel-modal">
                <flux:button icon="arrow-up-tray" variant="primary">Upload Excel Harian</flux:button>
            </flux:modal.trigger>
        </div>

        {{-- Filter Section --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <flux:label class="text-xs mb-1">Nama</flux:label>
                    <flux:input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama karyawan..." icon="magnifying-glass" />
                </div>
                <div>
                    <flux:label class="text-xs mb-1">Rentang Tanggal</flux:label>
                    <input type="text" id="dateRangePicker" class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-900 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-blue-500 focus:border-transparent" placeholder="Pilih rentang tanggal" readonly>
                </div>
                <div>
                    <flux:label class="text-xs mb-1">Status Verifikasi</flux:label>
                    <flux:select wire:model.live="filterVerified" placeholder="Semua Status">
                        <option value="">Semua Status</option>
                        <option value="{{ VerifiedBy::SISTEM->value }}">Sistem OCR</option>
                        <option value="{{ VerifiedBy::ADMIN->value }}">Admin</option>
                    </flux:select>
                </div>
            </div>
        </div>

        {{-- Table Section --}}
        <div class="bg-white dark:bg-zinc-800 rounded-lg shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">No</th>
                            <th wire:click="sort('tanggal_laporan')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800">
                                <div class="flex items-center gap-1">
                                    Tanggal
                                    @if($sortBy === 'tanggal_laporan')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sort('name')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800">
                                <div class="flex items-center gap-1">
                                    Nama Karyawan
                                    @if($sortBy === 'name')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sort('langkah')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800">
                                <div class="flex items-center gap-1">
                                    Langkah
                                    @if($sortBy === 'langkah')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sort('verified_id')" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-800">
                                <div class="flex items-center gap-1">
                                    Status
                                    @if($sortBy === 'verified_id')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            @if($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                            @endif
                                        </svg>
                                    @endif
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Screenshot</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                        @forelse ($reports as $index => $report)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-900">
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-zinc-100">
                                    {{ $reports->firstItem() + $index }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-zinc-100">
                                    {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d M Y') }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-zinc-100">
                                    <a href="{{ route('admin.detail-peserta', $report->user->id) }}" class="text-[#004444] hover:text-[#006666] dark:text-[#00aa88] dark:hover:text-[#00cc99] font-medium hover:underline">
                                    {{ $report->user->name }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-zinc-100">
                                    {{ number_format($report->langkah, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $report->verified_id->value === VerifiedBy::SISTEM->value ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                                        {{ $report->verified_id->value == VerifiedBy::SISTEM->value ? 'SISTEM' : 'ADMIN' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <a href="{{ $report->bukti_screenshot }}" data-fancybox="gallery" data-caption="{{ $report->user->name }} - {{ number_format($report->langkah, 0, ',', '.') }} langkah">
                                        <img src="{{ $report->bukti_screenshot }}" alt="Screenshot" class="h-12 w-12 object-cover rounded border border-gray-200 dark:border-zinc-600 cursor-pointer hover:opacity-75">
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <flux:modal.trigger name="verify-modal-{{ $report->id }}">
                                        <flux:button size="xs" icon="pencil-square" variant="primary">Verifikasi</flux:button>
                                    </flux:modal.trigger>
                                </td>
                            </tr>

                            {{-- Modal Verifikasi --}}
                            <flux:modal name="verify-modal-{{ $report->id }}" class="max-w-7xl" :dismissible="false">
                                <div class="space-y-6">
                                    <div>
                                        <flux:heading size="lg">Verifikasi Laporan Aktivitas</flux:heading>
                                        <flux:subheading>{{ $report->user->name }} - {{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d M Y') }}</flux:subheading>
                                    </div>

                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div class="space-y-4">
                                            <div>
                                                <flux:label>Bukti Screenshot</flux:label>
                                                <div class="mt-2 border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                                                    <img src="{{ $report->bukti_screenshot }}" alt="Bukti" class="w-full h-auto">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="space-y-4">
                                            <div class="grid grid-cols-2 gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Nama Peserta</p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->user->name }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Tanggal Laporan</p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($report->tanggal_laporan)->format('d M Y') }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Langkah Saat Ini</p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($report->langkah, 0, ',', '.') }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Diverifikasi Oleh</p>
                                                    <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $report->verified_id->value == VerifiedBy::SISTEM->value ? 'SISTEM OCR' : 'ADMIN' }}</p>
                                                </div>
                                            </div>

                                            <div wire:key="validSteps-{{ $report->id }}">
                                                <flux:label>Jumlah Langkah Valid <span class="text-red-500">*</span></flux:label>
                                                <flux:input wire:model.blur="validSteps.{{ $report->id }}" type="number" min="0" placeholder="Masukkan jumlah langkah yang valid" class="mt-1" />
                                                @error('validSteps.' . $report->id)
                                                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div wire:key="appNames-{{ $report->id }}">
                                                <flux:label>Nama Aplikasi <span class="text-zinc-400 text-xs">(Opsional)</span></flux:label>
                                                <flux:input wire:model.blur="appNames.{{ $report->id }}" type="text" placeholder="Contoh: Google Fit, Samsung Health" class="mt-1" />
                                                @error('appNames.' . $report->id)
                                                    <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="pt-4 border-t border-zinc-200 dark:border-zinc-700">
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">Pastikan data yang Anda masukkan sudah benar sebelum melakukan verifikasi.</p>
                                                <flux:button wire:click="approveReport({{ $report->id }})" icon="check" variant="primary" class="w-full">
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
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-12 text-center text-gray-500 dark:text-zinc-400">
                                    Tidak ada data laporan Di Hari Ini
                                    <br>
                                    Pilih Tanggal Lainnya untuk melihat data laporan yang lain.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $reports->links() }}
        </div>
    </div>

    {{-- Modal 1: Upload Excel --}}
    <flux:modal name="upload-excel-modal" class="max-w-2xl" :dismissible="false">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Upload File Excel Langkah Harian</flux:heading>
                <flux:subheading>Upload file Excel untuk import data langkah harian</flux:subheading>
            </div>

            <div class="space-y-4">
                <div>
                    <flux:label>Tanggal Laporan <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="uploadDate" type="date" class="mt-1" />
                    @error('uploadDate')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <flux:label>File Excel <span class="text-red-500">*</span></flux:label>
                    <input wire:model="excelFile" type="file" accept=".xlsx,.xls" class="mt-1 block w-full text-sm text-gray-900 dark:text-zinc-100 border border-gray-300 dark:border-zinc-600 rounded-lg cursor-pointer bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-zinc-800 dark:file:text-blue-400">
                    <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">Format: .xlsx atau .xls (Max: 10MB)</p>
                    @error('excelFile')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                    <p class="text-xs text-yellow-800 dark:text-yellow-200 font-medium mb-1">Format Header Excel:</p>
                    <p class="text-xs text-yellow-700 dark:text-yellow-300">No., Nama, NPK, Kota, No HP, Email, Jumlah Langkah, Jarak (KM)</p>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="validateAndPreview" icon="eye" variant="primary">Preview</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Modal 2: Preview Data --}}
    <flux:modal name="preview-excel-modal" class="max-w-7xl" :dismissible="false">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Validasi Isi Data</flux:heading>
                <flux:subheading>Preview data pada file Excel</flux:subheading>
            </div>

            <div class="space-y-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <p class="text-sm text-blue-800 dark:text-blue-200">
                        <strong>Tanggal Laporan:</strong> {{ $uploadDate ? \Carbon\Carbon::parse($uploadDate)->format('d M Y') : '-' }} | 
                        <strong>Total Data:</strong> {{ count($previewData) }} baris
                    </p>
                </div>

                @if($dataChecked)
                    @php
                        $counts = $this->getStatusCounts();
                    @endphp
                    <div class="bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <p class="text-sm text-gray-800 dark:text-gray-200 mb-2 font-medium">Hasil Analisa Data:</p>
                        <div class="flex flex-wrap gap-3">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                Dilewati: {{ $counts['skip'] }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Diperbarui: {{ $counts['update'] }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Data Baru: {{ $counts['insert'] }}
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                                Sudah Terbaru: {{ $counts['tidak perlu diupdate'] }}
                            </span>
                        </div>
                    </div>
                @endif

                <div class="max-h-96 overflow-y-auto border border-gray-200 dark:border-zinc-700 rounded-lg">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-900 sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase">NPK</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase">Kota</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase">Langkah</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-zinc-400 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700">
                            @foreach($previewData as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-900">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-zinc-100">{{ $row['no'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-zinc-100">{{ $row['nama'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-zinc-100">{{ $row['npk'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-zinc-100">{{ $row['kota'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-zinc-100">{{ $row['email'] }}</td>
                                    <td class="px-4 py-2 text-sm font-semibold text-gray-900 dark:text-zinc-100 inline-flex whitespace-nowrap">
                                        @if(isset($row['status']))
                                            @if($row['status'] === 'update')
                                                <span class="text-gray-600 dark:text-gray-400">{{ number_format($row['langkah_existing'] ?? 0, 0, ',', '.') }}</span>
                                                <span class="text-green-600 dark:text-green-400"> → {{ number_format($row['langkah'], 0, ',', '.') }}</span>
                                            @elseif($row['status'] === 'insert')
                                                <span class="text-gray-600 dark:text-gray-400">0</span>
                                                <span class="text-green-600 dark:text-green-400"> → {{ number_format($row['langkah'], 0, ',', '.') }}</span>
                                            @elseif($row['status'] === 'tidak perlu diupdate')
                                                <span class="text-green-600 dark:text-green-400 mr-1">{{ number_format($row['langkah_existing'] ?? 0, 0, ',', '.') }}</span>
                                                <span class="text-red-600 dark:text-red-400"> ({{ number_format($row['langkah'], 0, ',', '.') }})</span>
                                            @else
                                                {{ number_format($row['langkah'], 0, ',', '.') }}
                                            @endif
                                        @else
                                            {{ number_format($row['langkah'], 0, ',', '.') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        @if(isset($row['status']))
                                            @if($row['status'] === 'skip')
                                                <span class="inline-flex whitespace-nowrap items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">Dilewati</span>
                                            @elseif($row['status'] === 'update')
                                                <span class="inline-flex whitespace-nowrap items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Diperbarui</span>
                                            @elseif($row['status'] === 'insert')
                                                <span class="inline-flex whitespace-nowrap items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Data Baru</span>
                                            @elseif($row['status'] === 'tidak perlu diupdate')
                                                <span class="inline-flex whitespace-nowrap items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">Sudah Terbaru</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close wire:click="resetUpload">
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                @if(!$dataChecked)
                    <flux:button wire:click="checkData" icon="document-magnifying-glass" variant="primary">Check</flux:button>
                @else
                    <flux:modal.trigger name="confirm-save-modal">
                        <flux:button icon="check" variant="primary">Simpan Data</flux:button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>
    </flux:modal>

    {{-- Modal Konfirmasi Simpan --}}
    <flux:modal name="confirm-save-modal" class="max-w-md" :dismissible="false">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Konfirmasi Simpan Data</flux:heading>
                <flux:subheading>Apakah Anda yakin ingin menyimpan data ini?</flux:subheading>
            </div>

            @php
                $counts = $this->getStatusCounts();
            @endphp
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">Data yang akan diproses:</p>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Data Baru:</span>
                        <span class="font-semibold text-green-600 dark:text-green-400">{{ $counts['insert'] }} data</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Diperbarui:</span>
                        <span class="font-semibold text-blue-600 dark:text-blue-400">{{ $counts['update'] }} data</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Dilewati:</span>
                        <span class="font-semibold text-gray-600 dark:text-gray-400">{{ $counts['skip'] }} data</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Sudah Terbaru:</span>
                        <span class="font-semibold text-yellow-600 dark:text-yellow-400">{{ $counts['tidak perlu diupdate'] }} data</span>
                    </div>
                </div>
            </div>
            <div class="mt-3 text-center">
                Data Akan disimpan di Tanggal Laporan:
                <br>
                <span class="font-bold text-2xl">
                {{ $uploadDate ? \Carbon\Carbon::parse($uploadDate)->format('d M Y') : '-' }}
                </span>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost">Batal</flux:button>
                </flux:modal.close>
                <flux:button wire:click="saveData" icon="check" variant="primary">Ya, Simpan</flux:button>
            </div>
        </div>
    </flux:modal>
</div>

@push('styles')
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    /* Dark mode styles for daterangepicker */
    .dark .daterangepicker {
        background-color: #18181b !important;
        border-color: #3f3f46 !important;
    }
    .dark .daterangepicker:before,
    .dark .daterangepicker:after {
        border-bottom-color: #18181b !important;
    }
    .dark .daterangepicker .calendar-table {
        background-color: #18181b !important;
        border-color: #3f3f46 !important;
    }
    .dark .daterangepicker .calendar-table thead tr th,
    .dark .daterangepicker .calendar-table tbody tr td {
        color: #e4e4e7 !important;
    }
    .dark .daterangepicker .calendar-table thead tr {
        background-color: #27272a !important;
    }
    .dark .daterangepicker td.off,
    .dark .daterangepicker td.off.in-range,
    .dark .daterangepicker td.off.start-date,
    .dark .daterangepicker td.off.end-date {
        color: #71717a !important;
    }
    .dark .daterangepicker td.available:hover,
    .dark .daterangepicker th.available:hover {
        background-color: #3f3f46 !important;
        color: #fafafa !important;
    }
    .dark .daterangepicker td.in-range {
        background-color: #3f3f46 !important;
        color: #e4e4e7 !important;
    }
    .dark .daterangepicker td.active,
    .dark .daterangepicker td.active:hover {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
    }
    .dark .daterangepicker select.monthselect,
    .dark .daterangepicker select.yearselect {
        background-color: #27272a !important;
        color: #e4e4e7 !important;
        border-color: #3f3f46 !important;
    }
    .dark .daterangepicker .ranges li {
        color: #e4e4e7 !important;
    }
    .dark .daterangepicker .ranges li:hover {
        background-color: #3f3f46 !important;
    }
    .dark .daterangepicker .ranges li.active {
        background-color: #3b82f6 !important;
        color: #ffffff !important;
    }
    .dark .daterangepicker .drp-buttons {
        border-top-color: #3f3f46 !important;
    }
    .dark .daterangepicker .drp-selected {
        color: #e4e4e7 !important;
    }
</style>
@endpush

@push('scripts')
<script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
$(function() {
    $('#dateRangePicker').daterangepicker({
        startDate: moment(),
        endDate: moment(),
        locale: {
            format: 'YYYY-MM-DD',
            separator: ' to ',
            applyLabel: 'Terapkan',
            cancelLabel: 'Batal',
            fromLabel: 'Dari',
            toLabel: 'Sampai',
            customRangeLabel: 'Custom',
            weekLabel: 'W',
            daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
            monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            firstDay: 1
        },
        ranges: {
           'Hari Ini': [moment(), moment()],
           'Kemarin': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
           '7 Hari Terakhir': [moment().subtract(6, 'days'), moment()],
           '30 Hari Terakhir': [moment().subtract(29, 'days'), moment()],
           'Bulan Ini': [moment().startOf('month'), moment().endOf('month')],
           'Bulan Lalu': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        }
    }, function(start, end) {
        @this.set('dateRange', start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
    });

});
</script>
@endpush
