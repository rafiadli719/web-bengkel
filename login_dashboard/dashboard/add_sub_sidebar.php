<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// add_sub_sidebar.php
include 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $parent_sidebar_name = $_POST['parent_sidebar_name'];
    $sub_sidebar_name = $_POST['sub_sidebar_name'];
    $sub_sidebar_url = $_POST['sub_sidebar_url'];

    // Ambil parent_id dari nama sidebar
    $stmt = $pdo->prepare("SELECT id FROM dynamic_sidebars WHERE sidebar_name = ?");
    $stmt->execute([$parent_sidebar_name]);
    $parent_sidebar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($parent_sidebar) {
        $parent_id = $parent_sidebar['id'];

        // Query untuk menambahkan sub-sidebar
        $query = "INSERT INTO dynamic_sidebars (sidebar_name, sidebar_url, parent_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$sub_sidebar_name, $sub_sidebar_url, $parent_id]);

        if ($stmt->rowCount() > 0) {
            // Ambil ID dari sub-sidebar yang baru ditambahkan
            $newSubSidebarId = $pdo->lastInsertId();

            // Update tabel user_sidebar_settings untuk semua karyawan yang memiliki role "super admin" di tabel users
            $updateQuery = "
                INSERT INTO user_sidebar_settings (employee_code, sidebar_id, is_visible)
                SELECT u.kode_karyawan, ?, 1
                FROM users u
                WHERE u.role = 'Super_admin'
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$newSubSidebarId]);

            echo "Sub-sidebar berhasil ditambahkan dan diperbarui untuk super admin.";
        } else {
            echo "Gagal menambahkan sub-sidebar.";
        }
    } else {
        echo "Sidebar parent tidak ditemukan.";
    }
}
?>
