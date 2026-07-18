-- FIX KRITIS: trg_after_service_bayar (dibuat 2026-05-25) memanggil fungsi
-- fn_get_status_member_nominal() dan fn_get_status_member_kunjungan() yang TIDAK ADA
-- di database. Akibat: setiap UPDATE tblservice SET status_servis='bayar' (tombol
-- PROSES BAYAR reguler & jemput) gagal dengan error SQL sejak trigger ini dibuat.
-- Terbukti via testing E2E 2026-07-04: 0 baris status_servis='bayar' ter-update sejak
-- 2026-06-21 15:04:57 (itu timestamp bulk migration, bukan transaksi asli) —
-- pembayaran reguler/jemput lewat web nonaktif total selama ini.
--
-- Ada trigger duplikat trg_update_statistik_after_payment (dibuat 2026-06-21) yang
-- menghitung tier member dengan threshold nominal hardcode inline (bukan panggil
-- fungsi). Fix ini mengganti pola yang sama di trigger lama: query langsung ke
-- master_kategori_member (tabel master yang sudah berisi threshold nominal & kunjungan)
-- alih-alih memanggil fungsi yang hilang. Bagian insert master_kedatangan_pelanggan
-- (tracking kunjungan ke-berapa, unik di trigger ini) dipertahankan utuh.

DROP TRIGGER IF EXISTS `trg_after_service_bayar`;

