<?php
	include "../config/koneksi.php";

    $txttglabsen=date('Y/m/d');    
	$txtnip= $_POST['txtnip'];
	$cbostatus= $_POST['cbostatus'];
	$txtmasuk= $_POST['timepicker1'];
	$txtkeluar= $_POST['timepicker2'];
	$txtket= $_POST['txtket'];
	$cboperusahaan= $_POST['cboperusahaan'];
	
	mysqli_query($koneksi,"INSERT INTO tbabsensi 
							(nip, tgl, jam_masuk, jam_keluar, kode_status_kehadiran, keterangan, kode_perusahaan) 
							VALUES 
							('$txtnip','$txttglabsen','$txtmasuk','$txtkeluar','$cbostatus','$txtket','$cboperusahaan')");
	echo"<script>window.alert('Record has been saved!');window.location=('index.php');</script>";
?>