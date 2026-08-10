<!-- import excel ke mysql -->
<!-- www.malasngoding.com -->

<?php 
// menghubungkan dengan koneksi
	include "../config/koneksi.php";
// menghubungkan dengan library excel reader
include "excel_reader2.php";
?>

<?php
	function ubahformatTgl($tanggal) {
		$pisah = explode('/',$tanggal);
		$urutan = array($pisah[2],$pisah[1],$pisah[0]);
		$satukan = implode('-',$urutan);
		return $satukan;
	}
		$txttglabsen = ubahformatTgl($_POST['id-date-picker-1']); 
		$cboperusahaan=$_POST['cboperusahaan'];

	$modal=mysqli_query($koneksi,"Delete FROM tbabsensi_tmp WHERE tgl='$txttglabsen'");
		
// upload file xls
$target = basename($_FILES['filepegawai']['name']) ;
move_uploaded_file($_FILES['filepegawai']['tmp_name'], $target);

// beri permisi agar file xls dapat di baca
chmod($_FILES['filepegawai']['name'],0777);

// mengambil isi file xls
$data = new Spreadsheet_Excel_Reader($_FILES['filepegawai']['name'],false);
// menghitung jumlah baris data yang ada
$jumlah_baris = $data->rowcount($sheet_index=0);

// jumlah default data yang berhasil di import
$berhasil = 0;

$i=2;
$nama     = $data->val($i, 1);
echo $nama;

//for ($i=2; $i<=$jumlah_baris; $i++){

	// menangkap data dan memasukkan ke variabel sesuai dengan kolumnya masing-masing
//	$nama     = $data->val($i, 1);
//	$alamat   = $data->val($i, 2);
//	$telepon  = $data->val($i, 3);
//	$x1  = $data->val($i, 4);
//	$x2  = $data->val($i, 5);
	
//	if($nama != "" && $alamat != "" && $telepon != "" && $x1 != "" ){
		// input data ke database (table data_pegawai)
//		mysqli_query($koneksi,"INSERT into tbabsensi_tmp values('$nama','$txttglabsen','$alamat','$telepon','$x1','$x2','','$cboperusahaan')");
//		$berhasil++;
//	}
//}

// hapus kembali file .xls yang di upload tadi
//unlink($_FILES['filepegawai']['name']);

// alihkan halaman ke index.php
//header("location:absensi_upload_next.php?tgl=$txttglabsen");
?>