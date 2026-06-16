<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

$success_messages = [];
$error_messages = [];

// Check if we should run the database fixes
if (isset($_POST['run_fixes']) || isset($_GET['auto_fix'])) {

    try {
        // 1. Create tbljenis table
        $sql = "CREATE TABLE IF NOT EXISTS `tbljenis` (
            `kodejenis` varchar(10) NOT NULL,
            `namajenis` varchar(100) NOT NULL,
            `keterangan` text,
            `statusjenis` enum('1','0') DEFAULT '1',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`kodejenis`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($koneksi, $sql)) {
            $success_messages[] = "Table tbljenis created successfully";

            // Insert sample data
            $insert_sql = "INSERT IGNORE INTO `tbljenis` (`kodejenis`, `namajenis`, `keterangan`, `statusjenis`) VALUES
                ('SP', 'Spare Part', 'Suku cadang kendaraan bermotor', '1'),
                ('OLI', 'Oli & Pelumas', 'Oli mesin dan pelumas kendaraan', '1'),
                ('TIRE', 'Ban & Velg', 'Ban dan velg kendaraan', '1'),
                ('ACCS', 'Aksesoris', 'Aksesoris dan variasi kendaraan', '1'),
                ('TOOL', 'Tools', 'Peralatan bengkel dan tools', '1')";

            if (mysqli_query($koneksi, $insert_sql)) {
                $success_messages[] = "Sample data inserted into tbljenis";
            }
        } else {
            $error_messages[] = "Error creating tbljenis: " . mysqli_error($koneksi);
        }

        // 2. Create tblsatuan table
        $sql = "CREATE TABLE IF NOT EXISTS `tblsatuan` (
            `kodesatuan` varchar(10) NOT NULL,
            `satuan` varchar(50) NOT NULL,
            `keterangan` text,
            `statussatuan` enum('1','0') DEFAULT '1',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`kodesatuan`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($koneksi, $sql)) {
            $success_messages[] = "Table tblsatuan created successfully";

            // Insert sample data
            $insert_sql = "INSERT IGNORE INTO `tblsatuan` (`kodesatuan`, `satuan`, `keterangan`, `statussatuan`) VALUES
                ('PCS', 'Pcs', 'Pieces - satuan per buah', '1'),
                ('SET', 'Set', 'Set - satuan per set/paket', '1'),
                ('LITER', 'Liter', 'Liter - satuan cairan', '1'),
                ('KG', 'Kg', 'Kilogram - satuan berat', '1'),
                ('PACK', 'Pack', 'Pack - satuan kemasan', '1')";

            if (mysqli_query($koneksi, $insert_sql)) {
                $success_messages[] = "Sample data inserted into tblsatuan";
            }
        } else {
            $error_messages[] = "Error creating tblsatuan: " . mysqli_error($koneksi);
        }

        // 3. Create tblsupplier table
        $sql = "CREATE TABLE IF NOT EXISTS `tblsupplier` (
            `kode_supplier` varchar(20) NOT NULL,
            `nama_supplier` varchar(200) NOT NULL,
            `alamat_supplier` text,
            `telepon_supplier` varchar(50),
            `email_supplier` varchar(100),
            `contact_person` varchar(100),
            `status_supplier` enum('1','0') DEFAULT '1',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`kode_supplier`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($koneksi, $sql)) {
            $success_messages[] = "Table tblsupplier created successfully";

            // Insert sample data
            $insert_sql = "INSERT IGNORE INTO `tblsupplier` (`kode_supplier`, `nama_supplier`, `alamat_supplier`, `telepon_supplier`, `contact_person`, `status_supplier`) VALUES
                ('SUP001', 'PT Honda Parts Indonesia', 'Jakarta Pusat', '021-12345678', 'Budi Santoso', '1'),
                ('SUP002', 'PT Yamaha Motor Parts', 'Jakarta Timur', '021-87654321', 'Sari Dewi', '1'),
                ('SUP003', 'PT Suzuki Genuine Parts', 'Bekasi', '021-11111111', 'Ahmad Rahman', '1'),
                ('SUP004', 'CV Mitra Motor Parts', 'Surabaya', '031-44444444', 'Rina Sari', '1'),
                ('SUP005', 'Toko Spare Part Jaya', 'Bandung', '022-33333333', 'Joko Widodo', '1')";

            if (mysqli_query($koneksi, $insert_sql)) {
                $success_messages[] = "Sample data inserted into tblsupplier";
            }
        } else {
            $error_messages[] = "Error creating tblsupplier: " . mysqli_error($koneksi);
        }

        // 4. Create tbrakbarang table
        $sql = "CREATE TABLE IF NOT EXISTS `tbrakbarang` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `kode_rak` varchar(20) NOT NULL,
            `rak_barang` varchar(100) NOT NULL,
            `lokasi_rak` varchar(100),
            `kapasitas_rak` int(11) DEFAULT '0',
            `keterangan_rak` text,
            `status_rak` enum('1','0') DEFAULT '1',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `kode_rak` (`kode_rak`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($koneksi, $sql)) {
            $success_messages[] = "Table tbrakbarang created successfully";

            // Insert sample data
            $insert_sql = "INSERT IGNORE INTO `tbrakbarang` (`kode_rak`, `rak_barang`, `lokasi_rak`, `kapasitas_rak`, `keterangan_rak`, `status_rak`) VALUES
                ('RAK-A01', 'Rak A-01', 'Gudang Utama - Sisi Kiri', 100, 'Rak untuk spare part kecil', '1'),
                ('RAK-A02', 'Rak A-02', 'Gudang Utama - Sisi Kiri', 100, 'Rak untuk spare part kecil', '1'),
                ('RAK-B01', 'Rak B-01', 'Gudang Utama - Sisi Tengah', 150, 'Rak untuk spare part sedang', '1'),
                ('RAK-C01', 'Rak C-01', 'Gudang Utama - Sisi Kanan', 200, 'Rak untuk spare part besar', '1'),
                ('RAK-OLI', 'Rak Oli', 'Gudang Oli', 500, 'Rak khusus untuk oli dan pelumas', '1')";

            if (mysqli_query($koneksi, $insert_sql)) {
                $success_messages[] = "Sample data inserted into tbrakbarang";
            }
        } else {
            $error_messages[] = "Error creating tbrakbarang: " . mysqli_error($koneksi);
        }

        // 5. Create tbkategori_rak table
        $sql = "CREATE TABLE IF NOT EXISTS `tbkategori_rak` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `kode` varchar(10) NOT NULL,
            `kategori` varchar(100) NOT NULL,
            `deskripsi` text,
            `status` enum('1','0') DEFAULT '1',
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `kode` (`kode`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($koneksi, $sql)) {
            $success_messages[] = "Table tbkategori_rak created successfully";

            // Insert sample data
            $insert_sql = "INSERT IGNORE INTO `tbkategori_rak` (`kode`, `kategori`, `deskripsi`, `status`) VALUES
                ('KB', 'Kabel', 'Kabel dan komponen kabel kendaraan', '1'),
                ('EL', 'Kelistrikan', 'Komponen kelistrikan dan elektronik', '1'),
                ('RM', 'Rem', 'Sistem rem dan komponennya', '1'),
                ('MS', 'Mesin', 'Komponen mesin dan engine', '1'),
                ('CV', 'CVT', 'Sistem CVT dan transmisi otomatis', '1'),
                ('RD', 'Roda', 'Ban, velg, dan komponen roda', '1'),
                ('FL', 'Filter', 'Filter udara, oli, dan bahan bakar', '1'),
                ('CH', 'Cairan', 'Oli, pelumas, dan cairan kendaraan', '1')";

            if (mysqli_query($koneksi, $insert_sql)) {
                $success_messages[] = "Sample data inserted into tbkategori_rak";
            }
        } else {
            $error_messages[] = "Error creating tbkategori_rak: " . mysqli_error($koneksi);
        }

        // 6. Create tbitem_validation_log table
        $sql = "CREATE TABLE IF NOT EXISTS `tbitem_validation_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `noitem` varchar(50) NOT NULL,
            `action` varchar(50) NOT NULL,
            `notes` text,
            `user_id` int(11) NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_noitem` (`noitem`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($koneksi, $sql)) {
            $success_messages[] = "Table tbitem_validation_log created successfully";
        } else {
            $error_messages[] = "Error creating tbitem_validation_log: " . mysqli_error($koneksi);
        }

        // 7. Add missing columns to tblitem
        $columns_to_add = [
            "ADD COLUMN IF NOT EXISTS `tipe_item` ENUM('ORI', 'NON_ORI') DEFAULT 'NON_ORI'",
            "ADD COLUMN IF NOT EXISTS `status_validasi` ENUM('pending_validation', 'validated', 'rejected') DEFAULT 'pending_validation'",
            "ADD COLUMN IF NOT EXISTS `merek` VARCHAR(50) NULL",
            "ADD COLUMN IF NOT EXISTS `kode_part_resmi` VARCHAR(50) NULL",
            "ADD COLUMN IF NOT EXISTS `nama_part_resmi` VARCHAR(100) NULL",
            "ADD COLUMN IF NOT EXISTS `penggunaan_motor` VARCHAR(100) NULL",
            "ADD COLUMN IF NOT EXISTS `merek_tipe` VARCHAR(100) NULL",
            "ADD COLUMN IF NOT EXISTS `kategori_rak` VARCHAR(10) NULL",
            "ADD COLUMN IF NOT EXISTS `created_by` INT(11) NULL",
            "ADD COLUMN IF NOT EXISTS `validated_by` INT(11) NULL",
            "ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
        ];

        foreach ($columns_to_add as $column) {
            $sql = "ALTER TABLE `tblitem` $column";
            if (@mysqli_query($koneksi, $sql)) {
                $success_messages[] = "Column added to tblitem: " . substr($column, 28, 20);
            }
        }

        // 8. Create tblitem_stok table
        $sql = "CREATE TABLE IF NOT EXISTS `tblitem_stok` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `noitem` varchar(50) NOT NULL,
            `kode_cabang` varchar(20) NOT NULL,
            `stokmin` int(11) DEFAULT '0',
            `stok_maks` int(11) DEFAULT '0',
            `stok_awal` int(11) DEFAULT '0',
            `rakbarang` int(11) NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_item_cabang` (`noitem`, `kode_cabang`),
            KEY `idx_noitem` (`noitem`),
            KEY `idx_kode_cabang` (`kode_cabang`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        if (mysqli_query($koneksi, $sql)) {
            $success_messages[] = "Table tblitem_stok created successfully";
        } else {
            $error_messages[] = "Error creating tblitem_stok: " . mysqli_error($koneksi);
        }

        if (count($error_messages) == 0) {
            $success_messages[] = "✅ DATABASE SETUP COMPLETE! All tables created successfully.";
        }

    } catch (Exception $e) {
        $error_messages[] = "Fatal error: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Database Quick Fix - Web Bengkel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css" />
    <link rel="stylesheet" href="assets/css/ace.min.css" />
    <style>
        .container { margin-top: 30px; }
        .alert { margin: 15px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
    </style>
</head>

<body class="no-skin">
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-header" style="padding: 20px;">
                        <h3><i class="fa fa-database"></i> Database Quick Fix</h3>
                        <p class="text-muted">Fix missing tables for Web Bengkel Validation System</p>
                    </div>

                    <div class="panel-body" style="padding: 20px;">

                        <?php if (count($success_messages) > 0): ?>
                            <div class="alert alert-success">
                                <h4><i class="fa fa-check"></i> Success!</h4>
                                <?php foreach ($success_messages as $msg): ?>
                                    <p><?php echo $msg; ?></p>
                                <?php endforeach; ?>

                                <hr>
                                <p><strong>Next Steps:</strong></p>
                                <ul>
                                    <li><a href="barang_validate.php?kd=TEST001" class="btn btn-primary btn-sm">Test Validation System</a></li>
                                    <li><a href="barang_edit_improved.php?kd=TEST001" class="btn btn-info btn-sm">Test Edit System</a></li>
                                    <li><a href="barang.php" class="btn btn-default btn-sm">Go to Item List</a></li>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (count($error_messages) > 0): ?>
                            <div class="alert alert-danger">
                                <h4><i class="fa fa-times"></i> Errors Occurred:</h4>
                                <?php foreach ($error_messages as $msg): ?>
                                    <p><?php echo $msg; ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!isset($_POST['run_fixes']) && !isset($_GET['auto_fix'])): ?>
                            <div class="alert alert-info">
                                <h4><i class="fa fa-info-circle"></i> Database Tables Missing</h4>
                                <p>The following tables are required for the validation system to work:</p>
                                <ul>
                                    <li><code>tbljenis</code> - Item types/categories</li>
                                    <li><code>tblsatuan</code> - Units of measurement</li>
                                    <li><code>tblsupplier</code> - Supplier information</li>
                                    <li><code>tbrakbarang</code> - Storage racks</li>
                                    <li><code>tbkategori_rak</code> - NON-ORI categories</li>
                                    <li><code>tbitem_validation_log</code> - Validation history</li>
                                    <li><code>tblitem_stok</code> - Stock information</li>
                                </ul>

                                <p>This quick fix will create all missing tables with sample data.</p>
                            </div>

                            <div class="text-center">
                                <form method="post">
                                    <button type="submit" name="run_fixes" class="btn btn-success btn-lg">
                                        <i class="fa fa-magic"></i> Run Database Fix
                                    </button>
                                </form>

                                <br>
                                <p><a href="barang.php" class="btn btn-default">Back to Item List</a></p>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_POST['run_fixes']) || isset($_GET['auto_fix'])): ?>
                            <div class="alert alert-warning">
                                <h4><i class="fa fa-check-circle"></i> Database Fix Completed</h4>
                                <p>Tables: <strong><?php echo count($success_messages) - count($error_messages); ?></strong> created successfully</p>
                                <p>Errors: <strong><?php echo count($error_messages); ?></strong></p>

                                <hr>
                                <h5>Verification Query:</h5>
                                <pre>
-- Check if tables exist
SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = 'fitmotor_dbbengkel'
AND TABLE_NAME IN ('tbljenis', 'tblsatuan', 'tblsupplier', 'tbrakbarang', 'tbkategori_rak', 'tbitem_validation_log');

-- Check sample data
SELECT COUNT(*) as jenis_count FROM tbljenis;
SELECT COUNT(*) as satuan_count FROM tblsatuan;
SELECT COUNT(*) as supplier_count FROM tblsupplier;
</pre>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>
</body>
</html>