# Email Verification Setup

## Flow Registrasi dengan Email Verification

1. **User mengisi form registrasi** → Submit
2. **Akun dibuat** dengan `email_verified_at = null` (belum terverifikasi)
3. **Email verifikasi dikirim otomatis** ke email user
4. **User redirect ke halaman notice** (`/email/verify`)
5. **User cek email** dan klik link verifikasi
6. **Email terverifikasi** → `email_verified_at` diisi timestamp
7. **User bisa login** dan akses dashboard

## Konfigurasi Email (.env)

Pastikan konfigurasi email sudah diset di `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

## Migration yang Perlu Dijalankan

```bash
php artisan migrate
```

Migration akan:
- Rename kolom dari Bahasa Indonesia ke English
- `cabang` → `branch`
- `direktorat` → `directorate`
- `kendaraan_kantor` → `transport`
- `jarak_rumah` → `distance`
- `mode_kerja` → `work_mode`

## Routes

- `GET /register` - Form registrasi
- `GET /email/verify` - Notice email verifikasi
- `GET /email/verify/{id}/{hash}` - Link verifikasi (Fortify default)

## Testing

1. Registrasi user baru
2. Cek email untuk link verifikasi
3. Klik link verifikasi
4. Login dengan akun yang sudah terverifikasi

## Fitur Tambahan

- **Resend email verification** - User bisa kirim ulang email jika belum terima
- **Middleware verified** - Otomatis protect routes yang butuh email terverifikasi
- **Custom messages** - Pesan error dalam Bahasa Indonesia
