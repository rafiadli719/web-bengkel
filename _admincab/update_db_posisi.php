<?php
include __DIR__ . "/../config/koneksi.php";

// Check if permissions column exists
if(isset($koneksi)) {
    $check = mysqli_query($koneksi, "SHOW COLUMNS FROM tb_master_posisi LIKE 'permissions'");
    if(mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE tb_master_posisi ADD COLUMN permissions JSON DEFAULT NULL COMMENT 'JSON array of permissions' AFTER user_akses_level";
        if(mysqli_query($koneksi, $sql)) {
            echo "Column 'permissions' added successfully to tb_master_posisi.";
        } else {
            echo "Error adding column: " . mysqli_error($koneksi);
        }
    } else {
        echo "Column 'permissions' already exists.";
    }
} else {
    echo "Database connection failed.";
}
?>
