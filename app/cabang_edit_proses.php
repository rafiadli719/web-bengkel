<?php
	include "../config/koneksi.php";
	    
	$txtid= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);
    $txtalamat= mysqli_real_escape_string($koneksi, $_POST['txtalamat'] ?? '');
    $txtgooglemaps= mysqli_real_escape_string($koneksi, $_POST['txtgooglemaps'] ?? '');
    $txtlat= mysqli_real_escape_string($koneksi, $_POST['txtlat'] ?? '');
    $txtlong= mysqli_real_escape_string($koneksi, $_POST['txtlong'] ?? '');

	mysqli_query($koneksi,"UPDATE tbcabang
                        SET nama_cabang='$txtnama',
                            tipe_cabang='$cbolevel',
                            alamat_cabang='$txtalamat',
                            google_maps_cabang='$txtgooglemaps',
                            lat_cabang='$txtlat',
                            long_cabang='$txtlong'
                        WHERE kode_cabang='$txtid'");
								
	echo"<script>window.alert('Data Cabang Berhasil disimpan!');
    window.location=('cabang.php');</script>";
?>