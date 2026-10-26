# Backend dan dashboard Rakku

Aplikasi ada di `backend/`. API memakai Laravel, data pengembangan memakai SQLite, dan MySQL disiapkan di `.env.example` untuk server. Dashboard admin memakai Laravel Livewire. Pilihan ini diambil di Tahap 5 karena API dan halaman admin bisa hidup dalam satu aplikasi.

Perubahan berat ditampilkan sebagai indikasi aktivitas rak, bukan bukti transaksi.

## Menjalankan

```text
cd backend
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan reverb:start
```

`php artisan serve` dan `php artisan reverb:start` berjalan di dua terminal. Perangkat tetap mengirim lewat HTTP. Reverb hanya memberi tahu browser admin bahwa ada data baru, lalu halaman Livewire memuat ulang isinya tanpa refresh manual.

Buka `http://127.0.0.1:8000/login`.

`php artisan migrate --seed` di mesin pengembangan menjalankan `MainSeeder` lalu `DemoSeeder`. Di production jalankan `php artisan db:seed --class=MainSeeder` sekali untuk peran dan akun superadmin. Jangan menjalankan `DemoSeeder` pada data toko yang sudah dipakai.

Akun demo lokal, semuanya memakai kata sandi `password`:

- `admin@ritelin.local` — superadmin, peran tertinggi. Satu-satunya peran yang mengelola akun, plus seluruh pengelolaan rak.
- `admin.toko@ritelin.local` — admin. Mengelola perangkat, produk, kasir, anomali, dan pengaturan. Tidak mengelola akun.
- `petugas@ritelin.local`, `pemilik@ritelin.local`, `kasir.demo@ritelin.local`, dan `gudang@ritelin.local` — staf. Memantau perangkat, aktivitas, anomali, rekonsiliasi, dan laporan.

Token perangkat yang terpasang tetap `SHELF-001`: `local-shelf-001-token`. Seeder demo tidak mengubah nama, lokasi, slot, produk, token, atau riwayat `SHELF-001` yang sudah ada. `SHELF-010`, `SHELF-011`, dan `SHELF-012` hanya data contoh dan belum terpasang. Token webhook kasir: `local-pos-webhook-token`. Ganti akun dan token sebelum dipakai di luar mesin pengembangan. Perangkat baru mendapat token acak sendiri dari halaman Perangkat.

Untuk MySQL, buat database `rakku`, lalu isi `DB_CONNECTION=mysql` beserta host, nama database, dan pengguna di `.env`.

## API

Semua permintaan perangkat memakai `Authorization: Bearer <token perangkat>`.

`POST /api/events`

```json
{
  "device_id": "SHELF-001",
  "event_id": "evt-000123",
  "event_type": "ITEM_REMOVED",
  "weight_grams": 12.4,
  "timestamp": "2026-10-23T01:30:00Z"
}
```

`event_type` yang sah: `ITEM_REMOVED`, `ITEM_ADDED`, atau `ITEM_RETURNED`. `ITEM_ADDED` artinya berat naik dan stabil. Timestamp wajib berakhiran `Z`. Pengiriman ulang `event_id` yang sama untuk perangkat yang sama mengembalikan data lama dan tidak menambah baris.

`GET /api/config` mengembalikan `threshold_grams` dan `verify_ms`, lalu memperbarui waktu terakhir perangkat terlihat.

`POST /api/pos/transactions` memakai header `X-Pos-Token`.

```json
{
  "external_id": "POS-1",
  "sku": "PARFUM-DEMO",
  "quantity": 1,
  "timestamp": "2026-10-23T01:35:00Z"
}
```

`external_id` yang sama tidak membuat transaksi kedua.

Di lingkungan production, set `RAKKU_REQUIRE_TLS=true` dan layani aplikasi lewat HTTPS.

## Pencocokan

Satu perangkat dipetakan ke satu produk. Transaksi jumlah 1 untuk produk yang sama, di antara 2 menit sebelum event dan 10 menit sesudahnya, menjadi `matched` (cocok dengan transaksi). Tanpa transaksi sampai jendela berakhir menjadi `unmatched` (belum ada transaksi). Lebih dari satu kandidat, jumlah bukan 1, atau barang kembali bersamaan dengan transaksi menjadi `needs_review` (perlu ditinjau). Barang yang kembali tanpa transaksi ditandai kembali ke rak dan tidak dianggap selisih.

Halaman admin memakai kerangka yang sama dengan dashboard staf: menu samping, mode terang/gelap, dan peran. Isinya tetap rak pintar: Beranda, Perangkat, Riwayat rak, Transaksi kasir, dan Pengguna.
