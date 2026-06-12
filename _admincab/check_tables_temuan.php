<?php
/**
 * Check Tables untuk Sistem Temuan & Penawaran
 */

include "../config/koneksi.php";

echo "<h2>Checking Tables...</h2>";

$tables = [
    'tbmaster_temuan',
    'tbservis_temuan',
    'tbservis_penawaran_part',
    'tbservis_penawaran_log',
    'tbmaster_kategori_fastmoves',
    'tbmaster_barang_fastmoves',
    'tbmaster_barang_nonitem',
    'tbmaster_keluhan_temuan'
];

foreach($tables as $table) {
    $check = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
    if(mysqli_num_rows($check) > 0) {
        echo "<p style='color:green;'>✅ Table <strong>$table</strong> EXISTS</p>";
        
        // Count rows
        $count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM $table");
        $row = mysqli_fetch_array($count);
        echo "<p style='margin-left:20px;'>Total rows: {$row['total']}</p>";
    } else {
        echo "<p style='color:red;'>❌ Table <strong>$table</strong> NOT FOUND</p>";
    }
}

echo "<hr>";
echo "<h3>Checking Columns in tblservice...</h3>";

$columns = [
    'jumlah_temuan',
    'jumlah_penawaran',
    'total_penawaran',
    'total_penawaran_disetujui'
];

foreach($columns as $col) {
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE '$col'");
    if(mysqli_num_rows($check) > 0) {
        echo "<p style='color:green;'>✅ Column <strong>$col</strong> EXISTS in tblservice</p>";
    } else {
        echo "<p style='color:red;'>❌ Column <strong>$col</strong> NOT FOUND in tblservice</p>";
    }
}
?>
