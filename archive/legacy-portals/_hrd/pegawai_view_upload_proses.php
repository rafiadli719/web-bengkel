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

	$folder="../file_upload/";
	$folder_save="file_upload/";
	
	$txtnip= $_POST['kd'];

	$foto_save="";
	if(!empty($_FILES["id-input-file-3"]["tmp_name"])){
		$temp = $_FILES['id-input-file-3']['tmp_name'];
		$name = basename( $_FILES['id-input-file-3']['name']) ;
		$size = $_FILES['id-input-file-3']['size'];
		$type = $_FILES['id-input-file-3']['type'];
		$foto = $folder.$name;	
			
		move_uploaded_file($temp, $folder . $name);
		$foto_save=$folder_save.$name;
	}

	mysqli_query($koneksi,"UPDATE tbpegawai SET foto_pegawai='$foto_save' WHERE nip='$txtnip'");			
	
	$next = 'location:pegawai_view.php?kd=';
	$awal = $next.$txtnip;
	header($awal);	
?>