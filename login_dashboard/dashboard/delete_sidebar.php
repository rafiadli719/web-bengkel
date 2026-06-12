<?php
session_start();
include 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil sidebar_name yang akan dihapus
    $sidebar_name = $_POST['sidebar_name'];

    // Ambil sidebar_id berdasarkan sidebar_name untuk digunakan dalam penghapusan di user_sidebar_settings
    $stmt = $pdo->prepare("SELECT id FROM dynamic_sidebars WHERE sidebar_name = ?");
    $stmt->execute([$sidebar_name]);
    $sidebar = $stmt->fetch();

    if ($sidebar) {
        $sidebar_id = $sidebar['id'];

        // Hapus data dari user_sidebar_settings berdasarkan sidebar_id dan sub-sidebar terkait
        $deleteSettingsQuery = "DELETE FROM user_sidebar_settings WHERE sidebar_id = ? OR sidebar_id IN (SELECT id FROM dynamic_sidebars WHERE parent_id = ?)";
        $deleteSettingsStmt = $pdo->prepare($deleteSettingsQuery);
        $deleteSettingsStmt->execute([$sidebar_id, $sidebar_id]);

        // Hapus sub-sidebar yang memiliki parent_id sesuai sidebar_id
        $deleteSubSidebarQuery = "DELETE FROM dynamic_sidebars WHERE parent_id = ?";
        $deleteSubSidebarStmt = $pdo->prepare($deleteSubSidebarQuery);
        $deleteSubSidebarStmt->execute([$sidebar_id]);

        // Hapus sidebar dari database berdasarkan sidebar_id
        $deleteSidebarQuery = "DELETE FROM dynamic_sidebars WHERE id = ?";
        $deleteSidebarStmt = $pdo->prepare($deleteSidebarQuery);

        if ($deleteSidebarStmt->execute([$sidebar_id])) {
            echo json_encode(['success' => true, 'message' => 'Sidebar dan sub-sidebar berhasil dihapus.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus sidebar.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Sidebar tidak ditemukan.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
