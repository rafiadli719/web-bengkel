# Analisis dan Planning Sync Otomatis Berdasarkan `E:\projek\PWA_pelanggan`

Tanggal analisis: 2026-06-09

## Ringkasan

Project `PWA_pelanggan` sudah memakai pola sync otomatis yang rapi dan cukup aman untuk data Access:

1. Aplikasi web menyediakan endpoint API penerima sync.
2. Komputer yang menyimpan file Access menjalankan script Python terjadwal.
3. Script membaca tabel/query Access lewat ODBC, bukan upload manual.
4. Data dikirim ke web dalam bentuk batch JSON.
5. Script menyimpan state hash lokal di SQLite agar hanya perubahan yang dikirim ulang.
6. Task Scheduler dipakai untuk menjalankan sync berkala tanpa user harus klik manual.

Pola ini bisa diadopsi ke project `web-bengkel`, dan secara arsitektur lebih cocok untuk kebutuhan auto sync dibanding memaksa semua proses berjalan dari browser atau dari upload manual.

## Temuan Utama di Project Referensi

### 1. Ada 2 jalur sync/import

Project referensi memisahkan proses menjadi 2 jalur:

1. Jalur uploader/import master data
   - untuk pelanggan, kendaraan, item, dan master lain
2. Jalur sync draft servis otomatis
   - untuk membaca `TBLService` dari `FITMOTOR APP`
   - hasilnya membuat draft SPK awal di aplikasi web

Artinya, mereka tidak mencampur semua proses ke satu alur besar.

### 2. Auto sync tidak dijalankan dari Laravel scheduler

Auto sync di `PWA_pelanggan` bukan model:
- server web buka file Access langsung

Tetapi model:
- script Python diletakkan di komputer yang punya akses ke file `.mdb`
- script dijalankan oleh Windows Task Scheduler
- script mengirim hasilnya ke API project web

Ini penting, karena untuk file Access lokal, pola ini jauh lebih stabil.

### 3. Sync memakai token + endpoint khusus

Di `app/Http/Controllers/Api/SyncController.php` ada endpoint:

- `/api/sync/master-data/{entity}`
- `/api/sync/quick-spk-drafts`

Keamanan minimumnya:
- request wajib `POST`
- bisa divalidasi dengan `X-Sync-Token`

Jadi komputer sync bertindak sebagai client terjadwal, bukan sebagai user browser.

### 4. Ada local change tracking

Di script:
- `sync_access_to_api.py`
- `sync_access_service_drafts.py`

ada `SyncStateStore` berbasis SQLite lokal, fungsinya:

- menyimpan `row_key`
- menyimpan `row_hash`
- membandingkan data sekarang dengan data sync terakhir

Hasilnya:
- row yang tidak berubah tidak dikirim lagi
- traffic API lebih hemat
- proses sync lebih cepat

Ini salah satu bagian paling penting dan layak ditiru.

### 5. Jadwal master dan servis dipisah

Di bundle sync:

- `FitMotor Access Master Sync` jalan tiap `10 menit`
- `FitMotor Access Service Draft Sync` jalan tiap `1 menit`

Artinya, dataset berat dan dataset cepat tidak dicampur.

### 6. Ada tahap verifikasi sebelum task dipasang

Sebelum install task, project referensi menjalankan:

- cek file env
- cek Python
- cek dependency
- cek ODBC Access driver
- cek file `.mdb`
- cek source table/query Access
- cek endpoint API

Ini mengurangi risiko task gagal diam-diam setelah dipasang.

## Detail Alur Sync Otomatis di `PWA_pelanggan`

### A. Master Data Auto Sync

Alur:

1. Script Python membaca config dari `sync_settings.env`
2. Script membuka `FITMOTOR GABUNG` lewat `pyodbc`
3. Script membaca source query/tabel seperti:
   - `GABUNG_PELANGGAN`
   - `GABUNG_TBLKENDARAAN`
   - `KENDARAAN_PELANGGAN_GABUNG_TERBARU`
   - `GABUNG_TBLITEM`
   - `GABUNG_TBLITEM_PERBENGKEL`
   - `GABUNG_TBLITEM_PERBENGKEL_NONSTOK`
   - `GABUNG_TBLITEMNONSTOK`
