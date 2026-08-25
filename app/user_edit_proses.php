<?php
	include "../config/koneksi.php";
	    
	$txtid= mysqli_real_escape_string($koneksi, $_POST['txtid']);    
	$txtuser= mysqli_real_escape_string($koneksi, $_POST['txtuser']);    
	$txtpwd= mysqli_real_escape_string($koneksi, $_POST['txtpwd']);
    $cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);

    
	mysqli_query($koneksi,"UPDATE tbuser 
                        SET nama_user='$txtuser', password='$txtpwd', 
                        user_akses='$cbolevel'  
                        WHERE id='$txtid'");
								
	echo"<script>window.alert('Data User Berhasil disimpan!');
    window.location=('user.php');</script>";
?>