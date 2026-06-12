<?php
/**
 * Delete Temuan
 * Menghapus temuan beserta penawaran part dan jasa terkait
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

// Get temuan data
$query = mysqli_query($koneksi, "SELECT * FROM tbservis_temuan WHERE id = '$id'");
$temuan = mysqli_fetch_array($query);

if(!$temuan) {
    echo "<script>alert('Temuan tidak ditemukan'); window.location.href='servis-input-reguler.php?snoserv=$snoserv&tab=temuan';</script>";
    exit;
}

// Delete penawaran part terkait
mysqli_query($koneksi, "DELETE FROM tbservis_penawaran_part WHERE temuan_id = '$id'");

// Delete penawaran jasa terkait (jika tabel ada)
$check_jasa_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'tbservis_penawaran_jasa'");
if($check_jasa_table && mysqli_num_rows($check_jasa_table) > 0) {
    mysqli_query($koneksi, "DELETE FROM tbservis_penawaran_jasa WHERE temuan_id = '$id'");
}

// Delete temuan
$delete = mysqli_query($koneksi, "DELETE FROM tbservis_temuan WHERE id = '$id'");

if($delete) {
    echo "<script>alert('Temuan berhasil dihapus!'); window.location.href='servis-input-reguler.php?snoserv=$snoserv&tab=temuan';</script>";
} else {
    echo "<script>alert('Gagal menghapus temuan: ".mysqli_error($koneksi)."'); window.location.href='servis-input-reguler.php?snoserv=$snoserv&tab=temuan';</script>";
}
?>
