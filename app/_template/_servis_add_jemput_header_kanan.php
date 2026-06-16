<?php
// File: _servis_add_jemput_header_kanan.php
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

if(isset($no_service) && isset($koneksi)) {
    try {
        $cari_mek = mysqli_query($koneksi,"SELECT kepala_mekanik1, kepala_mekanik2, 
                                                  persen_kepala_mekanik1, persen_kepala_mekanik2,
                                                  mekanik1, mekanik2, mekanik3, mekanik4,
                                                  persen_mekanik1, persen_mekanik2, 
                                                  persen_mekanik3, persen_mekanik4 
                                           FROM tblservice WHERE no_service='$no_service'");
        if($cari_mek && $tm_mek = mysqli_fetch_array($cari_mek)) {
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
    } catch (Exception $e) {
        // Silent error handling - continue with default values
        error_log("Template error: " . $e->getMessage());
    }
}
?>

<div class="widget-box">
    <div class="widget-body">
        <div class="widget-main">
            <!-- Hidden fields untuk form submission -->
            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>" />
            <input type="hidden" name="txtcarisrv" value="<?php echo isset($txtcarisrv) ? $txtcarisrv : ''; ?>" />
            <input type="hidden" name="txtcaribrg" value="<?php echo isset($txtcaribrg) ? $txtcaribrg : ''; ?>" />
            <input type="hidden" name="txtcariwo" value="<?php echo isset($txtcariwo) ? $txtcariwo : ''; ?>" />
            
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
                            <input type="text" class="form-control" 
                            value="SERVICE JEMPUT" readonly="true" style="background-color: #f8f9fa; font-weight: bold; color: #fd7e14;" />
                        </div>
                    </div>
                </div>
            </div>
            <div class="space space-8"></div>

            <!-- Section Info Jemput -->
            <div class="row">
                <div class="col-xs-12 col-sm-12">
                    <h4 class="header blue">
                        <i class="ace-icon fa fa-motorcycle"></i> Informasi Service Jemput
                    </h4>
                    <div class="alert alert-info">
                        <i class="ace-icon fa fa-info-circle"></i>
                        <strong>Service Jemput:</strong> Kendaraan dijemput dari lokasi pelanggan.
                        <br><strong>Alamat:</strong> <?php echo htmlspecialchars($alamat); ?>
                        <?php if(!empty($patokan)) { ?>
                            <br><strong>Patokan:</strong> <?php echo htmlspecialchars($patokan); ?>
                        <?php } ?>
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
                    <select class="form-control" name="cbokepala1" id="cbokepala1" onchange="validateMekanikKepala(); autoFillKepalaPercentage()">
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
                               value="<?php echo $persen_kepala1; ?>" min="0" max="100" 
                               onchange="calculatePercentageKepala()" onkeyup="calculatePercentageKepala()">
                        <span class="input-group-addon">%</span>
                    </div>
                </div>

                <!-- Kepala Mekanik 2 dengan Persentase -->
                <div class="col-xs-8 col-sm-8">
                    <label>Kepala Mekanik 2 (Opsional):</label>
                    <select class="form-control" name="cbokepala2" id="cbokepala2" onchange="validateMekanikKepala(); autoFillKepalaPercentage()">
                        <option value="">- Pilih Kepala Mekanik -</option>
                        <?php
                            if(isset($koneksi)) {
                                try {
                                    // Re-execute query for second dropdown
                                    $sql2="SELECT nomekanik, nama FROM tblmekanik 
                                           WHERE nama<>'-' AND keahlian='1' 
                                           ORDER BY nama ASC";
                                    $sql_row2=mysqli_query($koneksi,$sql2);
                                    if($sql_row2) {
                                        while($sql_res2=mysqli_fetch_assoc($sql_row2)) {
                        ?>
                        <option value="<?php echo htmlspecialchars($sql_res2["nomekanik"]); ?>"
                                <?php echo ($kepala2==$sql_res2["nomekanik"])?'selected':''; ?>>
                            <?php echo htmlspecialchars($sql_res2["nama"]); ?>
                        </option>
                        <?php 
                                        }
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
                        <i class="ace-icon fa fa-user"></i>
                        Admin/Kasir
                    </h4>
                    <p><small><em>Minimal 1 admin/kasir harus diisi. Total persentase harus 100%</em></small></p>
                </div>
                
                <?php include "_template/_servis_admin_fields.php"; ?>
                
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
                
                <?php include "_template/_servis_mekanik_fields.php"; ?>
                
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

function showProsesDetail(keluhanId) {
    // Show modal dengan detail proses keluhan
    alert('Detail proses untuk keluhan ID: ' + keluhanId);
}

function showModalSearchKeluhan() {
    // Show modal search keluhan
    $('#modal-search-keluhan').modal('show');
}

// Validation functions like jemput template
function validateMekanikJemput(number) {
    var mekanik = document.getElementById('cbomekanik' + number).value;
    var persen = document.getElementById('txtpersen' + number);
    
    if (mekanik == '') {
        persen.value = '0';
    } else if (persen.value == '0' || persen.value == '') {
        // Auto set 100% jika mekanik pertama dan belum ada yang diisi
        if (number == 1) {
            var total_existing = 0;
            for(var i = 2; i <= 4; i++) {
                total_existing += parseInt(document.getElementById('txtpersen' + i).value) || 0;
            }
            if (total_existing == 0) {
                persen.value = '100';
            }
        }
    }
    calculatePercentageJemput();
}

function calculatePercentageJemput() {
    var persen1 = parseInt(document.getElementById('txtpersen1').value) || 0;
    var persen2 = parseInt(document.getElementById('txtpersen2').value) || 0;
    var persen3 = parseInt(document.getElementById('txtpersen3').value) || 0;
    var persen4 = parseInt(document.getElementById('txtpersen4').value) || 0;
    
    var total = persen1 + persen2 + persen3 + persen4;
    
    if(document.getElementById('totalPersenJemput')) {
        document.getElementById('totalPersenJemput').innerHTML = total;
        
        var statusDiv = document.getElementById('persentaseStatusJemput');
        var messageSpan = document.getElementById('persenMessageJemput');
        
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

function validateBeforeSubmitJemput() {
    var mekanik1 = document.getElementById('cbomekanik1').value;
    var kepala1 = document.getElementById('cbokepala1').value;
    
    if (kepala1 == '') {
        alert('Kepala Mekanik 1 harus diisi untuk service jemput!');
        return false;
    }
    
    if (mekanik1 == '') {
        alert('Minimal Admin/Kasir 1 harus diisi!');
        return false;
    }
    
    var total_persen = parseInt(document.getElementById('totalPersenJemput').innerHTML) || 0;
    if (total_persen != 100) {
        alert('Total persentase harus tepat 100%!\nSaat ini: ' + total_persen + '%');
        return false;
    }
    
    return true;
}

// Keluhan Management Functions
function selectKeluhan(keluhan) {
    // Set keluhan to the input field
    var keluhanInput = document.querySelector('input[name="txtkeluhan"]');
    if (keluhanInput) {
        keluhanInput.value = keluhan;
    }
    $('#modal-search-keluhan').modal('hide');
}

function tambahKeluhanKeService() {
    var noService = '<?php echo $no_service ?? ""; ?>';
    var keluhan = document.getElementById('txtkeluhan').value.trim();
    
    if (!noService) {
        alert('Simpan service terlebih dahulu sebelum menambahkan keluhan!');
        return false;
    }
    
    if (!keluhan) {
        alert('Masukkan keluhan terlebih dahulu!');
        document.getElementById('txtkeluhan').focus();
        return false;
    }
    
    // Use traditional form submission instead of AJAX for better compatibility
    return true;
}

function hapusKeluhan(id) {
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
}

function refreshTabelKeluhan() {
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
}

// Auto-fill percentage functions
function autoFillKepalaPercentage() {
    var kepala1 = document.getElementById('cbokepala1').value;
    var kepala2 = document.getElementById('cbokepala2').value;
    
    // If only kepala1 is selected, auto-fill 100%
    if (kepala1 && !kepala2) {
        document.getElementById('txtpersen_kepala1').value = '100';
        document.getElementById('txtpersen_kepala2').value = '';
    }
    // If only kepala2 is selected, auto-fill 100%
    else if (!kepala1 && kepala2) {
        document.getElementById('txtpersen_kepala1').value = '';
        document.getElementById('txtpersen_kepala2').value = '100';
    }
    // If both are selected, split 50-50
    else if (kepala1 && kepala2) {
        document.getElementById('txtpersen_kepala1').value = '50';
        document.getElementById('txtpersen_kepala2').value = '50';
    }
    // If none selected, clear percentages
    else {
        document.getElementById('txtpersen_kepala1').value = '';
        document.getElementById('txtpersen_kepala2').value = '';
    }
    
    calculatePercentageKepala();
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
    document.getElementById('txtpersen1').value = '';
    document.getElementById('txtpersen2').value = '';
    document.getElementById('txtpersen3').value = '';
    document.getElementById('txtpersen4').value = '';
    
    if (selectedCount === 1) {
        // Auto-fill 100% for the selected one
        if (mekanik1) document.getElementById('txtpersen1').value = '100';
        else if (mekanik2) document.getElementById('txtpersen2').value = '100';
        else if (mekanik3) document.getElementById('txtpersen3').value = '100';
        else if (mekanik4) document.getElementById('txtpersen4').value = '100';
    }
    else if (selectedCount === 2) {
        // Split 50-50
        var percentage = '50';
        if (mekanik1) document.getElementById('txtpersen1').value = percentage;
        if (mekanik2) document.getElementById('txtpersen2').value = percentage;
        if (mekanik3) document.getElementById('txtpersen3').value = percentage;
        if (mekanik4) document.getElementById('txtpersen4').value = percentage;
    }
    else if (selectedCount === 3) {
        // Split 33.33-33.33-33.33
        var percentage = '33';
        var lastPercentage = '34'; // To make total 100%
        var count = 0;
        if (mekanik1) { count++; document.getElementById('txtpersen1').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik2) { count++; document.getElementById('txtpersen2').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik3) { count++; document.getElementById('txtpersen3').value = (count === 3) ? lastPercentage : percentage; }
        if (mekanik4) { count++; document.getElementById('txtpersen4').value = (count === 3) ? lastPercentage : percentage; }
    }
    else if (selectedCount === 4) {
        // Split 25-25-25-25
        document.getElementById('txtpersen1').value = '25';
        document.getElementById('txtpersen2').value = '25';
        document.getElementById('txtpersen3').value = '25';
        document.getElementById('txtpersen4').value = '25';
    }
    
    calculatePercentageJemput();
}

// Auto calculate on page load
document.addEventListener('DOMContentLoaded', function() {
    calculatePercentageKepala();
    calculatePercentageJemput();
    
    // Event listeners
    $('#txtpersen_kepala1, #txtpersen_kepala2').on('input keyup', function() {
        calculatePercentageKepala();
    });
    
    $('#txtpersen1, #txtpersen2, #txtpersen3, #txtpersen4').on('input keyup', function() {
        calculatePercentageJemput();
    });
    
    // Add validation to form submit
    var form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            var submitButton = e.submitter || document.activeElement;
            
            // Only validate for main save buttons
            if (submitButton && (submitButton.name == 'btnsimpan' || submitButton.id == 'btnsimpan')) {
                if (!validateBeforeSubmitJemput()) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
});
</script>

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