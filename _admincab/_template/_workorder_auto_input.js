/**
 * Work Order Auto Input Functions
 * Fungsi untuk auto-input paket barang dan jasa berdasarkan work order yang dipilih
 */

// Function to auto-load work order package
function autoLoadWorkOrderPackage(kodeWO, noService) {
    if (!kodeWO || kodeWO.trim() === '') {
        return;
    }

    // Show loading indicator
    showLoadingWorkOrder();

    // AJAX call to get work order details
    $.ajax({
        url: '_ajax/ajax-get-workorder-details.php',
        type: 'POST',
        data: {
            kode_wo: kodeWO,
            no_service: noService
        },
        dataType: 'json',
        success: function(response) {
            hideLoadingWorkOrder();
            
            if (response.success) {
                // Auto-add items from work order
                if (response.items && response.items.length > 0) {
                    response.items.forEach(function(item) {
                        if (item.tipe == '1') {
                            // Add service/jasa
                            autoAddJasa(item);
                        } else if (item.tipe == '2') {
                            // Add barang
                            autoAddBarang(item);
                        }
                    });

                    // Update totals
                    updateServiceTotals();

                    // Show success message
                    showSuccessMessage('Paket Work Order "' + response.work_order_name + '" berhasil ditambahkan!');

                    // Save work order to service
                    saveWorkOrderToService(kodeWO, noService);

                    // Reload items in tabs to show the changes
                    setTimeout(function() {
                        if (typeof loadExistingItems === 'function') {
                            loadExistingItems();
                        }
                    }, 1000);
                } else {
                    showWarningMessage('Work Order tidak memiliki item atau sudah kosong');
                }
            } else {
                showErrorMessage('Error: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            hideLoadingWorkOrder();
            showErrorMessage('Terjadi kesalahan saat mengambil data work order: ' + error);
        }
    });
}

// Function to auto add jasa/service item
function autoAddJasa(item) {
    // Add to itemJasaList array if exists
    if (typeof itemJasaList !== 'undefined') {
        // Check if item already exists in array
        var existingIndex = itemJasaList.findIndex(jasa => jasa.kode === item.kode_barang);
        if (existingIndex >= 0) {
            // Update quantity
            itemJasaList[existingIndex].qty += parseInt(item.jumlah);
            itemJasaList[existingIndex].subtotal = itemJasaList[existingIndex].qty * itemJasaList[existingIndex].harga;
        } else {
            // Add new item to array with counter
            if (typeof itemJasaCounter === 'undefined') {
                window.itemJasaCounter = 1;
            } else {
                window.itemJasaCounter++;
            }

            itemJasaList.push({
                id: window.itemJasaCounter,
                kode: item.kode_barang,
                nama: item.nama_item || item.kode_barang,
                qty: parseInt(item.jumlah),
                harga: parseFloat(item.harga),
                subtotal: parseInt(item.jumlah) * parseFloat(item.harga),
                waktu: item.waktu || 0
            });
        }

        // Update the table display
        if (typeof updateItemJasaTable === 'function') {
            updateItemJasaTable();
        }
    }
}

// Function to auto add barang item
function autoAddBarang(item) {
    // Add to itemBarangList array if exists
    if (typeof itemBarangList !== 'undefined') {
        // Check if item already exists in array
        var existingIndex = itemBarangList.findIndex(barang => barang.kode === item.kode_barang);
        if (existingIndex >= 0) {
            // Update quantity
            itemBarangList[existingIndex].qty += parseInt(item.jumlah);
            itemBarangList[existingIndex].subtotal = itemBarangList[existingIndex].qty * itemBarangList[existingIndex].harga;
        } else {
            // Add new item to array with counter
            if (typeof itemBarangCounter === 'undefined') {
                window.itemBarangCounter = 1;
            } else {
                window.itemBarangCounter++;
            }

            itemBarangList.push({
                id: window.itemBarangCounter,
                kode: item.kode_barang,
                nama: item.nama_item || item.kode_barang,
                qty: parseInt(item.jumlah),
                satuan: item.satuan || 'Pcs',
                harga: parseFloat(item.harga),
                subtotal: parseInt(item.jumlah) * parseFloat(item.harga)
            });
        }

        // Update the table display
        if (typeof updateItemBarangTable === 'function') {
            updateItemBarangTable();
        }
    }
}

// Function to save work order to service
function saveWorkOrderToService(kodeWO, noService) {
    $.ajax({
        url: '_ajax/ajax-save-workorder-to-service.php',
        type: 'POST',
        data: {
            kode_wo: kodeWO,
            no_service: noService
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('Work order saved to service successfully');
            } else {
                console.log('Failed to save work order to service: ' + response.message);
            }
        },
        error: function() {
            console.log('Error saving work order to service');
        }
    });
}

// Function to save mechanic percentages
function saveMechanicPercentages(noService) {
    var mechanicData = {
        no_service: noService,
        kepala_mekanik1: $('#cbokepala_mekanik1').val() || '',
        persen_kepala1: $('#txtpersen_kepala1').val() || 0,
        kepala_mekanik2: $('#cbokepala_mekanik2').val() || '',
        persen_kepala2: $('#txtpersen_kepala2').val() || 0,
        mekanik1: $('#cbomekanik1').val() || '',
        persen_kerja1: $('#txtpersen_kerja1').val() || 0,
        mekanik2: $('#cbomekanik2').val() || '',
        persen_kerja2: $('#txtpersen_kerja2').val() || 0,
        mekanik3: $('#cbomekanik3').val() || '',
        persen_kerja3: $('#txtpersen_kerja3').val() || 0,
        mekanik4: $('#cbomekanik4').val() || '',
        persen_kerja4: $('#txtpersen_kerja4').val() || 0
    };

    $.ajax({
        url: '_ajax/ajax-save-mechanic-percentages.php',
        type: 'POST',
        data: mechanicData,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showSuccessMessage('Data mekanik berhasil disimpan');
            } else {
                showErrorMessage('Gagal menyimpan data mekanik: ' + response.message);
            }
        },
        error: function() {
            showErrorMessage('Terjadi kesalahan saat menyimpan data mekanik');
        }
    });
}

