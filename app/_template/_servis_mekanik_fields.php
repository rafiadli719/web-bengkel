<?php
// Ambil data mekanik untuk dropdown
$query_mekanik = "SELECT nomekanik, nama FROM tblmekanik WHERE nama != '-' ORDER BY nama ASC";
$result_mekanik = mysqli_query($koneksi, $query_mekanik);
?>

<!-- Mekanik 1 with percentage -->
<div class="row">
    <div class="col-xs-8 col-sm-8">
        <label>Mekanik 1 *:</label>
        <select name="cbomekanik1" id="cbomekanik1" class="form-control" required onchange="autoFillMekanikPercentage()">
            <option value="">- Pilih Mekanik -</option>
            <?php
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($mekanik1) && $mekanik1 == $row_mekanik['nomekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['nomekanik']."' $selected>".$row_mekanik['nama']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_kerja1" id="txtpersen_kerja1" 
                   class="form-control" value="<?php echo isset($persen_kerja1) ? $persen_kerja1 : ''; ?>" 
                   min="0" max="100" onchange="calculatePercentageMekanik()" />
            <span class="input-group-addon">%</span>
        </div>
    </div>
</div>
<div class="space space-4"></div>

<!-- Mekanik 2 with percentage -->
<div class="row">
    <div class="col-xs-8 col-sm-8">
        <label>Mekanik 2 (Opsional):</label>
        <select name="cbomekanik2" id="cbomekanik2" class="form-control" onchange="autoFillMekanikPercentage()">
            <option value="">- Pilih Mekanik -</option>
            <?php
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($mekanik2) && $mekanik2 == $row_mekanik['nomekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['nomekanik']."' $selected>".$row_mekanik['nama']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_kerja2" id="txtpersen_kerja2" 
                   class="form-control" value="<?php echo isset($persen_kerja2) ? $persen_kerja2 : ''; ?>" 
                   min="0" max="100" onchange="calculatePercentageMekanik()" />
            <span class="input-group-addon">%</span>
        </div>
    </div>
</div>
<div class="space space-4"></div>

<!-- Mekanik 3 with percentage -->
<div class="row">
    <div class="col-xs-8 col-sm-8">
        <label>Mekanik 3 (Opsional):</label>
        <select name="cbomekanik3" id="cbomekanik3" class="form-control" onchange="autoFillMekanikPercentage()">
            <option value="">- Pilih Mekanik -</option>
            <?php
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($mekanik3) && $mekanik3 == $row_mekanik['nomekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['nomekanik']."' $selected>".$row_mekanik['nama']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_kerja3" id="txtpersen_kerja3" 
                   class="form-control" value="<?php echo isset($persen_kerja3) ? $persen_kerja3 : ''; ?>" 
                   min="0" max="100" onchange="calculatePercentageMekanik()" />
            <span class="input-group-addon">%</span>
        </div>
    </div>
</div>
<div class="space space-4"></div>

<!-- Mekanik 4 with percentage -->
<div class="row">
    <div class="col-xs-8 col-sm-8">
        <label>Mekanik 4 (Opsional):</label>
        <select name="cbomekanik4" id="cbomekanik4" class="form-control" onchange="autoFillMekanikPercentage()">
            <option value="">- Pilih Mekanik -</option>
            <?php
            mysqli_data_seek($result_mekanik, 0);
            while($row_mekanik = mysqli_fetch_array($result_mekanik)) {
                $selected = (isset($mekanik4) && $mekanik4 == $row_mekanik['nomekanik']) ? 'selected' : '';
                echo "<option value='".$row_mekanik['nomekanik']."' $selected>".$row_mekanik['nama']."</option>";
            }
            ?>
        </select>
    </div>
    <div class="col-xs-4 col-sm-4">
        <label>% Pengerjaan:</label>
        <div class="input-group">
            <input type="number" name="txtpersen_kerja4" id="txtpersen_kerja4" 
                   class="form-control" value="<?php echo isset($persen_kerja4) ? $persen_kerja4 : ''; ?>" 
                   min="0" max="100" onchange="calculatePercentageMekanik()" />
            <span class="input-group-addon">%</span>
        </div>
    </div>
</div>
<div class="space space-4"></div>

<!-- Total Percentage Display for Mekanik -->
<div class="row">
    <div class="col-xs-12 col-sm-12">
        <div class="alert alert-info" id="persentaseStatusMekanik">
            <i class="fa fa-calculator"></i> 
            <strong>Total % Pengerjaan Mekanik: <span id="totalPersenMekanik">0</span>%</strong>
            <span id="persenMessageMekanik"> - Harus 100%</span>
        </div>
    </div>
</div>

<script type="text/javascript">
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

// Auto-calculate when page loads and when values change
$(document).ready(function() {
    calculatePercentageMekanik();
    
    $('#txtpersen_kerja1, #txtpersen_kerja2, #txtpersen_kerja3, #txtpersen_kerja4').on('keyup change', function() {
        calculatePercentageMekanik();
    });
});
</script>