DELIMITER $$
CREATE DEFINER=`fitmotor_LOGIN`@`%` TRIGGER `trg_after_service_bayar` AFTER UPDATE ON `tblservice` FOR EACH ROW BEGIN
    DECLARE v_total_transaksi INT DEFAULT 0;
    DECLARE v_total_nominal DECIMAL(15,2) DEFAULT 0.00;
    DECLARE v_jumlah_kunjungan INT DEFAULT 0;
    DECLARE v_rata_rata DECIMAL(15,2) DEFAULT 0.00;
    DECLARE v_status_member VARCHAR(20) DEFAULT 'Bronze';
    DECLARE v_kategori_member_kunjungan VARCHAR(20) DEFAULT 'Bronze';
    DECLARE v_tanggal_pertama DATE DEFAULT NULL;
    DECLARE v_tanggal_terakhir DATE DEFAULT NULL;
    DECLARE v_lama_tidak_datang INT DEFAULT NULL;
    DECLARE v_lama_pelanggan INT DEFAULT NULL;
    DECLARE v_estimasi_datang DATE DEFAULT NULL;
    DECLARE v_total_motor INT DEFAULT 0;

    DECLARE v_kedatangan_ke INT DEFAULT 1;
    DECLARE v_tanggal_sebelumnya DATE DEFAULT NULL;
    DECLARE v_jarak_hari INT DEFAULT 0;
    DECLARE v_jumlah_item INT DEFAULT 0;
    DECLARE v_rata2_per_item DECIMAL(15,2) DEFAULT 0.00;
    DECLARE v_rata_jarak_kunjungan DECIMAL(10,2) DEFAULT 0.00;

    IF NEW.status_servis = 'bayar' AND OLD.status_servis != 'bayar' THEN

        SELECT COUNT(*) INTO v_total_transaksi
        FROM tblservice
        WHERE no_pelanggan = NEW.no_pelanggan
        AND status_servis = 'bayar';

        SELECT COALESCE(SUM(total_akhir), 0) INTO v_total_nominal
        FROM tblservice
        WHERE no_pelanggan = NEW.no_pelanggan
        AND status_servis = 'bayar';

        SET v_jumlah_kunjungan = v_total_transaksi;

        IF v_total_transaksi > 0 THEN
            SET v_rata_rata = v_total_nominal / v_total_transaksi;
        END IF;

        -- FIX: ganti fn_get_status_member_nominal() (tidak ada) dengan query master_kategori_member
        SELECT `nama_kategori` INTO v_status_member
        FROM `master_kategori_member`
        WHERE `tipe_kategori` = 'nominal'
          AND v_total_nominal >= `min_value`
          AND (`max_value` IS NULL OR v_total_nominal <= `max_value`)
        ORDER BY `min_value` DESC LIMIT 1;

        -- FIX: ganti fn_get_status_member_kunjungan() (tidak ada) dengan query master_kategori_member
        SELECT `nama_kategori` INTO v_kategori_member_kunjungan
        FROM `master_kategori_member`
        WHERE `tipe_kategori` = 'kunjungan'
          AND v_jumlah_kunjungan >= `min_value`
          AND (`max_value` IS NULL OR v_jumlah_kunjungan <= `max_value`)
        ORDER BY `min_value` DESC LIMIT 1;

        SELECT MIN(tanggal), MAX(tanggal) INTO v_tanggal_pertama, v_tanggal_terakhir
        FROM tblservice
        WHERE no_pelanggan = NEW.no_pelanggan
        AND status_servis = 'bayar';

        SET v_lama_tidak_datang = DATEDIFF(CURDATE(), v_tanggal_terakhir);

        SET v_lama_pelanggan = DATEDIFF(CURDATE(), v_tanggal_pertama);

        SET v_estimasi_datang = DATE_ADD(v_tanggal_terakhir, INTERVAL 30 DAY);

        SELECT COUNT(DISTINCT no_polisi) INTO v_total_motor
        FROM tblservice
        WHERE no_pelanggan = NEW.no_pelanggan
        AND status_servis = 'bayar';

        INSERT INTO statistik_pelanggan (
            no_pelanggan,
            total_transaksi,
            total_nominal,
            jumlah_kunjungan,
            rata_rata_transaksi,
            status_member,
            kategori_member_kunjungan,
            tanggal_pertama_transaksi,
            tanggal_terakhir_transaksi,
            lama_tidak_datang,
            lama_menjadi_pelanggan,
            estimasi_datang_berikutnya,
            total_motor
        ) VALUES (
            NEW.no_pelanggan,
            v_total_transaksi,
            v_total_nominal,
            v_jumlah_kunjungan,
            v_rata_rata,
            v_status_member,
            v_kategori_member_kunjungan,
            v_tanggal_pertama,
            v_tanggal_terakhir,
            v_lama_tidak_datang,
            v_lama_pelanggan,
            v_estimasi_datang,
            v_total_motor
        )
        ON DUPLICATE KEY UPDATE
            total_transaksi = v_total_transaksi,
            total_nominal = v_total_nominal,
            jumlah_kunjungan = v_jumlah_kunjungan,
            rata_rata_transaksi = v_rata_rata,
            status_member = v_status_member,
            kategori_member_kunjungan = v_kategori_member_kunjungan,
            tanggal_terakhir_transaksi = v_tanggal_terakhir,
            lama_tidak_datang = v_lama_tidak_datang,
            lama_menjadi_pelanggan = v_lama_pelanggan,
            estimasi_datang_berikutnya = v_estimasi_datang,
            total_motor = v_total_motor,
            updated_at = CURRENT_TIMESTAMP;

        IF NOT EXISTS (
            SELECT 1 FROM master_kedatangan_pelanggan
            WHERE no_service = NEW.no_service
        ) THEN

            SELECT
                COALESCE(MAX(kedatangan_ke), 0) + 1,
                MAX(tanggal_datang)
            INTO
                v_kedatangan_ke,
                v_tanggal_sebelumnya
            FROM master_kedatangan_pelanggan
            WHERE no_pelanggan = NEW.no_pelanggan;

            IF v_kedatangan_ke = 1 THEN
                SET v_tanggal_sebelumnya = NULL;
                SET v_jarak_hari = 0;
            ELSE
                SET v_jarak_hari = DATEDIFF(NEW.tanggal, v_tanggal_sebelumnya);
            END IF;

            SELECT
                (SELECT COUNT(*) FROM tblservis_barang WHERE no_service = NEW.no_service) +
                (SELECT COUNT(*) FROM tblservis_jasa WHERE no_service = NEW.no_service)
            INTO v_jumlah_item;

            IF v_jumlah_item > 0 THEN
                SET v_rata2_per_item = NEW.total_akhir / v_jumlah_item;
            END IF;

            SELECT AVG(jarak_hari) INTO v_rata_jarak_kunjungan
            FROM master_kedatangan_pelanggan
            WHERE no_pelanggan = NEW.no_pelanggan
            AND jarak_hari > 0;

            INSERT INTO master_kedatangan_pelanggan (
                no_pelanggan,
                no_service,
                kedatangan_ke,
                tanggal_datang,
                tanggal_sebelumnya,
                jarak_hari,
                total_transaksi,
                jumlah_item,
                rata2_nilai_per_item,
                estimasi_datang_berikut,
                status_garansi,
                keterangan
            ) VALUES (
                NEW.no_pelanggan,
                NEW.no_service,
                v_kedatangan_ke,
                NEW.tanggal,
                v_tanggal_sebelumnya,
                v_jarak_hari,
                NEW.total_akhir,
                v_jumlah_item,
                v_rata2_per_item,
                DATE_ADD(NEW.tanggal, INTERVAL 30 DAY),
                'aktif',
                CONCAT('Kunjungan ke-', v_kedatangan_ke, ' pelanggan')
            );

            UPDATE statistik_pelanggan
            SET kedatangan_terakhir = v_kedatangan_ke,
                rata_jarak_kunjungan = COALESCE(v_rata_jarak_kunjungan, 0)
            WHERE no_pelanggan = NEW.no_pelanggan;

        END IF;

    END IF;
END$$
DELIMITER ;
