<?php
/**
 * REDESIGN: Tab Item Jasa
 * Clean & Focused UI v2.0
 */

// Get jasa data
// Note: Jasa items are stored in tblitem with jenis='SERVIS', no separate tbljasa table
// tblservis_jasa.no_item maps to tblitem.noitem
$sql_jasa = mysqli_query($koneksi, "SELECT sj.*,
    COALESCE(i.namaitem, sj.no_item) as nama_display
    FROM tblservis_jasa sj
    LEFT JOIN tblitem i ON sj.no_item = i.noitem
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
                <button type="button" class="rd-btn outline-purple" onclick="openSearchJasaPopup()" title="Buka halaman pencarian lengkap">
                    <i class="fa fa-external-link"></i>
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
                    <label class="rd-label">Waktu (mnt)</label>
                    <input type="number" id="add_waktu_jasa_v2" name="waktu_jasa" class="rd-input sm" value="0" min="0">
                </div>
                <div class="rd-form-group" style="flex: 0 0 80px;">
                    <label class="rd-label">Disc %</label>
                    <input type="number" id="add_disc_jasa_v2" name="diskon_jasa" class="rd-input sm" value="0" min="0" max="100">
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

<!-- Modal Edit Jasa -->
<div class="modal fade" id="modalEditJasa" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h5 class="modal-title"><i class="fa fa-edit"></i> Edit Item Jasa</h5>
            </div>
            <form method="POST">
                <input type="hidden" name="active_tab" value="jasa">
                <input type="hidden" name="updated_tab" value="jasa">
                <div class="modal-body">
                    <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">
                    <input type="hidden" name="btnupdatesrv" value="1">
                    <input type="hidden" id="edit_id_jasa" name="id_detail">
                    
                    <div class="form-group">
                        <label>Nama Jasa</label>
                        <input type="text" id="edit_nama_jasa" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Harga Jasa</label>
                        <input type="text" id="edit_harga_jasa" name="hrg_srv" class="form-control currency-format">
                    </div>
                    <div class="row">
                        <div class="col-xs-6">
                            <div class="form-group">
                                <label>Waktu</label>
                                <input type="number" id="edit_waktu_jasa" name="waktu_srv" class="form-control" min="0">
                            </div>
                        </div>
                         <div class="col-xs-6">
                             <div class="form-group">
                                <label>Disc %</label>
                                <input type="number" id="edit_disc_jasa" name="disc_srv" class="form-control" min="0" max="100">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-save"></i> Simpan</button>
                </div>
            </form>
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
                        <th width="8%" class="text-center">Disc</th>
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
                            <code><?= htmlspecialchars($item['no_item']) ?></code>
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
                        <td class="text-center">
                            <?php 
                            // Determine display based on source
                            $d_source = $item['diskon_source'] ?? 'none';
                            $d_persen = $item['diskon_persen'] ?? 0;
                            $d_nominal = $item['diskon_nominal'] ?? 0;
                            
                            // Check for legacy/manual 'potongan' if source is none
                            if ($d_source == 'none' && isset($item['potongan']) && $item['potongan'] > 0) {
                                $d_source = 'manual';
                                $d_persen = $item['potongan'];
                            }
                            
                            if ($d_source == 'promo') {
                                $val_disc = ($d_persen > 0) ? $d_persen.'%' : 'Rp'.number_format($d_nominal,0,',','.');
                                echo '<span class="rd-badge success">PROMO</span><br>';
                                echo '<small>'.$val_disc.'</small>';
                            } elseif ($d_source == 'member') {
                                echo '<span class="rd-badge info">MEMBER</span><br>';
                                echo '<small>'.$d_persen.'%</small>';
                            } elseif ($d_persen > 0) {
                                echo '<span class="rd-badge warning">'.$d_persen.'%</span>';
                            } else {
                                echo '<span class="rd-text-muted">-</span>';
                            }
                            ?>
                        </td>
                        <td class="text-right"><strong>Rp <?= number_format($item['total'], 0, ',', '.') ?></strong></td>
                        <td class="text-center">
                            <div class="rd-btn-group">
                                <button type="button" class="rd-btn xs outline-primary icon-only" 
                                    onclick="editItemJasa(<?= $item['id'] ?>, '<?= addslashes($item['nama_display']) ?>', '<?= $item['harga'] ?>', '<?= $item['waktu'] ?? 0 ?>', '<?= $item['diskon_persen'] ?? 0 ?>')"
                                    title="Edit">
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
    var disc = parseFloat($('#add_disc_jasa_v2').val()) || 0;

    var diskon = harga * (disc / 100);
    var total = harga - diskon;

    $('#add_total_jasa_v2').val('Rp ' + total.toLocaleString('id-ID'));
}

