<script type="text/javascript">
jQuery(function($) {
    
    // Initialize bootstrap tabs
    $('.nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
        
        console.log('Tab RST switched to:', $(this).attr('href'));
        
        // Auto-focus first input in active tab
        var target = $(this).attr('href');
        setTimeout(function() {
            $(target + ' input:visible:first').focus();
        }, 100);
    });
    
    // RST SERVICE SPECIFIC FUNCTIONS
    
    // Auto-calculate percentage for mechanics - RST STYLE
    function calculateMekanikPersentaseRST() {
        var total = 0;
        for(var i = 1; i <= 4; i++) {
            var val = parseInt($('#txtpersen' + i).val()) || 0;
            total += val;
        }
        
        $('#totalPersenRST').text(total);
        
        var statusDiv = $('#persentaseStatusRST');
        var messageSpan = $('#persenMessageRST');
        
        if (total == 100) {
            statusDiv.removeClass().addClass('alert alert-success');
            messageSpan.text(' - RST Coverage 100% (OK)').css('color', 'green');
        } else if (total > 100) {
            statusDiv.removeClass().addClass('alert alert-danger');
            messageSpan.text(' - Melebihi 100%! Check RST coverage').css('color', 'red');
        } else if (total > 0) {
            statusDiv.removeClass().addClass('alert alert-warning');
            messageSpan.text(' - Kurang dari 100%! Partial RST coverage').css('color', 'orange');
        } else {
            statusDiv.removeClass().addClass('alert alert-info');
            messageSpan.text(' - Belum ada coverage RST').css('color', 'blue');
        }
    }
    
    // Validation specific for RST (rework) service
    function validateRSTService() {
        var kepala1 = $('#cbokepala1').val();
        var mekanik1 = $('#cbomekanik1').val();
        
        if (!kepala1) {
            alert('Kepala Mekanik wajib diisi untuk service RST!\nRST memerlukan supervisi ketat.');
            $('#cbokepala1').focus();
            return false;
        }
        
        if (!mekanik1) {
            alert('Minimal 1 mekanik harus diisi untuk service RST!\nRST adalah rework yang harus ditangani dengan serius.');
            $('#cbomekanik1').focus();
            return false;
        }
        
        var totalPersen = parseInt($('#totalPersenRST').text()) || 0;
        if (totalPersen != 100) {
            alert('Total persentase mekanik harus tepat 100% untuk RST!\nSaat ini: ' + totalPersen + '%\n\nRST memerlukan responsibility yang jelas.');
            return false;
        }
        
        // Check if this is really an RST case
        var keluhan = $('#txtkeluhan').val();
        if (keluhan && keluhan.trim() === '') {
            var confirmRST = confirm('Belum ada keluhan yang tercatat.\nApakah ini benar-benar kasus RST (Rework)?');
            if (!confirmRST) {
                return false;
            }
        }
        
        return true;
    }
    
    // Auto-set percentage when mechanic selected for RST
    $('select[name^="cbomekanik"]').on('change', function() {
        var number = $(this).attr('name').replace('cbomekanik', '');
        var selectedValue = $(this).val();
        var persenInput = $('#txtpersen' + number);
        
        if (selectedValue === '') {
            persenInput.val('0');
        } else if (persenInput.val() == '0' || persenInput.val() == '') {
            // For RST, usually assign to primary mechanic for responsibility
            if (number == 1) {
                var total_existing = 0;
                for(var i = 2; i <= 4; i++) {
                    total_existing += parseInt($('#txtpersen' + i).val()) || 0;
                }
                if (total_existing == 0) {
                    persenInput.val('100');
                }
            }
        }
        
        calculateMekanikPersentaseRST();
    });
    
    // Update percentage calculation on input change
    $('input[name^="txtpersen"]').on('input keyup change', function() {
        calculateMekanikPersentaseRST();
    });
    
    // RST SERVICE STATUS MANAGEMENT
    function updateStatusRST(status) {
        var validStatuses = ['baru', 'proses', 'selesai', 'diambil'];
        if (validStatuses.includes(status)) {
            $('#cbostatus').val(status).trigger('change');
            
            // Add RST specific status handling
            if (status === 'selesai') {
                var confirmRST = confirm('Apakah RST sudah benar-benar selesai dan teruji?\n\nPastikan masalah yang sama tidak akan terjadi lagi.');
            }
        }
    }
    
    // KELUHAN MANAGEMENT - RST SPECIFIC
    $('#btnaddkeluhan').on('click', function(e) {
        e.preventDefault();
        
        var keluhan = $('#txtkeluhan').val().trim();
        var noService = '<?php echo $no_service ?? ""; ?>';
        
        if (!noService) {
            alert('Simpan service RST terlebih dahulu!');
            return false;
        }
        
        if (!keluhan) {
            alert('Keluhan WAJIB diisi untuk service RST!\nTuliskan keluhan yang kembali dan analisis penyebabnya.');
            $('#txtkeluhan').focus();
            return false;
        }
        
        // RST specific validation - keluhan harus detail
        if (keluhan.length < 10) {
            alert('Keluhan RST harus lebih detail!\nMinimal 10 karakter untuk menjelaskan masalah yang kembali.');
            $('#txtkeluhan').focus();
            return false;
        }
        
        // Submit form with keluhan
        $(this).closest('form').submit();
    });
    
    // FORM SUBMISSION VALIDATION
    $('form').on('submit', function(e) {
        var submitBtn;
        if (e.originalEvent && e.originalEvent.submitter) {
            submitBtn = $(e.originalEvent.submitter);
        } else {
            var ae = document.activeElement;
            if (ae && (ae.type === 'submit' || ae.tagName === 'BUTTON' || $(ae).is('input[type="submit"]'))) {
                submitBtn = $(ae);
            } else {
                submitBtn = $(this).find('button[name="btnsimpan"], #btnsimpan, input[type="submit"][name="btnsimpan"]').first();
            }
        }

        // Only validate for main save buttons
        if (submitBtn && (submitBtn.attr('name') === 'btnsimpan' || submitBtn.attr('id') === 'btnsimpan')) {
            if (!validateRSTService()) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // KEYBOARD SHORTCUTS - RST SERVICE
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
                case 82: // Ctrl + R
                    e.preventDefault();
                    alert('RST Service Reminder:\n- Pastikan keluhan sudah detail\n- Cek riwayat service sebelumnya\n- Analisis root cause\n- Assign mekanik yang kompeten');
                    break;
            }
        }
        
        // F5 for RST guidelines
        if (e.keyCode === 116) { // F5
            e.preventDefault();
            showRSTGuidelines();
        }
        
        // F2 for keluhan search
        if (e.keyCode === 113) { // F2
            e.preventDefault();
            showModalSearchKeluhan();
        }
    });
    
    // AUTO-CALCULATION ON PAGE LOAD
    calculateMekanikPersentaseRST();
    
    // Force first tab to be active
    setTimeout(function() {
        if ($('.nav-tabs li:first a').length) {
            $('.nav-tabs li:first a').tab('show');
        }
    }, 500);
    
    // RST specific validations and reminders
    function validateRSTServiceData() {
        var errors = [];
        
        // Check required fields for RST service
        if (!$('#cbokepala1').val()) {
            errors.push('Kepala Mekanik wajib diisi (RST memerlukan supervisi)');
        }
        
        if (!$('#cbomekanik1').val()) {
            errors.push('Minimal 1 mekanik harus diisi (RST adalah rework)');
        }
        
        // RST specific checks
        var keluhan = $('#txtkeluhan').val().trim();
        if (!keluhan || keluhan.length < 10) {
            errors.push('Keluhan RST harus detail (min 10 karakter)');
        }
        
        var kmSekarang = parseInt($('#txtkm_skr').val()) || 0;
        var kmBerikut = parseInt($('#txtkm_next').val()) || 0;
        
        if (kmBerikut > 0 && kmBerikut <= kmSekarang) {
            errors.push('Km berikut harus lebih besar dari Km sekarang');
        }
        
        if (errors.length > 0) {
            alert('Validasi RST gagal:\n' + errors.join('\n'));
            return false;
        }
        
        return true;
    }
    
    // Show RST guidelines
    function showRSTGuidelines() {
        var guidelines = 'PANDUAN SERVICE RST (REWORK):\n\n';
        guidelines += '1. RST adalah service ulang karena keluhan kembali\n';
        guidelines += '2. WAJIB mencatat keluhan dengan detail\n';
        guidelines += '3. Analisis root cause mengapa terjadi keluhan kembali\n';
        guidelines += '4. Assign mekanik yang kompeten\n';
        guidelines += '5. Cek riwayat service sebelumnya\n';
        guidelines += '6. Pastikan quality control lebih ketat\n';
        guidelines += '7. Dokumentasikan tindakan pencegahan\n\n';
        guidelines += 'Tekan F2 untuk search keluhan sebelumnya';
        
        alert(guidelines);
    }
    
    // Enhanced form validation
    window.validateBeforeSubmitRST = function() {
        return validateRSTServiceData() && validateRSTService();
    };
    
    // Initialize tooltips for RST service
    $('[data-toggle="tooltip"]').tooltip();
    
    console.log('RST Service footer initialized successfully');
});

