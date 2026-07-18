<!-- Modal Input Barang Custom with Search & List -->
<div class="modal fade" id="modalInputCustom" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#fff;border-bottom:1px solid #e5e7eb;padding:16px 20px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#9ca3af;opacity:1;text-shadow:none;">&times;</button>
                <h4 class="modal-title" style="color:#1f2937;font-size:17px;font-weight:600;">
                    <i class="ace-icon fa fa-cube" style="color:#4f46e5;margin-right:6px;"></i>
                    Pilih / Input Barang Custom
                </h4>
            </div>
            <div class="modal-body">
                <!-- Tabs -->
                <ul class="nav nav-tabs" id="tabBarangCustom">
                    <li class="active">
                        <a data-toggle="tab" href="#tabCariCustom">
                            <i class="ace-icon fa fa-search blue"></i> Cari dari Database
                        </a>
                    </li>
                    <li>
                        <a data-toggle="tab" href="#tabInputManual">
                            <i class="ace-icon fa fa-plus-circle orange"></i> Input Manual Baru
                        </a>
                    </li>
                </ul>

                <div class="tab-content" style="padding-top: 15px;">
                    <!-- Tab 1: Cari dari Database -->
                    <div id="tabCariCustom" class="tab-pane fade in active">
                        <div class="alert alert-info">
                            <i class="ace-icon fa fa-info-circle"></i>
                            <strong>Tips:</strong> Cari barang custom yang sudah pernah diinput sebelumnya. 
                            Jika tidak ditemukan, gunakan tab "Input Manual Baru".
                        </div>

                        <!-- Search Box -->
                        <div class="form-group">
                            <div class="input-group">
                                <input type="text" class="form-control" id="searchCustomItem" 
                                       placeholder="Cari nama barang atau kode...">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-info" onclick="searchBarangCustom()">
                                        <i class="ace-icon fa fa-search"></i> Cari
                                    </button>
                                </span>
                            </div>
                        </div>

                        <!-- Loading -->
                        <div id="loadingCustomItems" style="display:none;" class="text-center">
                            <i class="ace-icon fa fa-spinner fa-spin fa-2x blue"></i>
                            <p class="text-muted">Memuat data...</p>
                        </div>

                        <!-- Results Table -->
                        <div id="customItemsContainer" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-striped table-bordered table-hover" id="tableCustomResults">
                                <thead>
                                    <tr class="info">
                                        <th width="15%">Kode</th>
                                        <th width="35%">Nama Barang</th>
                                        <th width="15%" class="text-right">Harga</th>
                                        <th width="10%" class="text-center">Satuan</th>
                                        <th width="15%">Kategori</th>
                                        <th width="10%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="customItemsBody">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="ace-icon fa fa-search"></i> 
                                            Ketik keyword dan klik cari untuk menampilkan data
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Quick Stats -->
                        <div id="customItemsStats" class="text-muted small" style="margin-top: 10px;">
                            <!-- Will be populated by AJAX -->
                        </div>
                    </div>

                    <!-- Tab 2: Input Manual Baru -->
                    <div id="tabInputManual" class="tab-pane fade">
                        <div class="alert alert-warning">
                            <i class="ace-icon fa fa-exclamation-triangle"></i>
                            <strong>Info:</strong> Barang yang ditambahkan akan tersimpan di master custom dan bisa 
                            digunakan kembali. Kode barang akan di-generate otomatis (Format: <strong>CUSTOM-XXXXX</strong>)
                        </div>
                        
                        <form id="formCustomItem">
                            <div class="form-group">
                                <label>Nama Barang <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="custom_nama" name="nama_barang" 
                                       placeholder="Contoh: Spare Part Import Khusus" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Harga Jual <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="custom_harga" name="harga_jual" 
                                               min="0" step="1000" placeholder="0" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label>Satuan <span class="text-danger">*</span></label>
                                        <select class="form-control" id="custom_satuan" name="satuan" required>
                                            <option value="PCS">PCS</option>
                                            <option value="UNIT">UNIT</option>
                                            <option value="SET">SET</option>
                                            <option value="PAKET">PAKET</option>
                                            <option value="BTL">BTL (Botol)</option>
                                            <option value="LITER">LITER</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Kategori</label>
                                <select class="form-control" id="custom_kategori" name="kategori">
                                    <option value="LAINNYA">LAINNYA</option>
                                    <option value="JASA">JASA</option>
                                    <option value="IMPORT">IMPORT</option>
                                    <option value="MODIFIKASI">MODIFIKASI</option>
                                    <option value="AKSESORIS">AKSESORIS</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Deskripsi</label>
                                <textarea class="form-control" id="custom_deskripsi" name="deskripsi" rows="2" 
                                          placeholder="Keterangan tambahan (optional)"></textarea>
                            </div>

                            <div class="text-right">
                                <button type="button" class="btn btn-primary" id="btnSaveCustomItem">
                                    <i class="ace-icon fa fa-check"></i>
                                    Simpan & Gunakan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i>
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Wait for jQuery to be available
(function waitForJQuery() {
    if (typeof jQuery !== 'undefined') {
        initCustomItemModal();
    } else {
        setTimeout(waitForJQuery, 50);
    }
})();

