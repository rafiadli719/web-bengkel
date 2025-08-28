                                                        
                        <div class="row">
							<div class="col-xs-12 col-sm-12">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td class="center" width="5%">No</td>
                                            <td width="20%">Kode</td>
                                            <td width="34%">Nama Item</td>
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
                                            <td class="center"><?php echo $no ?></td>
                                            <td><?php echo $tampil['no_item']?></td>														
                                            <td><?php echo $namaitem_tbl; ?></td>														                                                        
                                            <td class="center"><?php echo $tampil['waktu']?></td>														                                                                                                                                                            
                                            <td align="right"><?php echo number_format($tampil['harga'],0)?></td>	
                                            <td align="right"><?php echo number_format($tampil['potongan'],0)?></td>														                                                        													                                                        
                                            <td align="right"><?php echo number_format($tampil['total'],0)?></td>														                                                                                                                
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                        <tr>
                                            <td colspan="6" align="right">Sub Total&nbsp;</td>
                                            <td align="right"><?php echo number_format($total_service,0)?></td>														                                                                                                                
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
