# PRODUCT REQUIREMENTS DOCUMENT (PRD)

## Rakku

* **Versi:** 1.0

* **Status:** Draft untuk pengembangan prototype

* **Kategori:** Internet of Things (IoT) — Smart Retail & Creative Commerce

* **Target:** CREANOMIC 2026 — Universitas Brawijaya

## 1. Ringkasan Produk

Rakku merupakan perangkat pemantauan barang pada rak toko berbasis Internet of Things (IoT). Perangkat menggunakan load cell dan modul HX711 untuk mendeteksi perubahan berat barang, kemudian memproses perubahan tersebut menggunakan mikrokontroler ESP32.

Sistem dirancang untuk memantau produk tertentu yang bernilai tinggi atau berisiko mengalami selisih stok, seperti parfum premium, kosmetik, skincare, dan produk elektronik berukuran kecil.

Pada tahap awal, perangkat bekerja secara mandiri untuk membaca berat, mendeteksi perubahan, serta menghasilkan event ketika barang kemungkinan diambil atau dikembalikan. Pada tahap berikutnya, perangkat akan terhubung ke backend Rakku dan sistem POS untuk membantu mendeteksi ketidaksesuaian antara aktivitas fisik pada rak dan transaksi kasir.

### 1.1 Visi produk

Membangun solusi smart retail yang menghubungkan kondisi fisik barang di rak dengan informasi digital secara otomatis, efisien, dan terukur.

### 1.2 Nilai utama

* Pemantauan fisik produk tertentu tanpa harus memasang sensor pada seluruh barang toko.

* Deteksi perubahan berat secara otomatis.

* Potensi pengurangan selisih stok melalui pencocokan aktivitas rak dengan transaksi kasir.

* Perangkat modular, ringkas, dan dapat dikembangkan menuju operasi berbaterai.

* Integrasi dengan sistem retail yang sudah ada tanpa mewajibkan toko mengganti POS.

## 2. Latar Belakang dan Permasalahan

Pengelolaan stok retail umumnya mengandalkan pencatatan transaksi kasir dan pemeriksaan stok secara manual. Informasi tersebut belum tentu selalu menggambarkan kondisi fisik barang pada rak secara langsung.

Beberapa permasalahan yang ingin ditangani:

1. Barang diambil dari rak tetapi belum tercatat dalam transaksi.

2. Stok fisik tidak sesuai dengan catatan sistem.

3. Petugas memerlukan waktu untuk mengetahui perubahan kondisi barang.

4. Produk bernilai tinggi membutuhkan pemantauan lebih terarah.

5. Sistem POS biasa tidak selalu mengetahui aktivitas pengambilan dan pengembalian barang sebelum transaksi terjadi.

Rakku bertujuan memberikan informasi tambahan dari sensor fisik agar aktivitas pada rak dapat dianalisis bersama catatan transaksi.

> **Batasan penting:** Perubahan berat bukan bukti bahwa barang telah terjual atau dicuri. Sistem hanya menghasilkan indikasi aktivitas fisik yang perlu dikonfirmasi dengan data lain.

## 3. Tujuan Produk

### 3.1 Tujuan MVP

MVP (Minimum Viable Product) difokuskan pada keberhasilan perangkat keras dan firmware sebelum pengembangan dashboard.

* Membaca data load cell menggunakan HX711.

* Melakukan kalibrasi berat dalam satuan gram.

* Mengurangi noise dan menstabilkan pembacaan.

* Menentukan kondisi normal, kemungkinan barang diambil, dan kemungkinan barang dikembalikan.

* Menampilkan berat dan event melalui Serial Monitor.

* Menghasilkan data event yang konsisten untuk integrasi berikutnya.

* Menyiapkan fondasi komunikasi Wi-Fi melalui HTTP.

### 3.2 Tujuan pengembangan lanjutan

* Mengirim event sensor ke backend Laravel.

* Menyimpan riwayat aktivitas rak.

* Menampilkan status perangkat pada dashboard.

* Mengintegrasikan transaksi POS.

* Mendeteksi ketidaksesuaian aktivitas rak dan transaksi.

* Mengoptimalkan konsumsi daya dan merancang perangkat compact.

## 4. Target Pengguna

| Pengguna | Kebutuhan | 
| ----- | ----- | 
| **Pemilik toko** | Mengetahui indikasi selisih stok pada produk tertentu | 
| **Petugas toko** | Memantau perubahan barang di rak | 
| **Admin retail** | Mengelola pemetaan produk dan perangkat | 
| **Pengembang** | Menguji sensor, firmware, komunikasi, dan integrasi | 
| **Juri kompetisi** | Melihat demonstrasi hubungan perangkat fisik, IoT, dan sistem digital | 

