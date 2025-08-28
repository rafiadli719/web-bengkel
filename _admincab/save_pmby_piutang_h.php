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
    
	$txttglpesan = ubahformatTgl($_GET['stgl']); 
	$kdpel= $_GET['kdpel'];
    $txtuser= $_GET['suser']; 
 
    
    include "function_pmby_piutang.php";
    $LastID=FormatNoTrans(OtomatisID());	
        
    mysqli_query($koneksi,"INSERT INTO tblpiutang_header 
                            (no_transaksi, tanggal, no_pelanggan, user) 
                            VALUES 
                            ('$LastID','$txttglpesan','$kdpel','$txtuser')");
    echo"<script>window.location=('pmby_piutang_add_next.php?nobyr=$LastID');</script>";        
?>