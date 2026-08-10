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
    
	$txttgl = ubahformatTgl($_POST['id-date-picker-1']); 
	//$txtcabang= $_POST['txtcabang'];
    //$txtuser= $_POST['txtuser']; 
            
    $txtnobyr= $_POST['txtnobyr'];
    $cboakun= $_POST['cboakun'];
    $txtnote= $_POST['txtnote'];
    $txtjml= $_POST['txtjml'];
    $cboakunbiaya= $_POST['cboakunbiaya'];
    
    mysqli_query($koneksi,"UPDATE tblkas_keluar_masuk 
                        SET tanggal='$txttgl', uraian='$txtnote', masuk='$txtjml', 
                        kode_akun='$cboakun', 
                        kode_akun_biaya='$cboakunbiaya' 
                        WHERE kode_km='$txtnobyr'");
                                                  
    echo"<script>window.alert('Kas Masuk Berhasil disimpan!');window.location=('kas_masuk.php');</script>";        
?>