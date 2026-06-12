# Mapping Teknis Fase 1 Sync Otomatis FITMOTOR GABUNG

Tanggal: 2026-06-09

## Tujuan Fase 1

Fase 1 fokus ke:

1. master data prioritas
2. transaksi prioritas
3. fondasi endpoint auto sync
4. source query/tabel dari `FITMOTOR GABUNG.mdb`

Tujuan utamanya bukan langsung memindahkan semua fitur Access, tetapi memastikan data utama lintas cabang bisa masuk otomatis dan aman ke project web.

## Prinsip Arsitektur

Pola yang dipakai:

1. komputer yang punya file `FITMOTOR GABUNG.mdb` membaca data lewat ODBC
2. script sync mengirim batch JSON ke API project web
3. API project web memproses data ke staging / master / konsolidasi
4. user memantau hasilnya dari panel

## Endpoint Yang Perlu Dibuat

### A. Endpoint Master Data

1. `POST /api/sync/master-data/cabang`
   - untuk sinkron master cabang
   - target utama: `tbcabang`

2. `POST /api/sync/master-data/customers`
   - untuk master pelanggan gabungan
   - target utama: staging pelanggan lalu `tblpelanggan`

3. `POST /api/sync/master-data/vehicles`
   - untuk master kendaraan gabungan
   - target utama: staging kendaraan lalu `tblkendaraan`

4. `POST /api/sync/master-data/vehicles-fill`
   - untuk enrichment kendaraan/pelanggan yang masih kosong
   - target utama: update field kosong saja

5. `POST /api/sync/master-data/items-master`
   - untuk master item utama
   - target utama: staging item lalu `tblitem`

6. `POST /api/sync/master-data/item-statuses`
   - untuk sinkron status nonstok / status item khusus
   - target utama: update field status item

7. `POST /api/sync/master-data/suppliers`
   - untuk master supplier
   - target utama: staging supplier lalu `tblsupplier`

8. `POST /api/sync/master-data/mechanics`
   - untuk master mekanik
   - target utama: staging mekanik lalu `tbuser_karyawan` / `tblmekanik`

9. `POST /api/sync/master-data/customer-members`
   - untuk kategori member pelanggan
   - target utama: `tblpelanggan.kgrup` dan `tblpelanggangrup`

### B. Endpoint Transaksi

1. `POST /api/sync/transactions/pembelian`
   - target: `stg_access_gabung_pembelian`
   - lanjut ke `konsolidasi_pembelian_header`
   - lanjut ke `konsolidasi_pembelian_detail`

2. `POST /api/sync/transactions/penjualan`
   - target: `stg_access_gabung_penjualan`
   - lanjut ke `konsolidasi_penjualan_header`
   - lanjut ke `konsolidasi_penjualan_detail`

3. `POST /api/sync/transactions/service`
   - target: `stg_access_gabung_service`
   - lanjut ke `konsolidasi_service`

### C. Endpoint Kontrol / Operasional

1. `POST /api/sync/preflight`
   - untuk cek token, endpoint, dan basic readiness

2. `POST /api/sync/report-run`
   - opsional
   - untuk mencatat log eksekusi machine sync

3. `POST /api/sync/merge/master/{dataset}`
   - opsional
   - jika merge ingin dipicu dari machine, bukan hanya panel

4. `POST /api/sync/merge/transactions/{dataset}`
   - opsional
   - jika konsolidasi transaksi ingin dipicu otomatis

## Source Query/Tabel Yang Dipakai dari FITMOTOR GABUNG

Catatan:
- fase 1 sebisa mungkin memakai source gabungan lintas cabang
- hindari baca tabel per cabang satu per satu jika sudah ada query gabung yang stabil

### A. Master Cabang

Source utama:

- `TBLCABANG`

Field inti:

- `KODE`
- `CABANG`

Target web:

- `tbcabang`

Catatan:
- perlu mapping ke `kode_cabang` internal web
- perlu simpan juga `kode referensi resmi`

### B. Master Pelanggan

Source utama:

- `GABUNG_PELANGGAN`

Field inti:

- `NoPelanggan`
- `NamaPelanggan`
- `S.Telephone`
- `C.Telephone`
- `NO_HP`
- `CABANG`
- `MEMBER`

Fallback tambahan jika perlu:

- `TBLPelanggan`
- `TBLPelanggan_PESALAKAN`
- `TBLPelanggan_PACUL`
- `TBLPelanggan_CIKDITIRO`
- `TBLPelanggan_TRAYEMAN`
- `TBLPelanggan_PUSAT`

Target web:

- `stg_access_gabung_pelanggan`
- `tblpelanggan`

### C. Master Kendaraan

Source utama:

- `GABUNG_TBLKENDARAAN`

Field inti:

- `NoPolisi`
- `Pemilik`
- `Alamat`
- `Merek`
- `Tipe`
- `Jenis`
- `TahunBuat`
- `Warna`
- `STS`

Source pelengkap:

- `KENDARAAN_PELANGGAN_GABUNG_DATA`
  atau file export turunannya `KENDARAAN_PELANGGAN_GABUNG_TERBARU`

Target web:

- `stg_access_gabung_kendaraan_pelanggan`
- `tblkendaraan`

### D. Master Item

Source utama:

- `GABUNG_TBLITEM`

Field inti:

- `NOITEM`
- `NAMA`
- `JENIS`
- `RAKBARANG`

