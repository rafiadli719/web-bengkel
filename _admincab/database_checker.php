<?php
// Database Checker Helper - untuk dipanggil otomatis
// File ini akan dicek oleh file PHP lain untuk memastikan database siap

function checkAndFixDatabase($koneksi) {
    $missing_tables = [];
    $required_tables = ['tbljenis', 'tblsatuan', 'tblsupplier', 'tbrakbarang', 'tbkategori_rak'];

    // Check which tables are missing
    foreach ($required_tables as $table) {
        $check_query = "SHOW TABLES LIKE '$table'";
        $result = @mysqli_query($koneksi, $check_query);
        if (!$result || mysqli_num_rows($result) == 0) {
            $missing_tables[] = $table;
        }
    }

    // If tables are missing, try to create them automatically
    if (count($missing_tables) > 0) {
        $create_queries = [
            'tbljenis' => [
                "CREATE TABLE IF NOT EXISTS `tbljenis` (
                    `kodejenis` varchar(10) NOT NULL,
                    `namajenis` varchar(100) NOT NULL,
                    `keterangan` text,
                    `statusjenis` enum('1','0') DEFAULT '1',
                    PRIMARY KEY (`kodejenis`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "INSERT IGNORE INTO `tbljenis` VALUES
                ('SP', 'Spare Part', 'Suku cadang kendaraan', '1'),
                ('OLI', 'Oli & Pelumas', 'Oli dan pelumas', '1'),
                ('ACCS', 'Aksesoris', 'Aksesoris kendaraan', '1')"
            ],
            'tblsatuan' => [
                "CREATE TABLE IF NOT EXISTS `tblsatuan` (
                    `kodesatuan` varchar(10) NOT NULL,
                    `satuan` varchar(50) NOT NULL,
                    `keterangan` text,
                    `statussatuan` enum('1','0') DEFAULT '1',
                    PRIMARY KEY (`kodesatuan`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "INSERT IGNORE INTO `tblsatuan` VALUES
                ('PCS', 'Pcs', 'Pieces', '1'),
                ('SET', 'Set', 'Set/Paket', '1'),
                ('LITER', 'Liter', 'Liter', '1')"
            ],
            'tblsupplier' => [
                "CREATE TABLE IF NOT EXISTS `tblsupplier` (
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
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "INSERT IGNORE INTO `tblsupplier` (`kode_supplier`, `nama_supplier`, `alamat_supplier`, `telepon_supplier`, `contact_person`, `status_supplier`) VALUES
                ('SUP001', 'Default Supplier', 'Jakarta', '021-0000000', 'Admin', '1'),
                ('SUP002', 'PT Honda Parts Indonesia', 'Jakarta', '021-1111111', 'Honda Admin', '1'),
                ('SUP003', 'PT Yamaha Motor Parts', 'Jakarta', '021-2222222', 'Yamaha Admin', '1')"
            ],
            'tbrakbarang' => [
                "CREATE TABLE IF NOT EXISTS `tbrakbarang` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `kode_rak` varchar(20) NOT NULL,
                    `rak_barang` varchar(100) NOT NULL,
                    `status_rak` enum('1','0') DEFAULT '1',
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "INSERT IGNORE INTO `tbrakbarang` (`kode_rak`, `rak_barang`) VALUES
                ('RAK-A01', 'Rak A-01'),
                ('RAK-A02', 'Rak A-02'),
                ('RAK-B01', 'Rak B-01')"
            ],
            'tbkategori_rak' => [
                "CREATE TABLE IF NOT EXISTS `tbkategori_rak` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `kode` varchar(10) NOT NULL,
                    `kategori` varchar(100) NOT NULL,
                    `status` enum('1','0') DEFAULT '1',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `kode` (`kode`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                "INSERT IGNORE INTO `tbkategori_rak` (`kode`, `kategori`) VALUES
                ('KB', 'Kabel'),
                ('EL', 'Kelistrikan'),
                ('RM', 'Rem'),
                ('MS', 'Mesin'),
                ('CV', 'CVT')"
            ]
        ];

        // Create missing tables
        foreach ($missing_tables as $table) {
            if (isset($create_queries[$table])) {
                foreach ($create_queries[$table] as $query) {
                    @mysqli_query($koneksi, $query);
                }
            }
        }

        // Create tbitem_validation_log if not exists
        $validation_log_sql = "CREATE TABLE IF NOT EXISTS `tbitem_validation_log` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `noitem` varchar(50) NOT NULL,
            `action` varchar(50) NOT NULL,
            `notes` text,
            `user_id` int(11) NOT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_noitem` (`noitem`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @mysqli_query($koneksi, $validation_log_sql);

        // Also ensure tblitem has required columns
        $alter_queries = [
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `tipe_item` ENUM('ORI', 'NON_ORI') DEFAULT 'NON_ORI'",
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `status_validasi` ENUM('pending_validation', 'validated', 'rejected') DEFAULT 'pending_validation'",
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `merek` VARCHAR(50) NULL",
            "ALTER TABLE `tblitem` ADD COLUMN IF NOT EXISTS `kategori_rak` VARCHAR(10) NULL"
        ];

        foreach ($alter_queries as $query) {
            @mysqli_query($koneksi, $query);
        }

        // Check and fix tbluser table structure if needed
        $user_check = @mysqli_query($koneksi, "SHOW COLUMNS FROM tbuser LIKE 'nama_user'");
        if (!$user_check || mysqli_num_rows($user_check) == 0) {
            // Add nama_user column if not exists (some systems might use 'nama' instead)
            @mysqli_query($koneksi, "ALTER TABLE `tbuser` ADD COLUMN IF NOT EXISTS `nama_user` VARCHAR(100) NULL");

            // Copy from existing 'nama' column if it exists
            $nama_check = @mysqli_query($koneksi, "SHOW COLUMNS FROM tbuser LIKE 'nama'");
            if ($nama_check && mysqli_num_rows($nama_check) > 0) {
                @mysqli_query($koneksi, "UPDATE tbuser SET nama_user = nama WHERE nama_user IS NULL");
            }
        }

        // Check and fix tblsupplier table structure if needed
        $supplier_check = @mysqli_query($koneksi, "SHOW TABLES LIKE 'tblsupplier'");
        if ($supplier_check && mysqli_num_rows($supplier_check) > 0) {
            // Check if kode_supplier column exists
            $kode_check = @mysqli_query($koneksi, "SHOW COLUMNS FROM tblsupplier LIKE 'kode_supplier'");
            if (!$kode_check || mysqli_num_rows($kode_check) == 0) {
                // Fix supplier table structure
                @mysqli_query($koneksi, "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `kode_supplier` VARCHAR(20) NOT NULL FIRST");
                @mysqli_query($koneksi, "ALTER TABLE `tblsupplier` ADD COLUMN IF NOT EXISTS `nama_supplier` VARCHAR(200) NOT NULL");
                @mysqli_query($koneksi, "ALTER TABLE `tblsupplier` ADD PRIMARY KEY IF NOT EXISTS (`kode_supplier`)");

                // Insert sample data if table is empty
                $count_check = @mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblsupplier");
                if ($count_check) {
                    $count = mysqli_fetch_assoc($count_check);
                    if ($count['count'] == 0) {
                        @mysqli_query($koneksi, "INSERT INTO `tblsupplier` (`kode_supplier`, `nama_supplier`) VALUES
                                             ('SUP001', 'Default Supplier'),
                                             ('SUP002', 'Honda Parts'),
                                             ('SUP003', 'Yamaha Parts')");
                    }
                }
            }
        }

        return true; // Tables were created/fixed
    }

    return false; // No fixes needed
}

// Auto-check function that can be included in other files
function autoCheckDatabase($koneksi) {
    // Quick check for tbljenis existence
    $result = @mysqli_query($koneksi, "SHOW TABLES LIKE 'tbljenis'");
    if (!$result || mysqli_num_rows($result) == 0) {
        return checkAndFixDatabase($koneksi);
    }
    return false;
}
?>