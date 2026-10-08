<?php
	include "../config/koneksi.php";

	$nobyr = mysqli_real_escape_string($koneksi, $_GET['nobyr']);
	$nobl = mysqli_real_escape_string($koneksi, $_GET['nobl']);
	$stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
	$ssup = mysqli_real_escape_string($koneksi, $_GET['ssup']);
    
	$modal=mysqli_query($koneksi,"Delete FROM tblpiutang_detail 
    WHERE no_transaksi='$nobyr' and no_penjualan='$nobl' AND NOT EXISTS (SELECT 1 FROM tblpiutang_header h WHERE h.no_transaksi = tblpiutang_detail.no_transaksi)");

    echo"<script>window.location=('pmby_piutang_add_next.php?nobyr=$nobyr&stgl=$stgl&ssup=$ssup');</script>";                    								
?>