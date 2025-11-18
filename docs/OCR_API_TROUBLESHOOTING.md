# OCR API Troubleshooting Guide

## Masalah Umum dan Solusi

### 1. Error: "env('OCRAPI_URL') tidak bisa selalu ada"

**Penyebab:**
- Environment variable `OCRAPI_URL` tidak terdefinisi
- Nilai environment variable kosong atau null
- Konfigurasi tidak konsisten

**Solusi:**

#### A. Periksa File .env
```bash
# Pastikan salah satu dari variabel ini ada di .env:
OCRAPI_URL=http://localhost:8000
# ATAU
OCR_API_URL=http://localhost:8000
```

#### B. Gunakan Command Test
```bash
php artisan ocr:test-connection
```

#### C. Periksa Log untuk Debug
```bash
tail -f storage/logs/laravel.log
```

### 2. Pendekatan Robust yang Diimplementasi

#### A. Multiple Fallback Strategy
```php
// Priority: config > env > fallback
$url = config('app.ocr_api_url') 
    ?? env('OCRAPI_URL') 
    ?? env('OCR_API_URL') 
    ?? 'http://localhost:8000';
```

#### B. Service Class dengan Error Handling
- `App\Services\OcrApiService` menangani semua komunikasi dengan OCR API
- Logging detail untuk setiap request/response
- Graceful error handling dengan pesan yang jelas

#### C. Comprehensive Logging
```php
Log::info('Fetching OCR system info', [
    'url' => $this->baseUrl . '/app-status',
    'timeout' => $this->timeout
]);
```

### 3. Konfigurasi yang Direkomendasikan

#### File .env
```env
# OCR API Config (pilih salah satu)
OCRAPI_URL=http://localhost:8000
# ATAU
OCR_API_URL=http://localhost:8000

# Optional: API Key jika diperlukan
OCR_API_KEY=your_api_key_here
```

#### File config/app.php
```php
'ocr_api_url' => env('OCR_API_URL', 'http://localhost:8000'),
'ocr_api_key' => env('OCR_API_KEY', ''),
```

### 4. Testing dan Debugging

#### Command untuk Test Koneksi
```bash
php artisan ocr:test-connection
```

Output yang diharapkan:
```
Testing OCR API Connection...

Configuration Check:
Base URL: http://localhost:8000
Config OCR_API_URL: http://localhost:8000
Env OCRAPI_URL: http://localhost:8000
Env OCR_API_URL: http://localhost:8000

Health Check:
✓ OCR service is available

System Info Test:
✓ System info fetched successfully
Service: OCR Processing Service
Version: 1.0.0
Uptime: 2 hours, 15 minutes
```

#### Periksa Log Error
```bash
# Lihat log real-time
tail -f storage/logs/laravel.log | grep -i ocr

# Lihat log hari ini
grep -i ocr storage/logs/laravel-$(date +%Y-%m-%d).log
```

### 5. Error Handling yang Diimplementasi

#### Connection Errors
- Timeout handling (10 detik default)
- Connection refused detection
- DNS resolution errors

#### HTTP Errors
- 404: Service endpoint tidak ditemukan
- 500: Internal server error di OCR service
- 503: Service unavailable

#### Response Errors
- Invalid JSON response
- Missing required fields
- Empty response body

### 6. Monitoring dan Alerting

#### Health Check Endpoint
```php
$ocrService = new OcrApiService();
if (!$ocrService->isServiceAvailable()) {
    // Service down - send alert
}
```

#### Log Monitoring
Monitor log untuk pattern:
- "OCR API returned error status"
- "Cannot connect to OCR API service"
- "OCR service health check failed"

### 7. Production Checklist

- [ ] Environment variables properly set
- [ ] OCR service running and accessible
- [ ] Network connectivity tested
- [ ] Log rotation configured
- [ ] Monitoring alerts set up
- [ ] Fallback URLs configured
- [ ] API keys secured

### 8. Common Commands

```bash
# Test OCR connection
php artisan ocr:test-connection

# Clear config cache
php artisan config:clear

# View current config
php artisan tinker
>>> config('app.ocr_api_url')

# Check environment variables
php artisan tinker
>>> env('OCRAPI_URL')
>>> env('OCR_API_URL')
```