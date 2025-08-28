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