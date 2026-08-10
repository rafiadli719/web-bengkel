<?php
	include "../config/koneksi.php";
	
	$txtid= $_POST['txtid'];
	$txtpwd= $_POST['txtpwd'];
			
	mysqli_query($koneksi,"UPDATE tbuser SET password='$txtpwd' WHERE id='$txtid'");
	echo"<script>window.alert('Password berhasil diubah! Silahkan login kembali');
    window.location=('../index.php');</script>";
?>