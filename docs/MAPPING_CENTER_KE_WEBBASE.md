# Pemetaan fitmotor_center → fitmotor_dbbengkel (Web Base)

> **Dokumen ini DIGENERATE** dari `scripts/center_reload/reload_cabang.php` oleh `scripts/center_reload/generate_mapping_doc.php`. Jangan edit tangan; ubah kode lalu jalankan ulang generator.
> Konteks keputusan, konflik master, dan hasil per sesi: `docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md`. Audit struktur: `docs/planning/AUDIT_DATABASE_CENTER_VS_DBBENGKEL_2026-11-15.md`.

## 1. Prinsip

- Sumber `fitmotor_center` (warehouse 5 MDB Access) dibaca **read-only**. Satu cabang dipilih lewat `tbcabang.cabang_ref_kode` (nilai `{ref}` pada query sumber).
- Target `fitmotor_dbbengkel`: kolom `kd_cabang` diisi kode kanonik (`PESALAKAN`, `PACUL`, `CIKDITIRO`, `TRAYEMAN`, `PST`).
- Data lama hasil impor Access (kode lama `PSL`, `PCL`, `CDT`, `TRM`) **dibuang** dan diganti isi center. Baris asli input web (kode kanonik yang sudah ada sebelum reload pertama) **dipertahankan**. Bila nomornya bentrok, baris center dilewati dan dicatat.
- Sebelum hapus, baris cabang itu disalin sekali ke `bak_cr<MMDD>_<CABANG>_<tabel>` (tidak ditimpa). `--mode=rollback` mengembalikannya.
- Master global (pelanggan, kendaraan, item, supplier) **hanya ditambah bila belum ada**, tidak menimpa. Master mekanik tidak dimuat karena `tblmekanik` di web adalah VIEW.
- Tiap dataset berjalan dalam satu transaksi; gagal = rollback dataset itu. Hasil dicatat di tabel `center_reload_log`.
- Kolom target yang tidak dipetakan diisi `lengkapi()`: NOT NULL tanpa default → `0` / `''` / `1970-01-01`; teks dipotong ke panjang kolom; tanggal kosong → NULL bila boleh.
- Koneksi memakai charset `latin1` di sumber dan target.

## 2. Fungsi pembantu pada kolom Ekspresi

| Fungsi | Arti |
|---|---|
| `s(x, n)` | trim teks, dipotong `n` karakter bila `n` diberikan |
| `n(x)` | angka desimal, kosong/NULL → 0 |
| `i(x)` | `round(n(x))` jadi integer |
| `d(x)` | tanggal `YYYY-MM-DD`; kosong atau `0000...` → `''` |
| `hm(x)` | jam `HH:MM` dari datetime |
| `lunas(r, kolom)` | `'1'` bila `Pembayaran + JumlahBayar >= total - 1`, selain itu `'0'` |
| `status_servis(st)` | Access `3` → status `4` + `bayar`; `2` → `2` + `diproses`; lainnya → `1` + `datang` |
| `$CAB` | kode cabang kanonik yang sedang direload |

## 3. Ringkasan tabel

| Dataset | Tabel center (sumber) | Tabel web (target) | Kunci baris | Jenis |
|---|---|---|---|---|
| `servis_jemputantar` | tblservice_jemputantar | `tblservice_jemputantar` | `no_service` | transaksi (hapus + isi ulang) |
| `servis_header` | tblservice | `tblservice` | `no_service` | transaksi (hapus + isi ulang) |
| `servis_barang` | tblserviceitemdt, tblserviceitemdtfifo | `tblservis_barang` | `no_service, nobaris, no_item` | transaksi (hapus + isi ulang) |
| `servis_jasa` | tblservicejasadt | `tblservis_jasa` | `no_service, nobaris, no_item` | transaksi (hapus + isi ulang) |
| `servis_advisor` | tblservice_advisor | `tblservice_advisor` | `no_service` | transaksi (hapus + isi ulang) |
| `beli_header` | tblpembelianheader | `tblpembelian_header` | `notransaksi` | transaksi (hapus + isi ulang) |
| `beli_detail` | tblpembeliandetail | `tblpembelian_detail` | `no_transaksi, nobaris, no_item` | transaksi (hapus + isi ulang) |
| `jual_header` | tblpenjualanheader | `tblpenjualan_header` | `notransaksi` | transaksi (hapus + isi ulang) |
| `jual_detail` | tblpenjualandetail | `tblpenjualan_detail` | `no_transaksi, nobaris, no_item` | transaksi (hapus + isi ulang) |
| `po_header` | tblorderheader | `tblorder_header` | `no_order` | transaksi (hapus + isi ulang) |
| `po_detail` | tblorderdetail | `tblorder_detail` | `no_order, nobaris, no_item` | transaksi (hapus + isi ulang) |
| `so_header` | tblorderjualheader | `tblorderjual_header` | `no_order` | transaksi (hapus + isi ulang) |
| `so_detail` | tblorderjualdetail | `tblorderjual_detail` | `no_order, nobaris, no_item` | transaksi (hapus + isi ulang) |
| `hutang_header` | tblhutangheader | `tblhutang_header` | `no_transaksi` | transaksi (hapus + isi ulang) |
| `hutang_detail` | tblhutangdetail | `tblhutang_detail` | `no_transaksi, nobaris` | transaksi (hapus + isi ulang) |
| `piutang_header` | tblpiutangheader | `tblpiutang_header` | `no_transaksi` | transaksi (hapus + isi ulang) |
| `piutang_detail` | tblpiutangdetail | `tblpiutang_detail` | `no_transaksi, nobaris` | transaksi (hapus + isi ulang) |
| `retur_beli_header` | tblreturbeliheader | `tblretur_pembelian_header` | `noretur` | transaksi (hapus + isi ulang) |
| `retur_beli_detail` | tblreturbelidetail, tblreturbeliheader | `tblretur_pembelian_detail` | `no_retur, no_item` | transaksi (hapus + isi ulang) |
| `kas` | tblkaskeluarmasuk | `tblkas_keluar_masuk` | `kode_km` | transaksi (hapus + isi ulang) |
| `harga_cabang` | tblitem | `tblitem_harga_cabang` | `noitem` | transaksi (hapus + isi ulang) |
| `master_pelanggan` | tblpelanggan | `tblpelanggan` | `nopelanggan` | master (hanya tambah) |
| `master_kendaraan` | tblkendaraan | `tblkendaraan` | `nopolisi` | master (hanya tambah) |
| `master_item` | tblitem | `tblitem` | `noitem` | master (hanya tambah) |
| `master_supplier` | tblsupplier | `tblsupplier` | `nosupplier` | master (hanya tambah) |
| `item_masuk_header` | tblitemmheader | `tbitem_masuk_header` | `no_transaksi` | transaksi (hapus + isi ulang) |
| `item_keluar_header` | tblitemkheader | `tbitem_keluar_header` | `no_transaksi` | transaksi (hapus + isi ulang) |
| `item_masuk_detail` | tblitemmdetail | `tbitem_masuk_detail` | `no_transaksi, no_item` | transaksi (hapus + isi ulang) |
| `item_keluar_detail` | tblitemkdetail | `tbitem_keluar_detail` | `no_transaksi, no_item` | transaksi (hapus + isi ulang) |
| `stok` | tblpembeliandetail, tblpenjualandetail, tblserviceitemdt, tblitemmdetail, tblitemkdetail, tblreturbelidetail, tblitem | `tbstok` | - | dibangun ulang (bagian 5) |

