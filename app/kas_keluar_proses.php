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
	$txtcabang= mysqli_real_escape_string($koneksi, $_POST['txtcabang']);
    $txtuser= mysqli_real_escape_string($koneksi, $_POST['txtuser']); 
            
    $txtnobyr= mysqli_real_escape_string($koneksi, $_POST['txtnobyr']);
    $cboakun= mysqli_real_escape_string($koneksi, $_POST['cboakun']);
    $txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']);
    $txtjml= mysqli_real_escape_string($koneksi, $_POST['txtjml']);
    $cboakunbiaya= mysqli_real_escape_string($koneksi, $_POST['cboakunbiaya']);
    
    // no_bukti, masuk, tipe NOT NULL tanpa default di tblkas_keluar_masuk: wajib diisi eksplisit
    // (dulu INSERT gagal diam-diam di MySQL strict padahal pesan "berhasil" tetap tampil).
    $simpan_ok = mysqli_query($koneksi,"INSERT INTO tblkas_keluar_masuk 
                        (tanggal, kode_km, no_bukti, kd_cabang, user, jenis, 
                        uraian, keluar, masuk, kode_akun, kode_akun_biaya, tipe) 
                        VALUES 
                        ('$txttgl','$txtnobyr','','$txtcabang','$txtuser','Keluar',
                        '$txtnote','$txtjml',0,'$cboakun','$cboakunbiaya','')");
    if(!$simpan_ok) {
        echo "<script>window.alert('Gagal menyimpan kas. Hubungi admin.');window.history.back();</script>";
        exit;
    }
                          
    echo"<script>window.alert('Pengeluaran Kas Berhasil disimpan!');window.location=('kas_keluar.php');</script>";        
?>