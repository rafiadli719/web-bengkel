<table class="table table-bordered">
            <tr>
                <td width="25%">
                    <label>Kode Item :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-8">
                            <input type="text" class="form-control input-sm" 
                            id="txtcaribrg" name="txtcaribrg" 
                            value="<?php echo $txtcaribrg; ?>" autocomplete="off" />
                        </div>
                        <div class="col-xs-4 col-sm-4">
                            <button class="btn btn-primary btn-sm" type="submit" 
                            id="btncari" name="btncari">
                                Cari
                            </button>                                                
                        </div>
                    </div>
                </td>
                <td width="40%">
                    <label>Nama Item :</label>
                    <input type="text" class="form-control input-sm" 
                    value="<?php echo $txtnamaitem; ?>" readonly="true" />
                </td>
                <td width="15%">
                    <label>Qty :</label>
                    <input type="text" class="form-control input-sm" 
                    id="txtqty" name="txtqty" value="1" autocomplete="off" />
                </td>
                <td width="20%">
                    <label>&nbsp;</label><br>
                    <button class="btn btn-success btn-sm btn-block" type="submit" 
                    id="btnadd" name="btnadd">
                        + Item
                    </button>
                </td>
            </tr>
        </table>

        <table class="table table-bordered table-striped">
            <thead>
                <tr class="info">
                    <th width="5%" class="center">No</th>
                    <th width="15%">Kode</th>
                    <th width="35%">Nama Item</th>
                    <th width="8%" class="center">Qty</th>
                    <th width="12%" class="center">Harga</th>
                    <th width="8%" class="center">Pot %</th>
                    <th width="12%" class="center">Total</th>
                    <th width="5%" class="center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $no = 0;
                    $total_barang_detail = 0;
                    $sql = mysqli_query($koneksi,"SELECT 
                                                    id, no_item, quantity, harga_jual, potongan, total
                                                    FROM tblservis_barang 
                                                    WHERE no_service='$no_service'
                                                    ORDER BY id ASC");
                    while ($tampil = mysqli_fetch_array($sql)) {
                        $no++;
                        $total_barang_detail += $tampil['total'];
                        
                        // Get item name
                        $get_item = mysqli_query($koneksi,"SELECT namaitem FROM view_cari_item WHERE noitem='{$tampil['no_item']}'");
                        $item_data = mysqli_fetch_array($get_item);
                        $nama_item = $item_data['namaitem'] ?? $tampil['no_item'];
                ?>
                <tr>
                    <td class="center"><?php echo $no; ?></td>
                    <td><?php echo $tampil['no_item']; ?></td>
                    <td><?php echo $nama_item; ?></td>
                    <td class="center"><?php echo $tampil['quantity']; ?></td>
                    <td class="right"><?php echo number_format($tampil['harga_jual'], 0, ',', '.'); ?></td>
                    <td class="center"><?php echo $tampil['potongan']; ?>%</td>
                    <td class="right"><?php echo number_format($tampil['total'], 0, ',', '.'); ?></td>
                    <td class="center">
                        <a class="red" data-rel="tooltip" title="Delete" 
                        href="servis-barang-hapus.php?bid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                        onclick="return confirm('Item barang akan dihapus. Lanjutkan?')">
                        <i class="ace-icon fa fa-trash-o bigger-130"></i>
                        </a>
                    </td>
                </tr>
                <?php
                    }
                    if($no == 0) {
                ?>
                <tr>
                    <td colspan="8" class="center"><em>Belum ada barang yang diinput</em></td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr class="info">
                    <th colspan="6" class="center">TOTAL BARANG</th>
                    <th class="right"><?php echo number_format($total_barang_detail, 0, ',', '.'); ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>