## 4. Pemetaan kolom per dataset

### `servis_jemputantar` → `tblservice_jemputantar`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_service`
- Query sumber: `SELECT * FROM tblservice_jemputantar WHERE kode_cabang='{ref}'`
- Baris dobel pada sumber (kunci sama) dilewati karena target punya UNIQUE pada (kunci, kd_cabang).

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_service` | `NoService` | turunan | `s($r['NoService'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `no_pelanggan` | `NoPelanggan` | turunan | `s($r['NoPelanggan'], 20)` |
| `no_polisi` | `NoPolisi` | turunan | `s($r['NoPolisi'], 20)` |
| `jam` | `JAM` | turunan | `strlen((string)$r['JAM']) >= 16 ? substr($r['JAM'], 11, 8) : null` |
| `keterangan` | `Keterangan` | turunan | `s($r['Keterangan'], 255)` |

### `servis_header` → `tblservice`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_service`
- Query sumber: `SELECT * FROM tblservice WHERE kode_cabang='{ref}'`
- Baris dobel pada sumber (kunci sama) dilewati karena target punya UNIQUE pada (kunci, kd_cabang).

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_service` | `NoService` | turunan | `s($r['NoService'])` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `jam` | `Tanggal` | turunan | `hm($r['Tanggal'])` |
| `no_pelanggan` | `NoPelanggan` | turunan | `s($r['NoPelanggan'], 20)` |
| `no_polisi` | `NoPolisi` | turunan | `s($r['NoPolisi'], 20)` |
| `status` | - | konstanta / default | `$st` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `id_user` | - | konstanta / default | `1` |
| `total` | `SubTotal` | turunan | `n($r['SubTotal'])` |
| `diskon_persen` | `Diskon` | turunan | `i($r['Diskon'])` |
| `diskon_nom` | `TotalDiskon` | turunan | `n($r['TotalDiskon'])` |
| `ppn_persen` | `Pajak` | turunan | `i($r['Pajak'])` |
| `ppn_nom` | `TotalPajak` | turunan | `n($r['TotalPajak'])` |
| `total_grand` | `TotalAkhir` | turunan | `n($r['TotalAkhir'])` |
| `bayar` | `Pembayaran` | turunan | `n($r['Pembayaran'])` |
| `kembali` | - | konstanta / default | `0` |
| `jenis_bayar` | - | konstanta / default | `1` |
| `total_waktu` | `TotalWaktu` | turunan | `i($r['TotalWaktu'])` |
| `status_jemput` | `NoService` | turunan | `isset($JEMPUT[s($r['NoService'])]) ? '1' : '0'` |
| `keterangan` | - | konstanta / default | `substr($ket, 0, 100)` |
| `km_skr` | `KmSekarang` | turunan | `i($r['KmSekarang'])` |
| `mekanik1` | `Mekanik1` | turunan | `s($r['Mekanik1'], 50)` |
| `mekanik2` | `Mekanik2` | turunan | `s($r['Mekanik2'], 50)` |
| `mekanik3` | `Mekanik3` | turunan | `s($r['Mekanik3'], 50)` |
| `mekanik4` | `Mekanik4` | turunan | `s($r['Mekanik4'], 50)` |
| `biayaM1` | `BiayaM1` | turunan | `n($r['BiayaM1'])` |
| `biayaM2` | `BiayaM2` | turunan | `n($r['BiayaM2'])` |
| `biayaM3` | `BiayaM3` | turunan | `n($r['BiayaM3'])` |
| `biayaM4` | `BiayaM4` | turunan | `n($r['BiayaM4'])` |
| `km_berikut` | `KmBerikut` | turunan | `i($r['KmBerikut'])` |
| `subtotal_jasa` | `SubTotalJasa` | turunan | `n($r['SubTotalJasa'])` |
| `subtotal_item` | `SubTotalItem` | turunan | `n($r['SubTotalItem'])` |
| `subtotal` | `SubTotal` | turunan | `n($r['SubTotal'])` |
| `total_diskon` | `TotalDiskon` | turunan | `n($r['TotalDiskon'])` |
| `total_pajak` | `TotalPajak` | turunan | `n($r['TotalPajak'])` |
| `total_akhir` | `TotalAkhir` | turunan | `n($r['TotalAkhir'])` |
| `status_servis` | - | konstanta / default | `$sts` |
| `boleh_dp` | - | konstanta / default | `0` |
| `metode_pembayaran` | - | konstanta / default | `'Tunai'` |
| `total_retur` | - | konstanta / default | `0` |
| `catatan` | - | konstanta / default | `strlen($ket) > 100 ? $ket : null` |
| `created_at` | `Tanggal` | turunan | `s($r['Tanggal']) ?: null` |

### `servis_barang` → `tblservis_barang`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_service, nobaris, no_item`
- Query sumber: `SELECT d.*, f.hpp FROM tblserviceitemdt d LEFT JOIN (SELECT NoTransaksi, NoItem, SUM(HargaPokok * Jumlah) / NULLIF(SUM(Jumlah), 0) hpp FROM tblserviceitemdtfifo WHERE kode_cabang='{ref}' GROUP BY NoTransaksi, NoItem) f ON f.NoTransaksi = d.NoService AND f.NoItem = d.NoItem WHERE d.kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_service` | `NoService` | turunan | `s($r['NoService'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 50)` |
| `quantity` | `Quantity` | turunan | `i($r['Quantity'])` |
| `qty_retur` | `QtyRetur` | turunan | `i($r['QtyRetur'])` |
| `harga_jual` | `Harga` | turunan | `n($r['Harga'])` |
| `potongan` | `Potongan` | turunan | `n($r['Potongan'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `asal_barang` | - | konstanta / default | `'SERVIS'` |
| `harga_pokok` | `hpp` | turunan | `round(n($r['hpp']), 2)` |

### `servis_jasa` → `tblservis_jasa`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_service, nobaris, no_item`
- Query sumber: `SELECT * FROM tblservicejasadt WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_service` | `NoService` | turunan | `s($r['NoService'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 50)` |
| `waktu` | `Waktu` | turunan | `i($r['Waktu'])` |
| `harga` | `Harga` | turunan | `n($r['Harga'])` |
| `potongan` | `Potongan` | turunan | `n($r['Potongan'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `qty_retur` | - | konstanta / default | `0` |

### `servis_advisor` → `tblservice_advisor`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_service`
- Query sumber: `SELECT * FROM tblservice_advisor WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_service` | `NoService` | turunan | `s($r['NoService'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `advisor` | `Advisor` | turunan | `s($r['Advisor'], 20)]; }` |

### `beli_header` → `tblpembelian_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `notransaksi`
- Query sumber: `SELECT * FROM tblpembelianheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `notransaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `status` | `Status` | turunan | `s($r['Status'], 50)` |
| `carabayar` | `CaraBayar` | turunan | `s($r['CaraBayar'], 50)` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `no_order` | `NoOrder` | turunan | `s($r['NoOrder'], 50)` |
| `tanggal_order` | `TanggalOrder` | turunan | `d($r['TanggalOrder'])` |
| `no_supplier` | `NoSupplier` | turunan | `s($r['NoSupplier'], 20)` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `total_qty_order` | `TotalQtyOrder` | turunan | `i($r['TotalQtyOrder'])` |
| `total_qty` | `TotalQty` | turunan | `i($r['TotalQty'])` |
| `total_beli` | `TotalBeli` | turunan | `n($r['TotalBeli'])` |
| `diskon` | `Diskon` | turunan | `n($r['Diskon'])` |
| `total_diskon` | `TotalDiskon` | turunan | `n($r['TotalDiskon'])` |
| `pajak` | `Pajak` | turunan | `n($r['Pajak'])` |
| `total_pajak` | `TotalPajak` | turunan | `n($r['TotalPajak'])` |
| `total_akhir` | `TotalAkhir` | turunan | `n($r['TotalAkhir'])` |
| `total_retur` | `TotalRetur` | turunan | `n($r['TotalRetur'])` |
| `pembayaran` | `Pembayaran` | turunan | `n($r['Pembayaran'])` |
| `tanggal_jt` | `TanggalJT` | turunan | `d($r['TanggalJT'])` |
| `tanggal_lunas` | `TanggalLunas` | turunan | `d($r['TanggalLunas'])` |
| `jumlah_bayar` | `JumlahBayar` | turunan | `i($r['JumlahBayar'])` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `Id_tabel` | `IDTabel` | turunan | `s($r['IDTabel'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_lunas` | - | konstanta / default | `lunas($r, 'TotalAkhir')` |
| `lama_hari` | - | konstanta / default | `0` |

