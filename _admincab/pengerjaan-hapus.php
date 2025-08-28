<?php
    session_start();
    if(empty($_SESSION['_iduser'])){
        header("location:../index.php");
    } else {
        include "../config/koneksi.php";
        
        // Get parameters
        $pengerjaan_id = $_GET['sid'];
        $no_service = $_GET['snoserv'];
        
        // Delete item pengerjaan
        mysqli_query($koneksi,"DELETE FROM tbservis_pengerjaan WHERE id='$pengerjaan_id'");
        
        // Redirect back to appropriate service input page
        // Check if this is from jemput service or regular service
        $cari_service = mysqli_query($koneksi,"SELECT tipe_service FROM tblservice WHERE no_service='$no_service'");
        $service_data = mysqli_fetch_array($cari_service);
        
        if($service_data && $service_data['tipe_service'] == 'jemput') {
            echo"<script>window.location=('servis-input-reguler-jemput.php?snoserv=$no_service');</script>";
        } else {
            echo"<script>window.location=('servis-input-reguler.php?snoserv=$no_service');</script>";
        }
    }
?>