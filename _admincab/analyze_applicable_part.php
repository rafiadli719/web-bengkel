<?php
include '../config/koneksi.php';

echo "=== ANALYZING APPLICABLE PART STRUCTURE ===\n\n";

// Check tipe motor table
echo "1. TBTIPE_MOTOR TABLE:\n";
$result = mysqli_query($koneksi, 'DESCRIBE tbtipe_motor');
if ($result) {
    echo "Structure:\n";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   {$row['Field']} | {$row['Type']}\n";
    }
    
    $count_result = mysqli_query($koneksi, 'SELECT COUNT(*) as count FROM tbtipe_motor');
    $count = mysqli_fetch_assoc($count_result)['count'];
    echo "Total records: $count\n";
    
    echo "Sample data:\n";
    $sample_result = mysqli_query($koneksi, 'SELECT * FROM tbtipe_motor LIMIT 10');
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "   {$row['kode_tipe']} - {$row['tipe']}\n";
    }
} else {
    echo "Table not found or error: " . mysqli_error($koneksi) . "\n";
}

// Check for relationship tables
echo "\n2. CHECKING RELATIONSHIP TABLES:\n";
$tables = ['tblitem_applicable', 'tblbarang_tipe', 'tblitem_tipe_motor', 'tblitem_motor'];
foreach ($tables as $table) {
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
    if ($result && mysqli_num_rows($result) > 0) {
        $count_result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM $table");
        if ($count_result) {
            $count = mysqli_fetch_assoc($count_result)['count'];
            echo "   ✓ $table exists ($count records)\n";
            
            // Show structure
            $desc_result = mysqli_query($koneksi, "DESCRIBE $table");
            if ($desc_result) {
                echo "     Structure:\n";
                while ($row = mysqli_fetch_assoc($desc_result)) {
                    echo "       {$row['Field']} | {$row['Type']}\n";
                }
            }
        } else {
            echo "   ✓ $table exists (count failed)\n";
        }
    } else {
        echo "   ✗ $table NOT FOUND\n";
    }
}

// Check save_barang.php to see how applicable part is saved
echo "\n3. CHECKING SAVE BARANG PROCESS:\n";
if (file_exists('save_barang.php')) {
    $save_content = file_get_contents('save_barang.php');
    if (strpos($save_content, 'hapus1') !== false) {
        echo "   ✓ Found hapus1[] processing in save_barang.php\n";
    }
    if (strpos($save_content, 'hapus2') !== false) {
        echo "   ✓ Found hapus2[] processing in save_barang.php\n";
    }
    if (strpos($save_content, 'hapus3') !== false) {
        echo "   ✓ Found hapus3[] processing in save_barang.php\n";
    }
    if (strpos($save_content, 'hapus4') !== false) {
        echo "   ✓ Found hapus4[] processing in save_barang.php\n";
    }
} else {
    echo "   ✗ save_barang.php not found\n";
}

mysqli_close($koneksi);
echo "\n=== ANALYSIS COMPLETE ===\n";
?>