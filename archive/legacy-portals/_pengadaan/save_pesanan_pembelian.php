<?php
    include "../config/koneksi.php";
    
	$no_order= $_POST['no_order'];
    $txttotal= $_POST['txttotal']; 
 
    $stmt = mysqli_prepare($koneksi, "SELECT
                                    sum(quantity) as tot
                                    FROM tblorder_detail
                                    WHERE no_order=?");
    mysqli_stmt_bind_param($stmt, "s", $no_order);
    mysqli_stmt_execute($stmt);
    $cari_kd = mysqli_stmt_get_result($stmt);
    $tm_cari=mysqli_fetch_array($cari_kd);
    $totqty=$tm_cari['tot'];
    mysqli_stmt_close($stmt);

    $stmt2 = mysqli_prepare($koneksi, "UPDATE tblorder_header
                            SET total_qty=?, total_order=?
                            WHERE no_order=?");
    mysqli_stmt_bind_param($stmt2, "sss", $totqty, $txttotal, $no_order);
    mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

   echo"<script>window.alert('Data Pesanan Pembelian berhasil disimpan!');
   window.location=('pesanan_pembelian_add_print.php?nopesanan=".rawurlencode($no_order)."');</script>";
?>