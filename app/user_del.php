<?php
	include "../config/koneksi.php";
	    
	$id=mysqli_real_escape_string($koneksi, $_GET['kd']);        
	mysqli_query($koneksi,"UPDATE tbuser 
                        SET status_row='1'  
                        WHERE id='$id'");
								
	echo"<script>window.alert('Data User Berhasil dihapus!');
    window.location=('user.php');</script>";
?>