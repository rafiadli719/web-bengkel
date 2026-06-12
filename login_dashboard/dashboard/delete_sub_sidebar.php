<?php 
session_start();
include 'database_connection.php';

header('Content-Type: application/json'); // Pastikan respons adalah JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil sub_sidebar_name dan sub_sidebar_url dari request POST
    $sub_sidebar_name = $_POST['sub_sidebar_name'];
    $sub_sidebar_url = $_POST['sub_sidebar_url'] ?? ''; // Pastikan ini ada di request

    // Hapus kolom sub_sidebar_name dan sub_sidebar_url dari database berdasarkan sub_sidebar_name
    $deleteSidebarQuery = "DELETE FROM dynamic_sidebars WHERE sub_sidebar_name = ? AND sub_sidebar_url = ?";
    $deleteSidebarStmt = $pdo->prepare($deleteSidebarQuery);

    if ($deleteSidebarStmt->execute([$sub_sidebar_name, $sub_sidebar_url])) {
        echo json_encode(['success' => true, 'message' => 'Sub-sidebar berhasil dihapus.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus sub-sidebar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
