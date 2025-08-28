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
    $txtsales= $_GET['ssales'];  
    
    include "function_penjualan.php";
    $LastID=FormatNoTrans(OtomatisID());	
        
    mysqli_query($koneksi,"INSERT INTO tblpenjualan_header 
                            (notransaksi, tanggal, no_pelanggan, user, status, no_sales) 
                            VALUES 
                            ('$LastID','$txttglpesan','$kdpel','$txtuser', 
                            'Penjualan','$txtsales')");
    echo"<script>window.location=('penjualan_add_next.php?nojl=$LastID');</script>";        
?>