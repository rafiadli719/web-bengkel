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
	    
	$txtkd= strtoupper($_POST['txtkd']);
	$txtnama= strtoupper($_POST['txtnama']);
	$txtalamat= strtoupper($_POST['txtalamat']);    
	$txtkota= strtoupper($_POST['txtkota']);
	$txtprop= strtoupper($_POST['txtprop']);
	$txtnegara= strtoupper($_POST['txtnegara']); 
  
	$txtpos= $_POST['txtpos'];    
	$txttlp= $_POST['txttlp']; 
	$txtfax= $_POST['txtfax'];
	$txtkontak= strtoupper($_POST['txtkontak']);
    $cbolevel= $_POST['cbolevel'];
	$txtnote= $_POST['txtnote']; 

	$txtpanggilan= $_POST['txtpanggilan'];    
	$txtlat= $_POST['txtlat'];    
	$txtlong= $_POST['txtlong'];    
	$txtpatokan= $_POST['txtpatokan'];       
    
	$cbopot= $_POST['cbopot'];    

    $cari_kd=mysqli_query($koneksi,"SELECT panggilan FROM tbpanggilan WHERE id='$txtpanggilan'");			
    $tm_cari=mysqli_fetch_array($cari_kd);
    $txtnmpanggilan=$tm_cari['panggilan'];       
    
	mysqli_query($koneksi,"UPDATE tblpelanggan 
                        SET namapelanggan='$txtnama', 
                        alamat='$txtalamat', kota='$txtkota', propinsi='$txtprop', 
                        kodepost='$txtpos', negara='$txtnegara',
                        telephone='$txttlp', fax='$txtfax', 
                        kontakperson='$txtkontak', note='$txtnote', kgrup='$cbolevel', 
                        patokan='$txtpatokan', klat='$txtlat', 
                        klong='$txtlong', panggilan='$txtnmpanggilan', 
                        tgllahir='$txttglpesan', 
                        tipepot='$cbopot', id_panggilan='$txtpanggilan'  
                        WHERE 
                        nopelanggan='$txtkd'");
								
	echo"<script>window.alert('Data Pelanggan Berhasil disimpan!');
    window.location=('pelanggan.php');</script>";
?>