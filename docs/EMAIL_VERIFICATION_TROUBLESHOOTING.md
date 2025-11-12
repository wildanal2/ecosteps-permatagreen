# Email Verification Troubleshooting

## Masalah: Link Verifikasi Tidak Berfungsi

### Gejala:
- Klik link verifikasi di email
- Setelah login, masih diminta verifikasi email
- `email_verified_at` masih NULL di database

### Penyebab:
1. **URL Encoding Issue**: Link di email menggunakan `&amp;` (HTML entity) bukan `&`
2. **Custom Notification**: Perlu override default VerifyEmail notification

### Solusi yang Sudah Diterapkan:

#### 1. Custom VerifyEmail Notification
File: `app/Notifications/CustomVerifyEmail.php`

```php
- Extends BaseVerifyEmail dari Laravel
- Custom buildMailMessage() dengan teks Bahasa Indonesia
- Custom verificationUrl() untuk generate URL yang benar
- Expire time: 60 menit
```

#### 2. Override di User Model
File: `app/Models/User.php`

```php
public function sendEmailVerificationNotification()
{
    $this->notify(new CustomVerifyEmail);
}
```

### Testing Manual:

#### Test 1: Cek URL di Email
```
URL yang benar:
http://localhost:8003/email/verify/2/27de11d6a13e4a9ba350b8adff1b913110a2d54e?expires=1762909049&signature=8c6965909a988532427cfcfdc6ee6074edecca201945c5bb8a7f4aea0034a1e2

Bukan:
http://localhost:8003/email/verify/2/27de11d6a13e4a9ba350b8adff1b913110a2d54e?expires=1762909049&amp;signature=...
```

#### Test 2: Cek Database Setelah Klik Link
```sql
SELECT id, email, email_verified_at FROM users WHERE id = 2;
```

Seharusnya `email_verified_at` terisi timestamp setelah klik link.

#### Test 3: Cek Route
```bash
php artisan route:list --path=email/verify
```

Harus ada:
- `GET email/verify/{id}/{hash}` → verification.verify

#### Test 4: Manual Verification (untuk testing)
```bash
php artisan tinker
```

```php
$user = App\Models\User::find(2);
$user->markEmailAsVerified();
```

### Flow yang Benar:

1. **User registrasi** → `email_verified_at = NULL`
2. **Email terkirim** dengan link verification
3. **User klik link** → Route: `verification.verify`
4. **Fortify handle** → Set `email_verified_at = now()`
5. **Redirect** → Login page dengan success message
6. **User login** → Cek `hasVerifiedEmail()` → TRUE
7. **Redirect** → Dashboard

### Debugging:

#### Cek apakah link diklik dengan benar:
```bash
# Lihat log Laravel
tail -f storage/logs/laravel.log
```

#### Cek manual di browser:
1. Copy link dari email
2. Paste di notepad
3. Replace semua `&amp;` dengan `&`
4. Paste URL yang sudah diperbaiki ke browser

#### Cek email_verified_at:
```bash
php artisan tinker
```

```php
$user = App\Models\User::where('email', 'test@example.com')->first();
echo $user->email_verified_at; // Harus ada timestamp
echo $user->hasVerifiedEmail(); // Harus return true
```

### Catatan Penting:

1. **Custom Notification** sudah dibuat untuk fix URL encoding
2. **Link expire** dalam 60 menit
3. **Signature** harus valid (jangan edit URL manual)
4. **Middleware verified** di dashboard akan block user yang belum verified

### Jika Masih Bermasalah:

1. Clear cache:
```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

2. Test kirim email baru:
```bash
php artisan tinker
```

```php
$user = App\Models\User::find(2);
$user->sendEmailVerificationNotification();
```

3. Cek email baru dan pastikan URL tidak ada `&amp;`
