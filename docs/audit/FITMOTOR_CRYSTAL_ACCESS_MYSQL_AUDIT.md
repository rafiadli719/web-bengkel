# Audit Migrasi FITMOTOR GABUNG: Access vs MySQL

Generated: 2026-06-25 09:28:45 

Source of truth: `E:\BENGKEL 2.0\FITMOTOR GABUNG.MDB`
Target MySQL: `fitmotor_gabung`
Fokus audit: integrasi migrasi database secara keseluruhan.

## Ringkasan Temuan

| Item | Jumlah |
|---|---:|
| TableDefs Access (semua) | 181 |
| Tabel user Access | 169 |
| QueryDefs Access | 253 |
| Objek MySQL | 42 |
| View MySQL | 2 |
| File Crystal `.rpt` | 0 |
| Mismatch prioritas | 399 |

Catatan: query Access internal bernama `~sq_...` tetap masuk data audit, tetapi view SQL otomatis hanya dibuat untuk query SELECT/UNION non-internal yang tidak mengandung sintaks Access berisiko.

## A. Mapping Tabel

| Tabel Access | Tabel MySQL | Status | Catatan |
|---|---|---|---|
| BAGI_HASIL |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_PEMBELIAN_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_SERVICE_HEADER_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_STATISTIK_ITEM_MINGGU_PIVOT |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_TBLITEM_PERBENGKEL_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_TBLMEKANIK_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_TBLSERVICE_ADVISOR_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_TBLSUPPLIER_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| GABUNG_TBLUSER_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| INSENTIF_JUAL_SERVIS_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| INSENTIF_JUAL_SERVIS_GABUNG_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| KENDARAAN_PELANGGAN_GABUNG_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP |  | MISSING_TABLE | Create table required in MySQL. |
| LABARUGI_ACUAN_QTY_ITEMKELUAR_TEMP |  | MISSING_TABLE | Create table required in MySQL. |
| LABARUGI_ACUAN_QTY_ITEMMASUK_TEMP |  | MISSING_TABLE | Create table required in MySQL. |
| LABARUGI_ACUAN_QTY_PENJUALAN_TEMP |  | MISSING_TABLE | Create table required in MySQL. |
| LABARUGI_ACUAN_QTY_SERVIS_TEMP |  | MISSING_TABLE | Create table required in MySQL. |
| NOMOR_WA_GCONTACT_SEMUA_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| NOMOR_WA__DOMISILI_POLISI_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| NOPOLISI_DOMISILI_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| PEMBELIAN_PERITEM_PERSUPLIER_1_2_TEMP |  | MISSING_TABLE | Create table required in MySQL. |
| PERNAH_JEMPUTANTAR_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| PERSEN_INSENTIF |  | MISSING_TABLE | Create table required in MySQL. |
| REKAP_JUALSERVIS_PERITEM_TERAKHIR_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| REKAP_KONSUMEN_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| REKAP_KONSUMEN_DATANGBERIKUTNYA_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| REKAP_KONSUMEN_NOMOR_WA_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| REKONSIL_JUAL_BELI_ANTARCABANG_DATA |  | MISSING_TABLE | Create table required in MySQL. |
| SIKLUS_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| SIKLUS_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| SIKLUS_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| SIKLUS_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| SIKLUS_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| STOK_OPNAM_TEMP |  | MISSING_TABLE | Create table required in MySQL. |
| STOK_OPNAM_TES |  | MISSING_TABLE | Create table required in MySQL. |
| STOK_OPNAM_TIDAKLAKU |  | MISSING_TABLE | Create table required in MySQL. |
| Switchboard Items |  | MISSING_TABLE | Create table required in MySQL. |
| TBLCABANG |  | MISSING_TABLE | Create table required in MySQL. |
| TBLItemJenis_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemJenis_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemJenis_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemJenis_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemJenis_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKDetail_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKDetail_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKDetail_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKDetail_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKDetail_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKHeader_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKHeader_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKHeader_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKHeader_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemKHeader_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMDetail_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMDetail_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMDetail_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMDetail_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMDetail_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMHeader_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMHeader_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMHeader_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMHeader_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItemMHeader_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItem_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItem_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItem_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItem_PRODUKSI |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItem_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLItem_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLKendaraan |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLKendaraan_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLKendaraan_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLKendaraan_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLKendaraan_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLKendaraan_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLMekanik_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLMekanik_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLMekanik_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLMekanik_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLMekanik_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPENJUALAN_HPPSTS_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPENJUALAN_HPPSTS_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPENJUALAN_HPPSTS_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPENJUALAN_HPPSTS_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPENJUALAN_HPPSTS_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelanggan |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelangganGrup |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelangganGrup_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelangganGrup_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelangganGrup_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelangganGrup_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelangganGrup_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelanggan_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelanggan_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelanggan_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelanggan_PRODUKSI |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelanggan_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPelanggan_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianDetail_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianDetail_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianDetail_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianDetail_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianDetail_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianHeader_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianHeader_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianHeader_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianHeader_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPembelianHeader_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDetail_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDetail_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDetail_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDetail_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDetail_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDtFifo_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDtFifo_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDtFifo_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDtFifo_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanDtFifo_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanHeader_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanHeader_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanHeader_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanHeader_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLPenjualanHeader_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSERVICE_HPPSTS_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSERVICE_HPPSTS_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSERVICE_HPPSTS_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSERVICE_HPPSTS_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSERVICE_HPPSTS_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDtFifo_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDtFifo_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDtFifo_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDtFifo_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDtFifo_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDt_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDt_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDt_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDt_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceItemDt_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceJasaDt_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceJasaDt_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceJasaDt_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceJasaDt_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLServiceJasaDt_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_Advisor_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_Advisor_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_Advisor_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_Advisor_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_Advisor_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_JEMPUTANTAR_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_JEMPUTANTAR_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_JEMPUTANTAR_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_JEMPUTANTAR_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_JEMPUTANTAR_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLService_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSupplier_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSupplier_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSupplier_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSupplier_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLSupplier_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLUser_CIKDITIRO |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLUser_PACUL |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLUser_PESALAKAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLUser_PUSAT |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TBLUser_TRAYEMAN |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |
| TIPEMOTOR |  | EMPTY_ACCESS_TABLE | Access TableDef exposes no fields through DAO; manual verification needed before MySQL creation. |

## B. Mapping Field Bermasalah

| Tabel | Field Access | Field MySQL Saat Ini | Status | Tindakan |
|---|---|---|---|---|

## C. Mapping Query/View

