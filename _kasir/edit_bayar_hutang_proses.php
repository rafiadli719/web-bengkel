<?php
	include "../config/koneksi.php";
	
	$nobyr= $_POST['txtnobyr'];
	$nobl= $_POST['txtnobl'];
	$txtbyr= $_POST['txtbyr'];

	$txttgl= $_POST['txttgl'];
	$txtsup= $_POST['txtsup'];    
    
	mysqli_query($koneksi,"UPDATE tblhutang_detail 
							SET jumlah_bayar='$txtbyr' 
							WHERE 
							no_transaksi='$nobyr' and no_pembelian='$nobl'");
    echo"<script>window.location=('pmby_hutang_add_next.php?nobyr=$nobyr&stgl=$txttgl&ssup=$txtsup');</script>";                    								
?>