<?php
/**
 * Database Migration: WO Items Approval System (REVISED)
 * 
 * Creates new table: tbservis_pending_items
 * For storing WO items (barang & jasa) that need approval
 */

// Include koneksi
$config_path = __DIR__ . '/../config/koneksi.php';
if (!file_exists($config_path)) {
    die("Error: koneksi.php tidak ditemukan");
}
require_once $config_path;

if (!isset($koneksi)) {
    die("Error: Variable \$koneksi tidak terdefinisi");
}

echo "=== DATABASE MIGRATION: WO ITEMS APPROVAL SYSTEM ===\n\n";

$results = [];
$errors = [];

// ============================================================
// 1. CREATE TABLE tbservis_pending_items
// ============================================================
echo "[1/2] Creating tbservis_pending_items table...\n";

try {
    // Check if table already exists
    $check = mysqli_query($koneksi, "SHOW TABLES LIKE 'tbservis_pending_items'");
    
    if(mysqli_num_rows($check) == 0) {
        echo "   - Creating new table...\n";
        
        $sql = "CREATE TABLE tbservis_pending_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            no_service VARCHAR(50) NOT NULL,
            wo_id INT NULL COMMENT 'Link ke tbservis_workorder',
            kode_item VARCHAR(50) NOT NULL,
            nama_item VARCHAR(255) NOT NULL,
            tipe ENUM('barang','jasa') NOT NULL,
            quantity INT DEFAULT 1,
            harga_satuan DECIMAL(15,2) NOT NULL,
            total DECIMAL(15,2) NOT NULL,
            waktu INT DEFAULT 0 COMMENT 'Waktu untuk jasa (menit)',
            status_approval ENUM('pending','disetujui','ditolak') DEFAULT 'pending',
            alasan_tolak ENUM(
                'stok_cabang_kosong',
                'stok_supplier_kosong',
                'customer_tidak_mau',
                'lainnya'
            ) NULL,
            keterangan_tolak TEXT NULL,
            approved_by VARCHAR(50) NULL,
            approved_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_no_service (no_service),
            INDEX idx_status (status_approval),
            INDEX idx_wo_id (wo_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Pending items from WO that need approval'";
        
        if(mysqli_query($koneksi, $sql)) {
            echo "   ✅ Table tbservis_pending_items created successfully\n";
            $results[] = "tbservis_pending_items created";
        } else {
            throw new Exception(mysqli_error($koneksi));
        }
    } else {
        echo "   ✅ Table already exists\n";
        $results[] = "tbservis_pending_items already OK";
    }
} catch (Exception $e) {
    $error = "Error creating tbservis_pending_items: " . $e->getMessage();
    echo "   ❌ $error\n";
    $errors[] = $error;
}

echo "\n";

// ============================================================
// 2. REVERT tbservis_workorder changes (remove status_approval)
// ============================================================
echo "[2/2] Reverting tbservis_workorder (items need approval, not WO)...\n";

try {
    // Check if status_approval column exists
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tbservis_workorder LIKE 'status_approval'");
    
    if(mysqli_num_rows($check) > 0) {
        echo "   - Removing unused approval columns from tbservis_workorder...\n";
        
        $sql = "ALTER TABLE tbservis_workorder
                DROP COLUMN status_approval,
                DROP COLUMN alasan_tolak,
                DROP COLUMN keterangan_tolak,
                DROP COLUMN approved_by,
                DROP COLUMN approved_at";
        
        if(mysqli_query($koneksi, $sql)) {
            echo "   ✅ Columns removed successfully\n";
            $results[] = "tbservis_workorder cleaned up";
        } else {
            throw new Exception(mysqli_error($koneksi));
        }
    } else {
        echo "   ✅ Already clean (no status_approval column)\n";
        $results[] = "tbservis_workorder already OK";
    }
} catch (Exception $e) {
    $error = "Error cleaning tbservis_workorder: " . $e->getMessage();
    echo "   ❌ $error\n";
    $errors[] = $error;
}

echo "\n";

// ============================================================
// SUMMARY
// ============================================================
echo "===========================================\n";
echo "MIGRATION SUMMARY\n";
echo "===========================================\n";
echo "Success: " . count($results) . " operations\n";
foreach ($results as $r) {
    echo "  ✅ $r\n";
}

if (count($errors) > 0) {
    echo "\nErrors: " . count($errors) . " operations failed\n";
    foreach ($errors as $e) {
        echo "  ❌ $e\n";
    }
} else {
    echo "\n✅ MIGRATION COMPLETED SUCCESSFULLY!\n";
}

echo "\n===========================================\n";
echo "NEXT STEPS:\n";
echo "===========================================\n";
echo "1. Verify table structure via phpMyAdmin\n";
echo "2. Update WO insert handler (items → pending table)\n";
echo "3. Create approve/reject item handlers\n";
echo "4. Update UI to show pending items\n";
echo "5. Test complete flow\n";
echo "\n";

mysqli_close($koneksi);
?>
