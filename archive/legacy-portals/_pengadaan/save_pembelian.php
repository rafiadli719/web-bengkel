<?php
    include "../config/koneksi.php";
    
	$no_order= $_POST['no_order'];
    $txttotal= $_POST['txttotal']; 
    $txtpotfaktur_persen= $_POST['txtpotfaktur_persen'];  
    $txtpotfaktur_nom= $_POST['txtpotfaktur_nom'];   
    $txtpajak_persen= $_POST['txtpajak_persen'];   
    $txtpajak_nom= $_POST['txtpajak_nom'];   
    $txtnet= $_POST['txtnet'];   
    $txtdp= $_POST['txtdp'];   
    $txtkekurangan= $_POST['txtkekurangan'];   

    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    sum(quantity) as tot 
                                    FROM tblpembelian_detail 
                                    WHERE no_transaksi='$no_order'");
    $tm_cari=mysqli_fetch_array($cari_kd);	
    $totqty=$tm_cari['tot'];

    $cari_kd=mysqli_query($koneksi,"SELECT 
                                    tanggal 
                                    FROM tblpembelian_header 
                                    WHERE notransaksi='$no_order'");
    $tm_cari=mysqli_fetch_array($cari_kd);	
    $tanggal_bl=$tm_cari['tanggal'];
        
    mysqli_query($koneksi,"UPDATE tblpembelian_header 
                            SET 
                            total_qty='$totqty', 
                            total_beli='$txttotal', 
                            diskon='$txtpotfaktur_persen', 
                            total_diskon='$txtpotfaktur_nom', 
                            pajak='$txtpajak_persen', 
                            total_pajak='$txtpajak_nom', 
                            total_akhir='$txtnet', 
                            pembayaran='$txtdp', 
                            jumlah_bayar='$txtkekurangan' 
                            WHERE 
                            notransaksi='$no_order'");

    $sql = mysqli_query($koneksi,"SELECT 
                                    no_item, quantity 
                                    FROM tblpembelian_detail 
                                    WHERE no_transaksi='$no_order'");
    while ($tampil = mysqli_fetch_array($sql)) {
        $no_item=$tampil['no_item'];
        $quantity=$tampil['quantity'];

        mysqli_query($koneksi,"INSERT INTO tbstok 
                        (tipe, no_transaksi, no_item, 
                        tanggal, masuk, keluar, keterangan) 
                        VALUES 
                        ('2','$no_order','$no_item',
                        '$tanggal_bl','$quantity',
                        '0','Pembelian')");
    }
                                                        
   echo"<script>window.alert('Data Pembelian berhasil disimpan!');
   window.location=('pembelian_add_print.php?nopesanan=$no_order');</script>";        
?>