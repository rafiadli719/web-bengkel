<?php
// F2-A: Catat DP (Down Payment) untuk servis mesin besar / part inden (Q9)
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";
include "../helper-functions.php";

$no_service = $_POST['no_service'] ?? '';
$jumlah_dp = (float) str_replace(['.', ','], '', $_POST['jumlah_dp'] ?? '0');
$keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');
$kd_cabang = $_SESSION['_cabang'] ?? '';
$id_user = $_SESSION['_iduser'];

if (empty($no_service)) {
    echo json_encode(['success' => false, 'message' => 'Nomor service tidak valid']);
    exit;
}

$ns = mysqli_real_escape_string($koneksi, $no_service);
$rs = mysqli_query($koneksi, "SELECT boleh_dp, status_servis, COALESCE(total,0) AS total FROM tblservice WHERE no_service='$ns' LIMIT 1");
if (!$rs || mysqli_num_rows($rs) === 0) {
    echo json_encode(['success' => false, 'message' => 'Service tidak ditemukan']);
    exit;
}
$srv = mysqli_fetch_assoc($rs);

if ((int)$srv['boleh_dp'] !== 1) {
    echo json_encode(['success' => false, 'message' => 'Service ini belum ditandai sebagai servis mesin besar / part inden']);
    exit;
}
if (in_array($srv['status_servis'], ['bayar', 'selesai', 'cancel'])) {
    echo json_encode(['success' => false, 'message' => 'Service sudah closing, tidak bisa catat DP baru']);
    exit;
}
if ($jumlah_dp <= 0) {
    echo json_encode(['success' => false, 'message' => 'Nominal DP harus lebih dari 0']);
    exit;
}

// Minimal 50% dari total berjalan (Keputusan bisnis F2-A: DP minimal 50%)
$total_berjalan = (float) $srv['total'];
if ($total_berjalan > 0 && $jumlah_dp < ($total_berjalan * 0.5)) {
    echo json_encode(['success' => false, 'message' => 'DP minimal 50% dari total (Rp ' . number_format($total_berjalan * 0.5, 0, ',', '.') . ')']);
    exit;
}

$no_dp = generateNoDP($koneksi, $kd_cabang);
$tanggal_dp = date('Y-m-d');
$id_user_esc = mysqli_real_escape_string($koneksi, $id_user);
$kd_cabang_esc = mysqli_real_escape_string($koneksi, $kd_cabang);

$ins = mysqli_query($koneksi, "INSERT INTO tb_dp_servis
    (no_service, no_dp, tanggal_dp, jumlah_dp, status, keterangan, id_user, kd_cabang, created_at)
    VALUES ('$ns', '$no_dp', '$tanggal_dp', '$jumlah_dp', 'pending', '$keterangan', '$id_user_esc', '$kd_cabang_esc', NOW())");

if ($ins) {
    echo json_encode([
        'success' => true,
        'message' => 'DP berhasil dicatat',
        'no_dp' => $no_dp,
        'jumlah_dp' => $jumlah_dp,
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan DP: ' . mysqli_error($koneksi)]);
}
