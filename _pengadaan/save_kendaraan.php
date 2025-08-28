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

	$data = mysqli_query($koneksi,"SELECT nopelanggan FROM tblpelanggan 
                                    WHERE nopelanggan='$txtnopol'");
	$cek = mysqli_num_rows($data);
	if($cek > 0){		
        echo"<script>window.alert('No Polisi sudah terdaftar di Data Pelanggan!');
        window.history.back();</script>";                
    } else {
        
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
        
        mysqli_query($koneksi,"INSERT INTO tblkendaraan 
                            (nopolisi, pemilik, alamat, 
                            kode_merek, kode_tipe, kode_jenis, 
                            tahun_buat, tahun_rakit, silinder, 
                            kode_warna, no_rangka, no_mesin, 
                            note,
                            tipe, jenis, warna) 
                            VALUES 
                            ('$txtnopol','$txtnama','$txtalamat',
                            '$cbomerek','$cbotipe','$cbojenis',
                            '$txtthn_buat','$txtthn_rakit','$txtsilinder',
                            '$cbowarna','$txtnorangka','$txtnomesin',
                            '$txtnote',
                            '$tipe_motor','$jenis_motor','$warna_motor')");
                                    
        echo"<script>window.alert('Data Kendaraan Berhasil disimpan!');
        window.location=('kendaraan.php');</script>";        
    }
?>