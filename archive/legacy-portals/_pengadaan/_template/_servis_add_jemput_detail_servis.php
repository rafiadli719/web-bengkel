

                                                        <div class="row">
															<div class="col-xs-8 col-sm-4">
                                                                <label>Service :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <div class="input-group">
                                                                            <input type="text" id="txtcarisrv" name="txtcarisrv" 
                                                                            class="form-control" 
                                                                            value="<?php echo $txtcarisrv; ?>" />
                                                                            <span class="input-group-btn">
                                                                                <button type="submit" class="btn btn-purple btn-sm" id="btncarisrv" name="btncarisrv">
                                                                                    <span class="ace-icon fa fa-search icon-on-right bigger-110"></span>
                                                                                </button>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-5">
                                                                <label>Nama Service :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" id="txtnamasrv" name="txtnamasrv" 
                                                                        class="form-control" 
                                                                        value="<?php echo $txtnamasrv; ?>" disabled />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-2">
                                                                <label>Pot :</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <input type="text" id="txtpotsrv" name="txtpotsrv" 
                                                                        class="form-control" value="0" autocomplete="off" />
                                                                    </div>
                                                                </div>
                                                            </div>
															<div class="col-xs-8 col-sm-1">
                                                                <label>&nbsp;</label>
                                                                <div class="row">
                                                                    <div class="col-xs-8 col-sm-12">
                                                                        <button type="submit" 
                                                                        class="btn btn-sm btn-primary" id="btnaddsrv" name="btnaddsrv">+</button>
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
                                            <td align="right" width="8%">Waktu</td>
                                            <td align="right" width="10%">Harga</td>
                                            <td align="right" width="8%">Pot.</td>
                                            <td align="right" width="15%">Total</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no = 0 ;
                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                        id, no_item, waktu, 
                                                                        harga, total, 
                                                                        potongan FROM tblservis_jasa 
                                                                        WHERE no_service='$no_service'");
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                $no++;
                                                $no_item=$tampil['no_item'];
                                                $cari_kd=mysqli_query($koneksi,"SELECT nama_wo 
                                                                                FROM tbworkorderheader 
                                                                                WHERE kode_wo='$no_item'");			
                                                $tm_cari=mysqli_fetch_array($cari_kd);
                                                $namaitem_tbl=$tm_cari['nama_wo'];				 
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
                                                            <a href="servis_edit_jemput_paket.php?sid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>&kd=<?php echo $txtcarisrv; ?>">Edit Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="servis_hapus_jemput_paket.php?sid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>&kd=<?php echo $txtcarisrv; ?>" onclick="return confirm('Item Paket akan dihapus. Lanjutkan?')">Hapus Item</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                                        
                                            </td>	
                                            <td class="center"><?php echo $no ?></td>
                                            <td><?php echo $tampil['no_item']?></td>														
                                            <td><?php echo $namaitem_tbl; ?></td>														                                                        
                                            <td align="right"><?php echo $tampil['waktu']?></td>														                                                                                                                                                            
                                            <td align="right"><?php echo number_format($tampil['harga'],0)?></td>	
                                            <td align="right"><?php echo $tampil['potongan']?>%</td>														                                                        													                                                        
                                            <td align="right"><?php echo number_format($tampil['total'],0)?></td>														                                                                                                                
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                        <tr>
                                            <td colspan="4" align="right">Total Waktu (menit)&nbsp;</td>
                                            <td align="right"><?php echo $total_waktu; ?></td>                                            
                                            <td colspan="2" align="right">Sub Total&nbsp;</td>
                                            <td align="right"><?php echo number_format($total_service,0)?></td>														                                                                                                                
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
