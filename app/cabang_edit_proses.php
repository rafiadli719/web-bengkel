<?php
	include "../config/koneksi.php";
	    
	$txtid= mysqli_real_escape_string($koneksi, $_POST['txtid']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);
    $txtalamat= mysqli_real_escape_string($koneksi, $_POST['txtalamat'] ?? '');
    $txtgooglemaps= mysqli_real_escape_string($koneksi, $_POST['txtgooglemaps'] ?? '');
    $txtlat= mysqli_real_escape_string($koneksi, $_POST['txtlat'] ?? '');
    $txtlong= mysqli_real_escape_string($koneksi, $_POST['txtlong'] ?? '');
    $txttelepon= mysqli_real_escape_string($koneksi, trim($_POST['txttelepon'] ?? ''));

    // telepon_cabang baru ada setelah migrasi 2026-10-04; tanpa kolom, simpan tetap jalan.
    $cek_tel = mysqli_query($koneksi, "SHOW COLUMNS FROM tbcabang LIKE 'telepon_cabang'");
    if ($cek_tel && mysqli_num_rows($cek_tel) > 0 && isset($_POST['txttelepon'])) {
        mysqli_query($koneksi, "UPDATE tbcabang SET telepon_cabang=" . ($txttelepon === '' ? "NULL" : "'$txttelepon'") . " WHERE kode_cabang='$txtid'");
    }

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