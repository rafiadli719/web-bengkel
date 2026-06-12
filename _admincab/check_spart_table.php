<?php
include '../config/koneksi.php';

$result = mysqli_query($koneksi, 'SHOW TABLES LIKE "tblitem_spart"');
if ($result && mysqli_num_rows($result) > 0) {
    echo "tblitem_spart exists\n";
    $desc = mysqli_query($koneksi, 'DESCRIBE tblitem_spart');
    while ($row = mysqli_fetch_assoc($desc)) {
        echo "  {$row['Field']} | {$row['Type']}\n";
    }
    $count = mysqli_fetch_assoc(mysqli_query($koneksi, 'SELECT COUNT(*) as count FROM tblitem_spart'))['count'];
    echo "Records: $count\n";
} else {
    echo "tblitem_spart NOT FOUND - creating table...\n";
    $create_sql = "CREATE TABLE `tblitem_spart` (
        `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
        `noitem` VARCHAR(20) NOT NULL,
        `kode_tipe` INT(11) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_noitem` (`noitem`),
        INDEX `idx_kode_tipe` (`kode_tipe`)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    
    if (mysqli_query($koneksi, $create_sql)) {
        echo "✓ tblitem_spart created successfully\n";
    } else {
        echo "✗ Failed to create tblitem_spart: " . mysqli_error($koneksi) . "\n";
    }
}

mysqli_close($koneksi);
?>