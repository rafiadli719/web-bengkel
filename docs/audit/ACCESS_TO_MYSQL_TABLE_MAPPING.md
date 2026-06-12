# Mapping Tabel Access ke MySQL

## Tujuan

Dokumen ini memetakan tabel prioritas dari `FITMOTOR GABUNG.mdb` dan `FITMOTOR APP.mdb` ke database MySQL yang dipakai project web saat ini.

Target utama:

- menentukan tabel yang sudah punya padanan langsung
- menentukan field yang masih parsial
- menentukan kebutuhan tabel staging dan konsolidasi baru

## Status Mapping

### 1. Pembelian Header

Access source:

- `TBLPembelianHeader_CIKDITIRO`
- `GABUNG_PEMBELIAN_DATA`

MySQL target:

- `tblpembelian_header`

Status:

- `existing`

Field inti yang sudah match:

- `NoTransaksi` -> `notransaksi`
- `Status` -> `status`
- `CaraBayar` -> `carabayar`
- `Tanggal` -> `tanggal`
- `NoOrder` -> `no_order`
- `TanggalOrder` -> `tanggal_order`
- `NoSupplier` -> `no_supplier`
- `Note` -> `note`
- `TotalQtyOrder` -> `total_qty_order`
- `TotalQty` -> `total_qty`
- `TotalBeli` -> `total_beli`
- `Diskon` -> `diskon`
- `TotalDiskon` -> `total_diskon`
- `Pajak` -> `pajak`
- `TotalPajak` -> `total_pajak`
- `TotalAkhir` -> `total_akhir`
- `TotalRetur` -> `total_retur`
- `Pembayaran` -> `pembayaran`
- `TanggalJT` -> `tanggal_jt`
- `TanggalLunas` -> `tanggal_lunas`
- `JumlahBayar` -> `jumlah_bayar`
- `User` -> `user`
- `IDTabel` -> `Id_tabel`

Field tambahan di MySQL:

- `kd_cabang`
- `status_lunas`
- `lama_hari`
- `no_do`
- `no_pr`
- `status_qc`
- `qc_by`
- `qc_date`
- `qc_notes`

Kesimpulan:

- pembelian header sudah sangat dekat dan bisa langsung diisi dari staging Access

### 2. Pembelian Detail

Access source:

- `TBLPembelianDetail_CIKDITIRO`
- `GABUNG_PEMBELIAN_DATA`

MySQL target:

- `tblpembelian_detail`

Status:

- `existing`

Field inti yang sudah match:

- `NoTransaksi` -> `no_transaksi`
- `NoBaris` -> `nobaris`
- `NoItem` -> `no_item`
- `QtyOrder` -> `qty_order`
- `Quantity` -> `quantity`
- `QtyRetur` -> `qty_retur`
- `HargaPokok` -> `harga_pokok`
- `Potongan` -> `potongan`
- `HargaSP` -> `harga_sp`
- `Total` -> `total`
- `StsOrder` -> `sts_order`
- `IDInv` -> `id_inv`

Field tambahan di MySQL:

- `id`
- `user`
- `kd_cabang`
- `status_trx`

### 3. Penjualan Header

Access source:

- `TBLPenjualanHeader_CIKDITIRO`
- `GABUNG_PENJUALAN`

MySQL target:

- `tblpenjualan_header`

Status:

- `existing`

Field inti yang sudah match:

- `NoTransaksi` -> `notransaksi`
- `Status` -> `status`
- `CaraBayar` -> `carabayar`
- `Tanggal` -> `tanggal`
- `NoOrder` -> `no_order`
- `TanggalOrder` -> `tanggal_order`
- `NoSales` -> `no_sales`
- `NoPelanggan` -> `no_pelanggan`
- `Note` -> `note`
- `TotalQtyOrder` -> `total_qty_order`
- `TotalQty` -> `total_qty`
- `TotalJual` -> `total_jual`
- `Diskon` -> `diskon`
- `TotalDiskon` -> `total_diskon`
- `Pajak` -> `pajak`
- `TotalPajak` -> `total_pajak`
- `TotalAkhir` -> `total_akhir`
- `TotalRetur` -> `total_retur`
- `Pembayaran` -> `pembayaran`
- `TanggalJT` -> `tanggal_jt`
- `TanggalLunas` -> `tanggal_lunas`
- `JumlahBayar` -> `jumlah_bayar`
- `User` -> `user`
- `IDTabel` -> `id_tabel`

