<?php
// mark_super_admin_visibility.php

require 'database_connection.php'; // Include koneksi database kamu

try {
    // Ambil kode_karyawan untuk semua pengguna yang memiliki role super_admin
    $query = "SELECT kode_karyawan FROM users WHERE role = 'super_admin'";
    $stmt = $pdo->query($query);
    $super_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Cek apakah ada super_admin di database
    if ($super_admins) {
        // Ambil sidebar ID terbaru yang ditambahkan
        $query = "SELECT id FROM sidebars ORDER BY id DESC LIMIT 1";
        $stmt = $pdo->query($query);
        $latest_sidebar = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($latest_sidebar) {
            $sidebar_id = $latest_sidebar['id'];

            // Update is_visible untuk super_admin
            $update_sql = "UPDATE user_sidebar_settings 
                           SET is_visible = 1 
                           WHERE kode_karyawan = :kode_karyawan AND sidebar_id = :sidebar_id";

            $stmt = $pdo->prepare($update_sql);

            // Loop melalui semua super_admin dan update visibility
            foreach ($super_admins as $admin) {
                $stmt->execute([
                    ':kode_karyawan' => $admin['kode_karyawan'],
                    ':sidebar_id' => $sidebar_id
                ]);
            }

            echo "Visibility updated for super_admins.";
        } else {
            echo "No sidebar found.";
        }
    } else {
        echo "No super admin found.";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
