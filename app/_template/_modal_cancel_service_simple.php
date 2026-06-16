<!-- Modal Cancel Service - SIMPLIFIED VERSION -->
<div class="modal fade" id="modalCancelService" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
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

                    <div class="row">
                        <div class="col-sm-6">
                            <!-- Kategori Alasan -->
                            <div class="form-group">
                                <label class="control-label">
                                    Kategori Alasan: <span class="text-danger">*</span>
                                </label>
                                <select name="kategori_alasan" class="form-control">
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="customer_request">Customer Request (Berubah Pikiran)</option>
                                    <option value="no_stock">No Stock (Barang Tidak Ada)</option>
                                    <option value="no_mekanik">No Mekanik (Kurang SDM)</option>
                                    <option value="customer_no_show">Customer No Show (Tidak Datang)</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>

                            <!-- Status Barang -->
                            <div class="form-group">
                                <label class="control-label">Status Barang:</label>
                                <select name="status_barang" class="form-control">
                                    <option value="belum_diambil">Belum Diambil</option>
                                    <option value="sudah_diambil">Sudah Diambil Customer</option>
                                    <option value="dikembalikan">Dikembalikan ke Gudang</option>
                                </select>
                            </div>

                            <!-- Status Pembayaran -->
                            <div class="form-group">
                                <label class="control-label">Status Pembayaran:</label>
                                <select name="status_pembayaran" class="form-control">
                                    <option value="belum_bayar">Belum Bayar</option>
                                    <option value="dp_dibayar">DP Sudah Dibayar</option>
                                    <option value="lunas">Lunas</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <!-- Nominal DP -->
                            <div class="form-group">
                                <label class="control-label">Nominal DP:</label>
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="number" name="nominal_dp" class="form-control text-right"
                                           placeholder="0" value="0">
                                </div>
                            </div>

                            <!-- Biaya Cancel -->
                            <div class="form-group">
                                <label class="control-label">Biaya Pembatalan:</label>
                                <div class="input-group">
                                    <span class="input-group-addon">Rp</span>
                                    <input type="number" name="biaya_cancel" class="form-control text-right"
                                           placeholder="0" value="0">
                                </div>
                                <small class="help-block text-muted">
                                    <i class="fa fa-info-circle"></i> Biaya barang/jasa yang sudah diproses + admin fee
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Alasan Detail -->
                    <div class="form-group">
                        <label class="control-label">
                            Alasan Detail: <span class="text-danger">*</span>
                        </label>
                        <textarea name="alasan_cancel" class="form-control" rows="3"
                                  placeholder="Jelaskan secara detail alasan pembatalan service..."></textarea>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="form-group">
                        <label class="control-label">Catatan Tambahan:</label>
                        <textarea name="catatan" class="form-control" rows="2"
                                  placeholder="Catatan tambahan (optional)..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Apakah Anda yakin ingin membatalkan service ini?\n\nPembatalan tidak dapat diurungkan!')">
                        <i class="fa fa-ban"></i> Ya, Cancel Service
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Simple form validation
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('formCancelService');
    if (form) {
        form.addEventListener('submit', function(e) {
            var kategori = form.querySelector('select[name="kategori_alasan"]').value;
            var alasan = form.querySelector('textarea[name="alasan_cancel"]').value;
            
            if (!kategori) {
                alert('Pilih kategori alasan terlebih dahulu!');
                e.preventDefault();
                return false;
            }
            
            if (!alasan.trim()) {
                alert('Isi alasan detail terlebih dahulu!');
                e.preventDefault();
                return false;
            }
            
            return true;
        });
    }
});
</script>
