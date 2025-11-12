# Email Template Customization

## Perubahan yang Dilakukan:

### 1. **Logo Email**
File: `resources/views/vendor/mail/html/header.blade.php`

**Sebelum:**
```
Logo Laravel (https://laravel.com/img/notification-logo.png)
```

**Sesudah:**
```
Logo Permata Bank ({{ config('app.url') }}/assets/images/permata-logo.png)
```

### 2. **Footer Email**
File: `resources/views/vendor/mail/html/message.blade.php`

**Sebelum:**
```
© 2025 Laravel. All rights reserved.
```

**Sesudah:**
```
© 2025 EcoSteps PermataGreen - Permata Bank. All rights reserved.
```

### 3. **Warna Tema**
File: `resources/views/vendor/mail/html/themes/default.css`

**Perubahan:**
- Link color: `#3869d4` → `#0061FE` (Biru Permata Bank)
- Primary button: `#2d3748` → `#0061FE` (Biru Permata Bank)
- Success button: `#48bb78` → `#10b981` (Hijau EcoSteps)
- Panel border: `#2d3748` → `#10b981` (Hijau EcoSteps)

### 4. **Custom Notification**
File: `app/Notifications/CustomVerifyEmail.php`

**Konten:**
- Subject: "Verifikasi Email Anda - EcoSteps PermataGreen"
- Greeting: "Halo!"
- Salutation: "Salam, Tim EcoSteps PermataGreen"
- Button: "Verifikasi Email" (warna biru Permata Bank)

## Struktur Email Template:

```
resources/views/vendor/mail/
├── html/
│   ├── themes/
│   │   └── default.css          ← Warna & styling
│   ├── header.blade.php         ← Logo
│   ├── footer.blade.php         ← Footer wrapper
│   ├── message.blade.php        ← Footer text
│   ├── button.blade.php         ← Button styling
│   └── layout.blade.php         ← Layout utama
└── text/
    └── ...                      ← Plain text version
```

## Testing Email:

### Test 1: Preview Email (Recommended)
```bash
php artisan tinker
```

```php
$user = App\Models\User::find(1);
$notification = new App\Notifications\CustomVerifyEmail;
$mailMessage = $notification->toMail($user);

// Preview subject
echo $mailMessage->subject;

// Preview content
print_r($mailMessage->toArray());
```

### Test 2: Kirim Email Test
```bash
php artisan tinker
```

```php
$user = App\Models\User::find(1);
$user->sendEmailVerificationNotification();
echo "Email sent to: " . $user->email;
```

### Test 3: Cek Email
1. Buka email yang diterima
2. Verifikasi:
   - ✅ Logo Permata Bank (bukan Laravel)
   - ✅ Button biru (#0061FE)
   - ✅ Footer: "© 2025 EcoSteps PermataGreen - Permata Bank"
   - ✅ Tidak ada "Laravel" di mana pun

## Customization Lanjutan:

### Mengubah Warna Button:
Edit: `resources/views/vendor/mail/html/themes/default.css`

```css
.button-primary {
    background-color: #YOUR_COLOR;
    border-bottom: 8px solid #YOUR_COLOR;
    /* ... */
}
```

### Mengubah Logo:
Edit: `resources/views/vendor/mail/html/header.blade.php`

```blade
<img src="{{ config('app.url') }}/path/to/your/logo.png" 
     class="logo" 
     alt="Your Brand" 
     style="height: 50px; max-height: 50px;">
```

### Mengubah Footer Text:
Edit: `resources/views/vendor/mail/html/message.blade.php`

```blade
<x-mail::footer>
© {{ date('Y') }} Your Company Name. All rights reserved.
</x-mail::footer>
```

### Menambahkan Social Links di Footer:
Edit: `resources/views/vendor/mail/html/footer.blade.php`

```blade
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0">
<tr>
<td class="content-cell" align="center">
{{ Illuminate\Mail\Markdown::parse($slot) }}
<br>
<a href="https://facebook.com/yourpage">Facebook</a> | 
<a href="https://instagram.com/yourpage">Instagram</a>
</td>
</tr>
</table>
</td>
</tr>
```

## Catatan Penting:

1. **Logo harus accessible via URL** - Gunakan `config('app.url')` untuk absolute URL
2. **Clear cache** setelah perubahan: `php artisan view:clear`
3. **Test di berbagai email client** (Gmail, Outlook, Yahoo, dll)
4. **Inline CSS** lebih reliable untuk email (sudah dihandle Laravel)
5. **Avoid complex CSS** - Email client support terbatas

## Troubleshooting:

### Logo tidak muncul di email:
- Pastikan `APP_URL` di `.env` benar
- Pastikan logo file accessible (tidak di-block firewall)
- Test dengan absolute URL: `http://localhost:8003/assets/images/permata-logo.png`

### Warna tidak berubah:
- Clear cache: `php artisan view:clear`
- Restart mail queue jika menggunakan queue
- Cek file CSS: `resources/views/vendor/mail/html/themes/default.css`

### Footer masih "Laravel":
- Clear cache
- Pastikan edit file yang benar: `message.blade.php` bukan `footer.blade.php`
- Cek apakah ada cache di email client (coba email baru)
