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
    
	$txttglpesan = ubahformatTgl($_POST['id-date-picker-1']); 	    
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtsaldo= mysqli_real_escape_string($koneksi, $_POST['txtsaldo']);
	//$txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']);    
    
	mysqli_query($koneksi,"UPDATE tblpelanggan 
                            SET saldoawal='$txtsaldo', pertanggal='$txttglpesan' 
                            WHERE nopelanggan='$txtkd'");
								
	echo"<script>window.alert('Saldo Awal Piutang Berhasil disimpan!');
    window.location=('pelanggan.php');</script>";
?>