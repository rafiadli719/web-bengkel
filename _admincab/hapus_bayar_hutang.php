<?php
	include "../config/koneksi.php";

	$nobyr = $_GET['nobyr'];
	$nobl = $_GET['nobl'];
	$stgl = $_GET['stgl'];
	$ssup = $_GET['ssup'];
    
	$modal=mysqli_query($koneksi,"Delete FROM tblhutang_detail 
    WHERE no_transaksi='$nobyr' and no_pembelian='$nobl'");

    echo"<script>window.location=('pmby_hutang_add_next.php?nobyr=$nobyr&stgl=$stgl&ssup=$ssup');</script>";                    								
?>