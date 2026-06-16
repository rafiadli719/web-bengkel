<!-- Modal Cancel Service - Simplified Version -->
<div class="modal fade" id="modalCancelService" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #dc3545; color: white;">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true" style="color: white;">&times;</span>
                </button>
                <h4 class="modal-title">
                    <i class="fa fa-ban"></i>
                    Cancel Service: <span id="cancelServiceNumber"><?php echo $no_service; ?></span>
                </h4>
            </div>

            <form method="POST" action="servis-cancel-proses.php" id="formCancelService">
                <div class="modal-body">
                    <!-- Alert Warning -->
                    <div class="alert alert-warning">
                        <i class="fa fa-warning"></i>
                        <strong>Perhatian!</strong> Pembatalan service tidak dapat diurungkan. Pastikan data sudah benar.
                    </div>

                    <input type="hidden" name="no_service" value="<?php echo $no_service; ?>">

                    <!-- Kategori Alasan -->
                    <div class="form-group">
                        <label class="control-label">
                            Kategori Alasan: <span class="text-danger">*</span>
                        </label>
                        <select name="kategori_alasan" id="kategori_alasan" class="form-control">
                            <option value="">-- Pilih Kategori --</option>
                            <option value="customer_request">Customer Request (Berubah Pikiran)</option>
                            <option value="no_stock">No Stock (Barang Tidak Ada)</option>
                            <option value="no_mekanik">No Mekanik (Kurang SDM)</option>
                            <option value="customer_no_show">Customer No Show (Tidak Datang)</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>

                    <!-- Alasan Detail / Keterangan -->
                    <div class="form-group">
                        <label class="control-label">
                            Keterangan Alasan: <span class="text-danger">*</span>
                        </label>
                        <textarea name="alasan_cancel" id="alasan_cancel" class="form-control" rows="4"
                                  placeholder="Jelaskan secara detail alasan pembatalan service..."></textarea>
                        <small class="help-block text-muted">
                            <i class="fa fa-info-circle"></i> Minimal 10 karakter
                        </small>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmCancel">
                        <i class="fa fa-ban"></i> Ya, Cancel Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for Modal Cancel Validation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formCancelService');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            var kategori = form.querySelector('select[name="kategori_alasan"]').value;
            var alasan = form.querySelector('textarea[name="alasan_cancel"]').value;

            // Validasi kategori
            if (!kategori) {
                alert('Pilih kategori alasan terlebih dahulu!');
                form.querySelector('select[name="kategori_alasan"]').focus();
                return false;
            }

            // Validasi alasan detail
            if (!alasan.trim()) {
                alert('Isi keterangan alasan terlebih dahulu!');
                form.querySelector('textarea[name="alasan_cancel"]').focus();
                return false;
            }

            // Validasi minimal karakter
            if (alasan.trim().length < 10) {
                alert('Keterangan alasan minimal 10 karakter!');
                form.querySelector('textarea[name="alasan_cancel"]').focus();
                return false;
            }

            // Konfirmasi akhir
            if (confirm('Apakah Anda yakin ingin membatalkan service ini?\n\nPembatalan tidak dapat diurungkan!')) {
                form.submit();
            }

            return false;
        });
    }
});
</script>
