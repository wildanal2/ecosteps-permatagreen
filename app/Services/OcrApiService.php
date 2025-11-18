<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class OcrApiService
{
    private string $baseUrl;
    private string $apiKey;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = $this->getOcrApiUrl();
        $this->apiKey = config('app.ocr_api_key', '');
        $this->timeout = 10;
    }

    private function getOcrApiUrl(): string
    {
        // Priority: config > env > fallback
        $url = config('app.ocr_api_url')
            ?? env('OCR_API_URL')
            ?? 'http://localhost:8000';

        if (empty($url) || $url === 'http://localhost:8000') {
            Log::warning('OCR API URL not properly configured, using fallback', [
                'fallback_url' => $url,
                'config_value' => config('app.ocr_api_url'),
                'env_ocr_api_url' => env('OCR_API_URL')
            ]);
        }

        return rtrim($url, '/');
    }

    public function getSystemInfo(): array
    {
        try {
            Log::info('Fetching OCR system info', [
                'url' => $this->baseUrl . '/app-status',
                'timeout' => $this->timeout
            ]);

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'User-Agent' => 'EcoSteps-Laravel/1.0'
                ])
                ->get($this->baseUrl . '/app-status');

            if ($response->successful()) {
                $data = $response->json();
                Log::info('OCR system info fetched successfully', [
                    'response_size' => strlen($response->body()),
                    'status_code' => $response->status()
                ]);
                return $data;
            }

            $errorMsg = "OCR API returned error status: {$response->status()}";
            Log::error($errorMsg, [
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'url' => $this->baseUrl . '/app-status'
            ]);

            throw new \Exception($errorMsg);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $errorMsg = "Cannot connect to OCR API service";
            Log::error($errorMsg, [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/app-status',
                'timeout' => $this->timeout
            ]);
            throw new \Exception($errorMsg . ": " . $e->getMessage());

        } catch (\Illuminate\Http\Client\RequestException $e) {
            $errorMsg = "OCR API request failed";
            Log::error($errorMsg, [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/app-status'
            ]);
            throw new \Exception($errorMsg . ": " . $e->getMessage());

        } catch (\Exception $e) {
            Log::error('Unexpected error fetching OCR system info', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'url' => $this->baseUrl . '/app-status'
            ]);
            throw $e;
        }
    }

    public function isServiceAvailable(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('OCR service health check failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/health'
            ]);
            return false;
        }
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
