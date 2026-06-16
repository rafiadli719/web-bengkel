<!-- Modal Fast Moves - Fixed & Responsive -->
<div id="modalFastMovesV2" class="modal fade" tabindex="-1">
    <div class="modal-dialog" style="width: 90%; max-width: 1200px;">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="ace-icon fa fa-bolt orange"></i>
                    Fast Moves - Pilih Part Cepat
                </h4>
            </div>

            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                
                <!-- Search Global -->
                <div class="row">
                    <div class="col-xs-12">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchGlobalFM" placeholder="Cari part berdasarkan kode atau nama...">
                            <span class="input-group-btn">
                                <button class="btn btn-primary btn-sm" type="button" id="btnSearchGlobalFM">
                                    <i class="ace-icon fa fa-search"></i> Cari
                                </button>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-12"></div>
                <div class="hr hr-dotted"></div>

                <!-- SERVIS RUTIN STANDAR -->
                <h5 class="blue">
                    <i class="ace-icon fa fa-wrench"></i>
                    SERVIS RUTIN STANDAR BEBEK / MATIC / SUPER MATIC / SUPER SPORT
                </h5>
                <div class="space-6"></div>
                <div class="row">
                    <?php
                    $kategori_servis_rutin = [
                        'JEMPUT' => 'Ongkos Jemput/Antar',
                        'SERVIS' => 'Servis/Tune Up',
                        'AKI' => 'Aki',
                        'OLI' => 'Oli Mesin',
                        'BUSI' => 'Busi',
                        'COP' => 'Cop Busi',
                        'ROTAKI' => 'Rotaki',
                        'LAMPU' => 'Bohlam/Lampu',
                        'FITLAMP' => 'Fitingan Lampu',
                        'FLTUDARA' => 'Filter Udara',
                        'FLTFUEL' => 'Filter Fuel',
                        'FLTCVT' => 'Filter Blok/CVT',
                        'KAMPAS_D' => 'Kampas Rem Depan',
                        'KAMPAS_B' => 'Kampas Rem Belakang',
                        'DISCPAD' => 'Disc Pad',
                        'MASTER_REM' => 'Master Rem',
                        'KOMSTIR' => 'Komstir',
                        'SOK_D' => 'Sok Depan',
                        'SOK_B' => 'Sok Belakang',
                        'MINYAK_REM' => 'Minyak Rem',
                        'GREASE' => 'Grease/Gemuk',
                        'GEARSET' => 'Gear Set',
                        'LAHER' => 'Laher Roda',
                        'GIRBOX' => 'Girbox',
                        'BAN_D' => 'Ban Depan',
                        'BAN_B' => 'Ban Belakang',
                        'BP' => 'BP 1/BP 2'
                    ];
                    
                    foreach($kategori_servis_rutin as $kode => $nama) {
                    ?>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <button type="button" class="btn btn-block btn-sm btn-primary btn-fm-kategori" 
                                data-kategori="<?php echo $kode; ?>"
                                data-nama="<?php echo $nama; ?>"
                                style="margin-bottom: 5px; text-align: left; white-space: normal; height: auto; min-height: 32px; padding: 6px 12px;">
                            <i class="ace-icon fa fa-angle-right"></i> <?php echo $nama; ?>
                        </button>
                    </div>
                    <?php } ?>
                </div>

                <div class="space-12"></div>
                <div class="hr hr-dotted"></div>

                <!-- SERVIS CVT MATIC -->
                <h5 class="orange">
                    <i class="ace-icon fa fa-cog"></i>
                    SERVIS CVT MATIC / SUPER MATIC
                </h5>
                <div class="space-6"></div>
                <div class="row">
                    <?php
                    $kategori_cvt = [
                        'SN_CLUTCH' => 'SN Clutch',
                        'GREASE_CVT' => 'Grease CVT',
                        'FAN_BELT' => 'Fan Belt',
                        'ROLLER' => 'Roller',
                        'RUMAH_ROLLER' => 'Rumah Roller',
                        'BOSH_ROLLER' => 'Bosh Roller',
                        'KIPAS_PULLY' => 'Kipas Pully',
                        'ORING_PULLY' => 'Oring Pully',
                        'SIL_PULLY' => 'Sil Pully',
                        'SIL_KERAS' => 'Sil Keras',
                        'SIL_KARDAN' => 'Sil Kardan'
                    ];
                    
                    foreach($kategori_cvt as $kode => $nama) {
                    ?>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <button type="button" class="btn btn-block btn-sm btn-warning btn-fm-kategori" 
                                data-kategori="<?php echo $kode; ?>"
                                data-nama="<?php echo $nama; ?>"
                                style="margin-bottom: 5px; text-align: left; white-space: normal; height: auto; min-height: 32px; padding: 6px 12px;">
                            <i class="ace-icon fa fa-angle-right"></i> <?php echo $nama; ?>
                        </button>
                    </div>
                    <?php } ?>
                </div>

                <div class="space-12"></div>
                <div class="hr hr-dotted"></div>

                <!-- SERVIS BARANG LAIN -->
                <h5 class="green">
                    <i class="ace-icon fa fa-box"></i>
                    SERVIS BARANG LAIN
                </h5>
                <div class="space-6"></div>
                <div class="row">
                    <?php
                    $kategori_lain = [
                        'CLEANER' => 'Cleaner',
                        'PEC' => 'Gurah Mesin (PEC)',
                        'TROUBLESHOOT' => 'Jasa Troubleshooting'
                    ];
                    
                    foreach($kategori_lain as $kode => $nama) {
                    ?>
                    <div class="col-xs-6 col-sm-4 col-md-3">
                        <button type="button" class="btn btn-block btn-sm btn-success btn-fm-kategori" 
                                data-kategori="<?php echo $kode; ?>"
                                data-nama="<?php echo $nama; ?>"
                                style="margin-bottom: 5px; text-align: left; white-space: normal; height: auto; min-height: 32px; padding: 6px 12px;">
                            <i class="ace-icon fa fa-angle-right"></i> <?php echo $nama; ?>
                        </button>
                    </div>
                    <?php } ?>
                </div>

                <div class="space-12"></div>

                <!-- Hasil Pencarian / Part List -->
                <div id="fmPartListContainer" style="display: none;">
                    <div class="hr hr-double"></div>
                    <h5 class="red">
                        <i class="ace-icon fa fa-list"></i>
                        Part untuk: <span id="fmKategoriNama"></span>
                        <button type="button" class="btn btn-xs btn-danger pull-right" id="btnCloseFMList">
                            <i class="ace-icon fa fa-times"></i> Tutup List
                        </button>
                    </h5>
                    <div class="space-6"></div>
                    <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                        <table class="table table-striped table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="120">Kode Part</th>
                                    <th>Nama Part</th>
                                    <th width="80">Satuan</th>
                                    <th width="120" class="text-right">Harga</th>
                                    <th width="70" class="center">Stok</th>
                                    <th width="80" class="center">Qty</th>
                                    <th width="70">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="fmPartListBody">
                                <tr>
                                    <td colspan="7" class="center">
                                        <i class="ace-icon fa fa-spinner fa-spin"></i> Loading...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <div class="modal-footer">
                <span class="pull-left">
                    <span class="label label-info arrowed-in arrowed-in-right">
                        Total Dipilih: <span id="totalPartDipilih">0</span>
                    </span>
                </span>
                <button class="btn btn-sm" data-dismiss="modal">
                    <i class="ace-icon fa fa-times"></i>
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(function($) {
    var totalDipilih = 0;
    
    // Search global - Enter key
    $('#searchGlobalFM').on('keypress', function(e) {
        if(e.which === 13) {
            $('#btnSearchGlobalFM').click();
        }
    });
    
    // Search global - Button click
    $('#btnSearchGlobalFM').on('click', function() {
        var search = $('#searchGlobalFM').val();
        if(search.length >= 2) {
            loadPartsBySearch(search);
        } else {
            alert('Minimal 2 karakter untuk pencarian');
        }
    });
    
    // Klik kategori
    $('.btn-fm-kategori').on('click', function() {
        var kategori = $(this).data('kategori');
        var nama = $(this).data('nama');
        
        $('#fmKategoriNama').text(nama);
        $('#fmPartListContainer').slideDown();
        
        // Load parts by kategori
        loadPartsByKategori(kategori);
        
        // Scroll to list
        setTimeout(function() {
            $('#fmPartListContainer')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 300);
    });
    
    // Close list
    $('#btnCloseFMList').on('click', function() {
        $('#fmPartListContainer').slideUp();
    });
    
    // Function: Load parts by kategori
    function loadPartsByKategori(kategori) {
        $('#fmPartListBody').html('<tr><td colspan="7" class="center"><i class="ace-icon fa fa-spinner fa-spin"></i> Loading...</td></tr>');
        
        $.ajax({
            url: '_handler_temuan_penawaran.php',
            type: 'GET',
            data: {
                action: 'get_parts_by_kategori',
                kategori: kategori
            },
            dataType: 'json',
            success: function(response) {
                if(response.length > 0) {
                    var html = '';
                    $.each(response, function(i, item) {
                        var stokClass = item.stok_tersedia > 0 ? 'label-success' : 'label-danger';
                        var disabled = item.stok_tersedia <= 0 ? 'disabled' : '';
                        var featured = item.is_featured == 1 ? '<i class="ace-icon fa fa-star orange"></i> ' : '';
                        
                        html += '<tr>';
                        html += '<td>' + featured + '<strong>' + item.kode_barang + '</strong></td>';
                        html += '<td>' + item.nama_barang + '</td>';
                        html += '<td>' + item.satuan + '</td>';
                        html += '<td class="text-right"><strong>Rp ' + formatNumber(item.harga_jual) + '</strong></td>';
                        html += '<td class="center"><span class="label ' + stokClass + '">' + item.stok_tersedia + '</span></td>';
                        html += '<td class="center">';
                        html += '<input type="number" class="form-control input-sm qty-fm-input" value="1" min="1" max="' + item.stok_tersedia + '" style="width: 60px; text-align: center;" ' + disabled + '>';
                        html += '</td>';
                        html += '<td class="center">';
                        html += '<button type="button" class="btn btn-success btn-xs btn-add-fm-part" ' + disabled;
                        html += ' data-kode="' + item.kode_barang + '"';
                        html += ' data-nama="' + item.nama_barang + '"';
                        html += ' data-harga="' + item.harga_jual + '"';
                        html += ' data-satuan="' + item.satuan + '"';
                        html += '><i class="ace-icon fa fa-plus"></i></button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    $('#fmPartListBody').html(html);
                } else {
                    $('#fmPartListBody').html('<tr><td colspan="7" class="center text-muted">Tidak ada part untuk kategori ini</td></tr>');
                }
            },
            error: function() {
                $('#fmPartListBody').html('<tr><td colspan="7" class="center text-danger">Error loading data</td></tr>');
            }
        });
    }
    
    // Function: Load parts by search
    function loadPartsBySearch(search) {
        $('#fmKategoriNama').text('Hasil Pencarian: "' + search + '"');
        $('#fmPartListContainer').slideDown();
        $('#fmPartListBody').html('<tr><td colspan="7" class="center"><i class="ace-icon fa fa-spinner fa-spin"></i> Loading...</td></tr>');
        
        $.ajax({
            url: '_handler_temuan_penawaran.php',
            type: 'GET',
            data: {
                action: 'search_part',
                q: search
            },
            dataType: 'json',
            success: function(response) {
                if(response.length > 0) {
                    var html = '';
                    $.each(response, function(i, item) {
                        html += '<tr>';
                        html += '<td><strong>' + item.kode_barang + '</strong></td>';
                        html += '<td>' + item.nama_barang + '</td>';
                        html += '<td>' + item.satuan + '</td>';
                        html += '<td class="text-right"><strong>Rp ' + formatNumber(item.harga_jual) + '</strong></td>';
                        html += '<td class="center"><span class="label label-info">-</span></td>';
                        html += '<td class="center">';
                        html += '<input type="number" class="form-control input-sm qty-fm-input" value="1" min="1" style="width: 60px; text-align: center;">';
                        html += '</td>';
                        html += '<td class="center">';
                        html += '<button type="button" class="btn btn-success btn-xs btn-add-fm-part"';
                        html += ' data-kode="' + item.kode_barang + '"';
                        html += ' data-nama="' + item.nama_barang + '"';
                        html += ' data-harga="' + item.harga_jual + '"';
                        html += ' data-satuan="' + item.satuan + '"';
                        html += '><i class="ace-icon fa fa-plus"></i></button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    $('#fmPartListBody').html(html);
                } else {
                    $('#fmPartListBody').html('<tr><td colspan="7" class="center text-muted">Tidak ada hasil</td></tr>');
                }
            },
            error: function() {
                $('#fmPartListBody').html('<tr><td colspan="7" class="center text-danger">Error loading data</td></tr>');
            }
        });
    }
    
    // Add part to penawaran
    $(document).on('click', '.btn-add-fm-part', function() {
        var btn = $(this);
        var row = btn.closest('tr');
        var kode = btn.data('kode');
        var nama = btn.data('nama');
        var harga = btn.data('harga');
        var satuan = btn.data('satuan');
        var qty = row.find('.qty-fm-input').val();
        
        // Set values to form
        $('#kode_barang_penawaran').val(kode);
        $('#nama_barang_penawaran').val(nama);
        $('#harga_satuan_penawaran').val(harga);
        $('#quantity_penawaran').val(qty);
        
        // Calculate total
        var total = qty * harga;
        $('#total_penawaran_display').val('Rp ' + formatNumber(total));
        
        // Show form
        $('#formTambahPenawaran').slideDown();
        
        // Visual feedback
        btn.html('<i class="ace-icon fa fa-check"></i>').removeClass('btn-success').addClass('btn-default').prop('disabled', true);
        
        totalDipilih++;
        $('#totalPartDipilih').text(totalDipilih);
        
        setTimeout(function() {
            btn.html('<i class="ace-icon fa fa-plus"></i>').removeClass('btn-default').addClass('btn-success').prop('disabled', false);
        }, 1500);
        
        // Close modal
        $('#modalFastMovesV2').modal('hide');
    });
    
    // Reset on modal close
    $('#modalFastMovesV2').on('hidden.bs.modal', function() {
        $('#fmPartListContainer').hide();
        $('#searchGlobalFM').val('');
        totalDipilih = 0;
        $('#totalPartDipilih').text('0');
    });
    
    // Helper: Format number
    function formatNumber(num) {
        return parseInt(num).toLocaleString('id-ID');
    }
});
</script>
