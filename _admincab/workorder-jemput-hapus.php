<?php
    session_start();
    if(empty($_SESSION['_iduser'])){
        header("location:../index.php");
    } else {
        include "../config/koneksi.php";
        
        $wo_id = $_GET['woid'];
        $no_service = $_GET['snoserv'];
        
        // Hapus work order dari service
        mysqli_query($koneksi,"DELETE FROM tbservis_workorder WHERE id='$wo_id'");
        
        echo"<script>window.location=('servis-input-reguler-jemput.php?snoserv=$no_service');</script>";
    }
?>