Field tambahan di MySQL:

- `kd_cabang`
- `status_lunas`
- `lama_hari`

Catatan:

- query `GABUNG_PENJUALAN` sempat terkunci saat baca sample data karena MDB sedang dipakai user Access
- metadata kolom tetap berhasil dibaca dan cukup untuk mapping awal

### 4. Penjualan Detail

Access source:

- `TBLPenjualanDetail_CIKDITIRO`
- `GABUNG_PENJUALAN`

MySQL target:

- `tblpenjualan_detail`

Status:

- `existing`

Field inti yang sudah match:

- `NoTransaksi` -> `no_transaksi`
- `NoBaris` -> `nobaris`
- `NoItem` -> `no_item`
- `QtyOrder` -> `qty_order`
- `Quantity` -> `quantity`
- `QtyRetur` -> `qty_retur`
- `HargaJual` -> `harga_jual`
- `Potongan` -> `potongan`
- `HargaSP` -> `harga_sp`
- `HargaPokok` -> `harga_pokok`
- `Total` -> `total`
- `StsOrder` -> `sts_order`

Field tambahan di MySQL:

- `id`
- `user`
- `kd_cabang`
- `status_trx`

### 5. Service Header

Access source:

- `TBLService_CIKDITIRO`
- `GABUNG_SERVICE_HEADER_DATA`

MySQL target:

- `tblservice`

Status:

- `partial but strong`

Field inti yang sudah match:

- `NoService` -> `no_service`
- `Tanggal` -> `tanggal`
- `NoPelanggan` -> `no_pelanggan`
- `NoPolisi` -> `no_polisi`
- `Mekanik1` -> `mekanik1`
- `Mekanik2` -> `mekanik2`
- `Mekanik3` -> `mekanik3`
- `Mekanik4` -> `mekanik4`
- `BiayaM1` -> `biayaM1`
- `BiayaM2` -> `biayaM2`
- `BiayaM3` -> `biayaM3`
- `BiayaM4` -> `biayaM4`
- `KmSekarang` -> `km_skr`
- `KmBerikut` -> `km_berikut`
- `TotalWaktu` -> `total_waktu`
- `Status` -> `status`
- `Keterangan` -> `keterangan`
- `SubTotalJasa` -> `subtotal_jasa`
- `SubTotalItem` -> `subtotal_item`
- `SubTotal` -> `subtotal`
- `Diskon` -> `diskon_persen` atau `diskon_nom` perlu aturan mapping
- `TotalDiskon` -> `total_diskon`
- `Pajak` -> `ppn_persen` atau `ppn_nom` perlu aturan mapping
- `TotalPajak` -> `total_pajak`
- `TotalAkhir` -> `total_akhir`
- `Pembayaran` -> `bayar`
- `User` -> `id_user` atau `admin/user field` perlu lookup
- `IDTabel` -> belum ada padanan langsung, cocok untuk staging/reference

Field tambahan di MySQL:

- `jam`
- `kd_cabang`
- `status_servis`
- `status_jemput`
- `tarif_jemput`
- `kepala_mekanik*`
- `admin*`
- `persen_*`
- `metode_pembayaran`
- `bukti_pembayaran`
- `cancel fields`
- `temuan / penawaran summary`

Kesimpulan:

- service adalah area paling penting untuk migrasi parsial
- butuh mapping bisnis tambahan, bukan sekadar copy kolom

### 6. Service Advisor

Access source:

- `TBLService_Advisor_CIKDITIRO`

MySQL target:

- `tblservice_advisor`

Status:

- `existing`

Field yang match:

- `NoService` -> `no_service`
- `Advisor` -> `advisor`

### 7. Pelanggan

Access source:

- `TBLPelanggan_CIKDITIRO`

MySQL target:

- `tblpelanggan`

Status:

- `existing`

Field inti yang sudah match:

- `NoPelanggan` -> `nopelanggan`
- `NamaPelanggan` -> `namapelanggan`
- `Alamat` -> `alamat`
- `Kota` -> `kota`
- `Propinsi` -> `propinsi`
- `KodePost` -> `kodepost`
- `Negara` -> `negara`
- `Telephone` -> `telephone`
- `Fax` -> `fax`
- `KontakPerson` -> `kontakperson`
- `Note` -> `note`
- `Potongan` -> `potongan`
- `TipePot` -> `tipepot`
- `LavelHarga` -> `lavelharga`
- `KGrup` -> `kgrup`

