-- Fix: kode_posisi di tb_master_approval_pembelian dibuat MySQL 8 default collation
-- (utf8mb4_0900_ai_ci) karena tidak di-COLLATE eksplisit saat CREATE TABLE, sedangkan
-- tb_master_posisi.kode_posisi pakai utf8mb4_general_ci (konvensi lama project).
-- Akibatnya JOIN di master-approval-pembelian.php gagal "Illegal mix of collations"
-- (silent, mysqli_query return false, tabel tampil kosong tanpa pesan error).
-- Ditemukan saat testing browser 2026-07-18. Sudah dijalankan live.

ALTER TABLE tb_master_approval_pembelian
  MODIFY kode_posisi VARCHAR(20) COLLATE utf8mb4_general_ci NOT NULL
  COMMENT 'FK longgar ke tb_master_posisi.kode_posisi - posisi minimal yang boleh approve';
