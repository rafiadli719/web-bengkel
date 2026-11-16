<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } // kd_cabang dari sesi (Fase K)
	include "../config/koneksi.php";
	
	$nobyr= mysqli_real_escape_string($koneksi, $_POST['txtnobyr']);
	$nobl= mysqli_real_escape_string($koneksi, $_POST['txtnobl']);
	$txtbyr= mysqli_real_escape_string($koneksi, $_POST['txtbyr']);

	$txttgl= mysqli_real_escape_string($koneksi, $_POST['txttgl']);
	$txtsup= mysqli_real_escape_string($koneksi, $_POST['txtsup']);    
    
	mysqli_query($koneksi,"UPDATE tblhutang_detail 
							SET jumlah_bayar='$txtbyr' 
							WHERE 
							no_transaksi='$nobyr' AND kd_cabang='{$_SESSION['_cabang']}' and no_pembelian='$nobl'");
    echo"<script>window.location=('pmby_hutang_add_next.php?nobyr=$nobyr&stgl=$txttgl&ssup=$txtsup');</script>";                    								
?>