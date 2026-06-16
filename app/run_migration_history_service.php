<?php
/**
 * ============================================================================
 * MIGRATION RUNNER: History Service Pelanggan
 * ============================================================================
 * File ini untuk menjalankan migration database secara otomatis
 *
 * CARA PENGGUNAAN:
 * 1. Buka browser: http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/run_migration_history_service.php
 * 2. Klik tombol "Jalankan Migration"
 * 3. Tunggu sampai selesai
 *
 * Tanggal: 2025-12-26
 * ============================================================================
 */

// Start session
session_start();

// Include koneksi database
include "../config/koneksi.php";

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Array untuk menyimpan log
$migration_log = [];
$success_count = 0;
$error_count = 0;

/**
 * Function untuk menjalankan query dan log hasilnya
 */
function runQuery($koneksi, $query, $description, &$log, &$success, &$error) {
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        $log[] = [
            'status' => 'success',
            'description' => $description,
            'message' => 'Berhasil dijalankan'
        ];
        $success++;
        return true;
    } else {
        $error_msg = mysqli_error($koneksi);
        // Jika error karena sudah ada, anggap sukses
        if (strpos($error_msg, 'already exists') !== false || strpos($error_msg, 'Duplicate') !== false) {
            $log[] = [
                'status' => 'info',
                'description' => $description,
                'message' => 'Sudah ada sebelumnya (skip)'
            ];
            $success++;
            return true;
        }

        $log[] = [
            'status' => 'error',
            'description' => $description,
            'message' => $error_msg
        ];
        $error++;
        return false;
    }
}

/**
 * Function untuk cek apakah tabel sudah ada
 */
function tableExists($koneksi, $table_name) {
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '$table_name'");
    return mysqli_num_rows($result) > 0;
}

/**
 * Function untuk cek apakah kolom sudah ada
 */
function columnExists($koneksi, $table_name, $column_name) {
    $result = mysqli_query($koneksi, "SHOW COLUMNS FROM `$table_name` LIKE '$column_name'");
    return $result && mysqli_num_rows($result) > 0;
}

// ============================================================================
// PROSES MIGRATION
// ============================================================================

