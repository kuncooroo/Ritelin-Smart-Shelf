# Backend dan dashboard RITELIN

Aplikasi ada di `backend/`. API memakai Laravel, data pengembangan memakai SQLite, dan MySQL disiapkan di `.env.example` untuk server. Dashboard admin memakai Laravel Livewire. Pilihan ini diambil di Tahap 5 karena API dan halaman admin bisa hidup dalam satu aplikasi.

Perubahan berat ditampilkan sebagai indikasi aktivitas rak, bukan bukti transaksi.

## Menjalankan

```text
cd backend
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Buka `http://127.0.0.1:8000/login`.

Akun demo lokal, semuanya memakai kata sandi `password`:

- `admin@ritelin.local` — admin retail, mengelola perangkat, produk, kasir, dan pengguna.
- `petugas@ritelin.local` — memantau perangkat dan riwayat rak.
- `pemilik@ritelin.local` — melihat kondisi rak dan indikasi selisih.

Token perangkat demo `SHELF-001`: `local-shelf-001-token`. Token webhook kasir: `local-pos-webhook-token`. Ganti akun dan token sebelum dipakai di luar mesin pengembangan. Perangkat baru mendapat token acak sendiri dari halaman Perangkat.

Untuk MySQL, buat database `ritelin_smart_shelf`, lalu isi `DB_CONNECTION=mysql` beserta host, nama database, dan pengguna di `.env`.

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

`event_type` yang sah: `ITEM_REMOVED` atau `ITEM_RETURNED`. Timestamp wajib berakhiran `Z`. Pengiriman ulang `event_id` yang sama untuk perangkat yang sama mengembalikan data lama dan tidak menambah baris.

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

Di lingkungan production, set `RITELIN_REQUIRE_TLS=true` dan layani aplikasi lewat HTTPS.

## Pencocokan

Satu perangkat dipetakan ke satu produk. Transaksi jumlah 1 untuk produk yang sama, di antara 2 menit sebelum event dan 10 menit sesudahnya, menjadi `matched` (cocok dengan transaksi). Tanpa transaksi sampai jendela berakhir menjadi `unmatched` (belum ada transaksi). Lebih dari satu kandidat, jumlah bukan 1, atau barang kembali bersamaan dengan transaksi menjadi `needs_review` (perlu ditinjau). Barang yang kembali tanpa transaksi ditandai kembali ke rak dan tidak dianggap selisih.

Halaman admin memakai kerangka yang sama dengan dashboard staf: menu samping, mode terang/gelap, dan peran. Isinya tetap rak pintar: Beranda, Perangkat, Riwayat rak, Transaksi kasir, dan Pengguna.
