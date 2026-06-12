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
                </div>
                
                <div class="table-responsive" style="max-height: 450px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-bordered">
                        <thead>
                            <tr class="info">
                                <th width="15%">Kode</th>
                                <th width="50%">Nama Keluhan</th>
                                <th width="20%">Kategori</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="keluhan-table-body">
                            <?php
                            $sql_keluhan = mysqli_query($koneksi,"SELECT * FROM tbmaster_keluhan WHERE status_aktif='1' AND status_approval='approved' ORDER BY nama_keluhan");
                            while ($keluhan = mysqli_fetch_array($sql_keluhan)) {
                            ?>
                            <tr class="keluhan-row" 
                                data-nama="<?php echo strtolower($keluhan['nama_keluhan']); ?>" 
                                data-kategori="<?php echo $keluhan['kategori']; ?>">
                                <td><?php echo $keluhan['kode_keluhan']; ?></td>
                                <td>
                                    <strong><?php echo $keluhan['nama_keluhan']; ?></strong>
                                    <?php if($keluhan['deskripsi']): ?>
                                    <br><small class="text-muted"><?php echo substr($keluhan['deskripsi'], 0, 100) . (strlen($keluhan['deskripsi']) > 100 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="label label-info"><?php echo $keluhan['kategori']; ?></span>
                                </td>
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
                <button type="button" class="btn btn-warning pull-left" onclick="tambahKeluhanBaru()">
                    <i class="fa fa-plus-circle"></i> Tambah Keluhan Baru (Perlu Approval)
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    <i class="fa fa-times"></i> Tutup
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
// Wait until jQuery is available, then initialize modal logic
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initModalSearchKeluhan);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initModalSearchKeluhan() {
        var $ = jQuery; // Alias for safety
        
        var selectedKeluhan = null;

        window.searchKeluhan = function() {
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
        };

        window.pilihKeluhan = function(kode, nama) {
            selectedKeluhan = {
                kode: kode,
                nama: nama
            };
            
            // Directly select keluhan
            konfirmasiPilihan();
        };

        window.showPreviewProses = function(kodeKeluhan, namaKeluhan) {
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
        };

        function konfirmasiPilihan() {
            if(!selectedKeluhan) return;

            // Set hidden field for kode_keluhan (try both IDs for compatibility)
            var kodeKeluhanField = document.getElementById('kode_keluhan_v2') || document.getElementById('kode_keluhan');
            if (kodeKeluhanField) {
                kodeKeluhanField.value = selectedKeluhan.kode;
            }

            // Set display field for keluhan name (try both IDs for compatibility)
            var keluhanField = document.getElementById('txtkeluhan_v2') || document.getElementById('txtkeluhan');
            if (keluhanField) {
                keluhanField.value = selectedKeluhan.nama;
                keluhanField.setAttribute('readonly', 'readonly');
                keluhanField.setAttribute('data-kode-keluhan', selectedKeluhan.kode);
            }

            // Tutup modal
            $('#modal-search-keluhan').modal('hide');
            $('#modal-preview-proses').modal('hide');

            // Show notification
            showNotification('Keluhan "' + selectedKeluhan.nama + '" berhasil dipilih', 'success');

            selectedKeluhan = null;
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
            window.searchKeluhan(); // Reset filter
            selectedKeluhan = null;
        });

        // Allow double click to select
        $(document).on('dblclick', '.keluhan-row', function() {
            var button = $(this).find('button[onclick^="pilihKeluhan"]');
            if(button.length > 0) {
                button.click();
            }
        });

        // Fungsi untuk tambah keluhan baru
        window.tambahKeluhanBaru = function() {
            $('#modal-search-keluhan').modal('hide');
            setTimeout(function() {
                $('#modal-tambah-keluhan-baru').modal('show');
            }, 300);
        };
}
</script>

<!-- Modal Tambah Keluhan Baru sudah dipindahkan ke file terpisah: modal-tambah-keluhan-baru.php -->

<script>
// NOTE: Form submit handler untuk modal tambah keluhan baru
// sudah ada di file modal-tambah-keluhan-baru.php
// Script ini di-comment untuk menghindari duplikasi handler
console.log('modal-search-keluhan.php loaded - form handler ada di modal-tambah-keluhan-baru.php');
</script>

<!-- Modal Tambah Keluhan Baru sudah INLINE di _servis_add_header_kanan_workorder_only.php -->
<!-- Include ini TIDAK DIGUNAKAN lagi untuk menghindari duplikasi -->
<script>console.log('✅ modal-search-keluhan.php: Modal tambah keluhan sudah inline');</script>