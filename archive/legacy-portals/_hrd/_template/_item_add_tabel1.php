                                <table class="table table-bordered">
                                    <tbody>
                                    <?php 
                                        $sql = mysqli_query($koneksi,"select 
                                                                    kode_tipe, tipe 
                                                                    FROM tbtipe_motor limit 30");
                                        while ($tampil = mysqli_fetch_array($sql)) {
                                    ?>
                                        <tr>
                                            <td class="center">
                                                <input type="checkbox" name="hapus1[]" 
                                                value="<?php echo $tampil['kode_tipe']; ?>">
                                            </td>
                                            <td><?php echo $tampil['tipe']?></td>																												                                                                                                                
                                        </tr>
                                    <?php
                                        }
                                    ?>
                                    </tbody>
                                </table>