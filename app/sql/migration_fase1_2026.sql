-- ============================================================
-- FASE 1 MIGRATION — Web Bengkel FIT MOTOR
-- Tanggal: 2026-06-30
-- Berisi: F1-C (multi-payment) + F1-A (garansi auto-check) + F1-B (komisi garansi)
-- AMAN dijalankan ulang (pakai INFORMATION_SCHEMA check).
-- ============================================================

DELIMITER $$

-- -------------------------------------------------------
-- Helper: tambah kolom hanya jika belum ada
-- -------------------------------------------------------
DROP PROCEDURE IF EXISTS AddColumnIfNotExists $$
CREATE PROCEDURE AddColumnIfNotExists(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    DECLARE col_exists INT DEFAULT 0;
    SELECT COUNT(*) INTO col_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = p_table
      AND COLUMN_NAME = p_column;
    IF col_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', p_table, ' ADD COLUMN ', p_column, ' ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

-- -------------------------------------------------------
-- F1-C: Multi-Payment (tunai + transfer + QRIS terpisah)
-- -------------------------------------------------------
CALL AddColumnIfNotExists('tblservice', 'bayar_tunai',    'DECIMAL(15,2) DEFAULT 0 COMMENT ''Pembayaran tunai''');
CALL AddColumnIfNotExists('tblservice', 'bayar_transfer', 'DECIMAL(15,2) DEFAULT 0 COMMENT ''Pembayaran transfer bank''');
CALL AddColumnIfNotExists('tblservice', 'bayar_qris',     'DECIMAL(15,2) DEFAULT 0 COMMENT ''Pembayaran QRIS''');
CALL AddColumnIfNotExists('tblservice', 'ref_transfer',   'VARCHAR(100) DEFAULT NULL COMMENT ''No. referensi transfer''');
CALL AddColumnIfNotExists('tblservice', 'bukti_transfer', 'VARCHAR(255) DEFAULT NULL COMMENT ''Path file bukti transfer/QRIS''');

-- -------------------------------------------------------
-- F1-A: Garansi Auto-Check
-- -------------------------------------------------------
CALL AddColumnIfNotExists('tblservice', 'is_garansi',              'TINYINT(1) DEFAULT 0 COMMENT ''1 jika ini servis garansi''');
CALL AddColumnIfNotExists('tblservice', 'ref_no_service_original', 'VARCHAR(30) DEFAULT NULL COMMENT ''No service asli yang dijamin''');
CALL AddColumnIfNotExists('tblservice', 'tanggal_garansi_expire',  'DATE DEFAULT NULL COMMENT ''Tanggal kadaluarsa garansi''');

-- Tabel master konfigurasi masa garansi per kategori member
CREATE TABLE IF NOT EXISTS tb_master_garansi (
    id                  INT          PRIMARY KEY AUTO_INCREMENT,
    kategori_member     VARCHAR(20)  NOT NULL DEFAULT 'ALL'   COMMENT 'BRONZE/SILVER/GOLD/PLATINUM/ALL',
    jenis_servis        VARCHAR(30)  NOT NULL DEFAULT 'all'   COMMENT 'reguler/garansi/jemput/all',
    masa_garansi_hari   INT          NOT NULL DEFAULT 7        COMMENT 'Masa garansi standar (hari)',
    masa_garansi_maks   INT          NOT NULL DEFAULT 14       COMMENT 'Masa garansi maksimal dengan override Supervisor',
    keterangan          TEXT,
    aktif               TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          DATETIME     DEFAULT NOW(),
    updated_at          DATETIME     DEFAULT NOW() ON UPDATE NOW()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO tb_master_garansi (kategori_member, jenis_servis, masa_garansi_hari, masa_garansi_maks, keterangan)
SELECT 'ALL', 'all', 7, 14, 'Default garansi bengkel'
WHERE NOT EXISTS (SELECT 1 FROM tb_master_garansi WHERE kategori_member='ALL' AND jenis_servis='all');

-- -------------------------------------------------------
-- F1-B: Komisi Mekanik Garansi
-- -------------------------------------------------------
CALL AddColumnIfNotExists('tblservice', 'mekanik_original',    'VARCHAR(50) DEFAULT NULL COMMENT ''Kode mekanik dari servis asli''');
CALL AddColumnIfNotExists('tblservice', 'komisi_garansi_mode', 'ENUM(''skip'',''transfer'',''unknown'') DEFAULT ''unknown'' COMMENT ''skip=mekanik sama, transfer=mekanik beda''');

-- -------------------------------------------------------
-- F1-E: Part Customer di tab Suku Cadang
-- -------------------------------------------------------
CALL AddColumnIfNotExists('tblservis_barang', 'keterangan', 'TEXT DEFAULT NULL COMMENT ''Keterangan khusus, misal [PART-CUST: nama | merek | kondisi]''');

-- Placeholder item untuk part customer (tidak masuk stok, tidak ada harga default)
INSERT INTO tblitem (kode_item, nama_item, satuan, harga_jual, harga_beli, stok, aktif)
SELECT 'PART-CUST', 'Part Milik Customer', 'pcs', 0, 0, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM tblitem WHERE kode_item = 'PART-CUST');

-- -------------------------------------------------------
-- Bersihkan helper procedure
-- -------------------------------------------------------
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- ============================================================
-- END OF MIGRATION
-- ============================================================
