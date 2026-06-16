<div class="col-xs-12 col-sm-12">
    <div class="table-header">
        List Faktur Penjualan
    </div>
    <table class="table table-bordered">
        <thead>
            <tr>
                                                            <td align="center" bgcolor="gainsboro" width="10%"></td>
                                                            <td bgcolor="gainsboro" width="15%"><b>No. Penjualan</b></td>
                                                            <td bgcolor="gainsboro" align="center" width="15%"><b>Tanggal</b></td>
                                                            
                                                            <td bgcolor="gainsboro" align="right" width="15%"><b>Total Netto</b></td>  
                                                            <td bgcolor="gainsboro" align="right" width="15%"><b>Pembayaran</b></td>
                                                            <td bgcolor="gainsboro" align="right" width="15%"><b>Kekurangan</b></td>                                                                
                                                            <td bgcolor="gainsboro" align="right" width="15%"><b>Jumlah Bayar</b></td>                                                                                                                            
            </tr>
        </thead>
        <tbody>
        <?php 
													$sql = mysqli_query($koneksi,"SELECT * FROM tblpiutang_detail 
                                                                                WHERE 
                                                                                no_transaksi='$nobyr'");
													while ($tampil = mysqli_fetch_array($sql)) {
                                                        $no_pembelian=$tampil['no_penjualan'];
                                                        $sudah_bayar=$tampil['jumlah_bayar'];
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        DATE_FORMAT(tanggal,'%d/%m/%Y') AS tanggal_trx, total_akhir, pembayaran 
                                                                                        FROM tblpenjualan_header 
                                                                                        WHERE notransaksi='$no_pembelian'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tanggal=$tm_cari['tanggal_trx'];
                                                        $total_akhir=$tm_cari['total_akhir'];
                                                        $pembayaran=$tm_cari['pembayaran'];
                                                                                                                
                                                        $kekurangan=$total_akhir-$pembayaran-$sudah_bayar;
												?>
													<tr>
                                                        <td class="center">
                                                            <div class="btn-group">
                                                                <button data-toggle="dropdown" class="btn dropdown-toggle btn-minier btn-yellow">
                                                                    Aksi
                                                                    <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-default">
                                                                    <li>
                                                                        <a href="edit_bayar_piutang.php?
                                                                        nobyr=<?php echo $tampil['no_transaksi']; ?>&nobl=<?php echo $tampil['no_penjualan']; ?>&jml=<?php echo $kekurangan; ?>&stgl=<?php echo $tgl_pilih; ?>&ssup=<?php echo $nopelanggan; ?>">Edit Jumlah Bayar</a>
                                                                    </li>
                                                                    <li>
                                                                        <a href="hapus_bayar_piutang.php?
                                                                        nobyr=<?php echo $tampil['no_transaksi']; ?>
                                                                        &nobl=<?php echo $tampil['no_penjualan']; ?>&stgl=<?php echo $tgl_pilih; ?>&ssup=<?php echo $nopelanggan; ?>">Hapus Faktur Penjualan</a>
                                                                    </li>
                                                                </ul>
                                                            </div><!-- /.btn-group -->                                                                                                                
                                                        </td>
														<td><?php echo $tampil['no_penjualan']?></td>														
														<td class="center"><?php echo $tanggal; ?></td>														                                                        
														<td align="right"><?php echo number_format($total_akhir,0)?></td>														                                                        
														<td align="right"><?php echo number_format($pembayaran,0)?></td>
														<td bgcolor="red" align="right"><font color="white"><?php echo number_format($kekurangan,0)?></font></td>														                                                                                                                
                                                        <td align="right"><?php echo number_format($tampil['jumlah_bayar'],0)?></td>
													</tr>


                                        <?php
                                                                    }
                                                            ?>

                                                    <tr>
                                                    
                                                    <tr>
                                                        <td colspan="6" align="right"><b>Total Pembayaran :</b></td>
                                                        <td align="right"><?php echo number_format($tot_bayar,0)?></td>
                                                        
                                                    </tr>
												</tbody>
                                </table>

</div>