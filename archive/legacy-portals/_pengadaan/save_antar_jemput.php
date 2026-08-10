<?php
	session_start();
    $id_user=$_SESSION['_iduser'];		#
    $kd_cabang=$_SESSION['_cabang'];        

    include "../config/koneksi.php";
    
    date_default_timezone_set('Asia/Jakarta');
    $waktuaja_skr=date('h:i');
    function ubahformatTgl($tanggal) {
        $pisah = explode('/',$tanggal);
        $urutan = array($pisah[2],$pisah[1],$pisah[0]);
        $satukan = implode('-',$urutan);
        return $satukan;
    }
                
    $txttgljemput = ubahformatTgl($_POST['id-date-picker-1']); 
	$nopol= $_POST['txtnopol'];
	$noserv= $_POST['txtnosrv'];    
	$txtjam= $_POST['txtjam'];        
	$txtket= $_POST['txtket'];    

    mysqli_query($koneksi,"INSERT INTO tblservice 
                            (no_service, tanggal, jam, 
                            no_pelanggan, no_polisi, 
                            kd_cabang, id_user, status_jemput, keterangan) 
                            VALUES 
                            ('$noserv','$txttgljemput','$txtjam',
                            '$nopol','$nopol',
                            '$kd_cabang','$id_user','1','$txtket')");
    echo"<script>window.location=('servis-input-reguler-jemput.php?snoserv=$noserv');</script>";        
?>