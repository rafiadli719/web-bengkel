-- Fix untuk 2 statement yang gagal/terlewat saat app/sql/migration_fase1_2026.sql dijalankan 2026-06-30.
-- Ditemukan saat verifikasi F1-E (2026-07-04): kolom tblservis_barang.keterangan
-- dan item placeholder PART-CUST tidak pernah ter-apply, meski F1-C/F1-A/F1-B sudah aktif.
-- Root cause item PART-CUST: migration asli pakai nama kolom salah (kode_item/nama_item/
-- harga_jual/harga_beli/stok/aktif) — tblitem sebenarnya pakai noitem/namaitem/kodebarcode/
-- hargapokok/hargajual/quantity/statusitem (tanpa kolom 'aktif').

-- 1. Kolom keterangan di tblservis_barang (dipakai F1-E untuk [PART-CUST: nama | merek | kondisi])
-- Catatan: "ADD COLUMN IF NOT EXISTS" tidak didukung sintaksnya di server ini (MySQL 8.4.3) — cek manual dulu kalau re-run.
ALTER TABLE `tblservis_barang`
  ADD COLUMN `keterangan` TEXT DEFAULT NULL COMMENT 'Keterangan khusus, misal [PART-CUST: nama | merek | kondisi]';

-- 2. Item placeholder PART-CUST (nama kolom disesuaikan skema tblitem yang benar)
INSERT INTO `tblitem`
  (`noitem`, `kodebarcode`, `namaitem`, `jenis`, `satuan`, `hargapokok`, `hargajual`, `hargajual2`, `hargajual3`,
   `hjqtyd2`, `hjqtyd3`, `hjqtys1`, `hjqtys2`, `totalpokok`, `quantity`, `stokmin`, `statusitem`,
   `supplier`, `supplier2`, `supplier3`, `statusproduk`, `gambar`, `note`, `rakbarang`,
   `jasawaktu`, `jasasatuanwaktu`, `jeniskomisi`, `komisiprosen`, `komisinominal`,
   `inv_idawal`, `inv_jmlawal`, `inv_hrgawal`, `inv_tglawal`, `stok_maks`, `kd_pabrik`, `kd_etalase`, `jenis_jasa`,
   `tipe_item`, `status_validasi`)
SELECT
  'PART-CUST', 'PART-CUST', 'Part Milik Customer', 'BARANG', 'PCS', 0, 0, 0, 0,
  0, 0, 0, 0, 0, 0, 0, '1',
  '', '', '', '1', '', 'Placeholder F1-E — part milik customer, tidak masuk stok bengkel', '',
  0, '1', '3', 0, 0,
  0, 0, 0, CURDATE(), 0, 0, '', 0,
  'NON_ORI', 'validated'
WHERE NOT EXISTS (SELECT 1 FROM `tblitem` WHERE `noitem` = 'PART-CUST');
