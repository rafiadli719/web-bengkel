<?php
	include "../config/koneksi.php";
	    
	$txtkd= $_POST['txtkd'];
	$txtnama= $_POST['txtnama'];
	$txtalamat= $_POST['txtalamat'];    
	$txtkota= $_POST['txtkota'];
	$txtprop= $_POST['txtprop'];
	$txtpos= $_POST['txtpos'];    
	$txtnegara= $_POST['txtnegara'];    
	$txttlp= $_POST['txttlp']; 
	$txtfax= $_POST['txtfax'];
	$txtbank= $_POST['txtbank'];
	$txtnorek= $_POST['txtnorek'];
	$txtnmrek= $_POST['txtnmrek'];    
	$txtkontak= $_POST['txtkontak'];
	$txtemail= $_POST['txtemail'];
	$txtnote= $_POST['txtnote'];    
	$cbocabang= $_POST['cbocabang'];    

// Baru ----------
	$txtlama= $_POST['txtlama'];    
	$txtjwkredit= $_POST['txtjwkredit'];    
    
	mysqli_query($koneksi,"INSERT INTO tblsupplier 
                        (nosupplier, namasupplier, 
                        alamat, kota, propinsi, kodepost, 
                        negara, telephone, fax, 
                        namabank, noaccount, atasnama, 
                        kontakperson, email, note, kd_cabang, 
                        lama_hari_kirim, jangka_waktu_kredit) 
                        VALUES 
                        ('$txtkd','$txtnama',
                        '$txtalamat','$txtkota','$txtprop','$txtpos',
                        '$txtnegara','$txttlp','$txtfax',
                        '$txtbank','$txtnorek','$txtnmrek',
                        '$txtkontak','$txtemail','$txtnote','$cbocabang',
                        '$txtlama','$txtjwkredit')");
    
    $jumlah=count($_POST["hapus"]);
    for($i=0; $i<$jumlah; $i++){
        $nip=$_POST["hapus"][$i];
        mysqli_query($koneksi,"INSERT INTO tblsupplier_spart 
                                (nosupplier, id_pabrik) 
                                VALUES 
                                ('$txtkd','$nip')");
    }    
								
	echo"<script>window.alert('Data Supplier Berhasil disimpan!');
    window.location=('supplier.php');</script>";
?>