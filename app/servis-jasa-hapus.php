<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    include "../config/koneksi.php";
    
    $jid = $_GET['jid'];
    $snoserv = $_GET['snoserv'];
    
    // Delete jasa item
    mysqli_query($koneksi,"DELETE FROM tblservis_jasa WHERE id='$jid'");
    
    echo"<script>window.alert('Item service berhasil dihapus!');
    window.location=('servis-input-reguler.php?snoserv=$snoserv');
    </script>";
}
?>