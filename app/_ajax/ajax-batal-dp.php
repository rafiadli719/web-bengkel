<?php
// F2-A: Batalkan DP pending (customer batal servis, DP dikembalikan penuh - Q9)
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

$no_dp = $_POST['no_dp'] ?? '';
if (empty($no_dp)) {
    echo json_encode(['success' => false, 'message' => 'No DP tidak valid']);
    exit;
}

$nd = mysqli_real_escape_string($koneksi, $no_dp);
$upd = mysqli_query($koneksi, "UPDATE tb_dp_servis SET status='batal' WHERE no_dp='$nd' AND status='pending'");

if ($upd && mysqli_affected_rows($koneksi) > 0) {
    echo json_encode(['success' => true, 'message' => 'DP dibatalkan, dikembalikan penuh ke customer']);
} else {
    echo json_encode(['success' => false, 'message' => 'DP tidak ditemukan atau sudah tidak pending']);
}
