# Analisa Fitur Access vs Project PHP

Dokumen ini dibuat otomatis dari metadata MDB dan dibandingkan secara heuristik dengan modul `/_admincab`.

## FITMOTOR APP.mdb

### Ringkasan Objek
- `SYNONYM`: `112`
- `SYSTEM TABLE`: `13`
- `TABLE`: `31`
- `VIEW`: `189`
- Estimasi objek yang punya padanan nama di `/_admincab`: `164` dari `332`

### Entitas Inti Yang Terdeteksi
- `TBLItem`: NoItem, KodeBarCode, NamaItem, Jenis, Satuan, HargaPokok, HargaJual, HargaJual2, HargaJual3, HJQtyD2, HJQtyD3, HJQtyS1
- `TBLPelanggan`: NoPelanggan, NamaPelanggan, Alamat, Kota, Propinsi, KodePost, Negara, Telephone, Fax, KontakPerson, Note, Potongan
- `TBLService`: NoService, Tanggal, NoPelanggan, NoPolisi, Mekanik1, Mekanik2, Mekanik3, Mekanik4, BiayaM1, BiayaM2, BiayaM3, BiayaM4
- `TBLUser`: UserID, NamaUser, Password, HakAkses
- `TBLMekanik`: NoMekanik, Nama, Alamat, Kota, Provinsi, NoTelepon, Note, Keahlian
- `TBLSupplier`: NoSupplier, NamaSupplier, Alamat, Kota, Propinsi, KodePost, Negara, Telephone, Fax, NamaBank, NoAccount, AtasNama
- `TBLHutangHeader`: NoTransaksi, Tanggal, NoSupplier, Note, TotalBayar, User, IDTabel
- `TBLPiutangHeader`: NoTransaksi, Tanggal, NoPelanggan, Note, TotalBayar, User, IDTabel
- `TBLCabang`: KODE, CABANG