Source pelengkap harga/stok cabang:

- `GABUNG_TBLITEM_PERBENGKEL_DATA`
  atau export turunannya `GABUNG_TBLITEM_PERBENGKEL`

Source status nonstok:

- `GABUNG_TBLITEM_PERBENGKEL_NONSTOK`
- `GABUNG_TBLITEMNONSTOK`

Target web:

- `stg_access_gabung_item`
- `tblitem`
- `tblitem_stok`

### E. Master Supplier

Source utama:

- `GABUNG_TBLSUPPLIER_DATA`
  atau `GABUNG_TBLSUPPLIER`

Field inti:

- `NoSupplier`
- `NamaSupplier`
- `Alamat`
- `Kota`
- `Propinsi`
- `Telephone`

Target web:

- `stg_access_gabung_supplier`
- `tblsupplier`

### F. Master Mekanik

Source utama:

- `GABUNG_TBLMEKANIK_DATA`
  atau `GABUNG_TBLMEKANIK`

Field inti:

- `NoMekanik`
- `Nama`
- `Alamat`
- `NoTelepon`
- `Keahlian`

Target web:

- `stg_access_gabung_mekanik`
- `tbuser_karyawan`

### G. Member Pelanggan

Source utama:

- `TIPE_MEMBER`
- `UPDATE_TIPE_MEMBER`

Catatan:
- bila `FITMOTOR GABUNG` tidak cukup lengkap untuk histori member, bisa dipadukan dengan `DATA_MEMBER` dari `FITMOTOR APP`

Target web:

- `stg_access_gabung_member_pelanggan`
- `tblpelanggangrup`
- `tblpelanggan.kgrup`

### H. Transaksi Pembelian

Source utama:

- `GABUNG_PEMBELIAN_DATA`

Field inti:

- `NoTransaksi`
- `NoBaris`
- `Tanggal`
- `NoOrder`
- `NoSupplier`
- `NoItem`
- `Quantity`
- `HargaPokok`
- `Total`
- `STS`

Target web:

- `stg_access_gabung_pembelian`
- `konsolidasi_pembelian_header`
- `konsolidasi_pembelian_detail`

### I. Transaksi Penjualan

Source utama:

- `GABUNG_PENJUALAN`

Field inti:

- `NoTransaksi`
- `NoBaris`
- `Tanggal`
- `NoSales`
- `NoPelanggan`
- `NoItem`
- `Quantity`
- `HargaJual`
- `Total`
- `STS`

Target web:

- `stg_access_gabung_penjualan`
- `konsolidasi_penjualan_header`
- `konsolidasi_penjualan_detail`

### J. Transaksi Service

Source utama:

- `GABUNG_SERVICE_HEADER_DATA`

Jika nanti perlu pecah detail:

- `HISTORY_SERVIS_HEADER`
- `HISTORY_SERVIS_DETAIL`
- `GABUNG_SERVISJUAL_ALL`

Field inti:

- `NoService`
- `Tanggal`
- `NoPelanggan`
- `NoPolisi`
- `Mekanik1..4`
- `KmSekarang`
- `KmBerikut`
- `Status`
- `SubTotalJasa`
- `SubTotalItem`
- `TotalAkhir`
- `STS`

Target web:

- `stg_access_gabung_service`
- `konsolidasi_service`

## Mapping Cabang Yang Dipakai di Fase 1

Kode kanonis web:

- `PESALAKAN`
- `PACUL`
- `CIKDITIRO`
- `TRAYEMAN`
- `PST`

Kode referensi resmi `fitmotor_maintance-beta`:

- `201601001` -> `PESALAKAN`
- `201809001` -> `PACUL`
- `202201001` -> `CIKDITIRO`
- `202505001` -> `TRAYEMAN`
- `202601001` -> `PST`

Alias legacy yang harus dinormalisasi:

- `CAB001` -> `PST`
- `KODE_CABANG_SESI_AND` -> `PST`
- `001` -> `PST`
- `1` -> `PST`
- `0` -> `PST`
- `PES` -> `PESALAKAN`

## Urutan Implementasi Teknis

### Tahap 1A

1. endpoint `master-data/cabang`
2. endpoint `master-data/customers`
3. endpoint `master-data/vehicles`
4. endpoint `master-data/items-master`

### Tahap 1B

1. endpoint `master-data/suppliers`
2. endpoint `master-data/mechanics`
3. endpoint `master-data/customer-members`

### Tahap 1C

1. endpoint `transactions/pembelian`
2. endpoint `transactions/penjualan`
3. endpoint `transactions/service`

## Prioritas Paling Masuk Akal

Kalau dikerjakan bertahap, urutan terbaik:

1. cabang
2. pelanggan
3. kendaraan
4. item
5. supplier
6. mekanik
7. member pelanggan
8. pembelian
9. penjualan
10. service

## Catatan Penting

1. `FITMOTOR GABUNG` sudah mewakili banyak cabang, jadi sumber utamanya cocok untuk master dan laporan lintas cabang.
2. Untuk draft servis cepat real-time, `FITMOTOR APP` tetap lebih cocok daripada `FITMOTOR GABUNG`.
3. Untuk fase 1, transaksi sebaiknya masuk ke tabel staging dan konsolidasi dulu, jangan langsung overwrite tabel operasional utama.
4. Kode cabang harus dinormalisasi dulu sebelum merge agar data gabungan tidak pecah per alias.
