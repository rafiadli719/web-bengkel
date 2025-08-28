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
	$txttglabsen = $_POST['txttgl'];     
    $cbocabang= $_POST['txtcab'];    
    $txtidupl= $_POST['txtidupl'];        
    
	$cbonip= $_POST['cbonip'];
	$cbostatus= $_POST['cbostatus'];
	$txtmasuk= $_POST['timepicker1'];
	$txtkeluar= $_POST['timepicker2'];
	$txtket= $_POST['txtket'];
	
    if($cbostatus=='1') {
        mysqli_query($koneksi,"INSERT INTO tbabsensi 
                                (nip, tgl, jam_masuk, jam_keluar, kode_status_kehadiran, keterangan, kode_perusahaan, kode_lokasi, id_upload) 
                                VALUES 
                                ('$cbonip','$txttglabsen','$txtmasuk','$txtkeluar','$cbostatus','$txtket','','$cbocabang','$txtidupl')");        
    } else {
        mysqli_query($koneksi,"INSERT INTO tbabsensi 
                                (nip, tgl, jam_masuk, jam_keluar, kode_status_kehadiran, keterangan, kode_perusahaan, kode_lokasi, id_upload) 
                                VALUES 
                                ('$cbonip','$txttglabsen','','','$cbostatus','$txtket','','$cbocabang','$txtidupl')");                
    }

	
    
    
    echo"<script>window.alert('Record has been saved!');
    window.location=('absensi_upload_rst_view_next.php?txtidupload=$txtidupl&cbotgl=$txttglabsen&cbonip=');</script>";
?>