-- ============================================================
-- FASE 1 MIGRATION — Web Bengkel FIT MOTOR
-- Tanggal: 2026-06-30
-- Berisi: F1-C (multi-payment) + F1-A (garansi auto-check) + F1-B (komisi garansi)
-- Jalankan SEKALI. Aman dijalankan ulang karena pakai IF NOT EXISTS / kolom sudah ada akan dilewati.
-- ============================================================

-- -------------------------------------------------------
-- F1-C: Multi-Payment (tunai + transfer + QRIS terpisah)
-- -------------------------------------------------------
ALTER TABLE tblservice
    ADD COLUMN IF NOT EXISTS bayar_tunai    DECIMAL(15,2) DEFAULT 0      COMMENT 'Pembayaran tunai',
    ADD COLUMN IF NOT EXISTS bayar_transfer DECIMAL(15,2) DEFAULT 0      COMMENT 'Pembayaran transfer bank',
    ADD COLUMN IF NOT EXISTS bayar_qris     DECIMAL(15,2) DEFAULT 0      COMMENT 'Pembayaran QRIS',
    ADD COLUMN IF NOT EXISTS ref_transfer   VARCHAR(100)  DEFAULT NULL   COMMENT 'No. referensi transfer',
    ADD COLUMN IF NOT EXISTS bukti_transfer VARCHAR(255)  DEFAULT NULL   COMMENT 'Path file bukti transfer/QRIS';

-- -------------------------------------------------------
-- F1-A: Garansi Auto-Check
-- -------------------------------------------------------
ALTER TABLE tblservice
    ADD COLUMN IF NOT EXISTS is_garansi              TINYINT(1)  DEFAULT 0    COMMENT '1 jika ini servis garansi',
    ADD COLUMN IF NOT EXISTS ref_no_service_original VARCHAR(30) DEFAULT NULL  COMMENT 'No service asli yang dijamin',
    ADD COLUMN IF NOT EXISTS tanggal_garansi_expire  DATE        DEFAULT NULL  COMMENT 'Tanggal kadaluarsa garansi';

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
ALTER TABLE tblservice
    ADD COLUMN IF NOT EXISTS mekanik_original      VARCHAR(50)                       DEFAULT NULL    COMMENT 'Kode mekanik dari servis asli',
    ADD COLUMN IF NOT EXISTS komisi_garansi_mode   ENUM('skip','transfer','unknown') DEFAULT 'unknown' COMMENT 'skip=mekanik sama, transfer=mekanik beda';

-- ============================================================
-- END OF MIGRATION — jalankan via phpMyAdmin atau MySQL CLI
-- ============================================================
