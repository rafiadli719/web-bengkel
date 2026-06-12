# Rencana Implementasi Fitur Access ke Project PHP

## Tujuan

Menerjemahkan fitur penting dari:

- `E:\BENGKEL 2.0\FITMOTOR APP.mdb`
- `E:\BENGKEL 2.0\FITMOTOR GABUNG.mdb`

ke aplikasi web PHP yang saat ini terpusat di `/_admincab` dan diakses via `/panel/`.

## Ringkasan Kelayakan

### Sangat Layak Diimplementasikan

- master item, pelanggan, supplier, cabang, mekanik
- pembelian, penjualan, hutang, piutang
- servis reguler dan tracking pengerjaan
- histori kendaraan dan histori servis
- loyalty/member dan diskon item/member
- laporan gabungan lintas cabang

### Layak Tapi Butuh Desain Ulang

- insentif jual servis admin/advisor/mekanik
- reminder jadwal servis
- analisa lead time supplier dan pembelian per item
- dashboard agregasi gabungan

### Tidak Disarankan Menyalin 1:1

- query/view Access yang sangat procedural atau bergantung linked table Access
- form/macro VBA yang hanya cocok di desktop Access

## Temuan Fitur Utama Dari Access

### FITMOTOR APP.mdb

Indikasi kuat fitur operasional cabang:

- `TBLItem`
- `TBLPelanggan`
- `TBLService`
- `TBLHutangHeader`
- `TBLCabang`
- `DATA_MEMBER`
- `DAFTAR_PENGERJAAN_SERVIS`
- `HARGA_JUAL_PLUSJASA`
- `CUCI_MOTOR`
- `BAGI_HASIL`
- `REMINDER_JADWAL_SERVIS`

### FITMOTOR GABUNG.mdb

Indikasi kuat fitur konsolidasi dan analitik:

- `GABUNG_PEMBELIAN_*`
- `GABUNG_PENJUALAN_*`
- `GABUNG_SERVICE_HEADER_DATA`
- `GABUNG_TBLITEM_PERBENGKEL_DATA`
- `GABUNG_TBLSERVICE_ADVISOR_DATA`
- `KENDARAAN_PELANGGAN_*`
- `INSENTIF_JUAL_SERVIS_*`
- `BELI_PERITEM_PERSUPLIER_*`
- `CEK_GRATIS_OLI`
- `CEK_NOMOR_WA_TERAKHIR_DATANG`

## Mapping Dengan Project Saat Ini

### Sudah Ada Pondasi di `/_admincab`

- barang / item
- pelanggan
- supplier
- cabang
- mekanik
- pembelian
- penjualan
- hutang / piutang
- servis reguler
- servis jemput
- tracking keluhan
- member / loyalty dasar
- diskon item/member

### Gap Yang Paling Mungkin Belum Lengkap

- report gabungan semua cabang
- dashboard konsolidasi pembelian/penjualan/servis
- insentif advisor/admin/mekanik
- reminder jadwal servis
- analisa pembelian per item dan lead time supplier
- beberapa query bisnis gabungan kendaraan pelanggan

## Prioritas Implementasi

### Fase 1: Konsolidasi Data Gabungan

Target:

- replikasi output `GABUNG_PEMBELIAN_*`
- replikasi output `GABUNG_PENJUALAN_*`
- replikasi output `GABUNG_SERVICE_HEADER_DATA`

Output web:

- menu laporan gabungan cabang
- filter per cabang / periode / advisor / mekanik
- export Excel/PDF

Kebutuhan teknis:

- normalisasi kode cabang
- query union lintas cabang atau tabel terpusat
- indeks untuk tanggal, no transaksi, no service, kode cabang

### Fase 2: Histori Pelanggan & Kendaraan

Target:

- replikasi `KENDARAAN_PELANGGAN_*`
- riwayat servis lintas cabang
- riwayat kedatangan, nomor WA, reminder

Output web:

- halaman profil pelanggan komprehensif
- timeline kendaraan
- summary service terakhir dan service berikutnya

### Fase 3: Reminder Servis

Target:

- replikasi logika `REMINDER_JADWAL_SERVIS`
- cek pelanggan yang harus di-follow up

Output web:

- daftar follow-up servis
- status sudah dihubungi / belum
- integrasi WA blast/manual reminder

### Fase 4: Insentif Jual Servis

Target:

- replikasi `INSENTIF_JUAL_SERVIS_*`

Output web:

- rekap insentif advisor
- rekap insentif admin
- rekap insentif mekanik
- laporan per siklus / per tanggal / per cabang

Catatan:

- fase ini butuh validasi rumus bisnis dengan user

### Fase 5: Analitik Pembelian Per Item

Target:

- replikasi `BELI_PERITEM_PERSUPLIER_*`
- cek harga beli terakhir / kedua terakhir / ketiga terakhir
- lead time supplier

Output web:

- panel evaluasi harga beli
- rekomendasi supplier
- histori pembelian item

## Cara Implementasi Yang Disarankan

1. Jangan migrasikan VBA/form Access apa adanya.
2. Ambil logika bisnis dari tabel/query Access.
3. Ubah menjadi:
   - query MySQL
   - helper/service PHP
   - halaman report dan dashboard web
4. Validasi tiap fase dengan user operasional sebelum lanjut.

## Langkah Eksekusi Praktis

1. Dump metadata dan sample data dari objek Access yang jadi prioritas.
2. Cocokkan field Access dengan tabel MySQL yang sekarang.
3. Tandai:
   - `existing`
   - `partial`
   - `missing`
4. Bangun 1 fase dulu sampai selesai dan dites.
5. Baru lanjut ke fase berikutnya.

## Rekomendasi Mulai Dari Mana

Urutan paling bernilai:

1. laporan gabungan cabang
2. histori kendaraan/pelanggan
3. reminder servis
4. insentif jual servis
5. analitik pembelian per item
