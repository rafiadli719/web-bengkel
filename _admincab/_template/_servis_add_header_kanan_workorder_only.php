<?php
// File: _servis_add_header_kanan_workorder_only.php
// Template untuk tab Workorder - TANPA persentase & tombol simpan
// Hanya berisi: Input SPK, Daftar SPK, dan Riwayat Service


// Ambil data status jemput jika ada
$status_jemput = '0';

if(isset($no_service) && isset($koneksi)) {
    try {
        $cari_data = mysqli_query($koneksi,"SELECT status_jemput
                                     FROM tblservice WHERE no_service='$no_service'");
        if($cari_data && $tm_data = mysqli_fetch_array($cari_data)) {
            $status_jemput = $tm_data['status_jemput'] ?? '0';
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
                                <option value="1" <?php echo (isset($status_jemput) && $status_jemput == '1') ? 'selected' : ''; ?>>Dijemput</option>
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
                        <strong>Nopol: <?php echo htmlspecialchars($no_polisi ?? '-'); ?></strong>
                        <span class="pull-right">
                            <button type="button" class="btn btn-xs btn-info" onclick="refreshSPK()" title="Refresh Daftar SPK">
                                <i class="ace-icon fa fa-refresh"></i> Refresh
                            </button>
                        </span>
                        <div class="clearfix"></div>
                        <small>Input keluhan dan work order untuk servis reguler</small>
                    </div>
                </div>
            </div>

            <!-- Form Input Section untuk SPK -->
            <div class="row" style="margin-bottom: 20px;">
                <!-- Input Keluhan & Work Order Section (ATAS) -->
                <div class="col-xs-12 col-sm-12">
                    <div class="widget-box widget-color-green" style="margin-bottom: 15px;">
                        <div class="widget-header widget-header-small">
                            <h5 class="widget-title">
                                <i class="ace-icon fa fa-plus-circle"></i>
                                Input Keluhan & Work Order
                            </h5>
                        </div>
                        <div class="widget-body">
                            <div class="widget-main" style="padding: 15px;">
                                <!-- Input Keluhan (ATAS) -->
                                <div class="row" style="margin-bottom: 20px;">
                                    <div class="col-xs-12 col-sm-12">
                                        <h6 class="header orange smaller" style="margin-top: 0;">
                                            <i class="ace-icon fa fa-exclamation-triangle"></i> Input Keluhan
                                        </h6>
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-8">
                                                <label>Keluhan:</label>
                                                <input type="hidden" name="kode_keluhan" id="kode_keluhan" />
                                                <div class="input-group">
                                                    <input type="text" name="txtkeluhan" id="txtkeluhan" class="form-control" 
                                                           placeholder="Ketik keluhan atau pilih dari master (klik Cari Keluhan)" />
                                                    <span class="input-group-btn">
                                                        <button type="button" class="btn btn-info" id="btn-cari-keluhan">
                                                            <i class="ace-icon fa fa-search"></i> Cari Keluhan
                                                        </button>
                                                    </span>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fa fa-info-circle"></i> 
                                                    Gunakan tombol "Cari Keluhan" untuk memilih dari master data keluhan yang tersedia
                                                </small>
                                                <div style="margin-top: 8px;">
                                                    <button type="button" class="btn btn-warning btn-sm" id="btn-tambah-keluhan-baru">
                                                        <i class="ace-icon fa fa-plus-circle"></i> Tambah Keluhan Baru (Perlu Approval Pusat)
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-4">
                                                <label>&nbsp;</label>
                                                <button class="btn btn-primary btn-block" type="submit" formnovalidate
                                                        name="btnaddkeluhan" id="btnaddkeluhan" onclick="return validateKeluhan()">
                                                    <i class="ace-icon fa fa-plus"></i> Tambah Keluhan
                                                </button>
                                            </div>
                                        </div>
                                        <div class="space space-8"></div>
                                        
                                        <!-- Tabel Daftar Keluhan yang Ditambahkan -->
                                        <h6 class="header green smaller">
                                            <i class="ace-icon fa fa-list"></i>
                                            Daftar Keluhan yang Ditambahkan
                                        </h6>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-striped table-bordered table-hover" id="tabel-keluhan-service">
                                                <thead>
                                                    <tr class="info">
                                                        <th width="5%" class="text-center">No</th>
                                                        <th width="30%">Keluhan</th>
                                                        <th width="15%" class="text-center">Status</th>
                                                        <th width="25%">Keterangan</th>
                                                        <th width="25%" class="text-center">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody-keluhan-service">
                                                    <?php
                                                    // Ambil data keluhan dengan status dari view
                                                    if(isset($no_service) && isset($koneksi) && !empty($no_service)) {
                                                        try {
                                                            // Coba load dari VIEW jika ada, jika tidak ada fallback ke tabel status langsung
                                                            $result_keluhan = mysqli_query($koneksi, "SELECT * FROM view_servis_keluhan_lengkap WHERE no_service = '$no_service' ORDER BY created_at DESC");
                                                            if(!$result_keluhan){
                                                                // Fallback sederhana (tanpa kolom badge dari VIEW)
                                                                $result_keluhan = mysqli_query($koneksi, "SELECT id, keluhan, status_pengerjaan, keterangan_tidak_selesai, created_at FROM tbservis_keluhan_status WHERE no_service='$no_service' ORDER BY created_at DESC");
                                                            }

                                                            if($result_keluhan && mysqli_num_rows($result_keluhan) > 0) {
                                                                $no = 1;
                                                                while($row_keluhan = mysqli_fetch_array($result_keluhan)) {
                                                                    // Highlight row jika tidak selesai
                                                                    $row_class = ($row_keluhan['status_pengerjaan'] == 'tidak_selesai') ? 'class="danger"' : '';
                                                                    
                                                                    // Icon status
                                                                    $status_icon = '';
                                                                    switch($row_keluhan['status_pengerjaan']) {
                                                                        case 'selesai': $status_icon = '<i class="ace-icon fa fa-check"></i> '; break;
                                                                        case 'diproses': $status_icon = '<i class="ace-icon fa fa-cog fa-spin"></i> '; break;
                                                                        case 'tidak_selesai': $status_icon = '<i class="ace-icon fa fa-times"></i> '; break;
                                                                        case 'datang': $status_icon = '<i class="ace-icon fa fa-clock-o"></i> '; break;
                                                                    }
                                                                    // Badge color fallback jika kolom view tidak tersedia
                                                                    $badge_color = isset($row_keluhan['status_badge_color']) ? $row_keluhan['status_badge_color'] : (
                                                                        $row_keluhan['status_pengerjaan'] == 'selesai' ? 'success' : (
                                                                        $row_keluhan['status_pengerjaan'] == 'diproses' ? 'warning' : (
                                                                        $row_keluhan['status_pengerjaan'] == 'tidak_selesai' ? 'danger' : 'info')));

                                                                    echo "<tr $row_class>";
                                                                    echo "<td class='text-center'>" . $no . "</td>";
                                                                    echo "<td>" . htmlspecialchars($row_keluhan['keluhan']) . "</td>";
                                                                    echo "<td class='text-center'>";
                                                                    echo "<span class='label label-" . $badge_color . "'>";
                                                                    echo $status_icon . strtoupper($row_keluhan['status_pengerjaan']);
                                                                    echo "</span>";
                                                                    echo "</td>";
                                                                    echo "<td>";
                                                                    if($row_keluhan['status_pengerjaan'] == 'tidak_selesai' && !empty($row_keluhan['keterangan_tidak_selesai'])) {
                                                                        echo "<strong class='text-danger'>" . htmlspecialchars($row_keluhan['keterangan_tidak_selesai']) . "</strong>";
                                                                    } else {
                                                                        echo "<em class='text-muted'>-</em>";
                                                                    }
                                                                    echo "</td>";
                                                                    echo "<td class='text-center'>";
                                                                    echo "<button type='button' class='btn btn-xs btn-info btn-update-status-keluhan' ";
                                                                    echo "data-id='" . $row_keluhan['id'] . "' ";
                                                                    echo "data-keluhan='" . htmlspecialchars($row_keluhan['keluhan'], ENT_QUOTES) . "' ";
                                                                    echo "data-status='" . $row_keluhan['status_pengerjaan'] . "' ";
                                                                    echo "data-keterangan='" . htmlspecialchars($row_keluhan['keterangan_tidak_selesai'] ?? '', ENT_QUOTES) . "' ";
                                                                    echo "title='Update Status'>";
                                                                    echo "<i class='ace-icon fa fa-edit'></i>";
                                                                    echo "</button> ";
                                                                    echo "<button type='button' class='btn btn-xs btn-danger' onclick='hapusKeluhan(" . $row_keluhan['id'] . ")' title='Hapus Keluhan'>";
                                                                    echo "<i class='ace-icon fa fa-trash'></i>";
                                                                    echo "</button>";
                                                                    echo "</td>";
                                                                    echo "</tr>";
                                                                    $no++;
                                                                }
                                                            } else {
                                                                echo "<tr>";
                                                                echo "<td colspan='5' class='text-center text-muted'>";
                                                                echo "<i class='fa fa-info-circle'></i> Belum ada keluhan yang ditambahkan untuk service ini";
                                                                echo "</td>";
                                                                echo "</tr>";
                                                            }
                                                        } catch (Exception $e) {
                                                            echo "<tr>";
                                                            echo "<td colspan='5' class='text-center text-danger'>";
                                                            echo "<i class='fa fa-info-circle'></i> Tidak ada data keluhan";
                                                            echo "</td>";
                                                            echo "</tr>";
                                                        }
                                                    } else {
                                                        echo "<tr>";
                                                        echo "<td colspan='5' class='text-center text-muted'>";
                                                        echo "<i class='fa fa-info-circle'></i> Simpan service terlebih dahulu untuk menambahkan keluhan";
                                                        echo "</td>";
                                                        echo "</tr>";
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Button untuk refresh tabel -->
                                        <div class="text-right" style="margin-top: 10px;">
                                            <button type="button" class="btn btn-sm btn-info" onclick="refreshTabelKeluhan()">
                                                <i class="ace-icon fa fa-refresh"></i> Refresh Tabel
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Input Work Order (BAWAH) -->
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12">
                                        <h6 class="header blue smaller" style="margin-top: 0;">
                                            <i class="ace-icon fa fa-cogs"></i> Input Work Order
                                        </h6>
                                        <table class="table table-bordered" style="margin-bottom: 15px;">
                                            <tr>
                                                <td width="70%">
                                                    <label>Kode Work Order :</label>
                                                    <input type="text" class="form-control input-sm"
                                                    id="txtcariwo" name="txtcariwo"
                                                    value="<?php echo $txtcariwo; ?>"
                                                    placeholder="Masukkan kode WO" autocomplete="off"
                                                    <?php echo !empty($is_readonly_wo) ? 'disabled' : ''; ?> />
                                                </td>
                                                <td width="30%">
                                                    <label>&nbsp;</label><br>
                                                    <button class="btn btn-primary btn-sm btn-block" type="submit" formnovalidate
                                                    id="btncariwo" name="btncariwo"
                                                    <?php echo !empty($is_readonly_wo) ? 'disabled' : ''; ?>>
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
                                                    <button class="btn btn-success btn-sm btn-block" type="submit" formnovalidate
                                                    id="btnaddworkorder" name="btnaddworkorder" onclick="return validateWorkOrder()"
                                                    <?php echo !empty($is_readonly_wo) ? 'disabled' : ''; ?>>
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
                                <div class="alert alert-info" style="margin-bottom: 15px; padding: 10px;">
                                    <div class="row">
                                        <div class="col-xs-8">
                                            <strong>Total: <?php echo $total_spk; ?> SPK</strong>
                                            <br><small>(<?php echo $no_keluhan; ?> keluhan + <?php echo $no_wo; ?> work order)</small>
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
                                                    echo "<li style='margin-bottom: 10px; padding: 8px; border-left: 4px solid #f39c12; background-color: #fef9e7; font-size: 14px; position: relative;'>";
                                                    echo "<div style='display: flex; justify-content: between; align-items: center;'>";
                                                    echo "<span style='flex: 1; font-weight: bold; color: #333;'>";
                                                    echo "<span style='display: inline-block; width: 30px; color: #f39c12; font-weight: bold;'>" . $counter . ".</span>";
                                                    echo "<span class='keluhan-text-" . $tampil['id'] . "'>" . htmlspecialchars($tampil['keluhan']) . "</span>";
                                                    echo "</span>";
                                                    echo "<div class='spk-actions' style='margin-left: 10px;'>";
                                                    echo "<button type='button' class='btn btn-xs btn-danger' onclick='hapusKeluhan(" . $tampil['id'] . ")' title='Hapus Keluhan'>";
                                                    echo "<i class='ace-icon fa fa-trash'></i>";
                                                    echo "</button>";
                                                    echo "</div>";
                                                    echo "</div>";
                                                    echo "</li>";
                                                    $counter++;
                                                }
                                            }

                                            // Tampilkan work order dengan status
                                            $sql_wo_list = mysqli_query($koneksi,"SELECT * FROM view_servis_workorder_lengkap WHERE no_service='$no_service' ORDER BY created_at DESC");
                                            if($sql_wo_list) {
                                                while ($tampil = mysqli_fetch_array($sql_wo_list)) {
                                                    // Tentukan warna border berdasarkan status
                                                    $border_color = '#3498db';
                                                    $bg_color = '#ebf3fd';
                                                    if($tampil['status_pengerjaan'] == 'selesai') {
                                                        $border_color = '#27ae60';
                                                        $bg_color = '#e8f8f5';
                                                    } elseif($tampil['status_pengerjaan'] == 'tidak_selesai') {
                                                        $border_color = '#e74c3c';
                                                        $bg_color = '#fadbd8';
                                                    }
                                                    
                                                    echo "<li style='margin-bottom: 10px; padding: 8px; border-left: 4px solid $border_color; background-color: $bg_color; font-size: 14px; position: relative;'>";
                                                    echo "<div style='display: flex; justify-content: space-between; align-items: center;'>";
                                                    echo "<span style='flex: 1; font-weight: bold; color: #333;'>";
                                                    echo "<span style='display: inline-block; width: 30px; color: $border_color; font-weight: bold;'>" . $counter . ".</span>";
                                                    echo htmlspecialchars($tampil['nama_wo']);
                                                    // Tampilkan badge status
                                                    echo " <span class='label label-" . $tampil['status_badge_color'] . "' style='margin-left: 5px;'>";
                                                    echo strtoupper($tampil['status_pengerjaan']);
                                                    echo "</span>";
                                                    // Tampilkan progress
                                                    $progress = $tampil['progress_percentage'];
                                                    $progress_color = $progress == 100 ? 'success' : ($progress >= 50 ? 'warning' : 'danger');
                                                    echo "<br><small style='color: #666;'>";
                                                    echo "<div class='progress' style='height: 15px; margin: 5px 0 0 0; width: 200px;'>";
                                                    echo "<div class='progress-bar progress-bar-$progress_color' style='width: $progress%;'>";
                                                    echo "<small>$progress%</small>";
                                                    echo "</div>";
                                                    echo "</div>";
                                                    echo "</small>";
                                                    echo "</span>";
                                                    echo "<div class='spk-actions' style='margin-left: 10px;'>";
                                                    echo "<button type='button' class='btn btn-xs btn-info btn-update-status-wo' ";
                                                    echo "data-id='" . $tampil['id'] . "' ";
                                                    echo "data-kode='" . $tampil['kode_wo'] . "' ";
                                                    echo "data-nama='" . htmlspecialchars($tampil['nama_wo'], ENT_QUOTES) . "' ";
                                                    echo "data-status='" . $tampil['status_pengerjaan'] . "' ";
                                                    echo "data-keterangan='" . htmlspecialchars($tampil['keterangan_tidak_selesai'], ENT_QUOTES) . "' ";
                                                    echo "title='Update Status'>";
                                                    echo "<i class='ace-icon fa fa-edit'></i>";
                                                    echo "</button> ";
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
                                        <i class="ace-icon fa fa-info-circle"></i>
                                        Belum ada SPK untuk Nopol: <strong><?php echo htmlspecialchars($no_polisi); ?></strong>
                                        <br><small>Tambahkan keluhan atau work order di atas</small>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space space-8"></div>

            <!-- Button Riwayat Kendaraan -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <button type="button" class="btn btn-purple btn-block" data-toggle="modal" data-target="#modalRiwayatKendaraan" style="text-align: left; position: relative; padding: 15px 20px; font-size: 14px;">
                        <i class="ace-icon fa fa-history"></i> 
                        <strong>Lihat Riwayat Service & Mekanik Kendaraan</strong>
                        <span style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%);">
                            <i class="ace-icon fa fa-arrow-circle-right"></i>
                        </span>
                    </button>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Hidden fields untuk form submission -->
            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>" />
            <input type="hidden" name="txtcarisrv" value="<?php echo isset($txtcarisrv) ? $txtcarisrv : ''; ?>" />
            <input type="hidden" name="txtcaribrg" value="<?php echo isset($txtcaribrg) ? $txtcaribrg : ''; ?>" />
            <input type="hidden" name="txtcariwo" value="<?php echo isset($txtcariwo) ? $txtcariwo : ''; ?>" />
            <!-- Hidden KM fields untuk preserve nilai saat submit dari workorder tab -->
            <input type="hidden" name="txtkm_skr" id="hidden_txtkm_skr" value="0" />
            <input type="hidden" name="txtkm_next" id="hidden_txtkm_next" value="0" />

        </div>
    </div>
</div>

<script type="text/javascript">
// === SPK FUNCTIONS ===
// Wait until jQuery is available, then initialize
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initWorkorderFunctions);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initWorkorderFunctions() {
        var $ = jQuery; // Alias for safety

        // === GLOBAL FUNCTIONS (attached to window for onclick handlers) ===
        window.showModalSearchKeluhan = function() {
            // Implementasi modal search keluhan
            if(typeof $('#modal-search-keluhan').modal === 'function') {
                $('#modal-search-keluhan').modal('show');
            } else {
                alert('Modal search keluhan belum tersedia');
            }
        };

        // Validation functions
        window.validateKeluhan = function() {
            var kodeKeluhan = (document.getElementById('kode_keluhan')||{}).value || '';
            var txtKeluhan = (document.getElementById('txtkeluhan')||{}).value || '';
            kodeKeluhan = String(kodeKeluhan).trim();
            txtKeluhan = String(txtKeluhan).trim();
            if(!kodeKeluhan && !txtKeluhan){
                alert('Masukkan atau pilih keluhan terlebih dahulu!');
                return false;
            }
            return true;
        };

        window.validateWorkOrder = function() {
            var kodeWO = document.getElementById('txtcariwo').value.trim();
            if(kodeWO === '') {
                alert('Kode Work Order tidak boleh kosong!');
                document.getElementById('txtcariwo').focus();
                return false;
            }
            return true;
        };

        window.selectKeluhan = function(keluhan) {
            // Set keluhan to the input field
            var keluhanInput = document.querySelector('input[name="txtkeluhan"]');
            if (keluhanInput) {
                keluhanInput.value = keluhan;
            }
            $('#modal-search-keluhan').modal('hide');
        };

        window.refreshSPK = function() {
            // Refresh halaman untuk memperbarui daftar SPK
            window.location.reload();
        };

        // === SPK EDIT/HAPUS FUNCTIONS ===
        window.editKeluhan = function(id, keluhanLama) {
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
        };

        window.hapusKeluhan = function(id) {
            if (!confirm('Yakin ingin menghapus keluhan ini?')) {
                return;
            }
            
            // AJAX request to delete keluhan
            $.ajax({
                url: 'keluhan-hapus.php',
                type: 'POST',
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Keluhan berhasil dihapus!');
                        refreshTabelKeluhan();
                    } else {
                        alert('Error: ' + (response.message || 'Gagal menghapus keluhan'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error: Gagal menghapus keluhan. ' + error);
                }
            });
        };
        
        window.refreshTabelKeluhan = function() {
            var noService = '<?php echo $no_service ?? ""; ?>';
            if (!noService) {
                return;
            }
            
            // AJAX request to refresh table
            $.ajax({
                url: 'ajax-get-keluhan-service.php',
                type: 'GET',
                data: {
                    no_service: noService
                },
                success: function(response) {
                    $('#tbody-keluhan-service').html(response);
                },
                error: function(xhr, status, error) {
                    console.log('Error refreshing keluhan table: ' + error);
                }
            });
        };

        window.hapusWorkOrder = function(id) {
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
                        window.refreshSPK(); // Refresh untuk update tampilan
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX Error: ' + error);
                }
            });
        };

        window.updateJenisService = function() {
            // Function untuk update jenis service
            var statusJemput = document.getElementById('status_jemput').value;
            console.log('Jenis service diubah ke: ' + statusJemput);
            // Optional: Tambahkan logika tambahan jika diperlukan
        };

        // Validation function for keluhan (duplicate removal - already defined above)
        // window.validateKeluhan - removed duplicate

        // Validation function for work order
        window.validateWorkOrder = function() {
            var kodeWO = $('#txtcariwo').val().trim();
            if (!kodeWO) {
                alert('Mohon masukkan kode Work Order terlebih dahulu!');
                $('#txtcariwo').focus();
                return false;
            }
            return true;
        };

        // Show modal search keluhan
        window.showModalSearchKeluhan = function() {
            if(typeof $('#modal-search-keluhan').modal === 'function') {
                $('#modal-search-keluhan').modal('show');
            } else {
                // Fallback if modal not available
                alert('Modal search keluhan belum tersedia. Silakan ketik keluhan secara manual.');
            }
        };
        
        window.selectKeluhan = function(keluhan) {
            // Set keluhan to the input field
            var keluhanInput = document.querySelector('input[name="txtkeluhan"]');
            if (keluhanInput) {
                keluhanInput.value = keluhan;
            }
            $('#modal-search-keluhan').modal('hide');
        };

        // Sync KM values from Actions tab to hidden fields in Workorder tab
        window.syncKMValues = function() {
            // Get values from Actions tab (if they exist)
            var kmSkrActions = $('#txtkm_skr').val();
            var kmNextActions = $('#txtkm_next').val();

            // Update hidden fields in Workorder tab
            if(typeof kmSkrActions !== 'undefined' && kmSkrActions !== '') {
                $('#hidden_txtkm_skr').val(kmSkrActions);
            }
            if(typeof kmNextActions !== 'undefined' && kmNextActions !== '') {
                $('#hidden_txtkm_next').val(kmNextActions);
            }
        };
        
        // ========== STATUS UPDATE HANDLERS ==========
        // Open modal update status keluhan
        $(document).on('click', '.btn-update-status-keluhan', function() {
            var id = $(this).data('id');
            var keluhan = $(this).data('keluhan');
            var status = $(this).data('status');
            var keterangan = $(this).data('keterangan');
            
            $('#keluhan_id').val(id);
            $('#keluhan_text').val(keluhan);
            $('#status_keluhan').val(status);
            $('#keterangan_keluhan').val(keterangan);
            
            // Show/hide keterangan field
            if(status == 'tidak_selesai') {
                $('#keterangan_keluhan_group').show();
            } else {
                $('#keterangan_keluhan_group').hide();
            }
            
            $('#modalUpdateStatusKeluhan').modal('show');
        });
        
        // Show/hide keterangan field for Keluhan
        $('#status_keluhan').change(function() {
            if($(this).val() == 'tidak_selesai') {
                $('#keterangan_keluhan_group').slideDown();
                $('#keterangan_keluhan').attr('required', true);
            } else {
                $('#keterangan_keluhan_group').slideUp();
                $('#keterangan_keluhan').attr('required', false);
                $('#keterangan_keluhan').val('');
            }
        });
        
        // Open modal update status work order
        $(document).on('click', '.btn-update-status-wo', function() {
            var id = $(this).data('id');
            var kode = $(this).data('kode');
            var nama = $(this).data('nama');
            var status = $(this).data('status');
            var keterangan = $(this).data('keterangan');
            
            $('#wo_id').val(id);
            $('#wo_text').val(kode + ' - ' + nama);
            $('#status_wo').val(status);
            $('#keterangan_wo').val(keterangan);
            
            // Show/hide keterangan field
            if(status == 'tidak_selesai') {
                $('#keterangan_wo_group').show();
            } else {
                $('#keterangan_wo_group').hide();
            }
            
            $('#modalUpdateStatusWO').modal('show');
        });
        
        // Show/hide keterangan field for Work Order
        $('#status_wo').change(function() {
            if($(this).val() == 'tidak_selesai') {
                $('#keterangan_wo_group').slideDown();
                $('#keterangan_wo').attr('required', true);
            } else {
                $('#keterangan_wo_group').slideUp();
                $('#keterangan_wo').attr('required', false);
                $('#keterangan_wo').val('');
            }
        });
    }

// Auto-sync KM values when Actions tab inputs change (wait for jQuery)
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initWorkorderKMSync);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initWorkorderKMSync() {
        var $ = jQuery; // Alias for safety

        // Sync on page load
        syncKMValues();

        // Sync when KM inputs in Actions tab change
        $(document).on('change blur', '#txtkm_skr, #txtkm_next', function() {
            syncKMValues();
        });

        // Sync before form submission
        $('form').on('submit', function() {
            syncKMValues();
        });
    }

