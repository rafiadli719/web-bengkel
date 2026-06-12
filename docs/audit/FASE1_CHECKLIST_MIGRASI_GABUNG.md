# Checklist Fase 1 Migrasi FITMOTOR GABUNG

## Tujuan Fase 1

Menyiapkan fondasi teknis agar data dari Access bisa mulai masuk ke aplikasi web melalui jalur staging, uploader, dan sync yang terkontrol.

## Checklist Analisis

- [x] identifikasi file sumber Access
- [x] identifikasi tabel prioritas
- [x] mapping awal Access ke MySQL
- [x] identifikasi tabel `existing`, `partial`, dan `missing`
- [x] draft struktur tabel staging
- [ ] final review nama field dan tipe data per dataset

## Checklist Database

- [x] draft SQL untuk `sync_access_runs`
- [x] draft SQL untuk `sync_access_row_errors`
- [x] draft SQL untuk staging pembelian
- [x] draft SQL untuk staging penjualan
- [x] draft SQL untuk staging service
- [x] draft SQL untuk staging item
- [x] draft SQL untuk staging supplier
- [x] draft SQL untuk staging mekanik
- [x] draft SQL untuk staging kendaraan pelanggan
- [ ] eksekusi SQL di database lokal
- [ ] validasi index dan ukuran field

## Checklist Uploader

- [ ] buat halaman `access-sync.php`
- [ ] buat form upload dataset
- [ ] buat validasi extension file
- [ ] buat validasi ukuran file
- [ ] simpan file upload ke folder sementara yang aman
- [ ] simpan log run ke `sync_access_runs`

## Checklist Parser

- [ ] parser pembelian
- [ ] parser penjualan
- [ ] parser service
- [ ] parser pelanggan dan kendaraan
- [ ] parser supplier
- [ ] parser mekanik
- [ ] simpan payload mentah untuk audit

## Checklist Sync

- [ ] normalisasi kode cabang
- [ ] normalisasi supplier
- [ ] normalisasi pelanggan
- [ ] normalisasi kendaraan
- [ ] merge pembelian ke target
- [ ] merge penjualan ke target
- [ ] merge service ke target
- [ ] pencatatan row gagal
- [ ] ringkasan hasil sync

## Checklist Testing

- [ ] upload sample file pembelian
- [ ] upload sample file penjualan
- [ ] upload sample file service
- [ ] cek hasil staging
- [ ] cek hasil error logging
- [ ] cek merge tidak membuat duplikasi liar
- [ ] cek histori sync tampil di UI

## Deliverable Minimum

- SQL staging siap pakai
- dokumen mapping tabel
- blueprint uploader dan sync
- checklist implementasi fase 1

## Catatan Prioritas

Prioritas implementasi:

1. pembelian
2. penjualan
3. service
4. pelanggan dan kendaraan
5. supplier dan mekanik
