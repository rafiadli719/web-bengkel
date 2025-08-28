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
	$cbosupplier= $_POST['cbosupplier'];
    $txtuser= $_POST['txtuser']; 
 
    
    include "function_pmby_hutang.php";
    $LastID=FormatNoTrans(OtomatisID());	
        
    mysqli_query($koneksi,"INSERT INTO tblhutang_header 
                            (no_transaksi, tanggal, no_supplier, user) 
                            VALUES 
                            ('$LastID','$txttglpesan','$cbosupplier','$txtuser')");
    echo"<script>window.location=('pmby_hutang_add_next.php?nobyr=$LastID');</script>";        
?>