</script>

<!-- Script untuk Modal Search Keluhan - Defer jQuery -->
<script type="text/javascript">
// Fungsi untuk membuka modal search keluhan
// Pastikan inisialisasi hanya berjalan setelah jQuery benar-benar tersedia
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initModalKeluhan);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initModalKeluhan() {
    var $ = jQuery; // Alias for safety
    
    // Make functions global
    window.showModalSearchKeluhan = function() {
        $('#modal-search-keluhan').modal('show');
    };
    
    window.pilihKeluhan = function(nama_keluhan) {
        $('#txtkeluhan').val(nama_keluhan);
        $('#modal-search-keluhan').modal('hide');
    };
    
    // Attach event listener to button
    $('#btn-cari-keluhan').on('click', function() {
        console.log('Button Cari Keluhan clicked');
        $('#modal-search-keluhan').modal('show');
    });
    
    // Attach event listener for direct add new keluhan
    $('#btn-tambah-keluhan-baru').on('click', function() {
        console.log('Button Tambah Keluhan Baru clicked');
        // Use global function if available, otherwise direct modal call
        if (typeof window.openModalTambahKeluhanBaru === 'function') {
            window.openModalTambahKeluhanBaru();
        } else {
            // Fallback
            if ($('#modal-tambah-keluhan-baru').length > 0) {
                $('#modal-tambah-keluhan-baru').modal('show');
            } else {
                alert('Modal belum siap. Silakan refresh halaman.');
            }
        }
    });
}
</script>

<!-- Include Modal Search Keluhan dengan Approval -->
<?php 
// File ini ada di folder _template, jadi include dari folder yang sama
$modal_file = __DIR__ . '/modal-search-keluhan.php';
if(file_exists($modal_file)) {
    include $modal_file;
} else {
    echo "<!-- ERROR: modal-search-keluhan.php not found at: $modal_file -->";
}
?>

<!-- Modal Tambah Keluhan Baru sudah dipindahkan ke servis-input-reguler.php (di luar form parent) -->
<!-- Tidak ada modal di sini lagi untuk menghindari nested form -->
<script>console.log('✅ Modal tambah keluhan ada di akhir body (servis-input-reguler.php), di luar form parent');</script>
