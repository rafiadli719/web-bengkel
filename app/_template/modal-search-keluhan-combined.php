<!-- Modal Search Keluhan Combined dengan WorkOrder -->
<div class="modal fade" id="modal-search-keluhan-combined" tabindex="-1" role="dialog" aria-labelledby="modalSearchKeluhanLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="modalSearchKeluhanLabel">
                    <i class="ace-icon fa fa-search"></i> Pilih Keluhan & WorkOrder
                </h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert alert-info">
                            <i class="ace-icon fa fa-info-circle"></i>
                            <strong>Info:</strong> Pilih keluhan dari daftar di bawah. WorkOrder akan otomatis dipilih berdasarkan keluhan yang Anda pilih.
                        </div>
                    </div>
                </div>
                
                <!-- Search Filter -->
                <div class="row">
                    <div class="col-sm-8">
                        <div class="form-group">
                            <label>Cari Keluhan:</label>
                            <input type="text" id="search-keluhan-input" class="form-control" 
                                   placeholder="Ketik nama keluhan, kategori, atau kode..." 
                                   onkeyup="filterKeluhan()">
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label>Filter Kategori:</label>
                            <select id="filter-kategori" class="form-control" onchange="filterKeluhan()">
                                <option value="">Semua Kategori</option>
                                <option value="Mesin">Mesin</option>
                                <option value="Rem">Rem</option>
                                <option value="Elektrik">Elektrik</option>
                                <option value="Ban">Ban</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Tabel Keluhan -->
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-bordered table-hover" id="table-keluhan-search">
                        <thead style="position: sticky; top: 0; background: white; z-index: 10;">
                            <tr class="info">
                                <th width="8%" class="text-center">Kode</th>
                                <th width="25%">Nama Keluhan</th>
                                <th width="15%">Kategori</th>
                                <th width="8%" class="text-center">Prioritas</th>
                                <th width="8%" class="text-center">Est. Waktu</th>
                                <th width="12%">WorkOrder</th>
                                <th width="15%">Nama WO</th>
                                <th width="9%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-keluhan-search">
                            <?php
                            if(isset($koneksi)) {
                                try {
                                    $query_keluhan_search = "SELECT 
                                                               mk.id,
                                                               mk.kode_keluhan,
                                                               mk.nama_keluhan,
                                                               mk.deskripsi,
                                                               mk.kategori,
                                                               mk.estimasi_waktu,
                                                               mk.tingkat_prioritas,
                                                               mk.workorder_default,
                                                               wo.nama_wo,
                                                               wo.harga as harga_wo,
                                                               wo.waktu as waktu_wo
                                                             FROM tbmaster_keluhan mk
                                                             LEFT JOIN tbworkorderheader wo ON mk.workorder_default = wo.kode_wo
                                                             WHERE mk.status_aktif = '1'
                                                             ORDER BY mk.tingkat_prioritas DESC, mk.kategori ASC, mk.nama_keluhan ASC";
                                    $result_keluhan_search = mysqli_query($koneksi, $query_keluhan_search);
                                    
                                    if($result_keluhan_search && mysqli_num_rows($result_keluhan_search) > 0) {
                                        while($row_search = mysqli_fetch_array($result_keluhan_search)) {
                                            $prioritas_class = '';
                                            $prioritas_text = '';
                                            switch($row_search['tingkat_prioritas']) {
                                                case 'darurat': 
                                                    $prioritas_class = 'label-danger'; 
                                                    $prioritas_text = 'Darurat';
                                                    break;
                                                case 'tinggi': 
                                                    $prioritas_class = 'label-warning'; 
                                                    $prioritas_text = 'Tinggi';
                                                    break;
                                                case 'sedang': 
                                                    $prioritas_class = 'label-info'; 
                                                    $prioritas_text = 'Sedang';
                                                    break;
                                                case 'rendah': 
                                                    $prioritas_class = 'label-success'; 
                                                    $prioritas_text = 'Rendah';
                                                    break;
                                                default: 
                                                    $prioritas_class = 'label-default';
                                                    $prioritas_text = 'Normal';
                                            }
                                            
                                            echo "<tr class='keluhan-row' ";
                                            echo "data-kode='" . htmlspecialchars($row_search['kode_keluhan']) . "' ";
                                            echo "data-nama='" . htmlspecialchars($row_search['nama_keluhan']) . "' ";
                                            echo "data-kategori='" . htmlspecialchars($row_search['kategori']) . "' ";
                                            echo "data-workorder='" . htmlspecialchars($row_search['workorder_default']) . "'>";
                                            
                                            echo "<td class='text-center'>" . htmlspecialchars($row_search['kode_keluhan']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row_search['nama_keluhan']) . "</td>";
                                            echo "<td>" . htmlspecialchars($row_search['kategori']) . "</td>";
                                            echo "<td class='text-center'>";
                                            echo "<span class='label $prioritas_class'>$prioritas_text</span>";
                                            echo "</td>";
                                            echo "<td class='text-center'>" . $row_search['estimasi_waktu'] . " min</td>";
                                            echo "<td>" . htmlspecialchars($row_search['workorder_default'] ?? '-') . "</td>";
                                            echo "<td>" . htmlspecialchars($row_search['nama_wo'] ?? '-') . "</td>";
                                            echo "<td class='text-center'>";
                                            echo "<button type='button' class='btn btn-sm btn-success' ";
                                            echo "onclick=\"selectKeluhanCombined('" . htmlspecialchars($row_search['kode_keluhan']) . "', '" . htmlspecialchars($row_search['nama_keluhan']) . "', '" . htmlspecialchars($row_search['workorder_default']) . "')\" ";
                                            echo "title='Pilih Keluhan'>";
                                            echo "<i class='ace-icon fa fa-check'></i> Pilih";
                                            echo "</button>";
                                            echo "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr>";
                                        echo "<td colspan='8' class='text-center text-muted'>";
                                        echo "<i class='fa fa-info-circle'></i> Tidak ada data keluhan yang tersedia";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } catch (Exception $e) {
                                    echo "<tr>";
                                    echo "<td colspan='8' class='text-center text-danger'>";
                                    echo "<i class='fa fa-exclamation-triangle'></i> Error loading data keluhan";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr>";
                                echo "<td colspan='8' class='text-center text-warning'>";
                                echo "<i class='fa fa-warning'></i> Database connection not available";
                                echo "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <div class="row">
                    <div class="col-sm-8">
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> 
                            <strong>Tip:</strong> Keluhan dengan prioritas "Darurat" dan "Tinggi" akan otomatis menggunakan WorkOrder "Servis Lengkap"
                        </small>
                    </div>
                    <div class="col-sm-4 text-right">
                        <button type="button" class="btn btn-default" data-dismiss="modal">
                            <i class="ace-icon fa fa-times"></i> Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// Function untuk filter keluhan
function filterKeluhan() {
    var searchText = document.getElementById('search-keluhan-input').value.toLowerCase();
    var selectedKategori = document.getElementById('filter-kategori').value.toLowerCase();
    var rows = document.querySelectorAll('#tbody-keluhan-search tr.keluhan-row');
    
    rows.forEach(function(row) {
        var kode = row.getAttribute('data-kode').toLowerCase();
        var nama = row.getAttribute('data-nama').toLowerCase();
        var kategori = row.getAttribute('data-kategori').toLowerCase();
        
        var matchSearch = searchText === '' || 
                         kode.includes(searchText) || 
                         nama.includes(searchText) ||
                         kategori.includes(searchText);
        
        var matchKategori = selectedKategori === '' || kategori === selectedKategori;
        
        if (matchSearch && matchKategori) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Function untuk clear search saat modal dibuka
$('#modal-search-keluhan-combined').on('shown.bs.modal', function () {
    document.getElementById('search-keluhan-input').value = '';
    document.getElementById('filter-kategori').value = '';
    filterKeluhan();
    document.getElementById('search-keluhan-input').focus();
});

// Function untuk select keluhan dengan workorder
function selectKeluhanCombined(kodeKeluhan, namaKeluhan, workorderDefault) {
    // Set nilai ke input keluhan
    var keluhanInput = document.querySelector('input[name="txtkeluhan"]');
    if (keluhanInput) {
        keluhanInput.value = namaKeluhan;
    }
    
    // Trigger event untuk auto select workorder
    if (typeof autoSelectWorkorder === 'function') {
        autoSelectWorkorder();
    }
    
    // Show preview workorder jika ada
    if (workorderDefault && workorderDefault !== '') {
        // AJAX call untuk mendapatkan detail workorder
        $.ajax({
            url: 'ajax-get-workorder-detail.php',
            type: 'GET',
            data: { kode_wo: workorderDefault },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.workorder && typeof showWorkorderPreview === 'function') {
                    selectedWorkorder = response.workorder;
                    showWorkorderPreview(response.workorder);
                }
            },
            error: function() {
                console.log('Error loading workorder detail');
            }
        });
    }
    
    // Tutup modal
    $('#modal-search-keluhan-combined').modal('hide');
}

// Enhanced search dengan Enter key
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('search-keluhan-input');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                filterKeluhan();
            }
        });
    }
});
</script>

<style>
/* Custom styles untuk modal */
#modal-search-keluhan-combined .modal-dialog {
    width: 90%;
    max-width: 1200px;
}

#modal-search-keluhan-combined .table th {
    background-color: #f5f5f5;
    font-weight: bold;
    border-bottom: 2px solid #ddd;
}

#modal-search-keluhan-combined .keluhan-row:hover {
    background-color: #f0f8ff;
    cursor: pointer;
}

#modal-search-keluhan-combined .table-responsive {
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    #modal-search-keluhan-combined .modal-dialog {
        width: 95%;
        margin: 10px auto;
    }
    
    #modal-search-keluhan-combined .table th,
    #modal-search-keluhan-combined .table td {
        font-size: 12px;
        padding: 5px;
    }
}
</style>