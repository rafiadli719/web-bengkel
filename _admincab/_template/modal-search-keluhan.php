<!-- File: _template/modal-search-keluhan.php -->
<!-- Modal Search Keluhan Enhanced -->
<div class="modal fade" id="modal-search-keluhan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-search"></i> Pilih Keluhan dari Master</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Cari Keluhan:</label>
                            <input type="text" id="search-keluhan-input" class="form-control" 
                                   placeholder="Ketik nama keluhan..." onkeyup="searchKeluhan()">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Kategori:</label>
                            <select id="filter-kategori" class="form-control" onchange="searchKeluhan()">
                                <option value="">Semua Kategori</option>
                                <option value="Mesin">Mesin</option>
                                <option value="Rem">Rem</option>
                                <option value="Kelistrikan">Kelistrikan</option>
                                <option value="Transmisi">Transmisi</option>
                                <option value="Ban">Ban</option>
                                <option value="Body">Body</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Prioritas:</label>
                            <select id="filter-prioritas" class="form-control" onchange="searchKeluhan()">
                                <option value="">Semua Prioritas</option>
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="darurat">Darurat</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-bordered">
                        <thead>
                            <tr class="info">
                                <th width="8%">Kode</th>
                                <th width="30%">Nama Keluhan</th>
                                <th width="20%">Deskripsi</th>
                                <th width="12%">Kategori</th>
                                <th width="10%">Prioritas</th>
                                <th width="8%">Estimasi</th>
                                <th width="10%">Admin/Kasir</th>
                                <th width="8%">Status</th>
                                <th width="5%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="keluhan-table-body">
                            <?php
                            $sql_keluhan = mysqli_query($koneksi,"SELECT mk.*, 
                                                                 COUNT(kp.id) as total_proses,
                                                                 SUM(CASE WHEN kp.wajib='1' THEN 1 ELSE 0 END) as proses_wajib
                                                                 FROM tbmaster_keluhan mk
                                                                 LEFT JOIN tbkeluhan_proses kp ON mk.kode_keluhan = kp.kode_keluhan
                                                                 GROUP BY mk.id
                                                                 ORDER BY mk.tingkat_prioritas DESC, mk.nama_keluhan ASC");
                            while ($keluhan = mysqli_fetch_array($sql_keluhan)) {
                                $priority_class = 'priority-' . $keluhan['tingkat_prioritas'];
                            ?>
                            <tr class="keluhan-row" 
                                data-nama="<?php echo strtolower($keluhan['nama_keluhan']); ?>" 
                                data-kategori="<?php echo $keluhan['kategori']; ?>"
                                data-prioritas="<?php echo $keluhan['tingkat_prioritas']; ?>">
                                <td>
                                    <small><strong><?php echo $keluhan['kode_keluhan']; ?></strong></small>
                                </td>
                                <td>
                                    <strong><?php echo $keluhan['nama_keluhan']; ?></strong>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo substr($keluhan['deskripsi'], 0, 60) . (strlen($keluhan['deskripsi']) > 60 ? '...' : ''); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="label label-info"><?php echo $keluhan['kategori']; ?></span>
                                </td>
                                <td>
                                    <span class="label <?php echo $priority_class; ?>">
                                        <?php echo ucfirst($keluhan['tingkat_prioritas']); ?>
                                    </span>
                                </td>
                                <td class="center">
                                    <small><?php echo $keluhan['estimasi_waktu']; ?> min</small>
                                </td>
                                <td class="center">
                                    <span class="label label-info">
                                        <i class="fa fa-info-circle"></i> Available
                                    </span>
                                </td>
                                <td class="center">
                                    <?php 
                                    $status_class = 'default';
                                    $status_text = 'Baru';
                                    $status_icon = 'fa-circle-o';
                                    
                                    if($keluhan['total_proses'] > 0) {
                                        $status_class = 'info';
                                        $status_text = 'Ada Proses';
                                        $status_icon = 'fa-cogs';
                                    }
                                    
                                    if($keluhan['proses_wajib'] > 0) {
                                        $status_class = 'warning';
                                        $status_text = 'Proses Wajib';
                                        $status_icon = 'fa-exclamation-triangle';
                                    }
                                    ?>
                                    <span class="label label-<?php echo $status_class; ?>" title="<?php echo $keluhan['total_proses']; ?> proses, <?php echo $keluhan['proses_wajib']; ?> wajib">
                                        <i class="fa <?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td class="center">
                                    <button type="button" class="btn btn-xs btn-primary" 
                                            onclick="pilihKeluhan('<?php echo $keluhan['kode_keluhan']; ?>', '<?php echo addslashes($keluhan['nama_keluhan']); ?>', <?php echo $keluhan['total_proses']; ?>)">
                                        <i class="fa fa-check"></i> Pilih
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i>
                            <strong>Tips:</strong> 
                            Pilih keluhan dari master untuk mendapatkan proses pengerjaan yang sudah terdefinisi. 
                            Jika tidak ada yang sesuai, Anda bisa menutup modal ini dan mengetik keluhan secara manual.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Tutup
                </button>
                <button type="button" class="btn btn-success" onclick="inputManual()">
                    <i class="fa fa-edit"></i> Input Manual
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Preview Proses -->
<div class="modal fade" id="modal-preview-proses" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-cogs"></i> Preview Proses Pengerjaan</h4>
            </div>
            <div class="modal-body" id="preview-proses-content">
                <!-- Content will be loaded -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary" onclick="konfirmasiPilihan()">
                    <i class="fa fa-check"></i> Ya, Pilih Keluhan Ini
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.priority-badge {
    font-size: 11px;
    padding: 2px 6px;
}
.priority-rendah { background-color: #5cb85c; color: white; }
.priority-sedang { background-color: #f0ad4e; color: white; }
.priority-tinggi { background-color: #d9534f; color: white; }
.priority-darurat { 
    background-color: #d9534f; 
    color: white;
    animation: blink 1.5s infinite; 
}

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0.7; }
    100% { opacity: 1; }
}

.keluhan-row:hover {
    background-color: #f5f5f5;
    cursor: pointer;
}

.badge {
    background-color: #337ab7;
}
</style>

<script>
var selectedKeluhan = null;

function searchKeluhan() {
    var input = document.getElementById('search-keluhan-input').value.toLowerCase();
    var kategori = document.getElementById('filter-kategori').value;
    var prioritas = document.getElementById('filter-prioritas').value;
    var rows = document.getElementsByClassName('keluhan-row');
    
    for (var i = 0; i < rows.length; i++) {
        var nama = rows[i].getAttribute('data-nama');
        var rowKategori = rows[i].getAttribute('data-kategori');
        var rowPrioritas = rows[i].getAttribute('data-prioritas');
        
        var showRow = true;
        
        // Filter berdasarkan nama
        if (input && nama.indexOf(input) === -1) {
            showRow = false;
        }
        
        // Filter berdasarkan kategori
        if (kategori && rowKategori !== kategori) {
            showRow = false;
        }
        
        // Filter berdasarkan prioritas
        if (prioritas && rowPrioritas !== prioritas) {
            showRow = false;
        }
        
        rows[i].style.display = showRow ? '' : 'none';
    }
}

function pilihKeluhan(kode, nama, totalProses) {
    selectedKeluhan = {
        kode: kode,
        nama: nama,
        totalProses: totalProses
    };
    
    if(totalProses > 0) {
        // Show preview proses first
        showPreviewProses(kode, nama);
    } else {
        // Directly select keluhan without proses
        konfirmasiPilihan();
    }
}

function showPreviewProses(kodeKeluhan, namaKeluhan) {
    $('#modal-preview-proses').modal('show');
    
    // Load preview via AJAX
    $.ajax({
        url: 'ajax-preview-proses.php',
        method: 'POST',
        data: { kode_keluhan: kodeKeluhan },
        success: function(response) {
            $('#preview-proses-content').html(response);
        },
        error: function() {
            $('#preview-proses-content').html('<div class="alert alert-danger">Error loading preview</div>');
        }
    });
}

function konfirmasiPilihan() {
    if(!selectedKeluhan) return;
    
    // Set ke field keluhan
    document.getElementById('txtkeluhan').value = selectedKeluhan.nama;
    document.getElementById('txtkeluhan').setAttribute('data-kode-keluhan', selectedKeluhan.kode);
    
    // Tutup modal
    $('#modal-search-keluhan').modal('hide');
    $('#modal-preview-proses').modal('hide');
    
    // Show notification
    if(selectedKeluhan.totalProses > 0) {
        showNotification('Keluhan dipilih dengan ' + selectedKeluhan.totalProses + ' proses pengerjaan', 'success');
    } else {
        showNotification('Keluhan dipilih tanpa proses khusus', 'info');
    }
    
    selectedKeluhan = null;
}

function inputManual() {
    $('#modal-search-keluhan').modal('hide');
    document.getElementById('txtkeluhan').focus();
    showNotification('Silakan ketik keluhan secara manual', 'info');
}

function showNotification(message, type) {
    // Simple notification function
    var alertClass = 'alert-' + (type || 'info');
    var notification = '<div class="alert ' + alertClass + ' alert-dismissible" style="position: fixed; top: 70px; right: 20px; z-index: 9999; min-width: 300px;">';
    notification += '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    notification += '<i class="fa fa-info-circle"></i> ' + message;
    notification += '</div>';
    
    $('body').append(notification);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        $('.alert-dismissible').fadeOut(500);
    }, 3000);
}

// Reset modal when closed
$('#modal-search-keluhan').on('hidden.bs.modal', function () {
    document.getElementById('search-keluhan-input').value = '';
    document.getElementById('filter-kategori').value = '';
    document.getElementById('filter-prioritas').value = '';
    searchKeluhan(); // Reset filter
    selectedKeluhan = null;
});

// Allow double click to select
$(document).on('dblclick', '.keluhan-row', function() {
    var button = $(this).find('button[onclick^="pilihKeluhan"]');
    if(button.length > 0) {
        button.click();
    }
});
</script>