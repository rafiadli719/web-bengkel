<?php 
// Determine service type from current page or variable
$jenis_servis = '';
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($jenis_servis) && !empty($jenis_servis)) {
    // Use existing jenis_servis variable if set
} elseif (strpos($current_page, 'garansi') !== false) {
    $jenis_servis = 'garansi';
} elseif (strpos($current_page, 'jemput') !== false) {
    $jenis_servis = 'jemput'; 
} elseif (strpos($current_page, 'rst') !== false) {
    $jenis_servis = 'rst';
} else {
    $jenis_servis = 'reguler';
}
?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="footer">
                <div class="footer-inner">
                    <div class="footer-content">
                        <?php include "../lib/footer.php"; ?>
                    </div>
                </div>
            </div>

            <a href="#" id="btn-scroll-up" class="btn-scroll-up btn btn-sm btn-inverse">
                <i class="ace-icon fa fa-angle-double-up icon-only bigger-110"></i>
            </a>
        </div>

        <!-- basic scripts -->
        <!--[if !IE]> -->
        <script src="assets/js/jquery-2.1.4.min.js"></script>
        <!-- <![endif]-->

        <!--[if IE]>
        <script src="assets/js/jquery-1.11.3.min.js"></script>
        <![endif]-->

        <script type="text/javascript">
            if('ontouchstart' in document.documentElement) document.write("<script src='assets/js/jquery.mobile.custom.min.js'>"+"<"+"/script>");
        </script>
        <script src="assets/js/bootstrap.min.js"></script>

        <!-- page specific plugin scripts -->
        <script src="assets/js/jquery-ui.custom.min.js"></script>
        <script src="assets/js/jquery.ui.touch-punch.min.js"></script>
        <script src="assets/js/moment.min.js"></script>
        <script src="assets/js/fullcalendar.min.js"></script>
        <script src="assets/js/bootbox.js"></script>
        <script src="assets/js/date-time/bootstrap-datepicker.min.js"></script>
        <script src="assets/js/date-time/bootstrap-timepicker.min.js"></script>
        <script src="assets/js/date-time/moment.min.js"></script>
        <script src="assets/js/date-time/daterangepicker.min.js"></script>
        <script src="assets/js/date-time/bootstrap-datetimepicker.min.js"></script>

        <!-- ace scripts -->
        <script src="assets/js/ace-elements.min.js"></script>
        <script src="assets/js/ace.min.js"></script>

        <!-- Custom JavaScript for Service Input - Dynamic Based on Service Type -->
        <script>
            // Service type from PHP
            var jenisServis = '<?php echo $jenis_servis; ?>';
            console.log('Service Type:', jenisServis);
            
            // Get service type display text
            function getServiceTypeText(jenis) {
                switch(jenis) {
                    case 'garansi':
                        return 'Komplain Garansi';
                    case 'jemput':
                        return 'Servis Jemput';
                    case 'rst':
                        return 'Servis RST';
                    case 'reguler':
                    default:
                        return 'Servis Reguler';
                }
            }
            
            // Generate antrian number
            function generateAntrian() {
                $.ajax({
                    url: '_ajax/ajax-generate-antrian.php',
                    type: 'POST',
                    data: {
                        jenis_servis: jenisServis
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if(data.success) {
                            document.getElementById('antrianNumber').textContent = data.no_antrian;
                            
                            // Update jenis antrian text based on service type
                            var jenisText = getServiceTypeText(jenisServis);
                            
                            // Show success message with jenis
                            alert('Nomor antrian ' + jenisText + ' baru: ' + data.no_antrian);
                        } else {
                            alert('Gagal generate antrian: ' + data.message);
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat generate antrian');
                    }
                });
            }

            // Search customer
            function cariPelanggan() {
                var kodePelanggan = document.getElementById('kode_pelanggan').value;
                if(kodePelanggan.trim() === '') {
                    alert('Masukkan kode pelanggan');
                    return;
                }
                
                $.ajax({
                    url: '_ajax/ajax-cari-pelanggan.php',
                    type: 'POST',
                    data: {
                        kode_pelanggan: kodePelanggan
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if(data.success) {
                            document.getElementById('namapelanggan').value = data.nama_pelanggan;
                        } else {
                            alert('Pelanggan tidak ditemukan');
                            document.getElementById('namapelanggan').value = '';
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat mencari pelanggan');
                    }
                });
            }

            // Search vehicle
            function cariKendaraan() {
                var noPolisi = document.getElementById('no_polisi').value;
                if(noPolisi.trim() === '') {
                    alert('Masukkan nomor polisi');
                    return;
                }
                
                $.ajax({
                    url: '_ajax/ajax-cari-kendaraan.php',
                    type: 'POST',
                    data: {
                        no_polisi: noPolisi
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if(data.success) {
                            document.getElementById('pemilik').value = data.pemilik;
                            document.getElementById('jenis').value = data.jenis;
                            document.getElementById('merek').value = data.merek;
                            document.getElementById('warna').value = data.warna;
                            document.getElementById('no_rangka').value = data.no_rangka;
                            document.getElementById('no_mesin').value = data.no_mesin;
                        } else {
                            alert('Kendaraan tidak ditemukan');
                            // Clear vehicle fields
                            document.getElementById('pemilik').value = '';
                            document.getElementById('jenis').value = '';
                            document.getElementById('merek').value = '';
                            document.getElementById('warna').value = '';
                            document.getElementById('no_rangka').value = '';
                            document.getElementById('no_mesin').value = '';
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat mencari kendaraan');
                    }
                });
            }

<?php if ($jenis_servis === 'garansi'): ?>
            // GARANSI-SPECIFIC FUNCTIONS
            
            // Validate garansi warranty
            function validateGaransi() {
                var noServiceAsal = document.getElementById('no_service_asal').value;
                if(noServiceAsal.trim() === '') {
                    alert('Masukkan nomor service asal terlebih dahulu');
                    return;
                }
                
                // AJAX call to validate warranty
                $.ajax({
                    url: '_ajax/ajax-validasi-garansi.php',
                    type: 'POST',
                    data: {
                        no_service_asal: noServiceAsal
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        if(data.success) {
                            document.getElementById('tanggal_service_asal').value = data.tanggal_service;
                            document.getElementById('jenis_garansi').value = data.jenis_garansi;
                            document.getElementById('masa_garansi').value = data.masa_garansi;
                            document.getElementById('status_garansi').value = data.status_garansi;
                            alert('Garansi valid: ' + data.message);
                        } else {
                            alert('Garansi tidak valid: ' + data.message);
                            document.getElementById('status_garansi').value = 'void';
                        }
                    },
                    error: function() {
                        alert('Terjadi kesalahan saat memvalidasi garansi');
                    }
                });
            }

            // Garansi form validation
            function validateGaransiForm() {
                var noServiceAsal = document.getElementById('no_service_asal') ? document.getElementById('no_service_asal').value : '';
                var jenisGaransi = document.getElementById('jenis_garansi') ? document.getElementById('jenis_garansi').value : '';
                
                if (!noServiceAsal.trim()) {
                    alert('No. Service Asal harus diisi untuk service garansi!');
                    return false;
                }
                
                if (!jenisGaransi) {
                    alert('Jenis Garansi harus dipilih!');
                    return false;
                }
                
                return true;
            }

            // Print garansi report
            function printGaransiReport() {
                var noService = '<?php echo $no_service ?? ""; ?>';
                if (!noService) {
                    alert('Service belum disimpan');
                    return;
                }
                
                window.open('cetak-garansi.php?no_service=' + noService, '_blank');
            }

<?php elseif ($jenis_servis === 'jemput'): ?>
            // JEMPUT-SPECIFIC FUNCTIONS
            
            // Update service type display
            function updateJenisService() {
                var status = document.getElementById('status_jemput').value;
                var label = '';
                switch(status) {
                    case '0': label = 'SERVIS DITINGGAL'; break;
                    case '1': label = 'SERVIS DIJEMPUT'; break;
                    case '2': label = 'SERVIS DITUNGGU'; break;
                    default: label = 'SERVIS JEMPUT';
                }
                console.log('Status jemput changed to:', label);
            }

            // Validate jemput form
            function validateJemputForm() {
                var statusJemput = document.getElementById('status_jemput') ? document.getElementById('status_jemput').value : '';
                var alamatJemput = document.getElementById('alamat_jemput') ? document.getElementById('alamat_jemput').value : '';
                
                if (statusJemput === '1' && !alamatJemput.trim()) {
                    alert('Alamat jemput harus diisi untuk service jemput!');
                    return false;
                }
                
                return validateRegulerForm(); // Also check regular validations
            }

            // Schedule pickup
            function schedulePickup() {
                var noService = '<?php echo $no_service ?? ""; ?>';
                var alamatJemput = document.getElementById('alamat_jemput') ? document.getElementById('alamat_jemput').value : '';
                
                if (!alamatJemput.trim()) {
                    alert('Alamat jemput harus diisi!');
                    return;
                }
                
                // Schedule pickup logic here
                alert('Penjemputan untuk service ' + noService + ' berhasil dijadwalkan');
            }

            // Confirm pickup
            function confirmPickup() {
                var noService = '<?php echo $no_service ?? ""; ?>';
                if (confirm('Konfirmasi bahwa kendaraan telah dijemput?')) {
                    // Update status to picked up
                    alert('Status service ' + noService + ' diubah menjadi "Sudah Dijemput"');
                }
            }

<?php elseif ($jenis_servis === 'rst'): ?>
            // RST-SPECIFIC FUNCTIONS
            
            // Prioritize RST service
            function prioritizeRST() {
                var prioritas = document.getElementById('prioritas_wo');
                if (prioritas) {
                    prioritas.value = 'urgent';
                    alert('Service RST telah diprioritaskan sebagai URGENT');
                }
            }

            // Escalate RST service
            function escalateRST() {
                var noService = '<?php echo $no_service ?? ""; ?>';
                if (confirm('Eskalasi service RST ' + noService + ' ke supervisor?')) {
                    // Escalation logic here
                    alert('Service RST telah dieskalasi');
                }
            }

            // RST form validation
            function validateRSTForm() {
                var result = validateRegulerForm();
                
                // Additional RST-specific validation
                var priority = document.getElementById('prioritas_wo') ? document.getElementById('prioritas_wo').value : '';
                if (priority !== 'urgent') {
                    alert('Service RST harus memiliki prioritas Urgent!');
                    return false;
                }
                
                return result;
            }

<?php else: ?>
            // REGULER-SPECIFIC FUNCTIONS (Default)
            
            // Regular form validation
            function validateRegulerForm() {
                var kepalaMemkanik = document.getElementById('cbokepala1') ? document.getElementById('cbokepala1').value : '';
                var mekanik1 = document.getElementById('cbomekanik1') ? document.getElementById('cbomekanik1').value : '';
                
                if (!kepalaMemkanik) {
                    alert('Kepala Mekanik harus dipilih untuk service reguler!');
                    return false;
                }
                
                if (!mekanik1) {
                    alert('Minimal 1 mekanik harus dipilih!');
                    return false;
                }
                
                return true;
            }

<?php endif; ?>

            // Common service-specific validation function
            function validateServiceForm() {
                switch(jenisServis) {
                    case 'garansi':
                        return validateGaransiForm();
                    case 'jemput':
                        return validateJemputForm();
                    case 'rst':
                        return validateRSTForm();
                    case 'reguler':
                    default:
                        return validateRegulerForm();
                }
            }

            // Service-specific form submission
            function submitServiceForm(action) {
                if (!validateServiceForm()) {
                    return false;
                }
                
                // Add service type to form data
                var form = document.getElementById('formService');
                if (form) {
                    var hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'jenis_servis';
                    hiddenInput.value = jenisServis;
                    form.appendChild(hiddenInput);
                }
                
                return true;
            }

            // Initialize date pickers
            $(document).ready(function() {
                $('.date-picker').datepicker({
                    autoclose: true,
                    todayHighlight: true,
                    format: 'dd/mm/yyyy'
                });

                // Initialize time picker
                $('#jam_service').timepicker({
                    minuteStep: 1,
                    showSeconds: false,
                    showMeridian: false
                });

                // Service-specific initialization
                switch(jenisServis) {
                    case 'garansi':
                        initGaransiFeatures();
                        break;
                    case 'jemput':
                        initJemputFeatures();
                        break;
                    case 'rst':
                        initRSTFeatures();
                        break;
                    case 'reguler':
                    default:
                        initRegulerFeatures();
                        break;
                }

                // Tab functionality initialization
                initTabFunctionality();

                // Auto-save form data periodically
                setInterval(function() {
                    console.log('Auto-save check for ' + jenisServis + ' service...');
                }, 30000);

                // Initialize tooltips
                $('[data-toggle="tooltip"]').tooltip();

                // Initialize popovers
                $('[data-toggle="popover"]').popover();

                // Form change detection
                var formChanged = false;
                $('#formService').on('change', 'input, select, textarea', function() {
                    formChanged = true;
                });

                // Warn user before leaving if form is changed
                window.onbeforeunload = function() {
                    if(formChanged) {
                        return "Form telah diubah. Apakah Anda yakin ingin meninggalkan halaman ini?";
                    }
                };

                // Reset form changed flag when form is submitted
                $('#formService').on('submit', function() {
                    formChanged = false;
                });
            });

<?php if ($jenis_servis === 'garansi'): ?>
            // Initialize garansi-specific features
            function initGaransiFeatures() {
                console.log('Initializing garansi features...');
                
                // Auto-validate garansi when service asal is entered
                $('#no_service_asal').on('blur', function() {
                    if ($(this).val().trim()) {
                        validateGaransi();
                    }
                });

                // Hide work order tab for garansi
                $('a[href="#work-order"]').parent().hide();
                
                // Add garansi-specific styling
                $('a[href="#garansi-detail"]').addClass('garansi-tab');
            }

<?php elseif ($jenis_servis === 'jemput'): ?>
            // Initialize jemput-specific features
            function initJemputFeatures() {
                console.log('Initializing jemput features...');
                
                // Auto-update form based on jemput status
                $('#status_jemput').on('change', function() {
                    var status = $(this).val();
                    if (status === '1') { // Dijemput
                        $('#alamat_jemput').prop('required', true);
                        $('#tanggal_jemput').prop('required', true);
                        $('#jam_jemput').prop('required', true);
                    } else {
                        $('#alamat_jemput').prop('required', false);
                        $('#tanggal_jemput').prop('required', false);
                        $('#jam_jemput').prop('required', false);
                    }
                    updateJenisService();
                });

                // Show jemput-specific tabs
                $('a[href="#jemput-detail"]').parent().show();
                
                // Add jemput-specific styling
                $('a[href="#jemput-detail"]').addClass('jemput-tab');
            }

<?php elseif ($jenis_servis === 'rst'): ?>
            // Initialize RST-specific features
            function initRSTFeatures() {
                console.log('Initializing RST features...');
                
                // Auto-set priority to urgent for RST
                var priority = document.getElementById('prioritas_wo');
                if (priority && !priority.value) {
                    priority.value = 'urgent';
                }

                // Add RST badge to all tabs
                $('.nav-tabs a').append(' <span class="label label-danger rst-badge">RST</span>');
                
                // Add RST-specific styling
                $('.nav-tabs').addClass('rst-tabs');
            }

<?php else: ?>
            // Initialize reguler-specific features
            function initRegulerFeatures() {
                console.log('Initializing reguler features...');
                
                // Standard reguler service features
                // Add any reguler-specific initialization here
            }

<?php endif; ?>

            // Initialize tab functionality
            function initTabFunctionality() {
                // Tab switching function
                window.switchTab = function($container, target) {
                    try {
                        console.log('Switching to tab:', target);
                        
                        // Validate target exists
                        if (!$container.find(target).length) {
                            console.warn('Target tab pane not found:', target);
                            return false;
                        }
                        
                        // Remove active from all tabs
                        $container.find('ul.nav-tabs > li').removeClass('active');
                        $container.find('.tab-content > .tab-pane').removeClass('active').hide();
                        
                        // Add active to target tab and pane
                        $container.find('a[href="'+target+'"]').parent('li').addClass('active');
                        var $targetPane = $container.find(target);
                        $targetPane.addClass('active').show();
                        
                        // Trigger custom event for tab change
                        $targetPane.trigger('tab:shown', [target]);
                        
                        console.log('Tab switched successfully to:', target);
                        return true;
                    } catch(err) {
                        console.error('Tab switch error:', err);
                        return false;
                    }
                };
                
                // Ensure first tab is active on page load
                var $tabbable = $('.tabbable').first();

                // If URL has hash, open that tab
                var hash = window.location.hash;
                if (hash && $tabbable.find(hash).length) {
                    switchTab($tabbable, hash);
                } else {
                    switchTab($tabbable, '#service-details');
                }

                // React to back/forward navigation on hash changes
                $(window).on('hashchange', function() {
                    var h = window.location.hash;
                    if (h && $tabbable.find(h).length) {
                        switchTab($tabbable, h);
                    }
                });
                
                // Tab click handlers
                $('#myTab a[data-toggle="tab"]').on('click', function (e) {
                    e.preventDefault();
                    var $this = $(this);
                    var target = $this.attr('href');
                    
                    console.log('Tab clicked:', target);
                    
                    if (!target || target === '#' || $(target).length === 0) {
                        console.warn('Invalid target tab:', target);
                        return;
                    }
                    
                    var $container = $this.closest('.tabbable');
                    switchTab($container, target);
                    
                    // Update URL hash without causing page jump
                    if (history.pushState) {
                        history.pushState(null, null, target);
                    } else {
                        window.location.hash = target;
                    }
                });
            }

            // Keyboard shortcuts
            $(document).keydown(function(e) {
                // Ctrl+S to save
                if(e.ctrlKey && e.keyCode == 83) {
                    e.preventDefault();
                    if(typeof saveService === 'function') {
                        saveService();
                    }
                }
                
                // Ctrl+P to print
                if(e.ctrlKey && e.keyCode == 80) {
                    e.preventDefault();
                    if(typeof printService === 'function') {
                        printService();
                    }
                }
                
                // Ctrl+N to generate new antrian
                if(e.ctrlKey && e.keyCode == 78) {
                    e.preventDefault();
                    generateAntrian();
                }

<?php if ($jenis_servis === 'garansi'): ?>
                // Ctrl+G for garansi validation
                if(e.ctrlKey && e.keyCode == 71) {
                    e.preventDefault();
                    validateGaransi();
                }
<?php elseif ($jenis_servis === 'jemput'): ?>
                // Ctrl+J for jemput scheduling
                if(e.ctrlKey && e.keyCode == 74) {
                    e.preventDefault();
                    schedulePickup();
                }
<?php elseif ($jenis_servis === 'rst'): ?>
                // Ctrl+R for RST prioritization
                if(e.ctrlKey && e.keyCode == 82) {
                    e.preventDefault();
                    prioritizeRST();
                }
<?php endif; ?>
            });

            // Console logging for debugging
            console.log('=== SERVICE FOOTER INITIALIZED ===');
            console.log('Service Type: ' + jenisServis);
            console.log('Page: <?php echo $current_page; ?>');
            console.log('=====================================');

        </script>
    </body>
</html>