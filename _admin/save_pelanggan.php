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
        



    
	$data = mysqli_query($koneksi,"SELECT telephone FROM tblpelanggan 
                                    WHERE telephone='$txttlp'");
	$cek = mysqli_num_rows($data);
	if($cek > 0){		
        echo"<script>window.alert('No Telephone/Whatsapp sudah terdaftar!');
        window.history.back();</script>";                
    } else { 
        mysqli_query($koneksi,"INSERT INTO tblpelanggan 
                            (nopelanggan, namapelanggan, 
                            alamat, kota, propinsi, kodepost, negara,
                            telephone, fax, kontakperson, note, kgrup, 
                            patokan, klat, klong, panggilan, tgllahir, 
                            tipepot, id_panggilan) 
                            VALUES 
                            ('$txtkd','$txtnama',
                            '$txtalamat','$txtkota','$txtprop','$txtpos','$txtnegara',
                            '$txttlp','$txtfax','$txtkontak','$txtnote','$cbolevel',
                            '$txtpatokan','$txtlat','$txtlong','$txtnmpanggilan',
                            '$txttglpesan','$cbopot','$txtpanggilan')");
                                    
        echo"<script>window.alert('Data Pelanggan Berhasil disimpan!');
        window.location=('pelanggan.php');</script>";
        
    }
?>