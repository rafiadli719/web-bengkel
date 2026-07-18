-- ============================================================
-- MIGRATION: Kendaraan Pindah Kepemilikan (Motor Dijual)
-- Tanggal: 2026-07-13
-- Tujuan:
--   1. Tambah surrogate key immutable id_kendaraan di tblkendaraan
--   2. Simpan riwayat plat kendaraan
--   3. Simpan riwayat kepemilikan kendaraan bertanggal
--   4. Siapkan request approval pindah kepemilikan
--   5. Siapkan statistik lifetime per kendaraan
-- Catatan:
--   - Disusun kompatibel untuk basis MariaDB/MySQL legacy project ini
--   - Bagian ALTER dijaga dengan INFORMATION_SCHEMA agar re-run aman
--   - Backfill fokus menentukan owner aktif saat ini; histori owner lama legacy
--     tidak selalu bisa dipulihkan akurat otomatis
-- ============================================================

SET @OLD_SQL_SAFE_UPDATES = @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

-- ------------------------------------------------------------
-- 1. Tambah id_kendaraan ke tblkendaraan jika belum ada
-- ------------------------------------------------------------
SET @col_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tblkendaraan'
      AND COLUMN_NAME = 'id_kendaraan'
);
SET @sql := IF(@col_exists = 0,
    'ALTER TABLE tblkendaraan ADD COLUMN id_kendaraan BIGINT UNSIGNED NULL AFTER nopolisi',
    'SELECT "id_kendaraan already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Isi id_kendaraan untuk row lama yang masih NULL.
SET @rownum := 0;
UPDATE tblkendaraan
SET id_kendaraan = (@rownum := @rownum + 1)
WHERE id_kendaraan IS NULL
ORDER BY nopolisi;

-- Naikkan auto seed berbasis MAX existing.
SET @max_id_kendaraan := (SELECT COALESCE(MAX(id_kendaraan), 0) FROM tblkendaraan);

-- Unique key id_kendaraan jika belum ada.
SET @uk_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tblkendaraan'
      AND INDEX_NAME = 'uk_tblkendaraan_id_kendaraan'
);
SET @sql := IF(@uk_exists = 0,
    'ALTER TABLE tblkendaraan ADD UNIQUE KEY uk_tblkendaraan_id_kendaraan (id_kendaraan)',
    'SELECT "uk_tblkendaraan_id_kendaraan already exists"'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 2. Tabel riwayat plat
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kendaraan_plat_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_kendaraan BIGINT UNSIGNED NOT NULL,
    nopolisi VARCHAR(20) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_akhir DATE DEFAULT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    alasan ENUM('ganti_plat','koreksi_typo','kendaraan_baru') NOT NULL DEFAULT 'kendaraan_baru',
    diinput_oleh INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kph_id_kendaraan_current (id_kendaraan, is_current),
    KEY idx_kph_nopolisi_current (nopolisi, is_current),
    KEY idx_kph_nopolisi (nopolisi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 3. Tabel riwayat kepemilikan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kepemilikan_kendaraan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_kendaraan BIGINT UNSIGNED NOT NULL,
    nopelanggan VARCHAR(20) NOT NULL,
    tanggal_mulai DATE NOT NULL,
    tanggal_akhir DATE DEFAULT NULL,
    is_current TINYINT(1) NOT NULL DEFAULT 1,
    sumber ENUM('input_awal','jual_beli','koreksi_admin','migrasi_backfill') NOT NULL DEFAULT 'migrasi_backfill',
    diinput_oleh INT(11) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_kk_id_kendaraan_current (id_kendaraan, is_current),
    KEY idx_kk_nopelanggan_current (nopelanggan, is_current),
    KEY idx_kk_nopelanggan (nopelanggan),
    KEY idx_kk_tanggal (tanggal_mulai, tanggal_akhir)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 4. Tabel request approval pindah kepemilikan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS permintaan_pindah_kepemilikan_kendaraan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_kendaraan BIGINT UNSIGNED NOT NULL,
    nopelanggan_lama VARCHAR(20) NOT NULL,
    nopelanggan_baru VARCHAR(20) NOT NULL,
    nopolisi_snapshot VARCHAR(20) NOT NULL,
    tanggal_efektif DATE NOT NULL,
    alasan TEXT NOT NULL,
    catatan_internal TEXT DEFAULT NULL,
    status ENUM('draft','diajukan','disetujui','ditolak','dieksekusi','batal') NOT NULL DEFAULT 'draft',
    blocker_snapshot_json LONGTEXT DEFAULT NULL,
    dibuat_oleh INT(11) NOT NULL,
    disetujui_oleh INT(11) DEFAULT NULL,
    dieksekusi_oleh INT(11) DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    executed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ppkk_id_kendaraan_status (id_kendaraan, status),
    KEY idx_ppkk_old_owner (nopelanggan_lama),
    KEY idx_ppkk_new_owner (nopelanggan_baru)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 5. Tabel statistik lifetime kendaraan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS statistik_kendaraan (
    id_kendaraan BIGINT UNSIGNED NOT NULL,
    nopelanggan_current VARCHAR(20) DEFAULT NULL,
    total_transaksi INT(11) NOT NULL DEFAULT 0,
    total_kunjungan INT(11) NOT NULL DEFAULT 0,
    total_nominal DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_jasa DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    total_sparepart DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    km_terakhir INT(11) DEFAULT NULL,
    tanggal_servis_terakhir DATE DEFAULT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_kendaraan),
    KEY idx_sk_current_owner (nopelanggan_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 6. Backfill riwayat plat dasar
-- ------------------------------------------------------------
INSERT INTO kendaraan_plat_history (
    id_kendaraan,
    nopolisi,
    tanggal_mulai,
    tanggal_akhir,
    is_current,
    alasan,
    diinput_oleh
)
SELECT
    k.id_kendaraan,
    k.nopolisi,
    COALESCE((
        SELECT MIN(s.tanggal)
        FROM tblservice s
        WHERE s.no_polisi = k.nopolisi
    ), CURDATE()) AS tanggal_mulai,
    NULL AS tanggal_akhir,
    1 AS is_current,
    'kendaraan_baru' AS alasan,
    NULL AS diinput_oleh
FROM tblkendaraan k
LEFT JOIN kendaraan_plat_history h
    ON h.id_kendaraan = k.id_kendaraan
   AND h.nopolisi = k.nopolisi
   AND h.is_current = 1
WHERE h.id IS NULL;

-- ------------------------------------------------------------
-- 7. Backfill owner aktif dasar
--    Prioritas owner:
--    A. no_pelanggan paling akhir dari tblservice berdasar no_polisi
--    B. fallback ke tblkendaraan.pemilik bila sama dengan tblpelanggan.nopelanggan
-- ------------------------------------------------------------
INSERT INTO kepemilikan_kendaraan (
    id_kendaraan,
    nopelanggan,
    tanggal_mulai,
    tanggal_akhir,
    is_current,
    sumber,
    diinput_oleh
)
SELECT
    k.id_kendaraan,
    COALESCE(last_owner.no_pelanggan_last, owner_from_text.nopelanggan_text) AS nopelanggan,
    COALESCE(first_service.tanggal_pertama, CURDATE()) AS tanggal_mulai,
    NULL AS tanggal_akhir,
    1 AS is_current,
    'migrasi_backfill' AS sumber,
    NULL AS diinput_oleh
FROM tblkendaraan k
LEFT JOIN (
    SELECT
        s.no_polisi,
        SUBSTRING_INDEX(
            GROUP_CONCAT(s.no_pelanggan ORDER BY s.tanggal DESC, s.jam DESC, s.no_service DESC SEPARATOR '||'),
            '||',
            1
        ) AS no_pelanggan_last
    FROM tblservice s
    WHERE s.no_pelanggan IS NOT NULL
      AND s.no_pelanggan <> ''
      AND s.no_polisi IS NOT NULL
      AND s.no_polisi <> ''
    GROUP BY s.no_polisi
) last_owner ON last_owner.no_polisi = k.nopolisi
LEFT JOIN (
    SELECT
        p.nopelanggan AS nopelanggan_text
    FROM tblpelanggan p
) owner_from_text ON owner_from_text.nopelanggan_text = k.pemilik
LEFT JOIN (
    SELECT
        s.no_polisi,
        MIN(s.tanggal) AS tanggal_pertama
    FROM tblservice s
    WHERE s.no_polisi IS NOT NULL
      AND s.no_polisi <> ''
    GROUP BY s.no_polisi
) first_service ON first_service.no_polisi = k.nopolisi
LEFT JOIN kepemilikan_kendaraan kk
    ON kk.id_kendaraan = k.id_kendaraan
   AND kk.is_current = 1
WHERE kk.id IS NULL
  AND COALESCE(last_owner.no_pelanggan_last, owner_from_text.nopelanggan_text) IS NOT NULL;

-- ------------------------------------------------------------
-- 8. Backfill statistik lifetime kendaraan
-- ------------------------------------------------------------
INSERT INTO statistik_kendaraan (
    id_kendaraan,
    nopelanggan_current,
    total_transaksi,
    total_kunjungan,
    total_nominal,
    total_jasa,
    total_sparepart,
    km_terakhir,
    tanggal_servis_terakhir
)
SELECT
    k.id_kendaraan,
    kk.nopelanggan AS nopelanggan_current,
    COUNT(DISTINCT s.no_service) AS total_transaksi,
    COUNT(DISTINCT s.no_service) AS total_kunjungan,
    COALESCE(SUM(COALESCE(s.total_akhir, s.total_grand, s.total, 0)), 0) AS total_nominal,
    COALESCE(SUM(COALESCE(s.subtotal_jasa, 0)), 0) AS total_jasa,
    COALESCE(SUM(COALESCE(s.subtotal_item, 0)), 0) AS total_sparepart,
    MAX(COALESCE(s.km_skr, 0)) AS km_terakhir,
    MAX(s.tanggal) AS tanggal_servis_terakhir
FROM tblkendaraan k
LEFT JOIN tblservice s
    ON s.no_polisi = k.nopolisi
LEFT JOIN kepemilikan_kendaraan kk
    ON kk.id_kendaraan = k.id_kendaraan
   AND kk.is_current = 1
LEFT JOIN statistik_kendaraan sk
    ON sk.id_kendaraan = k.id_kendaraan
WHERE sk.id_kendaraan IS NULL
GROUP BY k.id_kendaraan, kk.nopelanggan;

-- ------------------------------------------------------------
-- 9. View owner aktif kendaraan
-- ------------------------------------------------------------
DROP VIEW IF EXISTS view_kendaraan_owner_current;
CREATE VIEW view_kendaraan_owner_current AS
SELECT
    k.id_kendaraan,
    k.nopolisi,
    k.pemilik AS pemilik_legacy,
    k.no_rangka,
    k.no_mesin,
    kk.nopelanggan,
    p.namapelanggan,
    kk.tanggal_mulai,
    kk.tanggal_akhir,
    kk.is_current,
    kk.sumber
FROM tblkendaraan k
LEFT JOIN kepemilikan_kendaraan kk
    ON kk.id_kendaraan = k.id_kendaraan
   AND kk.is_current = 1
LEFT JOIN tblpelanggan p
    ON p.nopelanggan = kk.nopelanggan;

-- ------------------------------------------------------------
-- 10. View blocker pindah kepemilikan
-- ------------------------------------------------------------
DROP VIEW IF EXISTS view_kendaraan_blocker_pindah_kepemilikan;
CREATE VIEW view_kendaraan_blocker_pindah_kepemilikan AS
SELECT
    k.id_kendaraan,
    k.nopolisi,
    COUNT(*) AS total_blocker,
    GROUP_CONCAT(s.no_service ORDER BY s.tanggal DESC, s.jam DESC SEPARATOR ', ') AS daftar_no_service
FROM tblkendaraan k
JOIN tblservice s
    ON s.no_polisi = k.nopolisi
WHERE s.status_servis IN ('datang','diproses','selesai')
GROUP BY k.id_kendaraan, k.nopolisi;

SET SQL_SAFE_UPDATES = @OLD_SQL_SAFE_UPDATES;
