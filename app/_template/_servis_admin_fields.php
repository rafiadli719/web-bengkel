<?php
// Ambil data mekanik untuk dropdown
$query_mekanik = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
$result_mekanik = mysqli_query($koneksi, $query_mekanik);
?>

<!-- Admin/Kasir 1 with percentage -->
<div class="row">
    <div class="col-xs-8 col-sm-8">
        <label>Admin/Kasir 1 *:</label>
        <select name="cboadmin1" id="cboadmin1" class="form-control" required onchange="autoFillAdminPercentage()">
            <option value="">- Pilih Admin/Kasir -</option>
            <?php
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($admin1) && $admin1 == $row_mekanik['nomekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['nomekanik']."' $selected>".$row_mekanik['nama']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_admin1" id="txtpersen_admin1" 
                   class="form-control" value="<?php echo isset($persen_admin1) ? $persen_admin1 : ''; ?>" 
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
        <select name="cboadmin2" id="cboadmin2" class="form-control" onchange="autoFillAdminPercentage()">
            <option value="">- Pilih Admin/Kasir -</option>
            <?php
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($admin2) && $admin2 == $row_mekanik['nomekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['nomekanik']."' $selected>".$row_mekanik['nama']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_admin2" id="txtpersen_admin2" 
                   class="form-control" value="<?php echo isset($persen_admin2) ? $persen_admin2 : ''; ?>" 
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

// Auto-calculate when page loads and when values change
$(document).ready(function() {
    calculatePercentageAdmin();
    
    $('#txtpersen_admin1, #txtpersen_admin2').on('keyup change', function() {
        calculatePercentageAdmin();
    });
});
</script>
