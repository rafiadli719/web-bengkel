                                            <h4><b><font color="blue">Penjualan & Services</font></b></h4>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <td bgcolor="gainsboro" width="10%"><b>Kode</b></td>
                                                        <td bgcolor="gainsboro" width="30%"><b>Nama Cabang</b></td>
                                                        <td bgcolor="gainsboro" width="15%"><b>Tipe</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="15%"><b>Penjualan</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="15%"><b>Service</b></td>                                                        
                                                        <td bgcolor="gainsboro" align="right" width="15%"><b>Total</b></td>
                                                    <tr>
                                                </thead>
                                                <tbody>
												<?php 
                                                    $total_jual=0;
                                                    $total_service=0;
                                                    $total_penjualan=0;
                                                    
													$sql = mysqli_query($koneksi,"SELECT * FROM tbcabang");
													while ($tampil = mysqli_fetch_array($sql)) {
                                                        $kode_cabang=$tampil['kode_cabang'];
                                                        $tipe_cabang=$tampil['tipe_cabang'];
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        cabang_tipe 
                                                                                        FROM tbcabang_tipe 
                                                                                        WHERE id='$tipe_cabang'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $cabang_tipe=$tm_cari['cabang_tipe'];				                                                                

                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_akhir) as tot_jual, 
                                                                                        sum(pembayaran) as tot_bayar 
                                                                                        FROM tblpenjualan_header 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_jual=$tm_cari['tot_jual'];				                                                                                                                        
                                                        $tot_bayar=$tm_cari['tot_bayar'];				                                                                                                                                                                                
                                                        $total_jual=$total_jual+$tot_jual;
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_grand) as tot_jual 
                                                                                        FROM tblservice 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_service=$tm_cari['tot_jual'];	
                                                        $total_service=$total_service+$tot_service;
                                                    
                                                        $penjualan_total=$tot_jual+$tot_service;
                                                        $total_penjualan=$total_penjualan+$penjualan_total;
                                                    ?>
													<tr>
                                                        <td><?php echo $tampil['kode_cabang']?></td>														
														<td><?php echo $tampil['nama_cabang']?></td>														
														<td><?php echo $cabang_tipe; ?></td>														                                                        
                                                        <td align="right"><?php echo number_format($tot_jual,0)?></td>
                                                        <td align="right"><?php echo number_format($tot_service,0)?></td>
                                                        <td align="right"><?php echo number_format($penjualan_total,0)?></td>                                                        
                                                    </tr>
                                                    <?php
                                                            }
                                                    ?>
													<tr>
                                                        <td bgcolor="blue" colspan="3" align="right"><b><font color="white">Total Penjualan Keseluruhan Cabang &nbsp;</font></b></td>														                                                        
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($total_jual,0)?></font></b></td>
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($total_service,0)?></font></b></td>
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($total_penjualan,0)?></font></b></td>                                                        
                                                    </tr>
                                                </tbody>
                                            </table>