function initCustomItemModal() {
    jQuery(function($) {
        // Load data on modal open
        $('#modalInputCustom').on('shown.bs.modal', function() {
            loadRecentCustomItems();
        });

        // Search on Enter key
        $('#searchCustomItem').on('keypress', function(e) {
            if(e.which === 13) {
                e.preventDefault();
                searchBarangCustom();
            }
        });

        // Save custom item via AJAX
        $('#btnSaveCustomItem').on('click', function() {
            var form = $('#formCustomItem');
            
            // Validate
            if(!form[0].checkValidity()) {
                form[0].reportValidity();
                return;
            }
            
            var btn = $(this);
            var btnText = btn.html();
            btn.prop('disabled', true).html('<i class="ace-icon fa fa-spinner fa-spin"></i> Menyimpan...');
            
            // Get form data
            var formData = {
                action: 'quick_add_custom',
                nama_barang: $('#custom_nama').val(),
                harga_jual: $('#custom_harga').val(),
                satuan: $('#custom_satuan').val(),
                kategori: $('#custom_kategori').val(),
                deskripsi: $('#custom_deskripsi').val()
            };
            
            // AJAX call
            $.ajax({
                url: '_handler_barang_custom.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if(response.success) {
                        applyCustomItemToForm(response.data);
                        
                        // Show success message
                        alert(response.message + '\n\nKode: ' + response.data.kode_barang + '\n\nForm penawaran sudah terisi otomatis.');

                        // Close modal & reset form
                        $('#modalInputCustom').modal('hide');
                        form[0].reset();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Error: Gagal menyimpan barang custom.\n' + xhr.responseText);
                },
                complete: function() {
                    btn.prop('disabled', false).html(btnText);
                }
            });
        });
        
        // Reset form when modal closed
        $('#modalInputCustom').on('hidden.bs.modal', function() {
            $('#formCustomItem')[0].reset();
            $('#searchCustomItem').val('');
        });
    });
}

// Load recent custom items
function loadRecentCustomItems() {
    var container = $('#customItemsBody');
    var loading = $('#loadingCustomItems');
    
    loading.show();
    container.html('<tr><td colspan="6" class="text-center text-muted">Memuat data...</td></tr>');
    
    $.ajax({
        url: '_handler_barang_custom.php',
        type: 'GET',
        data: { action: 'list_custom_items', limit: 20 },
        dataType: 'json',
        success: function(response) {
            loading.hide();
            if(response.success && response.data && response.data.length > 0) {
                renderCustomItems(response.data);
                $('#customItemsStats').html('<i class="fa fa-info-circle"></i> Menampilkan ' + response.data.length + ' barang custom terbaru. Gunakan pencarian untuk hasil lebih spesifik.');
            } else {
                container.html('<tr><td colspan="6" class="text-center text-muted"><i class="fa fa-inbox"></i> Belum ada data barang custom. Gunakan tab "Input Manual Baru" untuk menambahkan.</td></tr>');
                $('#customItemsStats').html('');
            }
        },
        error: function() {
            loading.hide();
            container.html('<tr><td colspan="6" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Gagal memuat data</td></tr>');
        }
    });
}

