<table class="table table-bordered">
            <tr>
                <td width="30%">
                    <label>Kode Work Order :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-8">
                            <input type="text" class="form-control input-sm" 
                            id="txtcariwo" name="txtcariwo" 
                            value="<?php echo $txtcariwo; ?>" autocomplete="off" />
                        </div>
                        <div class="col-xs-4 col-sm-4">
                            <button class="btn btn-primary btn-sm" type="submit" 
                            id="btncariwo" name="btncariwo">
                                Cari
                            </button>                                                
                        </div>
                    </div>
                </td>
                <td width="50%">
                    <label>Nama Work Order :</label>
                    <input type="text" class="form-control input-sm" 
                    value="<?php echo $txtnamawo; ?>" readonly="true" />
                </td>
                <td width="20%">
                    <label>&nbsp;</label><br>
                    <button class="btn btn-success btn-sm btn-block" type="submit" 
                    id="btnaddworkorder" name="btnaddworkorder">
                        + Work Order
                    </button>
                </td>
            </tr>
        </table>

        <!-- Daftar Work Order yang sudah dipilih -->
        <table class="table table-bordered table-striped">
            <thead>
                <tr class="info">
                    <th width="5%" class="center">No</th>
                    <th width="15%">Kode WO</th>
                    <th width="35%">Nama Work Order</th>
                    <th width="20%">Status Pengerjaan</th>
                    <th width="20%">Keterangan</th>
                    <th width="5%" class="center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $no = 0;
                    $sql = mysqli_query($koneksi,"SELECT 
                                                    sw.id, sw.kode_wo, sw.status_pengerjaan, 
                                                    sw.keterangan_tidak_selesai, wh.nama_wo
                                                    FROM tbservis_workorder sw
                                                    LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                                                    WHERE sw.no_service='$no_service'
                                                    ORDER BY sw.id ASC");
                    while ($tampil = mysqli_fetch_array($sql)) {
                        $no++;
                        $status_color = '';
                        $status_text = '';
                        
                        switch($tampil['status_pengerjaan']) {
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
                    <td class="center"><?php echo $no; ?></td>
                    <td><?php echo $tampil['kode_wo']; ?></td>
                    <td><?php echo $tampil['nama_wo']; ?></td>
                    <td>
                        <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                        <br><br>
                        <!-- Form untuk update status -->
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>"/>
                            <input type="hidden" name="wo_id" value="<?php echo $tampil['id']; ?>"/>
                            <select name="status_wo" class="form-control input-xs" style="width:100%; margin-bottom:5px;">
                                <option value="diproses" <?php echo ($tampil['status_pengerjaan']=='diproses')?'selected':''; ?>>Di Proses</option>
                                <option value="selesai" <?php echo ($tampil['status_pengerjaan']=='selesai')?'selected':''; ?>>Selesai</option>
                                <option value="tidak_selesai" <?php echo ($tampil['status_pengerjaan']=='tidak_selesai')?'selected':''; ?>>Tidak Selesai</option>
                            </select>
                            <button type="submit" name="btnupdatestatuswo" class="btn btn-xs btn-primary">Update</button>
                        </form>
                    </td>
                    <td>
                        <?php if($tampil['status_pengerjaan'] == 'tidak_selesai') { ?>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>"/>
                                <input type="hidden" name="wo_id" value="<?php echo $tampil['id']; ?>"/>
                                <input type="hidden" name="status_wo" value="tidak_selesai"/>
                                <textarea name="keterangan_wo" class="form-control input-xs" rows="2" 
                                          placeholder="Masukkan keterangan..."><?php echo $tampil['keterangan_tidak_selesai']; ?></textarea>
                                <br>
                                <button type="submit" name="btnupdatestatuswo" class="btn btn-xs btn-warning">Simpan Keterangan</button>
                            </form>
                        <?php } else { ?>
                            <?php echo $tampil['keterangan_tidak_selesai']; ?>
                        <?php } ?>
                    </td>
                    <td class="center">
                        <a class="red" data-rel="tooltip" title="Delete" 
                        href="workorder-hapus.php?woid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                        onclick="return confirm('Work Order akan dihapus. Lanjutkan?')">
                        <i class="ace-icon fa fa-trash-o bigger-130"></i>
                        </a>
                    </td>
                </tr>
                <?php
                    }
                    if($no == 0) {
                ?>
                <tr>
                    <td colspan="6" class="center"><em>Belum ada work order yang dipilih</em></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>