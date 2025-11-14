# Fitur Manual Verification

## Overview
Fitur ini memungkinkan user untuk mengajukan verifikasi manual ketika laporan langkah mereka gagal terverifikasi oleh sistem OCR.

## Implementasi

### 1. Database Migration
File: `database/migrations/2025_11_14_204746_add_manual_verification_to_daily_reports_table.php`

Menambahkan 2 kolom baru:
- `manual_verification_requested` (boolean) - flag apakah user sudah request
- `manual_verification_requested_at` (timestamp) - waktu request dibuat

### 2. Model Update
File: `app/Models/DailyReport.php`

Menambahkan kolom ke `$fillable` dan `$casts`

### 3. Component Method
File: `resources/views/livewire/employee/dashboard.blade.php`

Method `requestManualVerification()`:
- Cek apakah laporan hari ini ada dan status = 3 (ditolak)
- Update status kembali ke PENDING (1)
- Set flag `manual_verification_requested = true`
- Tampilkan notifikasi ke user

### 4. UI Update
File: `resources/views/livewire/employee/dashboard.blade.php`

Alert untuk status ditolak sekarang menampilkan:
- Button "Ajukan Verifikasi Manual" jika belum pernah request
- Text "✓ Verifikasi manual telah diajukan" jika sudah request

## Flow
```
Status Ditolak (3)
    ↓
User klik "Ajukan Verifikasi Manual"
    ↓
Status → PENDING (1)
Flag manual_verification_requested → true
    ↓
Admin dapat melihat ini adalah request manual
    ↓
Admin verifikasi manual
```

## Cara Menjalankan
1. Jalankan migration: `php artisan migrate`
2. Fitur siap digunakan

## Untuk Admin
Admin dapat filter laporan dengan `manual_verification_requested = true` untuk melihat request verifikasi manual.

Query contoh:
```php
DailyReport::where('manual_verification_requested', true)
    ->where('status_verifikasi', StatusVerifikasi::PENDING)
    ->get();
```
