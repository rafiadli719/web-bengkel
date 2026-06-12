<?php
session_start();
include 'database_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $sidebar_name = $_POST['sidebar_name'];
    $new_sidebar_name = $_POST['new_sidebar_name'];
    $sidebar_url = $_POST['sidebar_url'];

    // Update sidebar di database berdasarkan sidebar_name
    $query = "UPDATE dynamic_sidebars SET sidebar_name = ?, sidebar_url = ? WHERE sidebar_name = ?";
    $stmt = $pdo->prepare($query);

    if ($stmt->execute([$new_sidebar_name, $sidebar_url, $sidebar_name])) {
        echo json_encode(['success' => true, 'message' => 'Sidebar berhasil diperbarui.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui sidebar.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Metode permintaan tidak valid.']);
}
?>