## 5. Ruang Lingkup Pengembangan

### 5.1 Fase 1 — Hardware dan firmware

Termasuk dalam ruang lingkup:

* ESP32 DevKit.

* Load cell.

* HX711.

* Platform mekanik untuk menempatkan produk.

* Pembacaan raw data.

* Kalibrasi berat.

* Filtering dan stabilisasi.

* Deteksi perubahan berat.

* Logika event removal dan return.

* Serial Monitor.

* Pengujian stabilitas pembacaan.

### 5.2 Fase 2 — Komunikasi IoT

* Koneksi ESP32 ke Wi-Fi.

* Identitas unik perangkat.

* Pengiriman event menggunakan HTTP/REST.

* Penanganan koneksi terputus.

* Retry dan pencegahan event ganda.

* Sinkronisasi waktu perangkat.

### 5.3 Fase 3 — Backend dan dashboard

* Laravel REST API.

* Database MySQL.

* Registrasi perangkat dan pemetaan produk.

* Riwayat event sensor.

* Dashboard monitoring.

* Status online/offline dan waktu sinkronisasi.

### 5.4 Fase 4 — Integrasi POS

* POS simulator untuk demonstrasi atau integrasi POS existing.

* Penerimaan data transaksi.

* Pencocokan event rak dengan transaksi.

* Status matched, unmatched, dan needs review.

* Pelaporan ketidaksesuaian stok.

### 5.5 Fase 5 — Finalisasi produk

* Optimasi daya.

* Pengujian baterai.

* Bluetooth Low Energy (BLE) untuk konfigurasi lokal bila diperlukan.

* Custom PCB.

* Casing 3D-print.

* Pengujian ergonomi dan pemasangan pada rak.

> **Prioritas:** Prioritas saat ini adalah Fase 1. Fase berikutnya belum perlu dikerjakan sampai perangkat berhasil membaca berat dan mendeteksi perubahan secara konsisten.

## 6. Spesifikasi Hardware

| Komponen | Spesifikasi awal | Fungsi | 
| ----- | ----- | ----- | 
| **Mikrokontroler** | ESP32 DevKit | Memproses sensor dan menjalankan komunikasi | 
| **Load cell** | Kapasitas awal 5 kg | Mengukur perubahan beban | 
| **ADC amplifier** | HX711 | Membaca sinyal kecil dari load cell | 
| **Catu daya** | USB 5 V | Sumber daya selama pengembangan | 
| **Platform rak** | Akrilik, aluminium, atau material lain yang kaku | Menopang produk | 
| **Breadboard dan jumper** | Sesuai kebutuhan | Prototyping dan pengujian | 
| **Indikator** | LED opsional | Menunjukkan status alat | 
| **Baterai** | Ditentukan pada fase optimasi | Operasi portabel | 

* Kapasitas 5 kg merupakan pilihan awal, bukan spesifikasi final. Kapasitas, sensitivitas, mekanisme pemasangan, dan resolusi perlu divalidasi terhadap berat produk yang diuji.

* ESP32 sudah mendukung Wi-Fi dan Bluetooth. Tidak diperlukan modul Bluetooth terpisah untuk penggunaan BLE standar.

### 6.1 Persyaratan mekanik

* Platform hanya bertumpu pada mekanisme load cell.

* Beban tidak boleh dialihkan ke sisi rak atau casing.

* Load cell tidak boleh menerima beban melebihi kapasitasnya.

* Kabel sensor harus terpasang aman.

* Posisi produk dibuat konsisten selama pengujian.

* Rancangan casing final dibuat setelah dimensi komponen dan kebutuhan mekanik diketahui.

## 7. Arsitektur Sistem

### 7.1 Arsitektur perangkat

`Load Cell` → `HX711` → `ESP32` → `Wi-Fi` → `Laravel API` → `MySQL` → `Dashboard`.

POS akan menjadi sumber data transaksi terpisah yang nantinya terhubung ke backend yang sama.

### 7.2 Pemilihan komunikasi

**Komunikasi utama:** Wi-Fi + HTTP/REST.

**Alasan:**

* Implementasi relatif sederhana.

* Mudah diuji menggunakan ESP32 dan Laravel.

* Mendukung pengiriman event melalui HTTP POST.

