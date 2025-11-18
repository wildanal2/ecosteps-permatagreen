<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;

class UploadDebugger
{
    /**
     * Debug upload environment and settings
     */
    public static function debugEnvironment(): array
    {
        $info = [
            'php_version' => PHP_VERSION,
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'memory_limit' => ini_get('memory_limit'),
            'file_uploads' => ini_get('file_uploads') ? 'enabled' : 'disabled',
            'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            'max_file_uploads' => ini_get('max_file_uploads'),
        ];

        Log::info('Upload environment debug', $info);
        return $info;
    }

    /**
     * Debug uploaded file details
     */
    public static function debugFile(?UploadedFile $file): array
    {
        if (!$file) {
            return ['status' => 'no_file'];
        }

        $info = [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'extension' => $file->extension(),
            'client_extension' => $file->getClientOriginalExtension(),
            'is_valid' => $file->isValid(),
            'error_code' => $file->getError(),
            'error_message' => self::getUploadErrorMessage($file->getError()),
            'real_path' => $file->getRealPath(),
            'temp_name' => $file->getPathname(),
        ];

        // Additional image checks if it's an image
        if (str_starts_with($file->getMimeType(), 'image/')) {
            try {
                $imageInfo = getimagesize($file->getRealPath());
                if ($imageInfo) {
                    $info['image_width'] = $imageInfo[0];
                    $info['image_height'] = $imageInfo[1];
                    $info['image_type'] = $imageInfo[2];
                    $info['image_bits'] = $imageInfo['bits'] ?? null;
                    $info['image_channels'] = $imageInfo['channels'] ?? null;
                }
            } catch (\Exception $e) {
                $info['image_error'] = $e->getMessage();
            }
        }

        Log::info('Upload file debug', $info);
        return $info;
    }

    /**
     * Get human readable upload error message
     */
    public static function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE from form',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by PHP extension',
        ];

        return $errors[$errorCode] ?? "Unknown error code: {$errorCode}";
    }
}