### Indikasi Cluster Fitur
- `service`: `33` objek. Contoh: TBLService, TBLService_Advisor, TBLService_CIKDITIRO, TBLSERVICE_HPPSTS, TBLService_JEMPUTANTAR, TBLService_PACUL, TBLService_PESALAKAN, TBLService_PUSAT
- `servis`: `42` objek. Contoh: REMINDER_JADWAL_SERVIS, ADMIN_SERVIS_KOSONG, DAFTAR_PENGERJAAN_SERVIS, MEKANIK_PERSERVIS_PERSEN_TEMP, BAHAN_HABIS_PAKAI_SERVIS, CEK_INPUT_GARANSI_SERVIS1, CEK_INPUT_GARANSI_SERVIS2, DATA_INSENTIF_SERVIS
- `item`: `61` objek. Contoh: TBLItem, TBLItem_CIKDITIRO, TBLItem_PACUL, TBLItem_PESALAKAN, TBLItem_PUSAT, TBLItem_TRAYEMAN, TBLItemFifo, TBLItemJenis
- `pelanggan`: `31` objek. Contoh: TBLPelanggan, TBLPelanggan_CIKDITIRO, TBLPelanggan_PACUL, TBLPelanggan_PESALAKAN, TBLPelanggan_PUSAT, TBLPelanggan_TRAYEMAN, TBLPelangganGrup, TBLPelangganGrup_CIKDITIRO
- `supplier`: `4` objek. Contoh: TBLSupplier, TBLSupplierSA, TBLSupplier_SUMBERDATA, GET_TBLSUPPLIER
- `hutang`: `4` objek. Contoh: TBLHutangDetail, TBLHutangHeader, QRYHutangDetail, QRYHutangHeader
- `piutang`: `6` objek. Contoh: TBLPiutangDetail, TBLPiutangHeader, PENJUALAN_ANTAR_CABANG_GETPIUTANG, PENJUALAN_ANTAR_CABANG_PIUTANG, QRYPiutangDetail, QRYPiutangHeader
- `stok`: `9` objek. Contoh: HASIL_STOK_OPNAM_TEMP, ITEM_KELUARMASUK_DETAIL_STOK_OPNAM, ITEM_KELUARMASUK_HEADER_STOK_OPNAM, Q_RPT_STOK_SISTEM_STOK_OPNAM, QRYStokHistory, REKAP_STOK_SISTEM_STOK_OPNAM, STOK_OPNAM_MINGGUAN, STOK_SISTEM_STOK_OPNAM
- `pembelian`: `12` objek. Contoh: TBLPembelianDetail, TBLPembelianHeader, CEK_PEMBELIAN, CEK_PEMBELIAN_CARI, CEK_PEMBELIAN_HRGJUAL, CEK_PEMBELIAN_HRGJUAL_SOURCE, CEK_PEMBELIAN_NAIK, CEK_PEMBELIAN_SOURCE
- `penjualan`: `27` objek. Contoh: TBLPENJUALAN_HPPSTS, TBLPenjualanDetail, TBLPenjualanDetail_CIKDITIRO, TBLPenjualanDetail_PACUL, TBLPenjualanDetail_PESALAKAN, TBLPenjualanDetail_PUSAT, TBLPenjualanDetail_TRAYEMAN, TBLPenjualanDtFifo
- `mekanik`: `9` objek. Contoh: TBLMekanik, MEKANIK_PERSERVIS_PERSEN_TEMP, GET_TBLMEKANIK, MEKANIK_ADMIN_DETAIL, MEKANIK_ADMIN_JUAL_SERVIS, MEKANIK_PERSERVIS, MEKANIK_PERSERVIS_999, MEKANIK_PERSERVIS_CEK
- `cabang`: `4` objek. Contoh: JasaOrderCabangMitra, TBLCABANG, PENJUALAN_ANTAR_CABANG_GETPIUTANG, PENJUALAN_ANTAR_CABANG_PIUTANG
- `motor`: `8` objek. Contoh: CUCI_MOTOR, KATEGORIMOTOR, TIPEMOTOR, GRATIS_CUCI_MOTOR, GRATIS_CUCI_MOTOR_89RIBU, GRATIS_CUCI_MOTOR_PERIODE, INFO_PAJAK_MOTOR, Q_SURAT_AMBILMOTOR
- `antar`: `3` objek. Contoh: TBLService_JEMPUTANTAR, PENJUALAN_ANTAR_CABANG_GETPIUTANG, PENJUALAN_ANTAR_CABANG_PIUTANG
- `member`: `2` objek. Contoh: DATA_MEMBER, TIPE_MEMBER_PELANGGAN
- `diskon`: `1` objek. Contoh: SERVIS_STANDAR_TERAKHIR_DISKON
- `promo`: `1` objek. Contoh: NOTA_SERVIS_ITEM_JASA_PROMO
- `jadwal`: `1` objek. Contoh: REMINDER_JADWAL_SERVIS
- `insentif`: `5` objek. Contoh: DATA_INSENTIF_JUAL, DATA_INSENTIF_SERVIS, INSENTIF_JUAL_SERVIS, INSENTIF_KARYAWAN, INSENTIF_KARYAWAN_NEW
- `laba`: `5` objek. Contoh: LABA_ITEM_PENJUALAN, LABA_ITEM_SERVICE, LABA_JASA_SERVICE, LABA_JUAL_SERVIS, QRYJualLaba

### Kesimpulan Awal
- Database ini terlihat sebagai database operasional cabang dengan linked/synonym table dan banyak view perhitungan/report.

## FITMOTOR GABUNG.mdb

### Ringkasan Objek
- `SYNONYM`: `136`
- `SYSTEM TABLE`: `12`
- `TABLE`: `33`
- `VIEW`: `225`
- Estimasi objek yang punya padanan nama di `/_admincab`: `183` dari `394`

