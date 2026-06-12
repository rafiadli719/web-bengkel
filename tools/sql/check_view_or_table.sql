-- ============================================================
-- FILE: check_view_or_table.sql
-- DESKRIPSI: Script untuk mengecek apakah objek adalah VIEW atau TABLE
-- TANGGAL: 2025-11-28
-- ============================================================

-- Cek semua objek yang ada di database
SELECT
    table_name,
    table_type,
    engine,
    table_rows,
    create_time
FROM information_schema.TABLES
WHERE table_schema = 'fitmotor_dbbengkel'
AND (
    table_name LIKE 'view_%' OR
    table_name LIKE 'v_%' OR
    table_name = 'master_tarif_jemput' OR
    table_name = 'tblmekanik' OR
    table_name = 'tbl_master_kepala_mekanik'
)
ORDER BY table_type DESC, table_name;

-- ============================================================
-- Expected Output:
-- table_name                 | table_type | engine
-- ---------------------------|------------|--------
-- view_cari_item            | BASE TABLE | InnoDB
-- view_stok                 | VIEW       | NULL
-- ============================================================
