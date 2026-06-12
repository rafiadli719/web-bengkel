<!-- File: modal-tambah-keluhan-baru.php -->
<script>console.log('=== LOADING MODAL HTML: modal-tambah-keluhan-baru.php ===');</script>
<!-- Modal Tambah Keluhan Baru (Perlu Approval Pusat) -->
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
<script>console.log('=== MODAL HTML LOADED: #modal-tambah-keluhan-baru ===');</script>

<script type="text/javascript">
// DEFER EXECUTION until jQuery is loaded - with retry mechanism
(function() {
    var maxRetries = 50; // Max 5 seconds (50 * 100ms)
    var retryCount = 0;
    
    function tryInit() {
        if (typeof jQuery !== 'undefined') {
            // jQuery is loaded, initialize
            jQuery(document).ready(initModalTambahKeluhan);
        } else {
            // jQuery not loaded yet, retry
            retryCount++;
            if (retryCount < maxRetries) {
                setTimeout(tryInit, 100); // Retry after 100ms
            } else {
                console.error('Modal Tambah Keluhan: jQuery not loaded after 5 seconds');
            }
        }
    }
    
    // Start trying to initialize
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInit);
    } else {
        tryInit();
    }

    function initModalTambahKeluhan() {
        var $ = jQuery;
        
        console.log('=== INIT MODAL TAMBAH KELUHAN BARU ===');
        console.log('jQuery version:', $.fn.jquery);
        
        // Check if form exists before proceeding
        if ($('#form-tambah-keluhan-baru').length === 0) {
            console.warn('Modal form not found yet, will retry... (attempt ' + (window.modalRetryCount || 0) + ')');
            window.modalRetryCount = (window.modalRetryCount || 0) + 1;
            
            // Retry up to 10 times with increasing delay
            if (window.modalRetryCount < 10) {
                setTimeout(function() {
                    if ($('#form-tambah-keluhan-baru').length > 0) {
                        console.log('✅ Form found on retry, initializing...');
                        initModalTambahKeluhan();
                    } else {
                        console.log('⏳ Retrying... (' + window.modalRetryCount + '/10)');
                        initModalTambahKeluhan();
                    }
                }, 500);
            } else {
                console.error('❌ Form still not found after 10 retries');
                console.error('Checking DOM:', {
                    'modal exists': $('#modal-tambah-keluhan-baru').length,
                    'form exists': $('#form-tambah-keluhan-baru').length,
                    'body': $('body').length
                });
            }
            return;
        }
        
        // Reset retry count
        window.modalRetryCount = 0;
        
        console.log('Form exists:', true);

        // Reset form when modal is closed
        $('#modal-tambah-keluhan-baru').on('hidden.bs.modal', function () {
            $('#form-tambah-keluhan-baru')[0].reset();
            $('#alert-container-keluhan').html('');
            $('#loading-submit').hide();
            $('#btn-submit-keluhan').prop('disabled', false);
        });

        // Form validation and submit
        $('#form-tambah-keluhan-baru').on('submit', function(e) {
            e.preventDefault();

            // Validasi form
            var namaKeluhan = $('#nama_keluhan').val().trim();
            var kategori = $('#kategori').val();

            if (!namaKeluhan) {
                showAlert('danger', 'Nama keluhan harus diisi!');
                $('#nama_keluhan').focus();
                return false;
            }

            if (!kategori) {
                showAlert('danger', 'Kategori harus dipilih!');
                $('#kategori').focus();
                return false;
            }

            // Konfirmasi sebelum submit
            if (!confirm('Apakah Anda yakin ingin mengajukan keluhan baru ini?\n\nKeluhan akan masuk dengan status PENDING dan memerlukan approval dari staff pusat.')) {
                return false;
            }

            // Disable button dan tampilkan loading
            $('#btn-submit-keluhan').prop('disabled', true);
            $('#loading-submit').show();
            $('#alert-container-keluhan').html('');

            // Kirim data via AJAX
            $.ajax({
                url: 'ajax-submit-keluhan-baru-debug.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    $('#loading-submit').hide();
                    $('#btn-submit-keluhan').prop('disabled', false);

                    if (response.success) {
                        // Tampilkan pesan sukses
                        showAlert('success', 
                            '<strong>Berhasil!</strong> ' + response.message + 
                            '<br><small>Kode Keluhan: <strong>' + response.kode_keluhan + '</strong></small>' +
                            '<br><small>Status: <span class="label label-warning">PENDING</span></small>' +
                            '<br><br><em>Keluhan akan dapat digunakan setelah diapprove oleh staff pusat.</em>'
                        );

                        // Reset form setelah 2 detik
                        setTimeout(function() {
                            $('#form-tambah-keluhan-baru')[0].reset();
                            $('#alert-container-keluhan').html('');
                            
                            // Tutup modal setelah 3 detik
                            setTimeout(function() {
                                $('#modal-tambah-keluhan-baru').modal('hide');
                                
                                // Optional: Refresh halaman atau update data
                                // window.location.reload();
                            }, 3000);
                        }, 2000);

                    } else {
                        // Tampilkan pesan error
                        showAlert('danger', '<strong>Gagal!</strong> ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading-submit').hide();
                    $('#btn-submit-keluhan').prop('disabled', false);
                    
                    var errorMsg = 'Terjadi kesalahan saat mengirim data.';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMsg = response.message;
                        }
                    } catch(e) {
                        errorMsg += ' ' + error;
                    }
                    
                    showAlert('danger', '<strong>Error!</strong> ' + errorMsg);
                }
            });

            return false;
        });

        // Function untuk menampilkan alert
        function showAlert(type, message) {
            var alertClass = 'alert-' + type;
            var iconClass = type === 'success' ? 'fa-check-circle' : 
                           type === 'danger' ? 'fa-exclamation-circle' : 
                           'fa-info-circle';
            
            var html = '<div class="alert ' + alertClass + ' alert-dismissible" role="alert">' +
                      '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                      '<i class="ace-icon fa ' + iconClass + '"></i> ' +
                      message +
                      '</div>';
            
            $('#alert-container-keluhan').html(html);
            
            // Scroll to alert
            $('#alert-container-keluhan')[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        // Real-time validation
        $('#nama_keluhan').on('blur', function() {
            var val = $(this).val().trim();
            if (val.length > 0 && val.length < 5) {
                $(this).addClass('has-error');
                showAlert('warning', 'Nama keluhan terlalu pendek. Minimal 5 karakter.');
            } else {
                $(this).removeClass('has-error');
            }
        });

        // Character counter untuk textarea
        $('#deskripsi, #alasan_pengajuan').on('input', function() {
            var maxLength = 500;
            var currentLength = $(this).val().length;
            var remaining = maxLength - currentLength;
            
            var counterId = $(this).attr('id') + '-counter';
            if ($('#' + counterId).length === 0) {
                $(this).after('<small id="' + counterId + '" class="text-muted pull-right"></small>');
            }
            
            $('#' + counterId).text(remaining + ' karakter tersisa');
            
            if (remaining < 50) {
                $('#' + counterId).removeClass('text-muted').addClass('text-warning');
            } else {
                $('#' + counterId).removeClass('text-warning').addClass('text-muted');
            }
        });
        
        console.log('=== MODAL TAMBAH KELUHAN BARU INITIALIZED ===');
        console.log('Form submit handler attached:', $('#form-tambah-keluhan-baru').length > 0);
        console.log('Modal close handler attached:', $('#modal-tambah-keluhan-baru').length > 0);
        
        // Make global function to open modal
        window.openModalTambahKeluhanBaru = function() {
            if ($('#modal-tambah-keluhan-baru').length > 0) {
                $('#modal-tambah-keluhan-baru').modal('show');
                console.log('Modal opened via global function');
            } else {
                console.error('Modal not found!');
                alert('Modal belum siap. Silakan refresh halaman.');
            }
        };
    }
})();

// Fallback global function jika script belum diinisialisasi
if (typeof window.openModalTambahKeluhanBaru === 'undefined') {
    window.openModalTambahKeluhanBaru = function() {
        console.warn('Modal script belum diinisialisasi, mencoba membuka modal...');
        if (typeof jQuery !== 'undefined' && jQuery('#modal-tambah-keluhan-baru').length > 0) {
            jQuery('#modal-tambah-keluhan-baru').modal('show');
        } else {
            alert('Modal belum siap. Silakan tunggu beberapa saat atau refresh halaman.');
        }
    };
}
</script>

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
