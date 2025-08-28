<?php
    include "../config/koneksi.php";
    
	$no_order= $_POST['no_order'];
    $txttotal= $_POST['txttotal']; 
    $txtpotfaktur_persen= $_POST['txtpotfaktur_persen'];  
    $txtpotfaktur_nom= $_POST['txtpotfaktur_nom'];   
    $txtpajak_persen= $_POST['txtpajak_persen'];   
    $txtpajak_nom= $_POST['txtpajak_nom'];   
    $txtnet= $_POST['txtnet'];   
    $txtdp= $_POST['txtdp'];   
    $txtkekurangan= $_POST['txtkekurangan'];   

    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    sum(quantity) as tot 
                                    FROM tblorderjual_detail 
                                    WHERE no_order='$no_order'");
    $tm_cari=mysqli_fetch_array($cari_kd);	
    $totqty=$tm_cari['tot'];
        
    mysqli_query($koneksi,"UPDATE tblorderjual_header 
                            SET 
                            total_qty='$totqty', 
                            total_jual='$txttotal', 
                            diskon='$txtpotfaktur_persen', 
                            total_diskon='$txtpotfaktur_nom', 
                            pajak='$txtpajak_persen', 
                            total_pajak='$txtpajak_nom', 
                            total_akhir='$txtnet', 
                            pembayaran='$txtdp' 
                            WHERE 
                            no_order='$no_order'");

   echo"<script>window.alert('Data Pesanan Penjualan berhasil disimpan!');
   window.location=('pesanan_penjualan_add_print.php?nopesanan=$no_order');</script>";        
?>