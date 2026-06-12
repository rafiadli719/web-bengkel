<?php
// Quick diagnostic to get tbuser column names
include "../config/koneksi.php";

echo "<h3>tbuser Table Columns:</h3>";
$result = mysqli_query($koneksi, "DESCRIBE tbuser");

if ($result) {
    echo "<ol>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li><strong>{$row['Field']}</strong> ({$row['Type']})</li>";
    }
    echo "</ol>";
    
    // Find last column name
    mysqli_data_seek($result, 0);
    $last_column = '';
    while ($row = mysqli_fetch_assoc($result)) {
        $last_column = $row['Field'];
    }
    
    echo "<hr>";
    echo "<h4>✅ Recommended SQL (use last column):</h4>";
    echo "<pre>";
    echo "ALTER TABLE tbuser \n";
    echo "ADD COLUMN kode_posisi VARCHAR(20) NULL \n";
    echo "COMMENT 'Foreign key to tb_master_posisi.kode_posisi'\n";
    echo "AFTER $last_column;";
    echo "</pre>";
    
} else {
    echo "Error: " . mysqli_error($koneksi);
}
?>
