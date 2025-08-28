<table class="table table-bordered">
            <tr>
                <td width="25%">
                    <label>Kode Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-8">
                            <input type="text" class="form-control input-sm" 
                            id="txtcarisrv" name="txtcarisrv" 
                            value="<?php echo $txtcarisrv; ?>" autocomplete="off" />
                        </div>
                        <div class="col-xs-4 col-sm-4">
                            <button class="btn btn-primary btn-sm" type="submit" 
                            id="btncarisrv" name="btncarisrv">
                                Cari
                            </button>                                                
                        </div>
                    </div>
                </td>
                <td width="40%">
                    <label>Nama Service :</label>
                    <input type="text" class="form-control input-sm" 
                    value="<?php echo $txtnamasrv; ?>" readonly="true" />
                </td>
                <td width="15%">
                    <label>Pot. % :</label>
                    <input type="text" class="form-control input-sm" 
                    id="txtpotsrv" name="txtpotsrv" value="0" autocomplete="off" />
                </td>
                <td width="20%">
                    <label>&nbsp;</label><br>
                    <button class="btn btn-success btn-sm btn-block" type="submit" 
                    id="btnaddsrv" name="btnaddsrv">
                        + Service
                    </button>
                </td>
            </tr>
        </table>

        <table class="table table-bordered table-striped">
            <thead>
                <tr class="info">
                    <th width="5%" class="center">No</th>
                    <th width="15%">Kode</th>
                    <th width="35%">Nama Service</th>
                    <th width="10%" class="center">Waktu</th>
                    <th width="12%" class="center">Harga</th>
                    <th width="8%" class="center">Pot %</th>
                    <th width="10%" class="center">Total</th>
                    <th width="5%" class="center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                    $no = 0;
                    $total_service_detail = 0;
                    $total_waktu_detail = 0;
                    $sql = mysqli_query($koneksi,"SELECT 
                                                    id, no_item, waktu, harga, potongan, total
                                                    FROM tblservis_jasa 
                                                    WHERE no_service='$no_service'
                                                    ORDER BY id ASC");
                    while ($tampil = mysqli_fetch_array($sql)) {
                        $no++;
                        $total_service_detail += $tampil['total'];
                        $total_waktu_detail += $tampil['waktu'];
                        
                        // Get service name
                        $get_service = mysqli_query($koneksi,"SELECT nama_wo FROM tbworkorderheader WHERE kode_wo='{$tampil['no_item']}'");
                        $service_data = mysqli_fetch_array($get_service);
                        $nama_service = $service_data['nama_wo'] ?? $tampil['no_item'];
                ?>
                <tr>
                    <td class="center"><?php echo $no; ?></td>
                    <td><?php echo $tampil['no_item']; ?></td>
                    <td><?php echo $nama_service; ?></td>
                    <td class="center"><?php echo $tampil['waktu']; ?> menit</td>
                    <td class="right"><?php echo number_format($tampil['harga'], 0, ',', '.'); ?></td>
                    <td class="center"><?php echo $tampil['potongan']; ?>%</td>
                    <td class="right"><?php echo number_format($tampil['total'], 0, ',', '.'); ?></td>
                    <td class="center">
                        <a class="red" data-rel="tooltip" title="Delete" 
                        href="servis-jasa-hapus.php?jid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                        onclick="return confirm('Item service akan dihapus. Lanjutkan?')">
                        <i class="ace-icon fa fa-trash-o bigger-130"></i>
                        </a>
                    </td>
                </tr>
                <?php
                    }
                    if($no == 0) {
                ?>
                <tr>
                    <td colspan="8" class="center"><em>Belum ada service yang diinput</em></td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr class="info">
                    <th colspan="3" class="center">TOTAL SERVICE</th>
                    <th class="center"><?php echo $total_waktu_detail; ?> menit</th>
                    <th colspan="2"></th>
                    <th class="right"><?php echo number_format($total_service_detail, 0, ',', '.'); ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>