# 🌱 Melangkah Penghijauan — Dokumentasi Teknis Aplikasi

## 📘 Deskripsi Singkat

**Melangkah Penghijauan** adalah aplikasi CSR (Corporate Social Responsibility) dari **PermataBank** yang bertujuan untuk mendukung pengurangan emisi karbon melalui aktivitas berjalan kaki karyawan.  
Setiap **10.000 langkah** karyawan akan dikonversi menjadi **1 pohon**, serta dihitung estimasi pengurangan karbon (**CO₂e**) berdasarkan langkah yang diambil.

Aplikasi ini digunakan oleh karyawan untuk:
- Mencatat langkah harian mereka
- Mengunggah bukti aktivitas (screenshot dari aplikasi seperti *Samsung Health*, *Google Fit*, *Apple Health*, dll)
- Melihat rekap mingguan, tren langkah, dan total pohon yang dikonversi

---

## 🧩 Arsitektur Sistem

Aplikasi menggunakan **Laravel 12 + Livewire** sebagai backend utama dan antarmuka, serta **FastAPI (Python)** sebagai service OCR (Optical Character Recognition) untuk membaca teks langkah dari gambar screenshot.

### 🔧 Komponen Utama
| Komponen | Teknologi | Fungsi |
|-----------|-------------|--------|
| Frontend & Backend | Laravel 12 + Livewire | Halaman dashboard, riwayat, dan pengelolaan data user |
| OCR Service | FastAPI (Python) | Menerima screenshot dari Laravel, melakukan OCR, dan mengembalikan hasil |
| Storage | AWS S3 | Menyimpan screenshot bukti langkah |
| Database | PostgreSQL | Menyimpan data user, laporan, statistik, dan log proses OCR |
| Queue | RabbitMQ / Celery (di sisi FastAPI) | Mengantrikan proses OCR |
| Webhook | HTTP POST (FastAPI → Laravel) | Mengirim hasil OCR kembali ke Laravel |

---

## 🔄 Alur Kerja Sistem

### 1️⃣ **Registrasi & Login**
- Pengguna mendaftar menggunakan **Email Corporate PermataBank**.
- Data disimpan di tabel `users`:
  - Nama Lengkap
  - Email Corporate
  - Password (hashed)
  - Cabang, Direktorat
  - Kendaraan ke kantor
  - Mode kerja saat ini (WFH / WFO)

---

### 2️⃣ **Dashboard**
Dashboard menampilkan rekap aktivitas pengguna:

| Komponen | Data Sumber | Keterangan |
|-----------|--------------|------------|
| CO₂e yang dihindari | `user_statistics.total_co2e_kg` | Akumulasi pengurangan karbon |
| Akumulasi Langkah | `user_statistics.total_langkah` | Total langkah yang dikonversi |
| Streak | `user_statistics.current_streak` | Jumlah hari berturut-turut aktif |
| Pohon | `user_statistics.total_pohon` | Konversi langkah ke pohon |
| Walltrack | `daily_reports` | Status langkah hari ini, ex: `1.000 / 10.000 langkah` |

---

### 3️⃣ **Kirim Laporan Aktivitas Harian**

1. User memilih file screenshot (`JPEG` / `PNG`, max 5MB).
2. Laravel mengunggah file ke **S3 Bucket**.
3. Setelah sukses, Laravel:
   - Menyimpan metadata ke tabel `daily_reports` (status = `pending`).
   - Mengirim trigger ke **FastAPI OCR Service** melalui HTTP POST:
     ```json
     {
       "report_id": 125,
       "user_id": 3,
       "s3_path": "s3://melangkah/uploads/2025-11-05_ocr.png"
     }
     ```
4. FastAPI menempatkan data ke dalam **queue OCR** dan mulai proses ekstraksi langkah.
5. Setelah selesai, FastAPI mengirim hasil OCR kembali ke Laravel via **webhook callback**:
   ```json
   {
     "report_id": 125,
     "ocr_text": "Total steps: 10,245",
     "status": "done"
   }


# ERD

```
┌────────────────────┐
│      users         │
├────────────────────┤
│ id (PK)            │
│ name               │
│ email              │
│ password_hash      │
│ cabang             │
│ direktorat         │
│ kendaraan_kantor   │
│ mode_kerja         │
│ user_level         │
│ timestamps         │
└─────────┬──────────┘
          │ 1
          │
          │ N
┌────────────────────┐
│ daily_reports      │
├────────────────────┤
│ id (PK)            │
│ user_id (FK)       │
│ tanggal_laporan    │
│ langkah            │
│ co2e_reduction_kg  │
│ poin               │
│ pohon              │
│ status_verifikasi  │
│ bukti_screenshot   │
│ ocr_result_raw     │
│ verified_at        │
│ timestamps         │
└─────────┬──────────┘
          │ 1
          │
          │ N
┌────────────────────┐
│ ocr_process_logs   │
├────────────────────┤
│ id (PK)            │
│ report_id (FK)     │
│ request_id         │
│ fastapi_status     │
│ ocr_text_result    │
│ received_at        │
│ timestamps         │
└────────────────────┘

┌────────────────────┐
│ user_statistics    │
├────────────────────┤
│ id (PK)            │
│ user_id (FK)       │
│ total_langkah      │
│ total_co2e_kg      │
│ total_pohon        │
│ current_streak     │
│ last_update        │
│ timestamps         │
└────────────────────┘

┌────────────────────┐
│ weekly_summaries   │
├────────────────────┤
│ id (PK)            │
│ user_id (FK)       │
│ week_start_date    │
│ week_end_date      │
│ total_langkah      │
│ total_co2e_kg      │
│ total_pohon        │
│ poin_mingguan      │
│ timestamps         │
└────────────────────┘

