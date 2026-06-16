

                                                        <div class="space space-8"></div>                                                                                                                
                                                        
                        <div class="row">
							<div class="col-xs-12 col-sm-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td class="center" width="5%"></td>
                                            <td class="center" width="5%">No</td>
                                            <td width="17%">Kode</td>
                                            <td width="32%">Nama Item</td>
                                            <td align="right" width="8%">Jumlah</td>
                                            <td align="right" width="10%">Harga</td>
                                            <td align="right" width="8%">Pot.</td>
                                            <td align="right" width="15%">Total</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no = 0 ;
                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                        id, no_item, quantity, 
                                                                        harga_jual, total, 
                                                                        potongan FROM tblorderjual_detail 
                                                                        WHERE 
                                                                        no_order='$nopesanan'");
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                $no++;
                                                $no_item=$tampil['no_item'];
                                                $cari_kd=mysqli_query($koneksi,"SELECT namaitem 
                                                                                FROM tblitem 
                                                                                WHERE noitem='$no_item'");			
                                                $tm_cari=mysqli_fetch_array($cari_kd);
                                                $namaitem_tbl=$tm_cari['namaitem'];		
                                                $txtcaribrg="";
                                        ?>
                                        <tr>
                                            <td class="center">
                                                <div class="btn-group">
                                                    <button data-toggle="dropdown" class="btn disabled dropdown-toggle btn-minier btn-yellow">
                                                        Aksi
                                                        <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-default">
                                                        <li>
                                                            <a href="tarik_pesanan_edit_item.php?sid=<?php echo $tampil['id']; ?>&nopesanan=<?php echo $nopesanan; ?>">Edit Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="tarik_pesanan_hapus_item.php?sid=<?php echo $tampil['id']; ?>&nopesanan=<?php echo $nopesanan; ?>" onclick="return confirm('Item Barang akan dihapus. Lanjutkan?')">Hapus Item</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                                        
                                            </td>	
                                            <td class="center"><?php echo $no ?></td>
                                            <td><?php echo $tampil['no_item']?></td>														
                                            <td><?php echo $namaitem_tbl; ?></td>														                                                        
                                            <td class="center"><?php echo $tampil['quantity']?></td>														                                                                                                                
                                            <td align="right"><?php echo number_format($tampil['harga_jual'],0)?></td>														                                                        
                                            <td align="right"><?php echo number_format($tampil['potongan'],0)?>%</td>
                                            <td align="right"><?php echo number_format($tampil['total'],0)?></td>														                                                                                                                
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                        
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