* Mendukung pengambilan konfigurasi melalui HTTP GET.

MQTT dapat dievaluasi jika jumlah perangkat dan kebutuhan komunikasi meningkat. BLE dipertimbangkan untuk konfigurasi awal, provisioning Wi-Fi, dan maintenance lokal.

### 7.3 Stack teknologi

| Bagian | Teknologi | 
| ----- | ----- | 
| **Firmware** | Arduino C/C++ | 
| **Mikrokontroler** | ESP32 | 
| **Sensor** | Load cell + HX711 | 
| **Protokol IoT** | HTTP/REST melalui Wi-Fi | 
| **Backend** | Laravel REST API | 
| **Database** | MySQL | 
| **Dashboard** | Laravel Livewire atau Next.js | 
| **Integrasi kasir** | API/webhook POS atau POS simulator | 

Pilihan dashboard final dapat ditentukan ketika Fase 1 dan Fase 2 berhasil.

## 8. Persyaratan Fungsional

* **FR-01 — Pembacaan sensor:** ESP32 harus dapat membaca raw data dari HX711 secara berkala.

  * *Kriteria penerimaan:* Data dapat ditampilkan di Serial Monitor dan perubahan beban menghasilkan perubahan pembacaan yang dapat diamati.

* **FR-02 — Kalibrasi:** Sistem harus mendukung tare dan kalibrasi menggunakan beban referensi.

  * *Kriteria penerimaan:* Pembacaan dapat dikonversi ke gram dan diuji terhadap beban referensi yang diketahui.

* **FR-03 — Filtering:** Firmware harus mengurangi pengaruh noise dan perubahan sesaat.

  * *Kriteria penerimaan:* Fluktuasi kecil saat beban tetap tidak langsung memicu event removal atau return.

* **FR-04 — Deteksi barang diambil:** Sistem mendeteksi perubahan berat yang signifikan dan stabil sebagai indikasi barang diambil.

  * *Kriteria penerimaan:* Ketika produk diangkat dari platform, firmware menghasilkan event `ITEM_REMOVED` setelah kondisi terverifikasi.

* **FR-05 — Deteksi barang dikembalikan:** Sistem mendeteksi kondisi ketika berat kembali ke rentang yang sesuai dengan kondisi produk sebelumnya.

  * *Kriteria penerimaan:* Pengembalian produk menghasilkan event `ITEM_RETURNED` atau status pengembalian yang setara.

* **FR-06 — Output event:** Firmware menampilkan identitas perangkat, berat, status, dan event melalui Serial Monitor.

* **FR-07 — Pengiriman data:** Pada fase komunikasi, ESP32 mengirim event melalui HTTP dan dapat mencoba kembali pengiriman ketika jaringan gagal.

* **FR-08 — Penyimpanan data:** Backend menyimpan event beserta identitas perangkat, jenis event, waktu, dan berat yang relevan.

* **FR-09 — Pencocokan transaksi:** Pada fase integrasi POS, sistem mencocokkan event sensor dengan transaksi yang relevan berdasarkan produk, jumlah, waktu, dan aturan yang ditetapkan.

* **FR-10 — Monitoring perangkat:** Dashboard menampilkan kondisi perangkat dan riwayat event setelah backend tersedia.

## 9. Logika Deteksi

Firmware tidak boleh menganggap setiap perubahan berat sebagai pengambilan barang.

**Alur yang diharapkan:**

1. Membaca berat secara berkala.

2. Menentukan baseline pada kondisi yang valid.

3. Menghitung selisih berat terhadap kondisi sebelumnya.

4. Membandingkan selisih dengan threshold yang dapat dikonfigurasi.

5. Memverifikasi perubahan selama periode tertentu.

6. Menghasilkan event setelah perubahan stabil.

7. Memantau apakah berat kembali ke kondisi sebelumnya.

8. Menampilkan event atau status yang sesuai.

**Contoh State:**
`NORMAL` → `CHANGE_DETECTED` → `VERIFYING` → `ITEM_REMOVED`

Jika berat kembali ke rentang sebelumnya:
`ITEM_REMOVED` → `VERIFYING_RETURN` → `ITEM_RETURNED`

Baseline harus dikelola secara hati-hati. Jika produk ditambahkan kembali, diganti, atau jumlah barang berubah, sistem perlu memperbarui baseline berdasarkan aturan yang valid.

### Batasan deteksi

Satu load cell tidak dapat memastikan identitas barang secara mandiri. Pemetaan produk hanya valid jika satu platform memang ditujukan untuk satu jenis produk dan tidak ada barang lain yang ikut membebani sensor.

