<div class="widget-box">
    <div class="widget-body">
        <div class="widget-main">
            <div class="row">
                <div class="col-xs-8 col-sm-6">
                    <label>No. Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                                   value="<?php echo $no_service; ?>" readonly="true" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>                                                                                                                
            <div class="row">
                <div class="col-xs-8 col-sm-6">
                    <label>Km Sekarang :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                                   id="txtkm_skr" name="txtkm_skr" 
                                   value="<?php echo $km_skr; ?>" 
                                   autocomplete="off" />
                        </div>
                    </div>
                </div>
                <div class="col-xs-8 col-sm-6">
                    <label for="id-date-picker-1">Km Berikut :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                                   id="txtkm_next" name="txtkm_next" 
                                   value="<?php echo $km_berikut; ?>" autocomplete="off" />
                        </div>
                    </div>
                </div>
                <div class="space space-8"></div>                                                                                                                 
                <div class="col-xs-8 col-sm-12">
                    <h4 class="header green">Keluhan Pelanggan</h4>
                </div>
                <div class="col-xs-8 col-sm-10">
                    <input type="text" class="form-control" id="txtkeluhan" name="txtkeluhan" 
                           placeholder="Isikan keluhan per item..." autocomplete="off" />
                </div>
                <div class="col-xs-8 col-sm-2">
                    <button class="btn btn-primary btn-block" type="submit" 
                            id="btnaddkeluhan" name="btnaddkeluhan">
                        + Keluhan
                    </button>
                </div>
                <div class="col-xs-8 col-sm-12">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="info">
                                <th width="5%" class="center">No</th>
                                <th width="40%">Keluhan</th>
                                <th width="25%">Status Pengerjaan</th>
                                <th width="25%">Keterangan</th>
                                <th width="5%" class="center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $no = 0 ;
                                $sql = mysqli_query($koneksi,"SELECT 
                                                                id, keluhan, status_pengerjaan, 
                                                                keterangan_tidak_selesai
                                                                FROM 
                                                                tbservis_keluhan_status 
                                                                WHERE 
                                                                no_service='$no_service'
                                                                ORDER BY id ASC");
                                while ($tampil = mysqli_fetch_array($sql)) {
                                    $no++;
                                    $status_color = '';
                                    $status_text = '';
                                    
                                    switch($tampil['status_pengerjaan']) {
                                        case 'datang':
                                            $status_color = 'label-warning';
                                            $status_text = 'Datang';
                                            break;
                                        case 'diproses':
                                            $status_color = 'label-info';
                                            $status_text = 'Di Proses';
                                            break;
                                        case 'selesai':
                                            $status_color = 'label-success';
                                            $status_text = 'Selesai';
                                            break;
                                        case 'tidak_selesai':
                                            $status_color = 'label-danger';
                                            $status_text = 'Tidak Selesai';
                                            break;
                                    }
                            ?>
                            <tr>
                                <td class="center"><?php echo $no ?></td>
                                <td><?php echo $tampil['keluhan']?></td>
                                <td>
                                    <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                                    <br><br>
                                    <!-- Form untuk update status -->
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>"/>
                                        <input type="hidden" name="keluhan_id" value="<?php echo $tampil['id']; ?>"/>
                                        <select name="status_keluhan" class="form-control input-xs" style="width:100%; margin-bottom:5px;">
                                            <option value="datang" <?php echo ($tampil['status_pengerjaan']=='datang')?'selected':''; ?>>Datang</option>
                                            <option value="diproses" <?php echo ($tampil['status_pengerjaan']=='diproses')?'selected':''; ?>>Di Proses</option>
                                            <option value="selesai" <?php echo ($tampil['status_pengerjaan']=='selesai')?'selected':''; ?>>Selesai</option>
                                            <option value="tidak_selesai" <?php echo ($tampil['status_pengerjaan']=='tidak_selesai')?'selected':''; ?>>Tidak Selesai</option>
                                        </select>
                                        <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-xs btn-primary">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <?php if($tampil['status_pengerjaan'] == 'tidak_selesai') { ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>"/>
                                            <input type="hidden" name="keluhan_id" value="<?php echo $tampil['id']; ?>"/>
                                            <input type="hidden" name="status_keluhan" value="tidak_selesai"/>
                                            <textarea name="keterangan_keluhan" class="form-control input-xs" rows="2" 
                                                      placeholder="Contoh: stok barang kosong"><?php echo $tampil['keterangan_tidak_selesai']; ?></textarea>
                                            <br>
                                            <button type="submit" name="btnupdatestatuskeluhan" class="btn btn-xs btn-warning">Simpan Keterangan</button>
                                        </form>
                                    <?php } else { ?>
                                        <?php echo $tampil['keterangan_tidak_selesai']; ?>
                                    <?php } ?>
                                </td>
                                <td class="center">
                                    <a class="red" data-rel="tooltip" title="Delete" 
                                       href="keluhan-hapus.php?kid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                       onclick="return confirm('Keluhan akan dihapus. Lanjutkan?')">
                                        <i class="ace-icon fa fa-trash-o bigger-130"></i>
                                    </a>
                                </td>														
                            </tr>
                            <?php
                                }
                                if($no == 0) {
                            ?>
                            <tr>
                                <td colspan="5" class="center"><em>Belum ada keluhan yang diinput</em></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="col-xs-8 col-sm-12">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <td colspan="3" bgcolor="gainsboro" align="center"><b>History Service</b></td>
                            </tr>
                            <tr>
                                <td width="15%" class="center">No. Service</td>
                                <td width="15%" class="center">Tanggal</td>
                                <td width="70%">Keluhan</td>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $sql = mysqli_query($koneksi,"SELECT 
                                                            no_service, tanggal_trx, status, total_grand 
                                                            FROM view_service 
                                                            WHERE no_polisi='$no_polisi' and 
                                                            status='4' 
                                                            order by tanggal desc
                                                            LIMIT 10");
                                while ($tampil = mysqli_fetch_array($sql)) {
                                    $status=$tampil['status'];
                                    $no_service_history=$tampil['no_service'];

                                    $ket_status="Selesai";
                            ?>
                            <tr>
                                <td class="center"><?php echo $tampil['no_service']?></td>														
                                <td class="center"><?php echo $tampil['tanggal_trx']?></td>														                                                                
                                <td>
                                    <table width="100%">
                                        <?php 
                                            $no1 = 0 ;
                                            $sql1 = mysqli_query($koneksi,"SELECT 
                                                                            keluhan 
                                                                            FROM tbservis_keluhan_status 
                                                                            WHERE no_service='$no_service_history'");
                                            while ($tampil1 = mysqli_fetch_array($sql1)) {
                                                $no1++;
                                        ?> 
                                        <tr valign="top">
                                            <td width="5%"><?php echo $no1; ?>.</td>
                                            <td width="95%"><?php echo $tampil1['keluhan']; ?></td>
                                        </tr>
                                        <?php 
                                            }
                                            if($no1 == 0) {
                                                echo "<tr><td colspan='2'><em>Tidak ada keluhan tercatat</em></td></tr>";
                                            }
                                        ?>
                                    </table>
                                </td>		
                            </tr>
                            <?php
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>