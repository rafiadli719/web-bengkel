# Timeline Migrasi FITMOTOR GABUNG dan Implementasi Fitur

## Tujuan

Dokumen ini menjadi pengajuan timeline kerja yang lebih ringkas untuk:

1. migrasi tabel penting dari `FITMOTOR GABUNG.mdb`
2. penyiapan mekanisme `sync` dan `uploader`
3. implementasi fitur prioritas yang belum lengkap di project web

Tanggal acuan timeline:

- **7 Juni 2026**

## Ringkasan Pengajuan

Estimasi yang lebih realistis dan padat:

- **8 minggu pengerjaan inti**
- **1 minggu buffer/stabilisasi**

Total pengajuan aman:

- **9 minggu**

Periode estimasi:

- **Mulai:** 8 Juni 2026
- **Target selesai inti:** 2 Agustus 2026
- **Target selesai dengan buffer:** 9 Agustus 2026

## Timeline Singkat Per Fase

### Minggu 1. Mapping dan Desain Migrasi

Fokus:

- mapping tabel Access ke MySQL
- identifikasi field `existing`, `partial`, `missing`
- tentukan tabel prioritas dari `FITMOTOR GABUNG`

Output:

- matriks mapping tabel
- daftar kebutuhan tabel staging/konsolidasi

### Minggu 2. Struktur Tabel Gabungan + Staging

Fokus:

- buat tabel konsolidasi
- buat tabel staging import
- normalisasi kode cabang, user, transaksi, service

Output:

- skema migrasi siap pakai
- struktur database untuk data gabungan

### Minggu 3. Uploader dan Importer Data

Fokus:

- buat mekanisme uploader hasil ekstraksi data
- buat importer dari dump/export Access ke MySQL
- logging hasil import

Output:

- modul uploader data gabungan
- importer data awal
- log validasi upload/import

### Minggu 4. Sync Data Gabungan

Fokus:

- buat proses `sync` data berkala dari sumber gabungan
- validasi insert/update duplicate-safe
- siapkan monitoring sync

Output:

- modul sync data
- status sync dan log hasil sinkronisasi

### Minggu 5. Laporan Gabungan Cabang

Fokus:

- laporan gabungan pembelian
- laporan gabungan penjualan
- laporan gabungan service

Output:

- menu laporan gabungan cabang
- filter cabang/periode
- export Excel/PDF

### Minggu 6. Histori Pelanggan dan Kendaraan

Fokus:

- histori service lintas cabang
- histori kendaraan pelanggan
- summary kunjungan terakhir dan potensi follow-up

Output:

- halaman histori pelanggan/kendaraan

### Minggu 7. Reminder Servis + CRM Follow Up

Fokus:

- reminder jadwal servis
- daftar pelanggan yang harus di-follow up
- status kontak / WA reminder

Output:

- dashboard reminder servis
- dasar integrasi WA follow-up

### Minggu 8. Insentif dan Analitik Pembelian

Fokus:

- rekap insentif advisor/admin/mekanik
- analisa pembelian per item
- lead time supplier dan histori harga beli

Output:

- laporan insentif awal
- panel analitik pembelian item

### Minggu 9. Buffer dan Stabilisasi

Fokus:

- bugfix
- validasi user
- penyesuaian performa query
- koreksi mismatch hasil migrasi atau sync

Output:

- sistem lebih stabil untuk dipakai operasional

## Prioritas Implementasi

### Prioritas 1

- migrasi tabel gabungan
- uploader data
- sync data
- laporan gabungan cabang

### Prioritas 2

- histori pelanggan/kendaraan
- reminder servis

### Prioritas 3

- insentif
- analitik pembelian per item

## Catatan Pengajuan

Timeline ini sudah dipersingkat dari versi awal yang lebih panjang.

Supaya tetap realistis, pemadatan dilakukan dengan asumsi:

- fokus hanya pada fitur prioritas
- tidak semua query/report Access dipindahkan 1:1
- fitur desktop/VBA akan diterjemahkan ke versi web yang lebih sederhana

## Quick Win Yang Bisa Dijanjikan

Hasil paling cepat yang bisa mulai terlihat:

- **akhir minggu 4:** uploader + sync data awal
- **akhir minggu 5:** laporan gabungan cabang mulai bisa dipakai
- **akhir minggu 7:** reminder servis dan histori pelanggan mulai terlihat manfaatnya
