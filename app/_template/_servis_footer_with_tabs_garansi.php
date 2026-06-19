<script type="text/javascript">
jQuery(function($) {
    
    // Initialize bootstrap tabs
    $('.nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
        
        console.log('Tab GARANSI switched to:', $(this).attr('href'));
        
        // Auto-focus first input in active tab
        var target = $(this).attr('href');
        setTimeout(function() {
            $(target + ' input:visible:first').focus();
        }, 100);
    });
    
    // GARANSI SERVICE SPECIFIC FUNCTIONS
    
    // Auto-calculate percentage for mechanics - GARANSI STYLE
    function calculateMekanikPersentaseGaransi() {
        var total = 0;
        for(var i = 1; i <= 4; i++) {
            var val = parseInt($('#txtpersen_kerja' + i).val()) || 0;
            total += val;
        }
        
        $('#totalPersenMekanik').text(total);
        
        var statusDiv = $('#persentaseStatusMekanik');
        var messageSpan = $('#persenMessageMekanik');
        
        if (total == 100) {
            statusDiv.removeClass().addClass('alert alert-success');
            messageSpan.text(' - Sempurna! Garansi Coverage 100%').css('color', 'green');
        } else if (total > 100) {
            statusDiv.removeClass().addClass('alert alert-danger');
            messageSpan.text(' - Melebihi 100%! Check garansi coverage').css('color', 'red');
        } else if (total > 0) {
            statusDiv.removeClass().addClass('alert alert-warning');
            messageSpan.text(' - Kurang dari 100%! Partial garansi coverage').css('color', 'orange');
        } else {
            statusDiv.removeClass().addClass('alert alert-info');
            messageSpan.text(' - Belum ada coverage garansi').css('color', 'blue');
        }
    }
    
    // Validation specific for warranty service
    function validateGaransiService() {
        var kepala1 = $('#cbokepala1').val();
        var admin1 = $('#cboadmin1').val();
        var mekanik1 = $('#cbomekanik1').val();
        
        if (!kepala1) {
            alert('Kepala Mekanik wajib diisi untuk service garansi!');
            $('#cbokepala1').focus();
            return false;
        }
        
        if (!admin1) {
            alert('Admin/Kasir minimal 1 harus diisi untuk service garansi!');
            $('#cboadmin1').focus();
            return false;
        }
        
        if (!mekanik1) {
            alert('Minimal 1 mekanik harus diisi untuk service garansi!');
            $('#cbomekanik1').focus();
            return false;
        }
        
        return true;
    }
    
    // Auto-set percentage when mechanic selected for GARANSI
    $('select[name^="cbomekanik"]').on('change', function() {
        var selectedMechanics = 0;
        $('select[name^="cbomekanik"]').each(function() {
            if ($(this).val() !== '') selectedMechanics++;
        });
        
        // Auto-distribute percentage for warranty coverage
        if (selectedMechanics > 0) {
            var autoPercent = Math.floor(100 / selectedMechanics);
            var remainder = 100 % selectedMechanics;
            var count = 0;
            
            $('select[name^="cbomekanik"]').each(function() {
                var number = $(this).attr('name').replace('cbomekanik', '');
                var persenInput = $('#txtpersen_kerja' + number);
                
                if ($(this).val() !== '') {
                    count++;
                    var percent = autoPercent + (count <= remainder ? 1 : 0);
                    persenInput.val(percent);
                } else {
                    persenInput.val('0');
                }
            });
        }
        
        calculateMekanikPersentaseGaransi();
    });
    
    // Update percentage calculation on input change
    $('input[name^="txtpersen_kerja"], input[name^="txtpersen_admin"]').on('input keyup change', function() {
        calculateMekanikPersentaseGaransi();
    });
    
    // WARRANTY SERVICE STATUS MANAGEMENT
    function updateStatusGaransi(status) {
        var validStatuses = ['baru', 'proses', 'selesai', 'diambil', 'warranty_expired'];
        if (validStatuses.includes(status)) {
            $('#cbostatus').val(status).trigger('change');
        }
    }
    
    // KELUHAN MANAGEMENT - GARANSI SPECIFIC
    $('#btnaddkeluhan').on('click', function(e) {
        e.preventDefault();
        
        var keluhan = $('#txtkeluhan').val().trim();
        var noService = '<?php echo $no_service ?? ""; ?>';
        
        if (!noService) {
            alert('Simpan service garansi terlebih dahulu!');
            return false;
        }
        
        if (!keluhan) {
            alert('Masukkan keluhan garansi terlebih dahulu!');
            $('#txtkeluhan').focus();
            return false;
        }
        
        // Submit form with warranty complaint
        $(this).closest('form').submit();
    });
    
    // WARRANTY COVERAGE FUNCTIONS
    function checkWarrantyCoverage() {
        var warrantyType = $('#warranty_type').val();
        var serviceDate = new Date($('#service_date').val() || Date.now());
        var warrantyStart = new Date($('#warranty_start_date').val());
        var warrantyEnd = new Date($('#warranty_end_date').val());
        
        if (warrantyType && warrantyStart && warrantyEnd) {
            var currentDate = new Date();
            
            if (currentDate > warrantyEnd) {
                alert('Peringatan: Periode garansi telah expired!\nTanggal berakhir: ' + warrantyEnd.toLocaleDateString());
                return false;
            } else if (currentDate < warrantyStart) {
                alert('Peringatan: Service dilakukan sebelum periode garansi dimulai!');
                return false;
            }
        }
        
        return true;
    }
    
    // FORM SUBMISSION VALIDATION
    $('form').on('submit', function(e) {
        var submitBtn = $(e.originalEvent.submitter);
        
        // Only validate for main save buttons
        if (submitBtn.attr('name') === 'btnsimpan' || submitBtn.attr('id') === 'btnsimpan') {
            if (!validateGaransiService()) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // KEYBOARD SHORTCUTS - GARANSI SERVICE
    $(document).on('keydown', function(e) {
        // Ctrl + 1-5 for tab navigation
        if (e.ctrlKey) {
            switch(e.keyCode) {
                case 49: // Ctrl + 1
                    e.preventDefault();
                    $('a[href="#service-details"]').click();
                    break;
                case 50: // Ctrl + 2
                    e.preventDefault();
                    $('a[href="#workorder-details"]').click();
                    break;
                case 51: // Ctrl + 3
                    e.preventDefault();
                    $('a[href="#service-items"]').click();
                    break;
                case 52: // Ctrl + 4
                    e.preventDefault();
                    $('a[href="#service-jasa"]').click();
                    break;
                case 53: // Ctrl + 5
                    e.preventDefault();
                    $('a[href="#service-actions"]').click();
                    break;
                case 83: // Ctrl + S
                    e.preventDefault();
                    if ($('button[name="btnsimpan"]').length) {
                        $('button[name="btnsimpan"]').click();
                    }
                    break;
                case 87: // Ctrl + W
                    e.preventDefault();
                    checkWarrantyCoverage();
                    break;
            }
        }
        
        // F4 for warranty check
        if (e.keyCode === 115) { // F4
            e.preventDefault();
            checkWarrantyCoverage();
        }
        
        // F2 for keluhan search
        if (e.keyCode === 113) { // F2
            e.preventDefault();
            showModalSearchKeluhan();
        }
    });
    
    // AUTO-CALCULATION ON PAGE LOAD
    calculateMekanikPersentaseGaransi();
    
    // Force first tab to be active
    setTimeout(function() {
        if ($('.nav-tabs li:first a').length) {
            $('.nav-tabs li:first a').tab('show');
        }
    }, 500);
    
    console.log('GARANSI Service footer initialized successfully');
});

// ========== KEPALA MEKANIK HARIAN AUTO-FILL FUNCTIONS ==========
// Auto Fill Kepala Mekanik Function
window.autoFillKepalaMetanik = function() {
    $.ajax({
        url: 'get_kepala_mekanik_harian.php?ajax=get_kepala_mekanik',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.has_data) {
                var data = response.data;
                var filled = false;

                // Find kepala mekanik 1 in dropdown and select it
                if (data.kepala_mekanik_1) {
                    $('#cbokepala_mekanik1 option').each(function() {
                        if ($(this).text().trim() === data.kepala_mekanik_1.trim()) {
                            $(this).prop('selected', true);
                            filled = true;
                            return false;
                        }
                    });
                }

                // Find kepala mekanik 2 in dropdown and select it
                if (data.kepala_mekanik_2) {
                    $('#cbokepala_mekanik2 option').each(function() {
                        if ($(this).text().trim() === data.kepala_mekanik_2.trim()) {
                            $(this).prop('selected', true);
                            filled = true;
                            return false;
                        }
                    });
                }

                // Auto-calculate percentage for ALL selected mechanics
                // This will distribute 100% evenly among all selected mechanics
                if (filled && typeof window.autoCalculatePercentage === 'function') {
                    window.autoCalculatePercentage();
                } else if (filled && typeof calculateMekanikPersentaseGaransi === 'function') {
                    calculateMekanikPersentaseGaransi();
                }

                alert('Kepala mekanik berhasil di-auto fill! Persentase dibagi otomatis ke semua mekanik.');
            } else {
                alert('Belum ada data kepala mekanik untuk hari ini');
            }
        },
        error: function() {
            alert('Gagal mengambil data kepala mekanik');
        }
    });
};