### `beli_detail` → `tblpembelian_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi, nobaris, no_item`
- Query sumber: `SELECT * FROM tblpembeliandetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 20)` |
| `qty_order` | `QtyOrder` | turunan | `i($r['QtyOrder'])` |
| `quantity` | `Quantity` | turunan | `i($r['Quantity'])` |
| `qty_retur` | `QtyRetur` | turunan | `i($r['QtyRetur'])` |
| `harga_pokok` | `HargaPokok` | turunan | `n($r['HargaPokok'])` |
| `potongan` | `Potongan` | turunan | `n($r['Potongan'])` |
| `harga_sp` | `HargaSP` | turunan | `n($r['HargaSP'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `sts_order` | `StsOrder` | turunan | `(string)i($r['StsOrder'])` |
| `id_inv` | `IDInv` | turunan | `s($r['IDInv'], 50)` |
| `user` | - | konstanta / default | `''` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_trx` | - | konstanta / default | `'1'` |

### `jual_header` → `tblpenjualan_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `notransaksi`
- Query sumber: `SELECT * FROM tblpenjualanheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `notransaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `status` | `Status` | turunan | `s($r['Status'], 50)` |
| `carabayar` | `CaraBayar` | turunan | `s($r['CaraBayar'], 50)` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `no_order` | `NoOrder` | turunan | `s($r['NoOrder'], 50)` |
| `tanggal_order` | `TanggalOrder` | turunan | `d($r['TanggalOrder'])` |
| `no_sales` | `NoSales` | turunan | `s($r['NoSales'], 50)` |
| `no_pelanggan` | `NoPelanggan` | turunan | `s($r['NoPelanggan'], 50)` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `total_qty_order` | `TotalQtyOrder` | turunan | `i($r['TotalQtyOrder'])` |
| `total_qty` | `TotalQty` | turunan | `i($r['TotalQty'])` |
| `total_jual` | `TotalJual` | turunan | `n($r['TotalJual'])` |
| `diskon` | `Diskon` | turunan | `n($r['Diskon'])` |
| `total_diskon` | `TotalDiskon` | turunan | `n($r['TotalDiskon'])` |
| `pajak` | `Pajak` | turunan | `n($r['Pajak'])` |
| `total_pajak` | `TotalPajak` | turunan | `n($r['TotalPajak'])` |
| `total_akhir` | `TotalAkhir` | turunan | `n($r['TotalAkhir'])` |
| `total_retur` | `TotalRetur` | turunan | `n($r['TotalRetur'])` |
| `pembayaran` | `Pembayaran` | turunan | `n($r['Pembayaran'])` |
| `tanggal_jt` | `TanggalJT` | turunan | `d($r['TanggalJT'])` |
| `tanggal_lunas` | `TanggalLunas` | turunan | `d($r['TanggalLunas'])` |
| `jumlah_bayar` | `JumlahBayar` | turunan | `i($r['JumlahBayar'])` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `id_tabel` | `IDTabel` | turunan | `s($r['IDTabel'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_lunas` | - | konstanta / default | `lunas($r, 'TotalAkhir')` |
| `lama_hari` | - | konstanta / default | `0` |