// Search custom items
function searchBarangCustom() {
    var keyword = $('#searchCustomItem').val().trim();
    var container = $('#customItemsBody');
    var loading = $('#loadingCustomItems');
    
    if(!keyword) {
        loadRecentCustomItems();
        return;
    }
    
    loading.show();
    container.html('<tr><td colspan="6" class="text-center text-muted">Mencari...</td></tr>');
    
    $.ajax({
        url: '_handler_barang_custom.php',
        type: 'GET',
        data: { action: 'search_custom_items', keyword: keyword },
        dataType: 'json',
        success: function(response) {
            loading.hide();
            if(response.success && response.data && response.data.length > 0) {
                renderCustomItems(response.data);
                $('#customItemsStats').html('<i class="fa fa-check-circle text-success"></i> Ditemukan ' + response.data.length + ' hasil untuk "' + keyword + '"');
            } else {
                container.html('<tr><td colspan="6" class="text-center text-warning"><i class="fa fa-search"></i> Tidak ditemukan barang dengan keyword "' + keyword + '". <br><strong>Silakan gunakan tab "Input Manual Baru" untuk menambahkan barang baru.</strong></td></tr>');
                $('#customItemsStats').html('');
            }
        },
        error: function() {
            loading.hide();
            container.html('<tr><td colspan="6" class="text-center text-danger"><i class="fa fa-exclamation-triangle"></i> Gagal melakukan pencarian</td></tr>');
        }
    });
}

// Render custom items table
function renderCustomItems(items) {
    var html = '';
    items.forEach(function(item) {
        var hargaFormatted = parseInt(item.harga_jual || 0).toLocaleString('id-ID');
        var statusBadge = item.status_aktif == '1' ? 
            '<span class="label label-success">Aktif</span>' : 
            '<span class="label label-default">Nonaktif</span>';
        
        html += '<tr class="custom-item-row" style="cursor:pointer;" ' +
                'data-kode="' + (item.kode_barang || '') + '" ' +
                'data-nama="' + (item.nama_barang || '') + '" ' +
                'data-harga="' + (item.harga_jual || 0) + '" ' +
                'data-satuan="' + (item.satuan || 'PCS') + '">';
        html += '<td><strong class="blue">' + (item.kode_barang || '-') + '</strong></td>';
        html += '<td>' + (item.nama_barang || '-');
        if(item.deskripsi) {
            html += '<br><small class="text-muted">' + item.deskripsi + '</small>';
        }
        html += '</td>';
        html += '<td class="text-right"><strong>Rp ' + hargaFormatted + '</strong></td>';
        html += '<td class="text-center">' + (item.satuan || 'PCS') + '</td>';
        html += '<td><span class="label label-info">' + (item.kategori || 'LAINNYA') + '</span></td>';
        html += '<td class="text-center">';
        html += '<button type="button" class="btn btn-xs btn-success btn-select-custom" ' +
                'onclick="selectCustomItem(this)" title="Pilih">';
        html += '<i class="ace-icon fa fa-check"></i> Pilih';
        html += '</button>';
        html += '</td>';
        html += '</tr>';
    });
    
    $('#customItemsBody').html(html);
    
    // Add row click handler
    $('.custom-item-row').on('click', function(e) {
        if(!$(e.target).is('button') && !$(e.target).is('i')) {
            selectCustomItem($(this).find('.btn-select-custom')[0]);
        }
    });
}

// Select custom item and apply to form
function selectCustomItem(btn) {
    var row = $(btn).closest('tr');
    var data = {
        kode_barang: row.data('kode'),
        nama_barang: row.data('nama'),
        harga_jual: row.data('harga'),
        satuan: row.data('satuan')
    };
    
    applyCustomItemToForm(data);
    
    // Visual feedback
    row.addClass('success');
    setTimeout(function() {
        $('#modalInputCustom').modal('hide');
    }, 300);
}

