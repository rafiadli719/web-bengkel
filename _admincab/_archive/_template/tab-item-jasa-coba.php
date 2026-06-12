<?php
/**
 * REDESIGN: Tab Item Jasa
 * Clean & Focused UI v2.0
 */

// Get jasa data
$sql_jasa = mysqli_query($koneksi, "SELECT sj.*,
    COALESCE(j.nama_jasa, sj.nama_jasa) as nama_display
    FROM tblservis_jasa sj
    LEFT JOIN tbljasa j ON sj.kode_jasa = j.kode_jasa
    WHERE sj.no_service = '$no_service'
    ORDER BY sj.id ASC");

$total_jasa = 0;
$total_waktu = 0;
$count_jasa = 0;
$items_jasa = [];
if($sql_jasa) {
    while($row = mysqli_fetch_array($sql_jasa)) {
        $items_jasa[] = $row;
        $total_jasa += $row['total'];
        $total_waktu += $row['waktu'] ?? 0;
        $count_jasa++;
    }
}
?>

<!-- Summary Stats -->
<div class="rd-stats-row">
    <div class="rd-stat-box">
        <div class="icon purple"><i class="fa fa-cogs"></i></div>
        <div class="value"><?= $count_jasa ?></div>
        <div class="label">Total Jasa</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon success"><i class="fa fa-money-bill"></i></div>
        <div class="value">Rp <?= number_format($total_jasa, 0, ',', '.') ?></div>
        <div class="label">Total Biaya</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon info"><i class="fa fa-clock"></i></div>
        <div class="value"><?= $total_waktu ?> menit</div>
        <div class="label">Estimasi Waktu</div>
    </div>
</div>

<!-- Add Jasa Section -->
<div class="rd-card purple">
    <div class="rd-card-header collapsible" onclick="toggleCardBody(this)">
        <h5><i class="fa fa-plus-circle"></i> Tambah Item Jasa</h5>
        <i class="fa fa-chevron-down rd-collapse-icon"></i>
    </div>
    <div class="rd-card-body">
        <!-- Quick Actions - Work Order -->
        <div class="rd-alert info" style="margin-bottom: 16px;">
            <i class="fa fa-lightbulb"></i>
            <div>
                <strong>Tips:</strong> Gunakan Work Order untuk menambahkan paket jasa yang sudah ditentukan, atau tambahkan jasa manual di bawah.
            </div>
        </div>

        <!-- Search Input -->
        <div class="rd-form-group">
            <label class="rd-label">Cari Jasa</label>
            <div class="rd-input-group">
                <input type="text" id="search_jasa_v2" class="rd-input" placeholder="Ketik kode atau nama jasa...">
                <button type="button" class="rd-btn purple" onclick="searchJasaV2()">
                    <i class="fa fa-search"></i> Cari
                </button>
            </div>
        </div>

        <!-- Search Results -->
        <div id="search_results_jasa_v2" style="display: none; margin-top: 16px;">
            <div class="rd-table-wrapper" style="max-height: 250px; overflow-y: auto;">
                <table class="rd-table">
                    <thead>
                        <tr>
                            <th width="15%">Kode</th>
                            <th>Nama Jasa</th>
                            <th width="12%" class="text-center">Waktu</th>
                            <th width="15%" class="text-right">Harga</th>
                            <th width="10%" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="search_results_tbody_jasa">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rd-divider"></div>

        <!-- Manual Input Form -->
        <div id="form_add_jasa_v2">
            <div class="rd-form-row">
                <div class="rd-form-group" style="flex: 0 0 150px;">
                    <label class="rd-label rd-required">Kode</label>
                    <input type="text" id="add_kode_jasa_v2" name="kode_jasa" class="rd-input sm" placeholder="Kode" readonly style="background: var(--rd-bg-light);">
                </div>
                <div class="rd-form-group">
                    <label class="rd-label rd-required">Nama Jasa</label>
                    <input type="text" id="add_nama_jasa_v2" name="nama_jasa" class="rd-input sm" placeholder="Nama jasa" readonly style="background: var(--rd-bg-light);">
                </div>
            </div>
            <div class="rd-form-row">
                <div class="rd-form-group">
                    <label class="rd-label rd-required">Harga</label>
                    <input type="number" id="add_harga_jasa_v2" name="harga_jasa" class="rd-input sm" placeholder="0" min="0">
                </div>
                <div class="rd-form-group" style="flex: 0 0 100px;">
                    <label class="rd-label rd-required">Qty</label>
                    <input type="number" id="add_qty_jasa_v2" name="qty_jasa" class="rd-input sm" value="1" min="1">
                </div>
                <div class="rd-form-group" style="flex: 0 0 100px;">
                    <label class="rd-label">Waktu (mnt)</label>
                    <input type="number" id="add_waktu_jasa_v2" name="waktu_jasa" class="rd-input sm" value="0" min="0">
                </div>
                <div class="rd-form-group">
                    <label class="rd-label">Total</label>
                    <input type="text" id="add_total_jasa_v2" class="rd-input sm" value="Rp 0" readonly style="background: var(--rd-bg-light); font-weight: 600;">
                </div>
            </div>
            <div class="rd-flex-end">
                <button type="button" class="rd-btn success" onclick="tambahItemJasaV2()">
                    <i class="fa fa-plus"></i> Tambah ke Daftar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Jasa List -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-list"></i> Daftar Item Jasa</h5>
        <span class="rd-badge purple"><?= $count_jasa ?> Jasa</span>
    </div>
    <div class="rd-card-body" style="padding: 0;">
        <?php if($count_jasa > 0): ?>
        <div class="rd-table-wrapper" style="border: none; border-radius: 0;">
            <table class="rd-table">
                <thead>
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="12%">Kode</th>
                        <th>Nama Jasa</th>
                        <th width="8%" class="text-center">Qty</th>
                        <th width="10%" class="text-center">Waktu</th>
                        <th width="12%" class="text-right">Harga</th>
                        <th width="12%" class="text-right">Total</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 0;
                    foreach($items_jasa as $item):
                        $no++;
                        $from_wo = !empty($item['wo_id']) || !empty($item['kode_wo']);
                    ?>
                    <tr>
                        <td class="text-center"><?= $no ?></td>
                        <td>
                            <code><?= htmlspecialchars($item['kode_jasa']) ?></code>
                            <?php if($from_wo): ?>
                            <br><span class="rd-badge info" style="font-size: 9px;">WO</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($item['nama_display']) ?></strong>
                            <?php if(!empty($item['keterangan'])): ?>
                            <br><small class="rd-text-muted"><?= htmlspecialchars($item['keterangan']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center"><?= $item['quantity'] ?? 1 ?></td>
                        <td class="text-center">
                            <?php if($item['waktu'] > 0): ?>
                            <span class="rd-badge info"><?= $item['waktu'] ?> mnt</span>
                            <?php else: ?>
                            <span class="rd-text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-right">Rp <?= number_format($item['harga'], 0, ',', '.') ?></td>
                        <td class="text-right"><strong>Rp <?= number_format($item['total'], 0, ',', '.') ?></strong></td>
                        <td class="text-center">
                            <div class="rd-btn-group">
                                <button type="button" class="rd-btn xs outline-primary icon-only" onclick="editItemJasa(<?= $item['id'] ?>)" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </button>
                                <button type="button" class="rd-btn xs outline-danger icon-only" onclick="hapusItemJasa(<?= $item['id'] ?>, '<?= addslashes($item['nama_display']) ?>')" title="Hapus">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                        <td class="text-center"><span class="rd-badge info"><?= $total_waktu ?> mnt</span></td>
                        <td></td>
                        <td class="text-right">
                            <strong style="font-size: 16px; color: var(--rd-purple);">
                                Rp <?= number_format($total_jasa, 0, ',', '.') ?>
                            </strong>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="rd-empty-state">
            <i class="fa fa-wrench"></i>
            <h6>Belum Ada Item Jasa</h6>
            <p>Gunakan form di atas atau Work Order untuk menambahkan jasa</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Calculate total jasa
function hitungTotalJasaV2() {
    var harga = parseFloat($('#add_harga_jasa_v2').val()) || 0;
    var qty = parseInt($('#add_qty_jasa_v2').val()) || 1;
    var total = harga * qty;

    $('#add_total_jasa_v2').val('Rp ' + total.toLocaleString('id-ID'));
}

// Event listeners
$('#add_harga_jasa_v2, #add_qty_jasa_v2').on('input change', hitungTotalJasaV2);

// Search jasa
function searchJasaV2() {
    var keyword = $('#search_jasa_v2').val().trim();
    if(!keyword || keyword.length < 2) {
        alert('Minimal 2 karakter untuk pencarian');
        return;
    }

    $('#search_results_jasa_v2').show();
    $('#search_results_tbody_jasa').html('<tr><td colspan="5" class="text-center"><i class="fa fa-spinner fa-spin"></i> Mencari...</td></tr>');

    $.ajax({
        url: 'servis-add-jasa-cari.php',
        type: 'GET',
        data: { q: keyword, format: 'json' },
        dataType: 'json',
        success: function(response) {
            if(response && response.length > 0) {
                var html = '';
                response.forEach(function(item) {
                    html += '<tr style="cursor: pointer;" onclick="pilihJasaV2(\'' + item.kode + '\', \'' + escapeHtml(item.nama) + '\', ' + item.harga + ', ' + (item.waktu || 0) + ')">';
                    html += '<td><code>' + item.kode + '</code></td>';
                    html += '<td>' + item.nama + '</td>';
                    html += '<td class="text-center">' + (item.waktu ? item.waktu + ' mnt' : '-') + '</td>';
                    html += '<td class="text-right">Rp ' + parseInt(item.harga).toLocaleString('id-ID') + '</td>';
                    html += '<td class="text-center"><button type="button" class="rd-btn xs purple icon-only"><i class="fa fa-plus"></i></button></td>';
                    html += '</tr>';
                });
                $('#search_results_tbody_jasa').html(html);
            } else {
                $('#search_results_tbody_jasa').html('<tr><td colspan="5" class="text-center rd-text-muted">Tidak ditemukan</td></tr>');
            }
        },
        error: function() {
            $('#search_results_tbody_jasa').html('<tr><td colspan="5" class="text-center rd-text-danger">Error pencarian</td></tr>');
        }
    });
}

// Select jasa from search
function pilihJasaV2(kode, nama, harga, waktu) {
    $('#add_kode_jasa_v2').val(kode);
    $('#add_nama_jasa_v2').val(nama);
    $('#add_harga_jasa_v2').val(harga);
    $('#add_waktu_jasa_v2').val(waktu);
    $('#add_qty_jasa_v2').val(1);
    $('#search_results_jasa_v2').hide();
    hitungTotalJasaV2();

    // Highlight
    $('#add_kode_jasa_v2, #add_nama_jasa_v2').addClass('rd-highlight');
    setTimeout(function() {
        $('#add_kode_jasa_v2, #add_nama_jasa_v2').removeClass('rd-highlight');
    }, 1000);
}

// Add jasa
function tambahItemJasaV2() {
    var kode = $('#add_kode_jasa_v2').val();
    var nama = $('#add_nama_jasa_v2').val();
    var harga = $('#add_harga_jasa_v2').val();
    var qty = $('#add_qty_jasa_v2').val();
    var waktu = $('#add_waktu_jasa_v2').val();

    if(!kode || !nama) {
        alert('Pilih jasa terlebih dahulu!');
        return;
    }

    if(!harga || harga <= 0) {
        alert('Harga harus diisi!');
        return;
    }

    // Submit via form
    var form = $('<form method="POST">' +
        '<input type="hidden" name="txtnosrv" value="<?= $no_service ?>">' +
        '<input type="hidden" name="btnsimpansrv" value="1">' +
        '<input type="hidden" name="kd_srv" value="' + kode + '">' +
        '<input type="hidden" name="nm_srv" value="' + escapeHtml(nama) + '">' +
        '<input type="hidden" name="hrg_srv" value="' + harga + '">' +
        '<input type="hidden" name="qty_srv" value="' + qty + '">' +
        '<input type="hidden" name="waktu_srv" value="' + waktu + '">' +
        '</form>');
    $('body').append(form);
    form.submit();
}

// Edit & Delete
function editItemJasa(id) {
    alert('Edit jasa ID: ' + id + '\n(Fitur dalam pengembangan)');
}

function hapusItemJasa(id, nama) {
    if(confirm('Hapus jasa "' + nama + '" dari daftar?')) {
        window.location = '?snoserv=<?= $no_service ?>&hapus_srv=' + id + '&tab=jasa';
    }
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Enter key search
$('#search_jasa_v2').on('keypress', function(e) {
    if(e.which === 13) {
        e.preventDefault();
        searchJasaV2();
    }
});
</script>
