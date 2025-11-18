# Struktur Role & Halaman

## User Level 1 - Karyawan
**Prefix Route:** `/employee`

### Menu & Halaman:
1. **Dashboard** - `/employee/dashboard`
   - Route: `employee.dashboard`
   - File: `resources/views/livewire/employee/dashboard.blade.php`

2. **Riwayat** - `/employee/riwayat`
   - Route: `employee.riwayat`
   - File: `resources/views/livewire/employee/riwayat.blade.php`

---

## User Level 2 - Admin
**Prefix Route:** `/admin`

### Menu & Halaman:
1. **Dashboard** - `/admin/dashboard`
   - Route: `admin.dashboard`
   - File: `resources/views/livewire/admin/dashboard.blade.php`

2. **Data Peserta** - `/admin/data-peserta`
   - Route: `admin.data-peserta`
   - File: `resources/views/livewire/admin/data-peserta.blade.php`

3. **Verifikasi Bukti** - `/admin/verifikasi-bukti`
   - Route: `admin.verifikasi-bukti`
   - File: `resources/views/livewire/admin/verifikasi-bukti.blade.php`

4. **Leaderboard** - `/admin/leaderboard`
   - Route: `admin.leaderboard`
   - File: `resources/views/livewire/admin/leaderboard.blade.php`

---

## Teknologi
- Semua halaman menggunakan **Livewire Volt**
- Layout: `components.layouts.app`
- Middleware: `check.user.level` untuk proteksi akses
- Navigation: Otomatis berdasarkan `user_level`

## Login Redirect
- User Level 1 → `/employee/dashboard`
- User Level 2 → `/admin/dashboard`