if (isset($_POST['run_migration'])) {

    $migration_log[] = [
        'status' => 'info',
        'description' => 'Migration Started',
        'message' => 'Memulai proses migration...'
    ];

    // ========================================================================
    // 1. CREATE TABLE: tb_history_service_pelanggan
    // ========================================================================
    if (!tableExists($koneksi, 'tb_history_service_pelanggan')) {
        $query = "CREATE TABLE `tb_history_service_pelanggan` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `no_pelanggan` VARCHAR(20) NOT NULL COMMENT 'Nomor pelanggan',
            `no_polisi` VARCHAR(20) NOT NULL COMMENT 'Nomor polisi kendaraan',
            `no_service` VARCHAR(30) NOT NULL COMMENT 'Nomor service unik',
            `tanggal_service` DATE NOT NULL COMMENT 'Tanggal service',
            `jam_service` VARCHAR(10) DEFAULT NULL COMMENT 'Jam service',
            `tipe_service` ENUM('reguler','jemput','garansi') DEFAULT 'reguler' COMMENT 'Tipe service',

            `total_jasa` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total biaya jasa',
            `total_barang` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total biaya barang/sparepart',
            `subtotal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Subtotal sebelum diskon',
            `diskon_member_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase diskon member',
            `diskon_member_nominal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Nominal diskon member',
            `diskon_tambahan_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase diskon tambahan',
            `diskon_tambahan_nominal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Nominal diskon tambahan',
            `total_diskon` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total semua diskon',
            `ppn_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase PPN',
            `ppn_nominal` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Nominal PPN',
            `total_bayar` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total akhir yang dibayar',
            `jumlah_bayar` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Jumlah uang yang diterima',
            `kembalian` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Kembalian',
            `metode_pembayaran` VARCHAR(50) DEFAULT 'Tunai' COMMENT 'Metode pembayaran',

            `km_service` INT DEFAULT 0 COMMENT 'KM saat service',
            `tipe_motor` VARCHAR(100) DEFAULT NULL COMMENT 'Tipe motor',
            `merek_motor` VARCHAR(50) DEFAULT NULL COMMENT 'Merek motor',
            `tahun_motor` VARCHAR(10) DEFAULT NULL COMMENT 'Tahun motor',

            `keluhan_list` TEXT DEFAULT NULL COMMENT 'Daftar keluhan dalam format JSON',
            `jumlah_keluhan` INT DEFAULT 0 COMMENT 'Jumlah keluhan',

            `temuan_list` TEXT DEFAULT NULL COMMENT 'Daftar temuan dalam format JSON',
            `jumlah_temuan` INT DEFAULT 0 COMMENT 'Jumlah temuan',
            `temuan_disetujui` INT DEFAULT 0 COMMENT 'Jumlah temuan yang disetujui',
            `temuan_ditolak` INT DEFAULT 0 COMMENT 'Jumlah temuan yang ditolak',

            `workorder_list` TEXT DEFAULT NULL COMMENT 'Daftar workorder dalam format JSON',
            `jumlah_workorder` INT DEFAULT 0 COMMENT 'Jumlah workorder',

            `barang_list` TEXT DEFAULT NULL COMMENT 'Daftar barang dalam format JSON',
            `jumlah_item_barang` INT DEFAULT 0 COMMENT 'Jumlah item barang',

            `jasa_list` TEXT DEFAULT NULL COMMENT 'Daftar jasa dalam format JSON',
            `jumlah_item_jasa` INT DEFAULT 0 COMMENT 'Jumlah item jasa',

            `kepala_mekanik1` VARCHAR(100) DEFAULT NULL COMMENT 'Kepala Mekanik 1',
            `kepala_mekanik2` VARCHAR(100) DEFAULT NULL COMMENT 'Kepala Mekanik 2',
            `persen_kepala1` INT DEFAULT 0 COMMENT 'Persentase kerja Kepala Mekanik 1',
            `persen_kepala2` INT DEFAULT 0 COMMENT 'Persentase kerja Kepala Mekanik 2',

            `admin1` VARCHAR(100) DEFAULT NULL COMMENT 'Admin 1',
            `admin2` VARCHAR(100) DEFAULT NULL COMMENT 'Admin 2',
            `persen_admin1` INT DEFAULT 0 COMMENT 'Persentase kerja Admin 1',
            `persen_admin2` INT DEFAULT 0 COMMENT 'Persentase kerja Admin 2',

            `mekanik1` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 1',
            `mekanik2` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 2',
            `mekanik3` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 3',
            `mekanik4` VARCHAR(100) DEFAULT NULL COMMENT 'Mekanik 4',
            `persen_mekanik1` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 1',
            `persen_mekanik2` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 2',
            `persen_mekanik3` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 3',
            `persen_mekanik4` INT DEFAULT 0 COMMENT 'Persentase kerja Mekanik 4',

            `status_member_sebelum` VARCHAR(50) DEFAULT 'Bronze' COMMENT 'Status member sebelum transaksi',
            `status_member_sesudah` VARCHAR(50) DEFAULT 'Bronze' COMMENT 'Status member setelah transaksi',
            `naik_tier` TINYINT(1) DEFAULT 0 COMMENT '1 jika naik tier setelah transaksi',
            `tier_baru` VARCHAR(50) DEFAULT NULL COMMENT 'Tier baru jika naik tier',

            `keterangan` TEXT DEFAULT NULL COMMENT 'Keterangan tambahan',
            `kode_cabang` VARCHAR(20) DEFAULT NULL COMMENT 'Kode cabang',
            `nama_cabang` VARCHAR(100) DEFAULT NULL COMMENT 'Nama cabang',

            `user_bayar` VARCHAR(50) DEFAULT NULL COMMENT 'User yang memproses pembayaran',
            `user_bayar_nama` VARCHAR(100) DEFAULT NULL COMMENT 'Nama user yang memproses',

            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Waktu record dibuat',
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Waktu record diupdate',

            UNIQUE KEY `uk_no_service` (`no_service`),
            INDEX `idx_pelanggan` (`no_pelanggan`),
            INDEX `idx_polisi` (`no_polisi`),
            INDEX `idx_tanggal` (`tanggal_service`),
            INDEX `idx_cabang` (`kode_cabang`),
            INDEX `idx_tipe` (`tipe_service`),
            INDEX `idx_member` (`status_member_sesudah`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='History lengkap service pelanggan termasuk keluhan, temuan, pengerjaan, dan mekanik'";

        runQuery($koneksi, $query, 'CREATE TABLE tb_history_service_pelanggan', $migration_log, $success_count, $error_count);
    } else {
        $migration_log[] = [
            'status' => 'info',
            'description' => 'CREATE TABLE tb_history_service_pelanggan',
            'message' => 'Tabel sudah ada (skip)'
        ];
        $success_count++;
    }

    // ========================================================================
    // 2. CREATE TABLE: tb_history_mekanik_servis
    // ========================================================================
    if (!tableExists($koneksi, 'tb_history_mekanik_servis')) {
        $query = "CREATE TABLE `tb_history_mekanik_servis` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `no_service` VARCHAR(30) NOT NULL COMMENT 'Nomor service',
            `tanggal_service` DATE NOT NULL COMMENT 'Tanggal service',
            `tipe_role` ENUM('kepala_mekanik','mekanik','admin') NOT NULL COMMENT 'Tipe role karyawan',
            `urutan` INT DEFAULT 1 COMMENT 'Urutan (1,2,3,4)',
            `kode_karyawan` VARCHAR(50) DEFAULT NULL COMMENT 'Kode karyawan',
            `nama_karyawan` VARCHAR(100) NOT NULL COMMENT 'Nama karyawan',
            `persen_kerja` INT DEFAULT 0 COMMENT 'Persentase kerja',
            `total_jasa_service` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total jasa service ini',
            `pendapatan_jasa` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Pendapatan dari jasa (persen x total)',
            `kode_cabang` VARCHAR(20) DEFAULT NULL COMMENT 'Kode cabang',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX `idx_no_service` (`no_service`),
            INDEX `idx_karyawan` (`nama_karyawan`),
            INDEX `idx_tanggal` (`tanggal_service`),
            INDEX `idx_role` (`tipe_role`),
            INDEX `idx_cabang` (`kode_cabang`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='History detail mekanik per service'";

        runQuery($koneksi, $query, 'CREATE TABLE tb_history_mekanik_servis', $migration_log, $success_count, $error_count);
    } else {
        $migration_log[] = [
            'status' => 'info',
            'description' => 'CREATE TABLE tb_history_mekanik_servis',
            'message' => 'Tabel sudah ada (skip)'
        ];
        $success_count++;
    }

    // ========================================================================
    // 3. CREATE TABLE: tb_log_naik_tier_member
    // ========================================================================
    if (!tableExists($koneksi, 'tb_log_naik_tier_member')) {
        $query = "CREATE TABLE `tb_log_naik_tier_member` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `no_pelanggan` VARCHAR(20) NOT NULL COMMENT 'Nomor pelanggan',
            `nama_pelanggan` VARCHAR(100) DEFAULT NULL COMMENT 'Nama pelanggan',
            `no_service` VARCHAR(30) NOT NULL COMMENT 'Nomor service yang menyebabkan naik tier',
            `tier_lama` VARCHAR(50) NOT NULL COMMENT 'Tier sebelumnya',
            `tier_baru` VARCHAR(50) NOT NULL COMMENT 'Tier baru',
            `total_nominal_saat_naik` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total nominal saat naik tier',
            `total_kunjungan_saat_naik` INT DEFAULT 0 COMMENT 'Total kunjungan saat naik tier',
            `diskon_lama` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Diskon tier lama',
            `diskon_baru` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Diskon tier baru',
            `kode_cabang` VARCHAR(20) DEFAULT NULL COMMENT 'Kode cabang',
            `notifikasi_terkirim` TINYINT(1) DEFAULT 0 COMMENT '1 jika notifikasi WA sudah terkirim',
            `tanggal_notifikasi` DATETIME DEFAULT NULL COMMENT 'Tanggal notifikasi dikirim',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX `idx_pelanggan` (`no_pelanggan`),
            INDEX `idx_service` (`no_service`),
            INDEX `idx_tier_baru` (`tier_baru`),
            INDEX `idx_tanggal` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Log kenaikan tier member pelanggan'";

        runQuery($koneksi, $query, 'CREATE TABLE tb_log_naik_tier_member', $migration_log, $success_count, $error_count);
    } else {
        $migration_log[] = [
            'status' => 'info',
            'description' => 'CREATE TABLE tb_log_naik_tier_member',
            'message' => 'Tabel sudah ada (skip)'
        ];
        $success_count++;
    }

    // ========================================================================
    // 4. ALTER TABLE: statistik_pelanggan - Tambah kolom baru
    // ========================================================================
    if (tableExists($koneksi, 'statistik_pelanggan')) {
        // Tambah kolom total_diskon_diberikan
        if (!columnExists($koneksi, 'statistik_pelanggan', 'total_diskon_diberikan')) {
            $query = "ALTER TABLE `statistik_pelanggan` ADD COLUMN `total_diskon_diberikan` DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total diskon yang sudah diberikan'";
            runQuery($koneksi, $query, 'ALTER TABLE statistik_pelanggan ADD total_diskon_diberikan', $migration_log, $success_count, $error_count);
        } else {
            $migration_log[] = ['status' => 'info', 'description' => 'ALTER TABLE statistik_pelanggan ADD total_diskon_diberikan', 'message' => 'Kolom sudah ada (skip)'];
            $success_count++;
        }

        // Tambah kolom jumlah_service_dengan_diskon
        if (!columnExists($koneksi, 'statistik_pelanggan', 'jumlah_service_dengan_diskon')) {
            $query = "ALTER TABLE `statistik_pelanggan` ADD COLUMN `jumlah_service_dengan_diskon` INT DEFAULT 0 COMMENT 'Jumlah service yang dapat diskon'";
            runQuery($koneksi, $query, 'ALTER TABLE statistik_pelanggan ADD jumlah_service_dengan_diskon', $migration_log, $success_count, $error_count);
        } else {
            $migration_log[] = ['status' => 'info', 'description' => 'ALTER TABLE statistik_pelanggan ADD jumlah_service_dengan_diskon', 'message' => 'Kolom sudah ada (skip)'];
            $success_count++;
        }

        // Tambah kolom tanggal_naik_tier_terakhir
        if (!columnExists($koneksi, 'statistik_pelanggan', 'tanggal_naik_tier_terakhir')) {
            $query = "ALTER TABLE `statistik_pelanggan` ADD COLUMN `tanggal_naik_tier_terakhir` DATE DEFAULT NULL COMMENT 'Tanggal terakhir naik tier'";
            runQuery($koneksi, $query, 'ALTER TABLE statistik_pelanggan ADD tanggal_naik_tier_terakhir', $migration_log, $success_count, $error_count);
        } else {
            $migration_log[] = ['status' => 'info', 'description' => 'ALTER TABLE statistik_pelanggan ADD tanggal_naik_tier_terakhir', 'message' => 'Kolom sudah ada (skip)'];
            $success_count++;
        }

        // Tambah kolom tier_history
        if (!columnExists($koneksi, 'statistik_pelanggan', 'tier_history')) {
            $query = "ALTER TABLE `statistik_pelanggan` ADD COLUMN `tier_history` TEXT DEFAULT NULL COMMENT 'History perubahan tier dalam JSON'";
            runQuery($koneksi, $query, 'ALTER TABLE statistik_pelanggan ADD tier_history', $migration_log, $success_count, $error_count);
        } else {
            $migration_log[] = ['status' => 'info', 'description' => 'ALTER TABLE statistik_pelanggan ADD tier_history', 'message' => 'Kolom sudah ada (skip)'];
            $success_count++;
        }
    } else {
        $migration_log[] = [
            'status' => 'warning',
            'description' => 'ALTER TABLE statistik_pelanggan',
            'message' => 'Tabel statistik_pelanggan tidak ditemukan'
        ];
    }

    // ========================================================================
    // 5. ALTER TABLE: master_kategori_member - Tambah kolom diskon_persen
    // ========================================================================
    if (tableExists($koneksi, 'master_kategori_member')) {
        if (!columnExists($koneksi, 'master_kategori_member', 'diskon_persen')) {
            $query = "ALTER TABLE `master_kategori_member` ADD COLUMN `diskon_persen` DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Persentase diskon untuk tier ini'";
            runQuery($koneksi, $query, 'ALTER TABLE master_kategori_member ADD diskon_persen', $migration_log, $success_count, $error_count);

            // Update default diskon
            $query_update = "UPDATE `master_kategori_member` SET `diskon_persen` = CASE
                WHEN `nama_kategori` = 'Bronze' THEN 0
                WHEN `nama_kategori` = 'Silver' THEN 10
                WHEN `nama_kategori` = 'Gold' THEN 15
                WHEN `nama_kategori` = 'Platinum' THEN 20
                ELSE 0
            END WHERE `diskon_persen` = 0 OR `diskon_persen` IS NULL";
            runQuery($koneksi, $query_update, 'UPDATE master_kategori_member SET default diskon_persen', $migration_log, $success_count, $error_count);
        } else {
            $migration_log[] = ['status' => 'info', 'description' => 'ALTER TABLE master_kategori_member ADD diskon_persen', 'message' => 'Kolom sudah ada (skip)'];
            $success_count++;
        }
    } else {
        $migration_log[] = [
            'status' => 'warning',
            'description' => 'ALTER TABLE master_kategori_member',
            'message' => 'Tabel master_kategori_member tidak ditemukan'
        ];
    }

    // ========================================================================
    // 6. CREATE VIEW: v_history_service_pelanggan_summary
    // ========================================================================
    $query = "CREATE OR REPLACE VIEW `v_history_service_pelanggan_summary` AS
        SELECT
            h.no_pelanggan,
            p.namapelanggan,
            p.telephone,
            COUNT(h.id) AS total_service_history,
            SUM(h.total_bayar) AS total_nominal_history,
            AVG(h.total_bayar) AS rata_rata_transaksi,
            MAX(h.tanggal_service) AS terakhir_service,
            MIN(h.tanggal_service) AS pertama_service,
            SUM(h.jumlah_keluhan) AS total_keluhan,
            SUM(h.jumlah_temuan) AS total_temuan,
            SUM(h.temuan_disetujui) AS total_temuan_disetujui,
            SUM(h.diskon_member_nominal) AS total_diskon_diterima,
            MAX(h.status_member_sesudah) AS status_member_terakhir,
            SUM(h.naik_tier) AS jumlah_naik_tier
        FROM tb_history_service_pelanggan h
        LEFT JOIN tblpelanggan p ON h.no_pelanggan = p.nopelanggan
        GROUP BY h.no_pelanggan";
    runQuery($koneksi, $query, 'CREATE VIEW v_history_service_pelanggan_summary', $migration_log, $success_count, $error_count);

    // ========================================================================
    // 7. CREATE VIEW: v_history_mekanik_summary
    // ========================================================================
    $query = "CREATE OR REPLACE VIEW `v_history_mekanik_summary` AS
        SELECT
            hm.nama_karyawan,
            hm.tipe_role,
            hm.kode_cabang,
            COUNT(DISTINCT hm.no_service) AS total_service,
            SUM(hm.pendapatan_jasa) AS total_pendapatan,
            AVG(hm.persen_kerja) AS rata_rata_persen_kerja,
            MAX(hm.tanggal_service) AS terakhir_kerja,
            MIN(hm.tanggal_service) AS pertama_kerja
        FROM tb_history_mekanik_servis hm
        GROUP BY hm.nama_karyawan, hm.tipe_role, hm.kode_cabang";
    runQuery($koneksi, $query, 'CREATE VIEW v_history_mekanik_summary', $migration_log, $success_count, $error_count);

    // ========================================================================
    // 8. CREATE VIEW: v_riwayat_kendaraan_lengkap
    // ========================================================================
    $query = "CREATE OR REPLACE VIEW `v_riwayat_kendaraan_lengkap` AS
        SELECT
            h.no_polisi,
            h.no_pelanggan,
            k.jenis AS jenis_motor,
            k.tipe AS tipe_motor_detail,
            pm.merek AS merek_motor,
            COUNT(h.id) AS total_service,
            SUM(h.total_bayar) AS total_biaya,
            MAX(h.km_service) AS km_terakhir,
            MAX(h.tanggal_service) AS service_terakhir,
            MIN(h.tanggal_service) AS service_pertama
        FROM tb_history_service_pelanggan h
        LEFT JOIN tblkendaraan k ON h.no_polisi = k.nopolisi
        LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
        GROUP BY h.no_polisi";
    runQuery($koneksi, $query, 'CREATE VIEW v_riwayat_kendaraan_lengkap', $migration_log, $success_count, $error_count);

    // ========================================================================
    // SELESAI
    // ========================================================================
    $migration_log[] = [
        'status' => 'success',
        'description' => 'Migration Completed',
        'message' => "Berhasil: $success_count | Error: $error_count"
    ];
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration: History Service Pelanggan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .migration-card { max-width: 900px; margin: 50px auto; }
        .log-item { padding: 10px; border-bottom: 1px solid #eee; }
        .log-item:last-child { border-bottom: none; }
        .log-success { background-color: #d4edda; }
        .log-error { background-color: #f8d7da; }
        .log-info { background-color: #d1ecf1; }
        .log-warning { background-color: #fff3cd; }
        .pre-code { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card migration-card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-database"></i> Migration: History Service Pelanggan
                </h4>
            </div>
            <div class="card-body">
                <?php if (!isset($_POST['run_migration'])): ?>
                    <!-- FORM SEBELUM MIGRATION -->
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Informasi Migration</h5>
                        <p>Migration ini akan membuat:</p>
                        <ul>
                            <li><strong>tb_history_service_pelanggan</strong> - History lengkap service (keluhan, temuan, workorder, barang, jasa, mekanik)</li>
                            <li><strong>tb_history_mekanik_servis</strong> - History detail mekanik per service</li>
                            <li><strong>tb_log_naik_tier_member</strong> - Log kenaikan tier member</li>
                            <li><strong>3 Views</strong> - Summary untuk reporting</li>
                            <li><strong>Kolom baru</strong> - di statistik_pelanggan dan master_kategori_member</li>
                        </ul>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Penting:</strong> Pastikan Anda sudah backup database sebelum menjalankan migration!
                    </div>

                    <form method="post" onsubmit="return confirm('Apakah Anda yakin ingin menjalankan migration?');">
                        <button type="submit" name="run_migration" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-play"></i> Jalankan Migration
                        </button>
                    </form>

                <?php else: ?>
                    <!-- HASIL MIGRATION -->
                    <div class="alert <?php echo $error_count > 0 ? 'alert-warning' : 'alert-success'; ?>">
                        <h5>
                            <i class="fas <?php echo $error_count > 0 ? 'fa-exclamation-triangle' : 'fa-check-circle'; ?>"></i>
                            Migration <?php echo $error_count > 0 ? 'Selesai dengan Warning' : 'Berhasil'; ?>
                        </h5>
                        <p>
                            <strong>Berhasil:</strong> <?php echo $success_count; ?> |
                            <strong>Error:</strong> <?php echo $error_count; ?>
                        </p>
                    </div>

                    <h5 class="mb-3"><i class="fas fa-list"></i> Log Migration</h5>
                    <div class="border rounded">
                        <?php foreach ($migration_log as $log): ?>
                            <div class="log-item log-<?php echo $log['status']; ?>">
                                <strong>
                                    <?php if ($log['status'] == 'success'): ?>
                                        <i class="fas fa-check text-success"></i>
                                    <?php elseif ($log['status'] == 'error'): ?>
                                        <i class="fas fa-times text-danger"></i>
                                    <?php elseif ($log['status'] == 'warning'): ?>
                                        <i class="fas fa-exclamation-triangle text-warning"></i>
                                    <?php else: ?>
                                        <i class="fas fa-info-circle text-info"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($log['description']); ?>
                                </strong>
                                <br>
                                <small class="text-muted"><?php echo htmlspecialchars($log['message']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <a href="run_migration_history_service.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-redo"></i> Jalankan Ulang
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="index.php" class="btn btn-primary btn-block">
                                <i class="fas fa-home"></i> Kembali ke Dashboard
                            </a>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
            <div class="card-footer text-muted">
                <small>
                    <i class="fas fa-clock"></i> <?php echo date('Y-m-d H:i:s'); ?> |
                    <i class="fas fa-database"></i> Database: <?php echo defined('DB_NAME') ? DB_NAME : 'fitmotor_dbbengkel'; ?>
                </small>
            </div>
        </div>

        <?php if (!isset($_POST['run_migration'])): ?>
        <!-- Preview Tables -->
        <div class="card migration-card shadow mt-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-eye"></i> Preview Struktur Tabel</h5>
            </div>
            <div class="card-body">
                <h6>1. tb_history_service_pelanggan</h6>
                <pre class="pre-code">- id, no_pelanggan, no_polisi, no_service, tanggal_service
- total_jasa, total_barang, subtotal, diskon_member_*, total_diskon
- keluhan_list (JSON), temuan_list (JSON), workorder_list (JSON)
- barang_list (JSON), jasa_list (JSON)
- kepala_mekanik1-2, admin1-2, mekanik1-4 (dengan persen)
- status_member_sebelum, status_member_sesudah, naik_tier</pre>

                <h6 class="mt-3">2. tb_history_mekanik_servis</h6>
                <pre class="pre-code">- id, no_service, tanggal_service
- tipe_role (kepala_mekanik/mekanik/admin), urutan
- nama_karyawan, persen_kerja, pendapatan_jasa</pre>

                <h6 class="mt-3">3. tb_log_naik_tier_member</h6>
                <pre class="pre-code">- id, no_pelanggan, nama_pelanggan, no_service
- tier_lama, tier_baru
- diskon_lama, diskon_baru
- notifikasi_terkirim, tanggal_notifikasi</pre>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