// Refresh Status Function
window.refreshKepalaMetanikHarian = function() {
    location.reload();
};

// Global functions for warranty service
function showModalSearchKeluhan() {
    $('#modal-search-keluhan').modal('show');
}

function selectKeluhan(keluhan) {
    $('#txtkeluhan').val(keluhan);
    $('#modal-search-keluhan').modal('hide');
}

function hapusKeluhan(id) {
    if (!confirm('Yakin ingin menghapus keluhan ini dari service garansi?')) {
        return;
    }
    
    $.post('_ajax/hapus_spk_keluhan.php', {id: id}, function(response) {
        var result = JSON.parse(response);
        if (result.success) {
            alert('Keluhan garansi berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

function hapusWorkOrder(id) {
    if (!confirm('Yakin ingin menghapus work order ini dari service garansi?')) {
        return;
    }
    
    $.post('_ajax/hapus_spk_workorder.php', {id: id}, function(response) {
        var result = JSON.parse(response);
        if (result.success) {
            alert('Work order garansi berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

function refreshTabelKeluhan() {
    location.reload();
}

function checkWarrantyClaim() {
    var warrantyType = $('#warranty_type').val();
    
    if (!warrantyType) {
        alert('Tipe garansi belum dipilih!');
        return false;
    }
    
    // Check warranty coverage and claim eligibility
    var currentDate = new Date();
    var warrantyEnd = new Date($('#warranty_end_date').val());
    
    if (currentDate > warrantyEnd) {
        var proceed = confirm('Garansi sudah expired!\nApakah tetap ingin melanjutkan sebagai service berbayar?');
        if (proceed) {
            $('#warranty_type').val('expired');
            $('.warranty-status').removeClass('label-success').addClass('label-danger').text('EXPIRED');
        }
        return proceed;
    }
    
    $('.warranty-status').removeClass('label-danger').addClass('label-success').text('AKTIF');
    return true;
}

window.saveMechanicData = function() {
    var noService = '<?php echo $no_service ?? ''; ?>';
    if (!noService) {
        alert('Nomor service tidak ditemukan!');
        return;
    }

    var data = {
        no_service:      noService,
        kepala_mekanik1: $('#cbokepala_mekanik1').val() || '',
        persen_kepala1:  $('#txtpersen_kepala1').val() || 0,
        kepala_mekanik2: $('#cbokepala_mekanik2').val() || '',
        persen_kepala2:  $('#txtpersen_kepala2').val() || 0,
        mekanik1:        $('#cbomekanik1').val() || '',
        persen_mekanik1: $('#txtpersen_mekanik1').val() || 0,
        mekanik2:        $('#cbomekanik2').val() || '',
        persen_mekanik2: $('#txtpersen_mekanik2').val() || 0,
        mekanik3:        $('#cbomekanik3').val() || '',
        persen_mekanik3: $('#txtpersen_mekanik3').val() || 0,
        mekanik4:        $('#cbomekanik4').val() || '',
        persen_mekanik4: $('#txtpersen_mekanik4').val() || 0,
        km_skr:          $('#txtkm_skr').val() || 0,
        km_berikut:      $('#txtkm_next').val() || 0
    };

    var button = $('#btnSaveMechanicData');
    button.prop('disabled', true).html('<i class="ace-icon fa fa-spinner fa-spin"></i> Menyimpan...');

    $.ajax({
        url: '_ajax/save_mechanic_data.php',
        type: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            button.prop('disabled', false).html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');
            if (response.status === 'success') {
                alert('Data mekanik dan persentase berhasil disimpan!');
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            button.prop('disabled', false).html('<i class="ace-icon fa fa-save"></i> Simpan Data Mekanik & Persentase');
            alert('Terjadi kesalahan saat menyimpan data: ' + error);
        }
    });
};
</script>