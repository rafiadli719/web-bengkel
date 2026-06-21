-- ============================================================
-- Fix database setelah migrasi Access -> MySQL
-- Jalankan di phpMyAdmin: database fitmotor_dbbengkel
-- ============================================================

-- ============================================================
-- FIX 1: Insert pelanggan yang missing dari tblpelanggan
-- (no_pelanggan di tblservice tidak ada di tblpelanggan)
-- ============================================================
INSERT INTO tblpelanggan
    (nopelanggan, namapelanggan, alamat, kota, propinsi, kodepost, negara,
     telephone, fax, kontakperson, note, potongan, tipepot, lavelharga,
     kgrup, patokan, klat, klong, panggilan, id_panggilan, saldoawal, pertanggal, tgllahir)
SELECT DISTINCT
    s.no_pelanggan,
    CONCAT('[', s.no_pelanggan, ']'),
    '', '', '', '', 'Indonesia',
    '', '', '', '', 0, 'P', '1',
    '', '', '', '', '', 0, 0.0, '2000-01-01', '1990-01-01'
FROM tblservice s
WHERE s.no_pelanggan IS NOT NULL
  AND s.no_pelanggan != ''
  AND s.no_pelanggan NOT IN (SELECT nopelanggan FROM tblpelanggan)
ON DUPLICATE KEY UPDATE nopelanggan = nopelanggan;

SELECT CONCAT('Pelanggan inserted: ', ROW_COUNT()) AS hasil;

-- ============================================================
-- FIX 2: Perbaiki tblservice dengan no_pelanggan kosong
-- ============================================================
UPDATE tblservice
SET no_pelanggan = no_polisi
WHERE (no_pelanggan = '' OR no_pelanggan IS NULL)
  AND no_polisi IS NOT NULL
  AND no_polisi != '';

SELECT CONCAT('tblservice no_pelanggan kosong diperbaiki: ', ROW_COUNT()) AS hasil;

-- ============================================================
-- FIX 3: Perbaiki estimasi_datang_berikutnya yang invalid
-- ============================================================
-- Disable strict mode agar bisa compare/set zero-date dari Access
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE,', ''), ',NO_ZERO_DATE', '');
SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_IN_DATE,', ''), ',NO_ZERO_IN_DATE', '');

UPDATE statistik_pelanggan
SET estimasi_datang_berikutnya = DATE_ADD(
    COALESCE(tanggal_terakhir_transaksi, CURDATE()),
    INTERVAL 60 DAY
)
WHERE estimasi_datang_berikutnya IS NULL
   OR estimasi_datang_berikutnya < '1000-01-01';

SELECT CONCAT('estimasi invalid diperbaiki: ', ROW_COUNT()) AS hasil;

-- ============================================================
-- FIX 4: Refresh lama_tidak_datang ke hari ini
-- ============================================================
UPDATE statistik_pelanggan
SET lama_tidak_datang = DATEDIFF(CURDATE(), tanggal_terakhir_transaksi)
WHERE tanggal_terakhir_transaksi IS NOT NULL;

SELECT CONCAT('lama_tidak_datang direfresh: ', ROW_COUNT()) AS hasil;

-- ============================================================
-- FIX 5: MySQL EVENT untuk refresh lama_tidak_datang otomatis tiap hari jam 01:00
-- ============================================================
DROP EVENT IF EXISTS evt_refresh_lama_tidak_datang;

CREATE EVENT evt_refresh_lama_tidak_datang
ON SCHEDULE EVERY 1 DAY
STARTS CONCAT(DATE(NOW() + INTERVAL 1 DAY), ' 01:00:00')
DO
    UPDATE statistik_pelanggan
    SET
        lama_tidak_datang = DATEDIFF(CURDATE(), tanggal_terakhir_transaksi),
        estimasi_datang_berikutnya = CASE
            WHEN rata_jarak_kunjungan > 0 AND rata_jarak_kunjungan <= 45 THEN
                DATE_ADD(tanggal_terakhir_transaksi,
                    INTERVAL (30 * CEIL(GREATEST(1, DATEDIFF(CURDATE(), tanggal_terakhir_transaksi)) / 30)) DAY)
            ELSE
                DATE_ADD(tanggal_terakhir_transaksi,
                    INTERVAL (60 * CEIL(GREATEST(1, DATEDIFF(CURDATE(), tanggal_terakhir_transaksi)) / 60)) DAY)
        END
    WHERE tanggal_terakhir_transaksi IS NOT NULL;

-- Aktifkan event scheduler jika belum aktif
SET GLOBAL event_scheduler = ON;

SELECT 'EVENT evt_refresh_lama_tidak_datang dibuat (jalan tiap hari jam 01:00)' AS hasil;

-- ============================================================
-- FIX 6: Pastikan cabang Access ada di tbcabang
-- ============================================================
INSERT INTO tbcabang (kode_cabang, nama_cabang)
VALUES
    ('PSL', 'Pesalakan'),
    ('PCL', 'Pacul'),
    ('CDT', 'Cikditiro'),
    ('TRM', 'Trayeman')
ON DUPLICATE KEY UPDATE nama_cabang = VALUES(nama_cabang);

SELECT CONCAT('Cabang upsert: ', ROW_COUNT()) AS hasil;

-- ============================================================
-- VERIFIKASI AKHIR
-- ============================================================
SELECT
    'tblpelanggan'      AS tabel, COUNT(*) AS total FROM tblpelanggan UNION ALL
SELECT 'tblservice',      COUNT(*) FROM tblservice UNION ALL
SELECT 'tblservis_barang', COUNT(*) FROM tblservis_barang UNION ALL
SELECT 'tblservis_jasa',  COUNT(*) FROM tblservis_jasa UNION ALL
SELECT 'statistik_pelanggan', COUNT(*) FROM statistik_pelanggan UNION ALL
SELECT 'pelanggan_masih_missing',
    (SELECT COUNT(DISTINCT no_pelanggan) FROM tblservice
     WHERE no_pelanggan NOT IN (SELECT nopelanggan FROM tblpelanggan)
       AND no_pelanggan IS NOT NULL AND no_pelanggan != '')
FROM DUAL UNION ALL
SELECT 'statistik_ada_estimasi',
    (SELECT COUNT(*) FROM statistik_pelanggan
     WHERE estimasi_datang_berikutnya IS NOT NULL
       AND estimasi_datang_berikutnya > '2000-01-01')
FROM DUAL;
