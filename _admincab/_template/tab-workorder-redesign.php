<?php
/**
 * REDESIGN: Tab Work Order
 * Clean & Focused UI v2.0
 */

// Get work order data
$sql_wo = mysqli_query($koneksi, "SELECT sw.*,
    wh.nama_wo, wh.harga as harga_wo, wh.waktu
    FROM tbservis_workorder sw
    LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
    WHERE sw.no_service = '$no_service'
    ORDER BY sw.id DESC");

$items_wo = [];
$count_wo = 0;
$total_waktu_wo = 0;
if($sql_wo) {
    while($row = mysqli_fetch_array($sql_wo)) {
        $items_wo[] = $row;
        $count_wo++;
        $total_waktu_wo += $row['waktu'] ?? 0;
    }
}

// Get keluhan data
$sql_keluhan = mysqli_query($koneksi, "SELECT * FROM tbservis_keluhan_status
    WHERE no_service='$no_service' ORDER BY id ASC");
$items_keluhan = [];
$count_keluhan = 0;
if($sql_keluhan) {
    while($row = mysqli_fetch_array($sql_keluhan)) {
        $items_keluhan[] = $row;
        $count_keluhan++;
    }
}

$total_spk = $count_keluhan + $count_wo;
?>

<!-- Summary Stats -->
<div class="rd-stats-row">
    <div class="rd-stat-box">
        <div class="icon warning"><i class="fa fa-exclamation-circle"></i></div>
        <div class="value"><?= $count_keluhan ?></div>
        <div class="label">Keluhan</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon info"><i class="fa fa-cogs"></i></div>
        <div class="value"><?= $count_wo ?></div>
        <div class="label">Work Order</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon purple"><i class="fa fa-clipboard-list"></i></div>
        <div class="value"><?= $total_spk ?></div>
        <div class="label">Total SPK</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon success"><i class="fa fa-clock"></i></div>
        <div class="value"><?= $total_waktu_wo ?> mnt</div>
        <div class="label">Est. Waktu</div>
    </div>
</div>

<!-- Service Type Selector -->
<div class="rd-card">
    <div class="rd-card-body" style="padding: 16px;">
        <div class="rd-form-row" style="align-items: center;">
            <div class="rd-form-group" style="flex: 0 0 150px; margin-bottom: 0;">
                <label class="rd-label">Jenis Service</label>
            </div>
            <div class="rd-form-group" style="flex: 1; margin-bottom: 0;">
                <div class="rd-radio-group">
                    <label class="rd-radio-option <?= (!isset($status_jemput) || $status_jemput == '0') ? 'active' : '' ?>">
                        <input type="radio" name="status_jemput" value="0" <?= (!isset($status_jemput) || $status_jemput == '0') ? 'checked' : '' ?>>
                        <i class="fa fa-store"></i> Ditinggal
                    </label>
                    <label class="rd-radio-option <?= (isset($status_jemput) && $status_jemput == '1') ? 'active' : '' ?>">
                        <input type="radio" name="status_jemput" value="1" <?= (isset($status_jemput) && $status_jemput == '1') ? 'checked' : '' ?>>
                        <i class="fa fa-truck"></i> Dijemput
                    </label>
                    <label class="rd-radio-option <?= (isset($status_jemput) && $status_jemput == '2') ? 'active' : '' ?>">
                        <input type="radio" name="status_jemput" value="2" <?= (isset($status_jemput) && $status_jemput == '2') ? 'checked' : '' ?>>
                        <i class="fa fa-hourglass-half"></i> Ditunggu
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Input Section - Keluhan -->
<div class="rd-card warning">
    <div class="rd-card-header collapsible" onclick="toggleCardBody(this)">
        <h5><i class="fa fa-comment-alt"></i> Input Keluhan</h5>
        <i class="fa fa-chevron-down rd-collapse-icon"></i>
    </div>
    <div class="rd-card-body">
        <div class="rd-form-row" style="align-items: flex-end;">
            <div class="rd-form-group" style="flex: 1;">
                <label class="rd-label">Keluhan Pelanggan</label>
                <div class="rd-input-group">
                    <input type="hidden" name="kode_keluhan" id="kode_keluhan_v2" />
                    <input type="text" name="txtkeluhan" id="txtkeluhan_v2" class="rd-input"
                           placeholder="Ketik keluhan atau pilih dari master...">
                    <button type="button" class="rd-btn outline-primary" onclick="$('#modal-search-keluhan').modal('show');">
                        <i class="fa fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="rd-form-group" style="flex: 0 0 auto;">
                <button class="rd-btn warning" type="submit" name="btnaddkeluhan" id="btnaddkeluhan_v2" onclick="return validateKeluhanV2()">
                    <i class="fa fa-plus"></i> Tambah Keluhan
                </button>
            </div>
        </div>

        <div style="margin-top: 12px;">
            <button type="button" class="rd-btn sm outline-warning" onclick="openModalTambahKeluhanBaru()">
                <i class="fa fa-plus-circle"></i> Keluhan Baru (Perlu Approval)
            </button>
        </div>
    </div>
</div>

<!-- Input Section - Work Order -->
<div class="rd-card info">
    <div class="rd-card-header collapsible" onclick="toggleCardBody(this)">
        <h5><i class="fa fa-cogs"></i> Input Work Order</h5>
        <i class="fa fa-chevron-down rd-collapse-icon"></i>
    </div>
    <div class="rd-card-body">
        <div class="rd-form-row" style="align-items: flex-end;">
            <div class="rd-form-group" style="flex: 0 0 200px;">
                <label class="rd-label">Kode Work Order</label>
                <input type="text" id="txtcariwo_v2" name="txtcariwo" class="rd-input"
                       placeholder="Kode WO..." value="<?= $txtcariwo ?? '' ?>">
            </div>
            <div class="rd-form-group" style="flex: 1;">
                <label class="rd-label">Nama Work Order</label>
                <input type="text" id="txtnamawo_v2" class="rd-input" value="<?= $txtnamawo ?? '' ?>"
                       readonly style="background: var(--rd-bg-light);">
            </div>
            <div class="rd-form-group" style="flex: 0 0 auto;">
                <button class="rd-btn outline-info" type="submit" name="btncariwo">
                    <i class="fa fa-search"></i> Cari
                </button>
            </div>
            <div class="rd-form-group" style="flex: 0 0 auto;">
                <button class="rd-btn info" type="submit" name="btnaddworkorder" onclick="return validateWorkOrderV2()">
                    <i class="fa fa-plus"></i> Tambah WO
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Daftar Keluhan -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-list"></i> Daftar Keluhan</h5>
        <span class="rd-badge warning"><?= $count_keluhan ?> Keluhan</span>
    </div>
    <div class="rd-card-body" style="padding: 0;">
        <?php if($count_keluhan > 0): ?>
        <div style="display: flex; flex-direction: column;">
            <?php
            $no = 0;
            foreach($items_keluhan as $kel):
                $no++;
                $status = $kel['status_pengerjaan'] ?? 'datang';
                $status_class = $status == 'selesai' ? 'success' : ($status == 'diproses' ? 'warning' : ($status == 'tidak_selesai' ? 'danger' : 'neutral'));
                $status_icon = $status == 'selesai' ? 'check' : ($status == 'diproses' ? 'cog fa-spin' : ($status == 'tidak_selesai' ? 'times' : 'clock'));
            ?>
            <div class="rd-list-item" style="border-left: 4px solid var(--rd-<?= $status_class ?>);">
                <div class="rd-flex rd-gap-12" style="flex: 1;">
                    <span class="rd-badge neutral" style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                        <?= $no ?>
                    </span>
                    <div style="flex: 1;">
                        <div style="font-weight: 500;"><?= htmlspecialchars($kel['keluhan']) ?></div>
                        <?php if($status == 'tidak_selesai' && !empty($kel['keterangan_tidak_selesai'])): ?>
                        <small class="rd-text-danger"><?= htmlspecialchars($kel['keterangan_tidak_selesai']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="rd-flex rd-gap-8" style="align-items: center;">
                    <span class="rd-badge <?= $status_class ?>">
                        <i class="fa fa-<?= $status_icon ?>"></i> <?= ucfirst($status) ?>
                    </span>
                    <button type="button" class="rd-btn xs outline-primary icon-only btn-update-status-keluhan"
                            data-id="<?= $kel['id'] ?>"
                            data-keluhan="<?= htmlspecialchars($kel['keluhan'], ENT_QUOTES) ?>"
                            data-status="<?= $status ?>"
                            data-keterangan="<?= htmlspecialchars($kel['keterangan_tidak_selesai'] ?? '', ENT_QUOTES) ?>"
                            title="Update Status">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" class="rd-btn xs outline-danger icon-only"
                            onclick="hapusKeluhanV2(<?= $kel['id'] ?>)" title="Hapus">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding: 30px;">
            <i class="fa fa-comment-slash"></i>
            <p>Belum ada keluhan</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Daftar Work Order -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-clipboard-list"></i> Daftar Work Order</h5>
        <span class="rd-badge info"><?= $count_wo ?> Work Order</span>
    </div>
    <div class="rd-card-body" style="padding: 0;">
        <?php if($count_wo > 0): ?>
        <div style="display: flex; flex-direction: column;">
            <?php
            $no = 0;
            foreach($items_wo as $wo):
                $no++;
                $status = $wo['status_pengerjaan'] ?? 'diproses';
                $status_class = $status == 'selesai' ? 'success' : ($status == 'diproses' ? 'warning' : 'danger');
                $progress = ($status == 'selesai') ? 100 : (($status == 'diproses') ? 50 : 0);
            ?>
            <div class="rd-list-item" style="border-left: 4px solid var(--rd-<?= $status_class ?>);">
                <div style="flex: 1;">
                    <div class="rd-flex rd-gap-12" style="align-items: flex-start;">
                        <span class="rd-badge info" style="font-family: monospace;"><?= htmlspecialchars($wo['kode_wo']) ?></span>
                        <div style="flex: 1;">
                            <div style="font-weight: 600;"><?= htmlspecialchars($wo['nama_wo'] ?? 'Work Order') ?></div>
                            <div style="margin-top: 8px;">
                                <div class="rd-progress" style="height: 8px; margin-bottom: 4px;">
                                    <div class="rd-progress-bar <?= $status_class ?>" style="width: <?= $progress ?>%;"></div>
                                </div>
                                <small class="rd-text-muted">Progress: <?= $progress ?>%</small>
                                <?php if(($wo['waktu'] ?? 0) > 0): ?>
                                <span class="rd-badge sm neutral" style="margin-left: 8px;">
                                    <i class="fa fa-clock"></i> <?= $wo['waktu'] ?> menit
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rd-flex rd-gap-8" style="align-items: center;">
                    <span class="rd-badge <?= $status_class ?>"><?= strtoupper($status) ?></span>
                    <button type="button" class="rd-btn xs outline-primary icon-only btn-update-status-wo"
                            data-id="<?= $wo['id'] ?>"
                            data-kode="<?= $wo['kode_wo'] ?>"
                            data-nama="<?= htmlspecialchars($wo['nama_wo'] ?? '', ENT_QUOTES) ?>"
                            data-status="<?= $status ?>"
                            data-keterangan="<?= htmlspecialchars($wo['keterangan_tidak_selesai'] ?? '', ENT_QUOTES) ?>"
                            title="Update Status">
                        <i class="fa fa-edit"></i>
                    </button>
                    <button type="button" class="rd-btn xs outline-danger icon-only"
                            onclick="hapusWorkOrderV2(<?= $wo['id'] ?>)" title="Hapus">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding: 30px;">
            <i class="fa fa-clipboard"></i>
            <p>Belum ada work order</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Vehicle History Button -->
<div class="rd-card" style="background: linear-gradient(135deg, var(--rd-purple) 0%, #7B48A8 100%); color: white;">
    <div class="rd-card-body" style="padding: 20px;">
        <div class="rd-flex-between" style="align-items: center;">
            <div>
                <h5 style="color: white; margin: 0 0 4px 0;">
                    <i class="fa fa-history"></i> Riwayat Service Kendaraan
                </h5>
                <p style="margin: 0; opacity: 0.9; font-size: 13px;">
                    Lihat history service dan mekanik untuk kendaraan <?= htmlspecialchars($no_polisi ?? '') ?>
                </p>
            </div>
            <button type="button" class="rd-btn" style="background: white; color: var(--rd-purple);"
                    onclick="$('#modalRiwayatKendaraan').modal('show');">
                Lihat Riwayat <i class="fa fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<style>
/* Radio Group Styles */
.rd-radio-group {
    display: flex;
    gap: 8px;
}

.rd-radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 2px solid var(--rd-border);
    border-radius: var(--rd-radius);
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
    font-weight: 500;
    font-size: 13px;
}

.rd-radio-option:hover {
    border-color: var(--rd-primary);
    background: var(--rd-bg-light);
}

.rd-radio-option.active {
    border-color: var(--rd-primary);
    background: rgba(74, 144, 217, 0.1);
    color: var(--rd-primary);
}

.rd-radio-option input[type="radio"] {
    display: none;
}

.rd-radio-option i {
    font-size: 14px;
}

/* List Item Styles */
.rd-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px;
    background: white;
    border-bottom: 1px solid var(--rd-border);
}

.rd-list-item:last-child {
    border-bottom: none;
}

.rd-list-item:hover {
    background: var(--rd-bg-light);
}

/* Progress Bar */
.rd-progress {
    width: 100%;
    height: 6px;
    background: var(--rd-border);
    border-radius: 10px;
    overflow: hidden;
}

.rd-progress-bar {
    height: 100%;
    border-radius: 10px;
    transition: width 0.3s ease;
}

.rd-progress-bar.success { background: var(--rd-success); }
.rd-progress-bar.warning { background: var(--rd-warning); }
.rd-progress-bar.danger { background: var(--rd-danger); }
.rd-progress-bar.info { background: var(--rd-info); }
</style>

<script>
// Validation functions
function validateKeluhanV2() {
    var kodeKeluhan = $('#kode_keluhan_v2').val() || '';
    var txtKeluhan = $('#txtkeluhan_v2').val() || '';

    if(!kodeKeluhan.trim() && !txtKeluhan.trim()) {
        alert('Masukkan atau pilih keluhan terlebih dahulu!');
        $('#txtkeluhan_v2').focus();
        return false;
    }
    return true;
}

function validateWorkOrderV2() {
    var kodeWO = $('#txtcariwo_v2').val().trim();
    if(!kodeWO) {
        alert('Kode Work Order tidak boleh kosong!');
        $('#txtcariwo_v2').focus();
        return false;
    }
    return true;
}

// Delete functions
function hapusKeluhanV2(id) {
    if(!confirm('Yakin ingin menghapus keluhan ini?')) return;

    $.ajax({
        url: 'keluhan-hapus.php',
        type: 'POST',
        data: { id: id },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                location.reload();
            } else {
                alert('Error: ' + (response.message || 'Gagal menghapus'));
            }
        },
        error: function() {
            alert('Error: Gagal menghapus keluhan');
        }
    });
}

