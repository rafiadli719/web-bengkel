# 2026-10-13 — Sidebar subfolder, tautan 404 Keuangan Kasir, A3, C1

## Fixed
- Sidebar di halaman subfolder (`_komplain/`, `_inventory/`) menghasilkan tautan relatif
  (mis. `barang.php` menjadi `_komplain/barang.php`, 404). `renderMenu()` di
  `_include_menu_rbac.php` sekarang memberi prefix `../` sesuai kedalaman folder
  (`menuLinkPrefix()` / `menuLinkHref()`); fallback di `menu_dashboard.php` ikut.
- `cabang_edit.php` HTTP 500: query memilih kolom opsional yang tidak ada di DB. Diganti `SELECT *`
  (kolom opsional sudah dibaca dengan `?? ''`).
- `rencana_order_add.php` HTTP 500 karena tabel `tblsupplier_tempo` belum ada: sekarang menampilkan
  penjelasan, bukan fatal error. Fitur penuh masih menunggu data tempo supplier.
- `jasa-list.php`: item "Lihat Detail" menuju `jasa-detail.php` yang tidak ada, dihapus (Edit sudah
  menampilkan seluruh data).
- Keuangan Kasir: sidebar `includes/sidebar.php` dan beberapa halaman menaut ke file web_kasir yang tidak
  ikut ter-port (404). Dipetakan ke padanan yang ada:
  `admin_dashboard.php` -> `index_kasir.php`; Detail Pemasukan/Pengeluaran/Omset -> `lap_kas_masuk`,
  `lap_kas_keluar`, `lap_penjualan`; Master User/Karyawan/Cabang -> `user_management`, `master_karyawan`,
  `cabang`; `detail_closing.php` -> `view_transaksi(_admin).php`; `generate_pdf.php` /
  `ganerate_*_admin.php` -> `export/export_pdf_closing_kasir.php` / `export/generate_excel.php`;
  `closing_revision_request.php` -> `closing_revisi.php`; `hapus_pemasukan.php` / `hapus_pengeluaran.php`
  -> `hapus_pemasukan1.php` / `hapus_pengeluaran1.php`; approval revisi menaut ke `view_transaksi_admin.php`
  (sebelumnya 403 untuk admin).

## Added
- A3: list Master Barang (`barang_rst.php`) memberi warna baris merah (stok kosong) dan kuning
  (stok <= minimal cabang), kolom "Stok Semua Cabang", dan legenda.
- C1: filter Status Pembayaran dan Tipe Supplier di Daftar Pembelian; filter Tipe Supplier dan Rentang
  Jatuh Tempo di pemilihan faktur Pembayaran Hutang (`pmby_hutang_add.php`). Tipe "Antar Cabang" =
  `no_supplier` berisi kode cabang (lihat `penerimaan_antarcab_proses.php`).

## Belum (temuan, tidak diubah)
- B4 nopol lama: tabel `kendaraan_plat_history` sudah ada (0 baris) tetapi tidak dipakai kode mana pun;
  cek duplikat `save_kendaraan.php` hanya membandingkan ke `tblpelanggan.nopelanggan`.
- Keuangan Kasir: tombol edit/hapus/detail di `keuangan_pusat.php` (`edit_keuangan_pusat`,
  `hapus_keuangan_pusat`, `detail_pemasukan`/`detail_pengeluaran?jenis_data=pusat`), `histori_gudang.php`,
  `print_serah_terima_pengambilan.php` di `setoran_keuangan.php` masih menuju file yang belum di-port.
- Unduh PDF/Excel di `view_transaksi_admin.php` memakai `export/*` yang butuh permission `kasir_operate`.

## Tambahan (audit UI/UX)
- Audit statis 156 halaman menu: 0 aset (css/js/img) rusak. Temuan kecil: `report-tracking-keluhan.php` dan
  `kas_awal_config_crud.php` tampil tanpa sidebar -> sidebar ditambahkan. Layout filter baru di Daftar
  Pembelian dipindah ke baris kedua supaya tombol Ascending/Descending tidak terpotong.
- B4: `save_kendaraan.php` kini menolak nopol yang sudah ada di `tblkendaraan` atau tercatat di
  `kendaraan_plat_history` (nopol lama). Tabel history masih 0 baris; pengisiannya (ganti plat / pindah
  kepemilikan) belum dibangun.

## Tambahan (port halaman Keuangan Kasir yang tidak ada di sumber)
Halaman berikut tidak ada di sumber web_kasir (tautannya sudah menggantung sejak awal), jadi dibuat baru
berdasarkan tabel `*_pusat_closing_kasir` dan `pengambilan_setoran_closing_kasir`:
- `edit_keuangan_pusat.php`, `hapus_keuangan_pusat.php` (izin `kasir_admin`, nama tabel whitelist)
- `detail_pemasukan.php`, `detail_pengeluaran.php` (filter periode + cabang, maks 1.000 baris)
- `histori_gudang.php` (pengambilan setoran ke pemasukan gudang / klasifikasi hutang)
- `print_serah_terima_pengambilan.php` (bukti serah terima + kolom tanda tangan)
- helper: `includes/page_open.php`, `page_close.php`, `keuangan_pusat_helpers.php`
Kesalahan sebelumnya di bagian "Belum" di atas untuk tombol-tombol ini sudah tidak berlaku.