// Event listeners (defer until jQuery is available)
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        jQuery(function($){
            $('#add_harga_jasa_v2, #add_disc_jasa_v2').on('input change', hitungTotalJasaV2);
            // Enter key search
            $('#search_jasa_v2').on('keypress', function(e) {
                if(e.which === 13) {
                    e.preventDefault();
                    searchJasaV2();
                }
            });
        });
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

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
        url: '_ajax/ajax-search-jasa.php',
        type: 'GET',
        data: { q: keyword, no_service: '<?= $no_service ?>' },
        dataType: 'json',
        success: function(response) {
            if(response && response.length > 0) {
                var html = '';
                response.forEach(function(item) {
                    var applicable = parseInt(item.applicable || 0) === 1;
                    var trStyle = applicable ? 'cursor: pointer;' : 'cursor: not-allowed; opacity: 0.55;';
                    html += '<tr style="' + trStyle + '"' + (applicable ? (' onclick="pilihJasaV2(\'' + item.kode + '\', \'' + escapeHtml(item.nama) + '\', ' + item.harga + ', ' + (item.waktu || 0) + ')"') : '') + '>';
                    html += '<td><code>' + item.kode + '</code></td>';
                    html += '<td>' + escapeHtml(item.nama) + (applicable ? '' : '<br><small class="rd-text-muted">Tidak sesuai kategori motor</small>') + '</td>';
                    html += '<td class="text-center">' + (item.waktu ? item.waktu + ' mnt' : '-') + '</td>';
                    html += '<td class="text-right">Rp ' + parseInt(item.harga).toLocaleString('id-ID') + '</td>';
                    html += '<td class="text-center"><button type="button" class="rd-btn xs purple icon-only"' + (applicable ? '' : ' disabled title="Tidak sesuai kategori motor"') + '><i class="fa ' + (applicable ? 'fa-plus' : 'fa-ban') + '"></i></button></td>';
                    html += '</tr>';
                });
                $('#search_results_tbody_jasa').html(html);
            } else {
                $('#search_results_tbody_jasa').html('<tr><td colspan="5" class="text-center rd-text-muted">Tidak ditemukan</td></tr>');
            }
        },
        error: function(xhr, status, error) {
            console.log('Search error:', error);
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
    var waktu = $('#add_waktu_jasa_v2').val();

    if(!kode || !nama) {
        alert('Pilih jasa terlebih dahulu!');
        return;
    }

    if(!harga || harga <= 0) {
        alert('Harga harus diisi!');
        return;
    }

    // Submit via form - using btnaddsrv handler with correct field names
    var form = $('<form method="POST">' +
        '<input type="hidden" name="txtnosrv" value="<?= $no_service ?>">' +
        '<input type="hidden" name="btnaddsrv" value="1">' +
        '<input type="hidden" name="txtcarisrv">' +
        '<input type="hidden" name="txtpotsrv">' +
        '<input type="hidden" name="keterangan_jasa" value="">' +
        '<input type="hidden" name="tab" value="jasa">' +
        '</form>');
    form.find('[name="txtcarisrv"]').val(kode);
    form.find('[name="txtpotsrv"]').val($('#add_disc_jasa_v2').val() || 0);
    $('body').append(form);
    form.submit();
}

// Edit & Delete
// Edit & Delete
function editItemJasa(id, nama, harga, waktu, disc) {
    $('#edit_id_jasa').val(id);
    $('#edit_nama_jasa').val(nama);
    $('#edit_harga_jasa').val(harga);
    $('#edit_waktu_jasa').val(waktu);
    $('#edit_disc_jasa').val(disc);
    $('#modalEditJasa').modal('show');
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

// Open search jasa popup (full page servis-add-jasa-cari.php)
function openSearchJasaPopup() {
    var keyword = $('#search_jasa_v2').val().trim();
    var url = 'servis-add-jasa-cari.php?snoserv=<?= $no_service ?>&_key=' + encodeURIComponent(keyword) + '&_cari=&_urut=kode_wo&_flt=asc&_tab=jasa&_from=reguler';
    window.location.href = url;
}
</script>