// Global functions for RST service
function showModalSearchKeluhan() {
    $('#modal-search-keluhan').modal('show');
}

function selectKeluhan(keluhan) {
    $('#txtkeluhan').val(keluhan);
    $('#modal-search-keluhan').modal('hide');
}

function hapusKeluhan(id) {
    if (!confirm('Yakin ingin menghapus keluhan ini dari service RST?\n\nPerhatian: RST memerlukan dokumentasi keluhan yang lengkap.')) {
        return;
    }
    
    $.post('keluhan-hapus.php', {id: id}, function(response) {
        var result = JSON.parse(response);
        if (result.success) {
            alert('Keluhan RST berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

function refreshTabelKeluhan() {
    location.reload();
}

// RST specific utility functions
function analyzeRSTCause() {
    var previousService = $('#previous_service_history').val();
    if (!previousService) {
        alert('Data service sebelumnya tidak ditemukan!\nSilakan cari riwayat service untuk analisis RST.');
        return;
    }
    
    // Show RST analysis dialog
    var analysis = 'ANALISIS RST:\n\n';
    analysis += 'Service Sebelumnya: ' + previousService + '\n';
    analysis += 'Kemungkinan Penyebab:\n';
    analysis += '- Part tidak original/berkualitas rendah\n';
    analysis += '- Pemasangan tidak sesuai prosedur\n';
    analysis += '- Diagnosa awal kurang tepat\n';
    analysis += '- Maintenance tidak dilakukan customer\n';
    analysis += '- Kondisi penggunaan ekstrim\n\n';
    analysis += 'Tindakan Pencegahan:\n';
    analysis += '- Gunakan part original\n';
    analysis += '- Double check prosedur\n';
    analysis += '- Test drive lebih teliti\n';
    analysis += '- Edukasi customer maintenance\n';
    
    alert(analysis);
}

function generateRSTReport() {
    var noService = $('input[name="txtnosrv"]').val();
    
    if (!noService) {
        alert('No service tidak ditemukan untuk generate laporan RST!');
        return;
    }
    
    // Open RST report in new window
    window.open('reports/rst_report.php?no_service=' + encodeURIComponent(noService), '_blank');
}
</script>