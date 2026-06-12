<?php
session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

if (!isset($_POST['kode_wo']) || !isset($_POST['no_service'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

$kode_wo = $_POST['kode_wo'];
$no_service = $_POST['no_service'];

try {
    // Check if work order already exists for this service
    $query_check = "SELECT COUNT(*) as count FROM tbservis_workorder WHERE no_service = ? AND kode_wo = ?";
    $stmt_check = mysqli_prepare($koneksi, $query_check);
    mysqli_stmt_bind_param($stmt_check, "ss", $no_service, $kode_wo);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $check_data = mysqli_fetch_assoc($result_check);
    
    if ($check_data['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Work order sudah ditambahkan ke service ini']);
        exit;
    }
    
    // Insert work order to service
    $query_insert = "INSERT INTO tbservis_workorder (no_service, kode_wo, status_pengerjaan, created_at) 
                     VALUES (?, ?, 'diproses', NOW())";
    $stmt_insert = mysqli_prepare($koneksi, $query_insert);
    mysqli_stmt_bind_param($stmt_insert, "ss", $no_service, $kode_wo);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        echo json_encode(['success' => true, 'message' => 'Work order berhasil ditambahkan ke service']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan work order ke service']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt_check)) mysqli_stmt_close($stmt_check);
    if (isset($stmt_insert)) mysqli_stmt_close($stmt_insert);
}
?>