<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class HandleLivewireUploadErrors
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this is a Livewire file upload request
        if ($request->is('livewire/upload-file*')) {
            Log::info('Livewire upload request received', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'content_length' => $request->header('Content-Length'),
                'content_type' => $request->header('Content-Type'),
                'user_agent' => $request->header('User-Agent'),
                'files_count' => count($request->allFiles()),
            ]);

            // Check for common upload issues
            $this->validateUploadRequest($request);
        }

        $response = $next($request);

        // Log response for upload requests
        if ($request->is('livewire/upload-file*')) {
            Log::info('Livewire upload response', [
                'status' => $response->getStatusCode(),
                'content' => $response->getContent(),
            ]);

            // Enhance error messages for 422 responses
            if ($response->getStatusCode() === 422) {
                $content = json_decode($response->getContent(), true);
                
                if (isset($content['errors'])) {
                    $enhancedErrors = $this->enhanceErrorMessages($content['errors']);
                    $content['errors'] = $enhancedErrors;
                    $content['message'] = $this->getDetailedErrorMessage($enhancedErrors);
                    
                    $response->setContent(json_encode($content));
                }
            }
        }

        return $response;
    }

    /**
     * Validate upload request for common issues
     */
    private function validateUploadRequest(Request $request): void
    {
        // Check if request has files
        if (empty($request->allFiles())) {
            Log::warning('Upload request without files', [
                'content_length' => $request->header('Content-Length'),
                'post_data' => $request->all()
            ]);
        }

        // Check content length vs PHP limits
        $contentLength = (int) $request->header('Content-Length', 0);
        $maxPostSize = $this->parseSize(ini_get('post_max_size'));
        $maxUploadSize = $this->parseSize(ini_get('upload_max_filesize'));

        if ($contentLength > $maxPostSize) {
            Log::error('Request exceeds post_max_size', [
                'content_length' => $contentLength,
                'post_max_size' => $maxPostSize,
                'post_max_size_ini' => ini_get('post_max_size')
            ]);
        }

        if ($contentLength > $maxUploadSize) {
            Log::error('Request exceeds upload_max_filesize', [
                'content_length' => $contentLength,
                'upload_max_filesize' => $maxUploadSize,
                'upload_max_filesize_ini' => ini_get('upload_max_filesize')
            ]);
        }
    }

    /**
     * Parse size string to bytes
     */
    private function parseSize(string $size): int
    {
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;

        switch ($last) {
            case 'g':
                $size *= 1024;
            case 'm':
                $size *= 1024;
            case 'k':
                $size *= 1024;
        }

        return $size;
    }

    /**
     * Enhance error messages with more details
     */
    private function enhanceErrorMessages(array $errors): array
    {
        $enhanced = [];

        foreach ($errors as $field => $messages) {
            $enhanced[$field] = [];
            
            foreach ($messages as $message) {
                if (str_contains($message, 'failed to upload')) {
                    $enhanced[$field][] = $this->getDetailedUploadError();
                } else {
                    $enhanced[$field][] = $message;
                }
            }
        }

        return $enhanced;
    }

    /**
     * Get detailed upload error message
     */
    private function getDetailedUploadError(): string
    {
        $reasons = [
            'File terlalu besar (melebihi batas server: ' . ini_get('upload_max_filesize') . ')',
            'Format file tidak didukung (hanya JPEG, PNG, JPG, GIF, WebP)',
            'File rusak atau tidak valid',
            'Koneksi terputus saat upload',
            'Server sedang sibuk, coba lagi'
        ];

        return 'Upload gagal. Kemungkinan penyebab: ' . implode(', ', $reasons);
    }

    /**
     * Get detailed error message for response
     */
    private function getDetailedErrorMessage(array $errors): string
    {
        $messages = [];
        
        foreach ($errors as $field => $fieldErrors) {
            $messages = array_merge($messages, $fieldErrors);
        }

        return implode(' ', $messages);
    }
}