function hapusWorkOrderV2(id) {
    if(!confirm('Yakin ingin menghapus work order ini?')) return;

    var noService = '<?= $no_service ?>';

    $.ajax({
        url: '_ajax/hapus_spk_workorder.php',
        type: 'POST',
        data: { id: id, no_service: noService },
        dataType: 'json',
        success: function(response) {
            if(response.status === 'success') {
                location.reload();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function() {
            alert('Error: Gagal menghapus work order');
        }
    });
}

// Defer jQuery-dependent bindings until jQuery is ready
(function waitForJQ(){
    if (typeof window.jQuery === 'function') {
        var $ = jQuery;

        // Radio button handler
        $('.rd-radio-option input[type="radio"]').on('change', function() {
            $('.rd-radio-option').removeClass('active');
            $(this).closest('.rd-radio-option').addClass('active');
        });

        // Update status handlers (connect to existing modals)
        $(document).on('click', '.btn-update-status-keluhan', function() {
            var id = $(this).data('id');
            var keluhan = $(this).data('keluhan');
            var status = $(this).data('status');
            var keterangan = $(this).data('keterangan');

            $('#keluhan_id').val(id);
            $('#keluhan_text').val(keluhan);
            $('#status_keluhan').val(status);
            $('#keterangan_keluhan').val(keterangan);

            if(status == 'tidak_selesai') {
                $('#keterangan_keluhan_group').show();
            } else {
                $('#keterangan_keluhan_group').hide();
            }

            $('#modalUpdateStatusKeluhan').modal('show');
        });

        $(document).on('click', '.btn-update-status-wo', function() {
            var id = $(this).data('id');
            var kode = $(this).data('kode');
            var nama = $(this).data('nama');
            var status = $(this).data('status');
            var keterangan = $(this).data('keterangan');

            $('#wo_id_status').val(id);
            $('#wo_text').val(kode + ' - ' + nama);
            $('#status_wo_update').val(status);
            $('#keterangan_wo').val(keterangan);

            if(status == 'tidak_selesai') {
                $('#keterangan_wo_group').show();
            } else {
                $('#keterangan_wo_group').hide();
            }

            $('#modalUpdateStatusWO').modal('show');
        });
    } else {
        setTimeout(waitForJQ, 50);
    }
})();

// Modal tambah keluhan baru
function openModalTambahKeluhanBaru() {
    if($('#modal-tambah-keluhan-baru').length) {
        $('#modal-tambah-keluhan-baru').modal('show');
    } else {
        alert('Modal belum tersedia');
    }
}
</script>

<!-- Modal Update Status Workorder -->
<div class="modal fade" id="modalUpdateStatusWO" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: #4A90D9; color: white;">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h5 class="modal-title"><i class="fa fa-edit"></i> Update Status Workorder</h5>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">
                    <input type="hidden" name="update_status_wo" value="1">
                    <input type="hidden" name="wo_id_status" id="wo_id_status">

                    <div class="form-group">
                        <label>Status Pengerjaan</label>
                        <select name="status_wo_update" id="status_wo_update" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="diproses">Diproses</option>
                            <option value="selesai">Selesai</option>
                            <option value="tidak_selesai">Tidak Selesai</option>
                        </select>
                    </div>

                    <div class="form-group" id="keterangan_wo_group" style="display: none;">
                        <label>Keterangan</label>
                        <textarea name="keterangan_wo" id="keterangan_wo" class="form-control" rows="3" placeholder="Keterangan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="btnupdatestatuswo" class="btn btn-primary">
                        <i class="fa fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
