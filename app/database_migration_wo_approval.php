<?php
/**
 * Database Migration: Work Order Approval System
 * 
 * Changes:
 * 1. Add approval workflow columns to tbservis_workorder
 * 2. Update status_penawaran in tbservis_penawaran_part
 * 3. Add alasan_tolak columns
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

echo "=== DATABASE MIGRATION: WO APPROVAL SYSTEM ===\n\n";

$results = [];
$errors = [];

// ============================================================
// 1. UPDATE tbservis_workorder
// ============================================================
echo "[1/2] Updating tbservis_workorder...\n";

try {
    // Check if columns already exist
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tbservis_workorder LIKE 'status_approval'");
    
    if(mysqli_num_rows($check) == 0) {
        echo "   - Adding approval workflow columns...\n";
        
        $sql = "ALTER TABLE tbservis_workorder
                ADD COLUMN status_approval ENUM('pending','disetujui','ditolak') DEFAULT 'pending' 
                    COMMENT 'Status approval WO' AFTER kode_wo,
                ADD COLUMN alasan_tolak ENUM(
                    'stok_cabang_kosong',
                    'stok_supplier_kosong',
                    'customer_tidak_mau',
                    'lainnya'
                ) NULL COMMENT 'Alasan tolak' AFTER status_approval,
                ADD COLUMN keterangan_tolak TEXT NULL COMMENT 'Keterangan tolak' AFTER alasan_tolak,
                ADD COLUMN approved_by VARCHAR(50) NULL COMMENT 'User approve/reject' AFTER keterangan_tolak,
                ADD COLUMN approved_at TIMESTAMP NULL COMMENT 'Waktu approve/reject' AFTER approved_by";
        
        if(mysqli_query($koneksi, $sql)) {
            echo "   ✅ Columns added successfully\n";
            $results[] = "tbservis_workorder updated";
            
            // Set existing WOs to 'disetujui' (backward compatibility)
            $update = "UPDATE tbservis_workorder SET status_approval = 'disetujui' WHERE status_approval IS NULL OR status_approval = 'pending'";
            mysqli_query($koneksi, $update);
            echo "   ✅ Existing WO data updated (set to 'disetujui')\n";
        } else {
            throw new Exception(mysqli_error($koneksi));
        }
    } else {
        echo "   ✅ Columns already exist\n";
        $results[] = "tbservis_workorder already OK";
    }
} catch (Exception $e) {
    $error = "Error updating tbservis_workorder: " . $e->getMessage();
    echo "   ❌ $error\n";
    $errors[] = $error;
}

echo "\n";

// ============================================================
// 2. UPDATE tbservis_penawaran_part
// ============================================================
echo "[2/2] Updating tbservis_penawaran_part...\n";

try {
    // Check if alasan_tolak exists
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tbservis_penawaran_part LIKE 'alasan_tolak'");
    
    if(mysqli_num_rows($check) == 0) {
        echo "   - Adding alasan_tolak columns...\n";
        
        $sql = "ALTER TABLE tbservis_penawaran_part
                ADD COLUMN alasan_tolak ENUM(
                    'stok_cabang_kosong',
                    'stok_supplier_kosong',
                    'customer_tidak_mau',
                    'lainnya'
                ) NULL COMMENT 'Alasan tolak' AFTER status_penawaran,
                ADD COLUMN keterangan_tolak TEXT NULL COMMENT 'Keterangan tolak' AFTER alasan_tolak";
        
        if(mysqli_query($koneksi, $sql)) {
            echo "   ✅ Columns added successfully\n";
            $results[] = "tbservis_penawaran_part updated";
        } else {
            throw new Exception(mysqli_error($koneksi));
        }
    } else {
        echo "   ✅ Columns already exist\n";
        $results[] = "tbservis_penawaran_part already OK";
    }
} catch (Exception $e) {
    $error = "Error updating tbservis_penawaran_part: " . $e->getMessage();
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
echo "1. Test database structure via phpMyAdmin\n";
echo "2. Update backend handlers in service files\n";
echo "3. Update UI in tab-temuan-penawaran-content.php\n";
echo "4. Test approval workflow\n";
echo "\n";

mysqli_close($koneksi);
?>
