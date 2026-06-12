<?php
/**
 * Database Migration: Tabel Penawaran Jasa
 * Jalankan file ini sekali untuk membuat tabel yang diperlukan
 */

session_start();
if (empty($_SESSION['_iduser'])) {
    die("Akses ditolak. Silakan login terlebih dahulu.");
}

include "../config/koneksi.php";

echo "<h2>Database Migration: Tabel Penawaran Jasa</h2>";
echo "<pre>";

$errors = [];
$success = [];

// 1. Create tbservis_penawaran_jasa table
$sql1 = "CREATE TABLE IF NOT EXISTS `tbservis_penawaran_jasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `no_service` varchar(50) NOT NULL,
  `temuan_id` int(11) DEFAULT NULL COMMENT 'Link ke tbservis_temuan.id',
  `kode_jasa` varchar(50) NOT NULL COMMENT 'Kode jasa dari tblitem (jenis=SERVIS)',
  `nama_jasa` varchar(255) DEFAULT NULL,
  `harga` double DEFAULT 0,
  `waktu_estimasi` int(11) DEFAULT 0 COMMENT 'Estimasi waktu dalam menit',
  `is_from_suggestion` tinyint(1) DEFAULT 0 COMMENT '1=dari mapping, 0=manual',
  `mapping_id` int(11) DEFAULT NULL COMMENT 'Link ke tbmaster_temuan_jasa_mapping jika dari saran',
  `status_penawaran` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `alasan_tolak` varchar(100) DEFAULT NULL,
  `keterangan_tolak` text DEFAULT NULL,
  `user_penawaran` varchar(100) DEFAULT NULL COMMENT 'User yang menambahkan penawaran',
  `user_respon` varchar(100) DEFAULT NULL COMMENT 'User yang approve/reject',
  `tanggal_respon` datetime DEFAULT NULL,
  `catatan_penawaran` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_no_service` (`no_service`),
  KEY `idx_temuan_id` (`temuan_id`),
  KEY `idx_status` (`status_penawaran`),
  KEY `idx_kode_jasa` (`kode_jasa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Penawaran jasa yang menunggu approval'";

if(mysqli_query($koneksi, $sql1)) {
    $success[] = "Tabel tbservis_penawaran_jasa berhasil dibuat/sudah ada";
} else {
    $errors[] = "Gagal membuat tbservis_penawaran_jasa: " . mysqli_error($koneksi);
}

// 2. Add index for better performance
$sql2 = "ALTER TABLE `tbservis_penawaran_jasa` ADD INDEX `idx_service_status` (`no_service`, `status_penawaran`)";
mysqli_query($koneksi, $sql2); // Ignore if already exists

// 3. Create tbmaster_temuan_jasa_mapping if not exists
$sql3 = "CREATE TABLE IF NOT EXISTS `tbmaster_temuan_jasa_mapping` (
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

if(mysqli_query($koneksi, $sql3)) {
    $success[] = "Tabel tbmaster_temuan_jasa_mapping berhasil dibuat/sudah ada";
} else {
    $errors[] = "Gagal membuat tbmaster_temuan_jasa_mapping: " . mysqli_error($koneksi);
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
