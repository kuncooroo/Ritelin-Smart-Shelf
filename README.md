# Rakku

Rakku adalah rak timbang untuk toko. Sensor di bawah rak membaca perubahan berat. Dashboard menampilkan perubahan itu sebagai **indikasi aktivitas rak**, bukan bukti penjualan, pencurian, atau transaksi.

Satu perangkat memegang satu jenis produk. Perangkat mengirim data lewat HTTP. Browser admin menerima pembaruan langsung lewat WebSocket, tanpa refresh manual.

Yang bisa dijalankan hanya dari repo ini adalah **dashboard dan API**. Papan ESP32 bersifat terpisah dan dijelaskan di bagian akhir.

## Yang perlu dipasang dulu

Pasang ini di komputer, lalu pastikan perintahnya dikenali di terminal:

| Perangkat | Versi | Untuk memeriksa |
| --- | --- | --- |
| Git | terbaru | `git --version` |
| PHP | 8.3 atau lebih baru | `php -v` |
| Composer | terbaru | `composer -V` |
| Node.js | 22 | `node -v` |

PHP perlu ekstensi yang biasa dipakai Laravel, termasuk `mbstring`, `openssl`, `pdo_sqlite`, dan `sqlite`.

## Clone sampai dashboard terbuka

Perintah di bawah memakai PowerShell di Windows. Di macOS atau Linux, ganti `copy` dengan `cp`, dan `cd backend` tetap sama.

1. Salin repo ke komputer.

```text
git clone https://github.com/kuncooroo/Ritelin-Smart-Shelf.git
cd Ritelin-Smart-Shelf
cd backend
```

2. Pasang library PHP.

```text
composer install
```

3. Buat file pengaturan lokal dari contoh yang sudah ada.

```text
copy .env.example .env
php artisan key:generate
```

File `.env` berisi kunci lokal. Jangan diunggah ke GitHub.

4. Buat database kosong, lalu isi tabel dan data contoh. Contoh bawaan memakai SQLite, jadi MySQL tidak wajib untuk mencoba di komputer sendiri.

```text
New-Item -ItemType File -Force database\database.sqlite
php artisan migrate --seed
```

Di macOS atau Linux, baris pembuatan file diganti dengan `touch database/database.sqlite`.

5. Bangun tampilan dashboard.

```text
npm install
npm run build
```

6. Jalankan dua perintah ini di **dua terminal terpisah**, keduanya dari folder `backend`.

Terminal pertama, website:

```text
php artisan serve --host=127.0.0.1 --port=8000
```

Terminal kedua, pembaruan langsung ke browser:

```text
php artisan reverb:start --host=127.0.0.1 --port=8080
```

Reverb tidak boleh memakai port 8000. Port 8000 milik website. Kalau keduanya sama, halaman tidak akan terbuka.

7. Buka [http://127.0.0.1:8000/login](http://127.0.0.1:8000/login).

Biarkan kedua terminal tetap berjalan. Menutup terminal sama dengan mematikan bagian itu.

## Akun untuk masuk

Semua akun contoh memakai kata sandi `password`. Ini hanya untuk komputer pengembangan.

| Email | Peran | Bisa melakukan |
| --- | --- | --- |
| `admin@ritelin.local` | Superadmin | Seluruh pengelolaan, termasuk akun pengguna |
| `admin.toko@ritelin.local` | Admin | Perangkat, produk, kasir, anomali, dan pengaturan |
| `petugas@ritelin.local` | Staf | Melihat rak, aktivitas, anomali, rekonsiliasi, dan laporan |

Akun staf lain yang ikut terisi: `pemilik@ritelin.local`, `kasir.demo@ritelin.local`, dan `gudang@ritelin.local`.

Perangkat contoh `SHELF-001` memakai token `local-shelf-001-token`. `SHELF-010`, `SHELF-011`, dan `SHELF-012` hanya data contoh dan belum terpasang di alat. Ganti akun dan token sebelum sistem dipakai di luar mesin pengembangan.

## Isi repo

| Folder | Isi |
| --- | --- |
| `backend/` | Dashboard admin dan API yang menerima data rak |
| `firmware/smart_shelf/` | Program untuk ESP32 |
| `docs/` | Penjelasan backend, perangkat, tahapan, dan pemasangan server |
| `deploy/ubuntu/` | Skrip pemasangan di VPS Ubuntu |

Dokumen lanjutan:

- [Cara kerja dashboard dan API](docs/backend.md)
- [Perangkat, pin, dan kalibrasi](docs/hardware.md)
- [Pemasangan di VPS Ubuntu](docs/vps.md)
- [Tahapan pengerjaan](docs/PHASE.md)

## Memasang alat

Bagian ini tidak diperlukan untuk membuka dashboard.

1. Pasang Arduino IDE dan board ESP32.
2. Pasang pustaka HX711 oleh Bogdan Necula.
3. Buka `firmware/smart_shelf/smart_shelf.ino`.
4. Isi Wi-Fi dan alamat API di `firmware/smart_shelf/config.h`. Contoh kosong ada di `config.example.h`.
5. Unggah sketsa itu ke ESP32. Jangan mengunggah `detector.h` sebagai sketsa.

Alat mengirim ke `http://<alamat-komputer>:8000`. Alamat itu harus bisa dijangkau dari Wi-Fi yang sama dengan ESP32, bukan `127.0.0.1` milik komputer lain. Satu perangkat memakai satu token. Token baru dibuat dari halaman Perangkat di dashboard.
