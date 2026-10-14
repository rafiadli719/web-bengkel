# 2026-10-14 — Opname otomatis (C2), laporan selisih (C3), guard master barang/supplier (A6/G9)

## Added
- `app/lap_selisih_opname.php`: laporan selisih stok opname (lebih = item masuk, kurang = item keluar), filter
  tanggal/jenis/cabang, ekspor CSV. Menu Laporan > "Selisih Stok Opname", izin `lap_stok_masuk_read`.
- Pemilih "Acuan Daftar Item" di Penyesuaian Stok Otomatis (`so-item-masuk.php`, `so-item-keluar.php`):
  item bertransaksi pada rentang tanggal (perilaku lama) atau semua item stok > 0 per tanggal "sampai" (G13).
- Izin `barang_write` dan `supplier_write` (hanya posisi ADM), migrasi reversible
  `db/migrations/2026-10-14_run_master_write_perm.php [up|down]`.
- `app/_include_guard_master.php` (`guardMasterWrite`).

## Changed
- Daftar kandidat opname diurut Rak lalu Nama Barang; kolom Rak terisi; N+1 query nama/saldo per baris diganti satu query.
- 23 endpoint tulis master barang/supplier (add/edit/del/save_*) kini memeriksa izin tulis. Sebelumnya hanya cek login,
  sehingga posisi non-ADM bisa menembak URL langsung.

## Fixed
- Tidak ada bug baru. Dua temuan 2026-10-13 ternyata bukan bug: `inv_config.mode` (kode memakai `config_key/config_value`,
  query smoke test yang salah) dan freeze `koreksi.php`/`laporan_valuasi.php` (artefak browser tool, respons 43-288 ms).

## Catatan
- Migrasi `2026-10-14_run_master_write_perm.php` sudah jalan di lokal, BELUM di produksi/fitmotor1. Tanpa migrasi,
  ADM non-`*` akan kena 403 di halaman master barang/supplier setelah kode ini dideploy.

## Perbaikan hasil review Opus (merge gate)
- so-item-masuk/keluar: tanggal POST di blok btnrst/btnpilih kini di-escape (SQL injection lama).
- Guard tulis ditambah ke 14 endpoint yang terlewat: *_edit_proses, *_del, barang_validate, supplier_awal_hutang_save
  (sebagian tadinya tanpa cek login sama sekali).