| Query Access | View MySQL | Status | Jenis | Internal | Catatan |
|---|---|---|---|---|---|
| BELI_PERITEM_PERSUPLIER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELI_PERITEM_PERSUPLIER_KEDUA_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELI_PERITEM_PERSUPLIER_KETIGA_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELI_PERITEM_PERSUPLIER_LEADTIME_KREDIT |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELI_PERITEM_PERSUPLIER_PRA1 |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELI_PERITEM_PERSUPLIER_PRA2 |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELI_PERITEM_PERSUPLIER_REKAP |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELI_PERITEM_PERSUPLIER_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BELUM_PERNAH_GURAHMESIN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| BIAYA_HABIS_PAKAI |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: LABA_JUAL_SERVIS, REKAP_ITEM_KELUAR |
| CEK_GRATIS_OLI |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| CEK_GRATIS_OLI_TERAKHIR |  | MANUAL_REVIEW | SELECT | False | manual review required |
| CEK_NOMOR_WA_TERAKHIR_DATANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| CEK_PEMBELIAN_HRGJUAL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| CEK_TANGGAL_TRANSAKSI_HPP |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: REKAP_ITEM_KELUAR, REKAP_ITEM_MASUK |
| CEK_UPDATE_SUPPLIER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| DATA_PELANGGAN_OLI_MOTUL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| DETAIL_BELI_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| Desember_Mei |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| GABUNG_PELANGGAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PELANGGAN_AWAL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PELANGGAN_PERCABANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PEMBELIAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PEMBELIANDETAIL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PEMBELIANHEADER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PEMBELIAN_LAMA |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PEMBELIAN_NOFAKTUR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PENJUALAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PENJUALAN_DETAIL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_PENJUALAN_DETAIL_HRGPOKOK |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| GABUNG_PENJUALAN_HEADER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_SERVICE_HEADER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_SERVICE_ITEM |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_SERVICE_JASA |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_SERVICE_JEMPUTANTAR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_SERVISJUAL_ALL |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: REKAP_ITEM_KELUAR |
| GABUNG_SERVISJUAL_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: REKAP_ITEM_KELUAR |
| GABUNG_SIKLUS |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| GABUNG_STATISTIK_ITEM |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| GABUNG_STATISTIK_ITEM_BULAN |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_01 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_01_08 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_02 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_03 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_04 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_05 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_06 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_07 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_08 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_09 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_09_12 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_10 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_11 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_12 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_MINGGU_TOT |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_STATISTIK_ITEM_PLUS_JASA |  | MANUAL_REVIEW | SELECT | False | manual review required |
| GABUNG_STATISTIK_ITEM_TOTAL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| GABUNG_TBLITEM |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLITEMJENIS |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| GABUNG_TBLITEMKDETAIL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLITEMKHEADER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLITEMMDETAIL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLITEMMHEADER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLITEM_NEW |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLITEM_ORDER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLITEM_PERBENGKEL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| GABUNG_TBLKENDARAAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLMEKANIK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| GABUNG_TBLPELANGGAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLPELANGGANGRUP |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLPENJUALAN_HPPSTS |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLPenjualanDtFifo |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLSERVICEDT_ITEM_JASA |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLSERVICEITEMDT |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLSERVICEITEMDTFIFO |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLSERVICEJASADT |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLSERVICE_ADVISOR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLSERVICE_HPPSTS |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLSUPPLIER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GABUNG_TBLUSER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GANTI_OLI_DATA |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GANTI_OLI_KEDUA_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GANTI_OLI_MPX2_YMLB_ENDR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GANTI_OLI_STATISTIK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| GANTI_OLI_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| HARI_KONSUMEN_DATANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| HISTORY_GARANSI |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: HISTORY_SERVIS_HEADER |
| HISTORY_GARANSI_GABUNG |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: HISTORY_GARANSI_SEBELUMNYA |
| HISTORY_GARANSI_SEBELUMNYA |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: HISTORY_SERVIS_HEADER |
| HISTORY_SERVIS_DETAIL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| HISTORY_SERVIS_HEADER |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| HRGPOKOK_PENJUALAN_OK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| HRGPOKOK_SERVICE_OK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| INSENTIF_JUAL_SERVIS |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: LABA_JUAL_SERVIS |
| INSENTIF_JUAL_SERVIS_ADMIN_PERSIKLUS |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: INSENTIF_JUAL_SERVIS_GABUNG |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: LABA_JUAL_SERVIS |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS |  | MANUAL_REVIEW | SELECT | False | manual review required |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS_LAMA |  | MANUAL_REVIEW | SELECT | False | manual review required |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERTANGGAL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| INSENTIF_JUAL_SERVIS_GABUNG |  | MANUAL_REVIEW | SELECT | False | manual review required |
| INSENTIF_JUAL_SERVIS_MEKANIK_PERSIKLUS |  | MANUAL_REVIEW | SELECT | False | manual review required |
| ITEM_JUAL_HARIAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| ITEM_JUAL_HARIAN_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| JUAL_SERVIS_HPPSTS_CEK |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: TBLPENJUALAN_HPPSTS_CEK, TBLSERVICE_HPPSTS_CEK |
| JUAL_SERVIS_HPPSTS_REKAP |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: TBLPENJUALAN_HPPSTS_REKAP, TBLSERVICE_HPPSTS_REKAP |
| KARTU_GUDANG |  | MANUAL_REVIEW | SELECT | False | manual review required |
| KENDARAAN_PELANGGAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| KENDARAAN_PELANGGAN_CARI |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| KENDARAAN_PELANGGAN_CARI_HISTORY |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| KENDARAAN_PELANGGAN_GABUNG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| KENDARAAN_PELANGGAN_GABUNG_TERBARU |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| KENDARAAN_PELANGGAN_HEADER_HISTORY |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| KENDARAAN_PELANGGAN_HISTORY |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| LABARUGI_ACUAN_HPP_PEMBELIAN |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| LABARUGI_ACUAN_QTY_ITEMKELUAR |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected; unresolved query dependency: REKAP_ITEM_KELUAR |
| LABARUGI_ACUAN_QTY_ITEMMASUK |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected; unresolved query dependency: REKAP_ITEM_MASUK |
| LABARUGI_ACUAN_QTY_PENJUALAN |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| LABARUGI_ACUAN_QTY_SERVIS |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| LABARUGI_HPP_ITEMKELUAR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| LABARUGI_HPP_ITEMMASUK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| LABARUGI_HPP_PENJUALAN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| LABARUGI_HPP_SERVIS |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| LABARUGI_HPP_TOTAL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| LABA_ITEM_PENJUALAN |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| LABA_ITEM_SERVICE |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| LABA_ITEM_SERVIS_PERJENIS_PERSIKLUS |  | MANUAL_REVIEW | SELECT | False | manual review required |
| LABA_JASA_SERVICE |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| LABA_JASA_SERVICE_OUTSOURCE |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| LABA_JUAL_SERVIS |  | MANUAL_REVIEW | SELECT | False | manual review required |
| LABA_JUAL_SERVIS_MINUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL |
| LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| LABA_JUAL_SERVIS_PERTRX_1 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| LABA_MEKANIK_PERSIKLUS |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| MEKANIK_ADMIN_DETAIL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| MEKANIK_ADMIN_JUAL_SERVIS |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| MEKANIK_PERSERVIS |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| MEKANIK_PERSERVIS_CEK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOMOR_POLISI_SALAH_CEK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| NOMOR_WA |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOMOR_WA_DAFTAR_NOPOLISI |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| NOMOR_WA_DOMISILI |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOMOR_WA_GCONTACT_SEMUA |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| NOMOR_WA_JUML_PERBENGKEL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOMOR_WA_SALAH_CEK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| NOMOR_WA_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOMOR_WA__DOMISILI_NOPOLISI |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOPOLISI_DOMISILI |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOPOLISI_JUML_PERBENGKEL |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| NOPOLISI_JUML_PERBENGKEL_MAX |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| NOPOLISI_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PEMBELIAN_PERITEM_PERSUPLIER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PEMBELIAN_PERITEM_PERSUPLIER1 |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PEMBELIAN_PERITEM_PERSUPLIER2 |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PEMBELIAN_PERITEM_PERSUPLIER_1_2 |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PENAWARAN_VOUCHER_REMAP |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PERNAH_GURAHMESIN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PERNAH_JEMPUTANTAR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| PROGRAM_OLI_GRATIS |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| QUERY_HITUNG_JASA_NET_PER_CABANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| Q_DATA_MINMAX |  | MANUAL_REVIEW | SELECT | False | manual review required |
| Q_HITUNG_MINMAX |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| Q_HITUNG_MINMAX_LAMA |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| Q_HITUNG_MINMAX_STATUS |  | MANUAL_REVIEW | SELECT | False | manual review required |
| Query1 |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: mei_juni |
| Query2 |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_BELI_CABANG_SALAH |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_BELI_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_BELI_PERSUPPLIER_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_BELI_TIPE_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_BHP_ITEMKELUAR_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_BHP_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_HPPJUAL_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_HPPJUAL_TIPE_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_HPPSERVIS_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_HUTANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| REKAP_ITEM_KELUAR |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_ITEM_MASUK |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_ITEM_MASUK_KELUAR_PERSIKLUS |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_JUALSERVIS_PERITEM_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| REKAP_JUAL_CABANG_SALAH |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_JUAL_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_JUAL_TIPE_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_KONSUMEN |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| REKAP_KONSUMEN_DATANGBERIKUTNYA |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| REKAP_KONSUMEN_NOMOR_WA |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| REKAP_KONSUMEN_NOMOR_WA_KATEGORI |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| REKAP_KONSUMEN_NOMOR_WA_TERBARU |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_OUTSOURCE_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKAP_SERVIS_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: REKAP_SERVIS_PERTRANSAKSI_PERBENGKEL |
| REKAP_SERVIS_PERTANGGAL_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: REKAP_SERVIS_PERTRANSAKSI_PERBENGKEL |
| REKAP_SERVIS_PERTRANSAKSI_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_STATISTIK_HARI_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| REKAP_STOK_PERCABANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKONSIL_BELI_ANTARCABANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| REKONSIL_JUAL_ANTARCABANG |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKONSIL_JUAL_BELI_ANTARCABANG |  | MANUAL_REVIEW | SELECT | False | manual review required |
| REKONSIL_JUAL_BELI_ANTARCABANG_LAMA |  | MANUAL_REVIEW | SELECT | False | manual review required |
| RESPONDEN_LAMA_TIDAK_DATANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed/unresolved; DAO resolution disabled. |
| SERVIS_CVT |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_CVT_BELUMPERNAH |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_CVT_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_STANDAR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_STANDAR_KEDUA_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_STANDAR_STATISTIK |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_STANDAR_TERAKHIR |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_STANDAR_TERAKHIR_DISKON |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_STANDAR_VOUCHER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_STANDAR_VOUCHER_JUMLAH |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| SERVIS_TANGGAL_TERBARU_PERCABANG |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| STOK_OPNAM_BUATDATA |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| STOK_OPNAM_BUATDATA_LAMA |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| STOK_OPNAM_DATA |  | MANUAL_REVIEW | SELECT | False | unresolved query dependency: STOK_OPNAM_SERVIS_JUAL, STOK_OPNAM_STATUS |
| STOK_OPNAM_DATA_TIDAKLAKU |  | MANUAL_REVIEW | SELECT | False | manual review required |
| STOK_OPNAM_ITEM |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| STOK_OPNAM_SERVIS_JUAL |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| STOK_OPNAM_STATUS |  | MANUAL_REVIEW | SELECT | False | manual review required |
| TBLPENJUALAN_HPPSTS_CEK |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| TBLPENJUALAN_HPPSTS_REKAP |  | MANUAL_REVIEW | SELECT | False | manual review required |
| TBLSERVICE_HPPSTS_BELUM_UPDATE |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| TBLSERVICE_HPPSTS_CEK |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| TBLSERVICE_HPPSTS_REKAP |  | MANUAL_REVIEW | SELECT | False | manual review required |
| TBLSUPPLIER_LEADTIME_KREDIT |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| TIPE_MEMBER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| UPDATE_TIPE_MEMBER |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| mei_juni |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| rekap_unit |  | MANUAL_REVIEW | SELECT | False | Access-specific syntax/reference detected |
| tmpExport |  | MISSING_VIEW | SELECT | False | Access output error: Output fields parsed from SELECT list. |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList0 |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList10 |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query; Access-specific syntax/reference detected |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList4 |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList6 |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList8 |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query; Access-specific syntax/reference detected |
| ~sq_cFR_DOWNLOAD_STOK_OPNAM~sq_cCB_TGL_AWAL |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query; Access-specific syntax/reference detected |
| ~sq_cFR_DOWNLOAD_STOK_OPNAM~sq_cPILIH_CABANG |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_KENDARAAN_CARI~sq_cSearchResults |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_KENDARAAN_DAFTARMOTOR_HISTORY~sq_cDaftarMotor |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_KENDARAAN_DETAIL_HISTORY_LAMA~sq_cListBarang |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query; Access-specific syntax/reference detected |
| ~sq_cFR_KENDARAAN_DETAIL_HISTORY_LAMA~sq_cListJasa |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query; Access-specific syntax/reference detected |
| ~sq_cFR_KENDARAAN_HEADER_HISTORY~sq_cListBarang |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query; Access-specific syntax/reference detected |
| ~sq_cFR_KENDARAAN_HEADER_HISTORY~sq_cListJasa |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_KENDARAAN_PELANGGAN_HISTORY~sq_cListHistory |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query; Access-specific syntax/reference detected |
| ~sq_cFR_LABARUGI_HITUNG_HPP~sq_cCBCABANG |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_LABARUGI_HITUNG_HPP~sq_cCBSIKLUS |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_NOMOR_WA_GCONTACT_ALL_DOWNLOAD~sq_cLIST_PELANGGAN |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_NOMOR_WA_GCONTACT_BARU_DOWNLOAD~sq_cLIST_PELANGGAN |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_NOMOR_WA_GCONTACT_DEPAN~sq_cList11 |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cFR_UPLOAD_STOK_OPNAM~sq_cPILIH_CABANG |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_cfLogin~sq_cT1 |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_fFR_KENDARAAN_PELANGGAN |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |
| ~sq_fSwitchboard |  | MANUAL_REVIEW | SELECT | True | internal Access form/report query |

## D. Daftar Mismatch Prioritas

