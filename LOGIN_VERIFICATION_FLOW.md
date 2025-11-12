# Login dengan Email Verification Flow

## Perubahan yang Dilakukan

### 1. Login Page - Livewire Volt Component
- ✅ Mengubah dari form tradisional ke Livewire Volt
- ✅ Validasi menggunakan `#[Rule]` attribute
- ✅ Rate limiting (5 percobaan per menit)
- ✅ Error messages dalam Bahasa Indonesia
- ✅ Cek email verification sebelum login

### 2. Email Verification Check
Setelah user berhasil login, sistem akan:
1. Cek apakah `email_verified_at` sudah terisi
2. Jika **belum verified**:
   - Logout user
   - Simpan email ke session
   - Redirect ke `/email/verify` (notice page)
3. Jika **sudah verified**:
   - Regenerate session
   - Redirect ke dashboard

### 3. Verification Notice Page
- Menampilkan pesan berbeda untuk:
  - User baru registrasi (`registered_email`)
  - User login tapi belum verified (`unverified_email`)
- Tombol "Kirim Ulang Email Verifikasi"
- Link kembali ke login

## Flow Lengkap

### Scenario 1: Registrasi Baru
```
1. User isi form registrasi → Submit
2. Akun dibuat (email_verified_at = null)
3. Email verifikasi dikirim
4. Redirect ke /email/verify
5. User cek email → Klik link
6. Email terverifikasi (email_verified_at = now())
7. User bisa login
```

### Scenario 2: Login Belum Verified
```
1. User isi form login → Submit
2. Kredensial valid
3. Sistem cek email_verified_at
4. Jika null → Logout + Redirect ke /email/verify
5. User cek email → Klik link
6. Email terverifikasi
7. User login lagi → Berhasil masuk dashboard
```

### Scenario 3: Login Sudah Verified
```
1. User isi form login → Submit
2. Kredensial valid
3. Email sudah verified
4. Redirect ke dashboard ✅
```

## Fitur Keamanan

1. **Rate Limiting**: Maksimal 5 percobaan login per menit
2. **Session Regeneration**: Mencegah session fixation
3. **Email Verification**: Memastikan email valid
4. **Password Hashing**: Menggunakan bcrypt
5. **CSRF Protection**: Livewire otomatis handle

## Testing

### Test Login Belum Verified:
```bash
# 1. Registrasi user baru (jangan klik link verifikasi)
# 2. Coba login dengan akun tersebut
# 3. Harus redirect ke /email/verify
# 4. Klik "Kirim Ulang Email Verifikasi"
# 5. Cek email dan klik link
# 6. Login lagi → Berhasil masuk
```

### Test Login Sudah Verified:
```bash
# 1. Login dengan akun yang sudah verified
# 2. Harus langsung masuk dashboard
```

## Routes

- `GET /login` - Form login (Volt)
- `POST /login` - Disabled (override Fortify)
- `GET /register` - Form registrasi (Volt)
- `POST /register` - Disabled (override Fortify)
- `GET /email/verify` - Notice verifikasi email
- `GET /email/verify/{id}/{hash}` - Link verifikasi (Fortify)
