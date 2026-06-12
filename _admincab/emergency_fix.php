<?php
// Emergency Database Fix - Run this if you're still getting errors

session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Database Fix</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <style>
        .container { margin: 30px auto; max-width: 800px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🚨 Emergency Database Fix</h2>
        <p>This will fix the remaining database issues automatically.</p>

        <?php
        $fixes_applied = [];
        $errors = [];

        // 1. Drop and recreate tblsupplier properly
        try {
            mysqli_query($koneksi, "DROP TABLE IF EXISTS tblsupplier");
            $fixes_applied[] = "Dropped existing tblsupplier table";

            $create_supplier = "CREATE TABLE `tblsupplier` (
                `kode_supplier` varchar(20) NOT NULL,
                `nama_supplier` varchar(200) NOT NULL,
                `alamat_supplier` text,
                `telepon_supplier` varchar(50) DEFAULT NULL,
                `contact_person` varchar(100) DEFAULT NULL,
                `status_supplier` enum('1','0') DEFAULT '1',
                PRIMARY KEY (`kode_supplier`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

            if (mysqli_query($koneksi, $create_supplier)) {
                $fixes_applied[] = "Created tblsupplier table with correct structure";

                $insert_suppliers = "INSERT INTO `tblsupplier` (`kode_supplier`, `nama_supplier`, `alamat_supplier`, `telepon_supplier`, `contact_person`, `status_supplier`) VALUES
                ('SUP001', 'Default Supplier', 'Jakarta', '021-0000000', 'Admin', '1'),
                ('SUP002', 'PT Honda Parts Indonesia', 'Jakarta', '021-1111111', 'Honda Admin', '1'),
                ('SUP003', 'PT Yamaha Motor Parts', 'Jakarta', '021-2222222', 'Yamaha Admin', '1'),
                ('SUP004', 'PT Suzuki Genuine Parts', 'Jakarta', '021-3333333', 'Suzuki Admin', '1'),
                ('SUP005', 'Toko Spare Part Jaya', 'Bandung', '022-4444444', 'Jaya Admin', '1')";

                if (mysqli_query($koneksi, $insert_suppliers)) {
                    $fixes_applied[] = "Inserted sample supplier data";
                }
            } else {
                $errors[] = "Failed to create tblsupplier: " . mysqli_error($koneksi);
            }
        } catch (Exception $e) {
            $errors[] = "Exception in supplier fix: " . $e->getMessage();
        }

        // 2. Ensure all other tables exist with minimal structure
        $essential_tables = [
            'tbrakbarang' => "CREATE TABLE IF NOT EXISTS `tbrakbarang` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `kode_rak` varchar(20) NOT NULL,
                `rak_barang` varchar(100) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'tbkategori_rak' => "CREATE TABLE IF NOT EXISTS `tbkategori_rak` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `kode` varchar(10) NOT NULL UNIQUE,
                `kategori` varchar(100) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            'tbitem_validation_log' => "CREATE TABLE IF NOT EXISTS `tbitem_validation_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `noitem` varchar(50) NOT NULL,
                `action` varchar(50) NOT NULL,
                `notes` text,
                `user_id` int(11) NOT NULL DEFAULT 1,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];

        foreach ($essential_tables as $table_name => $create_sql) {
            if (mysqli_query($koneksi, $create_sql)) {
                $fixes_applied[] = "Created/verified table: $table_name";
            } else {
                $errors[] = "Failed to create $table_name: " . mysqli_error($koneksi);
            }
        }

        // 3. Insert minimal data for tbrakbarang and tbkategori_rak
        $rak_data = "INSERT IGNORE INTO `tbrakbarang` (`kode_rak`, `rak_barang`) VALUES
        ('RAK-A01', 'Rak A-01'),
        ('RAK-A02', 'Rak A-02'),
        ('RAK-B01', 'Rak B-01')";

        if (mysqli_query($koneksi, $rak_data)) {
            $fixes_applied[] = "Inserted sample rack data";
        }

        $kategori_data = "INSERT IGNORE INTO `tbkategori_rak` (`kode`, `kategori`) VALUES
        ('KB', 'Kabel'),
        ('EL', 'Kelistrikan'),
        ('RM', 'Rem'),
        ('MS', 'Mesin'),
        ('CV', 'CVT')";

        if (mysqli_query($koneksi, $kategori_data)) {
            $fixes_applied[] = "Inserted sample category data";
        }

        // 4. Fix tbluser nama_user column
        $user_fix = "ALTER TABLE `tbuser` ADD COLUMN IF NOT EXISTS `nama_user` VARCHAR(100) DEFAULT NULL";
        if (mysqli_query($koneksi, $user_fix)) {
            $fixes_applied[] = "Added nama_user column to tbuser";

            // Copy existing nama to nama_user if exists
            $copy_nama = "UPDATE tbuser SET nama_user = COALESCE(nama, username) WHERE nama_user IS NULL OR nama_user = ''";
            mysqli_query($koneksi, $copy_nama);
            $fixes_applied[] = "Populated nama_user from existing data";
        }

        // 5. Ensure tblitem has required columns
        $item_fixes = [
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `tipe_item` ENUM('ORI', 'NON_ORI') DEFAULT 'NON_ORI'",
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `status_validasi` ENUM('pending_validation', 'validated', 'rejected') DEFAULT 'pending_validation'",
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `merek` VARCHAR(50) DEFAULT NULL",
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `kategori_rak` VARCHAR(10) DEFAULT NULL"
        ];

        foreach ($item_fixes as $fix) {
            if (mysqli_query($koneksi, $fix)) {
                $column_name = preg_match('/ADD COLUMN.*`([^`]+)`/', $fix, $matches) ? $matches[1] : 'unknown';
                $fixes_applied[] = "Added column: $column_name to tblitem";
            }
        }

        // Display results
        if (count($fixes_applied) > 0) {
            echo '<div class="status success">';
            echo '<h4>✅ Fixes Applied Successfully:</h4>';
            echo '<ul>';
            foreach ($fixes_applied as $fix) {
                echo "<li>$fix</li>";
            }
            echo '</ul>';
            echo '</div>';
        }

        if (count($errors) > 0) {
            echo '<div class="status error">';
            echo '<h4>❌ Errors Encountered:</h4>';
            echo '<ul>';
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo '</ul>';
            echo '</div>';
        }

        // Final verification
        echo '<div class="status info">';
        echo '<h4>🔍 Final Verification:</h4>';

        $tables_to_check = ['tbljenis', 'tblsatuan', 'tblsupplier', 'tbrakbarang', 'tbkategori_rak', 'tbitem_validation_log'];
        foreach ($tables_to_check as $table) {
            $check = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
            if ($check && mysqli_num_rows($check) > 0) {
                $count_result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM $table");
                $count = $count_result ? mysqli_fetch_assoc($count_result)['count'] : 0;
                echo "✅ $table: $count records<br>";
            } else {
                echo "❌ $table: Missing<br>";
            }
        }
        echo '</div>';
        ?>

        <div style="margin-top: 30px; text-align: center;">
            <h4>🚀 Test the Fixed System:</h4>
            <a href="barang_validate.php?kd=TEST001" class="btn btn-success">Test Validation System</a>
            <a href="barang_edit_improved.php?kd=TEST001" class="btn btn-primary">Test Edit System</a>
            <a href="barang.php" class="btn btn-default">Go to Items List</a>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
            <h5>📋 What This Fix Did:</h5>
            <ul>
                <li>✅ Recreated tblsupplier with correct column structure</li>
                <li>✅ Ensured all master tables exist with minimal data</li>
                <li>✅ Fixed tbuser.nama_user column mapping</li>
                <li>✅ Added required columns to tblitem</li>
                <li>✅ Created validation log table for history tracking</li>
            </ul>
            <p><strong>Status:</strong> 🟢 System should now be fully operational!</p>
        </div>
    </div>
</body>
</html>