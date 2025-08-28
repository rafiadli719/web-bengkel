<?php
    include "../config/koneksi.php";
    
	$no_order= $_POST['no_order'];
    $txttotal= $_POST['txttotal']; 
 
    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    sum(quantity) as tot 
                                    FROM tblorder_detail 
                                    WHERE no_order='$no_order'");
    $tm_cari=mysqli_fetch_array($cari_kd);	
    $totqty=$tm_cari['tot'];
        
    mysqli_query($koneksi,"UPDATE tblorder_header 
                            SET total_qty='$totqty', total_order='$txttotal' 
                            WHERE no_order='$no_order'");

   echo"<script>window.alert('Data Pesanan Pembelian berhasil disimpan!');
   window.location=('pesanan_pembelian_add_print.php?nopesanan=$no_order');</script>";        
?>