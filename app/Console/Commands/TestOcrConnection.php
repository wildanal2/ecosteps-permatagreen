<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OcrApiService;

class TestOcrConnection extends Command
{
    protected $signature = 'ocr:test-connection';
    protected $description = 'Test OCR API connection and configuration';

    public function handle()
    {
        $this->info('Testing OCR API Connection...');
        $this->newLine();

        $ocrService = new OcrApiService();

        // Test configuration
        $this->info('Configuration Check:');
        $this->line('Base URL: ' . $ocrService->getBaseUrl());
        $this->line('Config OCR_API_URL: ' . (config('app.ocr_api_url') ?? 'NOT SET'));
        $this->line('Env OCR_API_URL: ' . (env('OCR_API_URL') ?? 'NOT SET'));
        $this->line('Env OCR_API_URL: ' . (env('OCR_API_URL') ?? 'NOT SET'));
        $this->newLine();

        // Test health check
        $this->info('Health Check:');
        if ($ocrService->isServiceAvailable()) {
            $this->info('✓ OCR service is available');
        } else {
            $this->error('✗ OCR service is not available');
        }
        $this->newLine();

        // Test system info
        $this->info('System Info Test:');
        try {
            $systemInfo = $ocrService->getSystemInfo();
            $this->info('✓ System info fetched successfully');
            $this->line('Service: ' . ($systemInfo['service'] ?? 'Unknown'));
            $this->line('Version: ' . ($systemInfo['version'] ?? 'Unknown'));
            $this->line('Uptime: ' . ($systemInfo['uptime'] ?? 'Unknown'));
        } catch (\Exception $e) {
            $this->error('✗ Failed to fetch system info: ' . $e->getMessage());
        }

        return 0;
    }
}
