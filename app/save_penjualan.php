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

    $kekurangan_num = (float)preg_replace('/[^0-9\-\.]/', '', (string)$txtkekurangan);
    $status_lunas_jl = ($kekurangan_num <= 0) ? '1' : '0';

    $cari_kd=mysqli_query($koneksi,"SELECT sum(quantity) as tot
                                    FROM tblpenjualan_detail
                                    WHERE no_transaksi='$no_order'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $totqty=$tm_cari['tot'];

    $cari_kd=mysqli_query($koneksi,"SELECT tanggal, kd_cabang, carabayar, total_akhir, no_pelanggan
                                    FROM tblpenjualan_header
                                    WHERE notransaksi='$no_order'");
    $tm_cari=mysqli_fetch_array($cari_kd);
    $tanggal_jl  = $tm_cari['tanggal'];
    $kd_cabang   = $tm_cari['kd_cabang'];
    $carabayar   = $tm_cari['carabayar'];
    $total_akhir = (float)($tm_cari['total_akhir'] ?? 0);
    $no_pelanggan= $tm_cari['no_pelanggan'];

    $has_kd_cabang_stok = false;
    $qcol = mysqli_query($koneksi, "SHOW COLUMNS FROM tbstok LIKE 'kd_cabang'");
    if($qcol && mysqli_num_rows($qcol)>0){ $has_kd_cabang_stok = true; }

    // Baca semua item penjualan dulu
    $items_jual = [];
    $sql = mysqli_query($koneksi,"SELECT no_item, quantity FROM tblpenjualan_detail WHERE no_transaksi='$no_order'");
    while($row = mysqli_fetch_array($sql)){ $items_jual[] = $row; }

    // Cek stok tersedia per item sebelum menyimpan
    $stok_kurang = [];
    foreach($items_jual as $item){
        $ni  = mysqli_real_escape_string($koneksi, $item['no_item']);
        $qty = (int)$item['quantity'];
        if($has_kd_cabang_stok){
            $qs = mysqli_query($koneksi,"SELECT COALESCE(SUM(masuk),0)-COALESCE(SUM(keluar),0) AS stok_now
                                          FROM tbstok WHERE no_item='$ni' AND kd_cabang='$kd_cabang'");
        } else {
            $qs = mysqli_query($koneksi,"SELECT COALESCE(SUM(masuk),0)-COALESCE(SUM(keluar),0) AS stok_now
                                          FROM tbstok WHERE no_item='$ni'");
        }
        $rs = $qs ? mysqli_fetch_array($qs) : null;
        $stok_now = $rs ? (float)$rs['stok_now'] : 0;
        if($stok_now < $qty){
            $stok_kurang[] = "$ni (stok: $stok_now, butuh: $qty)";
        }
    }

    if(!empty($stok_kurang)){
        $pesan = addslashes(implode('\\n', $stok_kurang));
        echo "<script>window.alert('Stok tidak mencukupi:\\n$pesan\\nPenjualan dibatalkan.');
        window.location=('penjualan_add_next_rst.php?nopesanan=$no_order');</script>";
        exit;
    }

    mysqli_query($koneksi,"UPDATE tblpenjualan_header
                            SET
                            total_qty='$totqty',
                            total_jual='$txttotal',
                            diskon='$txtpotfaktur_persen',
                            total_diskon='$txtpotfaktur_nom',
                            pajak='$txtpajak_persen',
                            total_pajak='$txtpajak_nom',
                            total_akhir='$txtnet',
                            pembayaran='$txtdp',
                            jumlah_bayar='$txtkekurangan',
                            status_lunas='$status_lunas_jl'
                            WHERE
                            notransaksi='$no_order'");

    foreach($items_jual as $item){
        $no_item = mysqli_real_escape_string($koneksi, $item['no_item']);
        $quantity = (int)$item['quantity'];
        if($has_kd_cabang_stok){
            mysqli_query($koneksi,"INSERT INTO tbstok
                            (tipe, no_transaksi, no_item,
                            tanggal, masuk, keluar, keterangan, kd_cabang)
                            VALUES
                            ('2','$no_order','$no_item',
                            '$tanggal_jl','0','$quantity',
                            'Penjualan','$kd_cabang')");
        } else {
            mysqli_query($koneksi,"INSERT INTO tbstok
                            (tipe, no_transaksi, no_item,
                            tanggal, masuk, keluar, keterangan)
                            VALUES
                            ('2','$no_order','$no_item',
                            '$tanggal_jl','0','$quantity',
                            'Penjualan')");
        }
    }

    // Auto-create piutang jika penjualan kredit dengan sisa tagihan
    if($carabayar == 'Kredit' && $kekurangan_num > 0 && $no_pelanggan != ''){
        $q_cnt = mysqli_query($koneksi,"SELECT COUNT(no_transaksi) AS cnt FROM tblpiutang_header");
        $r_cnt = mysqli_fetch_array($q_cnt);
        $base  = (int)$r_cnt['cnt'] + 1;
        $thn   = date('y');
        $no_pi = 'PI' . $thn . str_pad($base, 9, '0', STR_PAD_LEFT);

        $no_pel_esc = mysqli_real_escape_string($koneksi, $no_pelanggan);
        $no_ord_esc = mysqli_real_escape_string($koneksi, $no_order);
        $kd_cab_esc = mysqli_real_escape_string($koneksi, $kd_cabang);

        $q_chk = mysqli_query($koneksi,"SELECT no_transaksi FROM tblpiutang_header WHERE no_transaksi='$no_pi'");
        if(!$q_chk || mysqli_num_rows($q_chk) == 0){
            mysqli_query($koneksi,"INSERT INTO tblpiutang_header
                            (no_transaksi, tanggal, no_pelanggan, note, total_bayar, user, id_tabel, kd_cabang)
                            VALUES
                            ('$no_pi','$tanggal_jl','$no_pel_esc',
                            'Piutang Penjualan Kredit','$kekurangan_num',
                            '','$no_ord_esc','$kd_cab_esc')");

            mysqli_query($koneksi,"INSERT INTO tblpiutang_detail
                            (no_transaksi, nobaris, no_penjualan, keterangan, jumlah_piutang, jumlah_bayar, status)
                            VALUES
                            ('$no_pi',1,'$no_ord_esc','Penjualan Kredit','$kekurangan_num','0','outstanding')");
        }
    }

   echo"<script>window.alert('Data Penjualan berhasil disimpan!');
   window.location=('penjualan_add_print.php?nopesanan=$no_order');</script>";
?>
