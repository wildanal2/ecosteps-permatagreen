<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ImageProcessingService
{
    public function processImage(UploadedFile $file, int $maxWidth = 1080, int $quality = 85): UploadedFile
    {
        try {
            // Check if file size is less than 1MB, return original if so
            if ($file->getSize() <= 563200) { // 550KB in bytes (0.55MB)
                return $file;
            }

            Log::info('Processing image', [
                'original_size' => $file->getSize(),
                'original_name' => $file->getClientOriginalName()
            ]);

            $extension = strtolower($file->getClientOriginalExtension());
            $imagePath = $file->getRealPath();

            // Create image resource based on file type
            $image = null;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $image = imagecreatefromjpeg($imagePath);
                    break;
                case 'png':
                    $image = imagecreatefrompng($imagePath);
                    break;
                case 'gif':
                    $image = imagecreatefromgif($imagePath);
                    break;
                case 'webp':
                    $image = imagecreatefromwebp($imagePath);
                    break;
                default:
                    return $file; // Unsupported format
            }

            if (!$image) {
                Log::warning('Failed to create image resource', ['file' => $file->getClientOriginalName()]);
                return $file;
            }

            // Get original dimensions
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Calculate new dimensions if needed
            $newWidth = $originalWidth;
            $newHeight = $originalHeight;

            if ($originalWidth > $maxWidth) {
                $newWidth = $maxWidth;
                $newHeight = intval(($originalHeight * $maxWidth) / $originalWidth);

                Log::info('Image will be resized', [
                    'from' => "{$originalWidth}x{$originalHeight}",
                    'to' => "{$newWidth}x{$newHeight}"
                ]);

                // Create new image with new dimensions
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Preserve transparency for PNG and GIF
                if ($extension === 'png' || $extension === 'gif') {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                    $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                    imagefill($resizedImage, 0, 0, $transparent);
                }

                // Resize the image
                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
                imagedestroy($image);
                $image = $resizedImage;
            }

            // Create temporary file
            $tempPath = tempnam(sys_get_temp_dir(), 'processed_image_');

            // Save with compression based on format
            $success = false;
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    $success = imagejpeg($image, $tempPath, $quality);
                    break;
                case 'png':
                    // PNG compression level (0-9, where 9 is maximum compression)
                    $pngCompression = intval((100 - $quality) / 11);
                    $success = imagepng($image, $tempPath, $pngCompression);
                    break;
                case 'gif':
                    $success = imagegif($image, $tempPath);
                    break;
                case 'webp':
                    $success = imagewebp($image, $tempPath, $quality);
                    break;
            }

            imagedestroy($image);

            if (!$success) {
                Log::warning('Failed to save processed image', ['file' => $file->getClientOriginalName()]);
                return $file;
            }

            // Create new UploadedFile from processed image
            $processedFile = new UploadedFile(
                $tempPath,
                $file->getClientOriginalName(),
                $file->getMimeType(),
                null,
                true
            );

            Log::info('Image processed successfully', [
                'original_size' => $file->getSize(),
                'processed_size' => $processedFile->getSize(),
                'compression_ratio' => round((1 - ($processedFile->getSize() / $file->getSize())) * 100, 2) . '%'
            ]);

            return $processedFile;

        } catch (\Exception $e) {
            Log::error('Image processing failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName()
            ]);

            // Return original file if processing fails
            return $file;
        }
    }
}
