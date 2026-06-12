<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'database_connection.php'; // Atau file koneksi database Anda

if (isset($_GET['employee_id'])) {
    $employee_id = $_GET['employee_id'];

    $stmt = $pdo->prepare("
        SELECT ds.id, ds.sidebar_name, 
        COALESCE(uss.is_visible, 0) AS is_visible 
        FROM dynamic_sidebars ds
        LEFT JOIN user_sidebar_settings uss ON ds.id = uss.sidebar_id AND uss.kode_karyawan = ?
    ");
    $stmt->execute([$employee_id]);

    $sidebars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($sidebars) {
        echo json_encode($sidebars);
    } else {
        // Jika tidak ada sidebar ditemukan, kirimkan respons kosong
        echo json_encode([]);
    }
} else {
    // Jika tidak ada employee_id, kirimkan pesan kesalahan
    http_response_code(400);
    echo json_encode(['error' => 'employee_id parameter is required.']);
}
?>