| Objek | Nama | Masalah | Dampak | Tindakan |
|---|---|---|---|---|
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER_KEDUA_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER_KETIGA_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER_LEADTIME_KREDIT | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER_PRA1 | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER_PRA2 | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER_REKAP | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELI_PERITEM_PERSUPLIER_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BELUM_PERNAH_GURAHMESIN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | BIAYA_HABIS_PAKAI | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | CEK_GRATIS_OLI | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | CEK_GRATIS_OLI_TERAKHIR | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | CEK_NOMOR_WA_TERAKHIR_DATANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | CEK_PEMBELIAN_HRGJUAL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | CEK_TANGGAL_TRANSAKSI_HPP | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | CEK_UPDATE_SUPPLIER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | DATA_PELANGGAN_OLI_MOTUL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | DETAIL_BELI_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | Desember_Mei | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_PELANGGAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PELANGGAN_AWAL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PELANGGAN_PERCABANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PEMBELIAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PEMBELIANDETAIL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PEMBELIANHEADER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PEMBELIAN_LAMA | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PEMBELIAN_NOFAKTUR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PENJUALAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PENJUALAN_DETAIL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_PENJUALAN_DETAIL_HRGPOKOK | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_PENJUALAN_HEADER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_SERVICE_HEADER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_SERVICE_ITEM | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_SERVICE_JASA | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_SERVICE_JEMPUTANTAR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_SERVISJUAL_ALL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_SERVISJUAL_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_SIKLUS | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_BULAN | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_01 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_01_08 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_02 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_03 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_04 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_05 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_06 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_07 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_08 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_09 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_09_12 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_10 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_11 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_12 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_MINGGU_TOT | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_PLUS_JASA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_STATISTIK_ITEM_TOTAL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | GABUNG_TBLITEM | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEMJENIS | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEMKDETAIL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEMKHEADER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEMMDETAIL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEMMHEADER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEM_NEW | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEM_ORDER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLITEM_PERBENGKEL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLKENDARAAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLMEKANIK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLPELANGGAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLPELANGGANGRUP | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLPENJUALAN_HPPSTS | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLPenjualanDtFifo | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLSERVICEDT_ITEM_JASA | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLSERVICEITEMDT | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLSERVICEITEMDTFIFO | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLSERVICEJASADT | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLSERVICE_ADVISOR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLSERVICE_HPPSTS | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLSUPPLIER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GABUNG_TBLUSER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GANTI_OLI_DATA | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GANTI_OLI_KEDUA_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GANTI_OLI_MPX2_YMLB_ENDR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GANTI_OLI_STATISTIK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | GANTI_OLI_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | HARI_KONSUMEN_DATANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | HISTORY_GARANSI | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | HISTORY_GARANSI_GABUNG | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | HISTORY_GARANSI_SEBELUMNYA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | HISTORY_SERVIS_DETAIL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | HISTORY_SERVIS_HEADER | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | HRGPOKOK_PENJUALAN_OK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | HRGPOKOK_SERVICE_OK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS_ADMIN_PERSIKLUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS_LAMA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS_ADVISOR_PERTANGGAL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS_GABUNG | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | INSENTIF_JUAL_SERVIS_MEKANIK_PERSIKLUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | ITEM_JUAL_HARIAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | ITEM_JUAL_HARIAN_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | JUAL_SERVIS_HPPSTS_CEK | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | JUAL_SERVIS_HPPSTS_REKAP | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | KARTU_GUDANG | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | KENDARAAN_PELANGGAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | KENDARAAN_PELANGGAN_CARI | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | KENDARAAN_PELANGGAN_CARI_HISTORY | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | KENDARAAN_PELANGGAN_GABUNG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | KENDARAAN_PELANGGAN_GABUNG_TERBARU | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | KENDARAAN_PELANGGAN_HEADER_HISTORY | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | KENDARAAN_PELANGGAN_HISTORY | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | LABARUGI_ACUAN_HPP_PEMBELIAN | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | LABARUGI_ACUAN_QTY_ITEMKELUAR | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABARUGI_ACUAN_QTY_ITEMMASUK | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABARUGI_ACUAN_QTY_PENJUALAN | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABARUGI_ACUAN_QTY_SERVIS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABARUGI_HPP_ITEMKELUAR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | LABARUGI_HPP_ITEMMASUK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | LABARUGI_HPP_PENJUALAN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | LABARUGI_HPP_SERVIS | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | LABARUGI_HPP_TOTAL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | LABA_ITEM_PENJUALAN | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_ITEM_SERVICE | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_ITEM_SERVIS_PERJENIS_PERSIKLUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_JASA_SERVICE | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_JASA_SERVICE_OUTSOURCE | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_JUAL_SERVIS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_JUAL_SERVIS_MINUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_JUAL_SERVIS_PERTRX_1 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | LABA_MEKANIK_PERSIKLUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | MEKANIK_ADMIN_DETAIL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | MEKANIK_ADMIN_JUAL_SERVIS | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | MEKANIK_PERSERVIS | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | MEKANIK_PERSERVIS_CEK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOMOR_POLISI_SALAH_CEK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOMOR_WA | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOMOR_WA_DAFTAR_NOPOLISI | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | NOMOR_WA_DOMISILI | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOMOR_WA_GCONTACT_SEMUA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | NOMOR_WA_JUML_PERBENGKEL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOMOR_WA_SALAH_CEK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOMOR_WA_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOMOR_WA__DOMISILI_NOPOLISI | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOPOLISI_DOMISILI | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOPOLISI_JUML_PERBENGKEL | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | NOPOLISI_JUML_PERBENGKEL_MAX | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | NOPOLISI_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PEMBELIAN_PERITEM_PERSUPLIER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PEMBELIAN_PERITEM_PERSUPLIER1 | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PEMBELIAN_PERITEM_PERSUPLIER2 | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PEMBELIAN_PERITEM_PERSUPLIER_1_2 | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PENAWARAN_VOUCHER_REMAP | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PERNAH_GURAHMESIN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PERNAH_JEMPUTANTAR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | PROGRAM_OLI_GRATIS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | QUERY_HITUNG_JASA_NET_PER_CABANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | Q_DATA_MINMAX | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | Q_HITUNG_MINMAX | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | Q_HITUNG_MINMAX_LAMA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | Q_HITUNG_MINMAX_STATUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | Query1 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | Query2 | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_BELI_CABANG_SALAH | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_BELI_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_BELI_PERSUPPLIER_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_BELI_TIPE_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_BHP_ITEMKELUAR_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_BHP_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_HPPJUAL_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_HPPJUAL_TIPE_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_HPPSERVIS_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_HUTANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKAP_ITEM_KELUAR | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_ITEM_MASUK | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_ITEM_MASUK_KELUAR_PERSIKLUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_JUALSERVIS_PERITEM_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKAP_JUAL_CABANG_SALAH | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_JUAL_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_JUAL_TIPE_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_KONSUMEN | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKAP_KONSUMEN_DATANGBERIKUTNYA | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKAP_KONSUMEN_NOMOR_WA | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKAP_KONSUMEN_NOMOR_WA_KATEGORI | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKAP_KONSUMEN_NOMOR_WA_TERBARU | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_OUTSOURCE_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_SERVIS_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_SERVIS_PERTANGGAL_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_SERVIS_PERTRANSAKSI_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_STATISTIK_HARI_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKAP_STOK_PERCABANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKONSIL_BELI_ANTARCABANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | REKONSIL_JUAL_ANTARCABANG | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKONSIL_JUAL_BELI_ANTARCABANG | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | REKONSIL_JUAL_BELI_ANTARCABANG_LAMA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | RESPONDEN_LAMA_TIDAK_DATANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_CVT | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_CVT_BELUMPERNAH | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_CVT_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_STANDAR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_STANDAR_KEDUA_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_STANDAR_STATISTIK | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_STANDAR_TERAKHIR | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_STANDAR_TERAKHIR_DISKON | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_STANDAR_VOUCHER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_STANDAR_VOUCHER_JUMLAH | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | SERVIS_TANGGAL_TERBARU_PERCABANG | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | STOK_OPNAM_BUATDATA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | STOK_OPNAM_BUATDATA_LAMA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | STOK_OPNAM_DATA | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | STOK_OPNAM_DATA_TIDAKLAKU | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | STOK_OPNAM_ITEM | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | STOK_OPNAM_SERVIS_JUAL | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | STOK_OPNAM_STATUS | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | TBLPENJUALAN_HPPSTS_CEK | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | TBLPENJUALAN_HPPSTS_REKAP | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | TBLSERVICE_HPPSTS_BELUM_UPDATE | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | TBLSERVICE_HPPSTS_CEK | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | TBLSERVICE_HPPSTS_REKAP | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | TBLSUPPLIER_LEADTIME_KREDIT | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | TIPE_MEMBER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | UPDATE_TIPE_MEMBER | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| QUERY_VIEW | mei_juni | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | rekap_unit | MANUAL_REVIEW | Crystal/query compatibility risk | Review query and create matching MySQL VIEW manually if needed |
| QUERY_VIEW | tmpExport | MISSING_VIEW | Crystal/query compatibility risk | Create/replace view with exact Access output columns |
| TABLE | BAGI_HASIL | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_PEMBELIAN_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_SERVICE_HEADER_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_STATISTIK_ITEM_MINGGU_PIVOT | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_TBLITEM_PERBENGKEL_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_TBLMEKANIK_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_TBLSERVICE_ADVISOR_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_TBLSUPPLIER_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | GABUNG_TBLUSER_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | INSENTIF_JUAL_SERVIS_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | INSENTIF_JUAL_SERVIS_GABUNG_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | KENDARAAN_PELANGGAN_GABUNG_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | LABARUGI_ACUAN_QTY_ITEMKELUAR_TEMP | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | LABARUGI_ACUAN_QTY_ITEMMASUK_TEMP | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | LABARUGI_ACUAN_QTY_PENJUALAN_TEMP | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | LABARUGI_ACUAN_QTY_SERVIS_TEMP | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | NOMOR_WA_GCONTACT_SEMUA_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | NOMOR_WA__DOMISILI_POLISI_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | NOPOLISI_DOMISILI_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | PEMBELIAN_PERITEM_PERSUPLIER_1_2_TEMP | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | PERNAH_JEMPUTANTAR_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | PERSEN_INSENTIF | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | REKAP_JUALSERVIS_PERITEM_TERAKHIR_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | REKAP_KONSUMEN_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | REKAP_KONSUMEN_DATANGBERIKUTNYA_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | REKAP_KONSUMEN_NOMOR_WA_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | REKONSIL_JUAL_BELI_ANTARCABANG_DATA | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | SIKLUS_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | SIKLUS_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | SIKLUS_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | SIKLUS_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | SIKLUS_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | STOK_OPNAM_TEMP | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | STOK_OPNAM_TES | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | STOK_OPNAM_TIDAKLAKU | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | Switchboard Items | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | TBLCABANG | Missing MySQL table | Crystal/query cannot bind this source | Create table with Access field names |
| TABLE | TBLItemJenis_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemJenis_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemJenis_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemJenis_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemJenis_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKDetail_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKDetail_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKDetail_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKDetail_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKDetail_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKHeader_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKHeader_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKHeader_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKHeader_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemKHeader_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMDetail_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMDetail_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMDetail_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMDetail_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMDetail_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMHeader_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMHeader_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMHeader_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMHeader_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItemMHeader_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItem_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItem_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItem_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItem_PRODUKSI | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItem_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLItem_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLKendaraan | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLKendaraan_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLKendaraan_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLKendaraan_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLKendaraan_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLKendaraan_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLMekanik_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLMekanik_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLMekanik_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLMekanik_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLMekanik_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPENJUALAN_HPPSTS_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPENJUALAN_HPPSTS_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPENJUALAN_HPPSTS_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPENJUALAN_HPPSTS_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPENJUALAN_HPPSTS_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelanggan | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelangganGrup | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelangganGrup_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelangganGrup_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelangganGrup_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelangganGrup_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelangganGrup_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelanggan_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelanggan_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelanggan_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelanggan_PRODUKSI | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelanggan_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPelanggan_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianDetail_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianDetail_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianDetail_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianDetail_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianDetail_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianHeader_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianHeader_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianHeader_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianHeader_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPembelianHeader_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDetail_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDetail_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDetail_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDetail_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDetail_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDtFifo_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDtFifo_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDtFifo_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDtFifo_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanDtFifo_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanHeader_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanHeader_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanHeader_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanHeader_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLPenjualanHeader_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSERVICE_HPPSTS_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSERVICE_HPPSTS_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSERVICE_HPPSTS_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSERVICE_HPPSTS_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSERVICE_HPPSTS_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDtFifo_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDtFifo_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDtFifo_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDtFifo_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDtFifo_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDt_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDt_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDt_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDt_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceItemDt_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceJasaDt_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceJasaDt_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceJasaDt_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceJasaDt_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLServiceJasaDt_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_Advisor_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_Advisor_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_Advisor_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_Advisor_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_Advisor_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_JEMPUTANTAR_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_JEMPUTANTAR_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_JEMPUTANTAR_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_JEMPUTANTAR_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_JEMPUTANTAR_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLService_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSupplier_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSupplier_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSupplier_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSupplier_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLSupplier_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLUser_CIKDITIRO | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLUser_PACUL | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLUser_PESALAKAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLUser_PUSAT | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TBLUser_TRAYEMAN | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |
| TABLE | TIPEMOTOR | Access table has no accessible fields via DAO | Cannot generate faithful MySQL table structure automatically | Manually inspect linked/empty table in Access before creating MySQL table |

## E. Dependency Query Access

