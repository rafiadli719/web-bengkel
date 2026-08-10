<?php
	include "../config/koneksi.php";
	    
	$txtkd= $_POST['txtkd'];    
	$txtbarcode= $_POST['txtbarcode'];        
	$txtnama= $_POST['txtnama'];
    $cbojenis= $_POST['cbojenis'];
    $cbosatuan= $_POST['cbosatuan'];
    
    $txtqty2= $_POST['txtqty1b'];
    $txtqty3= $_POST['txtqty2a'];    
    $txtqty4= $_POST['txtqty2b'];        
    $txtqty5= $_POST['txtqty3a'];            

    $txthjual1= $_POST['txthj1'];
    $txthjual2= $_POST['txthj2'];
    $txthjual3= $_POST['txthj3'];
    
    $txtnote= $_POST['txtnote'];
    $txtstokmin= $_POST['txtstokmin'];
    $cbosupplier1= $_POST['cbosupplier1'];
    $cbosupplier2= $_POST['cbosupplier2'];
    $cbosupplier3= $_POST['cbosupplier3'];
    $cborak= $_POST['cborak'];
    $txthpokok= $_POST['txthpokok'];
    
    // ------ Baru --------
    $cbostatus= $_POST['cbostatus'];
    $cbotipe= $_POST['cbotipe'];
    $txtstokawal= $_POST['txtstokawal'];
    $txtstokmaks= $_POST['txtstokmaks'];
    $cbopabrik= $_POST['cbopabrik'];
    $cboetalase= $_POST['cboetalase'];

    $cbojasa= $_POST['cbojasa'];
    if($cbojenis=='SERVIS') {
        $cbojasa="";
    }
    
	mysqli_query($koneksi,"UPDATE tblitem 
                        SET kodebarcode='$txtbarcode', namaitem='$txtnama', 
                        jenis='$cbojenis', satuan='$cbosatuan', 
                        hjqtys1='$txtqty2', hjqtyd2='$txtqty3', 
                        hjqtys2='$txtqty4', hjqtyd3='$txtqty5', 
                        hargajual='$txthjual1', hargajual2='$txthjual2', 
                        hargajual3='$txthjual3', 
                        note='$txtnote', 
                        supplier='$cbosupplier1', supplier2='$cbosupplier2', 
                        supplier3='$cbosupplier3', 
                        stokmin='$txtstokmin', rakbarang='$cborak', 
                        hargapokok='$txthpokok', statusproduk='$cbostatus', 
                        statusitem='$cbotipe', 
                        inv_jmlawal='$txtstokawal', 
                        stok_maks='$txtstokmaks', kd_pabrik='$cbopabrik', 
                        kd_etalase='$cboetalase', 
                        jenis_jasa='$cbojasa' 
                        WHERE 
                        noitem='$txtkd'");

	mysqli_query($koneksi,"DELETE FROM tblitem_spart WHERE noitem='$txtkd'");
    
    $jumlah=count($_POST["hapus1"]);
    for($i=0; $i<$jumlah; $i++){
        $nip=$_POST["hapus1"][$i];
        mysqli_query($koneksi,"INSERT INTO tblitem_spart 
                                (noitem, kode_tipe) 
                                VALUES 
                                ('$txtkd','$nip')");
    }                            

    $jumlah=count($_POST["hapus2"]);
    for($i=0; $i<$jumlah; $i++){
        $nip=$_POST["hapus2"][$i];
        mysqli_query($koneksi,"INSERT INTO tblitem_spart 
                                (noitem, kode_tipe) 
                                VALUES 
                                ('$txtkd','$nip')");
    }                            

    $jumlah=count($_POST["hapus3"]);
    for($i=0; $i<$jumlah; $i++){
        $nip=$_POST["hapus3"][$i];
        mysqli_query($koneksi,"INSERT INTO tblitem_spart 
                                (noitem, kode_tipe) 
                                VALUES 
                                ('$txtkd','$nip')");
    }                            

    $jumlah=count($_POST["hapus4"]);
    for($i=0; $i<$jumlah; $i++){
        $nip=$_POST["hapus4"][$i];
        mysqli_query($koneksi,"INSERT INTO tblitem_spart 
                                (noitem, kode_tipe) 
                                VALUES 
                                ('$txtkd','$nip')");
    }                            
								                        
	echo"<script>window.alert('Data Barang Berhasil disimpan!');
    window.location=('barang.php');</script>";
?>