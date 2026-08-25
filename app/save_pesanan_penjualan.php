<?php
    include "../config/koneksi.php";
    
	$no_order= mysqli_real_escape_string($koneksi, $_POST['no_order']);
    $txttotal= mysqli_real_escape_string($koneksi, $_POST['txttotal']); 
    $txtpotfaktur_persen= mysqli_real_escape_string($koneksi, $_POST['txtpotfaktur_persen']);  
    $txtpotfaktur_nom= mysqli_real_escape_string($koneksi, $_POST['txtpotfaktur_nom']);   
    $txtpajak_persen= mysqli_real_escape_string($koneksi, $_POST['txtpajak_persen']);   
    $txtpajak_nom= mysqli_real_escape_string($koneksi, $_POST['txtpajak_nom']);   
    $txtnet= mysqli_real_escape_string($koneksi, $_POST['txtnet']);   
    $txtdp= mysqli_real_escape_string($koneksi, $_POST['txtdp']);   
    $txtkekurangan= mysqli_real_escape_string($koneksi, $_POST['txtkekurangan']);   

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