| Query Access | Bergantung Pada | Jenis Dependency |
|---|---|---|
| BELI_PERITEM_PERSUPLIER | BELI_PERITEM_PERSUPLIER_PRA1 | QUERY |
| BELI_PERITEM_PERSUPLIER | BELI_PERITEM_PERSUPLIER_PRA2 | QUERY |
| BELI_PERITEM_PERSUPLIER_KEDUA_TERAKHIR | BELI_PERITEM_PERSUPLIER | QUERY |
| BELI_PERITEM_PERSUPLIER_KEDUA_TERAKHIR | BELI_PERITEM_PERSUPLIER_TERAKHIR | QUERY |
| BELI_PERITEM_PERSUPLIER_KETIGA_TERAKHIR | BELI_PERITEM_PERSUPLIER | QUERY |
| BELI_PERITEM_PERSUPLIER_KETIGA_TERAKHIR | BELI_PERITEM_PERSUPLIER_KEDUA_TERAKHIR | QUERY |
| BELI_PERITEM_PERSUPLIER_LEADTIME_KREDIT | BELI_PERITEM_PERSUPLIER_REKAP | QUERY |
| BELI_PERITEM_PERSUPLIER_LEADTIME_KREDIT | TBLSUPPLIER_LEADTIME_KREDIT | QUERY |
| BELI_PERITEM_PERSUPLIER_PRA1 | GABUNG_PEMBELIANDETAIL | QUERY |
| BELI_PERITEM_PERSUPLIER_PRA1 | GABUNG_PEMBELIANHEADER | QUERY |
| BELI_PERITEM_PERSUPLIER_PRA1 | TBLSUPPLIER_LEADTIME_KREDIT | QUERY |
| BELI_PERITEM_PERSUPLIER_PRA2 | BELI_PERITEM_PERSUPLIER_PRA1 | QUERY |
| BELI_PERITEM_PERSUPLIER_REKAP | BELI_PERITEM_PERSUPLIER_KEDUA_TERAKHIR | QUERY |
| BELI_PERITEM_PERSUPLIER_REKAP | BELI_PERITEM_PERSUPLIER_KETIGA_TERAKHIR | QUERY |
| BELI_PERITEM_PERSUPLIER_REKAP | BELI_PERITEM_PERSUPLIER_TERAKHIR | QUERY |
| BELI_PERITEM_PERSUPLIER_REKAP | GABUNG_TBLITEM | QUERY |
| BELI_PERITEM_PERSUPLIER_TERAKHIR | BELI_PERITEM_PERSUPLIER | QUERY |
| BELUM_PERNAH_GURAHMESIN | GABUNG_SERVICE_JASA | QUERY |
| BELUM_PERNAH_GURAHMESIN | PERNAH_GURAHMESIN | QUERY |
| BIAYA_HABIS_PAKAI | GABUNG_PELANGGAN | QUERY |
| BIAYA_HABIS_PAKAI | GABUNG_PENJUALAN_HEADER | QUERY |
| BIAYA_HABIS_PAKAI | LABA_JUAL_SERVIS | QUERY |
| BIAYA_HABIS_PAKAI | REKAP_ITEM_KELUAR | QUERY |
| CEK_GRATIS_OLI | GABUNG_SERVICE_ITEM | QUERY |
| CEK_GRATIS_OLI | GABUNG_TBLITEM | QUERY |
| CEK_GRATIS_OLI | HRGPOKOK_SERVICE_OK | QUERY |
| CEK_GRATIS_OLI | NOMOR_WA__DOMISILI_NOPOLISI | QUERY |
| CEK_GRATIS_OLI_TERAKHIR | CEK_GRATIS_OLI | QUERY |
| CEK_NOMOR_WA_TERAKHIR_DATANG | GABUNG_PELANGGAN_PERCABANG | QUERY |
| CEK_NOMOR_WA_TERAKHIR_DATANG | NOPOLISI_DOMISILI | QUERY |
| CEK_NOMOR_WA_TERAKHIR_DATANG | REKAP_KONSUMEN_DATANGBERIKUTNYA | QUERY |
| CEK_TANGGAL_TRANSAKSI_HPP | GABUNG_PEMBELIANHEADER | QUERY |
| CEK_TANGGAL_TRANSAKSI_HPP | GABUNG_PENJUALAN_HEADER | QUERY |
| CEK_TANGGAL_TRANSAKSI_HPP | GABUNG_SERVICE_ITEM | QUERY |
| CEK_TANGGAL_TRANSAKSI_HPP | REKAP_ITEM_KELUAR | QUERY |
| CEK_TANGGAL_TRANSAKSI_HPP | REKAP_ITEM_MASUK | QUERY |
| CEK_UPDATE_SUPPLIER | BELI_PERITEM_PERSUPLIER_LEADTIME_KREDIT | QUERY |
| CEK_UPDATE_SUPPLIER | TBLItem_PRODUKSI | TABLE |
| DATA_PELANGGAN_OLI_MOTUL | GABUNG_PELANGGAN_PERCABANG | QUERY |
| DATA_PELANGGAN_OLI_MOTUL | GABUNG_SERVICE_ITEM | QUERY |
| DATA_PELANGGAN_OLI_MOTUL | GABUNG_TBLKENDARAAN | QUERY |
| DETAIL_BELI_PERSIKLUS_PERBENGKEL | GABUNG_PEMBELIANHEADER | QUERY |
| DETAIL_BELI_PERSIKLUS_PERBENGKEL | GABUNG_TBLSUPPLIER | QUERY |
| Desember_Mei | GABUNG_SERVICE_HEADER | QUERY |
| GABUNG_PELANGGAN | GABUNG_PELANGGAN_AWAL | QUERY |
| GABUNG_PELANGGAN | TBLPelanggan_PACUL | TABLE |
| GABUNG_PELANGGAN | TBLPelanggan_PESALAKAN | TABLE |
| GABUNG_PELANGGAN_AWAL | TBLPelanggan_CIKDITIRO | TABLE |
| GABUNG_PELANGGAN_AWAL | TBLPelanggan_PACUL | TABLE |
| GABUNG_PELANGGAN_AWAL | TBLPelanggan_PESALAKAN | TABLE |
| GABUNG_PELANGGAN_AWAL | TBLPelanggan_PUSAT | TABLE |
| GABUNG_PELANGGAN_AWAL | TBLPelanggan_TRAYEMAN | TABLE |
| GABUNG_PELANGGAN_PERCABANG | TBLPelanggan_CIKDITIRO | TABLE |
| GABUNG_PELANGGAN_PERCABANG | TBLPelanggan_PACUL | TABLE |
| GABUNG_PELANGGAN_PERCABANG | TBLPelanggan_PESALAKAN | TABLE |
| GABUNG_PELANGGAN_PERCABANG | TBLPelanggan_PUSAT | TABLE |
| GABUNG_PELANGGAN_PERCABANG | TBLPelanggan_TRAYEMAN | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianDetail_CIKDITIRO | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianDetail_PACUL | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianDetail_PESALAKAN | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianDetail_PUSAT | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianDetail_TRAYEMAN | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianHeader_CIKDITIRO | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianHeader_PACUL | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianHeader_PESALAKAN | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianHeader_PUSAT | TABLE |
| GABUNG_PEMBELIAN | TBLPembelianHeader_TRAYEMAN | TABLE |
| GABUNG_PEMBELIANDETAIL | TBLPembelianDetail_CIKDITIRO | TABLE |
| GABUNG_PEMBELIANDETAIL | TBLPembelianDetail_PACUL | TABLE |
| GABUNG_PEMBELIANDETAIL | TBLPembelianDetail_PESALAKAN | TABLE |
| GABUNG_PEMBELIANDETAIL | TBLPembelianDetail_PUSAT | TABLE |
| GABUNG_PEMBELIANDETAIL | TBLPembelianDetail_TRAYEMAN | TABLE |
| GABUNG_PEMBELIANHEADER | TBLPembelianHeader_CIKDITIRO | TABLE |
| GABUNG_PEMBELIANHEADER | TBLPembelianHeader_PACUL | TABLE |
| GABUNG_PEMBELIANHEADER | TBLPembelianHeader_PESALAKAN | TABLE |
| GABUNG_PEMBELIANHEADER | TBLPembelianHeader_PUSAT | TABLE |
| GABUNG_PEMBELIANHEADER | TBLPembelianHeader_TRAYEMAN | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianDetail_CIKDITIRO | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianDetail_PACUL | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianDetail_PESALAKAN | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianDetail_PUSAT | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianDetail_TRAYEMAN | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianHeader_CIKDITIRO | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianHeader_PACUL | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianHeader_PESALAKAN | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianHeader_PUSAT | TABLE |
| GABUNG_PEMBELIAN_LAMA | TBLPembelianHeader_TRAYEMAN | TABLE |
| GABUNG_PEMBELIAN_NOFAKTUR | GABUNG_PEMBELIAN | QUERY |
| GABUNG_PENJUALAN | TBLPenjualanDetail_CIKDITIRO | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanDetail_PACUL | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanDetail_PESALAKAN | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanDetail_PUSAT | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanDetail_TRAYEMAN | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanHeader_CIKDITIRO | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanHeader_PACUL | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanHeader_PESALAKAN | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanHeader_PUSAT | TABLE |
| GABUNG_PENJUALAN | TBLPenjualanHeader_TRAYEMAN | TABLE |
| GABUNG_PENJUALAN_DETAIL | TBLPenjualanDetail_CIKDITIRO | TABLE |
| GABUNG_PENJUALAN_DETAIL | TBLPenjualanDetail_PACUL | TABLE |
| GABUNG_PENJUALAN_DETAIL | TBLPenjualanDetail_PESALAKAN | TABLE |
| GABUNG_PENJUALAN_DETAIL | TBLPenjualanDetail_PUSAT | TABLE |
| GABUNG_PENJUALAN_DETAIL | TBLPenjualanDetail_TRAYEMAN | TABLE |
| GABUNG_PENJUALAN_DETAIL_HRGPOKOK | GABUNG_PENJUALAN_DETAIL | QUERY |
| GABUNG_PENJUALAN_HEADER | TBLPenjualanHeader_CIKDITIRO | TABLE |
| GABUNG_PENJUALAN_HEADER | TBLPenjualanHeader_PACUL | TABLE |
| GABUNG_PENJUALAN_HEADER | TBLPenjualanHeader_PESALAKAN | TABLE |
| GABUNG_PENJUALAN_HEADER | TBLPenjualanHeader_PUSAT | TABLE |
| GABUNG_PENJUALAN_HEADER | TBLPenjualanHeader_TRAYEMAN | TABLE |
| GABUNG_SERVICE_HEADER | TBLService_CIKDITIRO | TABLE |
| GABUNG_SERVICE_HEADER | TBLService_PACUL | TABLE |
| GABUNG_SERVICE_HEADER | TBLService_PESALAKAN | TABLE |
| GABUNG_SERVICE_HEADER | TBLService_PUSAT | TABLE |
| GABUNG_SERVICE_HEADER | TBLService_TRAYEMAN | TABLE |
| GABUNG_SERVICE_ITEM | TBLServiceItemDt_CIKDITIRO | TABLE |
| GABUNG_SERVICE_ITEM | TBLServiceItemDt_PACUL | TABLE |
| GABUNG_SERVICE_ITEM | TBLServiceItemDt_PESALAKAN | TABLE |
| GABUNG_SERVICE_ITEM | TBLServiceItemDt_PUSAT | TABLE |
| GABUNG_SERVICE_ITEM | TBLServiceItemDt_TRAYEMAN | TABLE |
| GABUNG_SERVICE_ITEM | TBLService_CIKDITIRO | TABLE |
| GABUNG_SERVICE_ITEM | TBLService_PACUL | TABLE |
| GABUNG_SERVICE_ITEM | TBLService_PESALAKAN | TABLE |
| GABUNG_SERVICE_ITEM | TBLService_PUSAT | TABLE |
| GABUNG_SERVICE_ITEM | TBLService_TRAYEMAN | TABLE |
| GABUNG_SERVICE_JASA | TBLServiceJasaDt_CIKDITIRO | TABLE |
| GABUNG_SERVICE_JASA | TBLServiceJasaDt_PACUL | TABLE |
| GABUNG_SERVICE_JASA | TBLServiceJasaDt_PESALAKAN | TABLE |
| GABUNG_SERVICE_JASA | TBLServiceJasaDt_PUSAT | TABLE |
| GABUNG_SERVICE_JASA | TBLServiceJasaDt_TRAYEMAN | TABLE |
| GABUNG_SERVICE_JASA | TBLService_CIKDITIRO | TABLE |
| GABUNG_SERVICE_JASA | TBLService_PACUL | TABLE |
| GABUNG_SERVICE_JASA | TBLService_PESALAKAN | TABLE |
| GABUNG_SERVICE_JASA | TBLService_PUSAT | TABLE |
| GABUNG_SERVICE_JASA | TBLService_TRAYEMAN | TABLE |
| GABUNG_SERVICE_JEMPUTANTAR | TBLService_JEMPUTANTAR_CIKDITIRO | TABLE |
| GABUNG_SERVICE_JEMPUTANTAR | TBLService_JEMPUTANTAR_PACUL | TABLE |
| GABUNG_SERVICE_JEMPUTANTAR | TBLService_JEMPUTANTAR_PESALAKAN | TABLE |
| GABUNG_SERVICE_JEMPUTANTAR | TBLService_JEMPUTANTAR_PUSAT | TABLE |
| GABUNG_SERVICE_JEMPUTANTAR | TBLService_JEMPUTANTAR_TRAYEMAN | TABLE |
| GABUNG_SERVISJUAL_ALL | GABUNG_PENJUALAN | QUERY |
| GABUNG_SERVISJUAL_ALL | GABUNG_SERVICE_ITEM | QUERY |
| GABUNG_SERVISJUAL_ALL | REKAP_ITEM_KELUAR | QUERY |
| GABUNG_SERVISJUAL_PERBENGKEL | GABUNG_PENJUALAN | QUERY |
| GABUNG_SERVISJUAL_PERBENGKEL | GABUNG_SERVICE_ITEM | QUERY |
| GABUNG_SERVISJUAL_PERBENGKEL | REKAP_ITEM_KELUAR | QUERY |
| GABUNG_SIKLUS | SIKLUS_CIKDITIRO | TABLE |
| GABUNG_SIKLUS | SIKLUS_PACUL | TABLE |
| GABUNG_SIKLUS | SIKLUS_PESALAKAN | TABLE |
| GABUNG_SIKLUS | SIKLUS_PUSAT | TABLE |
| GABUNG_SIKLUS | SIKLUS_TRAYEMAN | TABLE |
| GABUNG_STATISTIK_ITEM | GABUNG_SERVISJUAL_ALL | QUERY |
| GABUNG_STATISTIK_ITEM | GABUNG_SERVISJUAL_PERBENGKEL | QUERY |
| GABUNG_STATISTIK_ITEM | GABUNG_TBLITEM | QUERY |
| GABUNG_STATISTIK_ITEM_BULAN | GABUNG_STATISTIK_ITEM | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU | GABUNG_STATISTIK_ITEM | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_01 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_01_08 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_02 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_03 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_04 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_05 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_06 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_07 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_08 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_09 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_09_12 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_10 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_11 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_12 | GABUNG_STATISTIK_ITEM_MINGGU | QUERY |
| GABUNG_STATISTIK_ITEM_MINGGU_TOT | GABUNG_STATISTIK_ITEM_MINGGU_PIVOT | TABLE |
| GABUNG_STATISTIK_ITEM_PLUS_JASA | GABUNG_STATISTIK_ITEM | QUERY |
| GABUNG_STATISTIK_ITEM_TOTAL | GABUNG_STATISTIK_ITEM | QUERY |
| GABUNG_TBLITEM | TBLItemJenis_CIKDITIRO | TABLE |
| GABUNG_TBLITEM | TBLItemJenis_PACUL | TABLE |
| GABUNG_TBLITEM | TBLItemJenis_PESALAKAN | TABLE |
| GABUNG_TBLITEM | TBLItemJenis_PUSAT | TABLE |
| GABUNG_TBLITEM | TBLItemJenis_TRAYEMAN | TABLE |
| GABUNG_TBLITEM | TBLItem_CIKDITIRO | TABLE |
| GABUNG_TBLITEM | TBLItem_PACUL | TABLE |
| GABUNG_TBLITEM | TBLItem_PESALAKAN | TABLE |
| GABUNG_TBLITEM | TBLItem_PUSAT | TABLE |
| GABUNG_TBLITEM | TBLItem_TRAYEMAN | TABLE |
| GABUNG_TBLITEMJENIS | TBLItemJenis_CIKDITIRO | TABLE |
| GABUNG_TBLITEMJENIS | TBLItemJenis_PACUL | TABLE |
| GABUNG_TBLITEMJENIS | TBLItemJenis_PESALAKAN | TABLE |
| GABUNG_TBLITEMJENIS | TBLItemJenis_PUSAT | TABLE |
| GABUNG_TBLITEMJENIS | TBLItemJenis_TRAYEMAN | TABLE |
| GABUNG_TBLITEMKDETAIL | TBLItemKDetail_CIKDITIRO | TABLE |
| GABUNG_TBLITEMKDETAIL | TBLItemKDetail_PACUL | TABLE |
| GABUNG_TBLITEMKDETAIL | TBLItemKDetail_PESALAKAN | TABLE |
| GABUNG_TBLITEMKDETAIL | TBLItemKDetail_PUSAT | TABLE |
| GABUNG_TBLITEMKDETAIL | TBLItemKDetail_TRAYEMAN | TABLE |
| GABUNG_TBLITEMKHEADER | TBLItemKHeader_CIKDITIRO | TABLE |
| GABUNG_TBLITEMKHEADER | TBLItemKHeader_PACUL | TABLE |
| GABUNG_TBLITEMKHEADER | TBLItemKHeader_PESALAKAN | TABLE |
| GABUNG_TBLITEMKHEADER | TBLItemKHeader_PUSAT | TABLE |
| GABUNG_TBLITEMKHEADER | TBLItemKHeader_TRAYEMAN | TABLE |
| GABUNG_TBLITEMMDETAIL | TBLItemMDetail_CIKDITIRO | TABLE |
| GABUNG_TBLITEMMDETAIL | TBLItemMDetail_PACUL | TABLE |
| GABUNG_TBLITEMMDETAIL | TBLItemMDetail_PESALAKAN | TABLE |
| GABUNG_TBLITEMMDETAIL | TBLItemMDetail_PUSAT | TABLE |
| GABUNG_TBLITEMMDETAIL | TBLItemMDetail_TRAYEMAN | TABLE |
| GABUNG_TBLITEMMHEADER | TBLItemMHeader_CIKDITIRO | TABLE |
| GABUNG_TBLITEMMHEADER | TBLItemMHeader_PACUL | TABLE |
| GABUNG_TBLITEMMHEADER | TBLItemMHeader_PESALAKAN | TABLE |
| GABUNG_TBLITEMMHEADER | TBLItemMHeader_PUSAT | TABLE |
| GABUNG_TBLITEMMHEADER | TBLItemMHeader_TRAYEMAN | TABLE |
| GABUNG_TBLITEM_NEW | TBLItem_PACUL | TABLE |
| GABUNG_TBLITEM_ORDER | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| GABUNG_TBLITEM_PERBENGKEL | TBLItem_CIKDITIRO | TABLE |
| GABUNG_TBLITEM_PERBENGKEL | TBLItem_PACUL | TABLE |
| GABUNG_TBLITEM_PERBENGKEL | TBLItem_PESALAKAN | TABLE |
| GABUNG_TBLITEM_PERBENGKEL | TBLItem_PUSAT | TABLE |
| GABUNG_TBLITEM_PERBENGKEL | TBLItem_TRAYEMAN | TABLE |
| GABUNG_TBLKENDARAAN | TBLKendaraan_CIKDITIRO | TABLE |
| GABUNG_TBLKENDARAAN | TBLKendaraan_PACUL | TABLE |
| GABUNG_TBLKENDARAAN | TBLKendaraan_PESALAKAN | TABLE |
| GABUNG_TBLKENDARAAN | TBLKendaraan_PUSAT | TABLE |
| GABUNG_TBLKENDARAAN | TBLKendaraan_TRAYEMAN | TABLE |
| GABUNG_TBLMEKANIK | TBLMekanik_CIKDITIRO | TABLE |
| GABUNG_TBLMEKANIK | TBLMekanik_PACUL | TABLE |
| GABUNG_TBLMEKANIK | TBLMekanik_PESALAKAN | TABLE |
| GABUNG_TBLMEKANIK | TBLMekanik_PUSAT | TABLE |
| GABUNG_TBLMEKANIK | TBLMekanik_TRAYEMAN | TABLE |
| GABUNG_TBLPELANGGAN | TBLPelanggan_CIKDITIRO | TABLE |
| GABUNG_TBLPELANGGAN | TBLPelanggan_PACUL | TABLE |
| GABUNG_TBLPELANGGAN | TBLPelanggan_PESALAKAN | TABLE |
| GABUNG_TBLPELANGGAN | TBLPelanggan_PUSAT | TABLE |
| GABUNG_TBLPELANGGAN | TBLPelanggan_TRAYEMAN | TABLE |
| GABUNG_TBLPELANGGANGRUP | TBLPelangganGrup_CIKDITIRO | TABLE |
| GABUNG_TBLPELANGGANGRUP | TBLPelangganGrup_PACUL | TABLE |
| GABUNG_TBLPELANGGANGRUP | TBLPelangganGrup_PESALAKAN | TABLE |
| GABUNG_TBLPELANGGANGRUP | TBLPelangganGrup_PUSAT | TABLE |
| GABUNG_TBLPELANGGANGRUP | TBLPelangganGrup_TRAYEMAN | TABLE |
| GABUNG_TBLPENJUALAN_HPPSTS | TBLPENJUALAN_HPPSTS_CIKDITIRO | TABLE |
| GABUNG_TBLPENJUALAN_HPPSTS | TBLPENJUALAN_HPPSTS_PACUL | TABLE |
| GABUNG_TBLPENJUALAN_HPPSTS | TBLPENJUALAN_HPPSTS_PESALAKAN | TABLE |
| GABUNG_TBLPENJUALAN_HPPSTS | TBLPENJUALAN_HPPSTS_PUSAT | TABLE |
| GABUNG_TBLPENJUALAN_HPPSTS | TBLPENJUALAN_HPPSTS_TRAYEMAN | TABLE |
| GABUNG_TBLPenjualanDtFifo | TBLPenjualanDtFifo_CIKDITIRO | TABLE |
| GABUNG_TBLPenjualanDtFifo | TBLPenjualanDtFifo_PACUL | TABLE |
| GABUNG_TBLPenjualanDtFifo | TBLPenjualanDtFifo_PESALAKAN | TABLE |
| GABUNG_TBLPenjualanDtFifo | TBLPenjualanDtFifo_PUSAT | TABLE |
| GABUNG_TBLPenjualanDtFifo | TBLPenjualanDtFifo_TRAYEMAN | TABLE |
| GABUNG_TBLSERVICEDT_ITEM_JASA | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| GABUNG_TBLSERVICEDT_ITEM_JASA | GABUNG_TBLSERVICEITEMDT | QUERY |
| GABUNG_TBLSERVICEDT_ITEM_JASA | GABUNG_TBLSERVICEJASADT | QUERY |
| GABUNG_TBLSERVICEITEMDT | TBLServiceItemDt_CIKDITIRO | TABLE |
| GABUNG_TBLSERVICEITEMDT | TBLServiceItemDt_PACUL | TABLE |
| GABUNG_TBLSERVICEITEMDT | TBLServiceItemDt_PESALAKAN | TABLE |
| GABUNG_TBLSERVICEITEMDT | TBLServiceItemDt_PUSAT | TABLE |
| GABUNG_TBLSERVICEITEMDT | TBLServiceItemDt_TRAYEMAN | TABLE |
| GABUNG_TBLSERVICEITEMDTFIFO | TBLServiceItemDtFifo_CIKDITIRO | TABLE |
| GABUNG_TBLSERVICEITEMDTFIFO | TBLServiceItemDtFifo_PACUL | TABLE |
| GABUNG_TBLSERVICEITEMDTFIFO | TBLServiceItemDtFifo_PESALAKAN | TABLE |
| GABUNG_TBLSERVICEITEMDTFIFO | TBLServiceItemDtFifo_PUSAT | TABLE |
| GABUNG_TBLSERVICEITEMDTFIFO | TBLServiceItemDtFifo_TRAYEMAN | TABLE |
| GABUNG_TBLSERVICEJASADT | TBLServiceJasaDt_CIKDITIRO | TABLE |
| GABUNG_TBLSERVICEJASADT | TBLServiceJasaDt_PACUL | TABLE |
| GABUNG_TBLSERVICEJASADT | TBLServiceJasaDt_PESALAKAN | TABLE |
| GABUNG_TBLSERVICEJASADT | TBLServiceJasaDt_PUSAT | TABLE |
| GABUNG_TBLSERVICEJASADT | TBLServiceJasaDt_TRAYEMAN | TABLE |
| GABUNG_TBLSERVICE_ADVISOR | TBLService_Advisor_CIKDITIRO | TABLE |
| GABUNG_TBLSERVICE_ADVISOR | TBLService_Advisor_PACUL | TABLE |
| GABUNG_TBLSERVICE_ADVISOR | TBLService_Advisor_PESALAKAN | TABLE |
| GABUNG_TBLSERVICE_ADVISOR | TBLService_Advisor_PUSAT | TABLE |
| GABUNG_TBLSERVICE_ADVISOR | TBLService_Advisor_TRAYEMAN | TABLE |
| GABUNG_TBLSERVICE_HPPSTS | TBLSERVICE_HPPSTS_CIKDITIRO | TABLE |
| GABUNG_TBLSERVICE_HPPSTS | TBLSERVICE_HPPSTS_PACUL | TABLE |
| GABUNG_TBLSERVICE_HPPSTS | TBLSERVICE_HPPSTS_PESALAKAN | TABLE |
| GABUNG_TBLSERVICE_HPPSTS | TBLSERVICE_HPPSTS_PUSAT | TABLE |
| GABUNG_TBLSERVICE_HPPSTS | TBLSERVICE_HPPSTS_TRAYEMAN | TABLE |
| GABUNG_TBLSUPPLIER | TBLSupplier_CIKDITIRO | TABLE |
| GABUNG_TBLSUPPLIER | TBLSupplier_PACUL | TABLE |
| GABUNG_TBLSUPPLIER | TBLSupplier_PESALAKAN | TABLE |
| GABUNG_TBLSUPPLIER | TBLSupplier_PUSAT | TABLE |
| GABUNG_TBLSUPPLIER | TBLSupplier_TRAYEMAN | TABLE |
| GABUNG_TBLUSER | TBLUser_CIKDITIRO | TABLE |
| GABUNG_TBLUSER | TBLUser_PACUL | TABLE |
| GABUNG_TBLUSER | TBLUser_PESALAKAN | TABLE |
| GABUNG_TBLUSER | TBLUser_PUSAT | TABLE |
| GABUNG_TBLUSER | TBLUser_TRAYEMAN | TABLE |
| GANTI_OLI_DATA | GABUNG_SERVICE_ITEM | QUERY |
| GANTI_OLI_DATA | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| GANTI_OLI_KEDUA_TERAKHIR | GANTI_OLI_DATA | QUERY |
| GANTI_OLI_KEDUA_TERAKHIR | GANTI_OLI_TERAKHIR | QUERY |
| GANTI_OLI_MPX2_YMLB_ENDR | GABUNG_SERVICE_ITEM | QUERY |
| GANTI_OLI_STATISTIK | GANTI_OLI_KEDUA_TERAKHIR | QUERY |
| GANTI_OLI_STATISTIK | GANTI_OLI_TERAKHIR | QUERY |
| GANTI_OLI_TERAKHIR | GANTI_OLI_DATA | QUERY |
| HARI_KONSUMEN_DATANG | GABUNG_SERVICE_HEADER | QUERY |
| HISTORY_GARANSI | GABUNG_TBLMEKANIK | QUERY |
| HISTORY_GARANSI | HISTORY_SERVIS_DETAIL | QUERY |
| HISTORY_GARANSI | HISTORY_SERVIS_HEADER | QUERY |
| HISTORY_GARANSI_GABUNG | HISTORY_GARANSI | QUERY |
| HISTORY_GARANSI_GABUNG | HISTORY_GARANSI_SEBELUMNYA | QUERY |
| HISTORY_GARANSI_SEBELUMNYA | GABUNG_TBLMEKANIK | QUERY |
| HISTORY_GARANSI_SEBELUMNYA | HISTORY_GARANSI | QUERY |
| HISTORY_GARANSI_SEBELUMNYA | HISTORY_SERVIS_HEADER | QUERY |
| HISTORY_SERVIS_DETAIL | GABUNG_TBLSERVICEDT_ITEM_JASA | QUERY |
| HISTORY_SERVIS_HEADER | GABUNG_PELANGGAN | QUERY |
| HISTORY_SERVIS_HEADER | GABUNG_SERVICE_HEADER | QUERY |
| HISTORY_SERVIS_HEADER | GABUNG_TBLKENDARAAN | QUERY |
| HISTORY_SERVIS_HEADER | GABUNG_TBLMEKANIK | QUERY |
| HRGPOKOK_PENJUALAN_OK | GABUNG_PENJUALAN_DETAIL | QUERY |
| HRGPOKOK_PENJUALAN_OK | GABUNG_TBLPENJUALAN_HPPSTS | QUERY |
| HRGPOKOK_SERVICE_OK | GABUNG_TBLSERVICEITEMDTFIFO | QUERY |
| HRGPOKOK_SERVICE_OK | GABUNG_TBLSERVICE_HPPSTS | QUERY |
| INSENTIF_JUAL_SERVIS | GABUNG_TBLSERVICE_ADVISOR | QUERY |
| INSENTIF_JUAL_SERVIS | LABA_JUAL_SERVIS | QUERY |
| INSENTIF_JUAL_SERVIS | MEKANIK_ADMIN_JUAL_SERVIS | QUERY |
| INSENTIF_JUAL_SERVIS_ADMIN_PERSIKLUS | GABUNG_TBLUSER | QUERY |
| INSENTIF_JUAL_SERVIS_ADMIN_PERSIKLUS | INSENTIF_JUAL_SERVIS_GABUNG | QUERY |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM | GABUNG_TBLMEKANIK | QUERY |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM | GABUNG_TBLSERVICE_ADVISOR | QUERY |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM | LABA_JUAL_SERVIS | QUERY |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS | INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM | QUERY |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS_LAMA | GABUNG_TBLMEKANIK | QUERY |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERSIKLUS_LAMA | INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM | QUERY |
| INSENTIF_JUAL_SERVIS_ADVISOR_PERTANGGAL | INSENTIF_JUAL_SERVIS_ADVISOR_PERITEM | QUERY |
| INSENTIF_JUAL_SERVIS_GABUNG | INSENTIF_JUAL_SERVIS | QUERY |
| INSENTIF_JUAL_SERVIS_MEKANIK_PERSIKLUS | GABUNG_TBLMEKANIK | QUERY |
| INSENTIF_JUAL_SERVIS_MEKANIK_PERSIKLUS | INSENTIF_JUAL_SERVIS_GABUNG | QUERY |
| ITEM_JUAL_HARIAN | GABUNG_PENJUALAN | QUERY |
| ITEM_JUAL_HARIAN | GABUNG_SERVICE_ITEM | QUERY |
| ITEM_JUAL_HARIAN | GABUNG_TBLITEM | QUERY |
| ITEM_JUAL_HARIAN | GABUNG_TBLITEMJENIS | QUERY |
| ITEM_JUAL_HARIAN_TERAKHIR | ITEM_JUAL_HARIAN | QUERY |
| JUAL_SERVIS_HPPSTS_CEK | TBLPENJUALAN_HPPSTS_CEK | QUERY |
| JUAL_SERVIS_HPPSTS_CEK | TBLSERVICE_HPPSTS_CEK | QUERY |
| JUAL_SERVIS_HPPSTS_REKAP | TBLPENJUALAN_HPPSTS_REKAP | QUERY |
| JUAL_SERVIS_HPPSTS_REKAP | TBLSERVICE_HPPSTS_REKAP | QUERY |
| KARTU_GUDANG | GABUNG_PEMBELIAN | QUERY |
| KARTU_GUDANG | GABUNG_PENJUALAN | QUERY |
| KARTU_GUDANG | GABUNG_SERVISJUAL_PERBENGKEL | QUERY |
| KARTU_GUDANG | GABUNG_TBLITEMMDETAIL | QUERY |
| KARTU_GUDANG | GABUNG_TBLITEMMHEADER | QUERY |
| KARTU_GUDANG | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| KENDARAAN_PELANGGAN | GABUNG_PELANGGAN_PERCABANG | QUERY |
| KENDARAAN_PELANGGAN | GABUNG_TBLKENDARAAN | QUERY |
| KENDARAAN_PELANGGAN | GABUNG_TBLPELANGGANGRUP | QUERY |
| KENDARAAN_PELANGGAN | TIPEMOTOR | TABLE |
| KENDARAAN_PELANGGAN_CARI | KENDARAAN_PELANGGAN | QUERY |
| KENDARAAN_PELANGGAN_CARI_HISTORY | KENDARAAN_PELANGGAN_HISTORY | QUERY |
| KENDARAAN_PELANGGAN_GABUNG | GABUNG_TBLKENDARAAN | QUERY |
| KENDARAAN_PELANGGAN_GABUNG | GABUNG_TBLPELANGGAN | QUERY |
| KENDARAAN_PELANGGAN_GABUNG | GABUNG_TBLPELANGGANGRUP | QUERY |
| KENDARAAN_PELANGGAN_GABUNG | TIPEMOTOR | TABLE |
| KENDARAAN_PELANGGAN_GABUNG_TERBARU | GABUNG_TBLKENDARAAN | QUERY |
| KENDARAAN_PELANGGAN_GABUNG_TERBARU | GABUNG_TBLPELANGGAN | QUERY |
| KENDARAAN_PELANGGAN_GABUNG_TERBARU | GABUNG_TBLPELANGGANGRUP | QUERY |
| KENDARAAN_PELANGGAN_GABUNG_TERBARU | NOMOR_WA__DOMISILI_NOPOLISI | QUERY |
| KENDARAAN_PELANGGAN_GABUNG_TERBARU | REKAP_KONSUMEN_DATANGBERIKUTNYA | QUERY |
| KENDARAAN_PELANGGAN_GABUNG_TERBARU | TIPEMOTOR | TABLE |
| KENDARAAN_PELANGGAN_HEADER_HISTORY | GABUNG_SERVICE_HEADER | QUERY |
| KENDARAAN_PELANGGAN_HISTORY | GABUNG_TBLKENDARAAN | QUERY |
| KENDARAAN_PELANGGAN_HISTORY | GABUNG_TBLPELANGGAN | QUERY |
| LABARUGI_ACUAN_HPP_PEMBELIAN | GABUNG_PEMBELIAN | QUERY |
| LABARUGI_ACUAN_HPP_PEMBELIAN | GABUNG_TBLSUPPLIER | QUERY |
| LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX | LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP | TABLE |
| LABARUGI_ACUAN_QTY_ITEMKELUAR | REKAP_ITEM_KELUAR | QUERY |
| LABARUGI_ACUAN_QTY_ITEMMASUK | REKAP_ITEM_MASUK | QUERY |
| LABARUGI_ACUAN_QTY_PENJUALAN | GABUNG_PENJUALAN | QUERY |
| LABARUGI_ACUAN_QTY_SERVIS | GABUNG_SERVICE_ITEM | QUERY |
| LABARUGI_HPP_ITEMKELUAR | LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX | QUERY |
| LABARUGI_HPP_ITEMKELUAR | LABARUGI_ACUAN_QTY_ITEMKELUAR_TEMP | TABLE |
| LABARUGI_HPP_ITEMMASUK | LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX | QUERY |
| LABARUGI_HPP_ITEMMASUK | LABARUGI_ACUAN_QTY_ITEMMASUK_TEMP | TABLE |
| LABARUGI_HPP_PENJUALAN | LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX | QUERY |
| LABARUGI_HPP_PENJUALAN | LABARUGI_ACUAN_QTY_PENJUALAN_TEMP | TABLE |
| LABARUGI_HPP_SERVIS | LABARUGI_ACUAN_HPP_PEMBELIAN_TEMP_MAX | QUERY |
| LABARUGI_HPP_SERVIS | LABARUGI_ACUAN_QTY_SERVIS_TEMP | TABLE |
| LABARUGI_HPP_TOTAL | LABARUGI_HPP_ITEMKELUAR | QUERY |
| LABARUGI_HPP_TOTAL | LABARUGI_HPP_ITEMMASUK | QUERY |
| LABARUGI_HPP_TOTAL | LABARUGI_HPP_PENJUALAN | QUERY |
| LABARUGI_HPP_TOTAL | LABARUGI_HPP_SERVIS | QUERY |
| LABA_ITEM_PENJUALAN | GABUNG_PENJUALAN_DETAIL | QUERY |
| LABA_ITEM_PENJUALAN | GABUNG_PENJUALAN_HEADER | QUERY |
| LABA_ITEM_PENJUALAN | HRGPOKOK_PENJUALAN_OK | QUERY |
| LABA_ITEM_PENJUALAN | TBLCABANG | TABLE |
| LABA_ITEM_SERVICE | GABUNG_SERVICE_HEADER | QUERY |
| LABA_ITEM_SERVICE | GABUNG_TBLSERVICEITEMDT | QUERY |
| LABA_ITEM_SERVICE | HRGPOKOK_SERVICE_OK | QUERY |
| LABA_ITEM_SERVICE | TBLCABANG | TABLE |
| LABA_ITEM_SERVIS_PERJENIS_PERSIKLUS | GABUNG_TBLITEMJENIS | QUERY |
| LABA_ITEM_SERVIS_PERJENIS_PERSIKLUS | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| LABA_ITEM_SERVIS_PERJENIS_PERSIKLUS | LABA_ITEM_SERVICE | QUERY |
| LABA_JASA_SERVICE | GABUNG_SERVICE_HEADER | QUERY |
| LABA_JASA_SERVICE | GABUNG_TBLITEMJENIS | QUERY |
| LABA_JASA_SERVICE | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| LABA_JASA_SERVICE | GABUNG_TBLSERVICEJASADT | QUERY |
| LABA_JASA_SERVICE | GABUNG_TBLSERVICE_HPPSTS | QUERY |
| LABA_JASA_SERVICE | TBLCABANG | TABLE |
| LABA_JASA_SERVICE_OUTSOURCE | GABUNG_SERVICE_HEADER | QUERY |
| LABA_JASA_SERVICE_OUTSOURCE | GABUNG_TBLITEMJENIS | QUERY |
| LABA_JASA_SERVICE_OUTSOURCE | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| LABA_JASA_SERVICE_OUTSOURCE | GABUNG_TBLSERVICEJASADT | QUERY |
| LABA_JASA_SERVICE_OUTSOURCE | GABUNG_TBLSERVICE_HPPSTS | QUERY |
| LABA_JUAL_SERVIS | LABA_ITEM_PENJUALAN | QUERY |
| LABA_JUAL_SERVIS | LABA_ITEM_SERVICE | QUERY |
| LABA_JUAL_SERVIS | LABA_JASA_SERVICE | QUERY |
| LABA_JUAL_SERVIS_MINUS_PERBENGKEL | LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL | QUERY |
| LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL | GABUNG_PELANGGAN_PERCABANG | QUERY |
| LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL | GABUNG_PENJUALAN_HEADER | QUERY |
| LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL | GABUNG_TBLITEM | QUERY |
| LABA_JUAL_SERVIS_MINUS_PERBENGKEL_DETAIL | LABA_JUAL_SERVIS | QUERY |
| LABA_JUAL_SERVIS_PERTRX_1 | LABA_JUAL_SERVIS | QUERY |
| LABA_MEKANIK_PERSIKLUS | GABUNG_TBLMEKANIK | QUERY |
| LABA_MEKANIK_PERSIKLUS | INSENTIF_JUAL_SERVIS | QUERY |
| MEKANIK_ADMIN_DETAIL | GABUNG_SERVICE_HEADER | QUERY |
| MEKANIK_ADMIN_JUAL_SERVIS | GABUNG_PENJUALAN_HEADER | QUERY |
| MEKANIK_ADMIN_JUAL_SERVIS | GABUNG_TBLPENJUALAN_HPPSTS | QUERY |
| MEKANIK_ADMIN_JUAL_SERVIS | GABUNG_TBLSERVICE_HPPSTS | QUERY |
| MEKANIK_ADMIN_JUAL_SERVIS | MEKANIK_ADMIN_DETAIL | QUERY |
| MEKANIK_PERSERVIS | GABUNG_SERVICE_HEADER | QUERY |
| MEKANIK_PERSERVIS_CEK | GABUNG_SERVICE_HEADER | QUERY |
| MEKANIK_PERSERVIS_CEK | MEKANIK_PERSERVIS | QUERY |
| NOMOR_POLISI_SALAH_CEK | NOMOR_WA__DOMISILI_NOPOLISI | QUERY |
| NOMOR_WA | GABUNG_PELANGGAN_PERCABANG | QUERY |
| NOMOR_WA_DAFTAR_NOPOLISI | NOMOR_WA__DOMISILI_NOPOLISI | QUERY |
| NOMOR_WA_DOMISILI | NOMOR_WA_JUML_PERBENGKEL | QUERY |
| NOMOR_WA_DOMISILI | NOMOR_WA_TERAKHIR | QUERY |
| NOMOR_WA_GCONTACT_SEMUA | GABUNG_PELANGGAN_PERCABANG | QUERY |
| NOMOR_WA_GCONTACT_SEMUA | REKAP_KONSUMEN_NOMOR_WA | QUERY |
| NOMOR_WA_JUML_PERBENGKEL | GABUNG_PELANGGAN_PERCABANG | QUERY |
| NOMOR_WA_JUML_PERBENGKEL | GABUNG_SERVICE_HEADER | QUERY |
| NOMOR_WA_SALAH_CEK | NOMOR_WA_JUML_PERBENGKEL | QUERY |
| NOMOR_WA_TERAKHIR | GABUNG_PELANGGAN_PERCABANG | QUERY |
| NOMOR_WA_TERAKHIR | GABUNG_SERVICE_HEADER | QUERY |
| NOMOR_WA__DOMISILI_NOPOLISI | GABUNG_PELANGGAN_PERCABANG | QUERY |
| NOMOR_WA__DOMISILI_NOPOLISI | NOMOR_WA_DOMISILI | QUERY |
| NOPOLISI_DOMISILI | NOPOLISI_JUML_PERBENGKEL | QUERY |
| NOPOLISI_DOMISILI | NOPOLISI_TERAKHIR | QUERY |
| NOPOLISI_JUML_PERBENGKEL | GABUNG_SERVICE_HEADER | QUERY |
| NOPOLISI_JUML_PERBENGKEL_MAX | NOPOLISI_JUML_PERBENGKEL | QUERY |
| NOPOLISI_TERAKHIR | GABUNG_SERVICE_HEADER | QUERY |
| PEMBELIAN_PERITEM_PERSUPLIER | GABUNG_PEMBELIANDETAIL | QUERY |
| PEMBELIAN_PERITEM_PERSUPLIER | GABUNG_PEMBELIANHEADER | QUERY |
| PEMBELIAN_PERITEM_PERSUPLIER | GABUNG_TBLSUPPLIER | QUERY |
| PEMBELIAN_PERITEM_PERSUPLIER1 | PEMBELIAN_PERITEM_PERSUPLIER | QUERY |
| PEMBELIAN_PERITEM_PERSUPLIER2 | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| PEMBELIAN_PERITEM_PERSUPLIER_1_2 | PEMBELIAN_PERITEM_PERSUPLIER1 | QUERY |
| PEMBELIAN_PERITEM_PERSUPLIER_1_2 | PEMBELIAN_PERITEM_PERSUPLIER2 | QUERY |
| PENAWARAN_VOUCHER_REMAP | GABUNG_SERVICE_HEADER | QUERY |
| PENAWARAN_VOUCHER_REMAP | KENDARAAN_PELANGGAN_GABUNG | QUERY |
| PERNAH_GURAHMESIN | GABUNG_SERVICE_JASA | QUERY |
| PERNAH_GURAHMESIN | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| PERNAH_JEMPUTANTAR | GABUNG_SERVICE_JEMPUTANTAR | QUERY |
| PROGRAM_OLI_GRATIS | CEK_GRATIS_OLI_TERAKHIR | QUERY |
| PROGRAM_OLI_GRATIS | NOMOR_WA__DOMISILI_NOPOLISI | QUERY |
| PROGRAM_OLI_GRATIS | SERVIS_STANDAR | QUERY |
| QUERY_HITUNG_JASA_NET_PER_CABANG | GABUNG_SERVICE_JASA | QUERY |
| QUERY_HITUNG_JASA_NET_PER_CABANG | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| Q_DATA_MINMAX | GABUNG_STATISTIK_ITEM_MINGGU_TOT | QUERY |
| Q_DATA_MINMAX | GABUNG_STATISTIK_ITEM_TOTAL | QUERY |
| Q_DATA_MINMAX | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| Q_HITUNG_MINMAX | Q_DATA_MINMAX | QUERY |
| Q_HITUNG_MINMAX_LAMA | Q_DATA_MINMAX | QUERY |
| Q_HITUNG_MINMAX_STATUS | Q_HITUNG_MINMAX | QUERY |
| Query1 | GABUNG_SERVICE_HEADER | QUERY |
| Query1 | mei_juni | QUERY |
| Query2 | Desember_Mei | QUERY |
| REKAP_BELI_CABANG_SALAH | GABUNG_PEMBELIANHEADER | QUERY |
| REKAP_BELI_CABANG_SALAH | GABUNG_TBLSUPPLIER | QUERY |
| REKAP_BELI_PERSIKLUS_PERBENGKEL | GABUNG_PEMBELIANHEADER | QUERY |
| REKAP_BELI_PERSUPPLIER_PERBENGKEL | GABUNG_PEMBELIANHEADER | QUERY |
| REKAP_BELI_PERSUPPLIER_PERBENGKEL | GABUNG_TBLSUPPLIER | QUERY |
| REKAP_BELI_TIPE_PERSIKLUS_PERBENGKEL | GABUNG_PEMBELIANHEADER | QUERY |
| REKAP_BELI_TIPE_PERSIKLUS_PERBENGKEL | GABUNG_TBLSUPPLIER | QUERY |
| REKAP_BHP_ITEMKELUAR_PERSIKLUS_PERBENGKEL | BIAYA_HABIS_PAKAI | QUERY |
| REKAP_BHP_PERSIKLUS_PERBENGKEL | BIAYA_HABIS_PAKAI | QUERY |
| REKAP_HPPJUAL_PERSIKLUS_PERBENGKEL | GABUNG_PENJUALAN_HEADER | QUERY |
| REKAP_HPPJUAL_PERSIKLUS_PERBENGKEL | LABA_ITEM_PENJUALAN | QUERY |
| REKAP_HPPJUAL_TIPE_PERSIKLUS_PERBENGKEL | GABUNG_PELANGGAN | QUERY |
| REKAP_HPPJUAL_TIPE_PERSIKLUS_PERBENGKEL | GABUNG_PENJUALAN_HEADER | QUERY |
| REKAP_HPPJUAL_TIPE_PERSIKLUS_PERBENGKEL | LABA_ITEM_PENJUALAN | QUERY |
| REKAP_HPPSERVIS_PERSIKLUS_PERBENGKEL | LABA_ITEM_SERVICE | QUERY |
| REKAP_HUTANG | GABUNG_PEMBELIANHEADER | QUERY |
| REKAP_HUTANG | GABUNG_TBLSUPPLIER | QUERY |
| REKAP_ITEM_KELUAR | GABUNG_TBLITEMKDETAIL | QUERY |
| REKAP_ITEM_KELUAR | GABUNG_TBLITEMKHEADER | QUERY |
| REKAP_ITEM_MASUK | GABUNG_TBLITEMMDETAIL | QUERY |
| REKAP_ITEM_MASUK | GABUNG_TBLITEMMHEADER | QUERY |
| REKAP_ITEM_MASUK_KELUAR_PERSIKLUS | REKAP_ITEM_KELUAR | QUERY |
| REKAP_ITEM_MASUK_KELUAR_PERSIKLUS | REKAP_ITEM_MASUK | QUERY |
| REKAP_JUALSERVIS_PERITEM_TERAKHIR | GABUNG_PENJUALAN | QUERY |
| REKAP_JUALSERVIS_PERITEM_TERAKHIR | GABUNG_SERVICE_ITEM | QUERY |
| REKAP_JUAL_CABANG_SALAH | GABUNG_PELANGGAN | QUERY |
| REKAP_JUAL_CABANG_SALAH | GABUNG_PENJUALAN_HEADER | QUERY |
| REKAP_JUAL_PERSIKLUS_PERBENGKEL | GABUNG_PENJUALAN_HEADER | QUERY |
| REKAP_JUAL_TIPE_PERSIKLUS_PERBENGKEL | GABUNG_PELANGGAN | QUERY |
| REKAP_JUAL_TIPE_PERSIKLUS_PERBENGKEL | GABUNG_PENJUALAN_HEADER | QUERY |
| REKAP_KONSUMEN | GABUNG_SERVICE_HEADER | QUERY |
| REKAP_KONSUMEN_DATANGBERIKUTNYA | GANTI_OLI_STATISTIK | QUERY |
| REKAP_KONSUMEN_DATANGBERIKUTNYA | REKAP_KONSUMEN | QUERY |
| REKAP_KONSUMEN_DATANGBERIKUTNYA | SERVIS_STANDAR_STATISTIK | QUERY |
| REKAP_KONSUMEN_NOMOR_WA | NOMOR_WA__DOMISILI_NOPOLISI | QUERY |
| REKAP_KONSUMEN_NOMOR_WA | REKAP_KONSUMEN_DATANGBERIKUTNYA | QUERY |
| REKAP_KONSUMEN_NOMOR_WA_KATEGORI | REKAP_KONSUMEN_NOMOR_WA | QUERY |
| REKAP_KONSUMEN_NOMOR_WA_TERBARU | NOMOR_WA__DOMISILI_NOPOLISI | QUERY |
| REKAP_KONSUMEN_NOMOR_WA_TERBARU | REKAP_KONSUMEN_DATANGBERIKUTNYA | QUERY |
| REKAP_OUTSOURCE_PERSIKLUS_PERBENGKEL | LABA_JASA_SERVICE_OUTSOURCE | QUERY |
| REKAP_SERVIS_PERSIKLUS_PERBENGKEL | REKAP_SERVIS_PERTRANSAKSI_PERBENGKEL | QUERY |
| REKAP_SERVIS_PERTANGGAL_PERBENGKEL | REKAP_SERVIS_PERTRANSAKSI_PERBENGKEL | QUERY |
| REKAP_SERVIS_PERTRANSAKSI_PERBENGKEL | GABUNG_SERVICE_HEADER | QUERY |
| REKAP_STATISTIK_HARI_PERSIKLUS_PERBENGKEL | GABUNG_PEMBELIANHEADER | QUERY |
| REKAP_STATISTIK_HARI_PERSIKLUS_PERBENGKEL | GABUNG_PENJUALAN_HEADER | QUERY |
| REKAP_STATISTIK_HARI_PERSIKLUS_PERBENGKEL | GABUNG_SERVICE_HEADER | QUERY |
| REKAP_STOK_PERCABANG | GABUNG_TBLITEM | QUERY |
| REKAP_STOK_PERCABANG | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL | REKAP_BELI_PERSIKLUS_PERBENGKEL | QUERY |
| REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL | REKAP_BHP_ITEMKELUAR_PERSIKLUS_PERBENGKEL | QUERY |
| REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL | REKAP_HPPJUAL_PERSIKLUS_PERBENGKEL | QUERY |
| REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL | REKAP_HPPSERVIS_PERSIKLUS_PERBENGKEL | QUERY |
| REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL | REKAP_JUAL_PERSIKLUS_PERBENGKEL | QUERY |
| REKAP_TRANSAKSI_PERSIKLUS_PERBENGKEL | REKAP_SERVIS_PERSIKLUS_PERBENGKEL | QUERY |
| REKONSIL_BELI_ANTARCABANG | GABUNG_PEMBELIAN_NOFAKTUR | QUERY |
| REKONSIL_BELI_ANTARCABANG | GABUNG_TBLSUPPLIER | QUERY |
| REKONSIL_JUAL_ANTARCABANG | GABUNG_PELANGGAN_PERCABANG | QUERY |
| REKONSIL_JUAL_ANTARCABANG | GABUNG_PENJUALAN_DETAIL_HRGPOKOK | QUERY |
| REKONSIL_JUAL_ANTARCABANG | GABUNG_PENJUALAN_HEADER | QUERY |
| REKONSIL_JUAL_BELI_ANTARCABANG | REKONSIL_BELI_ANTARCABANG | QUERY |
| REKONSIL_JUAL_BELI_ANTARCABANG | REKONSIL_JUAL_ANTARCABANG | QUERY |
| REKONSIL_JUAL_BELI_ANTARCABANG_LAMA | GABUNG_PELANGGAN_PERCABANG | QUERY |
| REKONSIL_JUAL_BELI_ANTARCABANG_LAMA | GABUNG_PEMBELIAN_NOFAKTUR | QUERY |
| REKONSIL_JUAL_BELI_ANTARCABANG_LAMA | GABUNG_PENJUALAN_DETAIL_HRGPOKOK | QUERY |
| REKONSIL_JUAL_BELI_ANTARCABANG_LAMA | GABUNG_PENJUALAN_HEADER | QUERY |
| REKONSIL_JUAL_BELI_ANTARCABANG_LAMA | GABUNG_TBLSUPPLIER | QUERY |
| RESPONDEN_LAMA_TIDAK_DATANG | KENDARAAN_PELANGGAN_GABUNG | QUERY |
| RESPONDEN_LAMA_TIDAK_DATANG | REKAP_KONSUMEN_NOMOR_WA | QUERY |
| SERVIS_CVT | GABUNG_SERVICE_JASA | QUERY |
| SERVIS_CVT | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| SERVIS_CVT_BELUMPERNAH | GABUNG_SERVICE_JASA | QUERY |
| SERVIS_CVT_BELUMPERNAH | SERVIS_CVT_TERAKHIR | QUERY |
| SERVIS_CVT_TERAKHIR | SERVIS_CVT | QUERY |
| SERVIS_STANDAR | GABUNG_SERVICE_JASA | QUERY |
| SERVIS_STANDAR | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| SERVIS_STANDAR_KEDUA_TERAKHIR | SERVIS_STANDAR | QUERY |
| SERVIS_STANDAR_KEDUA_TERAKHIR | SERVIS_STANDAR_TERAKHIR | QUERY |
| SERVIS_STANDAR_STATISTIK | SERVIS_STANDAR_KEDUA_TERAKHIR | QUERY |
| SERVIS_STANDAR_STATISTIK | SERVIS_STANDAR_TERAKHIR | QUERY |
| SERVIS_STANDAR_STATISTIK | SERVIS_STANDAR_VOUCHER_JUMLAH | QUERY |
| SERVIS_STANDAR_TERAKHIR | SERVIS_STANDAR | QUERY |
| SERVIS_STANDAR_TERAKHIR_DISKON | GABUNG_SERVICE_JASA | QUERY |
| SERVIS_STANDAR_VOUCHER | SERVIS_STANDAR | QUERY |
| SERVIS_STANDAR_VOUCHER | SERVIS_STANDAR_TERAKHIR_DISKON | QUERY |
| SERVIS_STANDAR_VOUCHER_JUMLAH | SERVIS_STANDAR_VOUCHER | QUERY |
| SERVIS_TANGGAL_TERBARU_PERCABANG | GABUNG_SERVICE_HEADER | QUERY |
| STOK_OPNAM_DATA | STOK_OPNAM_ITEM | QUERY |
| STOK_OPNAM_DATA | STOK_OPNAM_SERVIS_JUAL | QUERY |
| STOK_OPNAM_DATA | STOK_OPNAM_STATUS | QUERY |
| STOK_OPNAM_DATA_TIDAKLAKU | STOK_OPNAM_DATA | QUERY |
| STOK_OPNAM_DATA_TIDAKLAKU | STOK_OPNAM_ITEM | QUERY |
| STOK_OPNAM_ITEM | TBLItem_PACUL | TABLE |
| STOK_OPNAM_ITEM | TBLItem_PESALAKAN | TABLE |
| STOK_OPNAM_SERVIS_JUAL | ITEM_JUAL_HARIAN | QUERY |
| STOK_OPNAM_STATUS | GABUNG_STATISTIK_ITEM_TOTAL | QUERY |
| TBLPENJUALAN_HPPSTS_CEK | GABUNG_PENJUALAN_HEADER | QUERY |
| TBLPENJUALAN_HPPSTS_CEK | GABUNG_TBLPENJUALAN_HPPSTS | QUERY |
| TBLPENJUALAN_HPPSTS_CEK | GABUNG_TBLPenjualanDtFifo | QUERY |
| TBLPENJUALAN_HPPSTS_REKAP | TBLPENJUALAN_HPPSTS_CEK | QUERY |
| TBLSERVICE_HPPSTS_BELUM_UPDATE | GABUNG_SERVICE_HEADER | QUERY |
| TBLSERVICE_HPPSTS_BELUM_UPDATE | GABUNG_TBLSERVICE_HPPSTS | QUERY |
| TBLSERVICE_HPPSTS_CEK | GABUNG_SERVICE_HEADER | QUERY |
| TBLSERVICE_HPPSTS_CEK | GABUNG_TBLSERVICEITEMDTFIFO | QUERY |
| TBLSERVICE_HPPSTS_CEK | GABUNG_TBLSERVICE_HPPSTS | QUERY |
| TBLSERVICE_HPPSTS_REKAP | TBLSERVICE_HPPSTS_CEK | QUERY |
| TBLSUPPLIER_LEADTIME_KREDIT | GABUNG_TBLSUPPLIER | QUERY |
| TIPE_MEMBER | NOPOLISI_DOMISILI | QUERY |
| TIPE_MEMBER | REKAP_KONSUMEN | QUERY |
| UPDATE_TIPE_MEMBER | REKAP_KONSUMEN_NOMOR_WA | QUERY |
| UPDATE_TIPE_MEMBER | TBLPelanggan_PRODUKSI | TABLE |
| mei_juni | GABUNG_SERVICE_HEADER | QUERY |
| rekap_unit | GABUNG_SERVICE_HEADER | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList0 | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList0 | LABA_ITEM_PENJUALAN | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList10 | GABUNG_SERVICE_HEADER | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList10 | GABUNG_TBLSERVICE_ADVISOR | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList4 | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList4 | LABA_ITEM_SERVICE | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList6 | GABUNG_SERVICE_HEADER | QUERY |
| ~sq_cFR_CEK_DATA_KINERJA~sq_cList8 | GABUNG_SERVICE_HEADER | QUERY |
| ~sq_cFR_DOWNLOAD_STOK_OPNAM~sq_cPILIH_CABANG | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cFR_KENDARAAN_CARI~sq_cSearchResults | KENDARAAN_PELANGGAN_CARI | QUERY |
| ~sq_cFR_KENDARAAN_DAFTARMOTOR_HISTORY~sq_cDaftarMotor | REKAP_KONSUMEN | QUERY |
| ~sq_cFR_KENDARAAN_DETAIL_HISTORY_LAMA~sq_cListBarang | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cFR_KENDARAAN_DETAIL_HISTORY_LAMA~sq_cListBarang | GABUNG_TBLSERVICEITEMDT | QUERY |
| ~sq_cFR_KENDARAAN_DETAIL_HISTORY_LAMA~sq_cListJasa | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cFR_KENDARAAN_DETAIL_HISTORY_LAMA~sq_cListJasa | GABUNG_TBLSERVICEJASADT | QUERY |
| ~sq_cFR_KENDARAAN_HEADER_HISTORY~sq_cListBarang | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cFR_KENDARAAN_HEADER_HISTORY~sq_cListBarang | GABUNG_TBLSERVICEITEMDT | QUERY |
| ~sq_cFR_KENDARAAN_HEADER_HISTORY~sq_cListJasa | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cFR_KENDARAAN_HEADER_HISTORY~sq_cListJasa | GABUNG_TBLSERVICEJASADT | QUERY |
| ~sq_cFR_KENDARAAN_HEADER_HISTORY~sq_cListJasa | KENDARAAN_PELANGGAN_HEADER_HISTORY | QUERY |
| ~sq_cFR_KENDARAAN_PELANGGAN_HISTORY~sq_cListHistory | GABUNG_SERVICE_HEADER | QUERY |
| ~sq_cFR_LABARUGI_HITUNG_HPP~sq_cCBCABANG | TBLCABANG | TABLE |
| ~sq_cFR_LABARUGI_HITUNG_HPP~sq_cCBSIKLUS | GABUNG_SIKLUS | QUERY |
| ~sq_cFR_NOMOR_WA_GCONTACT_DEPAN~sq_cList11 | NOMOR_WA_GCONTACT_SEMUA_DATA | TABLE |
| ~sq_cFR_UPLOAD_STOK_OPNAM~sq_cPILIH_CABANG | GABUNG_TBLITEM_PERBENGKEL | QUERY |
| ~sq_cfLogin~sq_cT1 | GABUNG_TBLUSER | QUERY |
| ~sq_fFR_KENDARAAN_PELANGGAN | KENDARAAN_PELANGGAN | QUERY |
| ~sq_fSwitchboard | Switchboard Items | TABLE |

