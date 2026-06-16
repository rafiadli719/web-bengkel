<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
include "../config/koneksi.php";

// Ambil kode work order dari parameter
$kode_wo = $_GET['kode'] ?? '';

if(empty($kode_wo)) {
    header("location:paket.php?error=" . urlencode("Kode work order tidak valid"));
    exit;
}

// Mulai transaction
mysqli_autocommit($koneksi, FALSE);

try {
    // Hapus detail work order terlebih dahulu
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tbworkorderdetail WHERE kode_wo = ?");
    mysqli_stmt_bind_param($stmt, "s", $kode_wo);
    
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus detail work order: " . mysqli_error($koneksi));
    }
    mysqli_stmt_close($stmt);
    
    // Hapus header work order
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tbworkorderheader WHERE kode_wo = ?");
    mysqli_stmt_bind_param($stmt, "s", $kode_wo);
    
    if(!mysqli_stmt_execute($stmt)) {
        throw new Exception("Gagal menghapus header work order: " . mysqli_error($koneksi));
    }
    
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    
    if($affected_rows == 0) {
        throw new Exception("Work order tidak ditemukan atau sudah dihapus");
    }
    
    // Commit transaction
    mysqli_commit($koneksi);
    
    // Redirect dengan pesan sukses
    header("location:paket.php?success=" . urlencode("Work order berhasil dihapus"));
    
} catch(Exception $e) {
    // Rollback transaction
    mysqli_rollback($koneksi);
    
    // Redirect dengan pesan error
    header("location:paket.php?error=" . urlencode($e->getMessage()));
}

mysqli_close($koneksi);
?>
