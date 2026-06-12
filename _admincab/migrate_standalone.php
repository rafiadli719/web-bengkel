<?php
include "../config/koneksi.php";

echo "=== STARTING DATABASE MIGRATION ===\n\n";

$migration_steps = [
    "Add tipe_item column" => "ALTER TABLE `tblitem` ADD COLUMN `tipe_item` ENUM('ORI', 'NON_ORI') DEFAULT 'NON_ORI' COMMENT 'ORI=Genuine Part, NON_ORI=Aftermarket/Imitasi'",
    "Add merek column" => "ALTER TABLE `tblitem` ADD COLUMN `merek` VARCHAR(50) NULL COMMENT 'Merek untuk ORI (Honda, Yamaha, Suzuki, dll)'",
    "Add kode_part_resmi column" => "ALTER TABLE `tblitem` ADD COLUMN `kode_part_resmi` VARCHAR(50) NULL COMMENT 'Kode part number resmi untuk ORI'",
    "Add nama_part_resmi column" => "ALTER TABLE `tblitem` ADD COLUMN `nama_part_resmi` VARCHAR(100) NULL COMMENT 'Nama part resmi sesuai catalog'",
    "Add penggunaan_motor column" => "ALTER TABLE `tblitem` ADD COLUMN `penggunaan_motor` VARCHAR(100) NULL COMMENT 'Penggunaan motor untuk NON-ORI'",
    "Add merek_tipe column" => "ALTER TABLE `tblitem` ADD COLUMN `merek_tipe` VARCHAR(100) NULL COMMENT 'Merek/Tipe/Ukuran untuk NON-ORI'",
    "Add kategori_rak column" => "ALTER TABLE `tblitem` ADD COLUMN `kategori_rak` VARCHAR(10) NULL COMMENT 'Kategori rak untuk auto-generate code NON-ORI'",
    "Add status_validasi column" => "ALTER TABLE `tblitem` ADD COLUMN `status_validasi` ENUM('pending_validation', 'validated', 'rejected') DEFAULT 'pending_validation' COMMENT 'Status validasi item'",
    "Add created_by column" => "ALTER TABLE `tblitem` ADD COLUMN `created_by` INT(11) NULL COMMENT 'User ID yang membuat item'",
    "Add validated_by column" => "ALTER TABLE `tblitem` ADD COLUMN `validated_by` INT(11) NULL COMMENT 'User ID yang memvalidasi item'",
    "Add created_at column" => "ALTER TABLE `tblitem` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    "Add updated_at column" => "ALTER TABLE `tblitem` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
];

$index_steps = [
    "Add tipe_item index" => "ALTER TABLE `tblitem` ADD INDEX `idx_tipe_item` (`tipe_item`)",
    "Add merek index" => "ALTER TABLE `tblitem` ADD INDEX `idx_merek` (`merek`)",
    "Add kategori_rak index" => "ALTER TABLE `tblitem` ADD INDEX `idx_kategori_rak` (`kategori_rak`)",
    "Add status_validasi index" => "ALTER TABLE `tblitem` ADD INDEX `idx_status_validasi` (`status_validasi`)"
];

