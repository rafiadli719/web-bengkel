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
	
	//$txttglabsen = ubahformatTgl($_POST['id-date-picker-1']); 
	//$cbonip= $_POST['cbonip'];
	$cbostatus= $_POST['cbostatus'];
	$txtmasuk= $_POST['timepicker1'];
	$txtkeluar= $_POST['timepicker2'];
	$txtket= $_POST['txtket'];
	$id= $_POST['txtid'];
	$txttgl= $_POST['txttgl'];
	$cbocabang= $_POST['cbocabang'];
	$txtidupload= $_POST['txtidupload'];
    
    
	mysqli_query($koneksi,"UPDATE tbabsensi 
							SET jam_masuk='$txtmasuk', jam_keluar='$txtkeluar', 
							kode_status_kehadiran='$cbostatus', keterangan='$txtket', 
                            kode_lokasi='$cbocabang' WHERE id='$id'");
	
	$next = 'location:absensi_upload_rst_view_edit.php?kd=';
	$awal = $next.$txtidupload;
	header($awal);

?>