// Utility functions
function showLoadingWorkOrder() {
    if ($('#loadingWorkOrder').length === 0) {
        $('body').append('<div id="loadingWorkOrder" class="modal fade" role="dialog"><div class="modal-dialog"><div class="modal-content"><div class="modal-body text-center"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Loading Work Order...</div></div></div></div>');
    }
    $('#loadingWorkOrder').modal('show');
}

function hideLoadingWorkOrder() {
    $('#loadingWorkOrder').modal('hide');
}

function showSuccessMessage(message) {
    alert(message); // Can be replaced with better notification system
}

function showWarningMessage(message) {
    alert('Warning: ' + message);
}

function showErrorMessage(message) {
    alert('Error: ' + message);
}

function formatNumber(num) {
    return parseFloat(num).toLocaleString('id-ID');
}

// Update row totals
function updateJasaRowTotal(element) {
    var row = $(element).closest('tr');
    var qty = parseInt(row.find('.qty-input').val()) || 0;
    var harga = parseFloat(row.find('td:nth-child(4)').text().replace(/[^\d]/g, '')) || 0;
    var diskon = parseInt(row.find('.discount-input').val()) || 0;
    
    var subtotal = qty * harga;
    var total = subtotal - (subtotal * diskon / 100);
    
    row.find('.total-cell').text('Rp ' + formatNumber(total));
    updateServiceTotals();
}

function updateBarangRowTotal(element) {
    var row = $(element).closest('tr');
    var qty = parseInt(row.find('.qty-input').val()) || 0;
    var harga = parseFloat(row.find('td:nth-child(5)').text().replace(/[^\d]/g, '')) || 0;
    var diskon = parseInt(row.find('.discount-input').val()) || 0;
    
    var subtotal = qty * harga;
    var total = subtotal - (subtotal * diskon / 100);
    
    row.find('.total-cell').text('Rp ' + formatNumber(total));
    updateServiceTotals();
}

function updateServiceTotals() {
    // Calculate totals from arrays instead of DOM
    var totalJasa = 0;
    var totalBarang = 0;

    if (typeof itemJasaList !== 'undefined') {
        totalJasa = itemJasaList.reduce(function(sum, item) {
            return sum + (item.subtotal || 0);
        }, 0);
    }

    if (typeof itemBarangList !== 'undefined') {
        totalBarang = itemBarangList.reduce(function(sum, item) {
            return sum + (item.subtotal || 0);
        }, 0);
    }

    // Update display elements if they exist
    if ($('#totalItemJasa').length) {
        $('#totalItemJasa').text(formatNumber(totalJasa));
    }
    if ($('#totalItemBarang').length) {
        $('#totalItemBarang').text(formatNumber(totalBarang));
    }
    if ($('#grandTotal').length) {
        $('#grandTotal').text('Rp ' + formatNumber(totalJasa + totalBarang));
    }
}

function removeJasaRow(button) {
    $(button).closest('tr').remove();
    updateServiceTotals();
}

function removeBarangRow(button) {
    $(button).closest('tr').remove();
    updateServiceTotals();
}

// Auto-save mechanic percentages when changed
$(document).ready(function() {
    // Auto-save mechanic data when percentage fields change
    $('input[id*="txtpersen_"], select[id*="cbomekanik"], select[id*="cbokepala"]').on('change', function() {
        var noService = $('input[name="txtnosrv"]').val() || $('#txtnoservice').val();
        if (noService && noService.trim() !== '') {
            // Debounce the save operation
            clearTimeout(window.mechanicSaveTimeout);
            window.mechanicSaveTimeout = setTimeout(function() {
                saveMechanicPercentages(noService);
            }, 1000);
        }
    });
});