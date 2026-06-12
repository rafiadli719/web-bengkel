<?php
/**
 * Approve Penawaran Jasa
 * Menyetujui penawaran jasa dan menambahkan ke tblservis_jasa
 */
session_start();
if(empty($_SESSION['_iduser'])) {
    header('Location: ../index.php');
    exit;
}

include "../config/koneksi.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$snoserv = isset($_GET['snoserv']) ? mysqli_real_escape_string($koneksi, $_GET['snoserv']) : '';

if($id <= 0 || empty($snoserv)) {
    echo "<script>alert('Parameter tidak valid'); window.history.back();</script>";
    exit;
}

// Get penawaran data
$query = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_jasa WHERE id = '$id'");
$penawaran = mysqli_fetch_array($query);

// Tentukan halaman redirect berdasarkan tipe_service
$redirect_base = 'servis-input-reguler.php';
$qts = mysqli_query($koneksi, "SELECT tipe_service FROM tblservice WHERE no_service='$snoserv' LIMIT 1");
if($qts && ($rs = mysqli_fetch_assoc($qts))) {
    if(strtolower($rs['tipe_service'] ?? '') === 'jemput') {
        $redirect_base = 'servis-input-reguler-jemput.php';
    }
}

if(!$penawaran) {
    echo "<script>alert('Penawaran tidak ditemukan'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
    exit;
}

$no_service = $penawaran['no_service'];
$kode_jasa = $penawaran['kode_jasa'];
$harga = $penawaran['harga'];
$waktu = $penawaran['waktu_estimasi'];
$user_respon = $_SESSION['_nama'] ?? 'System';

// Update status penawaran
$update = mysqli_query($koneksi, "UPDATE tbservis_penawaran_jasa
                                  SET status_penawaran = 'disetujui',
                                      tanggal_respon = NOW(),
                                      user_respon = '".mysqli_real_escape_string($koneksi, $user_respon)."'
                                  WHERE id = '$id'");

if($update) {
    // Get next nobaris
    $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
    $nobaris_data = mysqli_fetch_array($q_nobaris);
    $nobaris = $nobaris_data['next_nobaris'] ?? 1;

    // Check waktu column exists
    $check_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
    $has_waktu = ($check_waktu && mysqli_num_rows($check_waktu) > 0);

    // Insert ke tblservis_jasa
    if($has_waktu) {
        $insert = mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                           (no_service, nobaris, no_item, harga, waktu, potongan, total)
                                           VALUES
                                           ('$no_service', '$nobaris', '$kode_jasa', '$harga', '$waktu', 0, '$harga')");
    } else {
        $insert = mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                           (no_service, nobaris, no_item, harga, potongan, total)
                                           VALUES
                                           ('$no_service', '$nobaris', '$kode_jasa', '$harga', 0, '$harga')");
    }

    if($insert) {
        echo "<script>alert('Penawaran jasa disetujui dan ditambahkan ke servis!'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
    } else {
        echo "<script>alert('Penawaran disetujui, tapi gagal menambahkan ke servis: ".mysqli_error($koneksi)."'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
    }
} else {
    echo "<script>alert('Gagal menyetujui penawaran: ".mysqli_error($koneksi)."'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
}
?>
