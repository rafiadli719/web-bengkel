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
                
                $txttglpesan = ubahformatTgl($_POST['id-date-picker-1']);
	    
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
	$txtalamat= mysqli_real_escape_string($koneksi, $_POST['txtalamat']);    
	$txtkota= mysqli_real_escape_string($koneksi, $_POST['txtkota']);
	$txtprop= mysqli_real_escape_string($koneksi, $_POST['txtprop']);
	$txtnegara= mysqli_real_escape_string($koneksi, $_POST['txtnegara']);    
	$txtpos= mysqli_real_escape_string($koneksi, $_POST['txtpos']);    
	$txttlp= mysqli_real_escape_string($koneksi, $_POST['txttlp']); 
	$txtfax= mysqli_real_escape_string($koneksi, $_POST['txtfax']);
	$txtkontak= mysqli_real_escape_string($koneksi, $_POST['txtkontak']);
    $cbolevel= mysqli_real_escape_string($koneksi, $_POST['cbolevel']);
	$txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']); 

	$txtpanggilan= mysqli_real_escape_string($koneksi, $_POST['txtpanggilan']);    
	$txtlat= mysqli_real_escape_string($koneksi, $_POST['txtlat']);    
	$txtlong= mysqli_real_escape_string($koneksi, $_POST['txtlong']);    
	$txtpatokan= mysqli_real_escape_string($koneksi, $_POST['txtpatokan']);       
    
	$cbopot= mysqli_real_escape_string($koneksi, $_POST['cbopot']);           
    
	mysqli_query($koneksi,"UPDATE tblpelanggan 
                        SET namapelanggan='$txtnama', 
                        alamat='$txtalamat', kota='$txtkota', propinsi='$txtprop', 
                        kodepost='$txtpos', negara='$txtnegara',
                        telephone='$txttlp', fax='$txtfax', 
                        kontakperson='$txtkontak', note='$txtnote', kgrup='$cbolevel', 
                        patokan='$txtpatokan', klat='$txtlat', 
                        klong='$txtlong', panggilan='$txtpanggilan', 
                        tgllahir='$txttglpesan', 
                        tipepot='$cbopot' 
                        WHERE 
                        nopelanggan='$txtkd'");
								
	echo"<script>window.alert('Data Pelanggan Berhasil disimpan!');
    window.location=('pelanggan.php');</script>";
?>