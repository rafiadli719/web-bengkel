<!-- Tab Item Barang -->
<div class="items-content">
    <div class="row">
        <div class="col-xs-12">
            <div class="padding-18">
                
                <!-- Item Search and Add -->
                <div class="form-section">
                    <h5 class="orange">
                        <i class="ace-icon fa fa-truck"></i>
                        Item Barang / Spare Parts
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Cari Barang :</label>									
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="txtcaribrg" id="txtcaribrg" 
                                        value="<?php echo $txtcaribrg; ?>" placeholder="Kode atau nama barang..." />
                                        <span class="input-group-btn">
                                            <button class="btn btn-info" type="button" onclick="cariBarang()">
                                                <i class="fa fa-search"></i>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-6">
                            <div class="form-group">
                                <label class="col-sm-3 control-label no-padding-right"> Nama Barang :</label>									
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="txtnamaitem" id="txtnamaitem" 
                                    value="<?php echo $txtnamaitem; ?>" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Qty :</label>									
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="txtqty" id="txtqty" 
                                    value="1" min="1" />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Harga :</label>									
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="txtharga" id="txtharga" 
                                    value="<?php echo $txtharga ?? ''; ?>" readonly />
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div class="form-group">
                                <label class="col-sm-4 control-label no-padding-right"> Subtotal :</label>									
                                <div class="col-sm-8">
                                    <input type="number" class="form-control" name="txtsubtotal" id="txtsubtotal" 
                                    value="<?php echo $txtsubtotal ?? ''; ?>" readonly />
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-xs-12 text-center">
                            <button type="button" class="btn btn-success" onclick="addItemBarang()">
                                <i class="fa fa-plus"></i> Tambah Item
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Item List -->
                <div class="form-section">
                    <h5 class="blue">
                        <i class="ace-icon fa fa-list"></i>
                        Daftar Item Barang
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">No</th>
                                    <th width="15%">Kode Barang</th>
                                    <th width="30%">Nama Barang</th>
                                    <th width="10%">Qty</th>
                                    <th width="15%">Harga</th>
                                    <th width="15%">Subtotal</th>
                                    <th width="10%">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemBarangList">
                                <!-- Item list will be populated by JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-right">Total Item Barang:</th>
                                    <th id="totalItemBarang">0</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Quick Add Common Items -->
                <div class="form-section">
                    <h5 class="green">
                        <i class="ace-icon fa fa-star"></i>
                        Item Umum
                    </h5>
                    <div class="hr hr-8"></div>
                    
                    <div class="row">
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddItem('OIL', 'Oli Mesin')">
                                <i class="fa fa-tint"></i> Oli Mesin
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddItem('FILTER', 'Filter Udara')">
                                <i class="fa fa-filter"></i> Filter Udara
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddItem('KAMPAS', 'Kampas Rem')">
                                <i class="fa fa-circle"></i> Kampas Rem
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-primary btn-block" onclick="quickAddItem('BAN', 'Ban Motor')">
                                <i class="fa fa-circle-o"></i> Ban Motor
                            </button>
                        </div>
                    </div>
                    
                    <div class="row" style="margin-top: 10px;">
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddItem('AKI', 'Aki Motor')">
                                <i class="fa fa-battery-full"></i> Aki Motor
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddItem('BUSI', 'Busi')">
                                <i class="fa fa-fire"></i> Busi
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddItem('RANTAI', 'Rantai Motor')">
                                <i class="fa fa-link"></i> Rantai Motor
                            </button>
                        </div>
                        <div class="col-xs-12 col-sm-3">
                            <button type="button" class="btn btn-sm btn-outline btn-success btn-block" onclick="quickAddItem('LAIN', 'Lain-lain')">
                                <i class="fa fa-ellipsis-h"></i> Lain-lain
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Global variables for item management
var itemBarangList = [];
var itemCounter = 0;

// Function to search for items
function cariBarang() {
    var kodeBarang = document.getElementById('txtcaribrg').value;
    if(kodeBarang.trim() === '') {
        alert('Masukkan kode atau nama barang');
        return;
    }
    
    // AJAX call to search for item
    $.ajax({
        url: '_ajax/ajax-cari-barang.php',
        type: 'POST',
        data: {
            kode_barang: kodeBarang
        },
        success: function(response) {
            var data = JSON.parse(response);
            if(data.success) {
                document.getElementById('txtnamaitem').value = data.nama_barang;
                document.getElementById('txtharga').value = data.harga;
                calculateSubtotal();
            } else {
                alert('Barang tidak ditemukan');
                document.getElementById('txtnamaitem').value = '';
                document.getElementById('txtharga').value = '';
                document.getElementById('txtsubtotal').value = '';
            }
        },
        error: function() {
            alert('Terjadi kesalahan saat mencari barang');
        }
    });
}

// Function to calculate subtotal
function calculateSubtotal() {
    var qty = parseInt(document.getElementById('txtqty').value) || 0;
    var harga = parseInt(document.getElementById('txtharga').value) || 0;
    var subtotal = qty * harga;
    document.getElementById('txtsubtotal').value = subtotal;
}

// Function to add item to list
function addItemBarang() {
    var kodeBarang = document.getElementById('txtcaribrg').value;
    var namaBarang = document.getElementById('txtnamaitem').value;
    var qty = parseInt(document.getElementById('txtqty').value) || 0;
    var harga = parseInt(document.getElementById('txtharga').value) || 0;
    var subtotal = parseInt(document.getElementById('txtsubtotal').value) || 0;
    
    if(kodeBarang.trim() === '' || namaBarang.trim() === '' || qty <= 0) {
        alert('Mohon lengkapi data barang');
        return;
    }
    
    itemCounter++;
    var item = {
        id: itemCounter,
        kode: kodeBarang,
        nama: namaBarang,
        qty: qty,
        harga: harga,
        subtotal: subtotal
    };
    
    itemBarangList.push(item);
    updateItemBarangTable();
    clearItemForm();
}

// Function to remove item from list
function removeItemBarang(id) {
    itemBarangList = itemBarangList.filter(item => item.id !== id);
    updateItemBarangTable();
}

// Function to update item table
function updateItemBarangTable() {
    var tbody = document.getElementById('itemBarangList');
    var total = 0;
    
    tbody.innerHTML = '';
    
    itemBarangList.forEach(function(item, index) {
        var row = tbody.insertRow();
        row.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.kode}</td>
            <td>${item.nama}</td>
            <td>${item.qty}</td>
            <td>${formatNumber(item.harga)}</td>
            <td>${formatNumber(item.subtotal)}</td>
            <td>
                <button type="button" class="btn btn-xs btn-danger" onclick="removeItemBarang(${item.id})">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        `;
        total += item.subtotal;
    });
    
    document.getElementById('totalItemBarang').textContent = formatNumber(total);
}

// Function to clear item form
function clearItemForm() {
    document.getElementById('txtcaribrg').value = '';
    document.getElementById('txtnamaitem').value = '';
    document.getElementById('txtqty').value = '1';
    document.getElementById('txtharga').value = '';
    document.getElementById('txtsubtotal').value = '';
}

// Function to quick add common items
function quickAddItem(kode, nama) {
    document.getElementById('txtcaribrg').value = kode;
    document.getElementById('txtnamaitem').value = nama;
    document.getElementById('txtqty').value = '1';
    document.getElementById('txtharga').value = '0';
    document.getElementById('txtsubtotal').value = '0';
}

// Function to format number
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('txtqty').addEventListener('input', calculateSubtotal);
    document.getElementById('txtharga').addEventListener('input', calculateSubtotal);
});
</script>
