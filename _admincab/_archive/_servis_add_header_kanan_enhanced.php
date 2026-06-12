<?php
// Ambil data mekanik yang sudah tersimpan jika ada
$kepala1 = '';
$kepala2 = '';
$persen_kepala1 = 0;
$persen_kepala2 = 0;
$mekanik1_val = '';
$mekanik2_val = '';
$mekanik3_val = '';
$mekanik4_val = '';
$persen1 = '';
$persen2 = '';
$persen3 = '';
$persen4 = '';

if(isset($no_service)) {
    $cari_mek = mysqli_query($koneksi,"SELECT kepala_mekanik1, kepala_mekanik2, 
                                              persen_kepala_mekanik1, persen_kepala_mekanik2,
                                              mekanik1, mekanik2, mekanik3, mekanik4,
                                              persen_mekanik1, persen_mekanik2, 
                                              persen_mekanik3, persen_mekanik4 
                                       FROM tblservice WHERE no_service='$no_service'");
    if($tm_mek = mysqli_fetch_array($cari_mek)) {
        $kepala1 = $tm_mek['kepala_mekanik1'];
        $kepala2 = $tm_mek['kepala_mekanik2'];
        $persen_kepala1 = $tm_mek['persen_kepala_mekanik1'];
        $persen_kepala2 = $tm_mek['persen_kepala_mekanik2'];
        $mekanik1_val = $tm_mek['mekanik1'];
        $mekanik2_val = $tm_mek['mekanik2'];
        $mekanik3_val = $tm_mek['mekanik3'];
        $mekanik4_val = $tm_mek['mekanik4'];
        $persen1 = $tm_mek['persen_mekanik1'];
        $persen2 = $tm_mek['persen_mekanik2'];
        $persen3 = $tm_mek['persen_mekanik3'];
        $persen4 = $tm_mek['persen_mekanik4'];
    }
}
?>

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

            <!-- Section Penanggung Jawab Servis dengan Persentase -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header blue smaller">
                        <i class="ace-icon fa fa-user-md"></i>
                        Penanggung Jawab Servis
                    </h4>
                </div>
                
                <!-- Kepala Mekanik 1 dengan Persentase -->
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 1 <span style="color:red;">*</span>:</label>
                    <select class="form-control" name="cbokepala1" id="cbokepala1" onchange="validateMekanikKepala()">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                            $sql="SELECT nomekanik, nama FROM tblmekanik 
                                  WHERE nama<>'-' AND keahlian='1' 
                                  ORDER BY nama ASC";
                            $sql_row=mysqli_query($koneksi,$sql);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>" 
                                <?php echo ($kepala1==$sql_res["nomekanik"])?'selected':''; ?>>
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala1" id="txtpersen_kepala1" 
                               value="<?php echo $persen_kepala1; ?>" min="0" max="100" 
                               onchange="calculatePercentageKepala()" onkeyup="calculatePercentageKepala()">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Kepala Mekanik 2 dengan Persentase -->
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 2 (Opsional):</label>
                    <select class="form-control" name="cbokepala2" id="cbokepala2" onchange="validateMekanikKepala()">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                            mysqli_data_seek($sql_row, 0);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>"
                                <?php echo ($kepala2==$sql_res["nomekanik"])?'selected':''; ?>>
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala2" id="txtpersen_kepala2" 
                               value="<?php echo $persen_kepala2; ?>" min="0" max="100" 
                               onchange="calculatePercentageKepala()" onkeyup="calculatePercentageKepala()">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Status Persentase Kepala Mekanik -->
                <div class="col-xs-12 col-sm-12">
                    <div class="space space-2"></div>
                    <div id="persentaseStatusKepala" class="alert alert-info">
                        <i class="ace-icon fa fa-info-circle"></i>
                        <strong>Total % Supervisi: <span id="totalPersenKepala">0</span>%</strong>
                        <span id="persenMessageKepala"> - Boleh kurang dari 100%</span>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Admin/Kasir -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header green smaller">
                        <i class="ace-icon fa fa-users"></i>
                        Admin/Kasir
                    </h4>
                    <p><small><em>Minimal 1 admin/kasir harus diisi. Total persentase harus 100%</em></small></p>
                </div>
                
                <!-- Mekanik 1 -->
                <div class="col-xs-6 col-sm-5">
                    <label>Mekanik 1 <span style="color:red;">*</span>:</label>
                    <select class="form-control" name="cbomekanik1" id="cbomekanik1" onchange="validateMekanik()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                            $sql="SELECT nomekanik, nama FROM tblmekanik 
                                  WHERE nama<>'-' ORDER BY nama ASC";
                            $sql_row=mysqli_query($koneksi,$sql);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>"
                                <?php echo ($mekanik1_val==$sql_res["nomekanik"])?'selected':''; ?>>
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-2">
                    <label>% Kerja:</label>
                    <input type="number" class="form-control" name="txtpersen1" id="txtpersen1" 
                           value="<?php echo $persen1; ?>" 
                           min="0" max="100" onchange="calculatePercentage()" onkeyup="calculatePercentage()">
                </div>

                <!-- Mekanik 2 -->
                <div class="col-xs-6 col-sm-5">
                    <label>Mekanik 2:</label>
                    <select class="form-control" name="cbomekanik2" id="cbomekanik2" onchange="validateMekanik()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                            mysqli_data_seek($sql_row, 0);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>"
                                <?php echo ($mekanik2_val==$sql_res["nomekanik"])?'selected':''; ?>>
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-2">
                    <label>% Kerja:</label>
                    <input type="number" class="form-control" name="txtpersen2" id="txtpersen2" 
                           value="<?php echo $persen2; ?>" 
                           min="0" max="100" onchange="calculatePercentage()" onkeyup="calculatePercentage()">
                </div>
            </div>
            
            <div class="row">
                <!-- Mekanik 3 -->
                <div class="col-xs-6 col-sm-5">
                    <label>Mekanik 3:</label>
                    <select class="form-control" name="cbomekanik3" id="cbomekanik3" onchange="validateMekanik()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                            mysqli_data_seek($sql_row, 0);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>"
                                <?php echo ($mekanik3_val==$sql_res["nomekanik"])?'selected':''; ?>>
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-2">
                    <label>% Kerja:</label>
                    <input type="number" class="form-control" name="txtpersen3" id="txtpersen3" 
                           value="<?php echo $persen3; ?>" 
                           min="0" max="100" onchange="calculatePercentage()" onkeyup="calculatePercentage()">
                </div>

                <!-- Mekanik 4 -->
                <div class="col-xs-6 col-sm-5">
                    <label>Mekanik 4:</label>
                    <select class="form-control" name="cbomekanik4" id="cbomekanik4" onchange="validateMekanik()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                            mysqli_data_seek($sql_row, 0);
                            while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo $sql_res["nomekanik"]; ?>"
                                <?php echo ($mekanik4_val==$sql_res["nomekanik"])?'selected':''; ?>>
                            <?php echo $sql_res["nama"]; ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-xs-6 col-sm-2">
                    <label>% Kerja:</label>
                    <input type="number" class="form-control" name="txtpersen4" id="txtpersen4" 
                           value="<?php echo $persen4; ?>" 
                           min="0" max="100" onchange="calculatePercentage()" onkeyup="calculatePercentage()">
                </div>

                <!-- Total Persentase -->
                <div class="col-xs-12 col-sm-12">
                    <div id="persentaseStatus" class="alert alert-warning">
                        <i class="ace-icon fa fa-calculator"></i>
                        <strong>Total Persentase: <span id="totalPersen">0</span>%</strong>
                        <span id="persenMessage"> - Harus 100%!</span>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Km -->
            <div class="row">
                <div class="col-xs-8 col-sm-6">
                    <label>Km Sekarang :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="number" class="form-control" 
                            id="txtkm_skr" name="txtkm_skr" 
                            value="<?php echo $km_skr; ?>" 
                            autocomplete="off" min="0" />
                        </div>
                    </div>
                </div>
                <div class="col-xs-8 col-sm-6">
                    <label>Km Berikut :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="number" class="form-control" 
                            id="txtkm_next" name="txtkm_next" 
                            value="<?php echo $km_berikut; ?>" autocomplete="off" min="0" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Keluhan dengan Search dari Master -->
            <div class="row">
                <div class="col-xs-8 col-sm-12">
                    <h4 class="header orange smaller">
                        <i class="ace-icon fa fa-exclamation-triangle"></i>
                        Keluhan Pelanggan
                    </h4>
                </div>
                <div class="col-xs-8 col-sm-8">
                    <label>Keluhan:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="txtkeluhan" name="txtkeluhan" 
                               placeholder="Ketik keluhan atau pilih dari master..." autocomplete="off" maxlength="255" />
                        <span class="input-group-btn">
                            <button type="button" class="btn btn-info" onclick="showModalSearchKeluhan()">
                                <i class="fa fa-search"></i> Cari
                            </button>
                        </span>
                    </div>
                    <small class="help-block">
                        <i class="fa fa-info-circle"></i> 
                        Ketik manual atau klik "Cari" untuk memilih dari master keluhan
                    </small>
                </div>
                <div class="col-xs-8 col-sm-4">
                    <label>&nbsp;</label>
                    <button class="btn btn-sm btn-primary btn-block" type="submit" 
                    id="btnaddkeluhan" name="btnaddkeluhan">
                        <i class="ace-icon fa fa-plus"></i> Tambah Keluhan
                    </button>
                </div>

                <!-- Tabel Keluhan dengan Status Enhanced -->
                <div class="col-xs-8 col-sm-12">
                    <div class="space space-4"></div>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr class="info">
                                <th width="5%" class="center">No</th>
                                <th width="35%">Keluhan Pelanggan</th>
                                <th width="15%">Status Pengerjaan</th>
                                <th width="15%">Progress</th>
                                <th width="10%">Estimasi</th>
                                <th width="10%">Proses</th>
                                <th width="10%" class="center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $no = 0;
                                $keluhan_table = 'tbservis_keluhan_status';
                                $sql = mysqli_query($koneksi,"SELECT k.*, mk.kode_keluhan, mk.estimasi_waktu, mk.tingkat_prioritas,
                                                             (SELECT COUNT(*) FROM tbkeluhan_proses kp WHERE kp.kode_keluhan = mk.kode_keluhan AND kp.status_aktif='1') as total_proses,
                                                             (SELECT COUNT(*) FROM tbservis_keluhan_tracking kt WHERE kt.keluhan_id = k.id AND kt.status_proses='selesai') as proses_selesai
                                                             FROM $keluhan_table k 
                                                             LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
                                                             WHERE k.no_service='$no_service' ORDER BY k.id ASC");
                                while ($tampil = mysqli_fetch_array($sql)) {
                                    $no++;
                                    $progress = 0;
                                    if($tampil['total_proses'] > 0) {
                                        $progress = round(($tampil['proses_selesai'] / $tampil['total_proses']) * 100);
                                    }
                            ?>
                            <tr>
                                <td class="center"><?php echo $no ?></td>
                                <td>
                                    <?php echo htmlspecialchars($tampil['keluhan'])?>
                                    <?php if($tampil['kode_keluhan']) { ?>
                                        <br><small class="text-muted">
                                            <i class="fa fa-tag"></i> <?php echo $tampil['kode_keluhan']; ?>
                                            <span class="label label-<?php echo $tampil['tingkat_prioritas'] == 'darurat' ? 'danger' : ($tampil['tingkat_prioritas'] == 'tinggi' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($tampil['tingkat_prioritas']); ?>
                                            </span>
                                        </small>
                                    <?php } ?>
                                </td>
                                <td class="center">
                                    <?php 
                                    switch($tampil['status_pengerjaan']) {
                                        case 'datang': echo '<span class="label label-default">Baru</span>'; break;
                                        case 'diproses': echo '<span class="label label-warning">Diproses</span>'; break;
                                        case 'selesai': echo '<span class="label label-success">Selesai</span>'; break;
                                        case 'tidak_selesai': echo '<span class="label label-danger">Tidak Selesai</span>'; break;
                                        default: echo '<span class="label label-default">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($tampil['total_proses'] > 0) { ?>
                                        <div class="progress" style="margin-bottom: 0;">
                                            <div class="progress-bar progress-bar-<?php echo $progress == 100 ? 'success' : ($progress > 50 ? 'warning' : 'info'); ?>" 
                                                 style="width: <?php echo $progress; ?>%">
                                                <?php echo $progress; ?>%
                                            </div>
                                        </div>
                                        <small><?php echo $tampil['proses_selesai']; ?>/<?php echo $tampil['total_proses']; ?> proses</small>
                                    <?php } else { ?>
                                        <span class="text-muted">Manual</span>
                                    <?php } ?>
                                </td>
                                <td class="center">
                                    <?php if($tampil['estimasi_waktu']) { ?>
                                        <i class="fa fa-clock-o"></i> <?php echo $tampil['estimasi_waktu']; ?> min
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                                <td class="center">
                                    <?php if($tampil['kode_keluhan'] && $tampil['total_proses'] > 0) { ?>
                                        <button type="button" class="btn btn-xs btn-info" 
                                                onclick="showProsesDetail(<?php echo $tampil['id']; ?>)" title="Detail Proses">
                                            <i class="fa fa-cogs"></i>
                                        </button>
                                    <?php } else { ?>
                                        <span class="text-muted">-</span>
                                    <?php } ?>
                                </td>
                                <td class="center">
                                    <a class="red" data-rel="tooltip" title="Hapus Keluhan" 
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
                                <td colspan="7" class="center"><em>Belum ada keluhan yang diinput</em></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <!-- History Service -->
                <div class="col-xs-8 col-sm-12">
                    <div class="space space-8"></div>
                    <h5 class="header purple smaller">
                        <i class="ace-icon fa fa-history"></i> Riwayat Service Kendaraan
                    </h5>
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr class="active">
                                <th width="15%" class="center">No. Service</th>
                                <th width="15%" class="center">Tanggal</th>
                                <th width="15%" class="center">Total</th>
                                <th width="55%">Keluhan Sebelumnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                $sql = mysqli_query($koneksi,"SELECT s.no_service, s.tanggal_trx, s.status, s.total_grand
                                                             FROM view_service s
                                                             WHERE s.no_polisi='$no_polisi' AND s.status='4' AND s.no_service!='$no_service'
                                                             ORDER BY s.tanggal DESC LIMIT 5");
                                $count_history = 0;
                                while ($tampil = mysqli_fetch_array($sql)) {
                                    $count_history++;
                                    $no_service_history=$tampil['no_service'];
                            ?>
                            <tr>
                                <td class="center">
                                    <strong><?php echo $tampil['no_service']?></strong>
                                </td>
                                <td class="center"><?php echo $tampil['tanggal_trx']?></td>
                                <td class="center">
                                    <span class="label label-success">
                                        Rp <?php echo number_format($tampil['total_grand'],0,',','.'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                        $no1 = 0;
                                        $keluhan_history_table = 'tbservis_keluhan_status';
                                        $sql1 = mysqli_query($koneksi,"SELECT keluhan FROM $keluhan_history_table 
                                                                      WHERE no_service='$no_service_history' LIMIT 3");
                                        while ($tampil1 = mysqli_fetch_array($sql1)) {
                                            $no1++;
                                            echo "<small>• " . htmlspecialchars($tampil1['keluhan']) . "</small><br>";
                                        }
                                        if($no1 == 0) {
                                            echo "<em><small>Tidak ada keluhan tercatat</small></em>";
                                        }
                                    ?>
                                </td>
                            </tr>
                            <?php } 
                                if($count_history == 0) {
                            ?>
                            <tr>
                                <td colspan="4" class="center">
                                    <em>Tidak ada riwayat service sebelumnya</em>
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

<!-- Include Modal Search Keluhan -->
<?php include "_template/modal-search-keluhan.php"; ?>

<!-- Modal Detail Proses Keluhan -->
<div class="modal fade" id="modal-proses-detail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-cogs"></i> Detail Proses Keluhan</h4>
            </div>
            <div class="modal-body" id="proses-detail-content">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="saveProses()">Simpan</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Function untuk kepala mekanik
function calculatePercentageKepala() {
    var persen_kepala1 = parseInt(document.getElementById('txtpersen_kepala1').value) || 0;
    var persen_kepala2 = parseInt(document.getElementById('txtpersen_kepala2').value) || 0;
    
    var total = persen_kepala1 + persen_kepala2;
    
    document.getElementById('totalPersenKepala').innerHTML = total;
    
    var statusDiv = document.getElementById('persentaseStatusKepala');
    var messageSpan = document.getElementById('persenMessageKepala');
    
    if (total <= 100) {
        statusDiv.className = 'alert alert-info';
        messageSpan.innerHTML = ' - Total supervisi: ' + total + '%';
        messageSpan.style.color = 'blue';
    } else {
        statusDiv.className = 'alert alert-warning';
        messageSpan.innerHTML = ' - Total melebihi 100%!';
        messageSpan.style.color = 'orange';
    }
}

// Function untuk mekanik pengerjaan
function calculatePercentage() {
    var persen1 = parseInt(document.getElementById('txtpersen1').value) || 0;
    var persen2 = parseInt(document.getElementById('txtpersen2').value) || 0;
    var persen3 = parseInt(document.getElementById('txtpersen3').value) || 0;
    var persen4 = parseInt(document.getElementById('txtpersen4').value) || 0;
    
    var total = persen1 + persen2 + persen3 + persen4;
    
    document.getElementById('totalPersen').innerHTML = total;
    
    var statusDiv = document.getElementById('persentaseStatus');
    var messageSpan = document.getElementById('persenMessage');
    
    if (total == 100) {
        statusDiv.className = 'alert alert-success';
        messageSpan.innerHTML = ' - Persentase sudah benar!';
        messageSpan.style.color = 'green';
    } else if (total > 100) {
        statusDiv.className = 'alert alert-danger';
        messageSpan.innerHTML = ' - Persentase melebihi 100%!';
        messageSpan.style.color = 'red';
    } else if (total > 0) {
        statusDiv.className = 'alert alert-warning';
        messageSpan.innerHTML = ' - Persentase kurang dari 100%!';
        messageSpan.style.color = 'orange';
    } else {
        statusDiv.className = 'alert alert-warning';
        messageSpan.innerHTML = ' - Belum ada persentase yang diisi!';
        messageSpan.style.color = 'gray';
    }
}

function validateMekanikKepala() {
    var kepala1 = document.getElementById('cbokepala1').value;
    var kepala2 = document.getElementById('cbokepala2').value;
    
    // Auto set persentase jika hanya satu kepala mekanik
    if (kepala1 && !kepala2) {
        if (document.getElementById('txtpersen_kepala1').value == '' || document.getElementById('txtpersen_kepala1').value == '0') {
            document.getElementById('txtpersen_kepala1').value = '100';
        }
    }
    
    calculatePercentageKepala();
}

function validateMekanik() {
    // Auto set 100% jika hanya 1 mekanik yang dipilih dan field kosong
    var mekanik1 = document.getElementById('cbomekanik1').value;
    var mekanik2 = document.getElementById('cbomekanik2').value;
    var mekanik3 = document.getElementById('cbomekanik3').value;
    var mekanik4 = document.getElementById('cbomekanik4').value;
    
    var filledMekaniks = 0;
    if(mekanik1) filledMekaniks++;
    if(mekanik2) filledMekaniks++;
    if(mekanik3) filledMekaniks++;
    if(mekanik4) filledMekaniks++;
    
    if(filledMekaniks == 1 && mekanik1) {
        if(document.getElementById('txtpersen1').value == '') {
            document.getElementById('txtpersen1').value = '100';
        }
    }
    
    calculatePercentage();
}

function showProsesDetail(keluhanId) {
    $('#modal-proses-detail').modal('show');
    
    // Load proses detail via AJAX
    $.ajax({
        url: 'ajax-get-detail-proses.php',
        method: 'POST',
        data: { keluhan_id: keluhanId },
        success: function(response) {
            $('#proses-detail-content').html(response);
        },
        error: function() {
            $('#proses-detail-content').html('<div class="alert alert-danger">Error loading proses detail</div>');
        }
    });
}

function showModalSearchKeluhan() {
    $('#modal-search-keluhan').modal('show');
}

function saveProses() {
    // Implementation for saving proses status
    alert('Fungsi simpan proses akan diimplementasikan');
}

// Auto calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculatePercentageKepala();
    calculatePercentage();
    
    // Event listeners
    $('#txtpersen_kepala1, #txtpersen_kepala2').on('input keyup', function() {
        calculatePercentageKepala();
    });
    
    $('#txtpersen1, #txtpersen2, #txtpersen3, #txtpersen4').on('input keyup', function() {
        calculatePercentage();
    });
});
</script>