<?php
/**
 * Database Migration: Master Temuan Jasa Mapping
 * Jalankan file ini sekali untuk membuat tabel yang diperlukan
 */

session_start();
if (empty($_SESSION['_iduser'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

include "../config/koneksi.php";

echo "<h2>Database Migration: Master Temuan Jasa Mapping</h2>";
echo "<pre>";

$errors = [];
$success = [];

// 1. Create tbmaster_temuan_jasa_mapping table
$sql1 = "CREATE TABLE IF NOT EXISTS `tbmaster_temuan_jasa_mapping` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_temuan` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Link ke tbmaster_temuan.kode_temuan',
  `noitem` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Link ke tblitem (field: noitem) dengan jenis=SERVIS',
  `is_primary` tinyint(1) DEFAULT 0 COMMENT '1=jasa utama/rekomendasi, 0=alternatif',
  `prioritas` int(11) DEFAULT 1 COMMENT 'Urutan prioritas tampil (1=tertinggi)',
  `waktu_estimasi` int(11) DEFAULT 0 COMMENT 'Estimasi waktu pengerjaan dalam menit',
  `keterangan` varchar(255) DEFAULT NULL COMMENT 'Keterangan tambahan',
  `status_aktif` tinyint(1) DEFAULT 1,
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_by` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_temuan_jasa` (`kode_temuan`, `noitem`),
  KEY `idx_kode_temuan` (`kode_temuan`),
  KEY `idx_noitem` (`noitem`),
  KEY `idx_status_aktif` (`status_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Mapping temuan ke jasa/servis untuk auto-suggest'";

if(mysqli_query($koneksi, $sql1)) {
    $success[] = "Tabel tbmaster_temuan_jasa_mapping berhasil dibuat/sudah ada";
} else {
    $errors[] = "Gagal membuat tbmaster_temuan_jasa_mapping: " . mysqli_error($koneksi);
}

// 2. Create tbservis_temuan_part table (optional - for tracking temuan-part relationship)
$sql2 = "CREATE TABLE IF NOT EXISTS `tbservis_temuan_part` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `temuan_id` int(11) NOT NULL COMMENT 'Link ke tbservis_temuan.id',
  `no_service` varchar(50) NOT NULL,
  `kode_barang` varchar(20) NOT NULL COMMENT 'Kode part dari tblitem',
  `nama_barang` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `harga_satuan` double DEFAULT 0,
  `total_harga` double DEFAULT 0,
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari saran mapping, 0=manual',
  `mapping_id` int(11) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan_barang_mapping jika dari saran',
  `status` enum('pending','approved','rejected','added_to_service') DEFAULT 'pending',
  `added_to_service_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_temuan_id` (`temuan_id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Part yang ditambahkan bersama temuan'";

if(mysqli_query($koneksi, $sql2)) {
    $success[] = "Tabel tbservis_temuan_part berhasil dibuat/sudah ada";
} else {
    $errors[] = "Gagal membuat tbservis_temuan_part: " . mysqli_error($koneksi);
}

// 3. Create tbservis_temuan_jasa table (optional - for tracking temuan-jasa relationship)
$sql3 = "CREATE TABLE IF NOT EXISTS `tbservis_temuan_jasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `temuan_id` int(11) NOT NULL COMMENT 'Link ke tbservis_temuan.id',
  `no_service` varchar(50) NOT NULL,
  `kode_jasa` varchar(20) NOT NULL COMMENT 'Kode jasa dari tblitem (jenis=SERVIS)',
  `nama_jasa` varchar(100) DEFAULT NULL,
  `harga` double DEFAULT 0,
  `waktu_estimasi` int(11) DEFAULT 0 COMMENT 'Estimasi waktu dalam menit',
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari saran mapping, 0=manual',
  `mapping_id` int(11) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan_jasa_mapping jika dari saran',
  `status` enum('pending','approved','rejected','added_to_service') DEFAULT 'pending',
  `added_to_service_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_temuan_id` (`temuan_id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Jasa yang ditambahkan bersama temuan'";

if(mysqli_query($koneksi, $sql3)) {
    $success[] = "Tabel tbservis_temuan_jasa berhasil dibuat/sudah ada";
} else {
    $errors[] = "Gagal membuat tbservis_temuan_jasa: " . mysqli_error($koneksi);
}

// Results
echo "\n=== HASIL MIGRASI ===\n\n";

if(count($success) > 0) {
    echo "SUKSES:\n";
    foreach($success as $s) {
        echo "  [OK] $s\n";
    }
}

if(count($errors) > 0) {
    echo "\nERROR:\n";
    foreach($errors as $e) {
        echo "  [FAIL] $e\n";
    }
}

echo "\n</pre>";

echo "<hr>";
echo "<p><strong>Migrasi selesai.</strong></p>";
echo "<p><a href='master-temuan-mapping-jasa.php' class='btn btn-primary'>Ke Halaman Master Mapping Temuan - Jasa</a></p>";
echo "<p><a href='index.php'>Kembali ke Dashboard</a></p>";
?>
