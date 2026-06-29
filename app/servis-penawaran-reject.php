<?php
/**
 * Backend: Reject Penawaran Part
 * Mencatat penolakan penawaran part beserta alasan/keterangan
 */

session_start();

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
include "../config/koneksi.php";

// Get user info
$cari_user = mysqli_query($koneksi, "SELECT nama_user FROM tbuser WHERE id='$id_user'");
$user_data = mysqli_fetch_array($cari_user);
$nama_user = $user_data['nama_user'] ?? '';

// Get parameters
$penawaran_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$no_service   = isset($_GET['snoserv']) ? mysqli_real_escape_string($koneksi, $_GET['snoserv']) : '';
$alasan_code  = isset($_GET['alasan']) ? mysqli_real_escape_string($koneksi, $_GET['alasan']) : '';
$keterangan   = isset($_GET['ket']) ? mysqli_real_escape_string($koneksi, $_GET['ket']) : '';

if($penawaran_id == 0 || empty($no_service)) {
    echo "<script>alert('Parameter tidak valid!'); history.back();</script>";
    exit;
}

// Normalisasi alasan — harus sesuai ENUM kolom alasan_tolak di database
$valid_alasan = ['customer_tidak_mau','stok_bengkel_kosong','stok_supplier_kosong','harga_tidak_cocok','lainnya'];
if(!in_array($alasan_code, $valid_alasan, true)) {
    $alasan_code = 'lainnya';
}

// Get penawaran data
$q_pen = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_part WHERE id='$penawaran_id'");
if(!$q_pen || mysqli_num_rows($q_pen) == 0){
    echo "<script>alert('Penawaran tidak ditemukan!'); history.back();</script>";
    exit;
}
$penawaran = mysqli_fetch_array($q_pen);

if($penawaran['status_penawaran'] !== 'pending'){
    echo "<script>alert('Penawaran ini sudah diproses sebelumnya! Status: ".$penawaran['status_penawaran']."'); history.back();</script>";
    exit;
}

mysqli_begin_transaction($koneksi);
try {
    // Update status penawaran menjadi ditolak
    $sql_update = "UPDATE tbservis_penawaran_part SET 
                    status_penawaran='ditolak',
                    alasan_tolak='$alasan_code',
                    keterangan_tolak='$keterangan',
                    tanggal_respon=NOW(),
                    user_respon='$id_user'
                   WHERE id='$penawaran_id'";
    if(!mysqli_query($koneksi, $sql_update)){
        throw new Exception('Error update penawaran: ' . mysqli_error($koneksi));
    }

    // Log activity
    $log_desc = "Penawaran DITOLAK: {$penawaran['nama_barang']} (Qty: {$penawaran['quantity']}) - Alasan: $alasan_code";
    mysqli_query($koneksi, "INSERT INTO tbservis_penawaran_log
                    (penawaran_id, no_service, action, alasan, user_action, created_at)
                    VALUES
                    ('$penawaran_id', '$no_service', 'ditolak', '$alasan_code', '$id_user', NOW())");

    mysqli_query($koneksi, "INSERT INTO tbservis_temuan_activity_log
                    (no_service, temuan_id, penawaran_id, activity_type, description, user_id, user_name, created_at)
                    VALUES
                    ('$no_service', '{$penawaran['temuan_id']}', '$penawaran_id', 'penawaran_rejected', '$log_desc', '$id_user', '$nama_user', NOW())");

    // Update rejection summary stats
    mysqli_query($koneksi, "INSERT INTO tbservis_penawaran_rejection_summary
                    (kode_barang, nama_barang, total_penawaran, total_diterima, total_ditolak, last_offered_date)
                    VALUES
                    ('{$penawaran['kode_barang']}', '{$penawaran['nama_barang']}', 1, 0, 1, CURDATE())
                    ON DUPLICATE KEY UPDATE
                    total_penawaran = total_penawaran + 1,
                    total_ditolak = total_ditolak + 1,
                    acceptance_rate = (total_diterima / total_penawaran) * 100,
                    last_offered_date = CURDATE(),
                    updated_at = NOW()");

    mysqli_commit($koneksi);

    $redirect_url = $_SERVER['HTTP_REFERER'] ?? "servis-carinopol.php";
    echo "<script>alert('Penawaran DITOLAK dan dicatat.'); window.location='$redirect_url';</script>";
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); history.back();</script>";
}
