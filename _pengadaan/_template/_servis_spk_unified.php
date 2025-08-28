<?php
// Template untuk Daftar SPK (Surat Perintah Kerja) - Keluhan dan Work Order dalam satu halaman
?>

<div class="widget-box">
    <div class="widget-body">
        <div class="widget-main">
            <h4 class="header blue">
                <i class="ace-icon fa fa-list-alt"></i>
                Daftar SPK (Surat Perintah Kerja)
            </h4>
            
            <!-- Header Info -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-info">
                        <i class="ace-icon fa fa-info-circle"></i>
                        <strong>No. Polisi: <?php echo htmlspecialchars($no_polisi); ?></strong>
                        <br>Kelola keluhan dan work order dalam satu halaman SPK
                    </div>
                </div>
            </div>
            
            <!-- Form Input Section -->
            <div class="row">
                <!-- Keluhan Section -->
                <div class="col-xs-12 col-sm-6">
                    <div class="widget-box widget-color-orange">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-exclamation-triangle"></i>
                                Input Keluhan
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="70%">
                                            <label>Keluhan :</label>
                                            <input type="text" class="form-control input-sm" 
                                            id="txtkeluhan" name="txtkeluhan" 
                                            placeholder="Masukkan keluhan" autocomplete="off" />
                                        </td>
                                        <td width="30%">
                                            <label>&nbsp;</label><br>
                                            <button type="button" class="btn btn-info btn-sm btn-block" onclick="showModalSearchKeluhan()">
                                                <i class="ace-icon fa fa-search"></i> Cari
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button class="btn btn-warning btn-sm btn-block" type="submit" 
                                            id="btnaddkeluhan" name="btnaddkeluhan">
                                                <i class="ace-icon fa fa-plus"></i> Tambah Keluhan ke SPK
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Daftar Keluhan -->
                                <h6 class="header orange smaller">Daftar Keluhan</h6>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="warning">
                                            <th width="5%" class="center">No</th>
                                            <th width="65%">Keluhan</th>
                                            <th width="20%">Status</th>
                                            <th width="10%" class="center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no_keluhan = 0;
                                            $sql_keluhan = mysqli_query($koneksi,"SELECT 
                                                                id, keluhan, status_pengerjaan, 
                                                                keterangan_tidak_selesai
                                                                FROM tbservis_keluhan_status
                                                                WHERE no_service='$no_service'
                                                                ORDER BY id ASC");
                                            while ($tampil = mysqli_fetch_array($sql_keluhan)) {
                                                $no_keluhan++;
                                                $status_color = '';
                                                $status_text = '';
                                                
                                                switch($tampil['status_pengerjaan']) {
                                                    case 'datang':
                                                        $status_color = 'label-warning';
                                                        $status_text = 'Baru';
                                                        break;
                                                    case 'diproses':
                                                        $status_color = 'label-info';
                                                        $status_text = 'Proses';
                                                        break;
                                                    case 'selesai':
                                                        $status_color = 'label-success';
                                                        $status_text = 'Selesai';
                                                        break;
                                                    case 'tidak_selesai':
                                                        $status_color = 'label-danger';
                                                        $status_text = 'Gagal';
                                                        break;
                                                }
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no_keluhan; ?></td>
                                            <td><?php echo htmlspecialchars($tampil['keluhan']); ?></td>
                                            <td>
                                                <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <a class="red" data-rel="tooltip" title="Hapus" 
                                                href="keluhan-hapus.php?keluhan_id=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                                onclick="return confirm('Hapus keluhan ini?')">
                                                <i class="ace-icon fa fa-trash-o"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        
                                        <?php if($no_keluhan == 0) { ?>
                                        <tr>
                                            <td colspan="4" class="center">
                                                <em>Belum ada keluhan</em>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Work Order Section -->
                <div class="col-xs-12 col-sm-6">
                    <div class="widget-box widget-color-blue">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-cogs"></i>
                                Input Work Order
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="70%">
                                            <label>Kode Work Order :</label>
                                            <input type="text" class="form-control input-sm" 
                                            id="txtcariwo" name="txtcariwo" 
                                            value="<?php echo $txtcariwo; ?>" 
                                            placeholder="Masukkan kode WO" autocomplete="off" />
                                        </td>
                                        <td width="30%">
                                            <label>&nbsp;</label><br>
                                            <button class="btn btn-primary btn-sm btn-block" type="submit" 
                                            id="btncariwo" name="btncariwo">
                                                <i class="ace-icon fa fa-search"></i> Cari
                                            </button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <label>Nama Work Order :</label>
                                            <input type="text" class="form-control input-sm" 
                                            value="<?php echo isset($txtnamawo) ? htmlspecialchars($txtnamawo) : ''; ?>" 
                                            readonly="true" style="background-color: #f5f5f5;" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <button class="btn btn-success btn-sm btn-block" type="submit" 
                                            id="btnaddworkorder" name="btnaddworkorder">
                                                <i class="ace-icon fa fa-plus"></i> Tambah Work Order ke SPK
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Daftar Work Order -->
                                <h6 class="header blue smaller">Daftar Work Order</h6>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="info">
                                            <th width="5%" class="center">No</th>
                                            <th width="15%">Kode</th>
                                            <th width="50%">Nama WO</th>
                                            <th width="20%">Status</th>
                                            <th width="10%" class="center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no_wo = 0;
                                            $sql_wo = mysqli_query($koneksi,"SELECT 
                                                            sw.id, sw.kode_wo, sw.status_pengerjaan, 
                                                            sw.keterangan_tidak_selesai, wh.nama_wo
                                                            FROM tbservis_workorder sw
                                                            LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                                                            WHERE sw.no_service='$no_service'
                                                            ORDER BY sw.id ASC");
                                            while ($tampil = mysqli_fetch_array($sql_wo)) {
                                                $no_wo++;
                                                $status_color = '';
                                                $status_text = '';
                                                
                                                switch($tampil['status_pengerjaan']) {
                                                    case 'diproses':
                                                        $status_color = 'label-info';
                                                        $status_text = 'Proses';
                                                        break;
                                                    case 'selesai':
                                                        $status_color = 'label-success';
                                                        $status_text = 'Selesai';
                                                        break;
                                                    case 'tidak_selesai':
                                                        $status_color = 'label-danger';
                                                        $status_text = 'Gagal';
                                                        break;
                                                }
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no_wo; ?></td>
                                            <td><?php echo $tampil['kode_wo']; ?></td>
                                            <td><?php echo htmlspecialchars($tampil['nama_wo']); ?></td>
                                            <td>
                                                <span class="label <?php echo $status_color; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td class="center">
                                                <a class="red" data-rel="tooltip" title="Hapus" 
                                                href="workorder-hapus.php?woid=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                                onclick="return confirm('Hapus work order ini?')">
                                                <i class="ace-icon fa fa-trash-o"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        
                                        <?php if($no_wo == 0) { ?>
                                        <tr>
                                            <td colspan="5" class="center">
                                                <em>Belum ada work order</em>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary SPK -->
            <?php 
                $total_spk = $no_keluhan + $no_wo;
                if($total_spk > 0) {
            ?>
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-success">
                        <i class="ace-icon fa fa-check-circle"></i>
                        <strong>Total SPK: <?php echo $total_spk; ?> item</strong>
                        (<?php echo $no_keluhan; ?> keluhan + <?php echo $no_wo; ?> work order)
                        untuk No. Polisi <strong><?php echo htmlspecialchars($no_polisi); ?></strong>
                    </div>
                </div>
            </div>
            <?php } ?>
            
            <!-- Pengerjaan Manual (Opsional) -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="widget-box widget-color-green">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-wrench"></i>
                                Pengerjaan Manual (Opsional)
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main">
                                <table class="table table-bordered">
                                    <tr>
                                        <td width="40%">
                                            <label>Item Pengerjaan :</label>
                                            <input type="text" class="form-control input-sm" 
                                            id="txtitempengerjaan" name="txtitempengerjaan" 
                                            placeholder="Masukkan item pengerjaan" autocomplete="off" />
                                        </td>
                                        <td width="30%">
                                            <label>Mekanik :</label>
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
                                        <td width="30%">
                                            <label>&nbsp;</label><br>
                                            <button class="btn btn-info btn-sm btn-block" type="submit" 
                                            id="btnaddpengerjaan" name="btnaddpengerjaan">
                                                <i class="ace-icon fa fa-plus"></i> Tambah Pengerjaan
                                            </button>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Daftar Pengerjaan -->
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr class="success">
                                            <th width="5%" class="center">No</th>
                                            <th width="60%">Item Pengerjaan</th>
                                            <th width="25%">Mekanik</th>
                                            <th width="10%" class="center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            $no_pengerjaan = 0;
                                            $sql_pengerjaan = mysqli_query($koneksi,"SELECT 
                                                                p.id, p.item_pengerjaan, m.nama as nama_mekanik
                                                                FROM tbservis_pengerjaan p
                                                                LEFT JOIN tblmekanik m ON p.kd_mekanik = m.nomekanik
                                                                WHERE p.no_service='$no_service'
                                                                ORDER BY p.id ASC");
                                            while ($tampil = mysqli_fetch_array($sql_pengerjaan)) {
                                                $no_pengerjaan++;
                                        ?>
                                        <tr>
                                            <td class="center"><?php echo $no_pengerjaan; ?></td>
                                            <td><?php echo htmlspecialchars($tampil['item_pengerjaan']); ?></td>
                                            <td><?php echo htmlspecialchars($tampil['nama_mekanik']); ?></td>
                                            <td class="center">
                                                <a class="red" data-rel="tooltip" title="Hapus" 
                                                href="pengerjaan-hapus.php?pengerjaan_id=<?php echo $tampil['id']; ?>&snoserv=<?php echo $no_service; ?>" 
                                                onclick="return confirm('Hapus pengerjaan ini?')">
                                                <i class="ace-icon fa fa-trash-o"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                        
                                        <?php if($no_pengerjaan == 0) { ?>
                                        <tr>
                                            <td colspan="4" class="center">
                                                <em>Belum ada pengerjaan manual</em>
                                            </td>
                                        </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
</script>