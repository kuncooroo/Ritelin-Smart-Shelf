# Perangkat RITELIN Smart Shelf

Firmware ada di `firmware/smart_shelf/`. Bahasa Arduino C/C++ untuk ESP32 DevKit. Catu daya prototype adalah USB 5 V. Load cell awal berkapasitas 5 kg.

Perubahan berat adalah indikasi aktivitas rak, bukan bukti transaksi.

## Komponen

| Komponen | Catatan |
| --- | --- |
| ESP32 DevKit | Wi-Fi dan BLE sudah ada di chip. Tidak perlu modul Bluetooth terpisah. |
| HX711 | ADC untuk load cell. |
| Load cell 5 kg | Kapasitas awal, belum spesifikasi final. |
| Platform kaku | Akrilik atau aluminium. Beban hanya boleh lewat load cell. |
| Kabel jumper | Prototipe. |
| USB 5 V | Sumber daya sampai konsumsi arus benar-benar diukur. |

Pustaka Arduino yang perlu dipasang: HX711 oleh Bogdan Necula. Wi-Fi, HTTP, Preferences, dan BLE mengikuti board package ESP32.

## Pinout

| Sinyal HX711 | GPIO ESP32 |
| --- | --- |
| DT / DOUT | 16 |
| SCK | 4 |
| VCC | 3V3 atau 5 V sesuai modul |
| GND | GND |

Jangan memakai GPIO 0, 2, 12, atau 15 untuk sensor. Pin itu mengubah mode boot.

## Mekanik

- Platform hanya bertumpu pada load cell. Jika deck menyentuh rak atau dinding casing, berat teralihkan dan pembacaan salah.
- Jangan membebani load cell di atas kapasitasnya.
- Kabel sensor dikunci agar tidak ikut menahan deck.
- Posisi produk harus sama pada setiap uji.
- Satu platform untuk satu jenis produk. Sensor tidak mengenali identitas barang.

## Kalibrasi

1. Buka Serial Monitor pada 115200 baud.
2. Kosongkan platform, lalu kirim `tare`. Pembacaan tanpa beban harus mendekati nol setelah kalibrasi.
3. Letakkan beban yang massanya diketahui, misalnya 100 g.
4. Kirim `calibrate 100`. Firmware menyimpan faktor ke memori perangkat.
5. Angkat dan letakkan ulang beban. `weight_g` harus mendekati massa referensi.
6. `status` menampilkan faktor tanpa menampilkan token.

Perintah lain: `factor <nilai>` bila faktor sudah diketahui, dan `provision` untuk BLE.

Sebelum kalibrasi, firmware hanya mencetak raw dan status `BELUM_KALIBRASI`. Event tidak dibuat dari angka raw.

Dua cara memakai tare:

- Tare saat platform kosong, lalu letakkan produk. Kenaikan berat yang stabil menjadi baseline tanpa event. Pengambilan setelah status `NORMAL` menghasilkan `ITEM_REMOVED`.
- Tare saat produk sudah berada di platform. Pengambilan terlihat sebagai berat negatif dan tetap bisa menghasilkan event.

Tunggu status `NORMAL` sebelum uji ambil-kembali. Median lima sampel butuh beberapa pembacaan sebelum perubahan diakui, lalu jendela verifikasi bawaan 1500 ms.

## Deteksi

State: `NORMAL` → `CHANGE_DETECTED` → `VERIFYING` → `ITEM_REMOVED`, lalu `VERIFYING_RETURN` → `ITEM_RETURNED`.

- Fluktuasi di bawah threshold 15 g tidak membuat event.
- Sentuhan yang kembali sebelum jendela verifikasi selesai tidak menjadi event permanen.
- Pengambilan yang tetap menghasilkan `ITEM_REMOVED` satu kali.
- Berat yang kembali ke baseline sebelum pengambilan menghasilkan `ITEM_RETURNED`.
- Menaruh beban baru saat `NORMAL` memperbarui baseline dan tidak membuat event removal.

Baris Serial:

```text
device_id=SHELF-001 raw=123456 weight_g=80.0 state=NORMAL event=NONE
```

Saat event dan waktu sudah tersinkron:

```json
{"device_id":"SHELF-001","event_id":"evt-000001","event_type":"ITEM_REMOVED","weight_grams":0.0,"timestamp":"2026-10-23T01:30:00Z"}
```

`event_id` naik dan tersimpan. Pengiriman ulang memakai id yang sama.

Uji logika tanpa perangkat:

```text
php firmware/tests/detector_spec.php
```

Uji itu tidak menggantikan uji di meja. Centang berikut masih harus diisi setelah perangkat nyata dijalankan: raw konsisten, tare mendekati nol, gram sesuai beban referensi, ambil, kembali, sentuhan, dan siklus berulang.

## Wi-Fi dan HTTP

Isi `WIFI_SSID`, `WIFI_PASSWORD`, dan `API_BASE_URL` di `firmware/smart_shelf/config.h`. Token demo `local-shelf-001-token` hanya untuk `SHELF-001` di mesin pengembangan. Perangkat lain harus mendapat token baru dari dashboard. Jangan memakai satu token untuk semua perangkat.

`POST /api/events` memakai `Authorization: Bearer`. Jawaban 200 atau 201 menghapus event dari antrean. Kegagalan jaringan menaikkan penghitung percobaan dan mempertahankan `event_id`. Antrean memuat 16 event. Jika penuh, event baru ditolak dan event lama tetap ada.

Waktu diambil dari NTP. Sebelum jam valid, event tetap masuk antrean dan timestamp diisi setelah sinkron.

Di LAN pengembangan, `http://` diperbolehkan. Di jaringan publik gunakan `https://`. `API_TLS_INSECURE` harus tetap 0 supaya sertifikat tidak dilewati.

`GET /api/config` mengambil `threshold_grams` dan `verify_ms`.

## BLE, daya, dan casing

Kirim `provision` di Serial untuk membuka BLE bernama `RITELIN-SHELF-001`. Tulis SSID, sandi, dan token pada karakteristik di `ble_provision.cpp`, lalu tulis `1` pada karakteristik commit. Perangkat menyimpan kredensial dan mulai ulang. Token yang dikosongkan tidak menghapus token lama.

`POWER_SAVE` bawaan adalah 0. Nilai 1 mengaktifkan modem sleep dan light sleep hanya saat status `NORMAL` dan antrean kosong. Konsumsi arus belum diukur, jadi operasi baterai belum boleh diklaim dan kotak baterai jangan dipasang sebagai sumber utama.

PCB khusus dan casing cetak belum dibuat. Rancangan nanti harus memenuhi aturan mekanik di atas, menyediakan akses USB untuk servis, dan tidak membuat deck menumpu pada dinding kotak. Kalibrasi serta penggantian ESP32 harus bisa dilakukan tanpa membongkar ikatan load cell.

## Unggah

1. Pasang board package ESP32 dan pustaka HX711.
2. Buka `firmware/smart_shelf/smart_shelf.ino`.
3. Pilih board ESP32 DevKit dan port USB.
4. Ubah `DEVICE_ID` bila bukan perangkat demo, lalu unggah.
5. Jalankan backend dan arahkan `API_BASE_URL` ke IP komputer, bukan `127.0.0.1`, jika ESP32 berada di jaringan yang sama.
