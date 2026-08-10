-- SQL untuk menambahkan kolom tahun ke tabel tbtipe_motor
-- Jalankan query ini di phpMyAdmin atau MySQL client

ALTER TABLE `tbtipe_motor` 
ADD COLUMN `tahun` VARCHAR(4) NULL COMMENT 'Tahun produksi motor (contoh: 2023)' AFTER `kode_kategori`;

-- Update data existing dengan tahun default (opsional)
-- UPDATE `tbtipe_motor` SET `tahun` = '2023' WHERE `tahun` IS NULL;
