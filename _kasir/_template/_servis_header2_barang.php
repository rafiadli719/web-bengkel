
                                                <form class="form-horizontal" action="" method="post" role="form">                                            
                                                    <input type="hidden" name="txtnoplg" 
                                                    class="form-control" value="<?php echo $kode_pelanggan; ?>"/>
                                                        <div class="row">
															<div class="col-xs-8 col-sm-3">
                                                                <label>Ketik Kode/Nama Item :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <div class="input-group">
                                                                            <input type="text" id="txtcaribrg" name="txtcaribrg" 
                                                                            class="form-control" 
                                                                            value="<?php echo $txtcaribrg; ?>" />
                                                                            <span class="input-group-btn">
                                                                                <button type="submit" class="btn btn-purple btn-sm" id="btncari" name="btncari">
                                                                                    <span class="ace-icon fa fa-search icon-on-right bigger-110"></span>
                                                                                </button>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-4">
                                                                <label>Nama Barang :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" id="txtnamabrg" name="txtnamabrg" 
                                                                        class="form-control" 
                                                                        value="<?php echo $txtnamaitem; ?>" disabled />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-2">
                                                                <label>Jumlah :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" id="txtqty" name="txtqty" 
                                                                        class="form-control" value="0" autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-2">
                                                                <label>Potongan :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" id="txtpot" name="txtpot" 
                                                                        class="form-control" value="0" autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-1">
                                                                <label>&nbsp;</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <button type="submit" 
                                                                        class="btn btn-sm btn-primary" id="btnadd" name="btnadd">+</button>
                                                                    </div>
                                                                </div>
                                                            </div>                                                            
                                                        </div>

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
                                                                        potongan FROM tblservis_barang 
                                                                        WHERE no_service='$LastID'");
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                $no++;
                                                $no_item=$tampil['no_item'];
                                                $cari_kd=mysqli_query($koneksi,"SELECT namaitem 
                                                                                FROM tblitem 
                                                                                WHERE noitem='$no_item'");			
                                                $tm_cari=mysqli_fetch_array($cari_kd);
                                                $namaitem_tbl=$tm_cari['namaitem'];				 
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
                                                            <a href="penjualan_add_next.php?kd=<?php echo $tampil['id']; ?>&kdsup=<?php echo $cbosupplier; ?>">Pilih</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                                        
                                            </td>	
                                            <td class="center"><?php echo $no ?></td>
                                            <td><?php echo $tampil['no_item']?></td>														
                                            <td><?php echo $namaitem_tbl; ?></td>														                                                        
                                            <td class="center"><?php echo $tampil['quantity']?></td>														                                                                                                                                                            
                                            <td align="right"><?php echo number_format($tampil['harga_jual'],0)?></td>	
                                            <td align="right"><?php echo number_format($tampil['potongan'],0)?></td>														                                                        													                                                        
                                            <td align="right"><?php echo number_format($tampil['total'],0)?></td>														                                                                                                                
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                        <tr>
                                            <td colspan="7" align="right">Sub Total&nbsp;</td>
                                            <td align="right"><?php echo number_format($total_barang,0)?></td>														                                                                                                                
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
