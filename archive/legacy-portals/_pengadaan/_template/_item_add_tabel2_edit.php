                                <table class="table table-bordered">
                                    <tbody>
                                    <?php 
                                        $sql = mysqli_query($koneksi,"select 
                                                                    kode_tipe, tipe 
                                                                    FROM tbtipe_motor limit 30,30");
                                        while ($tampil = mysqli_fetch_array($sql)) {
                                            $kode_tipe=$tampil['kode_tipe'];
                                            $data = mysqli_query($koneksi,"SELECT * FROM tblitem_spart 
                                                                            WHERE 
                                                                            kode_tipe='$kode_tipe' and 
                                                                            noitem='$kdbrg'");
                                            $cek = mysqli_num_rows($data);
                                            if($cek > 0){		
                                                $checked="checked";
                                            } else {
                                                $checked="";                                                
                                            }                                                                                        
                                    ?>
                                        <tr>
                                            <td class="center">
                                                <input type="checkbox" name="hapus2[]" 
                                                value="<?php echo $tampil['kode_tipe']; ?>"
                                                 <?php echo $checked; ?>>
                                            </td>
                                            <td><?php echo $tampil['tipe']?></td>																												                                                                                                                
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                    </tbody>
                                </table>