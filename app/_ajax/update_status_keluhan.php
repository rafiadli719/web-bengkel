<?php
// File: _ajax/update_status_keluhan.php
session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

include "../../config/koneksi.php";

$id = $_POST['id'] ?? '';
$status = $_POST['status'] ?? '';
$keterangan = $_POST['keterangan'] ?? '';

if(empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID not provided']);
    exit;
}

// Map status to valid enums if necessary, or just use direct values
// Valid values: 'datang', 'diproses', 'selesai', 'tidak_selesai'
$valid_statuses = ['datang', 'diproses', 'selesai', 'tidak_selesai'];
if(!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Sanitize inputs
$id = mysqli_real_escape_string($koneksi, $id);
$status = mysqli_real_escape_string($koneksi, $status);
$keterangan = mysqli_real_escape_string($koneksi, $keterangan);
$user_login = $_SESSION['_nama'] ?? 'System';

// Update query
$query = "UPDATE tbservis_keluhan_status 
          SET status_pengerjaan = '$status', 
              keterangan_tidak_selesai = '$keterangan',
              updated_at = NOW() 
          WHERE id = '$id'";

if(mysqli_query($koneksi, $query)) {
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($koneksi)]);
}
?>
