<?php
	include "../config/koneksi.php";

	$nobyr = $_GET['nobyr'];
	$nobl = $_GET['nobl'];
	$stgl = $_GET['stgl'];
	$ssup = $_GET['ssup'];
    
	$modal=mysqli_query($koneksi,"Delete FROM tblpiutang_detail 
    WHERE no_transaksi='$nobyr' and no_penjualan='$nobl'");

    echo"<script>window.location=('pmby_piutang_add_next.php?nobyr=$nobyr&stgl=$stgl&ssup=$ssup');</script>";                    								
?>