// Apply selected/created custom item - context-aware (fills correct form based on active tab)
function applyCustomItemToForm(data) {
    console.log('applyCustomItemToForm called with:', data);

    // Check which tab is active
    var activeTab = $('.rd-tab-btn.active').data('target') || '';
    console.log('Active tab:', activeTab);

    if (activeTab === 'temuan-penawaran') {
        // Fill penawaran form in Tab Temuan & Penawaran
        console.log('Filling penawaran form from Barang Custom');

        // Open penawaran form if collapsed FIRST before setting values
        var penawaranBody = $('#penawaran-form-body');
        if(penawaranBody.length && penawaranBody.is(':hidden')) {
            penawaranBody.show();
            $('#penawaran-form-chevron').removeClass('fa-chevron-down').addClass('fa-chevron-up');
        }

        // Fill penawaran fields
        $('#kode_barang_penawaran_v2').val(data.kode_barang || '');
        $('#nama_barang_penawaran_v2').val(data.nama_barang || '');
        $('#harga_satuan_penawaran_v2').val(data.harga_jual || 0);
        $('#quantity_penawaran_v2').val(1);

        // Calculate total
        var total = parseInt(data.harga_jual || 0) * 1;
        $('#total_penawaran_display_v2').val('Rp ' + total.toLocaleString('id-ID'));

        // Highlight penawaran fields
        $('#kode_barang_penawaran_v2, #nama_barang_penawaran_v2, #harga_satuan_penawaran_v2').css('backgroundColor', '#d4edda');
        setTimeout(function() {
            $('#kode_barang_penawaran_v2, #nama_barang_penawaran_v2, #harga_satuan_penawaran_v2').css('backgroundColor', '');
        }, 1500);

    } else if (activeTab === 'service-jasa') {
        // Fill jasa form in Tab Item Jasa
        console.log('Filling jasa form from Barang Custom');

        $('#add_kode_jasa_v2').val(data.kode_barang || '');
        $('#add_nama_jasa_v2').val(data.nama_barang || '');
        $('#add_harga_jasa_v2').val(data.harga_jual || 0);

        // Calculate total
        if (typeof hitungTotalJasaV2 === 'function') {
            hitungTotalJasaV2();
        }

        // Highlight jasa fields
        $('#add_kode_jasa_v2, #add_nama_jasa_v2').addClass('rd-highlight');
        setTimeout(function() {
            $('#add_kode_jasa_v2, #add_nama_jasa_v2').removeClass('rd-highlight');
        }, 1000);

    } else {
        // Fill barang form in Tab Item Barang (default)
        console.log('Filling barang form from Barang Custom');

        $('#add_kode_barang_v2').val(data.kode_barang || '');
        $('#add_nama_barang_v2').val(data.nama_barang || '');
        $('#add_harga_barang_v2').val(data.harga_jual || 0);
        $('#add_qty_barang_v2').val(1);

        // Calculate total
        if (typeof hitungTotalBarangV2 === 'function') {
            hitungTotalBarangV2();
        }

        // Highlight barang fields
        $('#add_kode_barang_v2, #add_nama_barang_v2').addClass('rd-highlight');
        setTimeout(function() {
            $('#add_kode_barang_v2, #add_nama_barang_v2').removeClass('rd-highlight');
        }, 1000);
    }

    console.log('Values set successfully');
}
</script>

<style>
.custom-item-row:hover {
    background-color: #f5f5f5 !important;
}
.custom-item-row.success {
    background-color: #dff0d8 !important;
}
.animated-input {
    animation: highlight-input 1s ease;
}
@keyframes highlight-input {
    0%, 100% { background-color: #fff; }
    50% { background-color: #fcf8e3; }
}
#tabBarangCustom > li > a {
    font-weight: bold;
}
#customItemsContainer {
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>
