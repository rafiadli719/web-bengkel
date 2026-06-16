<?php
	include "../config/koneksi.php";
	    
	$txtid= $_POST['txtid'];    
	$txtuser= $_POST['txtuser'];    
	$txtpwd= $_POST['txtpwd'];
    $cbolevel= $_POST['cbolevel'];

    
	mysqli_query($koneksi,"UPDATE tbuser 
                        SET nama_user='$txtuser', password='$txtpwd', 
                        user_akses='$cbolevel'  
                        WHERE id='$txtid'");
								
	echo"<script>window.alert('Data User Berhasil disimpan!');
    window.location=('user.php');</script>";
?>