<?php
/**
 * Tab Temuan & Penawaran - Improved Version
 * Version: 2.0
 * Redesigned dengan tampilan lebih modern dan integrasi Fast Moves Part
 */
?>

<style>
/* ===== MODERN CARD DESIGN ===== */
.temuan-modern-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
    border-left: 4px solid #ddd;
}

.temuan-modern-card:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    transform: translateY(-2px);
}

.temuan-modern-card.urgent-kritis {
    border-left-color: #d9534f;
}

.temuan-modern-card.urgent-tinggi {
    border-left-color: #f0ad4e;
}

.temuan-modern-card.urgent-sedang {
    border-left-color: #5bc0de;
}

.temuan-modern-card.urgent-rendah {
    border-left-color: #5cb85c;
}

.temuan-header-modern {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 15px 20px;
    border-bottom: 1px solid #dee2e6;
}

.temuan-body-modern {
    padding: 20px;
}

.temuan-title {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.temuan-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
}

.badge-modern {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-urgent-kritis {
    background: #d9534f;
    color: white;
}

.badge-urgent-tinggi {
    background: #f0ad4e;
    color: white;
}

.badge-urgent-sedang {
    background: #5bc0de;
    color: white;
}

.badge-urgent-rendah {
    background: #5cb85c;
    color: white;
}

.badge-status {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 600;
}

.status-ditemukan { background: #3498db; color: white; }
.status-ditawarkan { background: #f39c12; color: white; }
.status-disetujui { background: #27ae60; color: white; }
.status-ditolak { background: #e74c3c; color: white; }
.status-selesai { background: #1abc9c; color: white; }

/* ===== INFO SECTION ===== */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.info-item {
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border-left: 3px solid #3498db;
}

.info-item i {
    color: #3498db;
    margin-right: 8px;
}

.info-label {
    font-size: 11px;
    color: #7f8c8d;
    text-transform: uppercase;
    margin-bottom: 4px;
    font-weight: 600;
}

.info-value {
    font-size: 14px;
    color: #2c3e50;
    font-weight: 600;
}

/* ===== PENAWARAN SECTION ===== */
.penawaran-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 15px;
}

.penawaran-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.penawaran-table {
    background: white;
    border-radius: 6px;
    overflow: hidden;
}

.penawaran-table table {
    margin: 0;
}

.penawaran-row {
    transition: background 0.2s;
}

.penawaran-row:hover {
    background: #f1f3f5 !important;
}

.penawaran-pending {
    border-left: 4px solid #f39c12;
}

.penawaran-disetujui {
    border-left: 4px solid #27ae60;
    background: #f0fff4;
}

.penawaran-ditolak {
    border-left: 4px solid #e74c3c;
    background: #fff5f5;
}

/* ===== BUTTONS ===== */
.btn-modern {
    border-radius: 6px;
    padding: 8px 16px;
    font-weight: 600;
    transition: all 0.3s;
    border: none;
}

.btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.btn-fast-moves {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-fast-moves:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    color: white;
}

.action-buttons-group {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    padding: 15px 20px;
    background: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

/* ===== FORM INPUT SECTION ===== */
.input-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    overflow: hidden;
}

.input-header {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
}

.input-body {
    padding: 20px;
}

.form-section-title {
    font-size: 14px;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #3498db;
}

/* ===== STATS SUMMARY ===== */
.stats-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    padding: 15px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.stat-number {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #7f8c8d;
    text-transform: uppercase;
}

.stat-pending { color: #f39c12; }
.stat-approved { color: #27ae60; }
.stat-rejected { color: #e74c3c; }
.stat-total { color: #3498db; }

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 8px;
}

.empty-state i {
    font-size: 64px;
    color: #bdc3c7;
    margin-bottom: 20px;
}

.empty-state h4 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.empty-state p {
    color: #7f8c8d;
}
</style>

<!-- SECTION 1: INPUT TEMUAN -->
<div class="input-section">
    <div class="input-header">
        <i class="ace-icon fa fa-search-plus"></i>
        Input Temuan Hasil Pengecekan
    </div>
    <div class="input-body">
        <?php include "_template/_servis_input_temuan.php"; ?>
    </div>
</div>

<!-- SECTION 2: FORM PENAWARAN PART -->
<div class="input-section">
    <div class="input-header" style="background: linear-gradient(135deg, #5cb85c 0%, #449d44 100%);">
        <i class="ace-icon fa fa-shopping-cart"></i>
        Tambah Penawaran Part
        <button type="button" class="btn btn-sm pull-right"
                style="margin-top: -5px; background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white;"
                onclick="toggleFormPenawaran();">
            <i class="ace-icon fa fa-plus"></i> Tampilkan Form
        </button>
    </div>
    <div class="input-body" id="formAddPenawaran" style="display:none;">
        <form id="formTambahPenawaranPart" method="POST">
            <input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">
            <input type="hidden" name="btnaddpenawaran" value="1">

            <div class="form-section-title">
                <i class="fa fa-link"></i> Hubungkan dengan Temuan
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Temuan Terkait <span class="text-muted">(Optional)</span></label>
                        <select name="temuan_id" id="temuan_id_select" class="form-control">
                            <option value="">-- Pilih Temuan (atau biarkan kosong jika tidak terkait) --</option>
                            <?php
                            $q_t = mysqli_query($koneksi, "SELECT id, COALESCE(temuan_custom, (SELECT nama_temuan FROM tbmaster_temuan WHERE kode_temuan = tbservis_temuan.kode_temuan)) AS nama FROM tbservis_temuan WHERE no_service='{$no_service}' ORDER BY id DESC");
                            if ($q_t) {
                                while($t = mysqli_fetch_array($q_t)){
                                    echo "<option value='".$t['id']."'>".htmlspecialchars($t['nama'])."</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section-title">
                <i class="fa fa-cubes"></i> Pilih Part yang Ditawarkan
            </div>

            <div class="row">
                <div class="col-md-12 text-center" style="margin-bottom: 20px;">
                    <button type="button" class="btn btn-modern btn-fast-moves btn-lg"
                            onclick="showModalFastMoves();">
                        <i class="ace-icon fa fa-bolt"></i>
                        <strong>Fast Moves Part</strong> - Pilih Part Cepat
                    </button>

                    <button type="button" class="btn btn-modern btn-custom-item btn-lg"
                            onclick="showModalCustom();"
                            style="margin-left: 10px;">
                        <i class="ace-icon fa fa-cube"></i>
                        <strong>Input Barang Custom</strong>
                    </button>

                    <p class="text-muted" style="margin-top: 10px;">
                        <small>Pilih dari Fast Moves, tambah custom, atau masukkan manual di bawah ini</small>
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Kode Part <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kode_barang_penawaran"
                               name="kode_barang" placeholder="Masukkan kode part" required>
                        <small class="help-block">Akan terisi otomatis dari Fast Moves</small>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="form-group">
                        <label>Nama Part <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_barang_penawaran"
                               name="nama_barang" placeholder="Nama part" required>
                        <small class="help-block">Akan terisi otomatis dari Fast Moves</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control text-center" id="quantity_penawaran"
                               name="quantity" value="1" min="1" required
                               onchange="hitungTotalPenawaran()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Harga Satuan <span class="text-danger">*</span></label>
                        <input type="number" class="form-control text-right" id="harga_satuan_penawaran"
                               name="harga_satuan" placeholder="0" min="0" required
                               onchange="hitungTotalPenawaran()">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Total Harga</label>
                        <input type="text" class="form-control text-right" id="total_penawaran_display"
                               value="Rp 0" readonly style="background: #f0fff4; font-weight: 600; font-size: 16px;">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Catatan Penawaran <span class="text-muted">(Optional)</span></label>
                        <textarea class="form-control" name="catatan_penawaran" rows="2"
                                  placeholder="Catatan tambahan untuk penawaran ini..."></textarea>
                    </div>
                </div>
            </div>

            <div class="text-right">
                <button type="reset" class="btn btn-default btn-modern">
                    <i class="ace-icon fa fa-undo"></i> Reset
                </button>
                <button type="submit" class="btn btn-success btn-modern" name="btnaddpenawaran">
                    <i class="ace-icon fa fa-save"></i> Simpan Penawaran
                </button>
            </div>
        </form>
    </div>
</div>

<!-- SECTION 2.5: PENAWARAN DARI WORKORDER (PENDING ITEMS) -->
<?php
// Query pending items dari workorder
$pending_items_query = "SELECT
                            pi.*,
                            woh.nama_wo,
                            woh.kode_wo
                        FROM tbservis_pending_items pi
                        LEFT JOIN tbservis_workorder swo ON pi.wo_id = swo.id
                        LEFT JOIN tbworkorderheader woh ON swo.kode_wo = woh.kode_wo
                        WHERE pi.no_service='$no_service'
                        AND pi.status_approval='pending'
                        ORDER BY pi.created_at DESC";
$pending_items_result = mysqli_query($koneksi, $pending_items_query);
$total_pending_wo = mysqli_num_rows($pending_items_result);

if ($total_pending_wo > 0) {
?>
<div class="input-section" style="border-left: 4px solid #f39c12;">
    <div class="input-header" style="background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);">
        <i class="ace-icon fa fa-clock-o"></i>
        Penawaran dari Workorder - Menunggu Persetujuan (<?php echo $total_pending_wo; ?> item)
    </div>
    <div class="input-body">
        <div class="alert alert-warning" style="margin-bottom: 15px;">
            <i class="fa fa-info-circle"></i>
            <strong>Penting!</strong> Item berikut dari paket workorder memerlukan persetujuan customer sebelum dikerjakan.
            Silakan approve atau reject masing-masing item.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover">
                <thead class="bg-warning">
                    <tr>
                        <th width="5%" class="text-center">#</th>
                        <th width="15%">Workorder</th>
                        <th width="12%">Kode Item</th>
                        <th width="28%">Nama Item</th>
                        <th width="8%" class="text-center">Tipe</th>
                        <th width="8%" class="text-center">Qty</th>
                        <th width="12%" class="text-right">Harga</th>
                        <th width="12%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $no = 0;
                mysqli_data_seek($pending_items_result, 0); // Reset pointer
                while ($pending = mysqli_fetch_array($pending_items_result)) {
                    $no++;
                    $tipe_badge = $pending['tipe'] == 'jasa' ? 'label-info' : 'label-primary';
                    $tipe_icon = $pending['tipe'] == 'jasa' ? 'fa-wrench' : 'fa-cube';
                ?>
                    <tr>
                        <td class="text-center"><strong><?php echo $no; ?></strong></td>
                        <td>
                            <strong><?php echo htmlspecialchars($pending['nama_wo'] ?: 'Workorder'); ?></strong>
                            <br><small class="text-muted"><?php echo $pending['kode_wo']; ?></small>
                        </td>
                        <td><code><?php echo $pending['kode_item']; ?></code></td>
                        <td><strong><?php echo htmlspecialchars($pending['nama_item']); ?></strong></td>
                        <td class="text-center">
                            <span class="label <?php echo $tipe_badge; ?>">
                                <i class="fa <?php echo $tipe_icon; ?>"></i>
                                <?php echo strtoupper($pending['tipe']); ?>
                            </span>
                        </td>
                        <td class="text-center"><strong><?php echo $pending['quantity']; ?></strong></td>
                        <td class="text-right">
                            <div>@ Rp <?php echo number_format($pending['harga_satuan'], 0, ',', '.'); ?></div>
                            <strong class="blue">Rp <?php echo number_format($pending['total'], 0, ',', '.'); ?></strong>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-xs">
                                <button type="button" class="btn btn-success"
                                        onclick="approveWorkorderItem(<?php echo $pending['id']; ?>, '<?php echo addslashes($pending['nama_item']); ?>')"
                                        title="Setujui - Masuk ke Item Barang/Jasa">
                                    <i class="fa fa-check"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger"
                                        onclick="rejectWorkorderItem(<?php echo $pending['id']; ?>, '<?php echo addslashes($pending['nama_item']); ?>')"
                                        title="Tolak - Masuk ke Catatan">
                                    <i class="fa fa-times"></i> Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
                <tfoot>
                    <tr class="info">
                        <th colspan="6" class="text-right">
                            <strong>Total:</strong>
                        </th>
                        <th class="text-right">
                            <?php
                            mysqli_data_seek($pending_items_result, 0);
                            $total_nilai = 0;
                            while ($p = mysqli_fetch_array($pending_items_result)) {
                                $total_nilai += $p['total'];
                            }
                            ?>
                            <strong class="blue" style="font-size: 16px;">
                                Rp <?php echo number_format($total_nilai, 0, ',', '.'); ?>
                            </strong>
                        </th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="space-14"></div>
<?php } ?>

<!-- SECTION 3: STATISTICS SUMMARY -->
<?php
$stats_query = "SELECT
                    COUNT(*) as total_temuan,
                    SUM(CASE WHEN status_temuan = 'ditemukan' THEN 1 ELSE 0 END) as temuan_baru,
                    (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE no_service='$no_service' AND status_penawaran='pending') as penawaran_pending,
                    (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE no_service='$no_service' AND status_penawaran='disetujui') as penawaran_approved,
                    (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE no_service='$no_service' AND status_penawaran='ditolak') as penawaran_rejected,
                    (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE no_service='$no_service') as total_penawaran,
                    (SELECT COUNT(*) FROM tbservis_pending_items WHERE no_service='$no_service' AND status_approval='pending') as pending_wo_items
                FROM tbservis_temuan
                WHERE no_service='$no_service'";
$stats = mysqli_fetch_array(mysqli_query($koneksi, $stats_query));
?>

<!-- Export Button Section -->
<div class="row" style="margin-bottom: 15px;">
    <div class="col-xs-12 text-right">
        <div class="btn-group">
            <button type="button"
                    class="btn btn-success btn-lg dropdown-toggle"
                    data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="false"
                    style="background: linear-gradient(135deg, #27ae60 0%, #229954 100%); border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <i class="fa fa-download"></i> Download Data <span class="caret"></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-right">
                <li class="dropdown-header"><i class="fa fa-file-excel-o"></i> Format Excel (.xls)</li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=excel&type=all&no_service=<?php echo $no_service; ?>">Temuan &amp; Penawaran (Excel)</a></li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=excel&type=temuan&no_service=<?php echo $no_service; ?>">Temuan Saja (Excel)</a></li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=excel&type=penawaran&no_service=<?php echo $no_service; ?>">Penawaran Saja (Excel)</a></li>
                <li role="separator" class="divider"></li>
                <li class="dropdown-header"><i class="fa fa-file-pdf-o"></i> Format PDF (.pdf)</li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=pdf&type=all&no_service=<?php echo $no_service; ?>">Temuan &amp; Penawaran (PDF)</a></li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=pdf&type=temuan&no_service=<?php echo $no_service; ?>">Temuan Saja (PDF)</a></li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=pdf&type=penawaran&no_service=<?php echo $no_service; ?>">Penawaran Saja (PDF)</a></li>
                <li role="separator" class="divider"></li>
                <li class="dropdown-header"><i class="fa fa-filter"></i> Filter Status Penawaran (Excel)</li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=excel&type=penawaran&status=pending&no_service=<?php echo $no_service; ?>">Penawaran Pending (Excel)</a></li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=excel&type=penawaran&status=disetujui&no_service=<?php echo $no_service; ?>">Penawaran Disetujui (Excel)</a></li>
                <li><a target="_blank" href="temuan-penawaran-export.php?format=excel&type=penawaran&status=ditolak&no_service=<?php echo $no_service; ?>">Penawaran Ditolak (Excel)</a></li>
            </ul>
        </div>
    </div>
</div>

<div class="stats-summary">
    <div class="stat-card" style="border-left: 3px solid #f39c12;">
        <div class="stat-number stat-pending"><?php echo $stats['pending_wo_items'] ?: 0; ?></div>
        <div class="stat-label">Item WO Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-number stat-total"><?php echo $stats['total_temuan'] ?: 0; ?></div>
        <div class="stat-label">Total Temuan</div>
    </div>
    <div class="stat-card">
        <div class="stat-number stat-pending"><?php echo $stats['penawaran_pending'] ?: 0; ?></div>
        <div class="stat-label">Penawaran Part</div>
    </div>
    <div class="stat-card">
        <div class="stat-number stat-approved"><?php echo $stats['penawaran_approved'] ?: 0; ?></div>
        <div class="stat-label">Disetujui</div>
    </div>
    <div class="stat-card">
        <div class="stat-number stat-rejected"><?php echo $stats['penawaran_rejected'] ?: 0; ?></div>
        <div class="stat-label">Ditolak</div>
    </div>
</div>

<!-- SECTION 4A: PENAWARAN PART UMUM (Tidak terkait temuan spesifik) -->
<?php
// Query penawaran part yang tidak terkait temuan spesifik
$sql_penawaran_umum = "SELECT * FROM tbservis_penawaran_part
                       WHERE no_service = '$no_service'
                       AND (temuan_id IS NULL OR temuan_id = 0 OR temuan_id = '')
                       ORDER BY status_penawaran ASC, created_at DESC";
$result_penawaran_umum = mysqli_query($koneksi, $sql_penawaran_umum);

if($result_penawaran_umum && mysqli_num_rows($result_penawaran_umum) > 0) {
?>
<div class="row" style="margin-bottom: 20px;">
    <div class="col-xs-12">
        <div class="widget-box" style="border: 2px solid #5cb85c;">
            <div class="widget-header" style="background: linear-gradient(135deg, #5cb85c 0%, #449d44 100%); color: white;">
                <h5 class="widget-title">
                    <i class="ace-icon fa fa-shopping-cart"></i>
                    Penawaran Part Umum (<?php echo mysqli_num_rows($result_penawaran_umum); ?>)
                </h5>
            </div>
            <div class="widget-body">
                <div class="widget-main no-padding">
                    <table class="table table-bordered table-hover">
                        <thead class="thin-border-bottom" style="background: #f9f9f9;">
                            <tr>
                                <th width="5%">#</th>
                                <th width="35%">Part</th>
                                <th width="10%" class="text-center">Qty</th>
                                <th width="15%" class="text-right">Harga Satuan</th>
                                <th width="15%" class="text-right">Total</th>
                                <th width="10%" class="text-center">Status</th>
                                <th width="10%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 0;
                            mysqli_data_seek($result_penawaran_umum, 0); // Reset pointer
                            while($penawaran = mysqli_fetch_array($result_penawaran_umum)) {
                                $no++;
                                $row_class = '';
                                $status_badge = '';

                                if ($penawaran['status_penawaran'] == 'pending') {
                                    $row_class = 'warning';
                                    $status_badge = '<span class="label label-warning">Pending</span>';
                                } elseif ($penawaran['status_penawaran'] == 'disetujui') {
                                    $row_class = 'success';
                                    $status_badge = '<span class="label label-success">Disetujui</span>';
                                } elseif ($penawaran['status_penawaran'] == 'ditolak') {
                                    $row_class = 'danger';
                                    $status_badge = '<span class="label label-danger">Ditolak</span>';
                                }
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td class="text-center"><?php echo $no; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($penawaran['nama_barang']); ?></strong><br>
                                    <small class="text-muted">
                                        <i class="fa fa-barcode"></i> <?php echo $penawaran['kode_barang']; ?>
                                    </small>
                                    <?php if(strpos($penawaran['kode_barang'], 'CUSTOM-') === 0) { ?>
                                    <br><span class="badge badge-success" style="background: #5cb85c;">
                                        <i class="fa fa-cube"></i> Custom Item
                                    </span>
                                    <?php } ?>
                                </td>
                                <td class="text-center">
                                    <strong style="font-size: 16px;"><?php echo $penawaran['quantity']; ?></strong>
                                </td>
                                <td class="text-right">
                                    Rp <?php echo number_format($penawaran['harga_satuan'], 0, ',', '.'); ?>
                                </td>
                                <td class="text-right">
                                    <strong class="blue" style="font-size: 15px;">
                                        Rp <?php echo number_format($penawaran['total_harga'], 0, ',', '.'); ?>
                                    </strong>
                                </td>
                                <td class="text-center">
                                    <?php echo $status_badge; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($penawaran['status_penawaran'] == 'pending') { ?>
                                    <div class="btn-group btn-group-xs">
                                        <button type="button" class="btn btn-success btn-xs"
                                                onclick="approvePenawaran(<?php echo $penawaran['id']; ?>)"
                                                title="Setujui">
                                            <i class="ace-icon fa fa-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-danger btn-xs"
                                                onclick="rejectPenawaran(<?php echo $penawaran['id']; ?>)"
                                                title="Tolak">
                                            <i class="ace-icon fa fa-times"></i>
                                        </button>
                                    </div>
                                    <?php } elseif($penawaran['status_penawaran'] == 'disetujui') { ?>
                                    <span class="text-muted"><i class="fa fa-check-circle"></i> Approved</span>
                                    <?php } else { ?>
                                    <span class="text-muted"><i class="fa fa-ban"></i> Rejected</span>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        <tfoot>
                            <tr class="info">
                                <th colspan="4" class="text-right">TOTAL PENAWARAN:</th>
                                <th class="text-right">
                                    <?php
                                    // Calculate total
                                    mysqli_data_seek($result_penawaran_umum, 0);
                                    $grand_total = 0;
                                    while($p = mysqli_fetch_array($result_penawaran_umum)) {
                                        if ($p['status_penawaran'] == 'pending' || $p['status_penawaran'] == 'disetujui') {
                                            $grand_total += $p['total_harga'];
                                        }
                                    }
                                    ?>
                                    <strong style="font-size: 16px;">
                                        Rp <?php echo number_format($grand_total, 0, ',', '.'); ?>
                                    </strong>
                                </th>
                                <th colspan="2"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<!-- SECTION 4B: LIST TEMUAN & PENAWARAN -->
<div class="row">
    <div class="col-xs-12">
        <?php
        // Query temuan dengan penawaran part
        $sql_temuan = "SELECT
                        t.*,
                        k.keluhan,
                        COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display,
                        mt.kategori as kategori_temuan,
                        (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id) as total_penawaran,
                        (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='pending') as pending_count,
                        (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='disetujui') as approved_count,
                        (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='ditolak') as rejected_count
                      FROM tbservis_temuan t
                      LEFT JOIN tbservis_keluhan_status k ON t.keluhan_id = k.id
                      LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                      WHERE t.no_service = '$no_service'
                      ORDER BY t.created_at DESC";

        $result_temuan = mysqli_query($koneksi, $sql_temuan);
        $no_temuan = 0;

        if(mysqli_num_rows($result_temuan) > 0) {
            while($temuan = mysqli_fetch_array($result_temuan)) {
                $no_temuan++;

                // Determine urgensi class
                $urgensi_class = 'urgent-' . $temuan['tingkat_urgensi'];
                $urgensi_badge = 'badge-urgent-' . $temuan['tingkat_urgensi'];
                $urgensi_text = ucfirst($temuan['tingkat_urgensi']);

                // Status
                $status_class = 'status-' . $temuan['status_temuan'];
                $status_text = ucfirst($temuan['status_temuan']);
        ?>

        <!-- TEMUAN CARD -->
        <div class="temuan-modern-card <?php echo $urgensi_class; ?>">
            <!-- Header -->
            <div class="temuan-header-modern">
                <div class="row">
                    <div class="col-sm-8">
                        <div class="temuan-title">
                            <i class="ace-icon fa fa-search-plus"></i>
                            <?php echo htmlspecialchars($temuan['nama_temuan_display']); ?>
                        </div>
                        <div class="temuan-meta">
                            <span class="badge-modern <?php echo $urgensi_badge; ?>">
                                <?php echo $urgensi_text; ?>
                            </span>
                            <span class="badge-status <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                            <?php if($temuan['kategori_temuan']) { ?>
                            <span class="label label-default"><?php echo $temuan['kategori_temuan']; ?></span>
                            <?php } ?>
                            <?php if($temuan['jenis_perbaikan'] == 'penggantian_part') { ?>
                            <span class="label label-warning">
                                <i class="fa fa-wrench"></i> Penggantian Part
                            </span>
                            <?php } else { ?>
                            <span class="label label-info">
                                <i class="fa fa-cog"></i> Setting/Servis
                            </span>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-sm-4 text-right">
                        <small class="text-muted">
                            <i class="fa fa-clock-o"></i>
                            <?php echo date('d/m/Y H:i', strtotime($temuan['created_at'])); ?>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="temuan-body-modern">
                <div class="row">
                    <!-- Left Column: Temuan Info -->
                    <div class="col-md-6">
                        <div class="info-grid">
                            <?php if($temuan['keluhan']) { ?>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fa fa-exclamation-circle"></i> Keluhan Terkait
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($temuan['keluhan']); ?></div>
                            </div>
                            <?php } ?>

                            <?php if($temuan['mekanik_name']) { ?>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fa fa-user"></i> Ditemukan Oleh
                                </div>
                                <div class="info-value"><?php echo htmlspecialchars($temuan['mekanik_name']); ?></div>
                            </div>
                            <?php } ?>

                            <?php if($temuan['estimasi_biaya'] > 0) { ?>
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fa fa-money"></i> Estimasi Biaya
                                </div>
                                <div class="info-value">Rp <?php echo number_format($temuan['estimasi_biaya'], 0, ',', '.'); ?></div>
                            </div>
                            <?php } ?>
                        </div>

                        <?php if($temuan['deskripsi_temuan']) { ?>
                        <div class="alert alert-info" style="margin-top: 15px;">
                            <strong><i class="fa fa-info-circle"></i> Deskripsi Detail:</strong><br>
                            <?php echo nl2br(htmlspecialchars($temuan['deskripsi_temuan'])); ?>
                        </div>
                        <?php } ?>
                    </div>

                    <!-- Right Column: Penawaran Part -->
                    <div class="col-md-6">
                        <div class="penawaran-section">
                            <div class="penawaran-header">
                                <h5 style="margin: 0;">
                                    <i class="fa fa-shopping-cart"></i>
                                    Penawaran Part (<?php echo $temuan['total_penawaran']; ?>)
                                </h5>
                                <?php if($temuan['jenis_perbaikan'] == 'penggantian_part') { ?>
                                <button type="button" class="btn btn-xs btn-primary"
                                        onclick="tambahPartKeTemuan(<?php echo $temuan['id']; ?>)">
                                    <i class="fa fa-plus"></i> Tambah Part
                                </button>
                                <?php } ?>
                            </div>

                            <div class="rekomendasi-mapping">
                                <div class="rekomendasi-title">Rekomendasi Part (Mapping)</div>
                                <div id="rekomendasi-list-<?php echo $temuan['id']; ?>" class="rekomendasi-list" data-kode="<?php echo htmlspecialchars($temuan['kode_temuan']); ?>" data-temuan-id="<?php echo $temuan['id']; ?>">
                                    <span class="text-muted">Memuat rekomendasi...</span>
                                </div>
                            </div>

                            <?php
                            // Query penawaran part untuk temuan ini
                            $sql_penawaran = "SELECT * FROM tbservis_penawaran_part
                                             WHERE temuan_id = '{$temuan['id']}'
                                             ORDER BY status_penawaran ASC, created_at DESC";
                            $result_penawaran = mysqli_query($koneksi, $sql_penawaran);

                            if(mysqli_num_rows($result_penawaran) > 0) {
                            ?>
                                <div class="penawaran-table">
                                    <table class="table table-bordered table-condensed">
                                        <thead>
                                            <tr class="info">
                                                <th width="40%">Part</th>
                                                <th width="15%" class="text-center">Qty</th>
                                                <th width="20%" class="text-right">Harga</th>
                                                <th width="25%" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php while($penawaran = mysqli_fetch_array($result_penawaran)) {
                                            $penawaran_class = 'penawaran-' . $penawaran['status_penawaran'];
                                        ?>
                                            <tr class="penawaran-row <?php echo $penawaran_class; ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($penawaran['nama_barang']); ?></strong><br>
                                                    <small class="text-muted"><?php echo $penawaran['kode_barang']; ?></small>
                                                    <?php if($penawaran['is_from_suggestion']) { ?>
                                                    <br><span class="badge badge-success">Auto-Suggest</span>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center">
                                                    <strong><?php echo $penawaran['quantity']; ?></strong>
                                                </td>
                                                <td class="text-right">
                                                    <div>Rp <?php echo number_format($penawaran['harga_satuan'], 0, ',', '.'); ?></div>
                                                    <strong class="blue">
                                                        Rp <?php echo number_format($penawaran['total_harga'], 0, ',', '.'); ?>
                                                    </strong>
                                                </td>
                                                <td class="text-center">
                                                    <?php if($penawaran['status_penawaran'] == 'pending') { ?>
                                                    <div class="btn-group btn-group-xs">
                                                        <button type="button" class="btn btn-success"
                                                                onclick="approvePenawaran(<?php echo $penawaran['id']; ?>)"
                                                                title="Setujui">
                                                            <i class="fa fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger"
                                                                onclick="rejectPenawaran(<?php echo $penawaran['id']; ?>)"
                                                                title="Tolak">
                                                            <i class="fa fa-times"></i>
                                                        </button>
                                                    </div>
                                                    <?php } elseif($penawaran['status_penawaran'] == 'disetujui') { ?>
                                                    <span class="label label-success">
                                                        <i class="fa fa-check"></i> Disetujui
                                                    </span>
                                                    <?php } else { ?>
                                                    <span class="label label-danger">
                                                        <i class="fa fa-times"></i> Ditolak
                                                    </span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                            <?php if($penawaran['status_penawaran'] == 'ditolak' && $penawaran['keterangan_tolak']) { ?>
                                            <tr>
                                                <td colspan="4" style="background: #fff5f5; padding: 8px;">
                                                    <small>
                                                        <strong><i class="fa fa-info-circle text-danger"></i> Alasan ditolak:</strong>
                                                        <?php echo htmlspecialchars($penawaran['keterangan_tolak']); ?>
                                                    </small>
                                                </td>
                                            </tr>
                                            <?php } ?>
                                        <?php } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Summary -->
                                <div class="row" style="margin-top: 10px;">
                                    <div class="col-xs-4 text-center">
                                        <small>Pending</small><br>
                                        <strong class="stat-pending" style="font-size: 18px;"><?php echo $temuan['pending_count']; ?></strong>
                                    </div>
                                    <div class="col-xs-4 text-center">
                                        <small>Disetujui</small><br>
                                        <strong class="stat-approved" style="font-size: 18px;"><?php echo $temuan['approved_count']; ?></strong>
                                    </div>
                                    <div class="col-xs-4 text-center">
                                        <small>Ditolak</small><br>
                                        <strong class="stat-rejected" style="font-size: 18px;"><?php echo $temuan['rejected_count']; ?></strong>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="alert alert-warning">
                                    <i class="fa fa-info-circle"></i>
                                    <?php if($temuan['jenis_perbaikan'] == 'penggantian_part') { ?>
                                        Belum ada part yang ditawarkan. Klik tombol "Tambah Part" untuk menambahkan.
                                    <?php } else { ?>
                                        Temuan ini hanya memerlukan setting/servis, tidak perlu penggantian part.
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="action-buttons-group">
                <button type="button" class="btn btn-xs btn-info btn-modern"
                        onclick="editTemuan(<?php echo $temuan['id']; ?>)">
                    <i class="fa fa-edit"></i> Edit
                </button>
                <button type="button" class="btn btn-xs btn-danger btn-modern"
                        onclick="deleteTemuan(<?php echo $temuan['id']; ?>)">
                    <i class="fa fa-trash"></i> Hapus
                </button>
            </div>
        </div>

        <?php
            } // end while
        } else {
        ?>

        <!-- Empty State -->
        <div class="empty-state">
            <i class="ace-icon fa fa-search-plus"></i>
            <h4>Belum Ada Temuan</h4>
            <p>Belum ada temuan hasil pengecekan untuk servis ini.<br>Gunakan form di atas untuk menambahkan temuan baru.</p>
        </div>

        <?php } ?>
    </div>
</div>

<!-- JAVASCRIPT FUNCTIONS -->
<script type="text/javascript">
// Helper functions for onclick - no jQuery dependency
// Mapping Recommendation Helpers
function escapeHtml(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function jsEscape(s) {
    return String(s || '').replace(/\\/g, '\\\\').replace(/'/g, "\\'");
}

function formatNumber(n){
    try { return (parseInt(n)||0).toLocaleString('id-ID'); } catch(e){ return n; }
}

window.autoFillPenawaranFromRecommendation = function(temuanId, kode, nama, harga, qty) {
    if (typeof jQuery !== 'undefined') {
        jQuery('#temuan_id_select').val(temuanId);
        jQuery('#kode_barang_penawaran').val(kode);
        jQuery('#nama_barang_penawaran').val(nama);
        jQuery('#harga_satuan_penawaran').val(harga || 0);
        jQuery('#quantity_penawaran').val(qty || 1);
        hitungTotalPenawaran();
        jQuery('#formAddPenawaran').slideDown();
        jQuery('html, body').animate({ scrollTop: jQuery('#formAddPenawaran').offset().top - 100 }, 400);
        jQuery('#kode_barang_penawaran, #nama_barang_penawaran').addClass('animated-input');
        setTimeout(function(){ jQuery('#kode_barang_penawaran, #nama_barang_penawaran').removeClass('animated-input'); }, 800);
    } else {
        document.getElementById('temuan_id_select').value = temuanId;
        document.getElementById('kode_barang_penawaran').value = kode;
        document.getElementById('nama_barang_penawaran').value = nama;
        document.getElementById('harga_satuan_penawaran').value = harga || 0;
        document.getElementById('quantity_penawaran').value = qty || 1;
        hitungTotalPenawaran();
        var form = document.getElementById('formAddPenawaran');
        if (form) form.style.display = 'block';
    }
};

function initRekomendasiMapping(){
    if (typeof jQuery === 'undefined') return;
    var $ = jQuery;
    $('.rekomendasi-list').each(function(){
        var $el = $(this);
        var kode = $el.data('kode');
        var temuanId = $el.data('temuan-id');
        if(!kode){
            $el.html('<div class="alert alert-info" style="margin-bottom:8px;">Temuan belum punya kode standar.</div>');
            return;
        }
        $el.html('<span class="text-muted">Memuat rekomendasi...</span>');
        $.ajax({
            url: '_handler_temuan_penawaran.php',
            data: { action: 'get_parts_by_temuan_kode', kode_temuan: kode },
            dataType: 'json',
            method: 'GET'
        }).done(function(resp){
            try {
                if(resp && resp.success && resp.count > 0){
                    var html = '';
                    for(var i=0;i<resp.parts.length;i++){
                        var p = resp.parts[i];
                        var cls = (String(p.is_primary)==='1') ? 'primary' : 'alt';
                        html += '<div class="rekomendasi-item '+cls+'">'
                             +   '<div class="r-left">'
                             +     '<div class="r-name"><strong>'+escapeHtml(p.nama_barang)+'</strong></div>'
                             +     '<div class="r-meta"><code>'+escapeHtml(p.kode_barang)+'</code> • Stok: '+formatNumber(p.stok_tersedia||0)+' • Qty: '+formatNumber(p.qty_default||1)+'</div>'
                             +   '</div>'
                             +   '<div class="r-right">'
                             +     '<div class="r-price">Rp '+formatNumber(p.harga_jual||0)+'</div>'
                             +     '<div class="btn-group btn-group-xs">'
                             +       '<button type="button" class="btn btn-success" onclick="autoFillPenawaranFromRecommendation('+temuanId+', \'"+jsEscape(p.kode_barang)+"\', \'"+jsEscape(p.nama_barang)+"\', '+(p.harga_jual||0)+', '+(p.qty_default||1)+')">Auto-fill</button>'
                             +       '<button type="button" class="btn btn-primary" onclick="quickAddPenawaran('+temuanId+', \'"+jsEscape(p.kode_barang)+"\', '+(p.mapping_id||'null')+', '+(p.prioritas||'null')+')">Quick-Add</button>'
                             +     '</div>'
                             +   '</div>'
                             + '</div>';
                    }
                    $el.html(html);
                } else {
                    $el.html('<div class="alert alert-warning" style="margin-bottom:8px;">Tidak ada mapping part untuk temuan ini.</div>');
                }
            } catch(e){
                $el.html('<div class="alert alert-danger" style="margin-bottom:8px;">Gagal memproses data.</div>');
            }
        }).fail(function(){
            $el.html('<div class="alert alert-danger" style="margin-bottom:8px;">Gagal memuat rekomendasi.</div>');
        });
    });
}

// Quick-Add: langsung buat penawaran dari mapping (server ambil harga jual master)
function quickAddPenawaran(temuanId, kodeBarang, mappingId, priority){
    try {
        var f = document.createElement('form');
        f.method = 'POST';
        f.action = '_handler_temuan_penawaran.php';
        f.innerHTML = ''+
            '<input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">'+
            '<input type="hidden" name="temuan_id" value="'+temuanId+'">'+
            '<input type="hidden" name="kode_barang" value="'+kodeBarang+'">'+
            (mappingId ? '<input type="hidden" name="mapping_id" value="'+mappingId+'">' : '')+
            (priority ? '<input type="hidden" name="suggestion_priority" value="'+priority+'">' : '')+
            '<input type="hidden" name="btnquickaddpenawaran" value="1">';
        document.body.appendChild(f);
        f.submit();
    } catch(e) {
        alert('Gagal Quick-Add: '+e.message);
    }
}
function toggleFormPenawaran() {
    if (typeof jQuery !== 'undefined') {
        jQuery('#formAddPenawaran').slideToggle();
    } else {
        var form = document.getElementById('formAddPenawaran');
        if (form) {
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    }
}

function showModalFastMoves() {
    if (typeof jQuery !== 'undefined') {
        if (jQuery('#modalFastMovesV2').length) {
            jQuery('#modalFastMovesV2').modal('show');
        } else if (jQuery('#modalFastMovesPart').length) {
            jQuery('#modalFastMovesPart').modal('show');
        } else if (jQuery('#modalFastMoves').length) {
            jQuery('#modalFastMoves').modal('show');
        }
    }
}

function showModalCustom() {
    if (typeof jQuery !== 'undefined') {
        jQuery('#modalInputCustom').modal('show');
    }
}

// Hitung total penawaran
function hitungTotalPenawaran() {
    var harga = parseInt(document.getElementById('harga_satuan_penawaran').value || 0);
    var qty = parseInt(document.getElementById('quantity_penawaran').value || 0);
    var total = harga * qty;
    document.getElementById('total_penawaran_display').value = 'Rp ' + formatRupiah(total);
}

// Format rupiah
function formatRupiah(angka) {
    return parseInt(angka).toLocaleString('id-ID');
}

// Callback dari Fast Moves Part Modal
window.onFastMovesPartSelected = function(kode, nama, harga, satuan, qty) {
    document.getElementById('kode_barang_penawaran').value = kode;
    document.getElementById('nama_barang_penawaran').value = nama;
    document.getElementById('harga_satuan_penawaran').value = harga;
    document.getElementById('quantity_penawaran').value = qty || 1;
    hitungTotalPenawaran();

    // Close modal
    $('#modalFastMovesPart').modal('hide');

    // Show form if hidden
    $('#formAddPenawaran').slideDown();

    // Visual feedback
    $('#kode_barang_penawaran, #nama_barang_penawaran').addClass('animated-input');
    setTimeout(function() {
        $('#kode_barang_penawaran, #nama_barang_penawaran').removeClass('animated-input');
    }, 1000);
};

// Tambah part ke temuan tertentu
function tambahPartKeTemuan(temuanId) {
    $('#temuan_id_select').val(temuanId);
    $('#formAddPenawaran').slideDown();
    $('html, body').animate({
        scrollTop: $('#formAddPenawaran').offset().top - 100
    }, 500);
}

// Approve penawaran
function approvePenawaran(penawaranId) {
    if(confirm('Setujui penawaran part ini?\n\nPart akan ditambahkan ke daftar item barang servis.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="penawaran_id" value="' + penawaranId + '">' +
                        '<input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">' +
                        '<input type="hidden" name="btnsetujuipenawaran" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Reject penawaran
function rejectPenawaran(penawaranId) {
    var alasan = prompt('Alasan penolakan:\n\n1. Customer tidak mau\n2. Stok bengkel kosong\n3. Stok supplier kosong\n4. Harga tidak cocok\n5. Lainnya\n\nKetik nomor (1-5):');

    if(alasan) {
        var alasanMap = {
            '1': 'customer_tidak_mau',
            '2': 'stok_bengkel_kosong',
            '3': 'stok_supplier_kosong',
            '4': 'harga_tidak_cocok',
            '5': 'lainnya'
        };

        var alasanCode = alasanMap[alasan] || 'lainnya';
        var keterangan = prompt('Keterangan tambahan (optional):');

        // Alasan text untuk konfirmasi
        var alasanText = {
            'customer_tidak_mau': 'Customer tidak mau',
            'stok_bengkel_kosong': 'Stok bengkel kosong',
            'stok_supplier_kosong': 'Stok supplier kosong',
            'harga_tidak_cocok': 'Harga tidak cocok',
            'lainnya': 'Lainnya'
        };

        if(confirm('Tolak penawaran ini?\n\nAlasan: ' + alasanText[alasanCode] + '\n\nPenawaran akan ditandai sebagai ditolak.')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="penawaran_id" value="' + penawaranId + '">' +
                            '<input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">' +
                            '<input type="hidden" name="alasan_tolak" value="' + alasanCode + '">' +
                            '<input type="hidden" name="keterangan_tolak" value="' + (keterangan || '') + '">' +
                            '<input type="hidden" name="btnrejectpenawaran" value="1">';
            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Edit temuan
function editTemuan(temuanId) {
    alert('Fitur edit temuan akan segera tersedia.\nTemuan ID: ' + temuanId);
    // TODO: Implement edit modal
}

// Delete temuan
function deleteTemuan(temuanId) {
    if(confirm('Hapus temuan ini?\n\nSemua penawaran part yang terkait juga akan dihapus.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="temuan_id" value="' + temuanId + '">' +
                        '<input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">' +
                        '<input type="hidden" name="btndeletetemuan" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Approve workorder item
function approveWorkorderItem(itemId, namaItem) {
    if (confirm('Setujui item ini?\n\n' + namaItem + '\n\nItem akan ditambahkan ke daftar Barang/Jasa.')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="pending_item_id" value="' + itemId + '">' +
                        '<input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">' +
                        '<input type="hidden" name="btnapprove_wo_item" value="1">';
        document.body.appendChild(form);
        form.submit();
    }
}

// Reject workorder item
function rejectWorkorderItem(itemId, namaItem) {
    var alasan = prompt('Alasan penolakan:\n\n1. Pelanggan tidak setuju\n2. Stok barang di bengkel tidak ada\n3. Stok barang di supplier tidak ada\n4. Lainnya\n\nKetik nomor (1-4):');

    if (alasan) {
        var alasanMap = {
            '1': 'customer_tidak_mau',
            '2': 'stok_cabang_kosong',
            '3': 'stok_supplier_kosong',
            '4': 'lainnya'
        };

        var alasanCode = alasanMap[alasan] || 'lainnya';
        var keterangan = prompt('Keterangan tambahan (optional):');

        // Konfirmasi
        var alasanText = {
            'customer_tidak_mau': 'Pelanggan tidak setuju',
            'stok_cabang_kosong': 'Stok barang di bengkel tidak ada',
            'stok_supplier_kosong': 'Stok barang di supplier tidak ada',
            'lainnya': 'Lainnya'
        };

        if (confirm('Tolak item ini?\n\n' + namaItem + '\n\nAlasan: ' + alasanText[alasanCode] + '\n\nItem akan dicatat di catatan service.')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="pending_item_id" value="' + itemId + '">' +
                            '<input type="hidden" name="txtnosrv" value="<?php echo $no_service; ?>">' +
                            '<input type="hidden" name="alasan_reject" value="' + alasanCode + '">' +
                            '<input type="hidden" name="keterangan_reject" value="' + (keterangan || '') + '">' +
                            '<input type="hidden" name="btnreject_wo_item" value="1">';
            document.body.appendChild(form);
            form.submit();
        }
    }
}

// Initialize on load - Wait for jQuery
(function waitAndInit() {
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            // Auto-calculate on change
            $('#harga_satuan_penawaran, #quantity_penawaran').on('input', hitungTotalPenawaran);
            initRekomendasiMapping();
        });
    } else {
        setTimeout(waitAndInit, 50);
    }
})();
</script>

<style>
@keyframes highlightInput {
    0% { background-color: #fff3cd; }
    100% { background-color: #ffffff; }
}

.animated-input {
    animation: highlightInput 1s ease-in-out;
}

/* Button Custom Item */
.btn-custom-item {
    background: linear-gradient(135deg, #5cb85c 0%, #449d44 100%);
    border: none;
    color: white;
    padding: 12px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
}

.btn-custom-item:hover {
    background: linear-gradient(135deg, #449d44 0%, #398439 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    color: white;
}

.btn-custom-item:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.btn-custom-item i {
    margin-right: 5px;
}

.rekomendasi-mapping { margin-bottom: 12px; }
.rekomendasi-title { font-weight: 600; margin-bottom: 8px; color: #2c3e50; }
.rekomendasi-item { display: flex; justify-content: space-between; align-items: center; background: #fff; border: 1px solid #eee; border-left: 3px solid #5cb85c; padding: 8px 10px; border-radius: 6px; margin-bottom: 6px; }
.rekomendasi-item.alt { border-left-color: #f0ad4e; }
.rekomendasi-item .r-name { font-size: 13px; }
.rekomendasi-item .r-meta { font-size: 11px; color: #7f8c8d; }
.rekomendasi-item .r-price { font-weight: 600; margin-right: 8px; }
</style>

<?php
// Include modal input barang custom
$modal_custom_path = __DIR__ . "/modal-input-barang-custom.php";
if (file_exists($modal_custom_path)) {
    include "modal-input-barang-custom.php";
}
?>
