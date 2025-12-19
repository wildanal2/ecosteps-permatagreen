# Event End Date Feature

## Overview
Fitur ini memungkinkan aplikasi untuk secara otomatis menonaktifkan upload dan membatasi data berdasarkan tanggal berakhirnya event yang dikonfigurasi di environment variable.

## Konfigurasi

### 1. Environment Variable
Tambahkan di file `.env`:

```env
DATE_END_EVENT="2025-12-21"
```

Format: `YYYY-MM-DD`

### 2. Config File
Konfigurasi sudah ditambahkan di `config/app.php`:

```php
'date_end_event' => env('DATE_END_EVENT', '2025-12-21'),
```

## Fitur yang Terpengaruh

### 1. Dashboard User (`dashboard.blade.php`)

#### Notifikasi Event Berakhir
- Menampilkan alert merah di bagian atas dashboard
- Pesan: "Event telah berakhir pada [tanggal]. Upload dan laporan tidak dapat dilakukan lagi."

#### Card Progres Harian
- Card menjadi opacity 60% (semi-transparan)
- Tombol upload diganti dengan tombol disabled "Event Telah Berakhir"
- Tombol "Ajukan Banding" disembunyikan

#### Rekap Mingguan (Chart)
- Chart menampilkan data sampai tanggal event berakhir
- Data dibatasi 7 hari terakhir dari tanggal event berakhir

### 2. Halaman Riwayat (`riwayat.blade.php`)

#### Notifikasi Event Berakhir
- Menampilkan alert merah di atas list riwayat
- Pesan: "Event telah berakhir pada [tanggal]. Data riwayat dibatasi sampai tanggal tersebut."

#### Navigasi Minggu
- Tombol "Minggu Depan" disabled jika melewati event end date
- Data minggu dibatasi sampai tanggal event berakhir

#### Upload & Update
- Tombol "Kirim Laporan" disembunyikan untuk tanggal kosong
- Tombol "Perbarui" disembunyikan untuk laporan existing
- Tombol "Ajukan Banding" disembunyikan

#### Chart Trend Aktivitas
- Chart menampilkan data sampai minggu yang berisi event end date
- Tidak bisa navigasi ke minggu setelah event berakhir

## Logika Implementasi

### Check Event Ended
```php
$eventEndDate = \Carbon\Carbon::parse(config('app.date_end_event', '2025-12-21'));
$isEventEnded = now()->isAfter($eventEndDate);
```

### Batasi Data Chart
```php
$endDate = $isEventEnded ? $eventEndDate : now();
$chartDates = collect();
for ($i = 6; $i >= 0; $i--) {
    $chartDates->push($endDate->copy()->subDays($i));
}
```

### Batasi Navigasi Minggu
```php
$currentDate = $isEventEnded ? $eventEndDate : now();
$startOfWeek = $currentDate->copy()->subWeeks($this->weekOffset)->startOfWeek();
$endOfWeek = $currentDate->copy()->subWeeks($this->weekOffset)->endOfWeek();
```

### Conditional Upload Button
```blade
@if(!$isEventEnded)
    <flux:modal.trigger name="upload-harian">
        <button>Kirim Laporan</button>
    </flux:modal.trigger>
@else
    <button disabled>Event Telah Berakhir</button>
@endif
```

## Testing

### Test Case 1: Sebelum Event Berakhir
1. Set `DATE_END_EVENT` ke tanggal masa depan
2. Verifikasi semua fitur upload aktif
3. Verifikasi tidak ada notifikasi event berakhir

### Test Case 2: Setelah Event Berakhir
1. Set `DATE_END_EVENT` ke tanggal masa lalu
2. Verifikasi notifikasi event berakhir muncul
3. Verifikasi tombol upload disabled/hidden
4. Verifikasi chart dibatasi sampai event end date
5. Verifikasi navigasi minggu dibatasi

### Test Case 3: Pada Hari Event Berakhir
1. Set `DATE_END_EVENT` ke hari ini
2. Verifikasi masih bisa upload (karena menggunakan `isAfter`)
3. Besok verifikasi sudah tidak bisa upload

## Catatan Penting

1. **Timezone**: Aplikasi menggunakan timezone `Asia/Jakarta` (lihat `config/app.php`)
2. **Comparison**: Menggunakan `now()->isAfter($eventEndDate)` untuk check
3. **Data Preservation**: Data lama tetap tersimpan, hanya dibatasi tampilan dan input baru
4. **Admin Access**: Fitur ini hanya berlaku untuk user level employee, admin tetap bisa akses semua data

## Troubleshooting

### Event tidak berakhir padahal sudah lewat tanggal
- Cek timezone server: `php artisan tinker` → `now()`
- Cek format DATE_END_EVENT di .env (harus YYYY-MM-DD)
- Clear config cache: `php artisan config:clear`

### Chart tidak update setelah event berakhir
- Refresh halaman (Ctrl+F5)
- Clear browser cache
- Cek console browser untuk error JavaScript

### Upload masih bisa dilakukan setelah event berakhir
- Pastikan sudah melewati tanggal (bukan pada hari yang sama)
- Clear config cache: `php artisan config:clear`
- Restart queue worker: `php artisan queue:restart`

## Future Improvements

1. Tambahkan countdown timer sebelum event berakhir
2. Kirim email notifikasi ke semua user sebelum event berakhir
3. Tambahkan grace period (misal 1 hari setelah event berakhir masih bisa upload)
4. Buat halaman khusus "Event Summary" setelah event berakhir
