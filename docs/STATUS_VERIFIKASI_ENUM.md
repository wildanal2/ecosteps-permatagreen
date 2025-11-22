# StatusVerifikasi Enum - Dokumentasi

## Overview
Enum `StatusVerifikasi` digunakan untuk mengelola status verifikasi laporan secara terpusat.

## Enum Values
```php
StatusVerifikasi::PENDING = 1;      // Proses Verifikasi
StatusVerifikasi::DIVERIFIKASI = 2; // Diverifikasi
StatusVerifikasi::DITOLAK = 3;      // Tidak Valid
```

## Methods

### label(): string
Mengembalikan label text untuk status verifikasi.
```php
$report->status_verifikasi->label(); // "Proses Verifikasi", "Diverifikasi", atau "Tidak Valid"
```

### badgeClass(): string
Mengembalikan CSS class untuk badge background.
```php
$report->status_verifikasi->badgeClass(); // "bg-amber-100 dark:bg-amber-900/40", dll
```

### textColor(): string
Mengembalikan CSS class untuk text color.
```php
$report->status_verifikasi->textColor(); // "text-amber-500", "text-emerald-500", atau "text-red-500"
```

### canUpdate(): bool
Mengecek apakah laporan dapat diperbarui (hanya jika status bukan DIVERIFIKASI).
```php
$report->status_verifikasi->canUpdate(); // true jika PENDING atau DITOLAK, false jika DIVERIFIKASI
```

## Usage di Blade

### ❌ JANGAN (Hardcoded)
```blade
@if($report->status_verifikasi->value === 2)
    <span class="bg-emerald-100 text-emerald-500">Diverifikasi</span>
@endif
```

### ✅ GUNAKAN (Enum Methods)
```blade
@if($report->status_verifikasi === \App\Enums\StatusVerifikasi::DIVERIFIKASI)
    <span class="{{ $report->status_verifikasi->badgeClass() }} {{ $report->status_verifikasi->textColor() }}">
        {{ $report->status_verifikasi->label() }}
    </span>
@endif
```

### Contoh Penggunaan canUpdate()
```blade
@if($report->status_verifikasi->canUpdate())
    <button>Perbarui Laporan</button>
@endif
```

## Benefits
1. **Terpusat**: Semua logic status ada di satu tempat
2. **Konsisten**: Styling dan label selalu sama di seluruh aplikasi
3. **Maintainable**: Mudah diubah tanpa mencari di banyak file
4. **Type Safe**: Menggunakan enum comparison, bukan magic numbers