4. Data dipecah per batch
5. Script membandingkan row hash dengan state SQLite lokal
6. Hanya row baru/berubah yang dikirim ke endpoint API
7. API Laravel memanggil importer service
8. Importer melakukan `updateOrInsert` ke tabel target
9. Jika sukses, hash lokal diperbarui

### B. Draft Service Auto Sync

Alur:

1. Script Python membaca `FITMOTOR APP`
2. Script membaca `TBLService` dan table relasi yang dibutuhkan
3. Script membentuk payload draft SPK
4. Data dikirim ke endpoint `/api/sync/quick-spk-drafts`
5. Service Laravel:
   - mencari SPK existing berdasarkan `NoService`
   - insert/update header draft
   - sync complaint
   - sync service item tertentu
   - mengunci overwrite jika SPK sudah diproses manual / progress tertentu

Poin penting:
- sync draft tidak asal overwrite semua
- ada guard supaya data manual user tidak rusak

## Yang Paling Layak Diadopsi ke Project Ini

Untuk project `web-bengkel`, bagian yang paling layak diambil adalah:

1. pemisahan `master sync` dan `service sync`
2. endpoint API khusus sync
3. token khusus sync
4. script Python/Odbc di komputer Access
5. state SQLite untuk delta sync
6. scheduler Windows
7. logging file per jenis sync
8. preflight verification sebelum install task

## Kondisi Project `web-bengkel` Saat Ini

Yang sudah ada:

1. uploader manual via panel `Access Sync`
2. preview data
3. simpan ke staging
4. merge master ke tabel operasional
5. konsolidasi transaksi ke tabel laporan
6. laporan konsolidasi di panel

Yang belum ada:

1. endpoint API untuk auto sync machine-to-server
2. script Python pembaca `.mdb`
3. state SQLite untuk delta sync
4. task scheduler installer
5. verifikasi otomatis environment sync
6. auto push berkala tanpa upload manual

## Planning Implementasi Sync Otomatis untuk Project Ini

### Fase 1 - Finalisasi Desain Sync

Target:
- menyepakati pola yang akan dipakai

Pekerjaan:

1. Pisahkan scope menjadi:
   - `master_sync`
   - `transaction_sync`
   - `service_draft_sync`
2. Tentukan source Access per flow:
   - `FITMOTOR GABUNG.mdb` untuk master + transaksi gabungan
   - `FITMOTOR APP.mdb` untuk draft servis cepat
3. Tentukan tabel/query Access yang dipakai per dataset
4. Tentukan endpoint API target per dataset
5. Tentukan interval sync

Output:
- mapping final source -> endpoint -> target table

Estimasi:
- 2 sampai 3 hari

### Fase 2 - Bangun API Sync di `web-bengkel`

Target:
- web siap menerima sync otomatis tanpa browser

Pekerjaan:

1. Buat controller API khusus sync
2. Buat route seperti:
   - `/api/sync/master-data/{entity}`
   - `/api/sync/transactions/{entity}`
   - `/api/sync/quick-service-drafts`
3. Tambahkan validasi:
   - `POST only`
   - `X-Sync-Token`
   - validasi entity
4. Sambungkan endpoint ke service importer yang sudah ada
5. Tambahkan log request sync

Output:
- API sync siap dipanggil dari script luar

Estimasi:
- 3 sampai 4 hari

### Fase 3 - Ekstraktor Python untuk MDB

Target:
- ada script luar yang bisa baca file Access langsung

Pekerjaan:

1. Buat script `sync_access_master.py`
2. Buat script `sync_access_transactions.py`
3. Buat script `sync_access_service_drafts.py`
4. Tambahkan config `.env`/`.ini` untuk:
   - path MDB
   - password MDB
   - API URL
   - sync token
   - batch size
   - branch code
5. Tambahkan helper ODBC + serializer

Output:
- script sync lokal berjalan manual dari command line

Estimasi:
- 4 sampai 5 hari

