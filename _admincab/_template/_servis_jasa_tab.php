<!-- Tab Item Jasa -->
<div class="jasa-content">
    <div class="row">
        <div class="col-xs-12">
            <div class="padding-18">
                
                <!-- Service Search and Add -->
                <div class="form-section">
                    <h5 class="purple">
                        <i class="ace-icon fa fa-cogs"></i>
                        Item Jasa / Service
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Cari Jasa :</label>									
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="txtcarisrv" id="txtcarisrv" 
                                        value="<?php echo $txtcarisrv; ?>" placeholder="Kode atau nama jasa..." />
                                        <span class="input-group-btn">
                                            <button class="btn btn-info" type="button" onclick="cariJasa()">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Nama Jasa :</label>									
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="txtnamajasa" id="txtnamajasa" 
                                    value="<?php echo $txtnamajasa ?? ''; ?>" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Qty :</label>									
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="txtqtysrv" id="txtqtysrv" 
                                    value="1" min="1" />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Harga :</label>									
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="txthargasrv" id="txthargasrv" 
                                    value="<?php echo $txthargasrv ?? ''; ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Subtotal :</label>									
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="txtsubtotalsrv" id="txtsubtotalsrv" 
                                    value="<?php echo $txtsubtotalsrv ?? ''; ?>" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 text-center">
                            <button type="button" class="btn btn-success" onclick="addItemJasa()">
                                <i class="fa fa-plus"></i> Tambah Jasa
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Service List -->
                <div class="form-section">
                    <h5 class="blue">
                        <i class="ace-icon fa fa-list"></i>
                        Daftar Item Jasa
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">Kode Jasa</th>
                                    <th width="30%">Nama Jasa</th>
                                    <th width="10%">Qty</th>
                                    <th width="15%">Harga</th>
                                    <th width="15%">Subtotal</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemJasaList">
                                <!-- Service list will be populated by JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-right">Total Item Jasa:</th>
                                    <th id="totalItemJasa">0</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Quick Add Common Services -->
                <div class="form-section">
                    <h5 class="green">
                        <i class="ace-icon fa fa-star"></i>
                        Jasa Umum
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddJasa('SERV', 'Service Berkala')">
                                <i class="fa fa-wrench"></i> Service Berkala
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddJasa('TUNE', 'Tune Up')">
                                <i class="fa fa-cog"></i> Tune Up
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddJasa('REM', 'Service Rem')">
                                <i class="fa fa-stop-circle"></i> Service Rem
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddJasa('KARB', 'Service Karburator')">
                                <i class="fa fa-fire"></i> Service Karburator
                            </button>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddJasa('GANTI', 'Ganti Oli')">
                                <i class="fa fa-tint"></i> Ganti Oli
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddJasa('CUCI', 'Cuci Motor')">
                                <i class="fa fa-shower"></i> Cuci Motor
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddJasa('DETIL', 'Detailing')">
                                <i class="fa fa-magic"></i> Detailing
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddJasa('LAIN', 'Lain-lain')">
                                <i class="fa fa-ellipsis-h"></i> Lain-lain
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Service Categories -->
                <div class="form-section">
                    <h5 class="orange">
                        <i class="ace-icon fa fa-tags"></i>
                        Kategori Jasa
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <div class="panel panel-primary">
                                <div class="panel-heading">
                                    <h6 class="panel-title">Service Ringan</h6>
                                </div>
                                <div class="panel-body">
                                    <ul class="list-unstyled">
                                        <li><i class="fa fa-check text-success"></i> Ganti Oli</li>
                                        <li><i class="fa fa-check text-success"></i> Cuci Motor</li>
                                        <li><i class="fa fa-check text-success"></i> Ganti Busi</li>
                                        <li><i class="fa fa-check text-success"></i> Ganti Filter</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="panel panel-warning">
                                <div class="panel-heading">
                                    <h6 class="panel-title">Service Sedang</h6>
                                </div>
                                <div class="panel-body">
                                    <ul class="list-unstyled">
                                        <li><i class="fa fa-check text-success"></i> Service Berkala</li>
                                        <li><i class="fa fa-check text-success"></i> Tune Up</li>
                                        <li><i class="fa fa-check text-success"></i> Service Rem</li>
                                        <li><i class="fa fa-check text-success"></i> Ganti Kampas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="panel panel-danger">
                                <div class="panel-heading">
                                    <h6 class="panel-title">Service Berat</h6>
                                </div>
                                <div class="panel-body">
                                    <ul class="list-unstyled">
                                        <li><i class="fa fa-check text-success"></i> Overhaul Mesin</li>
                                        <li><i class="fa fa-check text-success"></i> Service Transmisi</li>
                                        <li><i class="fa fa-check text-success"></i> Ganti Piston</li>
                                        <li><i class="fa fa-check text-success"></i> Service Karburator</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Global variables for service management
