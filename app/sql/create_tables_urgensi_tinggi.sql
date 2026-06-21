-- ============================================================
-- URGENSI TINGGI: Tabel baru untuk insentif, harga beli, WA
-- ============================================================

-- 1. Tabel insentif per transaksi (dari INSENTIF_JUAL_SERVIS_GABUNG_DATA)
CREATE TABLE IF NOT EXISTS tbinsentif_jual_servis (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    siklus        VARCHAR(10)     NOT NULL DEFAULT '',
    tgl           DATE            NULL,
    trx           VARCHAR(50)     NOT NULL DEFAULT '',
    garap         DECIMAL(18,2)   NOT NULL DEFAULT 0,
    stts          VARCHAR(30)     NOT NULL DEFAULT '',
    sts           VARCHAR(30)     NOT NULL DEFAULT '',
    kode          VARCHAR(100)    NOT NULL DEFAULT '',
    mekanik       VARCHAR(100)    NOT NULL DEFAULT '',
    tipe          VARCHAR(50)     NOT NULL DEFAULT '',
    no_item       VARCHAR(100)    NOT NULL DEFAULT '',
    quantity      DECIMAL(18,2)   NOT NULL DEFAULT 0,
    jualnet       DECIMAL(18,2)   NOT NULL DEFAULT 0,
    tothpp        DECIMAL(18,2)   NOT NULL DEFAULT 0,
    laba          DECIMAL(18,2)   NOT NULL DEFAULT 0,
    kategori      VARCHAR(20)     NOT NULL DEFAULT '',
    tanggal_data  DATETIME        NULL,
    kd_cabang     VARCHAR(10)     NOT NULL DEFAULT '',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trx        (trx),
    INDEX idx_siklus     (siklus),
    INDEX idx_no_item    (no_item),
    INDEX idx_tgl        (tgl),
    INDEX idx_kd_cabang  (kd_cabang),
    INDEX idx_tipe       (tipe)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Riwayat harga beli per item (dari BELI_PERITEM_ALL)
CREATE TABLE IF NOT EXISTS tbbeli_peritem_history (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_tabel      VARCHAR(20)     NOT NULL DEFAULT '',
    tanggal       DATE            NULL,
    no_item       VARCHAR(100)    NOT NULL DEFAULT '',
    hrgnet        DECIMAL(18,2)   NOT NULL DEFAULT 0,
    no_supplier   VARCHAR(20)     NOT NULL DEFAULT '',
    fax           VARCHAR(50)     NOT NULL DEFAULT '',
    kd_cabang     VARCHAR(10)     NOT NULL DEFAULT '',
    created_at    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_tabel_item_tgl (id_tabel, no_item, tanggal),
    INDEX idx_no_item     (no_item),
    INDEX idx_tanggal     (tanggal),
    INDEX idx_no_supplier (no_supplier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Aturan margin harga jual (dari HARGA_JUAL)
CREATE TABLE IF NOT EXISTS tbharga_jual_rules (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    jenis       VARCHAR(50)   NOT NULL UNIQUE,
    margin      DECIMAL(6,4)  NOT NULL DEFAULT 1.0000,
    marginplus  INT           NOT NULL DEFAULT 0,
    bulat       INT           NOT NULL DEFAULT 1000,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Kolom no_wa di tblpelanggan (jika belum ada)
-- Dieksekusi terpisah via --force agar tidak abort jika kolom sudah ada
ALTER TABLE tblpelanggan ADD COLUMN no_wa VARCHAR(30) NOT NULL DEFAULT '' AFTER telephone;
ALTER TABLE tblpelanggan ADD COLUMN domisili_cabang VARCHAR(30) NOT NULL DEFAULT '' AFTER no_wa;

-- 5. Staging: insentif
CREATE TABLE IF NOT EXISTS stg_access_gabung_insentif (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_run_id   BIGINT UNSIGNED NOT NULL,
    kd_cabang     VARCHAR(30)     NULL,
    sts_source    VARCHAR(50)     NULL,
    tanggal_data  DATETIME        NULL,
    siklus        VARCHAR(10)     NULL,
    tgl           DATETIME        NULL,
    trx           VARCHAR(50)     NULL,
    garap         DECIMAL(18,2)   NULL,
    stts          VARCHAR(30)     NULL,
    sts           VARCHAR(30)     NULL,
    kode          VARCHAR(100)    NULL,
    mekanik       VARCHAR(100)    NULL,
    tipe          VARCHAR(50)     NULL,
    no_item       VARCHAR(100)    NULL,
    quantity      DECIMAL(18,2)   NULL,
    jualnet       DECIMAL(18,2)   NULL,
    tothpp        DECIMAL(18,2)   NULL,
    laba          DECIMAL(18,2)   NULL,
    kategori      VARCHAR(20)     NULL,
    raw_payload   JSON            NULL,
    INDEX idx_sync_run_id (sync_run_id),
    INDEX idx_trx         (trx)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Staging: harga beli per item
CREATE TABLE IF NOT EXISTS stg_access_beli_peritem (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_run_id   BIGINT UNSIGNED NOT NULL,
    kd_cabang     VARCHAR(30)     NULL,
    tanggal_data  DATETIME        NULL,
    id_tabel      VARCHAR(20)     NULL,
    tanggal       DATETIME        NULL,
    no_item       VARCHAR(100)    NULL,
    hrgnet        DECIMAL(18,2)   NULL,
    no_supplier   VARCHAR(20)     NULL,
    fax           VARCHAR(50)     NULL,
    raw_payload   JSON            NULL,
    INDEX idx_sync_run_id (sync_run_id),
    INDEX idx_no_item     (no_item)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Staging: nomor WA pelanggan
CREATE TABLE IF NOT EXISTS stg_access_nomor_wa (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sync_run_id     BIGINT UNSIGNED NOT NULL,
    kd_cabang       VARCHAR(30)     NULL,
    tanggal_data    DATETIME        NULL,
    nama_pelanggan  VARCHAR(100)    NULL,
    telephone       VARCHAR(30)     NULL,
    no_pelanggan    VARCHAR(30)     NULL,
    domisili        VARCHAR(50)     NULL,
    raw_payload     JSON            NULL,
    INDEX idx_sync_run_id  (sync_run_id),
    INDEX idx_no_pelanggan (no_pelanggan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SELECT 'SEMUA TABEL BERHASIL DIBUAT' AS hasil;

-- Verifikasi
SELECT table_name, table_rows
FROM information_schema.tables
WHERE table_schema='fitmotor_dbbengkel'
AND table_name IN (
  'tbinsentif_jual_servis','tbbeli_peritem_history','tbharga_jual_rules',
  'stg_access_gabung_insentif','stg_access_beli_peritem','stg_access_nomor_wa'
)
ORDER BY table_name;
