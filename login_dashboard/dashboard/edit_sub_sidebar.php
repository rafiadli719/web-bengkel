<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
session_start();
include 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $sidebar_name = $_POST['sidebar_name'];
    $current_sub_sidebar_name = $_POST['current_sub_sidebar_name']; // Nama sub-sidebar yang akan diedit
    $new_sub_sidebar_name = $_POST['new_sub_sidebar_name']; // Nama sub-sidebar yang baru
    $sub_sidebar_url = $_POST['sub_sidebar_url']; // URL sub-sidebar yang baru

    // Update sub-sidebar di database berdasarkan sidebar_name dan sub_sidebar_name
    $query = "UPDATE dynamic_sidebars SET sub_sidebar_name = ?, sub_sidebar_url = ? WHERE sidebar_name = ? AND sub_sidebar_name = ?";
    $stmt = $pdo->prepare($query);

    if ($stmt->execute([$new_sub_sidebar_name, $sub_sidebar_url, $sidebar_name, $current_sub_sidebar_name])) {
        echo json_encode(['success' => true, 'message' => 'Sub-sidebar berhasil diperbarui.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui sub-sidebar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
