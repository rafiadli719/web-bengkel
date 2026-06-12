# Blueprint Uploader dan Sync FITMOTOR GABUNG

## Tujuan

Dokumen ini menjadi rancangan teknis awal untuk membawa data dari `FITMOTOR GABUNG.mdb` ke aplikasi web berbasis MySQL secara aman, bertahap, dan bisa diaudit.

Target utama:

- ada jalur `upload manual` untuk file export
- ada jalur `sync terjadwal` untuk konsolidasi data
- data tidak langsung ditulis ke tabel operasional tanpa lapisan validasi
- setiap proses punya log, status, dan pencatatan error per baris

## Arsitektur yang Disarankan

Alur data:

1. Access export atau query hasil konsolidasi
2. file masuk ke uploader web
3. file dibaca ke tabel staging `stg_access_*`
4. proses validasi field wajib dan format
5. proses normalisasi kode cabang, supplier, pelanggan, item, dan kendaraan
6. proses merge ke tabel target atau tabel konsolidasi
7. hasil sync dicatat ke `sync_access_runs` dan `sync_access_row_errors`

## Mode Integrasi

### 1. Manual Upload

Dipakai saat:

- user ekspor data dari Access ke CSV atau Excel
- operator upload file lewat panel admin
- cocok untuk fase awal implementasi

Kelebihan:

- cepat dibuat
- aman untuk tahap validasi
- mudah ditelusuri bila ada data gagal

Kekurangan:

- belum realtime
- masih bergantung pada user operasional

### 2. Scheduled Sync

Dipakai saat:

- sumber data gabungan sudah stabil
- format export sudah konsisten
- proses merge sudah lolos validasi dari mode manual

Kelebihan:

- lebih otomatis
- lebih konsisten untuk laporan gabungan

Kekurangan:

- butuh disiplin format source
- perlu strategi retry dan monitoring

## Dataset Prioritas MVP

### Gelombang 1

- pembelian header + detail
- penjualan header + detail
- service header

Alasan:

- dampak bisnis paling cepat terlihat
- langsung berguna untuk laporan gabungan cabang

### Gelombang 2

- pelanggan
- kendaraan pelanggan
- supplier
- mekanik

Alasan:

- penting untuk histori dan analitik lintas cabang
- menjadi pondasi dashboard lanjutan

### Gelombang 3

- item per bengkel
- insentif jual servis
- reminder servis

Alasan:

- nilainya tinggi, tetapi butuh data inti yang sudah bersih

## Komponen yang Perlu Dibuat

### 1. Menu Uploader

Lokasi yang disarankan:

- `/panel/access-sync.php`

Fitur minimum:

- pilih dataset
- pilih file upload
- pilih mode `replace`, `append`, atau `preview only`
- tombol upload
- tabel histori sync
- tombol lihat detail error

### 2. Service Import Parser

Tugas:

- membaca CSV atau Excel hasil export
- memetakan header Access ke field staging
- mengisi `sync_run_id`
- menyimpan payload mentah ke `raw_payload`

### 3. Validator

Tugas:

- cek field wajib
- cek format tanggal dan numerik
- cek relasi dasar seperti `no_supplier`, `no_pelanggan`, `no_polisi`
- catat baris gagal ke `sync_access_row_errors`

### 4. Sync Processor

Tugas:

- normalisasi data
- deduplikasi berdasarkan transaksi dan cabang
- merge ke target
- update status run

## Strategi Merge

### Pembelian

Kunci minimum:

- `kd_cabang`
- `no_transaksi`
- `no_item`

Aturan:

- bila header transaksi sudah ada, update nilai total dan tanggal bila source lebih baru
- bila detail item sudah ada, update qty dan nilai bila source lebih baru

### Penjualan

Kunci minimum:

- `kd_cabang`
- `no_transaksi`
- `no_item`

Aturan:

- mirip pembelian
- perlu perhatian pada retur dan status lunas

### Service

Kunci minimum:

- `kd_cabang`
- `no_service`

Aturan:

- update hanya bila record source lebih baru
- simpan status servis dan nilai total akhir
- histori detail servis bisa menjadi fase lanjutan jika dibutuhkan

## Aturan Audit dan Keamanan

- file upload harus dibatasi extension dan size
- semua proses upload wajib cek session dan role
- simpan nama file source, user upload, waktu upload, dan status proses
- semua query insert atau update gunakan prepared statement
- error detail jangan langsung ditampilkan mentah ke UI

## Acceptance Criteria Fase 1

- uploader bisa menerima file pembelian, penjualan, dan service
- data masuk ke tabel staging tanpa merusak tabel operasional
- error row bisa dilihat per run
- ada satu proses sync sukses dari data sample ke staging
- ada satu laporan ringkas jumlah row sukses dan gagal

## Rekomendasi Eksekusi

Urutan implementasi yang paling aman:

1. jalankan SQL tabel staging
2. buat menu uploader
3. buat parser untuk pembelian
4. buat parser untuk penjualan
5. buat parser untuk service
6. buat validasi dan log error
7. buat sync processor dasar
8. buat histori run dan tampilan monitoring
