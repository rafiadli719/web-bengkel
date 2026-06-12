-- ============================================================
-- FILE: check_table_names.sql
-- DESKRIPSI: Script untuk mengecek nama tabel yang benar
-- TANGGAL: 2025-11-28
-- ============================================================

-- Cek tabel yang berkaitan dengan ITEM/BARANG
SELECT table_name
FROM information_schema.TABLES
WHERE table_schema = 'fitmotor_dbbengkel'
AND (
    table_name LIKE '%item%' OR
    table_name LIKE '%barang%' OR
    table_name LIKE '%kategori%'
)
ORDER BY table_name;

-- Hasil akan menampilkan nama tabel yang sebenarnya
-- Contoh: tblitem, tbmaster_kategori_item, dll
