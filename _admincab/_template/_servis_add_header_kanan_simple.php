<?php
// Simplified and safe version of right header template
// Initialize variables with default values
$kepala1 = '';
$kepala2 = '';
$persen_kepala1 = 0;
$persen_kepala2 = 0;

// Safe database query with error handling
if(isset($no_service) && isset($koneksi) && $koneksi) {
    try {
        $stmt = mysqli_prepare($koneksi, "SELECT kepala_mekanik1, kepala_mekanik2, persen_kepala_mekanik1, persen_kepala_mekanik2 FROM tblservice WHERE no_service = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $no_service);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_array($result)) {
                $kepala1 = $row['kepala_mekanik1'] ?? '';
                $kepala2 = $row['kepala_mekanik2'] ?? '';
                $persen_kepala1 = $row['persen_kepala_mekanik1'] ?? 0;
                $persen_kepala2 = $row['persen_kepala_mekanik2'] ?? 0;
            }
            mysqli_stmt_close($stmt);
        }
    } catch (Exception $e) {
        // Silent error handling - continue with default values
    }
}
?>

<div class="widget-box">
    <div class="widget-body">
        <div class="widget-main">
            <!-- Basic Service Info -->
            <div class="row">
                <div class="col-xs-8 col-sm-6">
                    <label>No. Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                            value="<?php echo isset($no_service) ? htmlspecialchars($no_service) : ''; ?>" readonly="true" />
                        </div>
                    </div>
                </div>
                <div class="col-xs-8 col-sm-6">
                    <label>Jenis Service :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="text" class="form-control" 
                            value="SERVICE REGULER" readonly="true" style="background-color: #f8f9fa; font-weight: bold; color: #007bff;" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Simplified Mechanic Section -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header blue smaller">
                        <i class="ace-icon fa fa-user-md"></i>
                        Penanggung Jawab Servis
                    </h4>
                </div>
                
                <!-- Kepala Mekanik 1 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 1 <span style="color:red;">*</span>:</label>
                    <select class="form-control" name="cbokepala1" id="cbokepala1">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '1' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $selected = ($kepala1 == $row["nomekanik"]) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '" ' . $selected . '>' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        } else {
                            echo '<option value="">Database not available</option>';
                        }
                        ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala1" id="txtpersen_kepala1" 
                               value="<?php echo $persen_kepala1; ?>" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Kepala Mekanik 2 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 2 (Opsional):</label>
                    <select class="form-control" name="cbokepala2" id="cbokepala2">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '1' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        $selected = ($kepala2 == $row["nomekanik"]) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '" ' . $selected . '>' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        } else {
                            echo '<option value="">Database not available</option>';
                        }
                        ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Supervisi:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen_kepala2" id="txtpersen_kepala2" 
                               value="<?php echo $persen_kepala2; ?>" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Status Persentase Kepala Mekanik -->
            <div class="row">
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
                
                <!-- Admin/Kasir 1 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Admin/Kasir 1 <span style="color:red;">*</span>:</label>
                    <select class="form-control" name="cbomekanik1" id="cbomekanik1">
                        <option value="">- Pilih Admin/Kasir -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '2' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '">' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Kerja:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen1" id="txtpersen1" 
                               value="0" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Admin/Kasir 2 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Admin/Kasir 2 (Opsional):</label>
                    <select class="form-control" name="cbomekanik2" id="cbomekanik2">
                        <option value="">- Pilih Admin/Kasir -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '2' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '">' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
                                }
                            } catch (Exception $e) {
                                echo '<option value="">Error loading data</option>';
                            }
                        }
                        ?>
                    </select> 
                </div>
                <div class="col-xs-4 col-sm-4">
                    <label>% Kerja:</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="txtpersen2" id="txtpersen2" 
                               value="0" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Mekanik Pengerjaan -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header purple smaller">
                        <i class="ace-icon fa fa-wrench"></i>
                        Mekanik Pengerjaan
                    </h4>
                    <p><small><em>Mekanik yang melakukan pengerjaan service. Total persentase harus 100%</em></small></p>
                </div>
                
                <!-- Mekanik 1 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 1 <span style="color:red;">*</span>:</label>
                    <select class="form-control" name="cbomekanik_kerja1" id="cbomekanik_kerja1">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '3' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '">' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
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
                        <input type="number" class="form-control" name="txtpersen_kerja1" id="txtpersen_kerja1" 
                               value="0" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Mekanik 2 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 2 (Opsional):</label>
                    <select class="form-control" name="cbomekanik_kerja2" id="cbomekanik_kerja2">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '3' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '">' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
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
                        <input type="number" class="form-control" name="txtpersen_kerja2" id="txtpersen_kerja2" 
                               value="0" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Mekanik 3 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 3 (Opsional):</label>
                    <select class="form-control" name="cbomekanik_kerja3" id="cbomekanik_kerja3">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '3' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '">' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
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
                        <input type="number" class="form-control" name="txtpersen_kerja3" id="txtpersen_kerja3" 
                               value="0" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Mekanik 4 -->
                <div class="col-xs-8 col-sm-8">
                    <label>Mekanik 4 (Opsional):</label>
                    <select class="form-control" name="cbomekanik_kerja4" id="cbomekanik_kerja4">
                        <option value="">- Pilih Mekanik -</option>
                        <?php
                        if(isset($koneksi) && $koneksi) {
                            try {
                                $stmt = mysqli_prepare($koneksi, "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' AND keahlian = '3' ORDER BY nama ASC");
                                if ($stmt) {
                                    mysqli_stmt_execute($stmt);
                                    $result = mysqli_stmt_get_result($stmt);
                                    while($row = mysqli_fetch_assoc($result)) {
                                        echo '<option value="' . htmlspecialchars($row["nomekanik"]) . '">' . htmlspecialchars($row["nama"]) . '</option>';
                                    }
                                    mysqli_stmt_close($stmt);
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
                        <input type="number" class="form-control" name="txtpersen_kerja4" id="txtpersen_kerja4" 
                               value="0" min="0" max="100">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Status Persentase Mekanik Pengerjaan -->
                <div class="col-xs-12 col-sm-12">
                    <div class="space space-2"></div>
                    <div id="persentaseStatusMekanik" class="alert alert-info">
                        <i class="ace-icon fa fa-info-circle"></i>
                        <strong>Total % Pengerjaan: <span id="totalPersenMekanik">0</span>%</strong>
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
                            value="0" autocomplete="off" min="0" />
                        </div>
                    </div>
                </div>
                <div class="col-xs-8 col-sm-6">
                    <label>Km Berikut :</label>
                    <div class="row">
                        <div class="col-xs-8 col-sm-12">
                            <input type="number" class="form-control" 
                            id="txtkm_next" name="txtkm_next" 
                            value="0" autocomplete="off" min="0" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Keluhan -->
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
                            <button type="button" class="btn btn-info" onclick="openModalSearchKeluhan()">
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
            </div>
            <div class="space space-8"></div>

            <!-- Riwayat Service Kendaraan -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
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
                                if(isset($no_polisi) && isset($no_service) && isset($koneksi) && $koneksi) {
                                    try {
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
                                    <strong><?php echo htmlspecialchars($tampil['no_service'])?></strong>
                                </td>
                                <td class="center"><?php echo htmlspecialchars($tampil['tanggal_trx'])?></td>
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
                            <?php } 
                                    } catch (Exception $e) {
                            ?>
                            <tr>
                                <td colspan="4" class="center">
                                    <em>Error loading service history</em>
                                </td>
                            </tr>
                            <?php 
                                    }
                                } else {
                            ?>
                            <tr>
                                <td colspan="4" class="center">
                                    <em>Data tidak tersedia</em>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Success Message -->
            <div class="alert alert-success">
                <i class="fa fa-check-circle"></i>
                <strong>Template Enhanced:</strong> Header kanan dengan riwayat service dan search keluhan.
                <br><small>Versi lengkap dengan semua fungsi mekanik, admin, km, keluhan, dan riwayat service.</small>
            </div>
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
// Function untuk modal search keluhan
function openModalSearchKeluhan() {
    if(typeof $('#modal-search-keluhan') !== 'undefined') {
        $('#modal-search-keluhan').modal('show');
    } else {
        alert('Modal search keluhan tidak tersedia');
    }
}

// Function untuk memilih keluhan dari modal
function selectKeluhan(keluhan) {
    document.getElementById('txtkeluhan').value = keluhan;
    $('#modal-search-keluhan').modal('hide');
}

// Function untuk kepala mekanik percentage calculation
function calculatePercentageKepala() {
    var persen_kepala1 = parseInt(document.getElementById('txtpersen_kepala1').value) || 0;
    var persen_kepala2 = parseInt(document.getElementById('txtpersen_kepala2').value) || 0;
    
    var total = persen_kepala1 + persen_kepala2;
    
    if(document.getElementById('totalPersenKepala')) {
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
}

// Function untuk mekanik pengerjaan percentage calculation
function calculatePercentageMekanik() {
    var persen1 = parseInt(document.getElementById('txtpersen_kerja1').value) || 0;
    var persen2 = parseInt(document.getElementById('txtpersen_kerja2').value) || 0;
    var persen3 = parseInt(document.getElementById('txtpersen_kerja3').value) || 0;
    var persen4 = parseInt(document.getElementById('txtpersen_kerja4').value) || 0;
    
    var total = persen1 + persen2 + persen3 + persen4;
    
    if(document.getElementById('totalPersenMekanik')) {
        document.getElementById('totalPersenMekanik').innerHTML = total;
        
        var statusDiv = document.getElementById('persentaseStatusMekanik');
        var messageSpan = document.getElementById('persenMessageMekanik');
        
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
}

// Auto calculate on input change
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners for kepala mekanik percentage inputs
    var kepalaPersen1 = document.getElementById('txtpersen_kepala1');
    var kepalaPersen2 = document.getElementById('txtpersen_kepala2');
    
    if(kepalaPersen1) {
        kepalaPersen1.addEventListener('input', calculatePercentageKepala);
    }
    if(kepalaPersen2) {
        kepalaPersen2.addEventListener('input', calculatePercentageKepala);
    }
    
    // Add event listeners for mekanik pengerjaan percentage inputs
    for(var i = 1; i <= 4; i++) {
        var persenInput = document.getElementById('txtpersen_kerja' + i);
        if(persenInput) {
            persenInput.addEventListener('input', calculatePercentageMekanik);
        }
    }
    
    // Initial calculation
    calculatePercentageKepala();
    calculatePercentageMekanik();
});
</script>
