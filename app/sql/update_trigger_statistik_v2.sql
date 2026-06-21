-- ============================================================
-- SQL: Update Trigger Statistik Pelanggan v2
-- Tambahan: rata_jarak_kunjungan + estimasi_datang_berikutnya
-- Formula dari algoritma FITMOTOR APP.mdb / Access
-- ============================================================
-- Jalankan di phpMyAdmin: database fitmotor_dbbengkel
-- ============================================================

DROP TRIGGER IF EXISTS trg_update_statistik_after_payment;

DELIMITER //

CREATE TRIGGER trg_update_statistik_after_payment
AFTER UPDATE ON tblservice
FOR EACH ROW
BEGIN
    DECLARE v_total_transaksi   INT DEFAULT 0;
    DECLARE v_total_nominal     DECIMAL(15,2) DEFAULT 0;
    DECLARE v_rata_rata         DECIMAL(15,2) DEFAULT 0;
    DECLARE v_total_motor       INT DEFAULT 0;
    DECLARE v_tanggal_pertama   DATE;
    DECLARE v_tanggal_terakhir  DATE;
    DECLARE v_tier              VARCHAR(50) DEFAULT 'Bronze';
    DECLARE v_lama_tidak_datang INT DEFAULT 0;
    DECLARE v_rata_jarak        DECIMAL(10,2) DEFAULT 0;
    DECLARE v_interval_hari     INT DEFAULT 30;
    DECLARE v_estimasi          DATE;

    IF NEW.status_servis = 'bayar' AND OLD.status_servis != 'bayar' THEN

        SELECT
            COUNT(*),
            COALESCE(SUM(IFNULL(total_akhir, IFNULL(total_grand, IFNULL(total, 0)))), 0),
            MIN(tanggal),
            MAX(tanggal),
            COUNT(DISTINCT no_polisi)
        INTO
            v_total_transaksi,
            v_total_nominal,
            v_tanggal_pertama,
            v_tanggal_terakhir,
            v_total_motor
        FROM tblservice
        WHERE no_pelanggan = NEW.no_pelanggan
          AND status_servis = 'bayar';

        IF v_total_transaksi > 0 THEN
            SET v_rata_rata = v_total_nominal / v_total_transaksi;
        END IF;

        -- Rata-rata jarak kunjungan (hari) — algoritma dari FITMOTOR Access
        IF v_total_transaksi > 1 THEN
            SET v_rata_jarak = ROUND(
                DATEDIFF(v_tanggal_terakhir, v_tanggal_pertama) / (v_total_transaksi - 1),
            2);
        ELSE
            SET v_rata_jarak = 0;
        END IF;

        -- Interval tier: <=45 hari pakai 30, >45 hari pakai 60
        SET v_interval_hari = IF(v_rata_jarak > 0 AND v_rata_jarak <= 45, 30, 60);

        -- Estimasi berikutnya: formula Access: AKHIR + interval * CEIL((HARI_INI - AKHIR) / interval)
        SET v_lama_tidak_datang = DATEDIFF(CURDATE(), v_tanggal_terakhir);
        IF v_lama_tidak_datang >= 0 THEN
            SET v_estimasi = DATE_ADD(
                v_tanggal_terakhir,
                INTERVAL (v_interval_hari * CEIL(GREATEST(v_lama_tidak_datang, 1) / v_interval_hari)) DAY
            );
        ELSE
            SET v_estimasi = DATE_ADD(v_tanggal_terakhir, INTERVAL v_interval_hari DAY);
        END IF;

        -- Tier member berdasarkan total nominal
        IF v_total_nominal >= 10000000 THEN
            SET v_tier = 'Platinum';
        ELSEIF v_total_nominal >= 5000000 THEN
            SET v_tier = 'Gold';
        ELSEIF v_total_nominal >= 2000000 THEN
            SET v_tier = 'Silver';
        ELSE
            SET v_tier = 'Bronze';
        END IF;

        INSERT INTO statistik_pelanggan (
            no_pelanggan,
            total_transaksi,
            total_nominal,
            jumlah_kunjungan,
            kedatangan_terakhir,
            rata_rata_transaksi,
            status_member,
            tanggal_pertama_transaksi,
            tanggal_terakhir_transaksi,
            total_motor,
            lama_tidak_datang,
            rata_jarak_kunjungan,
            estimasi_datang_berikutnya
        ) VALUES (
            NEW.no_pelanggan,
            v_total_transaksi,
            v_total_nominal,
            v_total_transaksi,
            v_total_transaksi,
            v_rata_rata,
            v_tier,
            v_tanggal_pertama,
            v_tanggal_terakhir,
            v_total_motor,
            v_lama_tidak_datang,
            v_rata_jarak,
            v_estimasi
        ) ON DUPLICATE KEY UPDATE
            total_transaksi              = v_total_transaksi,
            total_nominal                = v_total_nominal,
            jumlah_kunjungan             = v_total_transaksi,
            kedatangan_terakhir          = v_total_transaksi,
            rata_rata_transaksi          = v_rata_rata,
            status_member                = v_tier,
            tanggal_pertama_transaksi    = v_tanggal_pertama,
            tanggal_terakhir_transaksi   = v_tanggal_terakhir,
            total_motor                  = v_total_motor,
            lama_tidak_datang            = v_lama_tidak_datang,
            rata_jarak_kunjungan         = v_rata_jarak,
            estimasi_datang_berikutnya   = v_estimasi,
            updated_at                   = NOW();

    END IF;
END//

DELIMITER ;

SHOW TRIGGERS LIKE 'tblservice';
