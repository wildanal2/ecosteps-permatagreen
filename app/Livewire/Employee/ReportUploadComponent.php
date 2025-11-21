<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\{Storage, Auth, Http, Log};
use Illuminate\Validation\Rule;
use App\Models\DailyReport;
use App\Enums\StatusVerifikasi;
use App\Services\ImageProcessingService;
use App\Helpers\UploadDebugger;

class ReportUploadComponent extends Component
{
    use WithFileUploads;

    public $photo;
    public $isUploading = false;
    public $uploadProgress = 0;
    public $uploadedUrl = null;
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
            // Debug environment on first upload attempt
            UploadDebugger::debugEnvironment();

            // Reset previous errors
            $this->resetErrorBag();

            // Check if file exists and is valid
            if (!$this->photo) {
                Log::warning('updatedPhoto called but no photo present');
                return;
            }

            // Debug file details
            UploadDebugger::debugFile($this->photo);



            // Check if it's actually an image
            $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
            if (!in_array($this->photo->getMimeType(), $allowedMimes)) {
                $this->addError('photo', 'File harus berupa gambar dengan format JPEG, PNG, JPG, GIF, atau WebP.');
                return;
            }

            // Validate with custom messages
            $this->validate([
                'photo' => 'file|image|mimes:' . implode(',', config('upload.allowed_extensions'))
            ], $this->messages());

            Log::info('File validation passed', [
                'filename' => $this->photo->getClientOriginalName(),
                'size' => $this->photo->getSize(),
                'mime_type' => $this->photo->getMimeType(),
                'validation_rules' => 'file|image|mimes:' . implode(',', config('upload.allowed_extensions'))
            ]);

        } catch (\Exception $e) {
            Log::error('File validation error', [
                'error' => $e->getMessage(),
                'file_info' => $this->photo ? [
                    'name' => $this->photo->getClientOriginalName(),
                    'size' => $this->photo->getSize(),
                    'mime' => $this->photo->getMimeType()
                ] : 'No file'
            ]);

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
            // Comprehensive file validation
            $this->validateFileIntegrity();

            // Validate with detailed error messages
            $validated = $this->validate($this->rules(), $this->messages());

            Log::info('Starting upload process', [
                'user_id' => Auth::id(),
                'file_name' => $this->photo->getClientOriginalName(),
                'file_size' => $this->photo->getSize(),
                'mime_type' => $this->photo->getMimeType()
            ]);

            $user = Auth::user();
            $email = str_replace(['@', '.'], ['_', '_'], $user->email);
            $date = now()->format('Y-m-d_His');
            $filename = "{$date}.{$this->photo->extension()}";
            $path = "reports/{$email}/{$filename}";

            $this->uploadProgress = 20;

            // Process image if needed
            $imageProcessor = new ImageProcessingService();
            $processedPhoto = $imageProcessor->processImage($this->photo);

            $this->uploadProgress = 40;

            // Upload to S3
            Log::info('Starting file upload to S3', [
                'user_id' => $user->id,
                'filename' => $filename,
                'path' => $path,
                'original_size' => $this->photo->getSize(),
                'processed_size' => $processedPhoto->getSize()
            ]);

            try {
                $result = Storage::disk('s3')->putFileAs(
                    dirname($path),
                    $processedPhoto,
                    basename($path),
                    'public'
                );
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

            $this->uploadProgress = 70;

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
                    'manual_verification_requested' => false,
                    'manual_verification_requested_at' => null,
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
                    'environment' => config('app.env', 'staging'),
                ]);

                $response = Http::timeout(10)
                    ->withHeaders(['x-api-key' => config('app.ocr_api_key')])
                    ->post($ocrEndpoint, [
                        'report_id' => $report->id,
                        'user_id' => $user->id,
                        's3_url' => $s3Url,
                        'environment' => config('app.env', 'staging'),
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed during upload', [
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
                'file_info' => $this->getFileInfo()
            ]);

            // Handle validation errors specifically
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }

            $this->isUploading = false;
            $this->uploadProgress = 0;
        } catch (\Exception $e) {
            Log::error('Report upload failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file_info' => $this->getFileInfo()
            ]);

            $this->addError('photo', $e->getMessage());
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }
    }

    /**
     * Get file information for logging
     */
    private function getFileInfo(): array
    {
        if (!$this->photo) {
            return ['status' => 'no_file'];
        }

        try {
            return [
                'name' => $this->photo->getClientOriginalName(),
                'size' => $this->photo->getSize(),
                'mime_type' => $this->photo->getMimeType(),
                'extension' => $this->photo->extension(),
                'is_valid' => $this->photo->isValid(),
                'error' => $this->photo->getError()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error_getting_info',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate file before processing
     */
    private function validateFileIntegrity(): void
    {
        if (!$this->photo) {
            throw new \Exception('Tidak ada file yang dipilih.');
        }

        if (!$this->photo->isValid()) {
            $error = $this->photo->getError();
            $errorMessages = config('upload.upload_errors');

            $message = $errorMessages[$error] ?? 'File upload error code: ' . $error;

            Log::error('File upload error', [
                'error_code' => $error,
                'error_message' => $message,
                'file_info' => $this->getFileInfo()
            ]);

            throw new \Exception($message);
        }



        // Check MIME type
        if (!in_array($this->photo->getMimeType(), config('upload.allowed_mime_types'))) {
            throw new \Exception(config('upload.validation_messages.mimes'));
        }

        // Additional check for image validity
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
        return view('livewire.employee.report-upload-component');
    }
}
