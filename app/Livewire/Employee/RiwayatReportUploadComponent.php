<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\{Storage, Auth, Http, Log};
use App\Models\DailyReport;
use App\Enums\StatusVerifikasi;
use App\Services\ImageProcessingService;
use App\Helpers\UploadDebugger;
use Illuminate\Support\Carbon;

class RiwayatReportUploadComponent extends Component
{
    use WithFileUploads;

    public $photo;
    public $selectedDate;
    public $isUploading = false;
    public $uploadProgress = 0;
    public $showSuccess = false;

    protected function rules()
    {
        return [
            'photo' => 'required|file|image|mimes:' . implode(',', config('upload.allowed_extensions')),
        ];
    }

    protected function messages()
    {
        return [
            'photo.required' => config('upload.validation_messages.required'),
            'photo.file' => config('upload.validation_messages.file'),
            'photo.image' => config('upload.validation_messages.image'),
            'photo.mimes' => config('upload.validation_messages.mimes'),
            'photo.max' => config('upload.validation_messages.max'),
        ];
    }

    public function updatedPhoto()
    {
        try {
            $this->resetErrorBag();
            if (!$this->photo) return;

            UploadDebugger::debugFile($this->photo);

            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            if (!in_array($this->photo->getMimeType(), $allowedMimes)) {
                $this->addError('photo', 'File harus berupa gambar dengan format JPEG, PNG, JPG, GIF, atau WebP.');
                return;
            }

            $this->validate([
                'photo' => 'file|image|mimes:' . implode(',', config('upload.allowed_extensions'))
            ], $this->messages());

        } catch (\Exception $e) {
            $this->addError('photo', 'Terjadi kesalahan saat memvalidasi file: ' . $e->getMessage());
        }
    }

    public function removePhoto()
    {
        $this->reset('photo');
        $this->resetValidation('photo');
    }

    public function uploadReport()
    {
        $this->isUploading = true;
        $this->uploadProgress = 0;

        try {
            $this->validateFileIntegrity();
            $validated = $this->validate($this->rules(), $this->messages());

            $user = Auth::user();
            $email = str_replace(['@', '.'], ['_', '_'], $user->email);
            $date = now()->format('Y-m-d_His');
            $filename = "{$date}.{$this->photo->extension()}";
            $path = "reports/{$email}/{$filename}";

            $this->uploadProgress = 20;

            $imageProcessor = new ImageProcessingService();
            $processedPhoto = $imageProcessor->processImage($this->photo);

            $this->uploadProgress = 40;

            $result = Storage::disk('s3')->putFileAs(
                dirname($path),
                $processedPhoto,
                basename($path),
                'public'
            );

            if (!$result) {
                throw new \Exception('Gagal melakukan proses upload ke server, coba beberapa saat lagi');
            }

            $this->uploadProgress = 70;

            $s3Url = rtrim(config('filesystems.disks.s3.url'), '/') . '/' . $path;
            $reportDate = Carbon::parse($this->selectedDate);

            $report = DailyReport::where('user_id', $user->id)
                ->whereDate('tanggal_laporan', $reportDate)
                ->first();

            if ($report) {
                $report->update([
                    'bukti_screenshot' => $s3Url,
                    'status_verifikasi' => StatusVerifikasi::PENDING,
                    'manual_verification_requested' => false,
                    'manual_verification_requested_at' => null,
                ]);
                $report->increment('count_document');
            } else {
                $report = DailyReport::create([
                    'user_id' => $user->id,
                    'tanggal_laporan' => $reportDate,
                    'bukti_screenshot' => $s3Url,
                    'status_verifikasi' => StatusVerifikasi::PENDING,
                    'langkah' => 0,
                    'co2e_reduction_kg' => 0,
                    'poin' => 0,
                    'pohon' => 0,
                    'count_document' => 1,
                ]);
            }

            $this->uploadProgress = 80;

            $ocrApiUrl = rtrim(config('app.ocr_api_url', 'http://localhost:8000'), '/');
            $ocrEndpoint = $ocrApiUrl . '/api/v1/ocr-ecosteps';

            try {
                $response = Http::timeout(10)
                    ->withHeaders(['x-api-key' => config('app.ocr_api_key')])
                    ->post($ocrEndpoint, [
                        'report_id' => $report->id,
                        'user_id' => $user->id,
                        's3_url' => $s3Url,
                        'environment' => config('app.env', 'staging'),
                    ]);
            } catch (\Exception $e) {
                Log::error('Failed to send OCR request', ['error' => $e->getMessage()]);
            }

            $this->uploadProgress = 100;
            $this->showSuccess = true;

            sleep(1);

            $this->dispatch('close-modal', name: 'upload-riwayat-' . $this->selectedDate);
            $this->dispatch('refresh-riwayat');
            flash()->success('Laporan berhasil diunggah!');
            $this->reset(['photo', 'isUploading', 'uploadProgress', 'showSuccess']);
        } catch (\Exception $e) {
            $this->addError('photo', $e->getMessage());
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }
    }

    private function validateFileIntegrity(): void
    {
        if (!$this->photo) {
            throw new \Exception('Tidak ada file yang dipilih.');
        }

        if (!$this->photo->isValid()) {
            throw new \Exception('File tidak valid.');
        }

        if (!in_array($this->photo->getMimeType(), config('upload.allowed_mime_types'))) {
            throw new \Exception(config('upload.validation_messages.mimes'));
        }

        try {
            $imageInfo = getimagesize($this->photo->getRealPath());
            if (!$imageInfo) {
                throw new \Exception('File bukan gambar yang valid.');
            }
        } catch (\Exception $e) {
            throw new \Exception('File gambar tidak valid atau rusak.');
        }
    }

    public function render()
    {
        return view('livewire.employee.riwayat-report-upload-component');
    }
}
