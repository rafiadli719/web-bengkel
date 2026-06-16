<?php
session_start();

if(empty($_SESSION['_iduser'])){
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

include "../config/koneksi.php";

$kode_keluhan = $_GET['kode_keluhan'] ?? '';
$kode_workorder = $_GET['kode_workorder'] ?? '';

if(empty($kode_keluhan) || empty($kode_workorder)) {
    echo json_encode(['exists' => false]);
    exit;
}

// Check if mapping already exists
$query = "SELECT prioritas, status_aktif FROM tbmaster_keluhan_workorder 
          WHERE kode_keluhan = ? AND kode_workorder = ?";

$stmt = mysqli_prepare($koneksi, $query);
mysqli_stmt_bind_param($stmt, "ss", $kode_keluhan, $kode_workorder);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if($row = mysqli_fetch_array($result)) {
    echo json_encode([
        'exists' => true,
        'prioritas' => $row['prioritas'],
        'status_aktif' => $row['status_aktif']
    ]);
} else {
    echo json_encode(['exists' => false]);
}

mysqli_stmt_close($stmt);
?>
