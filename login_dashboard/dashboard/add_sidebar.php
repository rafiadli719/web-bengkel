<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'database_connection.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $sidebar_name = $_POST['sidebar_name'];
    $sidebar_url = $_POST['sidebar_url'];
    $parent_sidebar_name = $_POST['parent_sidebar_name'];

    // Jika tidak ada parent (value 0), set parent_id ke NULL
    $parent_id = null;
    if ($parent_sidebar_name !== '0') {
        // Cari ID berdasarkan sidebar_name
        $query = "SELECT id FROM dynamic_sidebars WHERE sidebar_name = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$parent_sidebar_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $parent_id = $result['id'];  // Ambil id dari parent sidebar
        }
    }

    // Validasi input
    if (!empty($sidebar_name) && !empty($sidebar_url)) {
        $query = "INSERT INTO dynamic_sidebars (sidebar_name, sidebar_url, parent_id) VALUES (?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$sidebar_name, $sidebar_url, $parent_id]);

        if ($stmt->rowCount() > 0) {
            // Ambil ID dari sidebar yang baru ditambahkan
            $newSidebarId = $pdo->lastInsertId();

            // Update tabel user_sidebar_settings untuk semua karyawan yang memiliki role "super admin" di tabel users
            $updateQuery = "
                INSERT INTO user_sidebar_settings (employee_code, sidebar_id, is_visible)
                SELECT u.kode_karyawan, ?, 1
                FROM users u
                WHERE u.role = 'Super_admin'
            ";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$newSidebarId]);

            echo "Sidebar berhasil ditambahkan dan diperbarui untuk super admin.";
        } else {
            echo "Gagal menambah sidebar.";
        }
    } else {
        echo "Nama dan URL sidebar tidak boleh kosong.";
    }
}
?>
