<script type="text/javascript">
// Script untuk Modal Tambah Keluhan Baru - INLINE VERSION
(function() {
    console.log('=== INIT INLINE MODAL SCRIPT ===');
    
    // Wait for jQuery and DOM
    function initWhenReady() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initWhenReady, 100);
            return;
        }
        
        jQuery(document).ready(function($) {
            console.log('=== INIT MODAL TAMBAH KELUHAN BARU (INLINE) ===');
            console.log('jQuery version:', $.fn.jquery);
            console.log('Modal exists:', $('#modal-tambah-keluhan-baru').length);
            console.log('Form exists:', $('#form-tambah-keluhan-baru').length);
            
            if ($('#form-tambah-keluhan-baru').length === 0) {
                console.error('❌ Form not found!');
                return;
            }
            
            console.log('✅ Form found, attaching handlers...');

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
            
            console.log('=== MODAL TAMBAH KELUHAN BARU INITIALIZED ===');
            console.log('✅ Form submit handler attached');
            console.log('✅ Modal close handler attached');
            
            // Make global function to open modal
            window.openModalTambahKeluhanBaru = function() {
                console.log('Opening modal via global function...');
                if ($('#modal-tambah-keluhan-baru').length > 0) {
                    $('#modal-tambah-keluhan-baru').modal('show');
                    console.log('✅ Modal opened');
                } else {
                    console.error('❌ Modal not found!');
                    alert('Modal belum siap. Silakan refresh halaman.');
                }
            };
            
            console.log('✅ Global function created: window.openModalTambahKeluhanBaru');
        });
    }
    
    initWhenReady();
})();

// Fallback global function
if (typeof window.openModalTambahKeluhanBaru === 'undefined') {
    window.openModalTambahKeluhanBaru = function() {
        console.warn('⚠️ Modal script belum diinisialisasi, mencoba membuka modal...');
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
