<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); } // kd_cabang dari sesi (Fase K)
	include "../config/koneksi.php";

	$nobyr = mysqli_real_escape_string($koneksi, $_GET['nobyr']);    
	$modal=mysqli_query($koneksi,"Delete FROM tblpiutang_detail 
    WHERE no_transaksi='$nobyr' AND kd_cabang='{$_SESSION['_cabang']}'");

    echo"<script>window.location=('pmby_piutang_add.php');</script>";                    								
?>