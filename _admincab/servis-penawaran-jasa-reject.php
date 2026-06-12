<?php
/**
 * Reject Penawaran Jasa
 * Menolak penawaran jasa
 */
session_start();
if(empty($_SESSION['_iduser'])) {
    header('Location: ../index.php');
    exit;
}

include "../config/koneksi.php";

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$snoserv = isset($_GET['snoserv']) ? mysqli_real_escape_string($koneksi, $_GET['snoserv']) : '';
$alasan = isset($_GET['alasan']) ? mysqli_real_escape_string($koneksi, $_GET['alasan']) : 'lainnya';

// Tentukan halaman redirect berdasarkan tipe_service
$redirect_base = 'servis-input-reguler.php';
$qts = mysqli_query($koneksi, "SELECT tipe_service FROM tblservice WHERE no_service='$snoserv' LIMIT 1");
if($qts && ($rs = mysqli_fetch_assoc($qts))) {
    if(strtolower($rs['tipe_service'] ?? '') === 'jemput') {
        $redirect_base = 'servis-input-reguler-jemput.php';
    }
}

if($id <= 0 || empty($snoserv)) {
    echo "<script>alert('Parameter tidak valid'); window.history.back();</script>";
    exit;
}

// Get penawaran data
$query = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_jasa WHERE id = '$id'");
$penawaran = mysqli_fetch_array($query);

if(!$penawaran) {
    echo "<script>alert('Penawaran tidak ditemukan'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
    exit;
}

$user_respon = $_SESSION['_nama'] ?? 'System';

// Update status penawaran
$update = mysqli_query($koneksi, "UPDATE tbservis_penawaran_jasa
                                  SET status_penawaran = 'ditolak',
                                      alasan_tolak = '$alasan',
                                      tanggal_respon = NOW(),
                                      user_respon = '".mysqli_real_escape_string($koneksi, $user_respon)."'
                                  WHERE id = '$id'");

if($update) {
    echo "<script>alert('Penawaran jasa ditolak!'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
} else {
    echo "<script>alert('Gagal menolak penawaran: ".mysqli_error($koneksi)."'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
}
?>
