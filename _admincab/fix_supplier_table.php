<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

echo "<h3>Fixing Supplier Table Structure</h3>";

// Check if tblsupplier exists
$check_table = @mysqli_query($koneksi, "SHOW TABLES LIKE 'tblsupplier'");

if ($check_table && mysqli_num_rows($check_table) > 0) {
    echo "<p>✅ Table tblsupplier exists</p>";

    // Check current structure
    $describe = @mysqli_query($koneksi, "DESCRIBE tblsupplier");
    if ($describe) {
        echo "<h4>Current Table Structure:</h4><ul>";
        while ($col = mysqli_fetch_assoc($describe)) {
            echo "<li>{$col['Field']} - {$col['Type']}</li>";
        }
        echo "</ul>";
    }

    // Fix missing columns
    $fixes = [
        "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `kode_supplier` VARCHAR(20) NOT NULL FIRST",
        "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `nama_supplier` VARCHAR(200) NOT NULL AFTER `kode_supplier`",
        "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `alamat_supplier` TEXT AFTER `nama_supplier`",
        "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `telepon_supplier` VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `contact_person` VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `status_supplier` ENUM('1','0') DEFAULT '1'"
    ];

    echo "<h4>Applying Fixes:</h4><ul>";
    foreach ($fixes as $fix) {
        $result = @mysqli_query($koneksi, $fix);
        if ($result) {
            echo "<li>✅ " . substr($fix, 0, 50) . "...</li>";
        } else {
            echo "<li>❌ Failed: " . substr($fix, 0, 50) . "... - " . mysqli_error($koneksi) . "</li>";
        }
    }
    echo "</ul>";

    // Try to add primary key if not exists
    $add_pk = @mysqli_query($koneksi, "ALTER TABLE `tblsupplier` ADD PRIMARY KEY (`kode_supplier`)");
    if ($add_pk) {
        echo "<p>✅ Primary key added</p>";
    }

    // Insert sample data
    $sample_data = "INSERT IGNORE INTO `tblsupplier` (`kode_supplier`, `nama_supplier`, `alamat_supplier`, `telepon_supplier`, `contact_person`, `status_supplier`) VALUES
    ('SUP001', 'Default Supplier', 'Jakarta', '021-0000000', 'Admin', '1'),
    ('SUP002', 'PT Honda Parts Indonesia', 'Jakarta', '021-1111111', 'Honda Admin', '1'),
    ('SUP003', 'PT Yamaha Motor Parts', 'Jakarta', '021-2222222', 'Yamaha Admin', '1')";

    $insert_result = @mysqli_query($koneksi, $sample_data);
    if ($insert_result) {
        echo "<p>✅ Sample data inserted</p>";
    } else {
        echo "<p>❌ Failed to insert sample data: " . mysqli_error($koneksi) . "</p>";
    }

} else {
    echo "<p>❌ Table tblsupplier does not exist</p>";

    // Create the table from scratch
    $create_sql = "CREATE TABLE IF NOT EXISTS `tblsupplier` (
        `kode_supplier` varchar(20) NOT NULL,
        `nama_supplier` varchar(200) NOT NULL,
        `alamat_supplier` text,
        `telepon_supplier` varchar(50) DEFAULT NULL,
        `email_supplier` varchar(100) DEFAULT NULL,
        `contact_person` varchar(100) DEFAULT NULL,
        `status_supplier` enum('1','0') DEFAULT '1',
        `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`kode_supplier`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    $create_result = @mysqli_query($koneksi, $create_sql);
    if ($create_result) {
        echo "<p>✅ Table tblsupplier created successfully</p>";

        // Insert sample data
        $sample_data = "INSERT INTO `tblsupplier` (`kode_supplier`, `nama_supplier`, `alamat_supplier`, `telepon_supplier`, `contact_person`, `status_supplier`) VALUES
        ('SUP001', 'Default Supplier', 'Jakarta', '021-0000000', 'Admin', '1'),
        ('SUP002', 'PT Honda Parts Indonesia', 'Jakarta', '021-1111111', 'Honda Admin', '1'),
        ('SUP003', 'PT Yamaha Motor Parts', 'Jakarta', '021-2222222', 'Yamaha Admin', '1')";

        $insert_result = @mysqli_query($koneksi, $sample_data);
        if ($insert_result) {
            echo "<p>✅ Sample data inserted</p>";
        }
    } else {
        echo "<p>❌ Failed to create table: " . mysqli_error($koneksi) . "</p>";
    }
}

// Final verification
echo "<h4>Final Verification:</h4>";
$final_check = @mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblsupplier");
if ($final_check) {
    $count = mysqli_fetch_assoc($final_check);
    echo "<p>✅ Table tblsupplier has {$count['count']} records</p>";
} else {
    echo "<p>❌ Cannot verify table</p>";
}

echo "<br><a href='barang_edit_improved.php?kd=TEST001' class='btn btn-primary'>Test Edit System</a>";
echo " <a href='barang_validate.php?kd=TEST001' class='btn btn-success'>Test Validation System</a>";
echo " <a href='barang.php' class='btn btn-default'>Back to Items</a>";
?>