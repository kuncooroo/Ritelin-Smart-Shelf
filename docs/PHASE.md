# Tahapan RITELIN Smart Shelf

Sumber persyaratan: [prd.md](prd.md).

**Tahap aktif: implementasi perangkat lunak Tahap 1–7 sudah ditulis.** Syarat yang hanya bisa dibuktikan di meja (ESP32, beban, arus, PCB, casing) masih terbuka. Jangan menandai syarat itu selesai sebelum uji fisik dicatat.

Prinsip tetap:

- Perubahan berat adalah indikasi fisik, bukan bukti penjualan, pencurian, atau transaksi.
- Satu platform load cell untuk satu jenis produk.
- Rahasia perangkat unik per perangkat. Jangan memakai satu kunci untuk semua device.
- Jaringan publik memakai HTTPS dan autentikasi per perangkat.

Dashboard memakai Laravel Livewire di dalam aplikasi `backend/`.

## Tahap 0 — Rules

Selesai. Berisi rules di `.cursor/rules/` dan dokumen ini.

## Tahap 1 — Baca dan kalibrasi

Firmware ada di `firmware/smart_shelf/`. Prosedur ada di [hardware.md](hardware.md).

Syarat keluar:

- [ ] ESP32 membaca raw data HX711 secara konsisten di Serial Monitor.
- [ ] Tare membuat pembacaan tanpa beban mendekati nol.
- [ ] Berat tampil dalam gram dan diuji dengan beban referensi yang diketahui.
- [x] Pinout, daftar komponen, dan prosedur kalibrasi tertulis di `docs/hardware.md`.

## Tahap 2 — Deteksi

State dan filter ada di `firmware/smart_shelf/detector.h`. Uji host: `php firmware/tests/detector_spec.php` (lulus).

Syarat keluar:

- [x] Beban stabil tidak memicu event removal palsu. (uji host)
- [x] Pengambilan produk menghasilkan `ITEM_REMOVED` setelah verifikasi. (uji host)
- [x] Pengembalian produk menghasilkan `ITEM_RETURNED`. (uji host)
- [x] Sentuhan ringan tidak menjadi event permanen. (uji host)
- [ ] Serial Monitor pada perangkat menampilkan `device_id`, berat, status, dan event.
- [x] Struktur event siap dikirim. Firmware mencetak JSON kontrak saat waktu sudah tersinkron.
- [ ] Hasil uji berulang di meja dan batasan sensor tercatat.

## Tahap 3 — Wi-Fi HTTP

Antrean, retry, dan `event_id` tetap ada di firmware. Server menolak duplikat.

Syarat keluar:

- [x] Setiap perangkat punya `device_id` unik dan setiap kejadian punya `event_id` unik.
- [ ] ESP32 benar-benar mengirim event lewat Wi-Fi. Kontrak HTTP sudah diuji ke server lokal dengan klien uji, bukan dari papan.
- [x] Putus jaringan tidak menghilangkan event: ada antrean lokal dan retry di firmware.
- [x] Pengiriman ulang `event_id` yang sama tidak menambah baris. Diuji di `ShelfApiTest` dan server yang sedang berjalan.

## Tahap 4 — Laravel API dan MySQL

API ada di `backend/`. Uji otomatis memakai SQLite di memori. Contoh `.env` menyiapkan MySQL; instalasi lokal saat ini memakai SQLite karena server MySQL tidak dihubungkan pada eksekusi ini.

Syarat keluar:

- [x] Perangkat terdaftar dengan kredensial unik per `device_id`.
- [x] Payload divalidasi sebelum disimpan.
- [x] Event tersimpan beserta perangkat, jenis, waktu, dan berat.
- [x] Pengiriman ulang `event_id` yang sama tidak menambah baris.

## Tahap 5 — Dashboard admin

Laravel Livewire.

Syarat keluar:

- [x] Admin melihat status online/offline dan waktu sinkron terakhir.
- [x] Admin melihat berat terakhir dan riwayat event.
- [x] Admin memetakan satu perangkat ke satu jenis produk.
- [x] Label UI menyebut indikasi aktivitas rak, bukan terjual, hilang, atau dicuri.

## Tahap 6 — POS

Simulator ada di halaman Transaksi kasir. Webhook: `POST /api/pos/transactions`. Proyek SimplePOS tidak diubah.

Syarat keluar:

- [x] Transaksi masuk ke backend yang sama.
- [x] Event rak dicocokkan dengan transaksi menurut produk, jumlah, dan jendela waktu.
- [x] Status pencocokan: `matched`, `unmatched`, atau `needs review`.

## Tahap 7 — Final hardware

Kode BLE provisioning dan sakelar `POWER_SAVE` ada di firmware. Arus belum diukur. PCB dan casing belum dibuat. Aturan mekaniknya ada di [hardware.md](hardware.md).

Syarat keluar:

- [ ] Konsumsi daya terukur sebelum klaim operasi baterai.
- [ ] Casing tidak mengalihkan beban keluar dari load cell.
- [ ] Kalibrasi dan penggantian perangkat tidak merusak platform rak.
