<?php
	include "../config/koneksi.php";
	
	$txtnip= $_POST['txtnip'];
	$txtuser= $_POST['txtuser'];
	$txtpwd= $_POST['txtpwd'];
			
	mysqli_query($koneksi,"UPDATE tbpegawai 
                            SET user_name='$txtuser', 
                            password='$txtpwd' 
                            WHERE nip='$txtnip'");
	echo"<script>window.alert('New Password Has Been Changed!');window.location=('pegawai.php');</script>";
?>