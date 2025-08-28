                                                <div class="row">
                                                    <div class="col-xs-8 col-sm-2">
                                                        <select class="col-xs-8 col-sm-12" name="cbobulan" id="cbobulan">
                                                        <?php
                                                            $q = mysqli_query($koneksi,"select 
                                                                            bulan, nama, id 
                                                                            FROM bulan_transaksi 
                                                                            order by id asc");
                                                            while ($row1 = mysqli_fetch_array($q)){
                                                                $k_id           = $row1['bulan'];
                                                                $k_opis         = $row1['nama'];
                                                        ?>
                                                        <option value='<?php echo $k_id; ?>' <?php if ($k_id == $bulan_skr){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                        </select>                                                    
                                                    </div>
                                                    <div class="col-xs-8 col-sm-2">   
                                                        <select class="col-xs-8 col-sm-12" name="cbotahun" id="cbotahun">
                                                        <?php
                                                            $q = mysqli_query($koneksi,"SELECT 
                                                                                        distinct(year(tanggal)) as tahun 
                                                                                        FROM view_stok 
                                                                                        order by year(tanggal)");
                                                            while ($row1 = mysqli_fetch_array($q)){
                                                                $k_id           = $row1['tahun'];
                                                                $k_opis         = $row1['tahun'];
                                                        ?>
                                                        <option value='<?php echo $k_id; ?>' <?php if ($k_id == $thn_skr){ echo 'selected'; } ?>><?php echo $k_opis; ?></option>
                                                        <?php
                                                            }
                                                        ?>
                                                        </select>                                                    
                                                    </div>
                                                    <div class="col-xs-8 col-sm-2">   
                                                        <button class="btn btn-sm btn-primary btn-block" type="submit" 
                                                        id="btnrst" name="btnrst">
                                                        Tampilkan
                                                        </button>
                                                    </div>
                                                </div>