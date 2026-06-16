<script type="text/javascript">
jQuery(function($) {
    
    // Initialize bootstrap tabs
    $('.nav-tabs a').on('click', function (e) {
        e.preventDefault();
        $(this).tab('show');
        
        console.log('Tab JEMPUT switched to:', $(this).attr('href'));
        
        // Auto-focus first input in active tab
        var target = $(this).attr('href');
        setTimeout(function() {
            $(target + ' input:visible:first').focus();
        }, 100);
    });
    
    // JEMPUT SERVICE SPECIFIC FUNCTIONS
    
    // Auto-calculate percentage for mechanics - JEMPUT STYLE
    function calculateMekanikPersentaseJemput() {
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
            messageSpan.text(' - Sempurna!').css('color', 'green');
        } else if (total > 100) {
            statusDiv.removeClass().addClass('alert alert-danger');
            messageSpan.text(' - Melebihi 100%!').css('color', 'red');
        } else if (total > 0) {
            statusDiv.removeClass().addClass('alert alert-warning');
            messageSpan.text(' - Kurang dari 100%!').css('color', 'orange');
        } else {
            statusDiv.removeClass().addClass('alert alert-info');
            messageSpan.text(' - Belum ada persentase yang diisi').css('color', 'blue');
        }
    }
    
    // Validation specific for pickup service
    function validateJemputService() {
        var kepala1 = $('#cbokepala1').val();
        var admin1 = $('#cboadmin1').val();
        var mekanik1 = $('#cbomekanik1').val();
        
        if (!kepala1) {
            alert('Kepala Mekanik wajib diisi untuk service jemput!');
            $('#cbokepala1').focus();
            return false;
        }
        
        if (!admin1) {
            alert('Admin/Kasir minimal 1 harus diisi untuk service jemput!');
            $('#cboadmin1').focus();
            return false;
        }
        
        if (!mekanik1) {
            alert('Minimal 1 mekanik harus diisi untuk service jemput!');
            $('#cbomekanik1').focus();
            return false;
        }
        
        return true;
    }
    
    // Auto-set percentage when mechanic selected for JEMPUT
    $('select[name^="cbomekanik"]').on('change', function() {
        var selectedMechanics = 0;
        $('select[name^="cbomekanik"]').each(function() {
            if ($(this).val() !== '') selectedMechanics++;
        });
        
        // Auto-distribute percentage
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
        
        calculateMekanikPersentaseJemput();
    });
    
    // Update percentage calculation on input change
    $('input[name^="txtpersen_kerja"], input[name^="txtpersen_admin"]').on('input keyup change', function() {
        calculateMekanikPersentaseJemput();
    });
    
    // PICKUP SERVICE STATUS MANAGEMENT
    function updateStatusJemput(status) {
        var validStatuses = ['jemput', 'proses', 'selesai', 'kirim'];
        if (validStatuses.includes(status)) {
            $('#status_jemput').val(status).trigger('change');
        }
    }
    
    // KELUHAN MANAGEMENT - JEMPUT SPECIFIC
    $('#btnaddkeluhan').on('click', function(e) {
        e.preventDefault();
        
        var keluhan = $('#txtkeluhan').val().trim();
        var noService = '<?php echo $no_service ?? ""; ?>';
        
        if (!noService) {
            alert('Simpan service jemput terlebih dahulu!');
            return false;
        }
        
        if (!keluhan) {
            alert('Masukkan keluhan terlebih dahulu!');
            $('#txtkeluhan').focus();
            return false;
        }
        
        // Submit form with keluhan
        $(this).closest('form').submit();
    });
    
    // FORM SUBMISSION VALIDATION
    $('form').on('submit', function(e) {
        var submitBtn = $(e.originalEvent.submitter);
        
        // Only validate for main save buttons
        if (submitBtn.attr('name') === 'btnsimpan' || submitBtn.attr('id') === 'btnsimpan') {
            if (!validateJemputService()) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // KEYBOARD SHORTCUTS - JEMPUT SERVICE
    $(document).on('keydown', function(e) {
        // Ctrl + 1-6 for tab navigation (jemput has detail jemput tab)
        if (e.ctrlKey) {
            switch(e.keyCode) {
                case 49: // Ctrl + 1
                    e.preventDefault();
                    $('a[href="#service-details"]').click();
                    break;
                case 50: // Ctrl + 2
                    e.preventDefault();
                    $('a[href="#detail-jemput"]').click();
                    break;
                case 51: // Ctrl + 3
                    e.preventDefault();
                    $('a[href="#workorder-details"]').click();
                    break;
                case 52: // Ctrl + 4
                    e.preventDefault();
                    $('a[href="#service-items"]').click();
                    break;
                case 53: // Ctrl + 5
                    e.preventDefault();
                    $('a[href="#service-jasa"]').click();
                    break;
                case 54: // Ctrl + 6
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
        
        // F3 for pickup scheduling
        if (e.keyCode === 114) { // F3
            e.preventDefault();
            if ($('#detail-jemput').length) {
                $('a[href="#detail-jemput"]').click();
                setTimeout(function() {
                    $('#alamat_jemput').focus();
                }, 200);
            }
        }
        
        // F2 for keluhan search
        if (e.keyCode === 113) { // F2
            e.preventDefault();
            showModalSearchKeluhan();
        }
    });
    
    // AUTO-CALCULATION ON PAGE LOAD
    calculateMekanikPersentaseJemput();

    console.log('JEMPUT Service footer initialized successfully');
});

// Global functions for pickup service
function showModalSearchKeluhan() {
    $('#modal-search-keluhan').modal('show');
}

function selectKeluhan(keluhan) {
    $('#txtkeluhan').val(keluhan);
    $('#modal-search-keluhan').modal('hide');
}

function hapusKeluhan(id) {
    if (!confirm('Yakin ingin menghapus keluhan ini dari service jemput?')) {
        return;
    }
    
    $.post('_ajax/hapus_spk_keluhan.php', {id: id}, function(response) {
        var result = JSON.parse(response);
        if (result.success) {
            alert('Keluhan jemput berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

function hapusWorkOrder(id) {
    if (!confirm('Yakin ingin menghapus work order ini dari service jemput?')) {
        return;
    }
    
    $.post('_ajax/hapus_spk_workorder.php', {id: id}, function(response) {
        var result = JSON.parse(response);
        if (result.success) {
            alert('Work order jemput berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    });
}

function refreshTabelKeluhan() {
    location.reload();
}
</script>