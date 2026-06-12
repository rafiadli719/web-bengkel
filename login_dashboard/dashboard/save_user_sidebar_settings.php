<?php
include 'database_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

$kode_karyawan = $data['employee'];
$sidebars = $data['sidebars'];

// Hapus semua pengaturan sidebar lama untuk karyawan ini
$query = "DELETE FROM user_sidebar_settings WHERE kode_karyawan = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$kode_karyawan]);

// Simpan pengaturan baru
$query = "INSERT INTO user_sidebar_settings (kode_karyawan, sidebar_id, is_visible) VALUES (?, ?, 1)";
$stmt = $pdo->prepare($query);

foreach ($sidebars as $sidebar_id) {
    $stmt->execute([$kode_karyawan, $sidebar_id]);
}

echo json_encode(['status' => 'success']);
?>
