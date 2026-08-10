<?php
	include "../config/koneksi.php";
	    
	$txtnopol= $_POST['txtnopol'];    
	$txtnama= $_POST['txtnama'];
    $txtalamat= $_POST['txtalamat'];
    $cbomerek= $_POST['cbomerek'];
    $cbotipe= $_POST['cbotipe'];
    $cbojenis= $_POST['cbojenis'];
    $cbowarna= $_POST['cbowarna'];
    $txtthn_buat= $_POST['txtthn_buat'];
    $txtthn_rakit= $_POST['txtthn_rakit'];
    $txtsilinder= $_POST['txtsilinder'];
    $txtnorangka= $_POST['txtnorangka'];
    $txtnomesin= $_POST['txtnomesin'];
    $txtnote= $_POST['txtnote'];
    
        // Tipe Motor : spt B eat-110, dll
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                            tipe 
                                            FROM tbtipe_motor 
                                            WHERE 
                                            kode_tipe='$cbotipe'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$tipe_motor=$tm_cari['tipe'];		
        // End Tipe Motor

        // Jenis Motor : spt FL, Carbu, dll
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                            jenis 
                                            FROM tbjenis_motor 
                                            WHERE 
                                            kd='$cbojenis'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$jenis_motor=$tm_cari['jenis'];		
        // End Jenis Motor

        // Warna Motor
		$cari_kd=mysqli_query($koneksi,"SELECT 
                                            warna 
                                            FROM tbwarna 
                                            WHERE 
                                            id='$cbowarna'");			
		$tm_cari=mysqli_fetch_array($cari_kd);
		$warna_motor=$tm_cari['warna'];		
        // End Jenis Motor
        
	mysqli_query($koneksi,"UPDATE tblkendaraan 
                        SET pemilik='$txtnama', alamat='$txtalamat', 
                        kode_merek='$cbomerek', kode_tipe='$cbotipe', kode_jenis='$cbojenis', 
                        tahun_buat='$txtthn_buat', tahun_rakit='$txtthn_rakit', silinder='$txtsilinder', 
                        kode_warna='$cbowarna', no_rangka='$txtnorangka', no_mesin='$txtnomesin', 
                        note='$txtnote', 
                        tipe='$tipe_motor', jenis='$jenis_motor', warna='$warna_motor' 
                        WHERE nopolisi='$txtnopol'");
								
	echo"<script>window.alert('Data Kendaraan Berhasil disimpan!');
    window.location=('kendaraan.php');</script>";
?>