<?php
 include __DIR__ . '/koneksi.php';
 $con = $koneksi;
 
 // Check connection
 if (mysqli_connect_errno()) {
     error_log("Database connection failed: " . mysqli_connect_error());
 }
?>