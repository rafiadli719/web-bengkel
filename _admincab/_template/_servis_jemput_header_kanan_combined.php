<?php
// File: _servis_jemput_header_kanan_combined.php
// Template lengkap dengan SPK, KM, Kepala Mekanik, Admin, dan Mekanik

// Ambil data yang sudah tersimpan jika ada
$kepala1 = '';
$kepala2 = '';
$persen_kepala1 = 0;
$persen_kepala2 = 0;
$admin1 = '';
$admin2 = '';
$persen_admin1 = 0;
$persen_admin2 = 0;
$mekanik1_val = '';
$mekanik2_val = '';
$mekanik3_val = '';
$mekanik4_val = '';
$persen1 = '';
$persen2 = '';
$persen3 = '';
$persen4 = '';
$km_skr = '';
$km_berikut = '';
$status_jemput = '1'; // Default dijemput

if(isset($no_service) && isset($koneksi)) {
    try {
        $cari_data = mysqli_query($koneksi,"SELECT 
                                            kepala_mekanik1, kepala_mekanik2, 
                                            persen_kepala_mekanik1, persen_kepala_mekanik2,
                                            admin1, admin2, persen_admin1, persen_admin2,
                                            mekanik1, mekanik2, mekanik3, mekanik4,
                                            persen_mekanik1, persen_mekanik2, persen_mekanik3, persen_mekanik4,
                                            km_skr, km_berikut, status_jemput
                                     FROM tblservice WHERE no_service='$no_service'");
        if($cari_data && $tm_data = mysqli_fetch_array($cari_data)) {
            $kepala1 = $tm_data['kepala_mekanik1'];
            $kepala2 = $tm_data['kepala_mekanik2'];
            $persen_kepala1 = $tm_data['persen_kepala_mekanik1'];
            $persen_kepala2 = $tm_data['persen_kepala_mekanik2'];
            $admin1 = $tm_data['admin1'];
            $admin2 = $tm_data['admin2'];
            $persen_admin1 = $tm_data['persen_admin1'];
            $persen_admin2 = $tm_data['persen_admin2'];
            $mekanik1_val = $tm_data['mekanik1'];
            $mekanik2_val = $tm_data['mekanik2'];
            $mekanik3_val = $tm_data['mekanik3'];
            $mekanik4_val = $tm_data['mekanik4'];
            $persen1 = $tm_data['persen_mekanik1'];
            $persen2 = $tm_data['persen_mekanik2'];
            $persen3 = $tm_data['persen_mekanik3'];
            $persen4 = $tm_data['persen_mekanik4'];
            $km_skr = $tm_data['km_skr'];
            $km_berikut = $tm_data['km_berikut'];
            $status_jemput = $tm_data['status_jemput'];
        }
    } catch (Exception $e) {
        error_log("Template error: " . $e->getMessage());
    }
}
?>

<!-- Section Info Service dan Jenis -->
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
                <div class="col-xs-8 col-sm-6">
                    <label>Jenis Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <select name="status_jemput" id="status_jemput" class="form-control" onchange="updateJenisService()">
                                <option value="0" <?php echo (isset($status_jemput) && $status_jemput == '0') ? 'selected' : ''; ?>>Ditinggal</option>
                                <option value="1" <?php echo (isset($status_jemput) && $status_jemput == '1') ? 'selected' : 'selected'; ?>>Dijemput</option>
                                <option value="2" <?php echo (isset($status_jemput) && $status_jemput == '2') ? 'selected' : ''; ?>>Ditunggu</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section SPK (Surat Perintah Kerja) -->
            <h4 class="header blue">
                <i class="ace-icon fa fa-list-alt"></i>
                Daftar SPK (Surat Perintah Kerja)
            </h4>
            
            <!-- Header Info SPK -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-info" style="margin-bottom: 15px;">
                        <i class="ace-icon fa fa-motorcycle"></i>
                        <strong>Nopol: <?php echo htmlspecialchars($no_polisi); ?></strong>
                        <span class="pull-right">
                            <button type="button" class="btn btn-xs btn-info" onclick="refreshSPK()" title="Refresh Daftar SPK">
                                <i class="ace-icon fa fa-refresh"></i> Refresh
                            </button>
                        </span>
                        <div class="clearfix"></div>
                        <small>Input keluhan dan work order untuk servis jemput</small>
                        <br><strong>Alamat Jemput:</strong> <?php echo htmlspecialchars($alamat ?? ''); ?>
                        <?php if(!empty($patokan)) { ?>
                            <br><strong>Patokan:</strong> <?php echo htmlspecialchars($patokan); ?>
                        <?php } ?>
                    </div>
                </div>
            </div>
            
            <!-- Hidden fields untuk form submission -->
            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>" />
            <input type="hidden" name="txtcarisrv" value="<?php echo isset($txtcarisrv) ? $txtcarisrv : ''; ?>" />
            <input type="hidden" name="txtcaribrg" value="<?php echo isset($txtcaribrg) ? $txtcaribrg : ''; ?>" />
            <input type="hidden" name="txtcariwo" value="<?php echo isset($txtcariwo) ? $txtcariwo : ''; ?>" />
            
            <!-- Form Input Section untuk SPK -->
            <div class="row" style="margin-bottom: 20px;">
                <!-- Input Keluhan & Work Order Section (ATAS) -->
                <div class="col-xs-12 col-sm-12">
                    <div class="widget-box widget-color-orange" style="margin-bottom: 15px;">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-plus-circle"></i>
                                Input Keluhan & Work Order Jemput
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main" style="padding: 15px;">
                                <!-- Input Keluhan (ATAS) -->
                                <div class="row" style="margin-bottom: 20px;">
                                    <div class="col-xs-12 col-sm-12">
                                        <h6 class="header orange smaller" style="margin-top: 0;">
                                            <i class="ace-icon fa fa-exclamation-triangle"></i> Input Keluhan Jemput
                                        </h6>
                                        <table class="table table-bordered" style="margin-bottom: 15px;">
                                            <tr>
                                                <td width="70%">
                                                    <label>Keluhan :</label>
                                                    <input type="text" class="form-control input-sm" 
                                                    id="txtkeluhan" name="txtkeluhan" 
                                                    placeholder="Masukkan keluhan jemput" autocomplete="off" />
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
                                    </div>
                                </div>
                                
                                <!-- Input Work Order (BAWAH) -->
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12">
                                        <h6 class="header blue smaller" style="margin-top: 0;">
                                            <i class="ace-icon fa fa-cogs"></i> Input Work Order Jemput
                                        </h6>
                                        <table class="table table-bordered" style="margin-bottom: 15px;">
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Daftar SPK Section (BAWAH) -->
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-xs-12 col-sm-12">
                    <?php 
                        // Hitung total SPK
                        $no_keluhan = 0;
                        $sql_keluhan_count = mysqli_query($koneksi,"SELECT COUNT(*) as total FROM tbservis_keluhan_status WHERE no_service='$no_service'");
                        if($sql_keluhan_count) {
                            $result_keluhan = mysqli_fetch_array($sql_keluhan_count);
                            $no_keluhan = $result_keluhan['total'];
                        }
                        
                        $no_wo = 0;
                        $sql_wo_count = mysqli_query($koneksi,"SELECT COUNT(*) as total FROM tbservis_workorder WHERE no_service='$no_service'");
                        if($sql_wo_count) {
                            $result_wo = mysqli_fetch_array($sql_wo_count);
                            $no_wo = $result_wo['total'];
                        }
                        
                        $total_spk = $no_keluhan + $no_wo;
                    ?>
                    
                    <div class="widget-box widget-color-purple" style="margin-bottom: 15px;">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-list"></i>
                                Daftar SPK untuk Nopol: <?php echo htmlspecialchars($no_polisi); ?>
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main" style="padding: 15px;">
                                <div class="alert alert-warning" style="margin-bottom: 15px; padding: 10px;">
                                    <div class="row">
                                        <div class="col-xs-8">
                                            <strong>Total: <?php echo $total_spk; ?> SPK</strong>
                                            <br><small>(<?php echo $no_keluhan; ?> keluhan + <?php echo $no_wo; ?> work order)</small>
                                            <br><span class="label label-warning">SERVIS JEMPUT</span>
                                        </div>
                                        <div class="col-xs-4 text-right">
                                            <button type="button" class="btn btn-xs btn-info" onclick="refreshSPK()" title="Refresh Daftar SPK">
                                                <i class="ace-icon fa fa-refresh"></i> Refresh
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="max-height: 300px; overflow-y: auto;">
                                    <?php if($total_spk > 0) { ?>
                                    <ol class="spk-list" style="list-style: none; padding-left: 0; margin-bottom: 0;">
                                        <?php 
                                            $counter = 1;
                                            
                                            // Tampilkan keluhan
                                            $sql_keluhan_list = mysqli_query($koneksi,"SELECT id, keluhan FROM tbservis_keluhan_status WHERE no_service='$no_service' ORDER BY id ASC");
                                            if($sql_keluhan_list) {
                                                while ($tampil = mysqli_fetch_array($sql_keluhan_list)) {
                                                    echo "<li style='margin-bottom: 10px; padding: 8px; border-left: 4px solid #ff9800; background-color: #fff3e0; font-size: 14px; position: relative;'>";
                                                    echo "<div style='display: flex; justify-content: between; align-items: center;'>";
                                                    echo "<span style='flex: 1; font-weight: bold; color: #333;'>";
                                                    echo "<span style='display: inline-block; width: 30px; color: #ff9800; font-weight: bold;'>" . $counter . ".</span>";
                                                    echo "<span class='keluhan-text-" . $tampil['id'] . "'>" . htmlspecialchars($tampil['keluhan']) . "</span>";
                                                    echo "</span>";
                                                    echo "<div class='spk-actions' style='margin-left: 10px;'>";
                                                    echo "<button type='button' class='btn btn-xs btn-warning' onclick='editKeluhan(" . $tampil['id'] . ", \"" . htmlspecialchars($tampil['keluhan'], ENT_QUOTES) . "\")' title='Edit Keluhan'>";
                                                    echo "<i class='ace-icon fa fa-edit'></i>";
                                                    echo "</button> ";
                                                    echo "<button type='button' class='btn btn-xs btn-danger' onclick='hapusKeluhan(" . $tampil['id'] . ")' title='Hapus Keluhan'>";
                                                    echo "<i class='ace-icon fa fa-trash'></i>";
                                                    echo "</button>";
                                                    echo "</div>";
                                                    echo "</div>";
                                                    echo "</li>";
                                                    $counter++;
                                                }
                                            }
                                            
                                            // Tampilkan work order
                                            $sql_wo_list = mysqli_query($koneksi,"SELECT sw.id, wh.nama_wo, sw.kode_wo FROM tbservis_workorder sw LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo WHERE sw.no_service='$no_service' ORDER BY sw.id ASC");
                                            if($sql_wo_list) {
                                                while ($tampil = mysqli_fetch_array($sql_wo_list)) {
                                                    echo "<li style='margin-bottom: 10px; padding: 8px; border-left: 4px solid #2196f3; background-color: #e3f2fd; font-size: 14px; position: relative;'>";
                                                    echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
                                                    echo "<span style='flex: 1; font-weight: bold; color: #333;'>";
                                                    echo "<span style='display: inline-block; width: 30px; color: #2196f3; font-weight: bold;'>" . $counter . ".</span>";
                                                    echo htmlspecialchars($tampil['nama_wo']);
                                                    echo "</span>";
                                                    echo "<div class='spk-actions' style='margin-left: 10px;'>";
                                                    echo "<button type='button' class='btn btn-xs btn-danger' onclick='hapusWorkOrder(" . $tampil['id'] . ")' title='Hapus Work Order'>";
                                                    echo "<i class='ace-icon fa fa-trash'></i>";
                                                    echo "</button>";
                                                    echo "</div>";
                                                    echo "</div>";
                                                    echo "</li>";
                                                    $counter++;
                                                }
                                            }
                                        ?>
                                    </ol>
                                    <?php } else { ?>
                                    <div class="alert alert-warning" style="margin-bottom: 0; padding: 10px;">
                                        <i class="ace-icon fa fa-motorcycle"></i>
                                        Belum ada SPK untuk Nopol: <strong><?php echo htmlspecialchars($no_polisi); ?></strong>
                                        <br><small>Tambahkan keluhan atau work order jemput di atas</small>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
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
                    <select class="form-control" name="cbokepala1" id="cbokepala1" onchange="validateMekanikKepala(); autoFillKepalaPercentageWithSave()">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                            if(isset($koneksi)) {
                                try {
                                    $sql="SELECT nomekanik, nama FROM tblmekanik 
                                          WHERE nama<>'-' AND keahlian='1' 
                                          ORDER BY nama ASC";
                                    $sql_row=mysqli_query($koneksi,$sql);
                                    if($sql_row) {
                                        while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo htmlspecialchars($sql_res["nomekanik"]); ?>" 
                                <?php echo ($kepala1==$sql_res["nomekanik"])?'selected':''; ?>>
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
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala1" id="txtpersen_kepala1" 
                               value="<?php echo $persen_kepala1; ?>" min="0" max="100" 
                               onchange="calculatePercentageKepalaWithSave()" onkeyup="calculatePercentageKepalaWithSave()">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Kepala Mekanik 2 dengan Persentase -->  
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 2 (Opsional):</label>
                    <select class="form-control" name="cbokepala2" id="cbokepala2" onchange="autoFillKepalaPercentageWithSave()">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                            if(isset($koneksi)) {
                                try {
                                    $sql="SELECT nomekanik, nama FROM tblmekanik 
                                          WHERE nama<>'-' AND keahlian='1' 
                                          ORDER BY nama ASC";
                                    $sql_row=mysqli_query($koneksi,$sql);
                                    if($sql_row) {
                                        while($sql_res=mysqli_fetch_assoc($sql_row)) {
                        ?>
                        <option value="<?php echo htmlspecialchars($sql_res["nomekanik"]); ?>" 
                                <?php echo ($kepala2==$sql_res["nomekanik"])?'selected':''; ?>>
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
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala2" id="txtpersen_kepala2" 
                               value="<?php echo $persen_kepala2; ?>" min="0" max="100" 
                               onchange="calculatePercentageKepalaWithSave()" onkeyup="calculatePercentageKepalaWithSave()">
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
                        <i class="ace-icon fa fa-user"></i>
                        Admin/Kasir
                    </h4>
                    <p><small><em>Minimal 1 admin/kasir harus diisi. Total persentase harus 100%</em></small></p>
                </div>
                
                <!-- Admin/Kasir 1 with percentage -->
                <div class="col-xs-8 col-sm-8">
                    <label>Admin/Kasir 1 *:</label>
                    <select name="cboadmin1" id="cboadmin1" class="form-control" required onchange="autoFillAdminPercentageWithSave()">
                        <option value="">- Pilih Admin/Kasir -</option>
                        <?php
                        if(isset($koneksi)) {
                            try {
                                $query_admin = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
                                $result_admin = mysqli_query($koneksi, $query_admin);
                                if($result_admin) {
                                    while($row_admin = mysqli_fetch_array($result_admin)) {
                                        $selected = ($admin1 == $row_admin['nomekanik']) ? 'selected' : '';
                                        echo "<option value='".$row_admin['nomekanik']."' $selected>".$row_admin['nama']."</option>";
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Pengerjaan:</label>
                    <div class="input-group">
                        <input type="number" name="txtpersen_admin1" id="txtpersen_admin1" 
                               class="form-control" value="<?php echo $persen_admin1; ?>" 
                               min="0" max="100" onchange="calculatePercentageAdminWithSave()" />
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Admin/Kasir 2 with percentage -->
                <div class="col-xs-8 col-sm-8">
                    <label>Admin/Kasir 2 (Opsional):</label>
                    <select name="cboadmin2" id="cboadmin2" class="form-control" onchange="autoFillAdminPercentageWithSave()">
                        <option value="">- Pilih Admin/Kasir -</option>
                        <?php
                        if(isset($koneksi)) {
                            try {
                                $query_admin = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
                                $result_admin = mysqli_query($koneksi, $query_admin);
                                if($result_admin) {
                                    while($row_admin = mysqli_fetch_array($result_admin)) {
                                        $selected = ($admin2 == $row_admin['nomekanik']) ? 'selected' : '';
                                        echo "<option value='".$row_admin['nomekanik']."' $selected>".$row_admin['nama']."</option>";
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Pengerjaan:</label>
                    <div class="input-group">
                        <input type="number" name="txtpersen_admin2" id="txtpersen_admin2" 
                               class="form-control" value="<?php echo $persen_admin2; ?>" 
                               min="0" max="100" onchange="calculatePercentageAdminWithSave()" />
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Total Percentage Display for Admin -->
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-info" id="persentaseStatusAdmin">
                        <i class="fa fa-calculator"></i> 
                        <strong>Total % Pengerjaan Admin/Kasir: <span id="totalPersenAdmin">0</span>%</strong>
                        <span id="persenMessageAdmin"> - Harus 100%</span>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Mekanik -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header blue smaller">
                        <i class="ace-icon fa fa-wrench"></i>
                        Mekanik
                    </h4>
                    <p><small><em>Minimal 1 mekanik harus diisi. Total persentase harus 100%</em></small></p>
                </div>
                
                <!-- Mekanik 1 with percentage -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 1 *:</label>
                    <select name="cbomekanik1" id="cbomekanik1" class="form-control" required onchange="autoFillMekanikPercentageWithSave()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi)) {
                            try {
                                $query_mek = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
                                $result_mek = mysqli_query($koneksi, $query_mek);
                                if($result_mek) {
                                    while($row_mek = mysqli_fetch_array($result_mek)) {
                                        $selected = ($mekanik1_val == $row_mek['nomekanik']) ? 'selected' : '';
                                        echo "<option value='".$row_mek['nomekanik']."' $selected>".$row_mek['nama']."</option>";
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Pengerjaan:</label>
                    <div class="input-group">
                        <input type="number" name="txtpersen_kerja1" id="txtpersen_kerja1" 
                               class="form-control" value="<?php echo $persen1; ?>" 
                               min="0" max="100" onchange="calculatePercentageMekanikWithSave()" />
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Mekanik 2 with percentage -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 2 (Opsional):</label>
                    <select name="cbomekanik2" id="cbomekanik2" class="form-control" onchange="autoFillMekanikPercentageWithSave()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi)) {
                            try {
                                $query_mek = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
                                $result_mek = mysqli_query($koneksi, $query_mek);
                                if($result_mek) {
                                    while($row_mek = mysqli_fetch_array($result_mek)) {
                                        $selected = ($mekanik2_val == $row_mek['nomekanik']) ? 'selected' : '';
                                        echo "<option value='".$row_mek['nomekanik']."' $selected>".$row_mek['nama']."</option>";
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Pengerjaan:</label>
                    <div class="input-group">
                        <input type="number" name="txtpersen_kerja2" id="txtpersen_kerja2" 
                               class="form-control" value="<?php echo $persen2; ?>" 
                               min="0" max="100" onchange="calculatePercentageMekanikWithSave()" />
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Mekanik 3 with percentage -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 3 (Opsional):</label>
                    <select name="cbomekanik3" id="cbomekanik3" class="form-control" onchange="autoFillMekanikPercentageWithSave()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi)) {
                            try {
                                $query_mek = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
                                $result_mek = mysqli_query($koneksi, $query_mek);
                                if($result_mek) {
                                    while($row_mek = mysqli_fetch_array($result_mek)) {
                                        $selected = ($mekanik3_val == $row_mek['nomekanik']) ? 'selected' : '';
                                        echo "<option value='".$row_mek['nomekanik']."' $selected>".$row_mek['nama']."</option>";
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Pengerjaan:</label>
                    <div class="input-group">
                        <input type="number" name="txtpersen_kerja3" id="txtpersen_kerja3" 
                               class="form-control" value="<?php echo $persen3; ?>" 
                               min="0" max="100" onchange="calculatePercentageMekanikWithSave()" />
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Mekanik 4 with percentage -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 4 (Opsional):</label>
                    <select name="cbomekanik4" id="cbomekanik4" class="form-control" onchange="autoFillMekanikPercentageWithSave()">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi)) {
                            try {
                                $query_mek = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
                                $result_mek = mysqli_query($koneksi, $query_mek);
                                if($result_mek) {
                                    while($row_mek = mysqli_fetch_array($result_mek)) {
                                        $selected = ($mekanik4_val == $row_mek['nomekanik']) ? 'selected' : '';
                                        echo "<option value='".$row_mek['nomekanik']."' $selected>".$row_mek['nama']."</option>";
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Pengerjaan:</label>
                    <div class="input-group">
                        <input type="number" name="txtpersen_kerja4" id="txtpersen_kerja4" 
                               class="form-control" value="<?php echo $persen4; ?>" 
                               min="0" max="100" onchange="calculatePercentageMekanikWithSave()" />
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Total Percentage Display for Mekanik -->
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-info" id="persentaseStatusMekanik">
                        <i class="fa fa-calculator"></i> 
                        <strong>Total % Pengerjaan Mekanik: <span id="totalPersenMekanik">0</span>%</strong>
                        <span id="persenMessageMekanik"> - Harus 100%</span>
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

            <!-- History Service -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h5 class="header purple smaller">
                        <i class="ace-icon fa fa-history"></i> Riwayat Service Kendaraan
                    </h5>
                </div>
                <div class="col-xs-12 col-sm-12">
                    <table class="table table-bordered table-condensed">
                        <thead>
                            <tr class="info">
                                <th width="15%" class="center">No. Service</th>
                                <th width="15%" class="center">Tanggal</th>
                                <th width="15%" class="center">KM</th>
                                <th width="55%">Keluhan Sebelumnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                                if(isset($koneksi) && isset($no_polisi) && $no_polisi != '') {
                                    try {
                                        $sql = mysqli_query($koneksi,"SELECT s.no_service, 
                                                                     DATE_FORMAT(s.tanggal,'%d/%m/%Y') AS tanggal_serv, 
                                                                     s.km_skr 
                                                                     FROM tblservice s 
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
                                <td class="center"><?php echo $tampil['tanggal_serv']?></td>
                                <td class="center"><?php echo number_format($tampil['km_skr'], 0, ',', '.'); ?></td>
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
                            <?php 
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="4" class="center"><em>Error loading history: ' . $e->getMessage() . '</em></td></tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="center"><em>No polisi tidak tersedia</em></td></tr>';
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Debug Panel untuk Auto-Save -->
            <div class="row" style="margin-bottom: 20px;">
                <div class="col-xs-12 col-sm-12">
                    <div class="alert alert-warning" style="padding: 10px;">
                        <h6 class="header orange smaller" style="margin: 0 0 10px 0;">
                            <i class="ace-icon fa fa-bug"></i> Debug Auto-Save Status (Jemput)
                        </h6>
                        <div id="debug-auto-save" style="font-size: 12px; max-height: 100px; overflow-y: auto;">
                            <div>✅ Auto-save system initialized</div>
                            <div>⏰ Auto-save interval: Every 3 seconds</div>
                        </div>
                        <button type="button" class="btn btn-xs btn-success" onclick="forceSaveAllData().then(function(){alert('Manual save completed!');})" style="margin-top: 5px;">
                            <i class="ace-icon fa fa-save"></i> Manual Save
                        </button>
                        <button type="button" class="btn btn-xs btn-info" onclick="$('#debug-auto-save').html('Debug cleared...');" style="margin-top: 5px;">
                            <i class="ace-icon fa fa-eraser"></i> Clear Debug
                        </button>
                    </div>
                </div>
            </div>

            <!-- Hidden fields untuk form submission -->
            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>" />
            <input type="hidden" name="txtcarisrv" value="<?php echo isset($txtcarisrv) ? $txtcarisrv : ''; ?>" />
            <input type="hidden" name="txtcaribrg" value="<?php echo isset($txtcaribrg) ? $txtcaribrg : ''; ?>" />
            <input type="hidden" name="txtcariwo" value="<?php echo isset($txtcariwo) ? $txtcariwo : ''; ?>" />
            
        </div>
    </div>
</div>

<!-- Include Modal Search Keluhan -->
<?php 
try {
    if(file_exists('_template/modal-search-keluhan.php')) {
        include '_template/modal-search-keluhan.php';
    }
} catch (Exception $e) {
    // Silent error handling
}
?>

<script type="text/javascript">
function showModalSearchKeluhan() {
    // Implementasi modal search keluhan
    if(typeof $('#modal-search-keluhan').modal === 'function') {
        $('#modal-search-keluhan').modal('show');
    } else {
        alert('Modal search keluhan belum tersedia');
    }
}

function selectKeluhan(keluhan) {
    // Set keluhan to the input field
    var keluhanInput = document.querySelector('input[name="txtkeluhan"]');
    if (keluhanInput) {
        keluhanInput.value = keluhan;
    }
    $('#modal-search-keluhan').modal('hide');
}

function refreshSPK() {
    // Refresh halaman untuk memperbarui daftar SPK
    window.location.reload();
}

// === SPK EDIT/HAPUS FUNCTIONS ===
function editKeluhan(id, keluhanLama) {
    // Tampilkan prompt untuk edit keluhan
    var keluhanBaru = prompt('Edit Keluhan:', keluhanLama);
    
    if (keluhanBaru === null) {
        // User cancel
        return;
    }
    
    if (keluhanBaru.trim() === '') {
        alert('Keluhan tidak boleh kosong!');
        return;
    }
    
    if (keluhanBaru === keluhanLama) {
        // Tidak ada perubahan
        return;
    }
    
    var noService = $('input[name="txtnosrv"]').val();
    
    $.ajax({
        url: '_ajax/edit_spk_keluhan.php',
        type: 'POST',
        data: {
            id: id,
            no_service: noService,
            keluhan_baru: keluhanBaru
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('Keluhan berhasil diupdate!');
                // Update text di halaman tanpa reload
                $('.keluhan-text-' + id).text(response.keluhan_baru);
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('AJAX Error: ' + error);
        }
    });
}

function hapusKeluhan(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus keluhan ini dari SPK?')) {
        return;
    }
    
    var noService = $('input[name="txtnosrv"]').val();
    
    $.ajax({
        url: '_ajax/hapus_spk_keluhan.php',
        type: 'POST',
        data: {
            id: id,
            no_service: noService
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('Keluhan berhasil dihapus dari SPK!');
                refreshSPK(); // Refresh untuk update tampilan
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('AJAX Error: ' + error);
        }
    });
}

function hapusWorkOrder(id) {
    if (!confirm('Apakah Anda yakin ingin menghapus work order ini dari SPK?')) {
        return;
    }
    
    var noService = $('input[name="txtnosrv"]').val();
    
    $.ajax({
        url: '_ajax/hapus_spk_workorder.php',
        type: 'POST',
        data: {
            id: id,
            no_service: noService
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                alert('Work order berhasil dihapus dari SPK!');
                refreshSPK(); // Refresh untuk update tampilan
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            alert('AJAX Error: ' + error);
        }
    });
}

function updateJenisService() {
    // Function untuk update jenis service
    var statusJemput = document.getElementById('status_jemput').value;
    console.log('Jenis service diubah ke: ' + statusJemput);
    // Optional: Tambahkan logika tambahan jika diperlukan
}

// === KEPALA MEKANIK FUNCTIONS ===
function calculatePercentageKepala() {
    var total = 0;
    var persen1 = parseInt($('#txtpersen_kepala1').val()) || 0;
    var persen2 = parseInt($('#txtpersen_kepala2').val()) || 0;
    
    total = persen1 + persen2;
    
    $('#totalPersenKepala').text(total);
    
    // Update status message and color
    var statusDiv = $('#persentaseStatusKepala');
    var messageSpan = $('#persenMessageKepala');
    
    statusDiv.removeClass('alert-info alert-warning alert-danger alert-success');
    
    if (total <= 100) {
        statusDiv.addClass('alert-success');
        messageSpan.text(' - OK, boleh kurang dari 100%');
    } else {
        statusDiv.addClass('alert-danger');
        messageSpan.text(' - Melebihi 100%!');
    }
}

function autoFillKepalaPercentage() {
    var kepala1 = document.getElementById('cbokepala1').value;
    var kepala2 = document.getElementById('cbokepala2').value;
    
    // Clear all percentages first
    document.getElementById('txtpersen_kepala1').value = '';
    document.getElementById('txtpersen_kepala2').value = '';
    
    // Count selected kepala
    var selectedCount = 0;
    if (kepala1) selectedCount++;
    if (kepala2) selectedCount++;
    
    if (selectedCount === 1) {
        // Auto-fill 100% for the selected one
        if (kepala1) document.getElementById('txtpersen_kepala1').value = '100';
        else if (kepala2) document.getElementById('txtpersen_kepala2').value = '100';
    }
    else if (selectedCount === 2) {
        // Split 50-50
        document.getElementById('txtpersen_kepala1').value = '50';
        document.getElementById('txtpersen_kepala2').value = '50';
    }
    
    calculatePercentageKepala();
}

function validateMekanikKepala() {
    var kepala1 = $('#cbokepala1').val();
    
    // Kepala Mekanik 1 is required
    if (!kepala1) {
        alert('Kepala Mekanik 1 wajib dipilih!');
        return false;
    }
    
    return true;
}

// === ADMIN FUNCTIONS ===
function calculatePercentageAdmin() {
    var total = 0;
    var persen1 = parseInt($('#txtpersen_admin1').val()) || 0;
    var persen2 = parseInt($('#txtpersen_admin2').val()) || 0;
    
    total = persen1 + persen2;
    
    $('#totalPersenAdmin').text(total);
    
    // Update status message and color
    var statusDiv = $('#persentaseStatusAdmin');
    var messageSpan = $('#persenMessageAdmin');
    
    statusDiv.removeClass('alert-info alert-warning alert-danger alert-success');
    
    if (total == 100) {
        statusDiv.addClass('alert-success');
        messageSpan.text(' - Sempurna!');
    } else if (total > 100) {
        statusDiv.addClass('alert-danger');
        messageSpan.text(' - Melebihi 100%!');
    } else if (total > 0) {
        statusDiv.addClass('alert-warning');
        messageSpan.text(' - Kurang dari 100%');
    } else {
        statusDiv.addClass('alert-info');
        messageSpan.text(' - Harus 100%');
    }
}

function autoFillAdminPercentage() {
    var admin1 = document.getElementById('cboadmin1').value;
    var admin2 = document.getElementById('cboadmin2').value;
    
    // Clear all percentages first
    document.getElementById('txtpersen_admin1').value = '';
    document.getElementById('txtpersen_admin2').value = '';
    
    // Count selected admins
    var selectedCount = 0;
    if (admin1) selectedCount++;
    if (admin2) selectedCount++;
    
    if (selectedCount === 1) {
        // Auto-fill 100% for the selected one
        if (admin1) document.getElementById('txtpersen_admin1').value = '100';
        else if (admin2) document.getElementById('txtpersen_admin2').value = '100';
    }
    else if (selectedCount === 2) {
        // Split 50-50
        document.getElementById('txtpersen_admin1').value = '50';
        document.getElementById('txtpersen_admin2').value = '50';
    }
    
    calculatePercentageAdmin();
}

function validateAdmin() {
    var admin1 = $('#cboadmin1').val();
    
    // Admin 1 is required
    if (!admin1) {
        alert('Admin/Kasir 1 wajib dipilih!');
        return false;
    }
    
    return true;
}

// === MEKANIK FUNCTIONS ===
function calculatePercentageMekanik() {
    var total = 0;
    var persen1 = parseInt($('#txtpersen_kerja1').val()) || 0;
    var persen2 = parseInt($('#txtpersen_kerja2').val()) || 0;
    var persen3 = parseInt($('#txtpersen_kerja3').val()) || 0;
    var persen4 = parseInt($('#txtpersen_kerja4').val()) || 0;
    
    total = persen1 + persen2 + persen3 + persen4;
    
    $('#totalPersenMekanik').text(total);
    
    // Update status message and color
    var statusDiv = $('#persentaseStatusMekanik');
    var messageSpan = $('#persenMessageMekanik');
    
    statusDiv.removeClass('alert-info alert-warning alert-danger alert-success');
    
    if (total == 100) {
        statusDiv.addClass('alert-success');
        messageSpan.text(' - Sempurna!');
    } else if (total > 100) {
        statusDiv.addClass('alert-danger');
        messageSpan.text(' - Melebihi 100%!');
    } else if (total > 0) {
        statusDiv.addClass('alert-warning');
        messageSpan.text(' - Kurang dari 100%');
    } else {
        statusDiv.addClass('alert-info');
        messageSpan.text(' - Harus 100%');
    }
}

function autoFillMekanikPercentage() {
    var mekanik1 = document.getElementById('cbomekanik1').value;
    var mekanik2 = document.getElementById('cbomekanik2').value;
    var mekanik3 = document.getElementById('cbomekanik3').value;
    var mekanik4 = document.getElementById('cbomekanik4').value;
    
    var selectedCount = 0;
    if (mekanik1) selectedCount++;
    if (mekanik2) selectedCount++;
    if (mekanik3) selectedCount++;
    if (mekanik4) selectedCount++;
    
    // Clear all percentages first
    document.getElementById('txtpersen_kerja1').value = '';
    document.getElementById('txtpersen_kerja2').value = '';
    document.getElementById('txtpersen_kerja3').value = '';
    document.getElementById('txtpersen_kerja4').value = '';
    
    if (selectedCount === 1) {
        // Auto-fill 100% for the selected one
        if (mekanik1) document.getElementById('txtpersen_kerja1').value = '100';
        else if (mekanik2) document.getElementById('txtpersen_kerja2').value = '100';
        else if (mekanik3) document.getElementById('txtpersen_kerja3').value = '100';
        else if (mekanik4) document.getElementById('txtpersen_kerja4').value = '100';
    }
    else if (selectedCount === 2) {
        // Split 50-50
        var percentage = '50';
        if (mekanik1) document.getElementById('txtpersen_kerja1').value = percentage;
        if (mekanik2) document.getElementById('txtpersen_kerja2').value = percentage;
        if (mekanik3) document.getElementById('txtpersen_kerja3').value = percentage;
        if (mekanik4) document.getElementById('txtpersen_kerja4').value = percentage;
    }
    else if (selectedCount === 3) {
        // Split 33.33-33.33-33.33
        var percentage = '33';
        var lastPercentage = '34'; // To make total 100%
        var count = 0;
        if (mekanik1) { count++; document.getElementById('txtpersen_kerja1').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik2) { count++; document.getElementById('txtpersen_kerja2').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik3) { count++; document.getElementById('txtpersen_kerja3').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik4) { count++; document.getElementById('txtpersen_kerja4').value = (count === 3) ? lastPercentage : percentage; }
    }
    else if (selectedCount === 4) {
        // Split 25-25-25-25
        document.getElementById('txtpersen_kerja1').value = '25';
        document.getElementById('txtpersen_kerja2').value = '25';
        document.getElementById('txtpersen_kerja3').value = '25';
        document.getElementById('txtpersen_kerja4').value = '25';
    }
    
    calculatePercentageMekanik();
}

function validateMekanik() {
    var mekanik1 = $('#cbomekanik1').val();
    
    // Mekanik 1 is required
    if (!mekanik1) {
        alert('Mekanik 1 wajib dipilih!');
        return false;
    }
    
    return true;
}

// === AUTO-SAVE FUNCTIONS ===
function autoSaveMekanikData(fieldType, fieldValue, fieldPercentage) {
    var noService = $('input[name="txtnosrv"]').val();
    if (!noService) {
        console.log('No service tidak ditemukan');
        return;
    }
    
    $.ajax({
        url: '_ajax/auto_save_mekanik.php',
        type: 'POST',
        data: {
            no_service: noService,
            field_type: fieldType,
            field_value: fieldValue,
            field_percentage: fieldPercentage
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                console.log('✓ ' + response.message + ' - ' + fieldType);
                showSaveIndicator(fieldType, 'success');
            } else {
                console.log('✗ Error: ' + response.message);
                showSaveIndicator(fieldType, 'error');
            }
        },
        error: function(xhr, status, error) {
            console.log('✗ AJAX Error: ' + error);
            showSaveIndicator(fieldType, 'error');
        }
    });
}

function autoSaveKMData() {
    var noService = $('input[name="txtnosrv"]').val();
    var kmSkr = $('#txtkm_skr').val() || 0;
    var kmBerikut = $('#txtkm_next').val() || 0;
    
    if (!noService) {
        console.log('No service tidak ditemukan untuk save KM');
        return;
    }
    
    $.ajax({
        url: '_ajax/auto_save_km.php',
        type: 'POST',
        data: {
            no_service: noService,
            km_skr: kmSkr,
            km_berikut: kmBerikut
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                console.log('✓ Data KM berhasil disimpan');
                showSaveIndicator('km', 'success');
            } else {
                console.log('✗ Error save KM: ' + response.message);
                showSaveIndicator('km', 'error');
            }
        },
        error: function(xhr, status, error) {
            console.log('✗ AJAX Error save KM: ' + error);
            showSaveIndicator('km', 'error');
        }
    });
}

function showSaveIndicator(fieldType, status) {
    var targetElement;
    
    if (fieldType.includes('kepala_mekanik')) {
        targetElement = $('#cbokepala' + fieldType.slice(-1));
    } else if (fieldType.includes('admin')) {
        targetElement = $('#cboadmin' + fieldType.slice(-1));
    } else if (fieldType.includes('mekanik')) {
        targetElement = $('#cbomekanik' + fieldType.slice(-1));
    } else if (fieldType === 'km') {
        targetElement = $('#txtkm_skr');
    }
    
    if (targetElement && targetElement.length) {
        var originalBorder = targetElement.css('border');
        var color = (status === 'success') ? '#5cb85c' : '#d9534f';
        
        targetElement.css('border', '2px solid ' + color);
        
        setTimeout(function() {
            targetElement.css('border', originalBorder);
        }, 1500);
    }
}

// Modified percentage calculation functions to include auto-save
function calculatePercentageKepalaWithSave() {
    calculatePercentageKepala();
    
    // Auto-save kepala mekanik data
    var kepala1 = $('#cbokepala1').val();
    var kepala2 = $('#cbokepala2').val();
    var persen1 = $('#txtpersen_kepala1').val() || 0;
    var persen2 = $('#txtpersen_kepala2').val() || 0;
    
    if (kepala1) {
        autoSaveMekanikData('kepala_mekanik1', kepala1, persen1);
    }
    if (kepala2) {
        autoSaveMekanikData('kepala_mekanik2', kepala2, persen2);
    }
}

function calculatePercentageAdminWithSave() {
    calculatePercentageAdmin();
    
    // Auto-save admin data
    var admin1 = $('#cboadmin1').val();
    var admin2 = $('#cboadmin2').val();
    var persen1 = $('#txtpersen_admin1').val() || 0;
    var persen2 = $('#txtpersen_admin2').val() || 0;
    
    if (admin1) {
        autoSaveMekanikData('admin1', admin1, persen1);
    }
    if (admin2) {
        autoSaveMekanikData('admin2', admin2, persen2);
    }
}

function calculatePercentageMekanikWithSave() {
    calculatePercentageMekanik();
    
    // Auto-save mekanik data
    var mekanik1 = $('#cbomekanik1').val();
    var mekanik2 = $('#cbomekanik2').val();
    var mekanik3 = $('#cbomekanik3').val();
    var mekanik4 = $('#cbomekanik4').val();
    var persen1 = $('#txtpersen_kerja1').val() || 0;
    var persen2 = $('#txtpersen_kerja2').val() || 0;
    var persen3 = $('#txtpersen_kerja3').val() || 0;
    var persen4 = $('#txtpersen_kerja4').val() || 0;
    
    if (mekanik1) {
        autoSaveMekanikData('mekanik1', mekanik1, persen1);
    }
    if (mekanik2) {
        autoSaveMekanikData('mekanik2', mekanik2, persen2);
    }
    if (mekanik3) {
        autoSaveMekanikData('mekanik3', mekanik3, persen3);
    }
    if (mekanik4) {
        autoSaveMekanikData('mekanik4', mekanik4, persen4);
    }
}

// Modified autoFill functions to include auto-save
function autoFillKepalaPercentageWithSave() {
    autoFillKepalaPercentage();
    calculatePercentageKepalaWithSave();
}

function autoFillAdminPercentageWithSave() {
    autoFillAdminPercentage();
    calculatePercentageAdminWithSave();
}

function autoFillMekanikPercentageWithSave() {
    autoFillMekanikPercentage();
    calculatePercentageMekanikWithSave();
}

// === DEBUG FUNCTIONS ===
function addDebugMessage(message, type) {
    var timestamp = new Date().toLocaleTimeString();
    var icon = type === 'success' ? '✅' : type === 'error' ? '❌' : type === 'info' ? 'ℹ️' : '🔄';
    var debugPanel = $('#debug-auto-save');
    
    debugPanel.append('<div>' + icon + ' [' + timestamp + '] ' + message + '</div>');
    
    // Keep only last 10 messages
    var messages = debugPanel.children();
    if (messages.length > 10) {
        messages.first().remove();
    }
    
    // Auto scroll to bottom
    debugPanel.scrollTop(debugPanel[0].scrollHeight);
}

// === ENHANCED AUTO-SAVE FUNCTIONS ===
function forceSaveAllData() {
    // Forced synchronous save - wait for completion
    var noService = $('input[name="txtnosrv"]').val();
    if (!noService) {
        console.log('No service tidak ditemukan untuk force save');
        return;
    }
    
    console.log('🔄 Force saving all data...');
    addDebugMessage('Force saving all data (JEMPUT)...', 'process');
    
    // Collect all current form values
    var data = {
        kepala1: $('#cbokepala1').val(),
        kepala2: $('#cbokepala2').val(),
        persen_kepala1: $('#txtpersen_kepala1').val() || 0,
        persen_kepala2: $('#txtpersen_kepala2').val() || 0,
        admin1: $('#cboadmin1').val(),
        admin2: $('#cboadmin2').val(),
        persen_admin1: $('#txtpersen_admin1').val() || 0,
        persen_admin2: $('#txtpersen_admin2').val() || 0,
        mekanik1: $('#cbomekanik1').val(),
        mekanik2: $('#cbomekanik2').val(),
        mekanik3: $('#cbomekanik3').val(),
        mekanik4: $('#cbomekanik4').val(),
        persen_mekanik1: $('#txtpersen_kerja1').val() || 0,
        persen_mekanik2: $('#txtpersen_kerja2').val() || 0,
        persen_mekanik3: $('#txtpersen_kerja3').val() || 0,
        persen_mekanik4: $('#txtpersen_kerja4').val() || 0,
        km_skr: $('#txtkm_skr').val() || 0,
        km_berikut: $('#txtkm_next').val() || 0
    };
    
    // Force save with synchronous AJAX
    var savePromises = [];
    
    // Save kepala mekanik
    if (data.kepala1) {
        savePromises.push(forceSaveMekanikData('kepala_mekanik1', data.kepala1, data.persen_kepala1));
    }
    if (data.kepala2) {
        savePromises.push(forceSaveMekanikData('kepala_mekanik2', data.kepala2, data.persen_kepala2));
    }
    
    // Save admin
    if (data.admin1) {
        savePromises.push(forceSaveMekanikData('admin1', data.admin1, data.persen_admin1));
    }
    if (data.admin2) {
        savePromises.push(forceSaveMekanikData('admin2', data.admin2, data.persen_admin2));
    }
    
    // Save mekanik
    if (data.mekanik1) {
        savePromises.push(forceSaveMekanikData('mekanik1', data.mekanik1, data.persen_mekanik1));
    }
    if (data.mekanik2) {
        savePromises.push(forceSaveMekanikData('mekanik2', data.mekanik2, data.persen_mekanik2));
    }
    if (data.mekanik3) {
        savePromises.push(forceSaveMekanikData('mekanik3', data.mekanik3, data.persen_mekanik3));
    }
    if (data.mekanik4) {
        savePromises.push(forceSaveMekanikData('mekanik4', data.mekanik4, data.persen_mekanik4));
    }
    
    // Save KM data
    if (data.km_skr || data.km_berikut) {
        savePromises.push(forceSaveKMData());
    }
    
    return Promise.all(savePromises);
}

function forceSaveMekanikData(fieldType, fieldValue, fieldPercentage) {
    var noService = $('input[name="txtnosrv"]').val();
    
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '_ajax/auto_save_mekanik.php',
            type: 'POST',
            async: false, // Synchronous for force save
            data: {
                no_service: noService,
                field_type: fieldType,
                field_value: fieldValue,
                field_percentage: fieldPercentage
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    console.log('✅ Force saved: ' + fieldType);
                    addDebugMessage('Saved: ' + fieldType + ' = ' + fieldValue + ' (' + fieldPercentage + '%)', 'success');
                    resolve(response);
                } else {
                    console.log('❌ Force save failed: ' + fieldType);
                    reject(response);
                }
            },
            error: function(xhr, status, error) {
                console.log('❌ AJAX Error force save: ' + error);
                reject(error);
            }
        });
    });
}

function forceSaveKMData() {
    var noService = $('input[name="txtnosrv"]').val();
    var kmSkr = $('#txtkm_skr').val() || 0;
    var kmBerikut = $('#txtkm_next').val() || 0;
    
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '_ajax/auto_save_km.php',
            type: 'POST',
            async: false, // Synchronous for force save
            data: {
                no_service: noService,
                km_skr: kmSkr,
                km_berikut: kmBerikut
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    console.log('✅ Force saved KM data');
                    resolve(response);
                } else {
                    console.log('❌ Force save KM failed');
                    reject(response);
                }
            },
            error: function(xhr, status, error) {
                console.log('❌ AJAX Error force save KM: ' + error);
                reject(error);
            }
        });
    });
}

function autoSaveAllData() {
    var noService = $('input[name="txtnosrv"]').val();
    if (!noService) {
        console.log('No service tidak ditemukan untuk auto-save');
        return;
    }
    
    // Auto-save kepala mekanik data
    var kepala1 = $('#cbokepala1').val();
    var kepala2 = $('#cbokepala2').val();
    var persen_kepala1 = $('#txtpersen_kepala1').val() || 0;
    var persen_kepala2 = $('#txtpersen_kepala2').val() || 0;
    
    if (kepala1) {
        autoSaveMekanikData('kepala_mekanik1', kepala1, persen_kepala1);
    }
    if (kepala2) {
        autoSaveMekanikData('kepala_mekanik2', kepala2, persen_kepala2);
    }
    
    // Auto-save admin data
    var admin1 = $('#cboadmin1').val();
    var admin2 = $('#cboadmin2').val();
    var persen_admin1 = $('#txtpersen_admin1').val() || 0;
    var persen_admin2 = $('#txtpersen_admin2').val() || 0;
    
    if (admin1) {
        autoSaveMekanikData('admin1', admin1, persen_admin1);
    }
    if (admin2) {
        autoSaveMekanikData('admin2', admin2, persen_admin2);
    }
    
    // Auto-save mekanik data
    var mekanik1 = $('#cbomekanik1').val();
    var mekanik2 = $('#cbomekanik2').val();
    var mekanik3 = $('#cbomekanik3').val();
    var mekanik4 = $('#cbomekanik4').val();
    var persen_mekanik1 = $('#txtpersen_kerja1').val() || 0;
    var persen_mekanik2 = $('#txtpersen_kerja2').val() || 0;
    var persen_mekanik3 = $('#txtpersen_kerja3').val() || 0;
    var persen_mekanik4 = $('#txtpersen_kerja4').val() || 0;
    
    if (mekanik1) {
        autoSaveMekanikData('mekanik1', mekanik1, persen_mekanik1);
    }
    if (mekanik2) {
        autoSaveMekanikData('mekanik2', mekanik2, persen_mekanik2);
    }
    if (mekanik3) {
        autoSaveMekanikData('mekanik3', mekanik3, persen_mekanik3);
    }
    if (mekanik4) {
        autoSaveMekanikData('mekanik4', mekanik4, persen_mekanik4);
    }
    
    // Auto-save KM data
    var kmSkr = $('#txtkm_skr').val() || 0;
    var kmBerikut = $('#txtkm_next').val() || 0;
    
    if (kmSkr || kmBerikut) {
        autoSaveKMData();
    }
}

// === AUTO CALCULATE ON PAGE LOAD ===
$(document).ready(function() {
    // Calculate percentages when page loads
    calculatePercentageKepala();
    calculatePercentageAdmin();
    calculatePercentageMekanik();
    
    // Auto-save berkala setiap 3 detik untuk memastikan data tidak hilang
    setInterval(function() {
        autoSaveAllData();
    }, 3000);
    
    // Force save saat user akan meninggalkan halaman
    $(window).on('beforeunload', function(e) {
        forceSaveAllData();
        // Optional: Show warning if there's unsaved data
        return 'Data sedang disimpan, mohon tunggu...';
    });
    
    // Auto-save saat ada perubahan focus (pindah ke element lain)
    $('input, select').on('blur', function() {
        autoSaveAllData();
    });
    
    // Force save saat form akan di-submit
    $('form').on('submit', function(e) {
        e.preventDefault(); // Prevent default submit
        var form = this;
        
        console.log('🔄 Form submitting - force saving data first...');
        
        forceSaveAllData().then(function() {
            console.log('✅ All data saved successfully, proceeding with form submit');
            // Remove the event handler temporarily to avoid infinite loop
            $(form).off('submit').submit();
        }).catch(function(error) {
            console.log('❌ Error saving data:', error);
            // Still allow form submission even if save fails
            $(form).off('submit').submit();
        });
    });
    
    // Force save saat klik tombol navigasi atau link
    $('a, button[type="submit"]').on('click', function(e) {
        var $this = $(this);
        
        // Skip if it's a non-navigation button
        if ($this.hasClass('btn-xs') || $this.attr('onclick')) {
            return; // Don't interfere with edit/delete SPK buttons
        }
        
        e.preventDefault();
        var href = $this.attr('href');
        var isSubmit = $this.attr('type') === 'submit';
        
        console.log('🔄 Navigation detected - force saving data...');
        
        forceSaveAllData().then(function() {
            console.log('✅ Data saved, proceeding with navigation');
            if (href && href !== '#') {
                window.location.href = href;
            } else if (isSubmit) {
                $this.closest('form').off('submit').submit();
            }
        }).catch(function(error) {
            console.log('❌ Error saving data, but proceeding with navigation');
            if (href && href !== '#') {
                window.location.href = href;
            } else if (isSubmit) {
                $this.closest('form').off('submit').submit();
            }
        });
    });
    
    // Auto-calculate and auto-save when values change for Kepala Mekanik
    $('#txtpersen_kepala1, #txtpersen_kepala2').on('keyup change', function() {
        calculatePercentageKepalaWithSave();
    });
    
    // Auto-calculate and auto-save when values change for Admin
    $('#txtpersen_admin1, #txtpersen_admin2').on('keyup change', function() {
        calculatePercentageAdminWithSave();
    });
    
    // Auto-calculate and auto-save when values change for Mekanik
    $('#txtpersen_kerja1, #txtpersen_kerja2, #txtpersen_kerja3, #txtpersen_kerja4').on('keyup change', function() {
        calculatePercentageMekanikWithSave();
    });
    
    // Auto-save KM data when changed
    $('#txtkm_skr, #txtkm_next').on('change blur', function() {
        autoSaveKMData();
    });
    
    // Auto-save when dropdown selections change
    $('#cbokepala1, #cbokepala2').on('change', function() {
        calculatePercentageKepalaWithSave();
    });
    
    $('#cboadmin1, #cboadmin2').on('change', function() {
        calculatePercentageAdminWithSave();
    });
    
    $('#cbomekanik1, #cbomekanik2, #cbomekanik3, #cbomekanik4').on('change', function() {
        calculatePercentageMekanikWithSave();
    });
});
</script>