<?php
/**
 * REDESIGN: Modal Search Barang
 * Clean & Focused UI v2.0
 */
?>

<!-- Modal Search Barang -->
<div class="modal fade rd-modal" id="modalSearchBarang" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-search"></i> Cari Barang
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Search Form -->
                <div class="rd-form-row" style="margin-bottom: 20px;">
                    <div class="rd-form-group" style="flex: 1;">
                        <div class="rd-input-group">
                            <span class="rd-input-addon"><i class="fa fa-search"></i></span>
                            <input type="text" id="searchBarangKeyword" class="rd-input"
                                   placeholder="Ketik kode atau nama barang...">
                        </div>
                    </div>
                    <div class="rd-form-group" style="flex: 0 0 150px;">
                        <select id="searchBarangKategori" class="rd-input">
                            <option value="">Semua Kategori</option>
                            <?php
                            $sql_kat = mysqli_query($koneksi, "SELECT DISTINCT kategori FROM tblbarang WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori");
                            if($sql_kat) {
                                while($kat = mysqli_fetch_array($sql_kat)) {
                                    echo "<option value='".htmlspecialchars($kat['kategori'])."'>".htmlspecialchars($kat['kategori'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="rd-form-group" style="flex: 0 0 auto;">
                        <button type="button" class="rd-btn primary" onclick="doSearchBarang()">
                            <i class="fa fa-search"></i> Cari
                        </button>
                    </div>
                </div>

                <!-- Results -->
                <div id="searchBarangResults">
                    <div class="rd-empty-state" style="padding: 40px;">
                        <i class="fa fa-box-open"></i>
                        <h6>Cari Barang</h6>
                        <p>Masukkan kata kunci dan klik Cari</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="rd-btn outline-neutral" data-dismiss="modal">
                    <i class="fa fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Search barang
function doSearchBarang() {
    var keyword = $('#searchBarangKeyword').val().trim();
    var kategori = $('#searchBarangKategori').val();

    if(keyword.length < 2) {
        alert('Minimal 2 karakter untuk pencarian');
        return;
    }

    $('#searchBarangResults').html('<div class="rd-loading"><i class="fa fa-spinner fa-spin"></i> Mencari...</div>');

    $.ajax({
        url: 'servis-add-item-cari.php',
        type: 'GET',
        data: {
            q: keyword,
            kategori: kategori,
            format: 'json'
        },
        dataType: 'json',
        success: function(response) {
            if(response && response.length > 0) {
                var html = '<div class="rd-table-wrapper" style="max-height: 350px; overflow-y: auto;">';
                html += '<table class="rd-table">';
                html += '<thead><tr>';
                html += '<th width="15%">Kode</th>';
                html += '<th>Nama Barang</th>';
                html += '<th width="10%" class="text-center">Stok</th>';
                html += '<th width="15%" class="text-right">Harga</th>';
                html += '<th width="10%" class="text-center">Aksi</th>';
                html += '</tr></thead>';
                html += '<tbody>';

                response.forEach(function(item) {
                    var stokClass = item.stok <= 0 ? 'danger' : (item.stok <= 5 ? 'warning' : 'success');
                    html += '<tr>';
                    html += '<td><code>' + item.kode + '</code></td>';
                    html += '<td><strong>' + item.nama + '</strong>';
                    if(item.kategori) {
                        html += '<br><span class="rd-badge neutral">' + item.kategori + '</span>';
                    }
                    html += '</td>';
                    html += '<td class="text-center"><span class="rd-badge ' + stokClass + '">' + item.stok + '</span></td>';
                    html += '<td class="text-right">Rp ' + parseInt(item.harga).toLocaleString('id-ID') + '</td>';
                    html += '<td class="text-center">';
                    if(item.stok > 0) {
                        html += '<button type="button" class="rd-btn xs primary" onclick="pilihBarangSearch(\'' + item.kode + '\', \'' + escapeHtml(item.nama) + '\', ' + item.harga + ', ' + item.stok + ')">';
                        html += '<i class="fa fa-plus"></i>';
                        html += '</button>';
                    } else {
                        html += '<span class="rd-badge danger">Habis</span>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                html += '<div style="padding: 12px; background: var(--rd-bg-light); text-align: right; font-size: 12px; color: var(--rd-text-muted);">';
                html += 'Ditemukan ' + response.length + ' barang';
                html += '</div>';

                $('#searchBarangResults').html(html);
            } else {
                $('#searchBarangResults').html(
                    '<div class="rd-empty-state" style="padding: 40px;">' +
                    '<i class="fa fa-search"></i>' +
                    '<h6>Tidak Ditemukan</h6>' +
                    '<p>Tidak ada barang yang cocok dengan pencarian</p>' +
                    '</div>'
                );
            }
        },
        error: function() {
            $('#searchBarangResults').html(
                '<div class="rd-alert danger">' +
                '<i class="fa fa-exclamation-circle"></i>' +
                '<div>Error saat mencari barang. Silakan coba lagi.</div>' +
                '</div>'
            );
        }
    });
}

// Select barang from search
function pilihBarangSearch(kode, nama, harga, stok) {
    // Fill the form (v2 version)
    if($('#add_kode_barang_v2').length) {
        $('#add_kode_barang_v2').val(kode);
        $('#add_nama_barang_v2').val(nama);
        $('#add_harga_barang_v2').val(harga);
        $('#add_stok_barang_v2').val(stok);
        $('#add_qty_barang_v2').val(1).attr('max', stok);

        if(typeof hitungTotalBarangV2 === 'function') {
            hitungTotalBarangV2();
        }
    }

    // Close modal
    $('#modalSearchBarang').modal('hide');

    // Highlight selected fields
    $('#add_kode_barang_v2, #add_nama_barang_v2').addClass('rd-highlight');
    setTimeout(function() {
        $('#add_kode_barang_v2, #add_nama_barang_v2').removeClass('rd-highlight');
    }, 1000);
}

// Helper function
function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Enter key to search
$('#searchBarangKeyword').on('keypress', function(e) {
    if(e.which === 13) {
        e.preventDefault();
        doSearchBarang();
    }
});
</script>