### Entitas Inti Yang Terdeteksi
- `TBLPelanggan`: NoPelanggan, NamaPelanggan, Alamat, Kota, Propinsi, KodePost, Negara, Telephone, Fax, KontakPerson, Note, Potongan
- `TBLCabang`: KODE, CABANG

### Indikasi Cluster Fitur
- `service`: `54` objek. Contoh: TBLService_Advisor_CIKDITIRO, TBLService_Advisor_PACUL, TBLService_Advisor_PESALAKAN, TBLService_Advisor_PUSAT, TBLService_Advisor_TRAYEMAN, TBLService_CIKDITIRO, TBLSERVICE_HPPSTS_CIKDITIRO, TBLSERVICE_HPPSTS_PACUL
- `servis`: `45` objek. Contoh: INSENTIF_JUAL_SERVIS_DATA, INSENTIF_JUAL_SERVIS_GABUNG_DATA, LABARUGI_ACUAN_QTY_SERVIS_TEMP, REKAP_JUALSERVIS_PERITEM_TERAKHIR_DATA, GABUNG_SERVISJUAL_ALL, GABUNG_SERVISJUAL_PERBENGKEL, HISTORY_SERVIS_DETAIL, HISTORY_SERVIS_HEADER
- `item`: `109` objek. Contoh: TBLItem_CIKDITIRO, TBLItem_PACUL, TBLItem_PESALAKAN, TBLItem_PRODUKSI, TBLItem_PUSAT, TBLItem_TRAYEMAN, TBLItemJenis_CIKDITIRO, TBLItemJenis_PACUL
- `pelanggan`: `27` objek. Contoh: TBLPelanggan, TBLPelanggan_CIKDITIRO, TBLPelanggan_PACUL, TBLPelanggan_PESALAKAN, TBLPelanggan_PRODUKSI, TBLPelanggan_PUSAT, TBLPelanggan_TRAYEMAN, TBLPelangganGrup
- `supplier`: `10` objek. Contoh: TBLSupplier_CIKDITIRO, TBLSupplier_PACUL, TBLSupplier_PESALAKAN, TBLSupplier_PUSAT, TBLSupplier_TRAYEMAN, GABUNG_TBLSUPPLIER_DATA, CEK_UPDATE_SUPPLIER, GABUNG_TBLSUPPLIER
- `hutang`: `1` objek. Contoh: REKAP_HUTANG
- `stok`: `11` objek. Contoh: STOK_OPNAM_TEMP, STOK_OPNAM_TES, STOK_OPNAM_TIDAKLAKU, REKAP_STOK_PERCABANG, STOK_OPNAM_BUATDATA, STOK_OPNAM_BUATDATA_LAMA, STOK_OPNAM_DATA, STOK_OPNAM_DATA_TIDAKLAKU
- `pembelian`: `25` objek. Contoh: TBLPembelianDetail_CIKDITIRO, TBLPembelianDetail_PACUL, TBLPembelianDetail_PESALAKAN, TBLPembelianDetail_PUSAT, TBLPembelianDetail_TRAYEMAN, TBLPembelianHeader_CIKDITIRO, TBLPembelianHeader_PACUL, TBLPembelianHeader_PESALAKAN
- `penjualan`: `33` objek. Contoh: TBLPENJUALAN_HPPSTS_CIKDITIRO, TBLPENJUALAN_HPPSTS_PACUL, TBLPENJUALAN_HPPSTS_PESALAKAN, TBLPENJUALAN_HPPSTS_PUSAT, TBLPENJUALAN_HPPSTS_TRAYEMAN, TBLPenjualanDetail_CIKDITIRO, TBLPenjualanDetail_PACUL, TBLPenjualanDetail_PESALAKAN
- `mekanik`: `13` objek. Contoh: TBLMekanik_CIKDITIRO, TBLMekanik_PACUL, TBLMekanik_PESALAKAN, TBLMekanik_PUSAT, TBLMekanik_TRAYEMAN, GABUNG_TBLMEKANIK_DATA, GABUNG_TBLMEKANIK, INSENTIF_JUAL_SERVIS_MEKANIK_PERSIKLUS
- `cabang`: `12` objek. Contoh: REKONSIL_JUAL_BELI_ANTARCABANG_DATA, TBLCABANG, GABUNG_PELANGGAN_PERCABANG, QUERY_HITUNG_JASA_NET_PER_CABANG, REKAP_BELI_CABANG_SALAH, REKAP_JUAL_CABANG_SALAH, REKAP_STOK_PERCABANG, REKONSIL_BELI_ANTARCABANG
- `motor`: `1` objek. Contoh: TIPEMOTOR
- `antar`: `13` objek. Contoh: TBLService_JEMPUTANTAR_CIKDITIRO, TBLService_JEMPUTANTAR_PACUL, TBLService_JEMPUTANTAR_PESALAKAN, TBLService_JEMPUTANTAR_PUSAT, TBLService_JEMPUTANTAR_TRAYEMAN, PERNAH_JEMPUTANTAR_DATA, REKONSIL_JUAL_BELI_ANTARCABANG_DATA, GABUNG_SERVICE_JEMPUTANTAR
- `member`: `2` objek. Contoh: TIPE_MEMBER, UPDATE_TIPE_MEMBER
- `diskon`: `1` objek. Contoh: SERVIS_STANDAR_TERAKHIR_DISKON
- `insentif`: `11` objek. Contoh: INSENTIF_JUAL_SERVIS_DATA, INSENTIF_JUAL_SERVIS_GABUNG_DATA, PERSEN_INSENTIF, INSENTIF_JUAL_SERVIS, INSENTIF_JUAL_SERVIS_ADMIN_PERSIKLUS, INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM, INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS, INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS_LAMA
- `laba`: `26` objek. Contoh: LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP, LABARUGI_ACUAN_QTY_ITEMKELUAR_TEMP, LABARUGI_ACUAN_QTY_ITEMMASUK_TEMP, LABARUGI_ACUAN_QTY_PENJUALAN_TEMP, LABARUGI_ACUAN_QTY_SERVIS_TEMP, LABA_ITEM_PENJUALAN, LABA_ITEM_SERVICE, LABA_ITEM_SERVIS_PERJENIS_PERSIKLUS
- `rugi`: `16` objek. Contoh: LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP, LABARUGI_ACUAN_QTY_ITEMKELUAR_TEMP, LABARUGI_ACUAN_QTY_ITEMMASUK_TEMP, LABARUGI_ACUAN_QTY_PENJUALAN_TEMP, LABARUGI_ACUAN_QTY_SERVIS_TEMP, LABARUGI_ACUAN_HPP_PEMBELIAN, LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX, LABARUGI_ACUAN_QTY_ITEMKELUAR

### Kesimpulan Awal
- Database ini terlihat sebagai database gabungan lintas cabang untuk konsolidasi, reporting, dan agregasi data.

## Penilaian Implementasi

- Fitur master data, pembelian, penjualan, hutang/piutang, servis reguler, mekanik, dan cabang sudah punya pondasi kuat di `/_admincab`.
- Fitur yang paling mungkin belum lengkap dibanding Access adalah agregasi lintas cabang, reporting gabungan, insentif, dan query analitis berbasis view Access.
- Implementasi terbaik bukan menyalin Access 1:1, tetapi memetakan proses bisnis per cluster lalu membangun versi web yang setara di PHP/MySQL.

## Rencana Implementasi Prioritas

1. Inventory fitur Access per cluster: master, transaksi, servis, antar cabang, report, insentif, reminder.
2. Cocokkan dengan modul `/_admincab` yang sudah ada untuk menandai `existing`, `partial`, dan `missing`.
3. Implementasikan gap yang paling operasional dulu: report gabungan cabang, reminder servis, dan query analitik stok/pembelian.
4. Baru setelah itu masuk ke fitur advanced seperti insentif, forecasting, dan dashboard konsolidasi.