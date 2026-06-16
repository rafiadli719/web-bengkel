<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

// Ambil data dari form
$id = $_POST['txtid'];
$kategori = strtoupper(trim($_POST['txtkategori']));
$nama = strtoupper(trim($_POST['txtnama']));
$keterangan = strtoupper(trim($_POST['txtketerangan']));
$margin_sesuai_jenis = $_POST['margin_sesuai_jenis'];
$margin_kategori = $_POST['txtmargin'] ?? null;

// Validasi input
if (empty($kategori) || empty($nama) || empty($keterangan) || empty($margin_sesuai_jenis)) {
    echo "<script>window.alert('Error: Semua field wajib harus diisi!');window.location=('barang_kategori_edit.php?kd=$id');</script>";
    exit;
}

// Validasi margin jika diperlukan
if ($margin_sesuai_jenis === 'TIDAK' && (empty($margin_kategori) || $margin_kategori < 0)) {
    echo "<script>window.alert('Error: Margin Kategori harus diisi dengan nilai yang valid jika pilih TIDAK!');window.location=('barang_kategori_edit.php?kd=$id');</script>";
    exit;
}

// Set nilai untuk database
$ikut_margin_jenis = ($margin_sesuai_jenis === 'YA') ? '1' : '0';
$margin_khusus = ($margin_sesuai_jenis === 'TIDAK') ? $margin_kategori : null;

// Update ke database
$update_query = "UPDATE tblitemjenis SET 
                 jenis = '$kategori',
                 namajenis = '$nama', 
                 keterangan = '$keterangan',
                 ikut_margin_jenis = '$ikut_margin_jenis',
                 margin_khusus = " . ($margin_khusus !== null ? $margin_khusus : 'NULL') . "
                 WHERE id = '$id'";

if (mysqli_query($koneksi, $update_query)) {
    echo "<script>window.alert('✅ Data kategori berhasil diperbarui!\\n\\nKategori: $kategori\\nNama: $nama\\nKeterangan: $keterangan\\nMargin Sesuai Jenis: $margin_sesuai_jenis\\nMargin Kategori: " . ($margin_khusus ? $margin_khusus . '%' : '-') . "');window.location=('barang_kategori.php');</script>";
} else {
    $error = mysqli_error($koneksi);
    echo "<script>window.alert('Error: Gagal memperbarui data - $error');window.location=('barang_kategori_edit.php?kd=$id');</script>";
}
?>
