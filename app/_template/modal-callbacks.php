<!-- ========================================== -->
<!-- CALLBACK FUNCTIONS untuk Modal -->
<!-- MUST be loaded BEFORE modal includes -->
<!-- ========================================== -->
<script>
console.log('=== Loading Modal Callbacks ===');

// Callback untuk Modal Cari Temuan - Support both v2 and original field IDs
window.onTemuanSelected = function(kode, nama, kategori, urgensi) {
    console.log('onTemuanSelected called:', {kode: kode, nama: nama, kategori: kategori, urgensi: urgensi});

    // Helper function to set value with fallback
    function setFieldValue(v2Id, originalId, value) {
        var field = window.jQuery('#' + v2Id);
        if(field.length) {
            field.val(value);
            console.log('Set ' + v2Id + ' = ' + value);
        } else {
            field = window.jQuery('#' + originalId);
            if(field.length) {
                field.val(value);
                console.log('Set ' + originalId + ' = ' + value);
            }
        }
        return field;
    }

    // Set nilai ke form - try v2 fields first, fallback to original
    setFieldValue('kode_temuan_v2', 'kode_temuan', kode);
    setFieldValue('nama_temuan_v2', 'nama_temuan', nama);
    var displayField = setFieldValue('nama_temuan_display_v2', 'nama_temuan_display', nama);

    // Support Edit Temuan modal fields
    if(window.jQuery('#edit_kode_temuan').length) {
        window.jQuery('#edit_kode_temuan').val(kode);
    }
    if(window.jQuery('#edit_nama_temuan_display').length) {
        window.jQuery('#edit_nama_temuan_display').val(nama);
    }

    // Set kategori jika field ada
    setFieldValue('kategori_temuan_v2', 'kategori_temuan', kategori);

    // Set urgensi - try v2 first
    setFieldValue('tingkat_urgensi_v2', 'tingkat_urgensi', urgensi);
    if(window.jQuery('#edit_temuan_urgensi').length) {
        window.jQuery('#edit_temuan_urgensi').val(urgensi);
    }

    // Visual feedback on display field
    if(displayField && displayField.length) {
        displayField.css('backgroundColor', '#d4edda');
        setTimeout(function() {
            displayField.css('backgroundColor', '');
        }, 1500);
    }

    console.log('Form updated with temuan data');
    // Auto-load suggested parts if penggantian_part dipilih
    try {
        var jp = document.getElementById('jenis_perbaikan_v2') || document.getElementById('jenis_perbaikan') || document.getElementById('edit_temuan_jenis');
        if (jp && jp.value === 'penggantian_part') {
            // Try v2 function first, fallback to original
            if (typeof loadSuggestedPartsV2 === 'function') {
                loadSuggestedPartsV2(kode || '');
            } else if (typeof loadSuggestedParts === 'function') {
                loadSuggestedParts(kode || '');
            }
        }
    } catch(e) { console.log('Error loading suggested parts:', e); }
};

// Callback untuk Input Temuan Manual - Support both v2 and original field IDs
window.onTemuanManualCreated = function(kode, nama, kategori, urgensi, deskripsi) {
    var manualName = nama || kode || '';
    console.log('onTemuanManualCreated called:', {kode: kode, nama: manualName, kategori: kategori, urgensi: urgensi});

    // Helper function to set value with fallback
    function setFieldValue(v2Id, originalId, value) {
        var field = window.jQuery('#' + v2Id);
        if(field.length) {
            field.val(value);
        } else {
            field = window.jQuery('#' + originalId);
            if(field.length) {
                field.val(value);
            }
        }
        return field;
    }

    // Set nilai ke form - try v2 fields first
    setFieldValue('kode_temuan_v2', 'kode_temuan', '');
    setFieldValue('nama_temuan_v2', 'nama_temuan', manualName);
    var displayField = setFieldValue('nama_temuan_display_v2', 'nama_temuan_display', manualName);

    // Set urgensi
    if(urgensi) {
        setFieldValue('tingkat_urgensi_v2', 'tingkat_urgensi', urgensi);
    }

    // Visual feedback - yellow for manual
    if(displayField && displayField.length) {
        displayField.css('backgroundColor', '#fff3cd');
        setTimeout(function() {
            displayField.css('backgroundColor', '');
        }, 1500);
    }
};

// Callback untuk Modal Fast Moves
window.onFastMovesPartSelected = function(kode, nama, harga, satuan, qty) {
    console.log('onFastMovesPartSelected called:', { kode: kode, nama: nama, harga: harga, satuan: satuan, qty: qty });
    var q = parseInt(qty || 1, 10) || 1;
    var h = parseFloat(harga || 0) || 0;
    var total = q * h;

    if (window.jQuery('#kode_barang_penawaran').length) {
        window.jQuery('#kode_barang_penawaran').val(kode);
        window.jQuery('#nama_barang_penawaran').val(nama);
        window.jQuery('#harga_satuan_penawaran').val(h);
        window.jQuery('#quantity_penawaran').val(q);
        if (window.jQuery('#total_penawaran_display').length) {
            try { window.jQuery('#total_penawaran_display').val('Rp ' + (total).toLocaleString('id-ID')); } catch(e) { window.jQuery('#total_penawaran_display').val(total); }
        }
    } else {
        window.jQuery('#kode_part').val(kode);
        window.jQuery('#nama_part').val(nama);
        window.jQuery('#harga_part').val(h);
        window.jQuery('#satuan_part').val(satuan);
        window.jQuery('#qty_part').val(q);
        if (window.jQuery('#subtotal_part').length) { window.jQuery('#subtotal_part').val(total); }
    }
};

console.log('=== Modal Callbacks Loaded ===');
console.log('Available callbacks:', {
    onTemuanSelected: typeof window.onTemuanSelected,
    onTemuanManualCreated: typeof window.onTemuanManualCreated,
    onFastMovesPartSelected: typeof window.onFastMovesPartSelected
});
</script>
