<?php
// This file has been replaced by barang_kategori_del_new.php
// Redirect to new deletion handler with validation
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

$txtid = $_GET['kd'] ?? null;
if (empty($txtid)) {
    echo "<script>window.alert('Error: ID kategori tidak ditemukan.');window.location=('barang_kategori.php');</script>";
    exit;
}

// Redirect to new handler with delete action
header("location:barang_kategori_del_new.php?kd=$txtid&action=delete");
exit;
?>
