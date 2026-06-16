-- Migration untuk menambah kolom diskon per item
-- Tanggal: 2025-12-30

-- Tambah kolom di tblservis_barang
ALTER TABLE tblservis_barang ADD COLUMN IF NOT EXISTS diskon_source VARCHAR(20) DEFAULT NULL COMMENT 'promo/member/none';
ALTER TABLE tblservis_barang ADD COLUMN IF NOT EXISTS diskon_persen DECIMAL(5,2) DEFAULT 0;
ALTER TABLE tblservis_barang ADD COLUMN IF NOT EXISTS diskon_nominal DECIMAL(15,2) DEFAULT 0;
ALTER TABLE tblservis_barang ADD COLUMN IF NOT EXISTS id_promo INT DEFAULT NULL;

-- Tambah kolom di tblservis_jasa
ALTER TABLE tblservis_jasa ADD COLUMN IF NOT EXISTS diskon_source VARCHAR(20) DEFAULT NULL COMMENT 'promo/member/none';
ALTER TABLE tblservis_jasa ADD COLUMN IF NOT EXISTS diskon_persen DECIMAL(5,2) DEFAULT 0;
ALTER TABLE tblservis_jasa ADD COLUMN IF NOT EXISTS diskon_nominal DECIMAL(15,2) DEFAULT 0;
ALTER TABLE tblservis_jasa ADD COLUMN IF NOT EXISTS id_promo INT DEFAULT NULL;
