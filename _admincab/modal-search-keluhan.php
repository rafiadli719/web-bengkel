<!-- File: modal-search-keluhan.php -->
<!-- Modal Search Keluhan -->
<div class="modal fade" id="modal-search-keluhan" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-search"></i> Pilih Keluhan</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <input type="text" id="search-keluhan-input" class="form-control" 
                               placeholder="Cari keluhan..." onkeyup="searchKeluhan()">
                    </div>
                    <div class="col-md-6">
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
                <br>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-bordered">
                        <thead>
                            <tr>
                                <th width="10%">Kode</th>
                                <th width="35%">Nama Keluhan</th>
                                <th width="20%">Kategori</th>
                                <th width="15%">Prioritas</th>
                                <th width="10%">Estimasi</th>
                                <th width="10%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="keluhan-table-body">
                            <?php
                            $sql_keluhan = mysqli_query($koneksi,"SELECT * FROM view_master_keluhan ORDER BY nama_keluhan");
                            while ($keluhan = mysqli_fetch_array($sql_keluhan)) {
                                $priority_class = 'priority-' . $keluhan['tingkat_prioritas'];
                            ?>
                            <tr class="keluhan-row" data-nama="<?php echo strtolower($keluhan['nama_keluhan']); ?>" 
                                data-kategori="<?php echo $keluhan['kategori']; ?>">
                                <td><?php echo $keluhan['kode_keluhan']; ?></td>
                                <td>
                                    <strong><?php echo $keluhan['nama_keluhan']; ?></strong>
                                    <br><small class="text-muted"><?php echo substr($keluhan['deskripsi'], 0, 80) . '...'; ?></small>
                                </td>
                                <td>
                                    <span class="label label-info"><?php echo $keluhan['kategori']; ?></span>
                                </td>
                                <td>
                                    <span class="label <?php echo $priority_class; ?>">
                                        <?php echo ucfirst($keluhan['tingkat_prioritas']); ?>
                                    </span>
                                </td>
                                <td class="center"><?php echo $keluhan['estimasi_waktu']; ?> min</td>
                                <td class="center">
                                    <button type="button" class="btn btn-xs btn-primary" 
                                            onclick="pilihKeluhan('<?php echo $keluhan['kode_keluhan']; ?>', '<?php echo addslashes($keluhan['nama_keluhan']); ?>')">
                                        <i class="fa fa-check"></i> Pilih
                                    </button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
.priority-badge {
    font-size: 11px;
    padding: 2px 6px;
}
.priority-rendah { background-color: #5cb85c; }
.priority-sedang { background-color: #f0ad4e; }
.priority-tinggi { background-color: #d9534f; }
.priority-darurat { background-color: #d9534f; animation: blink 1s infinite; }

@keyframes blink {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
</style>

<script>
function searchKeluhan() {
    var input = document.getElementById('search-keluhan-input').value.toLowerCase();
    var kategori = document.getElementById('filter-kategori').value;
    var rows = document.getElementsByClassName('keluhan-row');
    
    for (var i = 0; i < rows.length; i++) {
        var nama = rows[i].getAttribute('data-nama');
        var rowKategori = rows[i].getAttribute('data-kategori');
        
        var showRow = true;
        
        // Filter berdasarkan nama
        if (input && nama.indexOf(input) === -1) {
            showRow = false;
        }
        
        // Filter berdasarkan kategori
        if (kategori && rowKategori !== kategori) {
            showRow = false;
        }
        
        rows[i].style.display = showRow ? '' : 'none';
    }
}

function pilihKeluhan(kode, nama) {
    // Set ke field keluhan
    document.getElementById('txtkeluhan').value = nama;
    document.getElementById('txtkeluhan').setAttribute('data-kode-keluhan', kode);
    
    // Tutup modal
    $('#modal-search-keluhan').modal('hide');
    
    // Auto load proses keluhan jika ada
    loadProsesKeluhan(kode);
}

function loadProsesKeluhan(kodeKeluhan) {
    if (!kodeKeluhan) return;
    
    // AJAX untuk load proses keluhan
    $.ajax({
        url: 'ajax-get-proses-keluhan.php',
        method: 'POST',
        data: { kode_keluhan: kodeKeluhan },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showProsesKeluhan(response.data);
            }
        },
        error: function() {
            console.log('Error loading proses keluhan');
        }
    });
}

function showProsesKeluhan(proses) {
    var html = '<div class="alert alert-info"><strong>Proses Yang Disarankan:</strong></div>';
    html += '<div class="table-responsive"><table class="table table-sm table-bordered">';
    html += '<thead><tr><th>No</th><th>Proses</th><th>Tipe</th><th>Estimasi Waktu</th><th>Pilih</th></tr></thead><tbody>';
    
    for (var i = 0; i < proses.length; i++) {
        var p = proses[i];
        html += '<tr>';
        html += '<td>' + (i + 1) + '</td>';
        html += '<td>' + p.nama_proses + '</td>';
        html += '<td><span class="label label-' + (p.tipe_proses === 'jasa' ? 'primary' : 'success') + '">' + p.tipe_proses + '</span></td>';
        html += '<td>' + p.estimasi_waktu + ' min</td>';
        html += '<td><input type="checkbox" class="proses-checkbox" value="' + p.id + '" ' + (p.wajib === '1' ? 'checked disabled' : '') + '></td>';
        html += '</tr>';
    }
    
    html += '</tbody></table></div>';
    
    // Tampilkan dalam div khusus
    if (!document.getElementById('proses-keluhan-container')) {
        var container = document.createElement('div');
        container.id = 'proses-keluhan-container';
        container.className = 'col-xs-12 col-sm-12';
        document.querySelector('.col-xs-8.col-sm-12:last-of-type').after(container);
    }
    
    document.getElementById('proses-keluhan-container').innerHTML = html;
}

function showModalSearchKeluhan() {
    $('#modal-search-keluhan').modal('show');
}
</script>