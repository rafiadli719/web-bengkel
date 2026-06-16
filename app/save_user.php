<?php
	include "../config/koneksi.php";
	    
	$txtuser= $_POST['txtuser'];    
	$txtpwd= $_POST['txtpwd'];
    $cbolevel= $_POST['cbolevel'];

    
	mysqli_query($koneksi,"INSERT INTO tbuser 
                        (nama_user, password, foto_user, user_akses) 
                        VALUES 
                        ('$txtuser','$txtpwd','file_upload/avatar.png','$cbolevel')");
								
	echo"<script>window.alert('Data User Berhasil disimpan!');
    window.location=('user.php');</script>";
?>