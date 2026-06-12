<?php
/**
 * Template: Form Input Temuan dengan Auto-Suggest Part
 * Digunakan di halaman servis-input-reguler.php, servis-input-reguler-jemput.php, servis-garansi.php
 */
?>

<style>
.suggested-part-row {
    transition: background-color 0.2s;
}
.suggested-part-row:hover {
    background-color: #f5f5f5;
}
.suggested-part-row.selected {
    background-color: #dff0d8;
}
.badge-primary-part {
    background-color: #5cb85c;
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 10px;
}
</style>

<div class="widget-box widget-color-orange2">
    <div class="widget-header widget-header-small">
        <h5 class="widget-title">
            <i class="ace-icon fa fa-search-plus"></i>
            Input Temuan Hasil Pengecekan
        </h5>
        <div class="widget-toolbar">
            <a href="#" data-action="collapse">
                <i class="ace-icon fa fa-chevron-down"></i>
            </a>
        </div>
    </div>

    <div class="widget-body">
        <div class="widget-main">

            <form id="form-input-temuan" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
                <input type="hidden" name="txtkm_skr" value="<?php echo $km_skr; ?>">
                <input type="hidden" name="txtkm_next" value="<?php echo $km_berikut; ?>">
                <input type="hidden" name="txtcaribrg" value="<?php echo $txtcaribrg ?? ''; ?>">
                <input type="hidden" name="txtcarisrv" value="<?php echo $txtcarisrv ?? ''; ?>">
                <input type="hidden" name="txtcariwo" value="<?php echo $txtcariwo ?? ''; ?>">
                <input type="hidden" name="nama_temuan" id="nama_temuan" value="">

                <table class="table table-bordered">
                    <!-- Row 1: Keluhan Terkait -->
                    <tr>
                        <td colspan="2">
                            <label>
                                <i class="ace-icon fa fa-link"></i>
                                Keluhan Terkait:
                                <span class="text-danger">*</span>
                            </label>
                            <select name="keluhan_id" id="keluhan_id" class="form-control input-sm">
                                <option value="">- Pilih Keluhan yang Terkait -</option>
                                <?php
                                $sql_keluhan = mysqli_query($koneksi, "SELECT id, keluhan, status_pengerjaan
                                                                        FROM tbservis_keluhan_status
                                                                        WHERE no_service='$no_service'
                                                                        ORDER BY id ASC");
                                while($keluhan = mysqli_fetch_array($sql_keluhan)) {
                                    $status_badge = '';
                                    if($keluhan['status_pengerjaan'] == 'selesai') {
                                        $status_badge = ' ✓';
                                    }
                                    echo "<option value='{$keluhan['id']}'>{$keluhan['keluhan']}{$status_badge}</option>";
                                }
                                ?>
                            </select>
                            <small class="help-block">Pilih keluhan customer yang terkait dengan temuan ini</small>
                        </td>
                    </tr>

                    <!-- Row 2: Pilih Temuan (via Modal) -->
                    <tr>
                        <td colspan="2">
                            <label>
                                <i class="ace-icon fa fa-list"></i>
                                Temuan:
                            </label>
                            <input type="hidden" name="kode_temuan" id="kode_temuan" value="">
                            <div class="input-group input-group-sm">
                                <input type="text" id="nama_temuan_display" class="form-control" placeholder="Ketik atau pilih temuan...">
                                <span class="input-group-btn">
                                    <button type="button" class="btn btn-info btn-sm" onclick="$('#modalSearchTemuan').modal('show');">
                                        <i class="ace-icon fa fa-search"></i> Cari Temuan
                                    </button>
                                </span>
                            </div>
                            <small class="help-block text-muted">Gunakan tombol di atas untuk mencari dan memilih temuan</small>
                        </td>
                    </tr>

                    <!-- Row 3: Deskripsi Temuan -->
                    <tr>
                        <td colspan="2">
                            <label>
                                <i class="ace-icon fa fa-file-text-o"></i>
                                Deskripsi Detail Temuan:
                            </label>
                            <textarea name="deskripsi_temuan" id="deskripsi_temuan"
                                      class="form-control input-sm" rows="3"
                                      placeholder="Jelaskan detail kondisi/kerusakan yang ditemukan..."></textarea>
                        </td>
                    </tr>

                    <!-- Row 4: Tingkat Urgensi & Jenis Perbaikan -->
                    <tr>
                        <td width="50%">
                            <label>
                                <i class="ace-icon fa fa-exclamation-triangle"></i>
                                Tingkat Urgensi:
                                <span class="text-danger">*</span>
                            </label>
                            <select name="tingkat_urgensi" id="tingkat_urgensi" class="form-control input-sm">
                                <option value="rendah">🟢 Rendah (Bisa ditunda)</option>
                                <option value="sedang" selected>🟡 Sedang (Perlu segera)</option>
                                <option value="tinggi">🟠 Tinggi (Urgent)</option>
                            </select>
                        </td>
                        <td width="50%">
                            <label>
                                <i class="ace-icon fa fa-wrench"></i>
                                Jenis Perbaikan:
                                <span class="text-danger">*</span>
                            </label>
                            <select name="jenis_perbaikan" id="jenis_perbaikan"
                                    class="form-control input-sm"
                                    onchange="togglePartSuggestion(this.value)">
                                <option value="setting">⚙️ Hanya Setting/Servis Saja</option>
                                <option value="penggantian_part">🛒 Perlu Penggantian Part</option>
                            </select>
                        </td>
                    </tr>

                    <!-- Row 7: Button Submit -->
                    <tr>
                        <td colspan="2" class="center">
                            <button type="button" class="btn btn-warning btn-sm" onclick="submitTemuan()">
                                <i class="ace-icon fa fa-save"></i>
                                Simpan Temuan
                            </button>
                            <button type="reset" class="btn btn-default btn-sm">
                                <i class="ace-icon fa fa-refresh"></i>
                                Reset Form
                            </button>
                        </td>
                    </tr>
                </table>
            </form>

            <!-- Area Penawaran Part (Auto-Suggested) -->
            <div id="area-penawaran-part" style="display:none; margin-top: 20px;">
                <hr>
                <h5 class="header blue">
                    <i class="ace-icon fa fa-shopping-cart"></i>
                    Part yang Disarankan untuk Temuan Ini
                </h5>

                <div id="loading-parts" style="display:none;">
                    <div class="alert alert-info">
                        <i class="ace-icon fa fa-spinner fa-spin"></i>
                        Memuat saran part dari sistem...
                    </div>
                </div>

                <div id="suggested-parts-container">
                    <!-- Will be populated via AJAX -->
                </div>

                <div style="margin-top: 10px;">
                    <button type="button" class="btn btn-sm btn-primary" onclick="openFastMovesModal()" style="margin-right: 6px;">
                        <i class="ace-icon fa fa-bolt"></i>
                        Fast Moves Part
                    </button>
                    <button type="button" class="btn btn-sm btn-success" onclick="addCustomPartToSuggestion()">
                        <i class="ace-icon fa fa-plus"></i>
                        Tambah Part Manual (Tidak di List)
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// ========================================
// JAVASCRIPT FUNCTIONS
// ========================================

function togglePartSuggestion(jenisPerbaikan) {
    var areaPenawaran = document.getElementById('area-penawaran-part');

    if(jenisPerbaikan == 'penggantian_part') {
        areaPenawaran.style.display = 'block';

        // Auto-load suggested parts if kode_temuan already selected
        var kodeTemuan = document.getElementById('kode_temuan').value;
        if(kodeTemuan != '') {
            loadSuggestedParts(kodeTemuan);
        } else {
            var namaDisplayEl = document.getElementById('nama_temuan_display');
            var namaDisplay = namaDisplayEl && namaDisplayEl.value ? namaDisplayEl.value.trim() : '';
            if(namaDisplay) {
                loadSuggestedPartsFallbackByNama(namaDisplay);
            }
        }
    } else {
        areaPenawaran.style.display = 'none';
    }
}

function loadSuggestedParts(kodeTemuan) {
    if (typeof window.jQuery === 'undefined') { console.warn('jQuery not ready'); return; }
    if(kodeTemuan == '') {
        document.getElementById('suggested-parts-container').innerHTML = '';
        return;
    }

    // Show loading
    document.getElementById('loading-parts').style.display = 'block';
    document.getElementById('suggested-parts-container').innerHTML = '';

    // AJAX call to get suggested parts via existing handler
    $.ajax({
        url: '_handler_temuan_penawaran.php',
        type: 'GET',
        data: { action: 'get_parts_by_temuan_kode', kode_temuan: kodeTemuan },
        dataType: 'json'
    }).done(function(resp){
        document.getElementById('loading-parts').style.display = 'none';
        if(resp && resp.success && resp.count > 0){
            renderSuggestedParts(resp.parts || []);
        } else {
            // Coba fallback berdasarkan teks temuan
            var namaDisplayEl = document.getElementById('nama_temuan_display');
            var namaDisplay = namaDisplayEl && namaDisplayEl.value ? namaDisplayEl.value.trim() : '';
            if(namaDisplay) {
                loadSuggestedPartsFallbackByNama(namaDisplay);
            } else {
                document.getElementById('suggested-parts-container').innerHTML =
                    '<div class="alert alert-warning">' +
                    '<i class="ace-icon fa fa-info-circle"></i> ' +
                    'Tidak ada part yang disarankan untuk temuan ini di sistem. ' +
                    'Silakan tambah part manual atau hubungi admin untuk menambahkan mapping.' +
                    '</div>';
            }
        }
    }).fail(function(_, __, error){
        document.getElementById('loading-parts').style.display = 'none';
        document.getElementById('suggested-parts-container').innerHTML =
            '<div class="alert alert-danger">' +
            '<i class="ace-icon fa fa-exclamation-triangle"></i> ' +
            'Error loading suggested parts: ' + (error||'unknown') +
            '</div>';
    });
}

// Fallback: rekomendasi berdasarkan teks temuan (kategori/keyword)
function loadSuggestedPartsFallbackByNama(namaTemuan) {
    if (typeof window.jQuery === 'undefined') { console.warn('jQuery not ready'); return; }
    document.getElementById('loading-parts').style.display = 'block';
    document.getElementById('suggested-parts-container').innerHTML = '';
    $.ajax({
        url: '_handler_temuan_penawaran.php',
        type: 'GET',
        data: { action: 'get_recommended_parts_for_temuan', temuan: namaTemuan },
        dataType: 'json'
    }).done(function(resp){
        document.getElementById('loading-parts').style.display = 'none';
        if(resp && resp.parts && resp.parts.length > 0){
            renderSuggestedParts(resp.parts);
        } else {
            document.getElementById('suggested-parts-container').innerHTML =
                '<div class="alert alert-warning">' +
                '<i class="ace-icon fa fa-info-circle"></i> '
                + 'Tidak ada part yang disarankan untuk temuan ini di sistem. '
                + 'Silakan tambah part manual atau gunakan tombol Fast Moves.' +
                '</div>';
        }
    }).fail(function(_, __, error){
        document.getElementById('loading-parts').style.display = 'none';
        document.getElementById('suggested-parts-container').innerHTML =
            '<div class="alert alert-danger">' +
            '<i class="ace-icon fa fa-exclamation-triangle"></i> ' +
            'Error loading suggested parts: ' + (error||'unknown') +
            '</div>';
    });
}

function renderSuggestedParts(parts) {
    var html = '<div class="alert alert-success">';
    html += '<i class="ace-icon fa fa-lightbulb-o"></i> ';
    html += '<strong>Sistem merekomendasikan ' + parts.length + ' part berikut:</strong> ';
    html += 'Centang part yang akan ditawarkan ke customer.';
    html += '</div>';

    html += '<table class="table table-bordered table-striped table-hover">';
    html += '<thead><tr class="info">';
    html += '<th width="5%" class="center">✓</th>';
    html += '<th width="12%">Kode Part</th>';
    html += '<th width="28%">Nama Part</th>';
    html += '<th width="8%" class="center">Stok</th>';
    html += '<th width="8%" class="center">Status</th>';
    html += '<th width="8%" class="center">Qty</th>';
    html += '<th width="13%" class="right">Harga Satuan</th>';
    html += '<th width="13%" class="right">Total</th>';
    html += '<th width="5%" class="center">Aksi</th>';
    html += '</tr></thead><tbody>';

    parts.forEach(function(part, index) {
        var isPrimary = String(part.is_primary) === '1';
        var rowClass = isPrimary ? 'suggested-part-row selected' : 'suggested-part-row';
        var checked = isPrimary ? 'checked' : '';
        var stok = parseInt(part.stok_tersedia||0,10);

        html += '<tr class="' + rowClass + '" id="row_' + index + '" ' +
                ' data-index="'+index+'" ' +
                ' data-kode="'+(part.kode_barang||'')+'" ' +
                ' data-nama="'+(part.nama_barang||'')+'" ' +
                ' data-harga="'+(part.harga_jual||0)+'" ' +
                ' data-mapping-id="'+(part.mapping_id||'')+'" ' +
                ' data-prioritas="'+(part.prioritas||'')+'" ' +
                ' data-stok="'+stok+'" ' +
            '>';

        // Checkbox (allow selection even if stok = 0 -> akan jadi indent/pending)
        html += '<td class="center">';
        html += '<input type="checkbox" name="selected_parts[]" value="' + (part.kode_barang||'') + '" ' + checked + ' ';
        html += 'data-index="' + index + '" onchange="togglePartSelection(this, ' + index + ')">';
        if (stok <= 0) {
            html += '<br><span class="label label-warning" title="Stok habis, akan menjadi indent/pending">Indent</span>';
        }
        html += '</td>';

        // Kode Part
        html += '<td>' + part.kode_barang;
        if(isPrimary) {
            html += '<br><span class="badge-primary-part">REKOMENDASI</span>';
        }
        html += '</td>';

        // Nama Part
        html += '<td><strong>' + part.nama_barang + '</strong>';
        if(part.keterangan) {
            html += '<br><small class="text-muted"><i>' + part.keterangan + '</i></small>';
        }
        html += '</td>';

        // Stok
        html += '<td class="center"><strong>' + stok + '</strong></td>';

        // Status Stok
        var labelClass = stok > 0 ? 'label-success' : 'label-danger';
        html += '<td class="center">';
        html += '<span class="label ' + labelClass + '">' + (stok>0 ? 'Ready' : 'Habis') + '</span>';
        html += '</td>';

        // Quantity Input (allow input even if stok = 0; set max to 999)
        html += '<td class="center">';
        var maxQty = (stok > 0) ? stok : 999;
        var defQty = (parseInt(part.qty_default || 0, 10) > 0) ? part.qty_default : 1;
        html += '<input type="number" name="qty_' + (part.kode_barang||'') + '" id="qty_' + index + '" ';
        html += 'class="form-control input-sm text-center" value="' + defQty + '" ';
        html += 'min="1" max="' + maxQty + '" style="width:60px;" ';
        html += 'onchange="calculatePartTotal(' + index + ', ' + (part.harga_jual||0) + ')">';
        html += '</td>';

        // Harga Satuan
        html += '<td class="right">';
        var hargaFmt = (parseInt(part.harga_jual||0,10)).toLocaleString('id-ID');
        html += '<span id="harga_' + index + '">Rp ' + hargaFmt + '</span>';
        html += '<input type="hidden" name="harga_' + (part.kode_barang||'') + '" value="' + (part.harga_jual||0) + '">';
        html += '</td>';

        // Total
        html += '<td class="right">';
        var defTotal = (defQty * (part.harga_jual||0));
        html += '<strong><span id="total_' + index + '">Rp ' + formatNumber(defTotal) + '</span></strong>';
        html += '</td>';

        // Aksi
        html += '<td class="center">';
        html += '<a href="#" onclick="showPartDetail(\'' + part.kode_barang + '\'); return false;" ';
        html += 'data-rel="tooltip" title="Detail Part">';
        html += '<i class="ace-icon fa fa-info-circle blue bigger-130"></i>';
        html += '</a>';
        html += '</td>';

        html += '</tr>';
    });

    html += '</tbody></table>';

    // Grand Total Selected Parts
    html += '<div class="well well-sm">';
    html += '<div class="row">';
    html += '<div class="col-xs-8 text-right"><h5><strong>Total Penawaran Part:</strong></h5></div>';
    html += '<div class="col-xs-4 text-right"><h5><strong><span id="grand_total_parts">Rp 0</span></strong></h5></div>';
    html += '</div>';
    html += '</div>';

    document.getElementById('suggested-parts-container').innerHTML = html;

    // Calculate initial grand total
    calculateGrandTotalParts();
}

function togglePartSelection(checkbox, index) {
    var row = document.getElementById('row_' + index);
    if(checkbox.checked) {
        row.classList.add('selected');
    } else {
        row.classList.remove('selected');
    }

    // Recalculate grand total
    calculateGrandTotalParts();
}

function calculatePartTotal(index, hargaSatuan) {
    var qty = document.getElementById('qty_' + index).value || 1;
    var total = qty * hargaSatuan;
    document.getElementById('total_' + index).innerText = 'Rp ' + formatNumber(total);

    // Recalculate grand total
    calculateGrandTotalParts();
}

function calculateGrandTotalParts() {
    var checkboxes = document.querySelectorAll('input[name="selected_parts[]"]:checked');
    var grandTotal = 0;

    checkboxes.forEach(function(checkbox) {
        var index = checkbox.getAttribute('data-index');
        var totalText = document.getElementById('total_' + index).innerText;
        var total = parseInt(totalText.replace(/[^0-9]/g, ''));
        grandTotal += total;
    });

    document.getElementById('grand_total_parts').innerText = 'Rp ' + formatNumber(grandTotal);
}

function formatNumber(num) {
    return parseInt(num).toLocaleString('id-ID');
}

function addCustomPartToSuggestion() {
    // TODO: Implementasi modal untuk tambah part manual
    alert('Fitur tambah part manual akan dibuka di modal terpisah');
}

function showPartDetail(kodeBarang) {
    // TODO: Implementasi modal detail part
    alert('Detail part: ' + kodeBarang);
}

function submitTemuan() {
    // Validasi form
    var form = document.getElementById('form-input-temuan');
    // Fallback: jika inner form tidak ada (HTML melarang nested form), gunakan form utama halaman
    if(!form) {
        form = document.querySelector('form.form-horizontal') || document.querySelector('form');
    }
    if(!form){
        alert('Form tidak ditemukan. Muat ulang halaman dan coba lagi.');
        return false;
    }
    var keluhanId = document.getElementById('keluhan_id').value;
    var kodeTemuan = document.getElementById('kode_temuan').value;
    var namaTemuanHidden = document.getElementById('nama_temuan');
    var jenisPerbaikan = document.getElementById('jenis_perbaikan').value;

    // Validasi keluhan harus dipilih
    if(!keluhanId) {
        alert('Pilih keluhan yang terkait terlebih dahulu!');
        document.getElementById('keluhan_id').focus();
        return false;
    }

    // Validasi temuan (boleh master atau manual): jika tidak ada kode_temuan dan hidden kosong, ambil dari display
    var namaFromHidden = namaTemuanHidden && namaTemuanHidden.value ? namaTemuanHidden.value.trim() : '';
    var namaDisplayEl = document.getElementById('nama_temuan_display');
    var namaDisplay = namaDisplayEl && namaDisplayEl.value ? namaDisplayEl.value.trim() : '';
    if(!kodeTemuan && !namaFromHidden) {
        if(namaDisplay) {
            // Set ke hidden agar server bisa memproses standardisasi otomatis
            namaTemuanHidden.value = namaDisplay;
        } else {
            alert('Isi nama temuan atau pilih dari master terlebih dahulu!');
            if(namaTemuanHidden) { namaTemuanHidden.focus(); }
            return false;
        }
    }

    // Jika jenis perbaikan = penggantian_part, cek apakah ada part yang dipilih
    if(jenisPerbaikan == 'penggantian_part') {
        var selectedParts = document.querySelectorAll('input[name="selected_parts[]"]:checked');
        if(selectedParts.length == 0) {
            var konfirmasi = confirm(
                'Anda memilih "Penggantian Part" tetapi tidak ada part yang dicentang.\n\n' +
                'Apakah Anda yakin ingin melanjutkan tanpa penawaran part?\n' +
                '(Anda bisa menambahkan penawaran part nanti)'
            );
            if(!konfirmasi) {
                return false;
            }
        }
    }

    // Konfirmasi final
    var konfirmasiSubmit = confirm('Simpan temuan ini dan buat penawaran part (jika ada)?');
    if(konfirmasiSubmit) {
        // Set nama_temuan hidden dari tampilan display (fallback yang aman)
        var namaHidden = document.getElementById('nama_temuan');
        var namaDisplayEl = document.getElementById('nama_temuan_display');
        var namaDisplay = namaDisplayEl && namaDisplayEl.value ? namaDisplayEl.value.trim() : '';
        if(namaHidden && namaDisplay) {
            namaHidden.value = namaDisplay;
        }
        // Kemas selected parts ke hidden payload JSON agar diproses handler
        var selected = document.querySelectorAll('input[name="selected_parts[]"]:checked');
        if(selected && selected.length > 0){
            var payload = [];
            selected.forEach(function(cb){
                var idx = cb.getAttribute('data-index');
                var row = document.getElementById('row_'+idx);
                if(!row) return;
                var kode = row.getAttribute('data-kode') || '';
                var nama = row.getAttribute('data-nama') || '';
                var harga = parseFloat(row.getAttribute('data-harga') || '0');
                var qtyEl = document.getElementById('qty_'+idx);
                var qty = qtyEl ? parseInt(qtyEl.value||'1',10) : 1;
                var mappingId = row.getAttribute('data-mapping-id') || null;
                var prioritas = row.getAttribute('data-prioritas') || null;
                payload.push({ kode_barang: kode, nama_barang: nama, harga_satuan: harga, quantity: qty, mapping_id: mappingId, prioritas: prioritas });
            });
            var hiddenPayload = document.createElement('input');
            hiddenPayload.type = 'hidden';
            hiddenPayload.name = 'selected_parts_payload';
            hiddenPayload.value = JSON.stringify(payload);
            form.appendChild(hiddenPayload);
        }
        // Pastikan flag btnaddtemuan hanya ada ketika menyimpan temuan
        var flag = form.querySelector('input[name="btnaddtemuan"]');
        if(!flag){
            flag = document.createElement('input');
            flag.type = 'hidden';
            flag.name = 'btnaddtemuan';
            flag.value = '1';
            form.appendChild(flag);
        }
        // Submit form
        form.submit();
    }
}

// Initialize: Hide area penawaran part on load - WAIT FOR JQUERY
(function waitForJQ() {
    if (typeof window.jQuery !== 'undefined') {
        initTemuanInput();
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

function initTemuanInput() {
    var jp = document.getElementById('jenis_perbaikan');
    if (jp && jp.value === 'penggantian_part') {
        togglePartSuggestion('penggantian_part');
    } else {
        var area = document.getElementById('area-penawaran-part');
        if(area) area.style.display = 'none';
    }
}

// Open Fast Moves modal (cari ID yang tersedia)
function openFastMovesModal(){
    if (typeof jQuery !== 'undefined') {
        if (jQuery('#modalFastMovesV2').length) {
            jQuery('#modalFastMovesV2').modal('show');
            return;
        }
        if (jQuery('#modalFastMovesPart').length) {
            jQuery('#modalFastMovesPart').modal('show');
            return;
        }
        if (jQuery('#modalFastMoves').length) {
            jQuery('#modalFastMoves').modal('show');
        }
    }
}
</script>
