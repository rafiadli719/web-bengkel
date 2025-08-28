<?php
	include "../config/koneksi.php";

    $folder="tmp_excel/";
    $nama_file_xl = basename($_FILES['filepegawai']['name']) ;
    $temp = $_FILES['filepegawai']['tmp_name'];
    
    $cbocabang=$_POST['cbocabang'];
	$nama_file_baru = $nama_file_xl;
    move_uploaded_file($temp, $folder . $nama_file_baru);    

	// Load librari PHPExcel nya
	require_once 'PHPExcel/PHPExcel.php';

	$excelreader = new PHPExcel_Reader_Excel2007();
	$loadexcel = $excelreader->load('tmp_excel/'.$nama_file_baru); // Load file excel yang tadi diupload ke folder tmp
	$sheet = $loadexcel->getActiveSheet()->toArray(null, true, true ,true);

    function right($string, $n)
    {
          $balik = strrev($string);
          $hasil = strrev(substr($balik, 0, $n));
          return $hasil;
    }

	$numrow = 2;

    if($cbocabang=='001') {
        // Buat Pasalakan & Cik Di tiro
        include "ambil_tgl/upload-absensi-format1.php";
    }
    if($cbocabang=='002') {
        // Buat Pacul
        include "ambil_tgl/upload-absensi-format2.php";
    }    
    if($cbocabang=='003') {
        // Buat Pasalakan & Cik Di tiro
        include "ambil_tgl/upload-absensi-format1.php";
    }        
?>
