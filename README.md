# EcoSteps PermataGreen - Walk for Elephant

Aplikasi tracking langkah karyawan untuk program lingkungan PermataBank dengan sistem verifikasi OCR otomatis.

## 📋 Requirements

- PHP 8.2 atau lebih tinggi
- PostgreSQL 13+
- Composer
- Node.js & NPM
- AWS S3 Compatible Storage (Neo Object Storage)
- FastAPI OCR Service (untuk verifikasi screenshot)

## 🚀 Setup Awal

### 1. Clone Repository

```bash
git clone <repository-url>
cd ecosteps-permatagreen
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 3. Environment Configuration

```bash
# Copy file environment
cp .env.example-dev .env

# Generate application key
php artisan key:generate
```

### 4. Konfigurasi Database

Edit file `.env` dan sesuaikan dengan database PostgreSQL Anda:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ecosteps
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

### 5. Konfigurasi Storage (S3)

Edit konfigurasi AWS S3 di `.env`:

```env
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=idn
AWS_ENDPOINT=https://nos.jkt-1.neo.id
AWS_BUCKET=your_bucket_name
AWS_BUCKET_URL=https://nos.jkt-1.neo.id/your_bucket_name
AWS_URL=https://nos.jkt-1.neo.id
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### 6. Konfigurasi Email (SMTP)

Untuk notifikasi OTP dan verifikasi email:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 7. Konfigurasi OCR API

Setup koneksi ke FastAPI OCR Service:

```env
OCR_API_URL=http://localhost:8000
OCR_API_KEY=your_ocr_api_key
```

### 8. Konfigurasi Aplikasi

```env
APP_NAME="EcoSteps PermataGreen"
APP_ENV=local  # staging atau production
APP_DEBUG=true
APP_URL=http://localhost:8003

# Aktifkan verifikasi email (opsional)
REQUIRE_EMAIL_VERIFICATION=false

# API Key untuk external access
XAPI_KEY=your_api_key
```

### 9. Migrasi Database

```bash
# Jalankan migrasi
php artisan migrate

# Seed data dummy (opsional - 140 user dummy)
php artisan db:seed --class=UserSeeder
```

### 10. Build Assets

```bash
npm run build
```

## 🏃 Menjalankan Aplikasi

### Development Mode

```bash
# Jalankan semua service sekaligus (server, queue, logs, vite)
composer dev
```

Atau jalankan secara terpisah:

```bash
# Terminal 1 - Web Server
php artisan serve

# Terminal 2 - Queue Worker
php artisan queue:listen

# Terminal 3 - Logs
php artisan pail

# Terminal 4 - Vite Dev Server
npm run dev
```

### Production Mode

```bash
# Build assets
npm run build

# Jalankan dengan web server (nginx/apache)
# atau gunakan Laravel Octane untuk performa tinggi
```

## 👥 User Roles

Aplikasi memiliki 2 level user:

1. **Employee (user_level = 1)**: Karyawan yang melaporkan langkah harian
2. **Admin (user_level = 2)**: Admin yang mengelola verifikasi manual

### Default Admin Account

Setelah seeding, buat admin manual:

```bash
php artisan tinker
```

```php
User::create([
    'name' => 'Admin',
    'email' => 'admin@permatagreen.com',
    'password' => bcrypt('password'),
    'user_level' => 2,
    'directorate' => 0,
]);
```

## 📱 Fitur Utama

### Untuk Employee:
- Upload screenshot langkah harian
- Verifikasi otomatis via OCR
- Dashboard dengan statistik personal
- Leaderboard individu dan direktorat
- Riwayat laporan
- Request verifikasi manual jika OCR gagal

### Untuk Admin:
- Dashboard admin
- Verifikasi manual laporan
- Export data ke Excel
- Manajemen user
- Monitoring sistem

## 🔧 Maintenance Commands

```bash
# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Optimize untuk production
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue management
php artisan queue:work
php artisan queue:restart
php artisan queue:failed  # Lihat failed jobs

# Database
php artisan migrate:fresh --seed  # Reset database
php artisan db:seed  # Seed data saja
```

## 📊 Database Structure

### Main Tables:
- `users` - Data user (employee & admin)
- `daily_reports` - Laporan harian langkah
- `user_statistics` - Statistik akumulasi user
- `sessions` - Session management
- `jobs` - Queue jobs
- `cache` - Cache storage

## 🔐 Security

- Two-Factor Authentication (2FA) support
- Email verification
- API Key protection
- CSRF protection
- XSS protection
- SQL injection protection

## 📝 Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_ENV` | Environment (local/staging/production) | local |
| `APP_DEBUG` | Debug mode | true |
| `REQUIRE_EMAIL_VERIFICATION` | Aktifkan verifikasi email | false |
| `OCR_API_URL` | URL FastAPI OCR service | - |
| `OCR_API_KEY` | API key untuk OCR service | - |

## 🐛 Troubleshooting

### Upload gagal
- Cek koneksi S3
- Cek permission bucket
- Cek ukuran file (max 10MB)
- Lihat logs: `storage/logs/laravel.log`

### OCR tidak berjalan
- Pastikan FastAPI service running
- Cek `OCR_API_URL` dan `OCR_API_KEY`
- Cek queue worker berjalan
- Lihat dokumentasi: `docs/OCR_API_TROUBLESHOOTING.md`

### Email tidak terkirim
- Cek konfigurasi SMTP
- Untuk Gmail, gunakan App Password
- Lihat dokumentasi: `docs/EMAIL_VERIFICATION_TROUBLESHOOTING.md`

## 📚 Dokumentasi Tambahan

Lihat folder `docs/` untuk dokumentasi lengkap:
- `TECHNICAL-FLOW.md` - Flow teknis aplikasi
- `ROLE_STRUCTURE.md` - Struktur role dan permission
- `MANUAL_VERIFICATION_SYSTEM.md` - Sistem verifikasi manual
- `UPLOAD_OPTIMIZATION.md` - Optimasi upload
- Dan lainnya...

## 🤝 Contributing

1. Fork repository
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

## 📄 License

MIT License

## 👨‍💻 Support

Untuk bantuan lebih lanjut, hubungi tim development atau buka issue di repository.
