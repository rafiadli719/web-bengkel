<?php
include '../config/koneksi.php';

echo "=== ANALYZING CURRENT DATABASE STRUCTURE ===\n\n";

// 1. Check tblitem structure
echo "1. TBLITEM TABLE STRUCTURE:\n";
$result = mysqli_query($koneksi, 'DESCRIBE tblitem');
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   {$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']}\n";
    }
} else {
    echo "   ERROR: " . mysqli_error($koneksi) . "\n";
}

// 2. Check related tables
echo "\n2. RELATED TABLES STATUS:\n";
$tables = [
    'tbkategori_rak',
    'tbitem_validation_log', 
    'tblitem_stok',
    'tbrakbarang',
    'tblitemjenis',
    'tblitemsatuan',
    'tbuser'
];

foreach ($tables as $table) {
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
    if ($result && mysqli_num_rows($result) > 0) {
        $count_result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM $table");
        if ($count_result) {
            $count = mysqli_fetch_assoc($count_result)['count'];
            echo "   ✓ $table exists ($count records)\n";
        } else {
            echo "   ✓ $table exists (count failed)\n";
        }
    } else {
        echo "   ✗ $table MISSING\n";
    }
}

// 3. Check views
echo "\n3. VIEWS STATUS:\n";
$views = ['view_item_classified', 'view_cari_item', 'view_stok_master'];
foreach ($views as $view) {
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '$view'");
    if ($result && mysqli_num_rows($result) > 0) {
        echo "   ✓ $view exists\n";
    } else {
        echo "   ✗ $view MISSING\n";
    }
}

// 4. Check indexes on tblitem
echo "\n4. INDEXES ON TBLITEM:\n";
$result = mysqli_query($koneksi, 'SHOW INDEX FROM tblitem');
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "   {$row['Key_name']} on {$row['Column_name']}\n";
    }
}

// 5. Check ORI/NON-ORI data distribution
echo "\n5. ORI/NON-ORI DATA DISTRIBUTION:\n";
$result = mysqli_query($koneksi, "SELECT tipe_item, COUNT(*) as count FROM tblitem WHERE statusitem='1' GROUP BY tipe_item");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $tipe = $row['tipe_item'] ?: 'NULL';
        echo "   $tipe: {$row['count']} items\n";
    }
}

// 6. Check validation status distribution
echo "\n6. VALIDATION STATUS DISTRIBUTION:\n";
$result = mysqli_query($koneksi, "SELECT status_validasi, COUNT(*) as count FROM tblitem WHERE statusitem='1' GROUP BY status_validasi");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $status = $row['status_validasi'] ?: 'NULL';
        echo "   $status: {$row['count']} items\n";
    }
}

// 7. Check for potential issues
echo "\n7. POTENTIAL ISSUES CHECK:\n";

// Check items without tipe_item
$result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item IS NULL AND statusitem='1'");
if ($result) {
    $count = mysqli_fetch_assoc($result)['count'];
    if ($count > 0) {
        echo "   ⚠ $count items without tipe_item classification\n";
    } else {
        echo "   ✓ All items have tipe_item classification\n";
    }
}

// Check NON-ORI items without category
$result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='NON_ORI' AND (kategori_rak IS NULL OR kategori_rak='') AND statusitem='1'");
if ($result) {
    $count = mysqli_fetch_assoc($result)['count'];
    if ($count > 0) {
        echo "   ⚠ $count NON-ORI items without category\n";
    } else {
        echo "   ✓ All NON-ORI items have categories\n";
    }
}

// Check ORI items without merek
$result = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='ORI' AND (merek IS NULL OR merek='') AND statusitem='1'");
if ($result) {
    $count = mysqli_fetch_assoc($result)['count'];
    if ($count > 0) {
        echo "   ⚠ $count ORI items without brand\n";
    } else {
        echo "   ✓ All ORI items have brands\n";
    }
}

mysqli_close($koneksi);
echo "\n=== ANALYSIS COMPLETE ===\n";
?>