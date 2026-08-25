<?php
    include "../config/koneksi.php";
    
	date_default_timezone_set('Asia/Jakarta');
	$waktuaja_skr=date('h:i');
	function ubahformatTgl($tanggal) {
		$pisah = explode('/',$tanggal);
		$urutan = array($pisah[2],$pisah[1],$pisah[0]);
		$satukan = implode('-',$urutan);
		return $satukan;
	}
    
	$txttgl = ubahformatTgl($_POST['id-date-picker-1']); 
	$txtcabang= mysqli_real_escape_string($koneksi, $_POST['txtcabang']);
    $txtuser= mysqli_real_escape_string($koneksi, $_POST['txtuser']); 
            
    $txtnobyr= mysqli_real_escape_string($koneksi, $_POST['txtnobyr']);
    $cboakun= mysqli_real_escape_string($koneksi, $_POST['cboakun']);
    $txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']);
    $txtjml= mysqli_real_escape_string($koneksi, $_POST['txtjml']);
    $cboakunbiaya= mysqli_real_escape_string($koneksi, $_POST['cboakunbiaya']);
    
    mysqli_query($koneksi,"INSERT INTO tblkas_keluar_masuk 
                        (tanggal, kode_km, kd_cabang, user, jenis, 
                        uraian, masuk, kode_akun, kode_akun_biaya) 
                        VALUES 
                        ('$txttgl','$txtnobyr','$txtcabang','$txtuser','Masuk',
                        '$txtnote','$txtjml','$cboakun','$cboakunbiaya')");
                          
    echo"<script>window.alert('Kas Masuk Berhasil disimpan!');window.location=('kas_masuk.php');</script>";        
?>