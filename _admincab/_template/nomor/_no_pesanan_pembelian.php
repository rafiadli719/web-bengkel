<?php
    include "../../../config/koneksi.php";

    $thn_skr=date('Y');
    $thn=substr($thn_skr,2,2);
    
	$querycount="SELECT 
                count(no_order) as LastID 
                FROM 
                tblorder_header 
                WHERE 
                year(tanggal)='$thn_skr'";	
    $result=mysqli_query($koneksi,$querycount) or die(mysql_error());
	$row=mysqli_fetch_array($result);
	$jmlrow=$row['LastID']+1;

    if($jmlrow<10) {
        $no_pesanan_pembelian="PS".$thn."00000000".$jmlrow;    
    } else {
        if($jmlrow<100) {
            $no_pesanan_pembelian="PS".$thn."0000000".$jmlrow;    
        } else {
            if($jmlrow<1000) {
                $no_pesanan_pembelian="PS".$thn."0000000".$jmlrow;    
            } else {
                $no_pesanan_pembelian="PS".$thn."000000".$jmlrow;                    
            }            
        }        
    }
?>