<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\{Storage, Auth, Http, Log};
use Illuminate\Validation\Rule;
use App\Models\DailyReport;
use App\Enums\StatusVerifikasi;

class ReportUploadComponent extends Component
{
    use WithFileUploads;

    public $photo;
    public $isUploading = false;
    public $uploadProgress = 0;
    public $uploadedUrl = null;
    public $showSuccess = false;

    protected $rules = [
        'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
    ];

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);
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
            $validated = $this->validate();

            $user = Auth::user();
            $email = str_replace(['@', '.'], ['_', '_'], $user->email);
            $date = now()->format('Y-m-d_His');
            $filename = "{$date}.{$this->photo->extension()}";
            $path = "reports/{$email}/{$filename}";

            $this->uploadProgress = 30;

            // Upload to S3
            Log::info('Starting file upload to S3', [
                'user_id' => $user->id,
                'filename' => $filename,
                'path' => $path,
                'file_size' => $this->photo->getSize()
            ]);

            try {
                $result = Storage::disk('s3')->put($path, file_get_contents($this->photo->getRealPath()), 'public');
            } catch (\Exception $e) {
                Log::error('S3 Upload Exception', [
                    'user_id' => $user->id,
                    'path' => $path,
                    'filename' => $filename,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Gagal melakukan proses upload ke server, coba beberapa saat lagi');
            }

            if (!$result) {
                Log::error('Failed to upload file to S3', [
                    'user_id' => $user->id,
                    'path' => $path,
                    'filename' => $filename
                ]);
                throw new \Exception('Gagal melakukan proses upload ke server, coba beberapa saat lagi');
            }

            Log::info('File uploaded to S3 successfully', [
                'user_id' => $user->id,
                'path' => $path,
                's3_url' => rtrim(config('filesystems.disks.s3.url'), '/') . '/' . $path
            ]);

            $this->uploadProgress = 60;

            $s3Url = rtrim(config('filesystems.disks.s3.url'), '/') . '/' . $path;

            // Create or update DailyReport
            Log::info('Creating/updating daily report', [
                'user_id' => $user->id,
                's3_url' => $s3Url
            ]);

            $report = DailyReport::where('user_id', $user->id)
                ->whereDate('tanggal_laporan', today())
                ->first();

            if ($report) {
                $report->update([
                    'bukti_screenshot' => $s3Url,
                    'status_verifikasi' => StatusVerifikasi::PENDING,
                ]);
                $report->increment('count_document');
                Log::info('Daily report updated', ['report_id' => $report->id]);
            } else {
                $report = DailyReport::create([
                    'user_id' => $user->id,
                    'tanggal_laporan' => today(),
                    'bukti_screenshot' => $s3Url,
                    'status_verifikasi' => StatusVerifikasi::PENDING,
                    'langkah' => 0,
                    'co2e_reduction_kg' => 0,
                    'poin' => 0,
                    'pohon' => 0,
                    'count_document' => 1,
                ]);
                Log::info('Daily report created', ['report_id' => $report->id]);
            }

            $this->uploadProgress = 80;

            // Send to FastAPI OCR
            $ocrApiUrl = rtrim(config('app.ocr_api_url', 'http://localhost:8000'), '/');
            $ocrEndpoint = $ocrApiUrl . '/api/v1/ocr-ecosteps';

            try {
                Log::info('Sending OCR request to FastAPI', [
                    'endpoint' => $ocrEndpoint,
                    'report_id' => $report->id,
                    'user_id' => $user->id,
                    's3_url' => $s3Url,
                ]);

                $response = Http::timeout(10)
                    ->withHeaders(['x-api-key' => config('app.ocr_api_key')])
                    ->post($ocrEndpoint, [
                        'report_id' => $report->id,
                        'user_id' => $user->id,
                        's3_url' => $s3Url,
                    ]);

                if ($response->successful()) {
                    Log::info('OCR request sent successfully', ['status' => $response->status()]);
                } else {
                    Log::warning('OCR request failed', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send OCR request', [
                    'error' => $e->getMessage(),
                    'report_id' => $report->id,
                ]);
            }

            $this->uploadProgress = 100;
            $this->showSuccess = true;

            sleep(1);

            $this->dispatch('close-modal', name: 'upload-harian');
            $this->dispatch('refresh-dashboard');
            $this->dispatch('chart-updated');
            // Show success notification
            flash()->success('Laporan berhasil diunggah!');
            $this->reset(['photo', 'isUploading', 'uploadProgress', 'uploadedUrl', 'showSuccess']);
        } catch (\Exception $e) {
            Log::error('Report upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addError('photo', $e->getMessage());
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }
    }

    public function render()
    {
        return view('livewire.employee.report-upload-component');
    }
}
