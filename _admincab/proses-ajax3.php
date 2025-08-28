<?php
	//include "../config/koneksi.php";
	$nim = $_GET['nim'];
	//$query = mysqli_query($koneksi, "select nama, lokasi, kode_status_emp from tbpegawai where nip='$nim'");

		
	//$mahasiswa = mysqli_fetch_array($query);
	//$kode_status_emp=$mahasiswa['kode_status_emp'];
		//$cari_kd=mysqli_query($koneksi,"SELECT status FROM tbstatus_emp WHERE kode='$kode_status_emp'");
		//$tm_cari=mysqli_fetch_array($cari_kd);
		//$status_empl=$tm_cari['status'];

$status_empl=nim*100;
		
	$data = array(
				'nama'      =>  $status_empl,);
	echo json_encode($data);
?>
