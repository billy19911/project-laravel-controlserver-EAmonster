# EA Cloud Controller Laravel 11 (Deploy-Ready)

Folder ini sudah full Laravel 11 dan siap di-upload ke server.

## Isi Fitur

- Backend API untuk EA MT5:
  - `GET /api/v1/get-config?account_id=...`
  - `POST /api/v1/report-status`
- Backend API Dashboard (multi-user isolation):
  - `POST /api/v1/auth/register`
  - `POST /api/v1/auth/login`
  - `GET /api/v1/my/accounts`
  - `GET /api/v1/my/config?account_id=...`
  - `POST /api/v1/update-setting`
  - `GET /api/v1/my/status?account_id=...`
- Dashboard siap pakai:
  - `/ea-dashboard.html` (khusus Laravel API ini)
  - login-first UI dengan navbar + sidebar + menu Account/Settings/Statistik/Reports
  - login bisa pakai email atau username
  - form register langsung tersedia di layar login
  - menu Admin Monitor (khusus user admin) untuk cek akun user lain
- Dashboard lama Anda juga sudah dibundel:
  - `/dashboard.html`
  - `/dashboard-v209.html`

## Kenapa Tetap Perlu GUI Dashboard?

- EA bisa jalan otomatis tanpa GUI.
- Tetapi GUI tetap penting untuk:
  - ubah parameter live,
  - monitoring floating/layer,
  - multi-account control by user.

Jadi: **bukan berarti GUI tidak perlu**. GUI tetap dipakai operator/manusia.

## Langkah Jalankan Lokal (Windows/Linux)

1. Masuk folder project ini.
2. Copy env:

```bash
cp .env.example .env
```

3. Isi variabel penting di `.env`:

```env
APP_URL=http://localhost:8000
EA_API_KEY=isi_secret_khusus_ea
```

4. Konfigurasi database MySQL sudah diset ke:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u588311877_ea
DB_USERNAME=u588311877_ea
DB_PASSWORD=@Angelic123
```

Lalu migrate + seed:

```bash
php artisan migrate --seed
```

5. Jalankan server:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

6. Buka:
- `http://localhost:8000/` (redirect ke `ea-dashboard.html`)

## Akun Testing Default

Seeder membuat:
- Email: `test@example.com`
- Username: `testuser`
- Password default factory Laravel: `password`
- Account ID sample: `12345678`

Seeder juga membuat akun admin:
- Email: `admin@example.com`
- Username: `admin`
- Password: `password`

Jika ingin aman di production, ganti user/password ini.

## Header Untuk EA MT5

EA harus kirim header:

```http
X-EA-KEY: <EA_API_KEY>
Content-Type: application/json
```

## Endpoint Format Untuk EA

### 1) Ambil config

`GET /api/v1/get-config?account_id=12345678`

### 2) Lapor status live

`POST /api/v1/report-status`

Body JSON:

```json
{
  "account_id": "12345678",
  "current_layers": 2,
  "current_accumulative_lot": 0.03,
  "global_floating": -12.45,
  "guard_status": "ACTIVE"
}
```

## Deploy ke Hosting (ea.mcstokomebel.com)

1. Upload seluruh isi folder ini ke server.
2. Install dependency:

```bash
composer install --no-dev --optimize-autoloader
```

3. Set `.env` production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://ea.mcstokomebel.com
EA_API_KEY=secret_kuat
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u588311877_ea
DB_USERNAME=u588311877_ea
DB_PASSWORD=@Angelic123
```

4. Generate key (jika belum):

```bash
php artisan key:generate
```

5. Migrate:

```bash
php artisan migrate --force
```

Catatan: migration terbaru juga membuat tabel report histori `ea_status_reports` untuk menu Statistik dan Reports.

6. Optimasi:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Jika Muncul 403 Forbidden / Halaman Kosong

Penyebab paling umum adalah domain belum mengarah ke folder `public` Laravel.

Yang sudah saya siapkan di paket ini:

- Ada file `.htaccess` di root project untuk auto-forward request ke `public/`.
- Jadi untuk shared hosting, domain/subdomain bisa diarahkan ke folder project root tetap bisa jalan.

Checklist cepat:

1. Pastikan file `.htaccess` root ikut ter-upload.
2. Pastikan izin folder/file:
  - folder `755`
  - file `644`
3. Pastikan PHP version hosting minimal 8.2.
4. Jika panel hosting mendukung Document Root, set ke folder `public` (ini cara terbaik).
5. Jika tidak bisa set Document Root, pakai mode rewrite root (default paket ini).
6. Setelah upload, jalankan lagi:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

## Catatan Kecocokan Dengan EA Anda

- Endpoint utama yang diminta (`/v1/get-config` dan `/v1/report-status`) sudah tersedia.
- Mapping parameter martingale/grid sudah sesuai field tabel.
- Jika EA Anda sebelumnya memanggil endpoint Node.js lain yang lebih banyak (SSE/signal/report stream), itu modul terpisah dan perlu fase migrasi tambahan.

## Isolasi Dashboard Per User

- Setiap user login memakai Bearer token masing-masing.
- Endpoint dashboard (`/api/v1/my/*`) selalu query berdasarkan `user_id` token aktif.
- Dropdown akun hanya menampilkan `account_id` milik user tersebut.
- Save setting hanya boleh update akun yang dimiliki user login.
- Artinya setting user A tidak akan tampil/terubah oleh user B.
