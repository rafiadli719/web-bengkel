<?php
// Template untuk Daftar SPK (Surat Perintah Kerja) - Gabungan Keluhan & Work Order
?>

<div class="widget-box">
    <div class="widget-body">
        <div class="widget-main">
            <h4 class="header blue">
                <i class="ace-icon fa fa-list-alt"></i>
                Daftar SPK (Surat Perintah Kerja)
            </h4>
            
            <!-- Form Input SPK -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-info">
                        <i class="ace-icon fa fa-info-circle"></i>
                        <strong>SPK untuk No. Polisi: <?php echo htmlspecialchars($no_polisi); ?></strong>
                        <br>Tambahkan keluhan dan work order ke dalam satu daftar SPK
                    </div>
                </div>
            </div>
            
            <table class="table table-bordered">
                <tr>
                    <td width="25%">
                        <label><i class="ace-icon fa fa-exclamation-triangle orange"></i> Keluhan :</label>
                        <div class="row">
                            <div class="col-xs-8 col-sm-9">
                                <input type="text" class="form-control input-sm" 
                                id="txtkeluhan" name="txtkeluhan" 
                                placeholder="Masukkan keluhan atau klik cari" 
                                autocomplete="off" />
                            </div>
                            <div class="col-xs-4 col-sm-3">
                                <button type="button" class="btn btn-info btn-sm btn-block" onclick="showModalSearchKeluhan()">
                                    <i class="ace-icon fa fa-search"></i> Cari
                                </button>
                            </div>
                        </div>
                        <div style="margin-top: 5px;">
                            <button class="btn btn-warning btn-sm btn-block" type="submit" 
                            id="btnaddkeluhan" name="btnaddkeluhan">
                                <i class="ace-icon fa fa-plus"></i> + Keluhan ke SPK
                            </button>
                        </div>
                    </td>
                    <td width="25%">
                        <label><i class="ace-icon fa fa-cogs blue"></i> Work Order :</label>
                        <div class="row">
                            <div class="col-xs-8 col-sm-9">
                                <input type="text" class="form-control input-sm" 
                                id="txtcariwo" name="txtcariwo" 
                                value="<?php echo $txtcariwo; ?>" 
                                placeholder="Kode work order" autocomplete="off" />
                            </div>
                            <div class="col-xs-4 col-sm-3">
                                <button class="btn btn-primary btn-sm btn-block" type="submit" 
                                id="btncariwo" name="btncariwo">
                                    <i class="ace-icon fa fa-search"></i> Cari
                                </button>
                            </div>
                        </div>
                        <div style="margin-top: 5px;">
                            <button class="btn btn-success btn-sm btn-block" type="submit" 
                            id="btnaddworkorder" name="btnaddworkorder">
                                <i class="ace-icon fa fa-plus"></i> + WO ke SPK
                            </button>
                        </div>
                    </td>
                    <td width="30%">
                        <label>Nama Work Order :</label>
                        <input type="text" class="form-control input-sm" 
                        value="<?php echo isset($txtnamawo) ? htmlspecialchars($txtnamawo) : ''; ?>" readonly="true" style="background-color: #f5f5f5;" />
                        
                        <label style="margin-top: 10px;">Mekanik Penanggung Jawab :</label>
                        <select name="cbomekanik" id="cbomekanik" class="form-control input-sm">
                            <option value="">- Pilih Mekanik -</option>
                            <?php
                                if(isset($koneksi)) {
                                    try {
                                        $sql="SELECT nomekanik, nama FROM tblmekanik 
                                              WHERE nama<>'-' 
                                              ORDER BY nama ASC";
                                        $sql_row=mysqli_query($koneksi,$sql);
                                        if($sql_row) {
                                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                            ?>
                            <option value="<?php echo htmlspecialchars($sql_res["nomekanik"]); ?>">
                                <?php echo htmlspecialchars($sql_res["nama"]); ?>
                            </option>
                            <?php 
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo '<option value="">Error loading data</option>';
                                    }
                                }
                            ?>
                        </select>
                    </td>
                    <td width="20%">
                        <label>Item Pengerjaan :</label>
                        <input type="text" class="form-control input-sm" 
                        id="txtitempengerjaan" name="txtitempengerjaan" 
                        placeholder="Item pengerjaan" autocomplete="off" />
                        
                        <button class="btn btn-info btn-sm btn-block" type="submit" 
                        id="btnaddpengerjaan" name="btnaddpengerjaan" style="margin-top: 10px;">
                            <i class="ace-icon fa fa-plus"></i> + Pengerjaan
                        </button>
                    </td>
                </tr>
            </table>
            
            <!-- Daftar SPK -->
            <h5 class="header green smaller">
                <i class="ace-icon fa fa-list"></i>
                Daftar SPK untuk No. Polisi: <strong><?php echo htmlspecialchars($no_polisi); ?></strong>
            </h5>
            
            <table class="table table-bordered table-striped">
                <thead>
                    <tr class="info">
                        <th width="5%" class="center">No</th>
                        <th width="15%">Kode/Jenis</th>
                        <th width="35%">Uraian SPK</th>
                        <th width="15%">Penanggung Jawab</th>
                        <th width="15%">Status</th>
                        <th width="10%">Keterangan</th>
                        <th width="5%" class="center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $no_spk = 0;
                        
                        // 1. Tampilkan Keluhan
                        $sql_keluhan = mysqli_query($koneksi,"SELECT 
                                                    id, keluhan, status_pengerjaan, 
                                                    keterangan_tidak_selesai, 'KELUHAN' as jenis
                                                    FROM tbservis_keluhan_status
                                                    WHERE no_service='$no_service'
                                                    ORDER BY id ASC");
                        while ($tampil = mysqli_fetch_array($sql_keluhan)) {
                            $no_spk++;
                            $status_color = '';
                            $status_text = '';
                            
                            switch($tampil['status_pengerjaan']) {
                                case 'datang':
                                    $status_color = 'label-warning';
                                    $status_text = 'Belum Dikerjakan';
                                    break;
                                case 'diproses':
                                    $status_color = 'label-info';
                                    $status_text = 'Sedang Dikerjakan';
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
                        <td class="center"><?php echo $no_spk; ?></td>
                        <td><span class="label label-warning">KELUHAN</span></td>
                        <td><?php echo htmlspecialchars($tampil['keluhan']); ?></td>
                        <td>-</td>
                        <td>
                            <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($tampil['keterangan_tidak_selesai']); ?></td>
                        <td class="center">
                            <a class="red" data-rel="tooltip" title="Hapus SPK" 
                            href="keluhan-hapus.php?keluhan_id=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                            onclick="return confirm('SPK Keluhan akan dihapus. Lanjutkan?')">
                            <i class="ace-icon fa fa-trash-o bigger-130"></i>
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php 
                        // 2. Tampilkan Work Order
                        $sql_wo = mysqli_query($koneksi,"SELECT 
                                                sw.id, sw.kode_wo, sw.status_pengerjaan, 
                                                sw.keterangan_tidak_selesai, wh.nama_wo, 'WORKORDER' as jenis
                                                FROM tbservis_workorder sw
                                                LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                                                WHERE sw.no_service='$no_service'
                                                ORDER BY sw.id ASC");
                        while ($tampil = mysqli_fetch_array($sql_wo)) {
                            $no_spk++;
                            $status_color = '';
                            $status_text = '';
                            
                            switch($tampil['status_pengerjaan']) {
                                case 'diproses':
                                    $status_color = 'label-info';
                                    $status_text = 'Sedang Dikerjakan';
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
                        <td class="center"><?php echo $no_spk; ?></td>
                        <td><span class="label label-primary">WO-<?php echo $tampil['kode_wo']; ?></span></td>
                        <td><?php echo htmlspecialchars($tampil['nama_wo']); ?></td>
                        <td>-</td>
                        <td>
                            <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                        </td>
                        <td><?php echo htmlspecialchars($tampil['keterangan_tidak_selesai']); ?></td>
                        <td class="center">
                            <a class="red" data-rel="tooltip" title="Hapus SPK" 
                            href="workorder-hapus.php?woid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                            onclick="return confirm('SPK Work Order akan dihapus. Lanjutkan?')">
                            <i class="ace-icon fa fa-trash-o bigger-130"></i>
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php 
                        // 3. Tampilkan Pengerjaan Manual
                        $sql_pengerjaan = mysqli_query($koneksi,"SELECT 
                                                    p.id, p.item_pengerjaan, m.nama as nama_mekanik, 'PENGERJAAN' as jenis
                                                    FROM tbservis_pengerjaan p
                                                    LEFT JOIN tblmekanik m ON p.kd_mekanik = m.nomekanik
                                                    WHERE p.no_service='$no_service'
                                                    ORDER BY p.id ASC");
                        while ($tampil = mysqli_fetch_array($sql_pengerjaan)) {
                            $no_spk++;
                    ?>
                    <tr>
                        <td class="center"><?php echo $no_spk; ?></td>
                        <td><span class="label label-info">KERJA</span></td>
                        <td><?php echo htmlspecialchars($tampil['item_pengerjaan']); ?></td>
                        <td><?php echo htmlspecialchars($tampil['nama_mekanik']); ?></td>
                        <td>
                            <span class="label label-success">Dalam Proses</span>
                        </td>
                        <td>-</td>
                        <td class="center">
                            <a class="red" data-rel="tooltip" title="Hapus SPK" 
                            href="pengerjaan-hapus.php?pengerjaan_id=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                            onclick="return confirm('SPK Pengerjaan akan dihapus. Lanjutkan?')">
                            <i class="ace-icon fa fa-trash-o bigger-130"></i>
                            </a>
                        </td>
                    </tr>
                    <?php } ?>
                    
                    <?php if($no_spk == 0) { ?>
                    <tr>
                        <td colspan="7" class="center">
                            <em>Belum ada SPK yang ditambahkan untuk No. Polisi <?php echo htmlspecialchars($no_polisi); ?></em>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            
            <?php if($no_spk > 0) { ?>
            <div class="alert alert-success">
                <i class="ace-icon fa fa-check-circle"></i>
                <strong>Total SPK: <?php echo $no_spk; ?> item untuk No. Polisi <?php echo htmlspecialchars($no_polisi); ?></strong>
            </div>
            <?php } ?>
            
        </div>
    </div>
</div>

<script type="text/javascript">
function showModalSearchKeluhan() {
    // Implementasi modal search keluhan
    if(typeof $('#modal-search-keluhan').modal === 'function') {
        $('#modal-search-keluhan').modal('show');
    } else {
        alert('Modal search keluhan belum tersedia');
    }
}

function refreshSPK() {
    location.reload();
}
</script>