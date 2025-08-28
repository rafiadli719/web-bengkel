<?php
	include "../config/koneksi.php";

		$tgl_skr=date('Y/m/d');
	    
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
    
	mysqli_query($koneksi,"INSERT INTO tblitem 
                        (noitem, kodebarcode, namaitem, 
                        jenis, satuan, 
                        hjqtys1, hjqtyd2, hjqtys2, hjqtyd3, 
                        hargajual, hargajual2, hargajual3, 
                        note, 
                        supplier, supplier2, supplier3, 
                        stokmin, rakbarang, 
                        hargapokok, statusproduk, statusitem, 
                        inv_jmlawal,
                        stok_maks, kd_pabrik, kd_etalase, jenis_jasa) 
                        VALUES 
                        ('$txtkd','$txtbarcode','$txtnama',
                        '$cbojenis','$cbosatuan',
                        '$txtqty2','$txtqty3','$txtqty4','$txtqty5',
                        '$txthjual1','$txthjual2','$txthjual3',
                        '$txtnote',
                        '$cbosupplier1','$cbosupplier2','$cbosupplier3',
                        '$txtstokmin','$cborak','$txthpokok',
                        '$cbostatus','$cbotipe','$txtstokawal',
                        '$txtstokmaks','$cbopabrik','$cboetalase','$cbojasa')");

	mysqli_query($koneksi,"INSERT INTO tbstok 
                        (tipe, no_transaksi, no_item, 
                        tanggal, masuk, keluar, keterangan) 
                        VALUES 
                        ('1','-','$txtkd',
                        '$tgl_skr','$txtstokawal',
                        '0','Stok Awal')");

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