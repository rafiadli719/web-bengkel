
                                                        <div class="row">
															<div class="col-xs-8 col-sm-3">
                                                                <label>Kode/Nama Item :</label>
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
                                            <td align="center" width="8%">Diskon</td>
                                            <td align="right" width="15%">Total</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no = 0 ;
                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                        id, no_item, quantity, 
                                                                        harga_jual, total, 
                                                                        potongan,
                                                                        diskon_source, diskon_persen, diskon_nominal, id_promo
                                                                        FROM tblservis_barang 
                                                                        WHERE no_service='$no_service'");
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
                                                            <a href="servis_edit_item_garansi.php?sid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>&kd=<?php echo $txtcaribrg; ?>">Edit Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="servis_hapus_item_garansi.php?sid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>&kd=<?php echo $txtcaribrg; ?>" onclick="return confirm('Item Barang akan dihapus. Lanjutkan?')">Hapus Item</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                                        
                                            </td>	
                                            <td class="center"><?php echo $no ?></td>
                                            <td><?php echo $tampil['no_item']?></td>														
                                            <td><?php echo $namaitem_tbl; ?></td>														                                                        
                                            <td class="center"><?php echo $tampil['quantity']?></td>														                                                                                                                                                            
                                            <td align="right"><?php echo number_format($tampil['harga_jual'],0)?></td>	
                                            <td align="center">
                                                <?php 
                                                // Determine display based on source
                                                $d_source = $tampil['diskon_source'] ?? 'none';
                                                $d_persen = $tampil['diskon_persen'] ?? 0;
                                                $d_nominal = $tampil['diskon_nominal'] ?? 0;
                                                
                                                // Fallback for legacy data (potongan column)
                                                if ($d_source == 'none' && $tampil['potongan'] > 0) {
                                                    $d_source = 'manual';
                                                    $d_persen = $tampil['potongan'];
                                                }
                                                
                                                if ($d_source == 'promo') {
                                                    $tipe_disc = ($d_persen > 0) ? 'persen' : 'nominal';
                                                    $val_disc = ($d_persen > 0) ? $d_persen : $d_nominal;
                                                    echo '<span class="label label-success label-white middle" style="font-size: 10px;">PROMO</span><br>';
                                                    echo ($tipe_disc == 'persen') ? $val_disc.'%' : 'Rp'.number_format($val_disc,0,',','.');
                                                } elseif ($d_source == 'member') {
                                                    echo '<span class="label label-info label-white middle" style="font-size: 10px;">MEMBER</span><br>';
                                                    echo $d_persen.'%';
                                                } elseif ($d_source == 'manual') {
                                                    echo $d_persen.'%';
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>														                                                        													                                                        
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
                        
