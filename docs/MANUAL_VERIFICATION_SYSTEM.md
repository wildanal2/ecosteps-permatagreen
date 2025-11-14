# Sistem Verifikasi Manual

## Overview
Sistem verifikasi manual memungkinkan admin untuk memvalidasi laporan aktivitas yang diajukan oleh user dengan interface yang profesional dan terstruktur.

## Database Structure

### Tabel: `manual_verification_logs`
```sql
- id (bigint, primary key)
- report_id (foreign key -> daily_reports)
- image_url (text) - URL bukti screenshot
- valid_step (integer) - Jumlah langkah yang valid
- app_name (string, nullable) - Nama aplikasi tracking
- status_verifikasi (tinyint) - Status verifikasi (2=approved, 3=rejected)
- validated_by (foreign key -> users) - Admin yang melakukan validasi
- created_at, updated_at (timestamps)
```

## Fitur Halaman Verifikasi Bukti

### 1. **Header Profesional**
- Judul: "Verifikasi Bukti Aktivitas"
- Deskripsi: Menjelaskan tujuan halaman

### 2. **Tabel Data**
Menampilkan laporan yang memiliki `manual_verification_requested = true`

Kolom:
- Nama Peserta
- Direktorat
- Tanggal Laporan
- Jumlah Langkah
- CO₂e Dihindari
- Bukti Foto (link preview)
- Waktu Request
- Aksi (Button "Verifikasi")

### 3. **Filter & Search**
- Search by nama peserta
- Filter by status (Semua/Menunggu/Diverifikasi/Ditolak)

### 4. **Modal Verifikasi (2 Kolom)**

#### Kolom Kiri:
- **Bukti Screenshot** (full image display)
- **Hasil OCR** (jika ada)

#### Kolom Kanan:
- **Alert** - Warning untuk manual verification request
- **Info Card** - Data peserta (nama, direktorat, tanggal, langkah saat ini)
- **Form Input:**
  - **Jumlah Langkah Valid*** (required, number input)
  - **Nama Aplikasi** (optional, text input)
- **Action Buttons:**
  - Tolak (red, danger variant)
  - Verifikasi (blue, primary variant)

## Flow Verifikasi

```
User Request Manual Verification
    ↓
Laporan masuk ke halaman Verifikasi Bukti
    ↓
Admin klik "Verifikasi"
    ↓
Modal terbuka dengan 2 kolom (Image + Form)
    ↓
Admin input:
  - Jumlah langkah valid (required)
  - Nama aplikasi (optional)
    ↓
Admin klik "Verifikasi" atau "Tolak"
    ↓
System:
  1. Create log di manual_verification_logs
  2. Update daily_report:
     - langkah = valid_step (jika approved)
     - status_verifikasi = 2 atau 3
     - verified_by = ADMIN
     - verified_at = now()
     - verified_id = admin_id
    ↓
Flash message & modal close
```

## Model Relationships

### ManualVerificationLog
```php
- belongsTo(DailyReport, 'report_id')
- belongsTo(User, 'validated_by')
```

### DailyReport
```php
- hasMany(ManualVerificationLog, 'report_id')
```

## Validation Rules

### Approve/Reject Report
```php
'validStep' => 'required|integer|min:0'
'appName' => 'nullable|string|max:255'
```

## Migration Command
```bash
php artisan migrate
```

## Key Features
✅ Grid 2 kolom (Image + Form)
✅ Form validation
✅ Manual verification logging
✅ Update daily report dengan data valid
✅ Filter & search functionality
✅ Responsive design
✅ Dark mode support
✅ Professional UI/UX

## Admin Benefits
- Lihat bukti screenshot dengan jelas
- Input jumlah langkah yang benar-benar valid
- Catat nama aplikasi untuk referensi
- Semua verifikasi tercatat di log
- Audit trail lengkap
