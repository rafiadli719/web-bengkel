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
	// $cbocabang= $_POST['cbocabang']; // Removed cabang field    

// Baru ----------
	$txtlama= $_POST['txtlama'];    
	$txtjwkredit= $_POST['txtjwkredit'];   
    
	mysqli_query($koneksi,"UPDATE tblsupplier 
                        SET namasupplier='$txtnama', 
                        alamat='$txtalamat', kota='$txtkota', 
                        propinsi='$txtprop', kodepost='$txtpos', 
                        negara='$txtnegara', telephone='$txttlp', fax='$txtfax', 
                        namabank='$txtbank', noaccount='$txtnorek', atasnama='$txtnmrek', 
                        kontakperson='$txtkontak', email='$txtemail', note='$txtnote',
                        lama_hari_kirim='$txtlama', jangka_waktu_kredit='$txtjwkredit' 
                        WHERE nosupplier='$txtkd'");

	mysqli_query($koneksi,"DELETE FROM tblsupplier_spart WHERE nosupplier='$txtkd'");

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