var itemJasaList = [];
var jasaCounter = 0;

// Function to search for services
function cariJasa() {
    var kodeJasa = document.getElementById('txtcarisrv').value;
    if(kodeJasa.trim() === '') {
        alert('Masukkan kode atau nama jasa');
        return;
    }
    
    // AJAX call to search for service
    $.ajax({
        url: '_ajax/ajax-cari-jasa.php',
        type: 'POST',
        data: {
            kode_jasa: kodeJasa
        },
        success: function(response) {
            var data = JSON.parse(response);
            if(data.success) {
                document.getElementById('txtnamajasa').value = data.nama_jasa;
                document.getElementById('txthargasrv').value = data.harga;
                calculateSubtotalJasa();
            } else {
                alert('Jasa tidak ditemukan');
                document.getElementById('txtnamajasa').value = '';
                document.getElementById('txthargasrv').value = '';
                document.getElementById('txtsubtotalsrv').value = '';
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat mencari jasa');
        }
    });
}

// Function to calculate subtotal for service
function calculateSubtotalJasa() {
    var qty = parseInt(document.getElementById('txtqtysrv').value) || 0;
    var harga = parseInt(document.getElementById('txthargasrv').value) || 0;
    var subtotal = qty * harga;
    document.getElementById('txtsubtotalsrv').value = subtotal;
}

// Function to add service to list
function addItemJasa() {
    var kodeJasa = document.getElementById('txtcarisrv').value;
    var namaJasa = document.getElementById('txtnamajasa').value;
    var qty = parseInt(document.getElementById('txtqtysrv').value) || 0;
    var harga = parseInt(document.getElementById('txthargasrv').value) || 0;
    var subtotal = parseInt(document.getElementById('txtsubtotalsrv').value) || 0;
    
    if(kodeJasa.trim() === '' || namaJasa.trim() === '' || qty <= 0) {
        alert('Mohon lengkapi data jasa');
        return;
    }
    
    jasaCounter++;
    var jasa = {
        id: jasaCounter,
        kode: kodeJasa,
        nama: namaJasa,
        qty: qty,
        harga: harga,
        subtotal: subtotal
    };
    
    itemJasaList.push(jasa);
    updateItemJasaTable();
    clearJasaForm();
}

// Function to remove service from list
function removeItemJasa(id) {
    itemJasaList = itemJasaList.filter(jasa => jasa.id !== id);
    updateItemJasaTable();
}

// Function to update service table
function updateItemJasaTable() {
    var tbody = document.getElementById('itemJasaList');
    var total = 0;
    
    tbody.innerHTML = '';
    
    itemJasaList.forEach(function(jasa, index) {
        var row = tbody.insertRow();
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${jasa.kode}</td>
            <td>${jasa.nama}</td>
            <td>${jasa.qty}</td>
            <td>${formatNumber(jasa.harga)}</td>
            <td>${formatNumber(jasa.subtotal)}</td>
            <td>
                <button type="button" class="btn btn-xs btn-danger" onclick="removeItemJasa(${jasa.id})">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        `;
        total += jasa.subtotal;
    });
    
    document.getElementById('totalItemJasa').textContent = formatNumber(total);
}

// Function to clear service form
function clearJasaForm() {
    document.getElementById('txtcarisrv').value = '';
    document.getElementById('txtnamajasa').value = '';
    document.getElementById('txtqtysrv').value = '1';
    document.getElementById('txthargasrv').value = '';
    document.getElementById('txtsubtotalsrv').value = '';
}

// Function to quick add common services
function quickAddJasa(kode, nama) {
    document.getElementById('txtcarisrv').value = kode;
    document.getElementById('txtnamajasa').value = nama;
    document.getElementById('txtqtysrv').value = '1';
    document.getElementById('txthargasrv').value = '0';
    document.getElementById('txtsubtotalsrv').value = '0';
}

// Event listeners for service tab
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('txtqtysrv').addEventListener('input', calculateSubtotalJasa);
    document.getElementById('txthargasrv').addEventListener('input', calculateSubtotalJasa);
});
</script>
