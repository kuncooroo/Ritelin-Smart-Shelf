# Pasang di VPS Ubuntu

Targetnya Ubuntu 24.04. Aplikasi ada di `backend/`. Perangkat tetap mengirim HTTP ke `/api/events`. Browser admin memakai WebSocket lewat Laravel Reverb supaya halaman berubah tanpa refresh.

Ganti kata sandi demo, token perangkat, dan token kasir sebelum server dibuka ke jaringan publik. `APP_URL` memakai HTTPS, dan `RAKKU_REQUIRE_TLS=true`.

## Server

Salin repo ke `/var/www/rakku`, lalu dari folder itu:

```text
sudo bash deploy/ubuntu/setup.sh rak.tokoanda.com
```

Skrip ini memasang Nginx, PHP 8.3, MySQL, Node.js 22, Composer, Supervisor, dan Certbot. Nginx membaca `backend/public`. WebSocket path `/app` diteruskan ke Reverb di `127.0.0.1:8080`.

Buat database dan pengguna MySQL, lalu salin lingkungan:

```text
cd /var/www/rakku/backend
cp .env.example .env
php artisan key:generate
```

Isi `.env` production kira-kira seperti ini:

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://rak.tokoanda.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rakku
DB_USERNAME=rakku
DB_PASSWORD=kata-sandi-database

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=rakku
REVERB_APP_KEY=kunci-acak-panjang
REVERB_APP_SECRET=rahasia-acak-panjang
REVERB_HOST=rak.tokoanda.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=8080
REVERB_BROADCAST_HOST=127.0.0.1
REVERB_BROADCAST_PORT=8080
REVERB_BROADCAST_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

RAKKU_REQUIRE_TLS=true
```

`REVERB_HOST` adalah alamat yang dilihat browser. `REVERB_BROADCAST_HOST` adalah alamat yang dipakai PHP di server yang sama. Keduanya harus memakai kunci yang sama. Setelah `.env` diubah, bangun ulang aset karena nilai `VITE_` melekat saat `npm run build`.

Jalankan sekali:

```text
bash deploy/ubuntu/deploy.sh
php artisan migrate
php artisan db:seed --class=MainSeeder
sudo certbot --nginx -d rak.tokoanda.com
```

`MainSeeder` hanya untuk peran dan akun superadmin awal. Jangan menjalankan `DemoSeeder` di server yang sudah berisi data toko. Firmware mengarah ke `https://rak.tokoanda.com`, dengan token perangkat yang sama seperti baris di dashboard.

Agar deploy tanpa kata sandi sudo bisa me-restart layanan, tambahkan di sudoers untuk pengguna deploy:

```text
deploy ALL=(root) NOPASSWD: /usr/bin/supervisorctl restart rakku-reverb, /bin/systemctl reload php8.3-fpm
```

## CI dan CD

`.github/workflows/ci.yml` berjalan pada setiap push dan pull request: tes PHP, tes deteksi firmware, dan build aset.

`.github/workflows/deploy.yml` memperbarui server saat workflow dijalankan manual, atau otomatis pada push ke `main` setelah variabel repository `ENABLE_DEPLOY` bernilai `true`.

Secret yang dibutuhkan:

- `VPS_HOST`
- `VPS_USER`
- `VPS_SSH_KEY`
- `VPS_PATH`, misalnya `/var/www/rakku`

Pengguna SSH itu harus bisa `git pull` di folder tersebut. Server harus sudah punya remote dan kunci yang berhak membaca repo.