Jika satu platform berisi beberapa unit produk, penghitungan jumlah memerlukan informasi berat satuan yang konsisten dan algoritma tambahan. Perubahan berat saja tidak selalu dapat menentukan jumlah unit secara akurat.

## 10. Format Event dan Komunikasi

Contoh payload untuk integrasi berikutnya:

```
{
  "device_id": "SHELF-001",
  "event_id": "evt-000123",
  "event_type": "ITEM_REMOVED",
  "weight_grams": 12.4,
  "timestamp": "2026-10-23T01:30:00Z"
}

```

*Payload di atas hanya contoh struktur. Nilai berat, waktu, dan jenis event harus berasal dari firmware aktual.*

**Ketentuan:**

* Setiap perangkat memiliki `device_id` unik.

* Setiap event memiliki `event_id` unik.

* Timestamp harus memiliki zona waktu yang jelas.

* Backend memvalidasi data sebelum menyimpannya.

* Retry tidak boleh menyebabkan satu event tercatat berkali-kali.

* Kredensial perangkat tidak boleh ditanam sebagai rahasia bersama yang sama untuk semua perangkat.

* Komunikasi melalui jaringan publik harus menggunakan HTTPS dan autentikasi perangkat.

Untuk fase awal, event boleh ditampilkan di Serial Monitor tanpa backend.

## 11. Data yang Dikelola

* **Data perangkat:** ID perangkat, nama atau kode rak, status perangkat, versi firmware, waktu komunikasi terakhir.

* **Data sensor:** Raw reading, berat hasil kalibrasi, baseline, threshold, status deteksi, hasil kalibrasi.

* **Data event:** ID event, ID perangkat, produk terkait, jenis event, berat sebelum dan sesudah (jika tersedia), waktu kejadian, status verifikasi, status pengiriman ke backend.

* **Data transaksi (fase lanjutan):** ID transaksi POS, produk, kuantitas, waktu transaksi, referensi event yang cocok, hasil pencocokan.

## 12. Persyaratan Nonfungsional

* **NFR-01 — Stabilitas:** Pembacaan sensor harus cukup stabil untuk membedakan noise biasa dan perubahan beban yang bermakna.

* **NFR-02 — Modularitas:** Firmware, backend, dan dashboard dikembangkan sebagai bagian yang terpisah sehingga perubahan satu komponen tidak memerlukan perombakan total komponen lain.

* **NFR-03 — Keandalan event:** Event tidak boleh hilang tanpa penanganan ketika jaringan terputus. Implementasi lanjutan perlu menyediakan antrean lokal atau mekanisme penyimpanan sementara dan retry.

* **NFR-04 — Keamanan:** Endpoint backend harus memvalidasi identitas perangkat dan payload. Data transaksi harus terlindungi.

* **NFR-05 — Efisiensi daya:** Versi awal boleh menggunakan USB. Operasi berbaterai merupakan target pengembangan lanjutan dan harus divalidasi dengan pengukuran konsumsi daya aktual.

* **NFR-06 — Ergonomi:** Perangkat final diharapkan ringkas, memiliki casing yang rapi, konektor yang aman, dan mekanisme pemasangan yang tidak mengganggu penggunaan rak.

* **NFR-07 — Pemeliharaan:** Kalibrasi, penggantian perangkat, dan pembaruan firmware harus dapat dilakukan tanpa merusak platform rak.

## 13. Kriteria Keberhasilan MVP

MVP dinyatakan berhasil apabila:

 1. ESP32 dapat membaca data HX711 secara konsisten.

 2. Tare dan kalibrasi dapat dilakukan menggunakan beban referensi.

 3. Berat dapat ditampilkan dalam gram.

 4. Noise normal tidak memicu event secara berlebihan.

 5. Pengambilan produk dapat dideteksi dalam skenario pengujian.

 6. Pengembalian produk dapat dibedakan dari pengambilan yang menetap.

 7. Event dapat ditampilkan dengan jelas pada Serial Monitor.

 8. Sistem dapat menjalankan pengujian berulang dengan hasil yang tercatat.

 9. Firmware menghasilkan struktur event yang siap dikirim ke backend.

10. Dokumentasi wiring, kalibrasi, dan cara menjalankan tersedia.

Target kuantitatif seperti toleransi berat, waktu deteksi, tingkat keberhasilan, dan false positive rate harus ditentukan setelah jenis load cell, berat produk, serta kondisi mekanik diketahui.

