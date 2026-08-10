<?php
	session_start();
    $id_user=$_SESSION['_iduser'];		#
    $kd_cabang=$_SESSION['_cabang'];        
    
	date_default_timezone_set('Asia/Jakarta');
	$tgl_skr=date('Y/m/d');
    $waktu_skr=date('h:i');
	$nopol= $_GET['snopol'];
    
    include "../config/koneksi.php";
    include "function_servis.php";
    $LastID=FormatNoTrans(OtomatisID());	        
        
    mysqli_query($koneksi,"INSERT INTO tblservice 
                            (no_service, tanggal, jam, 
                            no_pelanggan, no_polisi, 
                            kd_cabang, id_user) 
                            VALUES 
                            ('$LastID','$tgl_skr','$waktu_skr',
                            '$nopol','$nopol',
                            '$kd_cabang','$id_user')");
    echo"<script>window.location=('servis-input-reguler.php?snoserv=$LastID');</script>";        
?>