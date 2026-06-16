<?php
/**
 * REDESIGN: Modal Fast Moves
 * Clean & Focused UI v2.0
 *
 * Modal untuk quick add item yang sering dipakai
 */

// Get fast moves data from database
$fastmoves = [];
$sql_fm = mysqli_query($koneksi, "SELECT fm.*, b.nama_barang, b.harga_jual
    FROM tbfast_moves fm
    LEFT JOIN tblbarang b ON fm.kode_barang = b.kode_barang
    WHERE fm.status = 'aktif'
    ORDER BY fm.urutan ASC, fm.nama_fast_move ASC
    LIMIT 20");

if($sql_fm) {
    while($row = mysqli_fetch_array($sql_fm)) {
        $fastmoves[] = $row;
    }
}

// Group by category
$categories = [];
foreach($fastmoves as $fm) {
    $cat = $fm['kategori'] ?? 'Lainnya';
    if(!isset($categories[$cat])) {
        $categories[$cat] = [];
    }
    $categories[$cat][] = $fm;
}
?>

<!-- Modal Fast Moves -->
<div class="modal fade rd-modal" id="modalFastMoves" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header primary">
                <h5 class="modal-title">
                    <i class="fa fa-bolt"></i> Fast Moves - Quick Add
                </h5>
                <button type="button" class="close" data-dismiss="modal" style="color: white; opacity: 1;">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Search Bar -->
                <div class="rd-form-group" style="margin-bottom: 20px;">
                    <div class="rd-input-group">
                        <span class="rd-input-addon"><i class="fa fa-search"></i></span>
                        <input type="text" id="searchFastMoves" class="rd-input"
                               placeholder="Cari item fast moves..." onkeyup="filterFastMoves()">
                    </div>
                </div>

                <!-- Fast Moves Grid -->
                <div class="fm-container" id="fmContainer">
                    <?php if(!empty($categories)): ?>
                        <?php foreach($categories as $catName => $items): ?>
                        <div class="fm-category">
                            <h6 class="fm-category-title">
                                <i class="fa fa-folder"></i> <?= htmlspecialchars($catName) ?>
                            </h6>
                            <div class="fm-grid">
                                <?php foreach($items as $item): ?>
                                <div class="fm-item" onclick="selectFastMove('<?= $item['kode_barang'] ?>', '<?= addslashes($item['nama_barang'] ?? $item['nama_fast_move']) ?>', <?= $item['harga_jual'] ?? 0 ?>)">
                                    <div class="fm-icon">
                                        <?php
                                        // Icon based on category
                                        $icon = 'fa-box';
                                        if(stripos($catName, 'oli') !== false) $icon = 'fa-oil-can';
                                        elseif(stripos($catName, 'ban') !== false) $icon = 'fa-circle';
                                        elseif(stripos($catName, 'aki') !== false) $icon = 'fa-battery-full';
                                        elseif(stripos($catName, 'filter') !== false) $icon = 'fa-filter';
                                        elseif(stripos($catName, 'lampu') !== false) $icon = 'fa-lightbulb';
                                        elseif(stripos($catName, 'rem') !== false) $icon = 'fa-stop-circle';
                                        ?>
                                        <i class="fa <?= $icon ?>"></i>
                                    </div>
                                    <div class="fm-info">
                                        <div class="fm-name"><?= htmlspecialchars($item['nama_barang'] ?? $item['nama_fast_move']) ?></div>
                                        <div class="fm-price">Rp <?= number_format($item['harga_jual'] ?? 0, 0, ',', '.') ?></div>
                                    </div>
                                    <div class="fm-add">
                                        <i class="fa fa-plus-circle"></i>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="rd-empty-state">
                            <i class="fa fa-bolt"></i>
                            <h6>Belum Ada Fast Moves</h6>
                            <p>Tambahkan item yang sering dipakai di Master Fast Moves</p>
                        </div>
                    <?php endif; ?>
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

<style>
/* Fast Moves Styles */
.fm-container {
    max-height: 400px;
    overflow-y: auto;
}

.fm-category {
    margin-bottom: 20px;
}

.fm-category-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--rd-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0 0 12px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--rd-border);
    display: flex;
    align-items: center;
    gap: 8px;
}

.fm-category-title i {
    color: var(--rd-primary);
}

.fm-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.fm-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: var(--rd-bg-white);
    border: 1px solid var(--rd-border);
    border-radius: var(--rd-radius-md);
    cursor: pointer;
    transition: all 0.2s ease;
}

.fm-item:hover {
    border-color: var(--rd-primary);
    background: var(--rd-primary-light);
    transform: translateY(-2px);
    box-shadow: var(--rd-shadow-sm);
}

.fm-icon {
    width: 40px;
    height: 40px;
    background: var(--rd-primary-light);
    color: var(--rd-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
}

.fm-item:hover .fm-icon {
    background: var(--rd-primary);
    color: white;
}

.fm-info {
    flex: 1;
    min-width: 0;
}

.fm-name {
    font-size: 13px;
    font-weight: 500;
    color: var(--rd-text-dark);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fm-price {
    font-size: 12px;
    color: var(--rd-text-muted);
    margin-top: 2px;
}

.fm-add {
    color: var(--rd-primary);
    font-size: 20px;
    opacity: 0;
    transition: opacity 0.2s;
}

.fm-item:hover .fm-add {
    opacity: 1;
}

/* Hidden class for filtering */
.fm-item.hidden {
    display: none;
}
</style>

<script>
// Filter fast moves
function filterFastMoves() {
    var keyword = $('#searchFastMoves').val().toLowerCase();
    var items = document.querySelectorAll('.fm-item');

    items.forEach(function(item) {
        var name = item.querySelector('.fm-name').textContent.toLowerCase();
        if(name.indexOf(keyword) !== -1) {
            item.classList.remove('hidden');
        } else {
            item.classList.add('hidden');
        }
    });

    // Show/hide categories with no visible items
    document.querySelectorAll('.fm-category').forEach(function(cat) {
        var visibleItems = cat.querySelectorAll('.fm-item:not(.hidden)');
        cat.style.display = visibleItems.length > 0 ? 'block' : 'none';
    });
}

// Select fast move item
function selectFastMove(kode, nama, harga) {
    // Fill the barang form (v2 version)
    if($('#add_kode_barang_v2').length) {
        $('#add_kode_barang_v2').val(kode);
        $('#add_nama_barang_v2').val(nama);
        $('#add_harga_barang_v2').val(harga);
        $('#add_qty_barang_v2').val(1);

        // Calculate total
        if(typeof hitungTotalBarangV2 === 'function') {
            hitungTotalBarangV2();
        }
    }

    // Close modal
    $('#modalFastMoves').modal('hide');

    // Show notification
    showNotifV2('Fast Move "' + nama + '" dipilih', 'success');
}

// Simple notification
function showNotifV2(message, type) {
    var bgColor = type === 'success' ? 'var(--rd-success)' : 'var(--rd-info)';

    var notif = $('<div class="fm-notif">' + message + '</div>');
    notif.css({
        position: 'fixed',
        top: '20px',
        right: '20px',
        padding: '12px 20px',
        background: bgColor,
        color: 'white',
        borderRadius: '8px',
        boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
        zIndex: 9999,
        animation: 'rd-fadeIn 0.3s ease'
    });

    $('body').append(notif);

    setTimeout(function() {
        notif.fadeOut(300, function() {
            notif.remove();
        });
    }, 2000);
}
</script>
