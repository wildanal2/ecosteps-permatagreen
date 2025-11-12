<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

            // Update progress to indicate processing
            $this->uploadProgress = 50;

            // Upload to S3
            $result = Storage::disk('s3')->put($path, file_get_contents($this->photo->getRealPath()), 'public');

            if (!$result) {
                throw new \Exception('Gagal mengupload file ke S3');
            }

            $this->uploadProgress = 100;

            $this->uploadedUrl = rtrim(config('filesystems.disks.s3.url'), '/') . '/' . $path;
            $this->showSuccess = true;

            // Reset photo after successful upload
            $this->photo = null;

            // Dispatch event to open URL in new tab
            $this->dispatch('open-url', url: $this->uploadedUrl);

            // Dispatch event to close modal
            $this->dispatch('close-modal', name: 'upload-harian');

            // Reset state after a delay
            $this->reset(['isUploading', 'uploadProgress', 'uploadedUrl', 'showSuccess']);
        } catch (\Exception $e) {
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