### Fase 4 - Delta Sync dan State Lokal

Target:
- sync hanya kirim data yang berubah

Pekerjaan:

1. Buat SQLite state store lokal
2. Simpan:
   - dataset
   - source name
   - row key
   - row hash
   - updated_at
3. Tambahkan hash compare sebelum kirim
4. Tambahkan opsi force full sync bila diperlukan

Output:
- auto sync hemat bandwidth dan lebih cepat

Estimasi:
- 2 sampai 3 hari

### Fase 5 - Scheduler dan Bundle Operasional

Target:
- sync bisa jalan otomatis di komputer cabang/server

Pekerjaan:

1. Buat batch runner
2. Buat hidden launcher `.vbs`
3. Buat `install_task.bat`
4. Buat `uninstall_task.bat`
5. Buat `verify_sync_setup.py`
6. Buat template config:
   - pusat
   - cabang
   - no-link
7. Buat README instalasi

Output:
- bundle sync siap dipasang di komputer Access

Estimasi:
- 3 sampai 4 hari

### Fase 6 - Guard dan Aturan Overwrite

Target:
- data manual user tidak rusak karena auto sync

Pekerjaan:

1. Tambahkan aturan overwrite per dataset
2. Untuk draft service:
   - jangan overwrite SPK yang sudah diproses
   - jangan hapus item/keluhan manual
3. Untuk transaksi:
   - pakai upsert berdasar key yang jelas
   - simpan source run/source file date
4. Tambahkan audit log ringkas

Output:
- sync aman dipakai harian

Estimasi:
- 3 hari

### Fase 7 - Pilot Cabang

Target:
- test otomatis di 1 cabang dulu

Pekerjaan:

1. Pasang bundle di 1 komputer cabang/pusat
2. Jalankan:
   - test manual
   - test scheduler
   - test internet putus / retry
3. Validasi data yang masuk ke panel
4. Pantau log 3 sampai 5 hari

Output:
- bukti stabil sebelum rollout penuh

Estimasi:
- 5 sampai 7 hari kalender

## Urutan Implementasi yang Paling Aman

Urutan yang saya sarankan:

1. `master_sync` dulu
   - pelanggan
   - kendaraan
   - supplier
   - item
   - mekanik
   - member pelanggan
2. `transaction_sync` kedua
   - pembelian
   - penjualan
   - service header
3. `service_draft_sync` terakhir
   - karena ini paling sensitif terhadap workflow operasional

Alasannya:
- master lebih aman
- transaksi bisa masuk tabel konsolidasi dulu
- draft servis perlu guard paling ketat

## Rekomendasi Praktis

Untuk project ini, saya sarankan **jangan langsung membuat full auto sync semua dataset sekaligus**.

Strategi terbaik:

1. Tahap 1:
   - auto sync master data
2. Tahap 2:
   - auto sync transaksi ke tabel konsolidasi
3. Tahap 3:
   - auto sync draft servis cepat dari `FITMOTOR APP`

Dengan pola ini, risiko gangguan operasional lebih kecil.

## Estimasi Timeline Ringkas

Jika dikerjakan fokus, estimasi realistis:

1. Fase 1-2: 1 minggu
2. Fase 3-4: 1 minggu
3. Fase 5-6: 1 minggu
4. Fase 7 pilot: 1 minggu

Total:
- sekitar `4 minggu` untuk versi auto sync yang sudah layak pilot

Jika hanya sampai:
- `master auto sync + transaksi konsolidasi auto sync`

maka bisa dipersingkat menjadi:
- sekitar `2 sampai 3 minggu`

## Kesimpulan

Alur sync otomatis di `PWA_pelanggan` sudah cukup jelas dan bisa dijadikan referensi langsung.

Pola yang dipakai adalah:

1. script lokal baca Access
2. scheduler menjalankan script
3. script kirim batch JSON ke API web
4. API web import data
5. state lokal menyaring perubahan

Untuk project `web-bengkel`, pola ini sangat memungkinkan diterapkan, dan akan jauh lebih kuat dibanding hanya mengandalkan upload manual lewat panel.