$table_creation = [
    "Create tbkategori_rak table" => "CREATE TABLE IF NOT EXISTS `tbkategori_rak` (
      `kode` VARCHAR(10) PRIMARY KEY,
      `nama_kategori` VARCHAR(50) NOT NULL,
      `deskripsi` TEXT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1",
    
    "Create tbitem_validation_log table" => "CREATE TABLE IF NOT EXISTS `tbitem_validation_log` (
      `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
      `noitem` VARCHAR(20) NOT NULL,
      `status_lama` VARCHAR(20) NULL,
      `status_baru` VARCHAR(20) NOT NULL,
      `keterangan` TEXT NULL,
      `validated_by` INT(11) NOT NULL,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1"
];

$data_insertion = [
    "Insert kategori rak data" => "INSERT INTO `tbkategori_rak` (`kode`, `nama_kategori`, `deskripsi`) VALUES
('KB', 'Kabel', 'Kategori untuk produk kabel'),
('EL', 'Kelistrikan', 'Kategori untuk produk kelistrikan'),
('RM', 'Rem', 'Kategori untuk produk rem'),
('MS', 'Mesin', 'Kategori untuk produk mesin'),
('CV', 'CVT', 'Kategori untuk produk CVT'),
('RD', 'Roda', 'Kategori untuk produk roda'),
('CR', 'Carbu', 'Kategori untuk produk karburator'),
('FL', 'Filter', 'Kategori untuk produk filter'),
('CH', 'Cairan', 'Kategori untuk produk cairan'),
('BD', 'Baud', 'Kategori untuk produk baud')
ON DUPLICATE KEY UPDATE 
nama_kategori = VALUES(nama_kategori), 
deskripsi = VALUES(deskripsi)"
];

// Step 1: Add columns
echo "Step 1: Adding new columns to tblitem\n";
foreach ($migration_steps as $description => $sql) {
    echo "- $description: ";
    if (mysqli_query($koneksi, $sql)) {
        echo "SUCCESS\n";
    } else {
        $error = mysqli_error($koneksi);
        if (strpos($error, 'Duplicate column name') !== false) {
            echo "ALREADY EXISTS\n";
        } else {
            echo "FAILED - $error\n";
        }
    }
}

echo "\nStep 2: Adding indexes\n";
foreach ($index_steps as $description => $sql) {
    echo "- $description: ";
    if (mysqli_query($koneksi, $sql)) {
        echo "SUCCESS\n";
    } else {
        $error = mysqli_error($koneksi);
        if (strpos($error, 'Duplicate key name') !== false) {
            echo "ALREADY EXISTS\n";
        } else {
            echo "FAILED - $error\n";
        }
    }
}

echo "\nStep 3: Creating new tables\n";
foreach ($table_creation as $description => $sql) {
    echo "- $description: ";
    if (mysqli_query($koneksi, $sql)) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED - " . mysqli_error($koneksi) . "\n";
    }
}

echo "\nStep 4: Inserting default data\n";
foreach ($data_insertion as $description => $sql) {
    echo "- $description: ";
    if (mysqli_query($koneksi, $sql)) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED - " . mysqli_error($koneksi) . "\n";
    }
}

echo "\n=== MIGRATION COMPLETED ===\n";

// Verify migration
echo "\nMigration Verification:\n";

$result = mysqli_query($koneksi, 'DESCRIBE tblitem');
$columns = array();
while ($row = mysqli_fetch_assoc($result)) {
    $columns[] = $row['Field'];
}

$required_columns = array('tipe_item', 'merek', 'kode_part_resmi', 'nama_part_resmi', 'penggunaan_motor', 'merek_tipe', 'kategori_rak', 'status_validasi');
$missing_columns = array();

foreach ($required_columns as $col) {
    if (!in_array($col, $columns)) {
        $missing_columns[] = $col;
    }
}

if (empty($missing_columns)) {
    echo "✓ All required columns exist in tblitem\n";
} else {
    echo "✗ Missing columns: " . implode(', ', $missing_columns) . "\n";
}

$tables_result = mysqli_query($koneksi, "SHOW TABLES LIKE 'tbkategori_rak'");
if (mysqli_num_rows($tables_result) > 0) {
    echo "✓ tbkategori_rak table exists\n";
    $kategori_count = mysqli_fetch_array(mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tbkategori_rak"))['count'];
    echo "✓ $kategori_count categories available\n";
} else {
    echo "✗ tbkategori_rak table missing\n";
}

$tables_result = mysqli_query($koneksi, "SHOW TABLES LIKE 'tbitem_validation_log'");
if (mysqli_num_rows($tables_result) > 0) {
    echo "✓ tbitem_validation_log table exists\n";
} else {
    echo "✗ tbitem_validation_log table missing\n";
}

echo "\nDatabase migration completed successfully!\n";
?>