<!-- Modal Tambah Keluhan Baru - INLINE (Di luar form parent) -->
<script>console.log('=== LOADING MODAL TAMBAH KELUHAN (INLINE - OUTSIDE FORM) ===');</script>

<div class="modal fade" id="modal-tambah-keluhan-baru" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">
                    <i class="ace-icon fa fa-plus-circle"></i> 
                    Tambah Keluhan Baru (Perlu Approval Pusat)
                </h4>
            </div>
            <form id="form-tambah-keluhan-baru">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="ace-icon fa fa-exclamation-triangle"></i>
                        <strong>Perhatian!</strong> Keluhan baru yang Anda ajukan akan masuk ke sistem dengan status <strong>PENDING</strong> 
                        dan memerlukan <strong>APPROVAL dari Staff Pusat</strong> sebelum dapat digunakan.
                    </div>

                    <div class="form-group">
                        <label for="nama_keluhan">
                            Nama Keluhan <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" id="nama_keluhan" name="nama_keluhan" 
                               placeholder="Contoh: Mesin Overheat" required maxlength="100">
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> Nama keluhan harus jelas dan spesifik
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="kategori">
                            Kategori <span class="text-danger">*</span>
                        </label>
                        <select class="form-control" id="kategori" name="kategori" required>
                            <option value="">-- Pilih Kategori --</option>
                            <option value="Mesin">Mesin</option>
                            <option value="Rem">Rem</option>
                            <option value="Elektrik">Elektrik</option>
                            <option value="Ban">Ban</option>
                            <option value="Body">Body</option>
                            <option value="Umum">Umum</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="deskripsi">
                            Deskripsi Keluhan <small class="text-muted">(Opsional)</small>
                        </label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3" 
                                  placeholder="Jelaskan detail keluhan..."></textarea>
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> Deskripsi membantu staff pusat memahami keluhan dengan lebih baik
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="alasan_pengajuan">
                            Alasan Pengajuan <small class="text-muted">(Opsional)</small>
                        </label>
                        <textarea class="form-control" id="alasan_pengajuan" name="alasan_pengajuan" rows="2" 
                                  placeholder="Mengapa keluhan ini perlu ditambahkan ke master data?"></textarea>
                        <small class="text-muted">
                            <i class="fa fa-info-circle"></i> Jelaskan mengapa keluhan ini penting untuk ditambahkan
                        </small>
                    </div>

                    <!-- Loading indicator -->
                    <div id="loading-submit" style="display: none;" class="text-center">
                        <i class="ace-icon fa fa-spinner fa-spin fa-2x text-primary"></i>
                        <p class="text-primary">Mengirim pengajuan...</p>
                    </div>

                    <!-- Alert container untuk pesan -->
                    <div id="alert-container-keluhan"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="ace-icon fa fa-times"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-warning" id="btn-submit-keluhan">
                        <i class="ace-icon fa fa-paper-plane"></i> Ajukan Keluhan Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
console.log('=== MODAL HTML LOADED (OUTSIDE FORM) ===');
// Immediate diagnostic
setTimeout(function() {
    console.log('=== DIAGNOSTIC CHECK (OUTSIDE FORM) ===');
    console.log('Modal in DOM:', document.getElementById('modal-tambah-keluhan-baru') ? 'YES' : 'NO');
    console.log('Form in DOM:', document.getElementById('form-tambah-keluhan-baru') ? 'YES' : 'NO');
    if (typeof jQuery !== 'undefined') {
        console.log('jQuery Modal:', jQuery('#modal-tambah-keluhan-baru').length);
        console.log('jQuery Form:', jQuery('#form-tambah-keluhan-baru').length);
        
        // Check if form is inside another form
        var form = jQuery('#form-tambah-keluhan-baru');
        var parentForms = form.parents('form');
        console.log('Parent forms:', parentForms.length);
        if (parentForms.length > 0) {
            console.error('❌ NESTED FORM DETECTED! Form is inside another form!');
        } else {
            console.log('✅ Form is NOT nested');
        }
    }
}, 200);
</script>

<!-- Include Modal Script -->
<?php include __DIR__ . '/_modal_keluhan_script.php'; ?>

<style>
.has-error {
    border-color: #a94442 !important;
}

#modal-tambah-keluhan-baru .modal-header.bg-warning {
    background-color: #f0ad4e;
    color: white;
}

#modal-tambah-keluhan-baru .modal-header.bg-warning .close {
    color: white;
    opacity: 0.8;
}

#modal-tambah-keluhan-baru .modal-header.bg-warning .close:hover {
    opacity: 1;
}
</style>
