<?php
	include "../config/koneksi.php";

	$nobyr = mysqli_real_escape_string($koneksi, $_GET['nobyr']);
	$nobl = mysqli_real_escape_string($koneksi, $_GET['nobl']);
	$stgl = mysqli_real_escape_string($koneksi, $_GET['stgl']);
	$ssup = mysqli_real_escape_string($koneksi, $_GET['ssup']);
    
	$modal=mysqli_query($koneksi,"Delete FROM tblhutang_detail 
    WHERE no_transaksi='$nobyr' and no_pembelian='$nobl'");

    echo"<script>window.location=('pmby_hutang_add_next.php?nobyr=$nobyr&stgl=$stgl&ssup=$ssup');</script>";                    								
?>