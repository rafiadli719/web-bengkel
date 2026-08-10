-- Migration: Add google_maps and foto_rumah columns to tblpelanggan
-- Date: 2025-10-11
-- Description: Menambahkan kolom untuk menyimpan link Google Maps dan foto tampak rumah pelanggan

-- Cek apakah kolom google_maps sudah ada, jika belum tambahkan
ALTER TABLE tblpelanggan
ADD COLUMN IF NOT EXISTS google_maps TEXT NULL COMMENT 'Link Google Maps lokasi rumah pelanggan';

-- Cek apakah kolom foto_rumah sudah ada, jika belum tambahkan
ALTER TABLE tblpelanggan
ADD COLUMN IF NOT EXISTS foto_rumah VARCHAR(255) NULL COMMENT 'Path file foto tampak depan rumah pelanggan';

-- Verifikasi hasil migration
SELECT
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM
    INFORMATION_SCHEMA.COLUMNS
WHERE
    TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblpelanggan'
    AND COLUMN_NAME IN ('google_maps', 'foto_rumah');