### `jual_detail` → `tblpenjualan_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi, nobaris, no_item`
- Query sumber: `SELECT * FROM tblpenjualandetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 20)` |
| `qty_order` | `QtyOrder` | turunan | `i($r['QtyOrder'])` |
| `quantity` | `Quantity` | turunan | `i($r['Quantity'])` |
| `qty_retur` | `QtyRetur` | turunan | `i($r['QtyRetur'])` |
| `harga_jual` | `HargaJual` | turunan | `n($r['HargaJual'])` |
| `potongan` | `Potongan` | turunan | `n($r['Potongan'])` |
| `harga_sp` | `HargaSP` | turunan | `n($r['HargaSP'])` |
| `harga_pokok` | `HargaPokok` | turunan | `n($r['HargaPokok'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `sts_order` | `StsOrder` | turunan | `(string)i($r['StsOrder'])` |
| `user` | - | konstanta / default | `''` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_trx` | - | konstanta / default | `'1'` |

### `po_header` → `tblorder_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_order`
- Query sumber: `SELECT * FROM tblorderheader WHERE kode_cabang='{ref}'`
- Baris dobel pada sumber (kunci sama) dilewati karena target punya UNIQUE pada (kunci, kd_cabang).

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_order` | `NoOrder` | turunan | `s($r['NoOrder'])` |
| `status` | `Status` | turunan | `s($r['Status'], 50)` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `tglkirim` | `TglKirim` | turunan | `d($r['TglKirim'])` |
| `no_supplier` | `NoSupplier` | turunan | `s($r['NoSupplier'], 20)` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `total_qty` | `TotalQty` | turunan | `i($r['TotalQty'])` |
| `total_terima` | `TotalTerima` | turunan | `n($r['TotalTerima'])` |
| `total_order` | `TotalOrder` | turunan | `n($r['TotalOrder'])` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `Id_tabel` | `IDTabel` | turunan | `s($r['IDTabel'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_pesanan` | `TotalTerima`, `TotalQty` | turunan | `n($r['TotalTerima']) >= n($r['TotalQty']) && n($r['TotalQty']) > 0 ? '1' : '0'` |
| `tipe_trx` | - | konstanta / default | `'ACCESS'` |
| `no_penjualan` | - | konstanta / default | `''` |
| `status_approval` | - | konstanta / default | `'approved'` |
| `po_type` | - | konstanta / default | `'regular'` |

### `po_detail` → `tblorder_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_order, nobaris, no_item`
- Query sumber: `SELECT * FROM tblorderdetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_order` | `NoOrder` | turunan | `s($r['NoOrder'])` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 20)` |
| `quantity` | `Quantity` | turunan | `i($r['Quantity'])` |
| `qty_terima` | `QtyTerima` | turunan | `i($r['QtyTerima'])` |
| `harga_pokok` | `HargaPokok` | turunan | `n($r['HargaPokok'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `user` | - | konstanta / default | `''` |
| `status_trx` | - | konstanta / default | `'1'` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |

### `so_header` → `tblorderjual_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_order`
- Query sumber: `SELECT * FROM tblorderjualheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_order` | `NoOrder` | turunan | `s($r['NoOrder'])` |
| `status` | `Status` | turunan | `s($r['Status'], 50)` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `no_sales` | `NoSales` | turunan | `s($r['NoSales'], 50)` |
| `no_pelanggan` | `NoPelanggan` | turunan | `s($r['NoPelanggan'], 50)` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `total_qty` | `TotalQty` | turunan | `i($r['TotalQty'])` |
| `total_terima` | `TotalTerima` | turunan | `i($r['TotalTerima'])` |
| `total_jual` | `TotalJual` | turunan | `n($r['TotalJual'])` |
| `diskon` | `Diskon` | turunan | `i($r['Diskon'])` |
| `total_diskon` | `TotalDiskon` | turunan | `n($r['TotalDiskon'])` |
| `pajak` | `Pajak` | turunan | `i($r['Pajak'])` |
| `total_pajak` | `TotalPajak` | turunan | `n($r['TotalPajak'])` |
| `total_akhir` | `TotalAkhir` | turunan | `n($r['TotalAkhir'])` |
| `pembayaran` | `Pembayaran` | turunan | `n($r['Pembayaran'])` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `id_tabel` | `IDTabel` | turunan | `s($r['IDTabel'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `tipe_trx` | - | konstanta / default | `'ACCESS'` |
| `order_ke` | - | konstanta / default | `''` |
| `status_pesanan` | `TotalTerima`, `TotalQty` | turunan | `n($r['TotalTerima']) >= n($r['TotalQty']) && n($r['TotalQty']) > 0 ? '1' : '0'` |

### `so_detail` → `tblorderjual_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_order, nobaris, no_item`
- Query sumber: `SELECT * FROM tblorderjualdetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_order` | `NoOrder` | turunan | `s($r['NoOrder'])` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 50)` |
| `quantity` | `Quantity` | turunan | `i($r['Quantity'])` |
| `qty_terima` | `QtyTerima` | turunan | `i($r['QtyTerima'])` |
| `harga_jual` | `HargaJual` | turunan | `n($r['HargaJual'])` |
| `potongan` | `Potongan` | turunan | `n($r['Potongan'])` |
| `harga_sp` | `HargaSP` | turunan | `n($r['HargaSP'])` |
| `harga_pokok` | `HargaPokok` | turunan | `n($r['HargaPokok'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `user` | - | konstanta / default | `''` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_trx` | - | konstanta / default | `'1'` |
| `margin_jual` | - | konstanta / default | `0` |

### `hutang_header` → `tblhutang_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi`
- Query sumber: `SELECT * FROM tblhutangheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `no_supplier` | `NoSupplier` | turunan | `s($r['NoSupplier'], 50)` |
| `note` | `Note` | turunan | `s($r['Note'], 50)` |
| `total_bayar` | `TotalBayar` | turunan | `n($r['TotalBayar'])` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `id_tabel` | `IDTabel` | turunan | `s($r['IDTabel'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |

### `hutang_detail` → `tblhutang_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi, nobaris`
- Query sumber: `SELECT * FROM tblhutangdetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_pembelian` | `NoPembelian` | turunan | `s($r['NoPembelian'], 50)` |
| `keterangan` | `Keterangan` | turunan | `s($r['Keterangan'], 100)` |
| `jumlah_hutang` | `JumlahHutang` | turunan | `n($r['JumlahHutang'])` |
| `jumlah_bayar` | `JumlahBayar` | turunan | `n($r['JumlahBayar'])` |
| `status` | `Status` | turunan | `s($r['Status'], 20)` |

### `piutang_header` → `tblpiutang_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi`
- Query sumber: `SELECT * FROM tblpiutangheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `no_pelanggan` | `NoPelanggan` | turunan | `s($r['NoPelanggan'], 50)` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `total_bayar` | `TotalBayar` | turunan | `n($r['TotalBayar'])` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `id_tabel` | `IDTabel` | turunan | `s($r['IDTabel'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |

### `piutang_detail` → `tblpiutang_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi, nobaris`
- Query sumber: `SELECT * FROM tblpiutangdetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `nobaris` | `NoBaris` | turunan | `i($r['NoBaris'])` |
| `no_penjualan` | `NoPenjualan` | turunan | `s($r['NoPenjualan'], 50)` |
| `keterangan` | `Keterangan` | turunan | `s($r['Keterangan'], 100)` |
| `jumlah_piutang` | `JumlahPiutang` | turunan | `n($r['JumlahPiutang'])` |
| `jumlah_bayar` | `JumlahBayar` | turunan | `n($r['JumlahBayar'])` |
| `status` | `Status` | turunan | `s($r['Status'], 20)` |

### `retur_beli_header` → `tblretur_pembelian_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `noretur`
- Query sumber: `SELECT * FROM tblreturbeliheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `noretur` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `nopembelian` | `NoBeli` | turunan | `s($r['NoBeli'], 50)` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `total_qty_retur` | `TotalQty` | turunan | `i($r['TotalQty'])` |
| `total_retur` | `TotalBeli` | turunan | `n($r['TotalBeli'])` |
| `diskon` | `Diskon` | turunan | `n($r['Diskon'])` |
| `total_diskon` | `TotalDiskon` | turunan | `n($r['TotalDiskon'])` |
| `pajak` | `Pajak` | turunan | `n($r['Pajak'])` |
| `total_pajak` | `TotalPajak` | turunan | `n($r['TotalPajak'])` |
| `total_akhir` | `TotalAkhir` | turunan | `n($r['TotalAkhir'])` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_retur` | - | konstanta / default | `'1'` |

### `retur_beli_detail` → `tblretur_pembelian_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_retur, no_item`
- Query sumber: `SELECT d.*, h.NoBeli FROM tblreturbelidetail d LEFT JOIN tblreturbeliheader h ON h.NoTransaksi = d.NoTransaksi AND h.kode_cabang = d.kode_cabang WHERE d.kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_retur` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `no_pembelian` | `NoBeli` | turunan | `s($r['NoBeli'], 50)` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 50)` |
| `quantity` | `Quantity` | turunan | `i($r['Quantity'])` |
| `harga_pokok` | `HargaPokok` | turunan | `n($r['HargaPokok'])` |
| `potongan` | `Potongan` | turunan | `n($r['Potongan'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `user` | - | konstanta / default | `''` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `alasan_retur` | - | konstanta / default | `''` |
| `jenis_penggantian` | - | konstanta / default | `0` |
| `status_penggantian` | - | konstanta / default | `'0'` |

### `kas` → `tblkas_keluar_masuk`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `kode_km`
- Query sumber: `SELECT * FROM tblkaskeluarmasuk WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `kode_km` | `KodeKM` | turunan | `s($r['KodeKM'])` |
| `no_bukti` | `NoBukti` | turunan | `s($r['NoBukti'], 50)` |
| `kode_akun` | `KodeAkun` | turunan | `s($r['KodeAkun'], 50)` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `uraian` | `uraian` | turunan | `s($r['uraian'], 200)` |
| `masuk` | `Masuk` | turunan | `n($r['Masuk'])` |
| `keluar` | `Keluar` | turunan | `n($r['Keluar'])` |
| `jenis` | `Jenis` | turunan | `s($r['Jenis'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `user` | - | konstanta / default | `''` |
| `kode_akun_biaya` | - | konstanta / default | `''` |
| `tipe` | - | konstanta / default | `''` |

### `harga_cabang` → `tblitem_harga_cabang`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `noitem`
- Query sumber: `SELECT NoItem, HargaPokok, HargaJual, HargaJual2, HargaJual3, Quantity FROM tblitem WHERE kode_cabang='{ref}'`
- Baris dobel pada sumber (kunci sama) dilewati karena target punya UNIQUE pada (kunci, kd_cabang).

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `noitem` | `NoItem` | turunan | `s($r['NoItem'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `hargapokok` | `HargaPokok` | turunan | `n($r['HargaPokok'])` |
| `hargajual` | `HargaJual` | turunan | `n($r['HargaJual'])` |
| `hargajual2` | `HargaJual2` | turunan | `n($r['HargaJual2'])` |
| `hargajual3` | `HargaJual3` | turunan | `n($r['HargaJual3'])` |
| `qty_access` | `Quantity` | turunan | `i($r['Quantity'])` |
| `tanggal_sync` | - | konstanta / default | `date('Y-m-d H:i:s')` |

### `master_pelanggan` → `tblpelanggan`

- Jenis: master global, hanya ditambah bila kunci belum ada (tidak menimpa)
- Kunci: `nopelanggan`
- Query sumber: `SELECT * FROM tblpelanggan WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `nopelanggan` | `NoPelanggan` | turunan | `s($r['NoPelanggan'])` |
| `namapelanggan` | `NamaPelanggan` | turunan | `s($r['NamaPelanggan'])` |
| `alamat` | `Alamat` | turunan | `s($r['Alamat'])` |
| `kota` | `Kota` | turunan | `s($r['Kota'])` |
| `propinsi` | `Propinsi` | turunan | `s($r['Propinsi'])` |
| `kodepost` | `KodePost` | turunan | `s($r['KodePost'])` |
| `negara` | `Negara` | turunan | `s($r['Negara'])` |
| `telephone` | `Telephone` | turunan | `s($r['Telephone'])` |
| `fax` | `Fax` | turunan | `s($r['Fax'])` |
| `kontakperson` | `KontakPerson` | turunan | `s($r['KontakPerson'])` |
| `note` | `note` | turunan | `s($r['note'])` |
| `potongan` | `Potongan` | turunan | `n($r['Potongan'])` |
| `tipepot` | `TipePot` | turunan | `s($r['TipePot'])` |
| `lavelharga` | `LavelHarga` | turunan | `s($r['LavelHarga'])` |
| `kgrup` | `KGrup` | turunan | `s($r['KGrup'])` |
| `domisili_cabang` | - | kode cabang yang direload | `$CAB` |

### `master_kendaraan` → `tblkendaraan`

- Jenis: master global, hanya ditambah bila kunci belum ada (tidak menimpa)
- Kunci: `nopolisi`
- Query sumber: `SELECT * FROM tblkendaraan WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `nopolisi` | `NoPolisi` | turunan | `s($r['NoPolisi'])` |
| `pemilik` | `Pemilik` | turunan | `s($r['Pemilik'])` |
| `alamat` | `Alamat` | turunan | `s($r['Alamat'])` |
| `tipe` | `Tipe` | turunan | `s($r['Tipe'])` |
| `jenis` | `Jenis` | turunan | `s($r['Jenis'])` |
| `tahun_buat` | `TahunBuat` | turunan | `s($r['TahunBuat'])` |
| `tahun_rakit` | `TahunRakit` | turunan | `s($r['TahunRakit'])` |
| `silinder` | `Silinder` | turunan | `s($r['Silinder'])` |
| `warna` | `Warna` | turunan | `s($r['Warna'])` |
| `no_rangka` | `NoRangka` | turunan | `s($r['NoRangka'])` |
| `no_mesin` | `NoMesin` | turunan | `s($r['NoMesin'])` |
| `note` | `note` | turunan | `s($r['note'])` |

### `master_item` → `tblitem`

- Jenis: master global, hanya ditambah bila kunci belum ada (tidak menimpa)
- Kunci: `noitem`
- Query sumber: `SELECT * FROM tblitem WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `noitem` | `NoItem` | turunan | `$r['NoItem']` |
| `kodebarcode` | `KodeBarCode` | turunan | `$r['KodeBarCode']` |
| `namaitem` | `NamaItem` | turunan | `$r['NamaItem']` |
| `jenis` | `Jenis` | turunan | `$r['Jenis']` |
| `satuan` | `Satuan` | turunan | `$r['Satuan']` |
| `hargapokok` | `HargaPokok` | turunan | `$r['HargaPokok']` |
| `hargajual` | `HargaJual` | turunan | `$r['HargaJual']` |
| `hargajual2` | `HargaJual2` | turunan | `$r['HargaJual2']` |
| `hargajual3` | `HargaJual3` | turunan | `$r['HargaJual3']` |
| `hjqtyd2` | `HJQtyD2` | turunan | `$r['HJQtyD2']` |
| `hjqtyd3` | `HJQtyD3` | turunan | `$r['HJQtyD3']` |
| `hjqtys1` | `HJQtyS1` | turunan | `$r['HJQtyS1']` |
| `hjqtys2` | `HJQtyS2` | turunan | `$r['HJQtyS2']` |
| `totalpokok` | `TotalPokok` | turunan | `$r['TotalPokok']` |
| `stokmin` | `StokMin` | turunan | `$r['StokMin']` |
| `statusitem` | `StatusItem` | turunan | `$r['StatusItem']` |
| `supplier` | `Supplier` | turunan | `$r['Supplier']` |
| `supplier2` | `Supplier2` | turunan | `$r['Supplier2']` |
| `supplier3` | `Supplier3` | turunan | `$r['Supplier3']` |
| `statusproduk` | `StatusProduk` | turunan | `$r['StatusProduk']` |
| `gambar` | `Gambar` | turunan | `$r['Gambar']` |
| `note` | `note` | turunan | `$r['note']` |
| `rakbarang` | `RakBarang` | turunan | `$r['RakBarang']` |
| `jasawaktu` | `JasaWaktu` | turunan | `$r['JasaWaktu']` |
| `jasasatuanwaktu` | `JasaSatuanWaktu` | turunan | `$r['JasaSatuanWaktu']` |
| `jeniskomisi` | `JenisKomisi` | turunan | `$r['JenisKomisi']` |
| `komisiprosen` | `KomisiProsen` | turunan | `$r['KomisiProsen']` |
| `komisinominal` | `KomisiNominal` | turunan | `$r['KomisiNominal']` |
| `quantity` | - | konstanta / default | `0` |

### `master_supplier` → `tblsupplier`

- Jenis: master global, hanya ditambah bila kunci belum ada (tidak menimpa)
- Kunci: `nosupplier`
- Query sumber: `SELECT * FROM tblsupplier WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `nosupplier` | `NoSupplier` | turunan | `$r['NoSupplier']` |
| `namasupplier` | `NamaSupplier` | turunan | `$r['NamaSupplier']` |
| `alamat` | `Alamat` | turunan | `$r['Alamat']` |
| `kota` | `Kota` | turunan | `$r['Kota']` |
| `propinsi` | `Propinsi` | turunan | `$r['Propinsi']` |
| `kodepost` | `KodePost` | turunan | `$r['KodePost']` |
| `negara` | `Negara` | turunan | `$r['Negara']` |
| `telephone` | `Telephone` | turunan | `$r['Telephone']` |
| `fax` | `Fax` | turunan | `$r['Fax']` |
| `namabank` | `NamaBank` | turunan | `$r['NamaBank']` |
| `noaccount` | `NoAccount` | turunan | `$r['NoAccount']` |
| `atasnama` | `AtasNama` | turunan | `$r['AtasNama']` |
| `kontakperson` | `KontakPerson` | turunan | `$r['KontakPerson']` |
| `email` | `Email` | turunan | `$r['Email']` |
| `note` | `note` | turunan | `$r['note']` |
| `saldoawal` | `SaldoAwal` | turunan | `$r['SaldoAwal']` |
| `jmlbayar` | `JmlBayar` | turunan | `$r['JmlBayar']` |
| `sisa` | `Sisa` | turunan | `$r['Sisa']` |
| `pertanggal` | `PerTanggal` | turunan | `d($r['PerTanggal'])` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |

### `item_masuk_header` → `tbitem_masuk_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi`
- Query sumber: `SELECT * FROM tblitemmheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |

### `item_keluar_header` → `tbitem_keluar_header`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi`
- Query sumber: `SELECT * FROM tblitemkheader WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `tanggal` | `Tanggal` | turunan | `d($r['Tanggal'])` |
| `note` | `note` | turunan | `s($r['note'], 50)` |
| `user` | `User` | turunan | `s($r['User'], 50)` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |

### `item_masuk_detail` → `tbitem_masuk_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi, no_item`
- Query sumber: `SELECT * FROM tblitemmdetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 20)` |
| `quantity` | `Qty` | turunan | `i($r['Qty'])` |
| `harga` | `Harga` | turunan | `n($r['Harga'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `keterangan` | `note` | turunan | `s($r['note'], 100)` |
| `user` | - | konstanta / default | `''` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_trx` | - | konstanta / default | `'1'` |
| `stok_sistem` | - | konstanta / default | `0` |
| `penyesuaian` | `Qty` | turunan | `i($r['Qty'])` |

### `item_keluar_detail` → `tbitem_keluar_detail`

- Jenis: transaksi, baris cabang dihapus lalu diisi ulang dari center
- Kunci: `no_transaksi, no_item`
- Query sumber: `SELECT * FROM tblitemkdetail WHERE kode_cabang='{ref}'`

| Kolom web | Kolom center | Jenis | Ekspresi |
|---|---|---|---|
| `no_transaksi` | `NoTransaksi` | turunan | `s($r['NoTransaksi'])` |
| `no_item` | `NoItem` | turunan | `s($r['NoItem'], 20)` |
| `quantity` | `Qty` | turunan | `i($r['Qty'])` |
| `harga` | `Harga` | turunan | `n($r['Harga'])` |
| `total` | `Total` | turunan | `n($r['Total'])` |
| `keterangan` | `note` | turunan | `s($r['note'], 100)` |
| `user` | - | konstanta / default | `''` |
| `kd_cabang` | - | kode cabang yang direload | `$CAB` |
| `status_trx` | - | konstanta / default | `'1'` |
| `stok_sistem` | - | konstanta / default | `0` |
| `penyesuaian` | `Qty` | turunan | `i($r['Qty'])` |

## 5. Kartu stok `tbstok` (dibangun ulang, bukan salin)

Baris cabang yang bukan web-native dihapus, lalu dibangun dari transaksi center. Kolom: `tipe`, `no_transaksi`, `no_item`, `tanggal`, `masuk`, `keluar`, `keterangan`, `kd_cabang`.

| tipe | Sumber center | masuk | keluar | keterangan |
|---|---|---|---|---|
| `2` | tblpembeliandetail + header | Quantity | 0 | Pembelian |
| `3` | tblpenjualandetail + header | 0 | Quantity - QtyRetur | Penjualan |
| `4` | tblserviceitemdt + tblservice | 0 | Quantity - QtyRetur | Servis |
| `5` | tblitemmdetail + header | Qty | 0 | Penyesuaian Stok Item Masuk |
| `6` | tblitemkdetail + header | 0 | Qty | Penyesuaian Stok Item Keluar |
| `7` | tblreturbelidetail + header | 0 | Quantity | Retur Pembelian |
| `M` | selisih saldo vs `tblitem.Quantity` Access | max(selisih,0) | max(-selisih,0) | Rekonsiliasi saldo Access (`REKON-ACC-<CABANG>`) |

Baris dengan masuk = keluar = 0 dilewati. Setelah semua mutasi masuk, saldo per item (termasuk baris web-native) dibandingkan dengan `tblitem.Quantity` Access; selisih ditutup satu baris rekonsiliasi. Hasil akhir: saldo `tbstok` = Quantity Access.

## 6. Verifikasi (`--mode=verify`)

Jumlah baris dan total rupiah di center dibandingkan dengan web dikurangi baris web-native di backup. Hasil `COCOK` bila jumlah sama dan selisih total < 1.

| Cek | Tabel web | Kolom total |
|---|---|---|
| servis | `tblservice` | `total_akhir` |
| servis_barang | `tblservis_barang` | `total` |
| servis_jasa | `tblservis_jasa` | `total` |
| beli | `tblpembelian_header` | `total_akhir` |
| beli_detail | `tblpembelian_detail` | `total` |
| jual | `tblpenjualan_header` | `total_akhir` |
| jual_detail | `tblpenjualan_detail` | `total` |
| po | `tblorder_header` | `total_order` |
| hutang | `tblhutang_header` | `total_bayar` |
| stok_saldo | `tbstok` | `SUM(masuk-keluar) per item` |

## 7. Perintah

```bash
# env sumber (tanpa default, jangan ditulis ke file): CENTER_DB_HOST CENTER_DB_PORT CENTER_DB_USER CENTER_DB_PASS [CENTER_DB_NAME]
php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=preflight
php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=run [--only=servis_header,stok] [--tag=MMDD]
php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=verify
php scripts/center_reload/reload_cabang.php --cabang=PACUL --mode=rollback --tag=MMDD
php scripts/center_reload/generate_mapping_doc.php --counts > docs/MAPPING_CENTER_KE_WEBBASE.md
```