## 14. Rencana Pengujian

| Pengujian | Prosedur | Hasil yang diharapkan | 
| ----- | ----- | ----- | 
| **Tanpa beban** | Kosongkan platform | Pembacaan mendekati nol setelah tare | 
| **Beban referensi** | Letakkan beban dengan massa diketahui | Hasil mendekati massa referensi | 
| **Beban stabil** | Biarkan produk di platform | Tidak ada event removal palsu | 
| **Pengambilan** | Angkat produk dari platform | Event removal terdeteksi | 
| **Pengembalian** | Letakkan produk kembali | Event return terdeteksi | 
| **Gangguan ringan** | Sentuh platform secara wajar | Sistem tidak langsung memicu event permanen | 
| **Wi-Fi terputus** | Putuskan koneksi saat fase komunikasi | Firmware menangani kegagalan pengiriman | 
| **Pengiriman ulang** | Kirim event yang sama kembali | Backend tidak menggandakan event | 
| **Pengujian berulang** | Lakukan siklus ambil-kembali beberapa kali | Hasil dicatat dan dievaluasi | 

*Setiap pengujian perlu mencatat kondisi, hasil aktual, error, dan tindakan perbaikan.*

## 15. Risiko dan Mitigasi

| Risiko | Mitigasi | 
| ----- | ----- | 
| **Noise sensor** | Filtering, median/average, dan verifikasi stabilitas | 
| **Kalibrasi tidak akurat** | Gunakan beban referensi dan prosedur kalibrasi konsisten | 
| **Platform menyentuh rak** | Perbaiki desain mekanik agar beban melewati load cell | 
| **Produk dipindahkan sementara** | Gunakan state machine dan masa verifikasi | 
| **Berat produk bervariasi** | Tetapkan rentang toleransi berdasarkan pengujian | 
| **Wi-Fi tidak stabil** | Retry, antrean lokal, dan ID event unik | 
| **Baterai cepat habis** | Ukur konsumsi daya dan optimalkan duty cycle | 
| **Sensor dianggap bukti transaksi** | Pisahkan event fisik dari transaksi resmi | 
| **Event cocok dengan transaksi yang salah** | Gunakan jendela waktu, identitas produk, kuantitas, dan status review | 

## 16. Roadmap Pengembangan

1. **Fase 1 — Prototipe sensor:** ESP32, load cell, HX711, pembacaan raw data, dan kalibrasi.

2. **Fase 2 — Firmware deteksi:** Filtering, baseline, state machine, removal/return, dan output Serial Monitor.

3. **Fase 3 — Konektivitas:** Wi-Fi, HTTP, identitas perangkat, retry, dan pengiriman event.

4. **Fase 4 — Backend:** Laravel REST API, database MySQL, validasi, dan penyimpanan event.

5. **Fase 5 — Dashboard:** Monitoring perangkat, berat, riwayat event, dan peringatan.

6. **Fase 6 — Integrasi POS:** POS simulator atau API/webhook POS existing dan pencocokan transaksi.

7. **Fase 7 — Finalisasi hardware:** Optimasi daya, baterai, BLE bila diperlukan, custom PCB, dan casing 3D-print.

## 17. Definisi Selesai

Prototype Smart Shelf dianggap siap masuk ke tahap integrasi backend apabila:

* Rangkaian hardware bekerja dengan benar.

* Kalibrasi dapat diulang.

* Logika deteksi removal/return telah diuji.

* Hasil pengujian dan batasan sensor terdokumentasi.

* Firmware menghasilkan event yang konsisten.

* Kode dapat dibangun ulang dan diunggah mengikuti instruksi.

* Pinout, daftar komponen, dan konfigurasi firmware terdokumentasi.

*Dashboard dan integrasi POS tidak menjadi syarat penyelesaian Fase 1.*

## 18. Kesimpulan

Rakku dikembangkan secara bertahap, dimulai dari pembuktian bahwa load cell dan ESP32 mampu mendeteksi perubahan kondisi barang secara andal.

Fokus awal bukan membuat sistem retail lengkap, melainkan membangun perangkat IoT yang stabil, terukur, dan siap diintegrasikan. Setelah perangkat berhasil, sistem dapat diperluas menjadi platform monitoring inventori yang menghubungkan aktivitas fisik pada rak dengan transaksi kasir.

> **Prinsip pengembangan:** Buktikan sensor, validasi deteksi, stabilkan firmware, baru bangun komunikasi, backend, dashboard, dan integrasi POS.