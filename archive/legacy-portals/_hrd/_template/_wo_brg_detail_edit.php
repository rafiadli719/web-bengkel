
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <td class="center" width="5%"></td>
                                            <td class="center" width="5%">No</td>
                                            <td width="20%">Kode</td>
                                            <td width="60%">Nama Barang</td>
                                            <td align="right" width="10%">Jumlah</td>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no = 0 ;
                                            $sql = mysqli_query($koneksi,"SELECT 
                                                                        id, kode_barang,jumlah 
                                                                        FROM 
                                                                        tbworkorderdetail 
                                                                        WHERE 
                                                                        kode_wo='$no_wo' and tipe='2'");
                                            while ($tampil = mysqli_fetch_array($sql)) {
                                                $no++;
                                                $no_item=$tampil['kode_barang'];
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
                                                            <a href="wo_edit_brg_detail.php?sid=<?php echo $tampil['id']; ?>&kdwo=<?php echo $no_wo; ?>">Edit Item</a>
                                                        </li>
                                                        <li>
                                                            <a href="wo_hapus_brg_detail.php?sid=<?php echo $tampil['id']; ?>&kdwo=<?php echo $no_wo; ?>" 
                                                            onclick="return confirm('Item Barang/Part akan dihapus. Lanjutkan?')">Hapus Item</a>
                                                        </li>
                                                    </ul>
                                                </div><!-- /.btn-group -->                                                        
                                            </td>	
                                            <td class="center"><?php echo $no ?></td>
                                            <td><?php echo $tampil['kode_barang']?></td>														
                                            <td><?php echo $namaitem_tbl; ?></td>														                                                        
                                            <td class="center"><?php echo $tampil['jumlah']?></td>														                                                                                                                
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                        
                                    </tbody>
                                </table>
                        
