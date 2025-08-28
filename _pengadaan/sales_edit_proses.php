<?php
	include "../config/koneksi.php";
	    
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
	$txtalamat= $_POST['txtalamat'];    
	$txtkota= $_POST['txtkota'];
	$txttlp= $_POST['txttlp']; 

	$cbokomisi1= $_POST['cbokomisi1']; 
	$cbokomisi2= $_POST['cbokomisi2']; 
	$txtilai= $_POST['txtilai']; 

    if($cbokomisi2=='1') {
        mysqli_query($koneksi,"UPDATE tblsales 
                        SET namasales='$txtnama', alamat='$txtalamat', kota='$txtkota', 
                        telephone='$txttlp', 
                        op_pil_hitung='$cbokomisi1', 
                        op_pil_sistem_komisi='$cbokomisi2', 
                        komisijual='$txtilai', komisi_nominal='0'  
                        WHERE 
                        nosales='$txtkd'");
    } else {
        mysqli_query($koneksi,"UPDATE tblsales 
                        SET namasales='$txtnama', alamat='$txtalamat', kota='$txtkota', 
                        telephone='$txttlp', 
                        op_pil_hitung='$cbokomisi1', 
                        op_pil_sistem_komisi='$cbokomisi2', 
                        komisi_nominal='$txtilai', komisijual='0' 
                        WHERE 
                        nosales='$txtkd'");
    }
    

								
	echo"<script>window.alert('Data Sales Berhasil disimpan!');
    window.location=('sales.php');</script>";
?>