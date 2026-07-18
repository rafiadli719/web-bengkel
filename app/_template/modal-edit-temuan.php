<?php
/**
 * Modal: Edit Temuan
 * File: modal-edit-temuan.php
 * 
 * Modal untuk mengedit detil temuan yang sudah ada
 * Menggunakan style yang konsisten dengan redesign UI
 */
?>

<style>
/* Modal Edit Temuan - Redesign Style */
#modalEditTemuan .modal-dialog {
    width: 90%;
    max-width: 600px;
}

#modalEditTemuan .modal-header {
    background: #fff;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 5px 5px 0 0;
    padding: 16px 20px;
}

#modalEditTemuan .modal-header .close {
    color: #9ca3af;
    opacity: 1;
    text-shadow: none;
}
#modalEditTemuan .modal-header .close:hover {
    color: #374151;
}

#modalEditTemuan .modal-title {
    color: #1f2937;
    font-size: 17px;
    font-weight: 600;
}
#modalEditTemuan .modal-title i {
    color: #4f46e5;
    margin-right: 6px;
}

#modalEditTemuan .modal-body {
    padding: 20px;
}

#modalEditTemuan .form-group {
    margin-bottom: 16px;
}

#modalEditTemuan .form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

#modalEditTemuan .form-group label .required {
    color: #D9534F;
}

#modalEditTemuan .form-group .form-control {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #E9ECEF;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

#modalEditTemuan .form-group .form-control:focus {
    border-color: #4A90D9;
    box-shadow: 0 0 0 3px rgba(74, 144, 217, 0.1);
    outline: none;
}

#modalEditTemuan .form-group .form-control:readonly {
    background: #F8F9FA;
    color: #6C757D;
}

#modalEditTemuan .form-group textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

#modalEditTemuan .form-row {
    display: flex;
    gap: 16px;
}

#modalEditTemuan .form-row .form-group {
    flex: 1;
}

#modalEditTemuan .btn-save {
    background: #5CB85C;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

#modalEditTemuan .btn-save:hover {
    background: #4CAF50;
}

#modalEditTemuan .btn-cancel {
    background: #E9ECEF;
    color: #333;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

#modalEditTemuan .btn-cancel:hover {
    background: #DEE2E6;
}

#modalEditTemuan .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 20px;
    border-top: 1px solid #E9ECEF;
}

#modalEditTemuan .info-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    background: rgba(74, 144, 217, 0.15);
    color: #4A90D9;
    margin-left: 8px;
}
</style>

<div class="modal fade" id="modalEditTemuan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="formEditTemuan" method="POST">
                <input type="hidden" name="btnupdatetemuan" value="1">
                <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">
                <input type="hidden" name="temuan_id" id="edit_temuan_id" value="">
                <input type="hidden" name="kode_temuan" id="edit_kode_temuan" value="">
                
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">
                        <i class="fa fa-edit"></i> Edit Temuan
                    </h4>
                </div>
                
                <div class="modal-body">
                    <!-- Nama Temuan -->
                    <div class="form-group">
                        <label>
                            Nama Temuan <span class="required">*</span>
                            <span class="info-badge" id="edit_temuan_type_badge" style="display: none;"></span>
                        </label>
                        <input type="text" class="form-control" id="edit_nama_temuan_display" name="nama_temuan" required>
                        <small id="edit_temuan_note" style="color: #6C757D; display: none;">Temuan dari master tidak dapat diubah namanya</small>
                    </div>
                    
                    <!-- Jenis & Urgensi -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Jenis Perbaikan</label>
                            <select class="form-control" id="edit_temuan_jenis" name="jenis_perbaikan">
                                <option value="setting">Setting/Servis</option>
                                <option value="penggantian_part">Penggantian Part</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Tingkat Urgensi</label>
                            <select class="form-control" id="edit_temuan_urgensi" name="tingkat_urgensi">
                                <option value="rendah">Rendah</option>
                                <option value="sedang">Sedang</option>
                                <option value="tinggi">Tinggi</option>
                                <option value="kritis">Kritis</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Deskripsi -->
                    <div class="form-group">
                        <label>Deskripsi Temuan</label>
                        <textarea class="form-control" id="edit_temuan_deskripsi" name="deskripsi_temuan" rows="3" placeholder="Deskripsi tambahan tentang temuan ini..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn-save">
                        <i class="fa fa-save"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    // Wait for jQuery
    function initEditTemuanModal() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initEditTemuanModal, 100);
            return;
        }
        var $ = jQuery;
        
        // Function to load temuan data
        window.loadTemuanForEdit = function(temuanId) {
            // Reset form
            $('#formEditTemuan')[0].reset();
            $('#edit_temuan_id').val(temuanId);
            
            // Show loading
            $('#edit_nama_temuan_display').val('Loading...').prop('readonly', true);
            
            // Load data via AJAX
            $.ajax({
                url: '_handler_temuan_penawaran.php',
                type: 'GET',
                data: {
                    action: 'get_temuan_info',
                    id: temuanId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data) {
                        var data = response.data;
                        
                        // Set values
                        $('#edit_temuan_id').val(data.id);
                        $('#edit_kode_temuan').val(data.kode_temuan || '');
                        $('#edit_nama_temuan_display').val(data.nama_temuan || '');
                        $('#edit_temuan_deskripsi').val(data.deskripsi_temuan || '');
                        $('#edit_temuan_jenis').val(data.jenis_perbaikan || 'setting');
                        $('#edit_temuan_urgensi').val(data.tingkat_urgensi || 'sedang');
                        
                        // Check if from master
                        if (data.kode_temuan) {
                            $('#edit_nama_temuan_display').prop('readonly', true);
                            $('#edit_temuan_type_badge').text('Master').show();
                            $('#edit_temuan_note').show();
                        } else {
                            $('#edit_nama_temuan_display').prop('readonly', false);
                            $('#edit_temuan_type_badge').text('Custom').css({
                                'background': 'rgba(240, 173, 78, 0.15)',
                                'color': '#F0AD4E'
                            }).show();
                            $('#edit_temuan_note').hide();
                        }
                    } else {
                        alert('Error: ' + (response.message || 'Gagal memuat data temuan'));
                        $('#modalEditTemuan').modal('hide');
                    }
                },
                error: function() {
                    alert('Error: Gagal menghubungi server');
                    $('#modalEditTemuan').modal('hide');
                }
            });
        };
        
        // Form submit
        $('#formEditTemuan').on('submit', function(e) {
            var namaTemuan = $('#edit_nama_temuan_display').val().trim();
            if (!namaTemuan) {
                e.preventDefault();
                alert('Nama temuan tidak boleh kosong');
                $('#edit_nama_temuan_display').focus();
                return false;
            }
            
            // Show loading on button
            var btn = $(this).find('.btn-save');
            btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
            
            return true;
        });
        
        // Reset on modal close
        $('#modalEditTemuan').on('hidden.bs.modal', function() {
            $('#formEditTemuan')[0].reset();
            $('#edit_temuan_type_badge').hide();
            $('#edit_temuan_note').hide();
            $('#edit_nama_temuan_display').prop('readonly', false);
            var btn = $(this).find('.btn-save');
            btn.prop('disabled', false).html('<i class="fa fa-save"></i> Simpan Perubahan');
        });
    }
    
    // Initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditTemuanModal);
    } else {
        initEditTemuanModal();
    }
})();
</script>
