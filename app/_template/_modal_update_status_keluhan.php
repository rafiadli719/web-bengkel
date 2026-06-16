<?php
// File: _template/_modal_update_status_keluhan.php
?>
<div class="modal fade" id="modalUpdateStatusKeluhan" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-edit"></i> Update Status Keluhan</h4>
            </div>
            <div class="modal-body">
                <form id="formUpdateStatusKeluhan">
                    <input type="hidden" id="keluhan_id" name="id">
                    
                    <div class="form-group">
                        <label>Keluhan:</label>
                        <input type="text" id="keluhan_text" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Status:</label>
                        <select id="status_keluhan" name="status" class="form-control">
                            <option value="datang">Datang</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="tidak_selesai">Tidak Selesai / Pending</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="keterangan_keluhan_group" style="display:none;">
                        <label>Keterangan (Alasan Tidak Selesai):</label>
                        <textarea id="keterangan_keluhan" name="keterangan" class="form-control" rows="3" placeholder="Jelaskan alasan kenapa tidak selesai..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSimpanStatusKeluhan">
                    <i class="fa fa-save"></i> Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initModalUpdateStatus);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initModalUpdateStatus() {
    var $ = jQuery;
    
    // Save handler
    $('#btnSimpanStatusKeluhan').click(function() {
        var btn = $(this);
        var originalText = btn.html();
        
        // Validate
        if($('#status_keluhan').val() == 'tidak_selesai' && !$('#keterangan_keluhan').val().trim()) {
            alert('Mohon isi keterangan alasan tidak selesai!');
            $('#keterangan_keluhan').focus();
            return;
        }
        
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
        
        var data = {
            id: $('#keluhan_id').val(),
            status: $('#status_keluhan').val(),
            keterangan: $('#keterangan_keluhan').val()
        };
        
        $.ajax({
            url: '_ajax/update_status_keluhan.php',
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    $('#modalUpdateStatusKeluhan').modal('hide');
                    alert('Status berhasil diupdate!');
                    
                    // Call global refresh function if exists
                    if(typeof window.refreshTabelKeluhan === 'function') {
                        window.refreshTabelKeluhan();
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Gagal update: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('Error AJAX: ' + error);
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
}
</script>
