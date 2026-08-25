<?php
    include "../config/koneksi.php";
    
	$no_order= mysqli_real_escape_string($koneksi, $_POST['no_order']);
    $txttotal= mysqli_real_escape_string($koneksi, $_POST['txttotal']); 
    $txtpotfaktur_persen= mysqli_real_escape_string($koneksi, $_POST['txtpotfaktur_persen']);  
    $txtpotfaktur_nom= mysqli_real_escape_string($koneksi, $_POST['txtpotfaktur_nom']);   
    $txtpajak_persen= mysqli_real_escape_string($koneksi, $_POST['txtpajak_persen']);   
    $txtpajak_nom= mysqli_real_escape_string($koneksi, $_POST['txtpajak_nom']);   
    $txtnet= mysqli_real_escape_string($koneksi, $_POST['txtnet']);   
    $txtdp= mysqli_real_escape_string($koneksi, $_POST['txtdp']);   
    $txtkekurangan= mysqli_real_escape_string($koneksi, $_POST['txtkekurangan']);   

    $kekurangan_num = (float)preg_replace('/[^0-9\-\.]/', '', (string)$txtkekurangan);
    $status_lunas = ($kekurangan_num <= 0) ? '1' : '0';
    $tanggal_lunas_val = ($status_lunas === '1') ? date('Y-m-d') : '';

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
    
    // Cek apakah ada link DO pada pembelian ini; jika ada, stok tidak perlu diposting ulang
    $hdr = mysqli_query($koneksi,"SELECT no_do, kd_cabang FROM tblpembelian_header WHERE notransaksi='$no_order'");
    $row_hdr = mysqli_fetch_array($hdr);
    $linked_do = isset($row_hdr['no_do'])     ? trim($row_hdr['no_do'])     : '';
    $kd_cabang = isset($row_hdr['kd_cabang']) ? trim($row_hdr['kd_cabang']) : '';

    $has_kd_cabang_stok = false;
    $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tbstok LIKE 'kd_cabang'");
    if($qcol && mysqli_num_rows($qcol) > 0){ $has_kd_cabang_stok = true; }

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
                            jumlah_bayar='$txtkekurangan',
                            status_lunas='$status_lunas',
                            tanggal_lunas='$tanggal_lunas_val'
                            WHERE
                            notransaksi='$no_order'");

    if($linked_do=='') {
        $sql = mysqli_query($koneksi,"SELECT
                                        no_item, quantity
                                        FROM tblpembelian_detail
                                        WHERE no_transaksi='$no_order'");
        while ($tampil = mysqli_fetch_array($sql)) {
            $no_item=$tampil['no_item'];
            $quantity=$tampil['quantity'];

            if($has_kd_cabang_stok){
                mysqli_query($koneksi,"INSERT INTO tbstok
                                (tipe, no_transaksi, no_item,
                                tanggal, masuk, keluar, keterangan, kd_cabang)
                                VALUES
                                ('2','$no_order','$no_item',
                                '$tanggal_bl','$quantity',
                                '0','Pembelian','$kd_cabang')");
            } else {
                mysqli_query($koneksi,"INSERT INTO tbstok
                                (tipe, no_transaksi, no_item,
                                tanggal, masuk, keluar, keterangan)
                                VALUES
                                ('2','$no_order','$no_item',
                                '$tanggal_bl','$quantity',
                                '0','Pembelian')");
            }
        }
    }

   echo"<script>window.alert('Data Pembelian berhasil disimpan!');
   window.location=('pembelian_add_print.php?nopesanan=$no_order');</script>";        
?>