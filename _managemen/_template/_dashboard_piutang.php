                                            <h4><b><font color="blue">Total Piutang</font></b></h4>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <td bgcolor="gainsboro" width="60%"><b>Nama Cabang</b></td>
                                                        <td bgcolor="gainsboro" align="right" width="40%"><b>Total Piutang</b></td>
                                                    <tr>
                                                </thead>
                                                <tbody>
												<?php 
                                                    $piutang_keseluruhan=0;                                                    
													$sql = mysqli_query($koneksi,"SELECT * FROM tbcabang");
													while ($tampil = mysqli_fetch_array($sql)) {
                                                        $kode_cabang=$tampil['kode_cabang'];
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_akhir) as tot_jual, 
                                                                                        sum(pembayaran) as tot_bayar 
                                                                                        FROM tblpenjualan_header 
                                                                                        WHERE kd_cabang='$kode_cabang' and 
                                                                                        carabayar='Kredit' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_jualk=$tm_cari['tot_jual'];				                                                                                                                        
                                                        $tot_bayark=$tm_cari['tot_bayar'];				                                                                                                                                                                                
                                                        
                                                        $cari_kd=mysqli_query($koneksi,"SELECT 
                                                                                        sum(total_bayar) as tot 
                                                                                        FROM tblpiutang_header 
                                                                                        WHERE kd_cabang='$kode_cabang' AND 
                                                                                        month(tanggal)='$bulan_skr' AND 
                                                                                        year(tanggal)='$thn_skr'");			
                                                        $tm_cari=mysqli_fetch_array($cari_kd);
                                                        $tot_bayar_piutang=$tm_cari['tot'];	

                                                        $total_piutang=$tot_jualk-($tot_bayark+$tot_bayar_piutang);
                                                        $piutang_keseluruhan=$piutang_keseluruhan+$total_piutang;

                                                    ?>
													<tr>
														<td><?php echo $tampil['nama_cabang']?></td>														
                                                        <td align="right"><?php echo number_format($total_piutang,0)?></td>
                                                    </tr>
                                                    <?php
                                                            }
                                                    ?>
													<tr>
                                                        <td bgcolor="blue" align="right"><b><font color="white">Piutang Keseluruhan Cabang</font></b></td>														                                                        
                                                        <td bgcolor="blue" align="right"><b><font color="white"><?php echo number_format($piutang_keseluruhan,0)?></font></b></td>
                                                    </tr>
                                                </tbody>
                                            </table>