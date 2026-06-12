<?php
/**
 * AJAX Handler: Submit Keluhan Baru (Perlu Approval)
 * File: ajax-submit-keluhan-baru.php
 * Deskripsi: Menangani pengajuan keluhan baru dari cabang yang perlu approval pusat
 */

session_start();
header('Content-Type: application/json');

// Check session
if(empty($_SESSION['_iduser'])){
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Silakan login kembali.'
    ]);
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

include "../config/koneksi.php";

// Get user info
$query_user = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='$id_user'");
$user_data = mysqli_fetch_array($query_user);
$nama_user = $user_data['nama_user'] ?? 'Unknown';

// Validate POST data
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get and validate input
$nama_keluhan = isset($_POST['nama_keluhan']) ? mysqli_real_escape_string($koneksi, trim($_POST['nama_keluhan'])) : '';
$deskripsi = isset($_POST['deskripsi']) ? mysqli_real_escape_string($koneksi, trim($_POST['deskripsi'])) : '';
$kategori = isset($_POST['kategori']) ? mysqli_real_escape_string($koneksi, $_POST['kategori']) : '';
$alasan_pengajuan = isset($_POST['alasan_pengajuan']) ? mysqli_real_escape_string($koneksi, trim($_POST['alasan_pengajuan'])) : '';

// Validation
if(empty($nama_keluhan)) {
    echo json_encode([
        'success' => false,
        'message' => 'Nama keluhan harus diisi'
    ]);
    exit;
}

if(empty($kategori)) {
    echo json_encode([
        'success' => false,
        'message' => 'Kategori harus dipilih'
    ]);
    exit;
}

// Alasan pengajuan opsional (tidak wajib)

// Check duplicate keluhan name
$check_duplicate = mysqli_query($koneksi, "SELECT id FROM tbmaster_keluhan 
                                           WHERE LOWER(nama_keluhan) = LOWER('$nama_keluhan') 
                                           AND status_aktif='1'");
if(mysqli_num_rows($check_duplicate) > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Keluhan dengan nama yang sama sudah ada di sistem'
    ]);
    exit;
}

// Generate kode keluhan otomatis
$query_max = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(kode_keluhan, 4) AS UNSIGNED)) as max_no 
                                     FROM tbmaster_keluhan 
                                     WHERE kode_keluhan LIKE 'KEL%'");
$data_max = mysqli_fetch_array($query_max);
$next_no = ($data_max['max_no'] ?? 0) + 1;
$kode_keluhan = 'KEL' . str_pad($next_no, 3, '0', STR_PAD_LEFT);

// Gabungkan alasan pengajuan ke deskripsi
$deskripsi_lengkap = $deskripsi;
if(!empty($alasan_pengajuan)) {
    $deskripsi_lengkap .= "\n\n[ALASAN PENGAJUAN]\n" . $alasan_pengajuan;
}

// Insert keluhan baru dengan status pending
$query_insert = "INSERT INTO tbmaster_keluhan 
                (kode_keluhan, nama_keluhan, deskripsi, kategori, 
                 status_aktif, status_approval, requested_by, requested_from) 
                VALUES 
                ('$kode_keluhan', '$nama_keluhan', '$deskripsi_lengkap', '$kategori',
                 '1', 'pending', '$nama_user', '$kd_cabang')";

$result = mysqli_query($koneksi, $query_insert);

if($result) {
    // Log activity (opsional)
    $log_message = "Keluhan baru diajukan: $kode_keluhan - $nama_keluhan (oleh: $nama_user dari cabang: $kd_cabang)";
    // mysqli_query($koneksi, "INSERT INTO tb_log_activity (user_id, activity, created_at) VALUES ('$id_user', '$log_message', NOW())");
    
    echo json_encode([
        'success' => true,
        'message' => 'Keluhan berhasil diajukan',
        'kode_keluhan' => $kode_keluhan,
        'nama_keluhan' => $nama_keluhan,
        'status' => 'pending'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan data: ' . mysqli_error($koneksi)
    ]);
}

mysqli_close($koneksi);
?>
