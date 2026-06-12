<script type="text/javascript">
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        window.jQuery(initRegulerFooter);
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initRegulerFooter($) {
    
    // Initialize bootstrap tabs
    $('.nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
        try { console.log('[Debug Footer] Tab switched to:', $(this).attr('href')); } catch(err) {}
        
        // Auto-focus first input in active tab
        var target = $(this).attr('href');
        setTimeout(function() {
            $(target + ' input:visible:first').focus();
        }, 100);
    });
    
    // REGULER SERVICE SPECIFIC FUNCTIONS
    
    // REGULER SERVICE SPECIFIC FUNCTIONS
    
    // Mechanic validation for regular service
    function validateMekanikReguler() {
        var mekanik1 = $('#cbomekanik1').val();
        
        // Use the correct ID from _servis_actions_tab.php
        var kepala1 = $('#cbokepala_mekanik1').val(); 
        
        // Fallback or check hidden field if main one is missing (backwards compatibility)
        if (!kepala1 && $('#cbokepala1').length) {
             kepala1 = $('#cbokepala1').val();
        }
        
        if (!kepala1) {
            alert('Kepala Mekanik wajib diisi untuk service reguler!');
            // Try to focus the correct element
            if ($('#cbokepala_mekanik1').is(':visible')) {
                $('#cbokepala_mekanik1').focus();
            } else if ($('#cbokepala1').is(':visible')) {
                $('#cbokepala1').focus();
            } else {
                 // Switch to actions tab if needed
                 $('a[href="#service-actions"]').tab('show');
                 setTimeout(function(){ $('#cbokepala_mekanik1').focus(); }, 100);
            }
            return false;
        }
        
        if (!mekanik1) {
            alert('Minimal 1 mekanik harus diisi untuk service reguler!');
            $('#cbomekanik1').focus();
            return false;
        }
        
        var totalPersen = parseInt($('#totalPersenReguler').text()) || 0;
        if (totalPersen != 100) {
            alert('Total persentase mekanik harus tepat 100%!\nSaat ini: ' + totalPersen + '%');
            return false;
        }
        
        return true;
    }
    
    // Auto-set percentage callbacks removed here because they are handled in _servis_actions_tab.php
    // Attempting to calculate here with wrong IDs causes "0%" overwrite issues.
    
    /* 
       NOTE: Logic calculation moved to _servis_actions_tab.php (window.updateTotalPercentage)
       Legacy listeners removed to prevent conflict.
    */
    
    // KELUHAN MANAGEMENT - REGULER SPECIFIC
    // Penting: jangan pakai submit() programatik agar 'submitter' tetap terdeteksi benar
    $('#btnaddkeluhan').on('click', function(e) {
        var keluhan = $('#txtkeluhan').val().trim();
        var noService = '<?php echo $no_service ?? ""; ?>';
        
        if (!noService) {
            e.preventDefault();
            alert('Simpan service reguler terlebih dahulu!');
            return false;
        }
        
        if (!keluhan) {
            e.preventDefault();
            alert('Masukkan keluhan terlebih dahulu!');
            $('#txtkeluhan').focus();
            return false;
        }
        // Biarkan default submit berjalan supaya e.originalEvent.submitter === #btnaddkeluhan
    });
    
    // FORM SUBMISSION VALIDATION
    $('form').on('submit', function(e) {
        var submitBtn = null;
        if (e.originalEvent && e.originalEvent.submitter) {
            submitBtn = $(e.originalEvent.submitter);
        } else {
            var ae = document.activeElement;
            if (ae && (ae.type === 'submit' || ae.tagName === 'BUTTON' || $(ae).is('input[type="submit"]'))) {
                submitBtn = $(ae);
            }
        }

        // Hanya validasi ketika benar-benar tombol Simpan yang dipakai
        if (submitBtn && (submitBtn.attr('name') === 'btnsimpan' || submitBtn.attr('id') === 'btnsimpan')) {
            if (!validateMekanikReguler()) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // KEYBOARD SHORTCUTS - REGULER SERVICE
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
            }
        }
        
        // F2 for keluhan search
        if (e.keyCode === 113) { // F2
            e.preventDefault();
            showModalSearchKeluhan();
        }
    });
    
    // AUTO-CALCULATION ON PAGE LOAD
    if (typeof window.calculateMekanikPersentaseReguler === 'function') {
        window.calculateMekanikPersentaseReguler();
    }
    
    // Additional reguler service functions
    function validateRegularServiceData() {
        var errors = [];
        
        // Check required fields for regular service
        if (!$('#cbokepala1').val()) {
            errors.push('Kepala Mekanik wajib diisi');
        }
        
        if (!$('#cbomekanik1').val()) {
            errors.push('Minimal 1 mekanik harus diisi');
        }
        
        var kmSekarang = parseInt($('#txtkm_skr').val()) || 0;
        var kmBerikut = parseInt($('#txtkm_next').val()) || 0;
        
        if (kmBerikut > 0 && kmBerikut <= kmSekarang) {
            errors.push('Km berikut harus lebih besar dari Km sekarang');
        }
        
        if (errors.length > 0) {
            alert('Validasi gagal:\n' + errors.join('\n'));
            return false;
        }
        
        return true;
    }
    
    // Enhanced form validation
    window.validateBeforeSubmitReguler = function() {
        return validateRegularServiceData() && validateMekanikReguler();
    };
    
    // Initialize tooltips for regular service
    $('[data-toggle="tooltip"]').tooltip();
    
    // Force first tab to be active (only if no tab/hash requested)
    setTimeout(function() {
        var hash = window.location.hash;
        var tabParam = null;
        try {
            var sp = new URLSearchParams(window.location.search);
            tabParam = sp.get('tab');
        } catch(err) {}
        var shouldForce = !(hash && hash.length) && !(tabParam && tabParam.length);
        try { console.log('[Debug Footer] Force first tab check | hash:', hash, '| tabParam:', tabParam, '| shouldForce:', shouldForce); } catch(err) {}
        if (shouldForce && $('.nav-tabs li:first a').length) {
            try { console.log('[Debug Footer] Forcing first tab to show'); } catch(err) {}
            $('.nav-tabs li:first a').tab('show');
        } else {
            try { console.log('[Debug Footer] Skip forcing first tab'); } catch(err) {}
        }
    }, 500);
    
    try { console.log('[Debug Footer] REGULER Service footer initialized successfully'); } catch(err) {}
}

// Global functions for regular service
function showModalSearchKeluhan() {
    if (typeof window.jQuery === 'function') {
        window.jQuery('#modal-search-keluhan').modal('show');
    }
}

function selectKeluhan(keluhan) {
    if (typeof window.jQuery === 'function') {
        window.jQuery('#txtkeluhan').val(keluhan);
        window.jQuery('#modal-search-keluhan').modal('hide');
    }
}

function hapusKeluhan(id) {
    if (!confirm('Yakin ingin menghapus keluhan ini dari service reguler?')) {
        return;
    }
    
    if (typeof window.jQuery === 'function') {
        window.jQuery.post('keluhan-hapus.php', {id: id}, function(response) {
            var result = JSON.parse(response);
            if (result.success) {
                alert('Keluhan berhasil dihapus!');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        });
    }
}

function refreshTabelKeluhan() {
    location.reload();
}
</script>