## F. SQL / Perubahan yang Disiapkan

- SQL fix struktur tabel: `db/migrations/2026-06-24_fitmotor_access_table_fix.sql`
- SQL create/replace view: `db/migrations/2026-06-24_fitmotor_access_views.sql`
- Data audit lengkap JSON: `docs/audit/FITMOTOR_ACCESS_MYSQL_AUDIT_DATA.json`

## G. Progress Log

- Sudah diaudit: metadata tabel/field/index Access, query SQL Access, schema tabel/view MySQL.
- Sudah disiapkan: mapping tabel, mapping field, mapping query/view, dependency query, SQL rekonsiliasi tabel, SQL view best-effort.
- Masih terbuka: review manual query Access dengan sintaks `PARAMETERS`, `TRANSFORM`, referensi form/report, operator concat `&`, dan query internal `~sq_...`.
- Validasi berikutnya: jalankan SQL di database staging, lalu ulangi audit untuk memastikan status query/view berubah menjadi `OK` dan output kolom view sama persis.

## H. Open Questions / Risks

- Script rename field memakai heuristik nama mirip; review setiap `LIKELY_RENAME` sebelum eksekusi.
- Field ekstra MySQL tidak otomatis di-drop karena bisa dipakai modul web baru; tandai dulu sebelum diputuskan.
- Query Access yang sangat kompleks tetap ditandai `MANUAL_REVIEW` agar tidak salah terjemah.