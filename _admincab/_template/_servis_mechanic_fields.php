<!-- Admin/Kasir 1 dengan Persentase -->
<div class="col-xs-8 col-sm-8">
    <label>Admin/Kasir 1 <span class="text-danger">*</span>:</label>
    <select class="form-control" name="cbomekanik1" id="cbomekanik1" required onchange="validateMekanik()">
        <option value="">- Pilih Admin/Kasir -</option>
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
        <option value="<?php echo htmlspecialchars($sql_res["nomekanik"]); ?>"
                <?php echo (isset($mekanik1_val) && $mekanik1_val==$sql_res["nomekanik"])?'selected':''; ?>>
            <?php echo htmlspecialchars($sql_res["nama"]); ?>
        </option>
        <?php 
                        }
                    }
                } catch (Exception $e) {
                    // Silent error handling
                }
            }
        ?>
    </select> 
</div>
<div class="col-xs-4 col-sm-4">
    <label>% Pengerjaan:</label>
    <div class="input-group">
        <input type="number" class="form-control" name="txtpersen_kerja1" id="txtpersen_kerja1" 
               value="<?php echo isset($persen1) ? $persen1 : '0'; ?>" min="0" max="100" 
               onchange="calculatePercentage()" onkeyup="calculatePercentage()">
        <span class="input-group-addon">%</span>
    </div>
</div>

<!-- Admin/Kasir 2 dengan Persentase (Opsional) -->
<div class="col-xs-8 col-sm-8">
    <label>Admin/Kasir 2 (Opsional):</label>
    <select class="form-control" name="cbomekanik2" id="cbomekanik2" onchange="validateMekanik()">
        <option value="">- Pilih Admin/Kasir -</option>
        <?php
            if(isset($koneksi)) {
                try {
                    // Re-execute query for second dropdown
                    $sql2="SELECT nomekanik, nama FROM tblmekanik 
                           WHERE nama<>'-' 
                           ORDER BY nama ASC";
                    $sql_row2=mysqli_query($koneksi,$sql2);
                    if($sql_row2) {
                        while($sql_res2=mysqli_fetch_assoc($sql_row2)) {
        ?>
        <option value="<?php echo htmlspecialchars($sql_res2["nomekanik"]); ?>"
                <?php echo (isset($mekanik2_val) && $mekanik2_val==$sql_res2["nomekanik"])?'selected':''; ?>>
            <?php echo htmlspecialchars($sql_res2["nama"]); ?>
        </option>
        <?php 
                        }
                    }
                } catch (Exception $e) {
                    // Silent error handling
                }
            }
        ?>
    </select> 
</div>
<div class="col-xs-4 col-sm-4">
    <label>% Pengerjaan:</label>
    <div class="input-group">
        <input type="number" class="form-control" name="txtpersen_kerja2" id="txtpersen_kerja2" 
               value="<?php echo isset($persen2) ? $persen2 : '0'; ?>" min="0" max="100" 
               onchange="calculatePercentage()" onkeyup="calculatePercentage()">
        <span class="input-group-addon">%</span>
    </div>
</div>

<!-- Mekanik 3 dengan Persentase -->
<div class="col-xs-8 col-sm-8">
    <label>Mekanik 3 (Opsional):</label>
    <select class="form-control" name="cbomekanik3" id="cbomekanik3" onchange="validateMekanik()">
        <option value="">- Pilih Mekanik -</option>
        <?php
            if(isset($koneksi)) {
                try {
                    $sql3="SELECT nomekanik, nama FROM tblmekanik 
                           WHERE nama<>'-' 
                           ORDER BY nama ASC";
                    $sql_row3=mysqli_query($koneksi,$sql3);
                    if($sql_row3) {
                        while($sql_res3=mysqli_fetch_assoc($sql_row3)) {
        ?>
        <option value="<?php echo htmlspecialchars($sql_res3["nomekanik"]); ?>"
                <?php echo (isset($mekanik3_val) && $mekanik3_val==$sql_res3["nomekanik"])?'selected':''; ?>>
            <?php echo htmlspecialchars($sql_res3["nama"]); ?>
        </option>
        <?php 
                        }
                    }
                } catch (Exception $e) {
                    // Silent error handling
                }
            }
        ?>
    </select> 
</div>
<div class="col-xs-4 col-sm-4">
    <label>% Pengerjaan:</label>
    <div class="input-group">
        <input type="number" class="form-control" name="txtpersen_kerja3" id="txtpersen_kerja3" 
               value="<?php echo isset($persen3) ? $persen3 : '0'; ?>" min="0" max="100" 
               onchange="calculatePercentage()" onkeyup="calculatePercentage()">
        <span class="input-group-addon">%</span>
    </div>
</div>

