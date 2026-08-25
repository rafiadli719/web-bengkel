<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    include "../config/koneksi.php";
    
    $bid = mysqli_real_escape_string($koneksi, $_GET['bid']);
    $snoserv = mysqli_real_escape_string($koneksi, $_GET['snoserv']);
    
    // Delete barang item
    mysqli_query($koneksi,"DELETE FROM tblservis_barang WHERE id='$bid'");
    
    echo"<script>window.alert('Item barang berhasil dihapus!');
    window.location=('servis-input-reguler.php?snoserv=$snoserv');
    </script>";
}
?>