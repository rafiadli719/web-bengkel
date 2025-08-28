<?php 
	// Local XAMPP Database Configuration for Testing
	$koneksi = mysqli_connect("localhost","fitmotor_LOGIN","Sayalupa12","fitmotor_dbbengkel");
	if (mysqli_connect_errno()){
		echo "Koneksi database gagal : " . mysqli_connect_error();
	}
?>