<?php
/**
 * REDESIGN: Tab Item Barang
 * Clean & Focused UI v2.0
 */

// Get items data
$sql_barang = mysqli_query($koneksi, "SELECT sb.*,
    COALESCE(b.nama_item, sb.nama_item) as nama_display,
    COALESCE(b.satuan, 'PCS') as satuan_display
    FROM tblservis_barang sb
    LEFT JOIN tblitem b ON sb.no_item = b.no_item
    WHERE sb.no_service = '$no_service'
    ORDER BY sb.id ASC");

$total_barang = 0;
$count_barang = 0;
$items_barang = [];
if($sql_barang) {
    while($row = mysqli_fetch_array($sql_barang)) {
        $items_barang[] = $row;
        $total_barang += $row['total'];
        $count_barang++;
    }
}
?>

<!-- Summary Stats -->
<div class="rd-stats-row">
    <div class="rd-stat-box">
        <div class="icon primary"><i class="fa fa-cubes"></i></div>
        <div class="value"><?= $count_barang ?></div>
        <div class="label">Total Item</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon success"><i class="fa fa-money-bill"></i></div>
        <div class="value">Rp <?= number_format($total_barang, 0, ',', '.') ?></div>
        <div class="label">Total Harga</div>
    </div>
</div>

<!-- Add Item Section -->
<div class="rd-card primary">
    <div class="rd-card-header collapsible" onclick="toggleCardBody(this)">
        <h5><i class="fa fa-plus-circle"></i> Tambah Item Barang</h5>
        <i class="fa fa-chevron-down rd-collapse-icon"></i>
    </div>
    <div class="rd-card-body">
        <!-- Quick Actions -->
        <div class="rd-flex rd-gap-8 rd-mb-16">
            <button type="button" class="rd-btn sm primary" onclick="$('#modalFastMovesV2').modal('show');">
                <i class="fa fa-bolt"></i> Fast Moves
            </button>
            <button type="button" class="rd-btn sm warning" onclick="$('#modalInputCustom').modal('show');">
                <i class="fa fa-plus-circle"></i> Barang Custom
            </button>
        </div>

        <!-- Search Input -->
        <div class="rd-form-group">
            <label class="rd-label">Cari Barang</label>
            <div class="rd-input-group">
                <input type="text" id="search_barang_v2" class="rd-input" placeholder="Ketik kode atau nama barang...">
                <button type="button" class="rd-btn primary" onclick="searchBarangV2()">
                    <i class="fa fa-search"></i> Cari
                </button>
            </div>
        </div>

        <!-- Search Results -->
        <div id="search_results_barang_v2" style="display: none; margin-top: 16px;">
            <div class="rd-table-wrapper" style="max-height: 250px; overflow-y: auto;">
                <table class="rd-table">
                    <thead>
                        <tr>
                            <th width="15%">Kode</th>
                            <th>Nama Barang</th>
                            <th width="10%" class="text-center">Stok</th>
                            <th width="15%" class="text-right">Harga</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="search_results_tbody_barang">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rd-divider"></div>

        <!-- Manual Input Form -->
        <div id="form_add_barang_v2">
            <div class="rd-form-row">
                <div class="rd-form-group" style="flex: 0 0 150px;">
                    <label class="rd-label rd-required">Kode</label>
                    <input type="text" id="add_kode_barang_v2" name="kode_barang" class="rd-input sm" placeholder="Kode" readonly style="background: var(--rd-bg-light);">
                </div>
                <div class="rd-form-group">
                    <label class="rd-label rd-required">Nama Barang</label>
                    <input type="text" id="add_nama_barang_v2" name="nama_barang" class="rd-input sm" placeholder="Nama barang" readonly style="background: var(--rd-bg-light);">
                </div>
            </div>
            <div class="rd-form-row">
                <div class="rd-form-group">
                    <label class="rd-label rd-required">Harga</label>
                    <input type="number" id="add_harga_barang_v2" name="harga_satuan" class="rd-input sm" placeholder="0" min="0">
                </div>
                <div class="rd-form-group" style="flex: 0 0 100px;">
                    <label class="rd-label rd-required">Qty</label>
                    <input type="number" id="add_qty_barang_v2" name="quantity" class="rd-input sm" value="1" min="1">
                </div>
                <div class="rd-form-group" style="flex: 0 0 80px;">
                    <label class="rd-label">Disc %</label>
                    <input type="number" id="add_disc_barang_v2" name="diskon" class="rd-input sm" value="0" min="0" max="100">
                </div>
                <div class="rd-form-group">
                    <label class="rd-label">Total</label>
                    <input type="text" id="add_total_barang_v2" class="rd-input sm" value="Rp 0" readonly style="background: var(--rd-bg-light); font-weight: 600;">
                </div>
            </div>
            <div class="rd-flex-end">
                <button type="button" class="rd-btn success" onclick="tambahItemBarangV2()">
                    <i class="fa fa-plus"></i> Tambah ke Daftar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Items List -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-list"></i> Daftar Item Barang</h5>
        <span class="rd-badge primary"><?= $count_barang ?> Item</span>
    </div>
    <div class="rd-card-body" style="padding: 0;">
        <?php if($count_barang > 0): ?>
        <div class="rd-table-wrapper" style="border: none; border-radius: 0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="12%">Kode</th>
                        <th>Nama Barang</th>
                        <th width="8%" class="text-center">Qty</th>
                        <th width="12%" class="text-right">Harga</th>
                        <th width="8%" class="text-center">Disc</th>
                        <th width="12%" class="text-right">Total</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 0;
                    foreach($items_barang as $item):
                        $no++;
                    ?>
                    <tr>
                        <td class="text-center"><?= $no ?></td>
                        <td><code><?= htmlspecialchars($item['no_item']) ?></code></td>
                        <td>
                            <strong><?= htmlspecialchars($item['nama_display']) ?></strong>
                            <br><small class="rd-text-muted"><?= $item['satuan_display'] ?></small>
                        </td>
                        <td class="text-center"><?= $item['quantity'] ?></td>
                        <td class="text-right">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                        <td class="text-center">
                            <?php if($item['diskon'] > 0): ?>
                            <span class="rd-badge warning"><?= $item['diskon'] ?>%</span>
                            <?php else: ?>
                            <span class="rd-text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right"><strong>Rp <?= number_format($item['total'], 0, ',', '.') ?></strong></td>
                        <td class="text-center">
                            <div class="rd-btn-group">
                                <button type="button" class="rd-btn xs outline-primary icon-only" onclick="editItemBarang(<?= $item['id'] ?>)" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="rd-btn xs outline-danger icon-only" onclick="hapusItemBarang(<?= $item['id'] ?>, '<?= addslashes($item['nama_display']) ?>')" title="Hapus">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6" class="text-right"><strong>TOTAL BARANG</strong></td>
                        <td class="text-right">
                            <strong style="font-size: 16px; color: var(--rd-primary);">
                                Rp <?= number_format($total_barang, 0, ',', '.') ?>
                            </strong>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="rd-empty-state">
            <i class="fa fa-box-open"></i>
            <h6>Belum Ada Item Barang</h6>
            <p>Gunakan form di atas untuk menambahkan item barang</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Calculate total barang
function hitungTotalBarangV2() {
    var harga = parseFloat($('#add_harga_barang_v2').val()) || 0;
    var qty = parseInt($('#add_qty_barang_v2').val()) || 1;
    var disc = parseFloat($('#add_disc_barang_v2').val()) || 0;

    var subtotal = harga * qty;
    var diskon = subtotal * (disc / 100);
    var total = subtotal - diskon;

    $('#add_total_barang_v2').val('Rp ' + total.toLocaleString('id-ID'));
}

// Event listeners
$('#add_harga_barang_v2, #add_qty_barang_v2, #add_disc_barang_v2').on('input change', hitungTotalBarangV2);

// Search barang
function searchBarangV2() {
    var keyword = $('#search_barang_v2').val().trim();
    if(!keyword || keyword.length < 2) {
        alert('Minimal 2 karakter untuk pencarian');
        return;
    }

    $('#search_results_barang_v2').show();
    $('#search_results_tbody_barang').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Mencari...</td></tr>');

    $.ajax({
        url: 'servis-add-item-cari.php',
        type: 'GET',
        data: { q: keyword, format: 'json' },
        dataType: 'json',
        success: function(response) {
            if(response && response.length > 0) {
                var html = '';
                response.forEach(function(item) {
                    var stokClass = item.stok > 0 ? 'success' : 'danger';
                    html += '<tr style="cursor: pointer;" onclick="pilihBarangV2(\'' + item.kode + '\', \'' + escapeHtml(item.nama) + '\', ' + item.harga + ', ' + item.stok + ')">';
                    html += '<td><code>' + item.kode + '</code></td>';
                    html += '<td>' + item.nama + '</td>';
                    html += '<td class="text-center"><span class="rd-badge ' + stokClass + '">' + item.stok + '</span></td>';
                    html += '<td class="text-right">Rp ' + parseInt(item.harga).toLocaleString('id-ID') + '</td>';
                    html += '<td class="text-center"><button type="button" class="rd-btn xs success icon-only"><i class="fa fa-plus"></i></button></td>';
                    html += '</tr>';
                });
                $('#search_results_tbody_barang').html(html);
            } else {
                $('#search_results_tbody_barang').html('<tr><td colspan="5" class="text-center rd-text-muted">Tidak ditemukan</td></tr>');
            }
        },
        error: function() {
            $('#search_results_tbody_barang').html('<tr><td colspan="5" class="text-center rd-text-danger">Error pencarian</td></tr>');
        }
    });
}

// Select barang from search
function pilihBarangV2(kode, nama, harga, stok) {
    $('#add_kode_barang_v2').val(kode);
    $('#add_nama_barang_v2').val(nama);
    $('#add_harga_barang_v2').val(harga);
    $('#add_qty_barang_v2').val(1).attr('max', stok > 0 ? stok : 999);
    $('#search_results_barang_v2').hide();
    hitungTotalBarangV2();

    // Highlight
    $('#add_kode_barang_v2, #add_nama_barang_v2').addClass('rd-highlight');
    setTimeout(function() {
        $('#add_kode_barang_v2, #add_nama_barang_v2').removeClass('rd-highlight');
    }, 1000);
}

// Add item
function tambahItemBarangV2() {
    var kode = $('#add_kode_barang_v2').val();
    var nama = $('#add_nama_barang_v2').val();
    var harga = $('#add_harga_barang_v2').val();
    var qty = $('#add_qty_barang_v2').val();

    if(!kode || !nama) {
        alert('Pilih barang terlebih dahulu!');
        return;
    }

    if(!harga || harga <= 0) {
        alert('Harga harus diisi!');
        return;
    }

    // Submit via form
    var form = $('<form method="POST">' +
        '<input type="hidden" name="txtnosrv" value="<?= $no_service ?>">' +
        '<input type="hidden" name="btnsimpanbrg" value="1">' +
        '<input type="hidden" name="kd_brg" value="' + kode + '">' +
        '<input type="hidden" name="nm_brg" value="' + escapeHtml(nama) + '">' +
        '<input type="hidden" name="hrg_brg" value="' + harga + '">' +
        '<input type="hidden" name="qty_brg" value="' + qty + '">' +
        '<input type="hidden" name="disc_brg" value="' + ($('#add_disc_barang_v2').val() || 0) + '">' +
        '</form>');
    $('body').append(form);
    form.submit();
}

// Edit & Delete
function editItemBarang(id) {
    alert('Edit item ID: ' + id + '\n(Fitur dalam pengembangan)');
}

function hapusItemBarang(id, nama) {
    if(confirm('Hapus item "' + nama + '" dari daftar?')) {
        window.location = '?snoserv=<?= $no_service ?>&hapus_brg=' + id + '&tab=items';
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Enter key search
$('#search_barang_v2').on('keypress', function(e) {
    if(e.which === 13) {
        e.preventDefault();
        searchBarangV2();
    }
});
</script>
