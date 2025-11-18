# Test Email Verification

## URL yang Anda Berikan (SALAH):
```
http://localhost:8003/email/verify/2/27de11d6a13e4a9ba350b8adff1b913110a2d54e?expires=1762910011&amp;signature=2c03d37b240dca018726e2793860dcaa4b01ad0f7bdc5fde55a26b075c54b83a
```

**Masalah:** `&amp;` adalah HTML entity, bukan karakter ampersand biasa.

## URL yang BENAR:
```
http://localhost:8003/email/verify/2/27de11d6a13e4a9ba350b8adff1b913110a2d54e?expires=1762910011&signature=2c03d37b240dca018726e2793860dcaa4b01ad0f7bdc5fde55a26b075c54b83a
```

**Perbaikan:** Ganti `&amp;` dengan `&`

## Cara Test:

### 1. Copy URL yang BENAR di atas
### 2. Paste ke browser
### 3. Cek log Laravel:

```bash
tail -f storage/logs/laravel.log
```

Anda akan melihat log seperti ini:

```
[timestamp] local.INFO: === EMAIL VERIFICATION STARTED ===
[timestamp] local.INFO: Request URL: http://localhost:8003/email/verify/2/...
[timestamp] local.INFO: User ID from URL: 2
[timestamp] local.INFO: Hash from URL: 27de11d6a13e4a9ba350b8adff1b913110a2d54e
[timestamp] local.INFO: Expires: 1762910011
[timestamp] local.INFO: Signature: 2c03d37b240dca018726e2793860dcaa4b01ad0f7bdc5fde55a26b075c54b83a
[timestamp] local.INFO: User found: user@example.com
[timestamp] local.INFO: Current email_verified_at: NULL
[timestamp] local.INFO: Expected hash: 27de11d6a13e4a9ba350b8adff1b913110a2d54e
[timestamp] local.INFO: Provided hash: 27de11d6a13e4a9ba350b8adff1b913110a2d54e
[timestamp] local.INFO: Hash match: YES
[timestamp] local.INFO: Email verified successfully!
[timestamp] local.INFO: New email_verified_at: 2025-01-11 12:34:56
[timestamp] local.INFO: === EMAIL VERIFICATION COMPLETED ===
```

### 4. Cek Database:

```bash
php artisan tinker
```

```php
$user = App\Models\User::find(2);
echo $user->email_verified_at; // Harus ada timestamp
echo $user->hasVerifiedEmail(); // Harus return true (1)
```

### 5. Test Login:

1. Buka http://localhost:8003/login
2. Login dengan user ID 2
3. Harus langsung masuk dashboard (tidak redirect ke verification notice)

## Troubleshooting:

### Jika Log Menunjukkan "Invalid signature":
- Link sudah kedaluwarsa (expires sudah lewat)
- URL di-edit manual
- Solusi: Kirim ulang email verifikasi

### Jika Log Menunjukkan "Hash mismatch":
- Email user berubah setelah link digenerate
- Solusi: Kirim ulang email verifikasi

### Jika Tidak Ada Log Sama Sekali:
- Route tidak terpanggil
- Cek apakah URL benar (tidak ada `&amp;`)
- Cek route: `php artisan route:list --path=email/verify`

## Kirim Ulang Email Verifikasi:

```bash
php artisan tinker
```

```php
$user = App\Models\User::find(2);
$user->sendEmailVerificationNotification();
echo "Email sent!";
```

Cek email baru dan pastikan URL tidak ada `&amp;`.

## Catatan Penting:

1. **Custom Controller** sudah dibuat dengan logging lengkap
2. **Route** sudah override Fortify default
3. **Middleware signed** memastikan signature valid
4. **Throttle 6,1** = maksimal 6 percobaan per menit
5. **Log** akan muncul di `storage/logs/laravel.log`

## Expected Flow:

1. Klik link verifikasi (URL yang BENAR)
2. Controller log semua detail
3. Validasi hash dan signature
4. Mark email as verified
5. Redirect ke login dengan success message
6. Login → Masuk dashboard
