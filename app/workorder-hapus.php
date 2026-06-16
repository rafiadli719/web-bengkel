<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    include "../config/koneksi.php";
    
    $woid = $_GET['woid'];
    $snoserv = $_GET['snoserv'];
    
    // Get work order info before deleting
    $get_wo = mysqli_query($koneksi,"SELECT kode_wo FROM tbservis_workorder WHERE id='$woid'");
    $wo_data = mysqli_fetch_array($get_wo);
    $kode_wo = $wo_data['kode_wo'];
    
    // Delete work order from service
    mysqli_query($koneksi,"DELETE FROM tbservis_workorder WHERE id='$woid'");
    
    // Also remove related service items and parts that were added by this work order
    // Remove jasa items
    $wo_details = mysqli_query($koneksi,"SELECT kode_barang, tipe FROM tbworkorderdetail WHERE kode_wo='$kode_wo'");
    while($detail = mysqli_fetch_array($wo_details)) {
        if($detail['tipe'] == '1') { // Jasa
            mysqli_query($koneksi,"DELETE FROM tblservis_jasa WHERE no_service='$snoserv' AND no_item='{$detail['kode_barang']}' LIMIT 1");
        } else { // Barang
            mysqli_query($koneksi,"DELETE FROM tblservis_barang WHERE no_service='$snoserv' AND no_item='{$detail['kode_barang']}' LIMIT 1");
        }
    }
    
    echo"<script>window.alert('Work Order berhasil dihapus!');
    </script>";
}
?>