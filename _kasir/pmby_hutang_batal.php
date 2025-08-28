<?php
	include "../config/koneksi.php";

	$nobyr = $_GET['nobyr'];    
	$modal=mysqli_query($koneksi,"Delete FROM tblhutang_detail 
    WHERE no_transaksi='$nobyr'");

    echo"<script>window.location=('pmby_hutang_add.php');</script>";                    								
?>