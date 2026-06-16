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
	$txtkd= $_POST['txtkd'];
	$txtsaldo= $_POST['txtsaldo'];
	//$txtnote= $_POST['txtnote'];    
    
//	mysqli_query($koneksi,"INSERT INTO tbsaldo_hutang 
//                            (nosupplier, tanggal, saldo_awal, keterangan) 
//                            VALUES 
//                            ('$txtkd','$txttglpesan','$txtsaldo','$txtnote')");

	mysqli_query($koneksi,"UPDATE tblsupplier 
                            SET saldoawal='$txtsaldo', pertanggal='$txttglpesan' 
                            WHERE nosupplier='$txtkd'");
								
	echo"<script>window.alert('Saldo Awal Hutang Berhasil disimpan!');
    window.location=('supplier.php');</script>";
?>