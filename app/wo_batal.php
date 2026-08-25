<?php
	include "../config/koneksi.php";

	$skode = mysqli_real_escape_string($koneksi, $_GET['skode']);    
	$modal=mysqli_query($koneksi,"Delete 
                                    FROM tbworkorderdetail 
                                    WHERE 
                                    kode_wo='$skode'");

	echo"<script>window.location=('paket_add.php');</script>";
?>