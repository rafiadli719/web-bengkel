<?php
	include "../config/koneksi.php";
	    
	$txtkd= mysqli_real_escape_string($koneksi, $_POST['txtkd']);
	$txtnama= mysqli_real_escape_string($koneksi, $_POST['txtnama']);
	$txtalamat= mysqli_real_escape_string($koneksi, $_POST['txtalamat']);    
	$txtkota= mysqli_real_escape_string($koneksi, $_POST['txtkota']);
	$txtprop= mysqli_real_escape_string($koneksi, $_POST['txtprop']);
	$txtpos= mysqli_real_escape_string($koneksi, $_POST['txtpos']);    
	$txtnegara= mysqli_real_escape_string($koneksi, $_POST['txtnegara']);    
	$txttlp= mysqli_real_escape_string($koneksi, $_POST['txttlp']); 
	$txtfax= mysqli_real_escape_string($koneksi, $_POST['txtfax']);
	$txtbank= mysqli_real_escape_string($koneksi, $_POST['txtbank']);
	$txtnorek= mysqli_real_escape_string($koneksi, $_POST['txtnorek']);
	$txtnmrek= mysqli_real_escape_string($koneksi, $_POST['txtnmrek']);    
	$txtkontak= mysqli_real_escape_string($koneksi, $_POST['txtkontak']);
	$txtemail= mysqli_real_escape_string($koneksi, $_POST['txtemail']);
	$txtnote= mysqli_real_escape_string($koneksi, $_POST['txtnote']);    
	// $cbocabang= mysqli_real_escape_string($koneksi, $_POST['cbocabang']); // Removed cabang field    

// Baru ----------
	$txtlama= mysqli_real_escape_string($koneksi, $_POST['txtlama']);    
	$txtjwkredit= mysqli_real_escape_string($koneksi, $_POST['txtjwkredit']);   
    
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
        $nip=mysqli_real_escape_string($koneksi, $_POST["hapus"][$i]);
        mysqli_query($koneksi,"INSERT INTO tblsupplier_spart 
                                (nosupplier, id_pabrik) 
                                VALUES 
                                ('$txtkd','$nip')");
    }    
								
	echo"<script>window.alert('Data Supplier Berhasil disimpan!');
    window.location=('supplier.php');</script>";
?>