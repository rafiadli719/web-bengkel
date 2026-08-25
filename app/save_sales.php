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
        mysqli_query($koneksi,"INSERT INTO tblsales 
                            (nosales, namasales, alamat, kota, telephone, 
                            op_pil_hitung, op_pil_sistem_komisi, komisijual) 
                            VALUES 
                            ('$txtkd','$txtnama','$txtalamat','$txtkota','$txttlp',
                            '$cbokomisi1','$cbokomisi2','$txtilai')");        
    } else {
        mysqli_query($koneksi,"INSERT INTO tblsales 
                            (nosales, namasales, alamat, kota, telephone, 
                            op_pil_hitung, op_pil_sistem_komisi, komisi_nominal) 
                            VALUES 
                            ('$txtkd','$txtnama','$txtalamat','$txtkota','$txttlp',
                            '$cbokomisi1','$cbokomisi2','$txtilai')");                
    }

								
	echo"<script>window.alert('Data Sales Berhasil disimpan!');
    window.location=('sales.php');</script>";
?>