<?php
/**
 * DATABASE MIGRATION: CUSTOM ITEMS FEATURE
 * 
 * Purpose: Create table for custom/non-master items
 * Run once: http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/database_migration_custom_items.php
 */

// Include database connection
require_once('../config/koneksi.php');

// Start output
echo "<html><head><title>Migration: Custom Items</title></head><body>";
echo "<h2>=== DATABASE MIGRATION: CUSTOM ITEMS FEATURE ===</h2>";

$success = 0;
$errors = [];

// ==========================================
// STEP 1: Create tbmaster_barang_custom table
// ==========================================
echo "<h3>[1/2] Creating tbmaster_barang_custom table...</h3>";

$sql_create_table = "CREATE TABLE IF NOT EXISTS `tbmaster_barang_custom` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_barang` VARCHAR(50) NOT NULL,
  `nama_barang` VARCHAR(255) NOT NULL,
  `harga_jual` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `satuan` VARCHAR(20) DEFAULT 'PCS',
  `kategori` VARCHAR(50) DEFAULT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  `status_aktif` ENUM('1','0') DEFAULT '1',
  `created_by` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kode_barang` (`kode_barang`),
  KEY `status_aktif` (`status_aktif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if(mysqli_query($koneksi, $sql_create_table)) {
    echo "✅ Table tbmaster_barang_custom created/verified<br>";
    $success++;
} else {
    $error_msg = "❌ Error creating table: " . mysqli_error($koneksi);
    echo "$error_msg<br>";
    $errors[] = $error_msg;
}

// ==========================================
// STEP 2: Insert sample data
// ==========================================
echo "<br><h3>[2/2] Inserting sample custom items...</h3>";

// Check if sample data already exists
$check_data = mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM tbmaster_barang_custom");
$row = mysqli_fetch_assoc($check_data);

if($row['cnt'] > 0) {
    echo "ℹ️ Sample data already exists (". $row['cnt'] ." items). Skipping insert.<br>";
    $success++;
} else {
    $sql_insert_samples = "INSERT INTO `tbmaster_barang_custom` 
    (kode_barang, nama_barang, harga_jual, satuan, kategori, deskripsi, created_by) 
    VALUES
    ('CUSTOM-00001', 'Spare Part Import Khusus', 250000, 'PCS', 'IMPORT', 'Part import yang tidak tersedia di master', 'System'),
    ('CUSTOM-00002', 'Jasa Modifikasi Custom', 500000, 'PAKET', 'JASA', 'Jasa modifikasi sesuai permintaan pelanggan', 'System'),
    ('CUSTOM-00003', 'Part Langka - Discontinued', 750000, 'PCS', 'LAINNYA', 'Part discontinue yang sulit dicari', 'System'),
    ('CUSTOM-00004', 'Aksesoris Custom Motor', 150000, 'PCS', 'AKSESORIS', 'Aksesoris tambahan custom', 'System'),
    ('CUSTOM-00005', 'Komponen Modifikasi Racing', 1200000, 'SET', 'MODIFIKASI', 'Set komponen racing custom', 'System')";
    
    if(mysqli_query($koneksi, $sql_insert_samples)) {
        echo "✅ Sample data inserted (5 items)<br>";
        $success++;
    } else {
        $error_msg = "❌ Error inserting samples: " . mysqli_error($koneksi);
        echo "$error_msg<br>";
        $errors[] = $error_msg;
    }
}

// ==========================================
// SUMMARY
// ==========================================
echo "<br><h2>===========================================</h2>";
echo "<h2>MIGRATION SUMMARY</h2>";
echo "<h2>===========================================</h2>";

if(count($errors) > 0) {
    echo "<h3 style='color: red;'>❌ MIGRATION COMPLETED WITH ERRORS</h3>";
    echo "<p><strong>Errors:</strong></p><ul>";
    foreach($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
} else {
    echo "<h3 style='color: green;'>✅ MIGRATION COMPLETED SUCCESSFULLY!</h3>";
}

echo "<p><strong>Success:</strong> $success/2 operations</p>";

echo "<br><h2>===========================================</h2>";
echo "<h2>NEXT STEPS:</h2>";
echo "<h2>===========================================</h2>";
echo "<ol>";
echo "<li>✅ Verify table structure in phpMyAdmin</li>";
echo "<li>⏭️ Create admin page: master-barang-custom.php</li>";
echo "<li>⏭️ Create modal: modal-input-barang-custom.php</li>";
echo "<li>⏭️ Integrate in service input pages</li>";
echo "<li>⏭️ Test complete flow</li>";
echo "</ol>";

echo "</body></html>";
?>
