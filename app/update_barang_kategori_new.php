<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// Ambil data dari form
$id = mysqli_real_escape_string($koneksi, $_POST['txtid']);
$keterangan = strtoupper(trim($_POST['txtketerangan']));

// Validasi input
if (empty($keterangan)) {
    $_SESSION['error'] = 'Keterangan harus diisi!';
    header("location:barang_kategori_edit_new.php?kd=$id");
    exit;
}

// Update ke database
$update_query = "UPDATE tblitemjenis SET keterangan = '$keterangan' WHERE id = '$id'";

if (mysqli_query($koneksi, $update_query)) {
    $_SESSION['success'] = 'Data kategori berhasil diperbarui!';
    header("location:barang_kategori_new.php");
} else {
    $_SESSION['error'] = 'Gagal memperbarui data: ' . mysqli_error($koneksi);
    header("location:barang_kategori_edit_new.php?kd=$id");
}
?>