<!-- Mekanik 4 dengan Persentase -->
<div class="col-xs-8 col-sm-8">
    <label>Mekanik 4 (Opsional):</label>
    <select class="form-control" name="cbomekanik4" id="cbomekanik4" onchange="validateMekanik()">
        <option value="">- Pilih Mekanik -</option>
        <?php
            if(isset($koneksi)) {
                try {
                    $sql4="SELECT nomekanik, nama FROM tblmekanik 
                           WHERE nama<>'-' 
                           ORDER BY nama ASC";
                    $sql_row4=mysqli_query($koneksi,$sql4);
                    if($sql_row4) {
                        while($sql_res4=mysqli_fetch_assoc($sql_row4)) {
        ?>
        <option value="<?php echo htmlspecialchars($sql_res4["nomekanik"]); ?>"
                <?php echo (isset($mekanik4_val) && $mekanik4_val==$sql_res4["nomekanik"])?'selected':''; ?>>
            <?php echo htmlspecialchars($sql_res4["nama"]); ?>
        </option>
        <?php 
                        }
                    }
                } catch (Exception $e) {
                    // Silent error handling
                }
            }
        ?>
    </select> 
</div>
<div class="col-xs-4 col-sm-4">
    <label>% Pengerjaan:</label>
    <div class="input-group">
        <input type="number" class="form-control" name="txtpersen_kerja4" id="txtpersen_kerja4" 
               value="<?php echo isset($persen4) ? $persen4 : '0'; ?>" min="0" max="100" 
               onchange="calculatePercentage()" onkeyup="calculatePercentage()">
        <span class="input-group-addon">%</span>
    </div>
</div>

<!-- Status Persentase Mekanik -->
<div class="col-xs-12 col-sm-12">
    <div class="space space-2"></div>
    <div id="persentaseStatusMekanik" class="alert alert-info">
        <i class="ace-icon fa fa-info-circle"></i>
        <strong>Total % Pengerjaan Mekanik: <span id="totalPersenMekanik">0</span>%</strong>
        <span id="persenMessageMekanik"> - Harus 100%</span>
    </div>
</div>

<!-- Admin/Kasir 1 with percentage -->
<div class="row">
    <div class="col-xs-8 col-sm-8">
        <label>Admin/Kasir 1 *:</label>
        <select name="cboadmin1" id="cboadmin1" class="form-control" required>
            <option value="">- Pilih Admin/Kasir -</option>
            <?php
            // Ambil data mekanik untuk dropdown
            $query_mekanik = "SELECT * FROM tblmekanik ORDER BY nama_mekanik ASC";
            $result_mekanik = mysqli_query($koneksi, $query_mekanik);
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($admin1) && $admin1 == $row_mekanik['kode_mekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['kode_mekanik']."' $selected>".$row_mekanik['nama_mekanik']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_admin1" id="txtpersen_admin1" 
                   class="form-control" value="<?php echo $persen_admin1; ?>" 
                   min="0" max="100" onchange="calculatePercentageAdmin()" />
            <span class="input-group-addon">%</span>
        </div>
    </div>
</div>
<div class="space space-4"></div>

<!-- Admin/Kasir 2 with percentage -->
<div class="row">
    <div class="col-xs-8 col-sm-8">
        <label>Admin/Kasir 2 (Opsional):</label>
        <select name="cboadmin2" id="cboadmin2" class="form-control">
            <option value="">- Pilih Admin/Kasir -</option>
            <?php
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($admin2) && $admin2 == $row_mekanik['kode_mekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['kode_mekanik']."' $selected>".$row_mekanik['nama_mekanik']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_admin2" id="txtpersen_admin2" 
                   class="form-control" value="<?php echo $persen_admin2; ?>" 
                   min="0" max="100" onchange="calculatePercentageAdmin()" />
            <span class="input-group-addon">%</span>
        </div>
    </div>
</div>
<div class="space space-4"></div>

<!-- Total Percentage Display for Admin -->
<div class="row">
    <div class="col-xs-12 col-sm-12">
        <div class="alert alert-info" id="persentaseStatusAdmin">
            <i class="fa fa-calculator"></i> 
            <strong>Total % Pengerjaan Admin/Kasir: <span id="totalPersenAdmin">0</span>%</strong>
            <span id="persenMessageAdmin"> - Harus 100%</span>
        </div>
    </div>
</div>

<script type="text/javascript">
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

function validateAdmin() {
    var admin1 = $('#cboadmin1').val();
    
    // Admin 1 is required
    if (!admin1) {
        alert('Admin/Kasir 1 wajib dipilih!');
        return false;
    }
    
    return true;
}

// Auto-calculate when page loads and when values change
$(document).ready(function() {
    calculatePercentageAdmin();
    
    $('#txtpersen_admin1, #txtpersen_admin2').on('keyup change', function() {
        calculatePercentageAdmin();
    });
});
</script>
