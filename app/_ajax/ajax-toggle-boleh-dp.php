<?php
// F2-A: Toggle penanda "servis mesin besar / part inden" (boleh_dp) pada tblservice
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$kd_cabang = $_SESSION['_cabang'];

include "../config/koneksi.php";

$no_service = $_POST['no_service'] ?? '';
$boleh_dp = !empty($_POST['boleh_dp']) ? 1 : 0;

if (empty($no_service)) {
    echo json_encode(['success' => false, 'message' => 'Nomor service tidak valid']);
    exit;
}

$ns = mysqli_real_escape_string($koneksi, $no_service);
$cb = mysqli_real_escape_string($koneksi, $kd_cabang);
$upd = mysqli_query($koneksi, "UPDATE tblservice SET boleh_dp=$boleh_dp WHERE no_service='$ns' AND kd_cabang='$cb'");

if ($upd) {
    echo json_encode(['success' => true, 'boleh_dp' => $boleh_dp]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update: ' . mysqli_error($koneksi)]);
}
