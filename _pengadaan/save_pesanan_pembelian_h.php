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
 
    
    include "function_pesanan_pembelian.php";
    $LastID=FormatNoTrans(OtomatisID());	
        
    $stmt = mysqli_prepare($koneksi, "INSERT INTO tblorder_header
                            (no_order, tanggal, no_supplier, user)
                            VALUES
                            (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssss", $LastID, $txttglpesan, $cbosupplier, $txtuser);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo"<script>window.location=('pesanan_pembelian_add_next.php?nopesanan=".rawurlencode($LastID)."');</script>";
?>