Field tambahan di MySQL:

- `notlp`
- `patokan`
- `foto_tampak_rumah`
- `link_gmaps`
- `panggilan`
- `saldoawal`
- `tgllahir`
- `gender`
- `informasi_sumber`

### 8. Kendaraan Pelanggan

Access source:

- `KENDARAAN_PELANGGAN`
- `KENDARAAN_PELANGGAN_GABUNG_DATA`

MySQL target:

- `tblkendaraan`
- `view_pelanggan_kendaraan`

Status:

- `partial`

Field yang cukup dekat:

- `NoPolisi` -> `nopolisi`
- `NamaPelanggan` -> `pemilik` atau lookup ke pelanggan
- `Tipe` -> `tipe`
- `Jenis` -> `jenis`
- `Warna` -> `warna`
- `TahunBuat` -> `tahun_buat`
- `Alamat` -> `alamat`
- `Note` -> `note`

Gap:

- Access punya bentuk view gabungan pelanggan-kendaraan yang lebih kaya untuk histori
- MySQL butuh tabel/report gabungan khusus agar setara

### 9. Supplier

Access source:

- `TBLSupplier_CIKDITIRO`
- `GABUNG_TBLSUPPLIER_DATA`

MySQL target:

- `tblsupplier`

Status:

- `existing`

Field inti yang sudah match:

- `NoSupplier` -> `nosupplier`
- `NamaSupplier` -> `namasupplier`
- `Alamat` -> `alamat`
- `Kota` -> `kota`
- `Propinsi` -> `propinsi`
- `KodePost` -> `kodepost`
- `Negara` -> `negara`
- `Telephone` -> `telephone`
- `Fax` -> `fax`
- `NamaBank` -> `namabank`
- `NoAccount` -> `noaccount`
- `AtasNama` -> `atasnama`
- `KontakPerson` -> `kontakperson`
- `Email` -> `email`
- `Note` -> `note`
- `SaldoAwal` -> `saldoawal`
- `PerTanggal` -> `pertanggal`
- `JmlBayar` -> `jmlbayar`
- `Sisa` -> `sisa`

Field tambahan di MySQL:

- `tipe_pemasok`
- `no_whatsapp`
- `kd_cabang`
- `lama_hari_kirim`
- `jangka_waktu_kredit`
- `accurate_id`

### 10. Mekanik

Access source:

- `TBLMekanik_CIKDITIRO`
- `GABUNG_TBLMEKANIK_DATA`

MySQL target:

- `tblmekanik`
- `tbuser_karyawan`

Status:

- `partial`

Field yang match:

- `NoMekanik` -> `nomekanik`
- `Nama` -> `nama`
- `Alamat` -> `alamat`
- `NoTelepon` -> `telp`
- `Keahlian` -> `keahlian`

Field tambahan di MySQL:

- `status`
- `email`
- `tanggal_masuk`
- `gaji_pokok`
- `spesialisasi`
- `sertifikat`

### 11. Cabang

Access source:

- `TBLCabang`

MySQL target:

- `tbcabang`

Status:

- `existing`

Field yang match:

- `KODE` -> `kode_cabang`
- `CABANG` -> `nama_cabang`

Field tambahan di MySQL:

- `perusahaan_id`
- `alamat_cabang`
- `google_maps_cabang`
- `lat_cabang`
- `long_cabang`
- `tipe_cabang`

## Tabel Baru Yang Disarankan

Perlu dibuat khusus untuk migrasi/sync:

- `stg_access_gabung_pembelian`
- `stg_access_gabung_penjualan`
- `stg_access_gabung_service`
- `stg_access_gabung_item`
- `stg_access_gabung_supplier`
- `stg_access_gabung_mekanik`
- `stg_access_gabung_kendaraan_pelanggan`
- `sync_access_runs`
- `sync_access_row_errors`

## Kesimpulan

- pembelian, penjualan, pelanggan, supplier, cabang, dan advisor sudah punya padanan kuat
- service, kendaraan pelanggan, dan mekanik butuh mapping bisnis tambahan
- `FITMOTOR GABUNG` paling cocok masuk lewat layer staging + sync, bukan insert langsung ke tabel transaksi utama
