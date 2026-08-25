<?php
	include "../config/koneksi.php";
	    
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
	$txtalamat= mysqli_real_escape_string($koneksi, $_POST['txtalamat']);    
	$txtkota= mysqli_real_escape_string($koneksi, $_POST['txtkota']);
	$txttlp= mysqli_real_escape_string($koneksi, $_POST['txttlp']); 

	$cbokomisi1= mysqli_real_escape_string($koneksi, $_POST['cbokomisi1']); 
	$cbokomisi2= mysqli_real_escape_string($koneksi, $_POST['cbokomisi2']); 
	$txtilai= mysqli_real_escape_string($koneksi, $_POST['txtilai']); 

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