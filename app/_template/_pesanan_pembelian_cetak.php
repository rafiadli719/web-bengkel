                                                        <?php if (empty($mode_view_only)) { ?>
    <div class="row">
        <div class="col-xs-8 col-sm-3">
            <label>Ketik Kode/Nama Item :</label>
            <div class="row">
                <div class="col-xs-8 col-sm-12">
                    <div class="input-group">
                        <input type="text" id="txtcaribrg" name="txtcaribrg" 
                        class="form-control" 
                        value="" readonly="true" />
                        <span class="input-group-btn">
                            <button type="submit" class="btn disabled btn-purple btn-sm" id="btncari" name="btncari">
                                <span class="ace-icon fa fa-search icon-on-right bigger-110"></span>
                            </button>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-8 col-sm-5">
            <label>Nama Barang :</label>
            <div class="row">
                <div class="col-xs-8 col-sm-12">
                    <input type="text" id="txtnamabrg" name="txtnamabrg" 
                    class="form-control" 
                    value="" disabled />
                </div>
            </div>
        </div>
        <div class="col-xs-8 col-sm-1">
            <label>Jumlah :</label>
            <div class="row">
                <div class="col-xs-8 col-sm-12">
                    <input type="text" id="txtqty" name="txtqty" 
                    class="form-control" value="0" autocomplete="off" readonly="true" />
                </div>
            </div>
        </div>
        <div class="col-xs-8 col-sm-1">
            <label>&nbsp;</label>
            <div class="row">
                <div class="col-xs-8 col-sm-12">
                    <button type="submit" 
                    class="btn disabled btn-sm btn-primary" id="btnadd" name="btnadd">+</button>
                </div>
            </div>
        </div>                                                            
    </div>
    <?php } ?>

    <div class="space space-8"></div>                                                                                                                

    <div class="row">
        <div class="col-xs-12 col-sm-12">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <?php if (empty($mode_view_only)) { ?>
                        <td class="center" width="5%"></td>
                        <?php } ?>
                        <td class="center" width="5%">No</td>
                        <td width="20%">Kode</td>
                        <td width="35%">Nama Item</td>
                        <td align="right" width="8%">Jumlah</td>
                        <td class="center" width="6%">Satuan</td>
                        <td align="right" width="12%">Harga Pokok</td>
                        <td align="right" width="15%">Total</td>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $no = 0 ;
                        $tot_harga = 0;
                        $sql = mysqli_query($koneksi,"SELECT 
                                             id, no_item, quantity, harga_pokok, total 
                                             FROM tblorder_detail 
                                             WHERE no_order='$nopesanan'");
                        while ($tampil = mysqli_fetch_array($sql)) {
                            $no++;
                            $no_item=$tampil['no_item'];
                            $cari_kd=mysqli_query($koneksi,"SELECT namaitem, satuan, hargapokok 
                                                             FROM tblitem 
                                                             WHERE (noitem='$no_item' OR kodebarcode='$no_item')");			
                            $tm_cari=mysqli_fetch_array($cari_kd);
                            $namaitem_tbl=$tm_cari['namaitem'];
                            $harga_pokok = (float)$tampil['harga_pokok'];
                            if ($harga_pokok <= 0) {
                                $harga_pokok = (float)$tm_cari['hargapokok'];
                            }
                            $qty = (int)$tampil['quantity'];
                            $total_row = $harga_pokok * $qty;
                            $tot_harga += $total_row;			 
                    ?>
                    <tr>
                        <?php if (empty($mode_view_only)) { ?>
                        <td class="center">
                            <div class="btn-group">
                                <button data-toggle="dropdown" class="btn disabled dropdown-toggle btn-minier btn-yellow">
                                    Aksi
                                    <span class="ace-icon fa fa-caret-down icon-on-right"></span>
                                </button>
                                <ul class="dropdown-menu dropdown-default">
                                    <li>
                                        <a href="pesanan_pembelian_edit_item.php?kd=<?php echo $tampil['id']; ?>&kdsup=<?php echo $cbosupplier; ?>">Edit Item</a>
                                    </li>
                                    <li>
                                        <a href="pesanan_pembelian_hapus_item.php?kd=<?php echo $tampil['id']; ?>&kdsup=<?php echo $cbosupplier; ?>">Hapus Item</a>
                                    </li>
                                </ul>
                            </div><!-- /.btn-group -->                                                    
                        </td>	
                        <?php } ?>
                        <td class="center"><?php echo $no ?></td>
                        <td><?php echo $tampil['no_item']?></td>												
                        <td><?php echo $namaitem_tbl; ?></td>												
                        <td class="center"><?php echo $qty?></td>												
                        <td class="center"><?php echo isset($tm_cari['satuan']) ? $tm_cari['satuan'] : ''; ?></td>
                        <td align="right"><?php echo number_format($harga_pokok,0)?></td>	
                        <td align="right"><?php echo number_format($total_row,0)?></td>												
                    </tr>
                <?php
                    }
                ?>
                <tr>
                    <td colspan="<?php echo empty($mode_view_only) ? '7' : '6'; ?>" align="right">Sub Total&nbsp;</td>
                    <td align="right"><?php echo number_format($tot_harga,0)?></td>												
                </tr>
                </tbody>
            </table>
        </div>
    </div>
