<?php
include "../config/koneksi.php";

function execute_query($koneksi, $sql, $description) {
    if (mysqli_query($koneksi, $sql)) {
        echo "[SUCCESS] $description<br>";
    } else {
        echo "[ERROR] $description: " . mysqli_error($koneksi) . "<br>";
    }
}

echo "<h3>Starting Database Update for Dynamic Categories...</h3>";

// 1. Modify master_kategori_member.nama_kategori
$sql = "ALTER TABLE master_kategori_member MODIFY COLUMN nama_kategori VARCHAR(50) NOT NULL";
execute_query($koneksi, $sql, "Convert master_kategori_member.nama_kategori to VARCHAR(50)");

// 2. Modify setting_highlight_member.kategori_member
$sql = "ALTER TABLE setting_highlight_member MODIFY COLUMN kategori_member VARCHAR(50) NOT NULL";
execute_query($koneksi, $sql, "Convert setting_highlight_member.kategori_member to VARCHAR(50)");

// 3. Modify statistik_pelanggan.status_member
$sql = "ALTER TABLE statistik_pelanggan MODIFY COLUMN status_member VARCHAR(50) NOT NULL DEFAULT 'Bronze'";
execute_query($koneksi, $sql, "Convert statistik_pelanggan.status_member to VARCHAR(50)");

// 4. Modify statistik_pelanggan.kategori_member_kunjungan
$sql = "ALTER TABLE statistik_pelanggan MODIFY COLUMN kategori_member_kunjungan VARCHAR(50) DEFAULT 'Bronze'";
execute_query($koneksi, $sql, "Convert statistik_pelanggan.kategori_member_kunjungan to VARCHAR(50)");

echo "<h3>Update Complete.</h3>";
?>
