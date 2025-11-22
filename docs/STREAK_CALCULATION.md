# Streak Calculation - Dokumentasi

## Overview
Streak adalah jumlah hari berturut-turut user melaporkan langkah harian mereka.

## Logic Perhitungan

### Kondisi Streak Valid:
1. ✅ `tanggal_laporan` = tanggal yang dilaporkan
2. ✅ `DATE(created_at)` = `tanggal_laporan` (laporan dibuat di hari yang sama)
3. ✅ `status_verifikasi` = DIVERIFIKASI
4. ✅ `langkah` > 0

### Mengapa Perlu Validasi `DATE(created_at) = tanggal_laporan`?

#### ❌ Tanpa Validasi (SALAH):
```
User A:
- Senin: Upload laporan Senin (created_at = Senin) ✓
- Selasa: Upload laporan Selasa (created_at = Selasa) ✓
- Rabu: TIDAK upload
- Kamis: Upload laporan Rabu (created_at = Kamis, tanggal_laporan = Rabu) ✓
- Kamis: Upload laporan Kamis (created_at = Kamis) ✓

Streak = 4 hari ❌ (SALAH - seharusnya putus di Rabu)
```

#### ✅ Dengan Validasi (BENAR):
```
User A:
- Senin: Upload laporan Senin (created_at = Senin) ✓
- Selasa: Upload laporan Selasa (created_at = Selasa) ✓
- Rabu: TIDAK upload
- Kamis: Upload laporan Rabu (created_at = Kamis ≠ Rabu) ✗ (tidak dihitung)
- Kamis: Upload laporan Kamis (created_at = Kamis) ✓

Streak = 1 hari ✓ (BENAR - mulai dari Kamis)
```

## Implementasi

### Query SQL:
```sql
SELECT * FROM daily_reports
WHERE user_id = ?
  AND tanggal_laporan = ?
  AND DATE(created_at) = tanggal_laporan  -- Validasi penting!
  AND status_verifikasi = 2
  AND langkah > 0
```

### Code:
```php
$report = DailyReport::where('user_id', $userId)
    ->where('tanggal_laporan', $currentDate->toDateString())
    ->whereRaw('DATE(created_at) = tanggal_laporan')  // Validasi penting!
    ->where('status_verifikasi', StatusVerifikasi::DIVERIFIKASI)
    ->where('langkah', '>', 0)
    ->first();
```

## Algoritma Perhitungan

1. Mulai dari hari ini
2. Cek apakah ada laporan yang valid (4 kondisi di atas)
3. Jika ada, streak++, mundur 1 hari
4. Jika tidak ada, stop
5. Return streak

## Contoh Kasus

### Kasus 1: Streak Sempurna
```
Senin   : Upload Senin (created Senin)   → Streak = 7
Selasa  : Upload Selasa (created Selasa) → Streak = 6
Rabu    : Upload Rabu (created Rabu)     → Streak = 5
Kamis   : Upload Kamis (created Kamis)   → Streak = 4
Jumat   : Upload Jumat (created Jumat)   → Streak = 3
Sabtu   : Upload Sabtu (created Sabtu)   → Streak = 2
Minggu  : Upload Minggu (created Minggu) → Streak = 1

Total Streak = 7 hari
```

### Kasus 2: Streak Putus
```
Senin   : Upload Senin (created Senin)   → Streak = 3
Selasa  : Upload Selasa (created Selasa) → Streak = 2
Rabu    : TIDAK UPLOAD                   → PUTUS
Kamis   : Upload Kamis (created Kamis)   → Streak = 1

Total Streak = 1 hari (mulai dari Kamis)
```

### Kasus 3: Upload Terlambat (Tidak Dihitung)
```
Senin   : Upload Senin (created Senin)   → Streak = 2
Selasa  : Upload Selasa (created Selasa) → Streak = 1
Rabu    : TIDAK UPLOAD                   → PUTUS
Kamis   : Upload Rabu (created Kamis)    → TIDAK DIHITUNG (terlambat)
Kamis   : Upload Kamis (created Kamis)   → Streak = 1

Total Streak = 1 hari (mulai dari Kamis)
```

## Catatan Penting

1. **Timezone**: Pastikan timezone server dan database sama
2. **Update Streak**: Streak dihitung ulang setiap kali ada perubahan status verifikasi
3. **Backfill**: User tidak bisa "mengisi" hari yang terlewat untuk menambah streak
4. **Real-time**: Streak harus dilaporkan di hari yang sama, tidak bisa backdate

## Testing

### Test Case 1: Normal Streak
```php
// User upload setiap hari selama 5 hari
// Expected: streak = 5
```

### Test Case 2: Streak Putus
```php
// User upload 3 hari, skip 1 hari, upload lagi
// Expected: streak = 1 (hari terakhir saja)
```

### Test Case 3: Upload Terlambat
```php
// User upload hari ini untuk kemarin
// Expected: tidak menambah streak
```

## Benefits

- ✅ Mendorong konsistensi harian
- ✅ Mencegah cheating dengan backfill
- ✅ Fair untuk semua user
- ✅ Gamification yang sehat
