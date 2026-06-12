<?php
/**
 * TEMPLATE: Previous Service Data (Read-Only)
 * Menampilkan data servis sebelumnya sebagai referensi untuk garansi
 * Mode: READ-ONLY (tidak ada form input atau tombol edit)
 */

// Check if reference service data exists
if(empty($ref_service) || empty($ref_service_data)) {
    return; // Don't render anything if no reference
}

$ref_service_escaped = mysqli_real_escape_string($koneksi, $ref_service);

// ========== FETCH KELUHAN ==========
$ref_keluhan = [];
$q_keluhan = mysqli_query($koneksi, "SELECT * FROM tbservis_keluhan_status 
    WHERE no_service = '$ref_service_escaped' 
    ORDER BY id ASC");
if($q_keluhan) {
    while($row = mysqli_fetch_assoc($q_keluhan)) {
        $ref_keluhan[] = $row;
    }
}

// ========== FETCH WORKORDER ==========
$ref_workorder = [];
$q_wo = mysqli_query($koneksi, "SELECT sw.*, 
    COALESCE(mw.nama_workorder, sw.kode_workorder) as nama_workorder_display
    FROM tbservis_workorder sw
    LEFT JOIN tbmaster_workorder mw ON sw.kode_workorder = mw.kode_workorder
    WHERE sw.no_service = '$ref_service_escaped'
    ORDER BY sw.id ASC");
if($q_wo) {
    while($row = mysqli_fetch_assoc($q_wo)) {
        $ref_workorder[] = $row;
    }
}

// ========== FETCH TEMUAN ==========
$ref_temuan = [];
$q_temuan = mysqli_query($koneksi, "SELECT t.*, 
    COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display
    FROM tbservis_temuan t
    LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
    WHERE t.no_service = '$ref_service_escaped'
    ORDER BY t.id ASC");
if($q_temuan) {
    while($row = mysqli_fetch_assoc($q_temuan)) {
        $ref_temuan[] = $row;
    }
}

// ========== FETCH REJECTED/DENIED ITEMS ==========
$ref_rejected_items = [];
// From penawaran part (rejected)
$q_rejected_part = mysqli_query($koneksi, "SELECT pp.*, 
    COALESCE(i.namaitem, pp.kode_barang) as nama_display,
    'barang' as jenis_item
    FROM tbservis_penawaran_part pp
    LEFT JOIN tblitem i ON pp.kode_barang = i.noitem
    WHERE pp.no_service = '$ref_service_escaped' 
    AND pp.status_penawaran = 'ditolak'
    ORDER BY pp.id ASC");
if($q_rejected_part) {
    while($row = mysqli_fetch_assoc($q_rejected_part)) {
        $ref_rejected_items[] = $row;
    }
}

// From pending items (rejected)
$q_rejected_pending = mysqli_query($koneksi, "SELECT pi.*, 
    COALESCE(i.namaitem, pi.kode_item) as nama_display
    FROM tbservis_pending_items pi
    LEFT JOIN tblitem i ON pi.kode_item = i.noitem
    WHERE pi.no_service = '$ref_service_escaped' 
    AND pi.status_approval = 'ditolak'
    ORDER BY pi.id ASC");
if($q_rejected_pending) {
    while($row = mysqli_fetch_assoc($q_rejected_pending)) {
        $ref_rejected_items[] = $row;
    }
}

// ========== FETCH CATATAN ==========
$ref_catatan = $ref_service_data['catatan'] ?? '';
$ref_catatan_internal = $ref_service_data['catatan_internal'] ?? '';

// Fetch barang items from reference service
$ref_barang = [];
$ref_total_barang = 0;
$q_ref_brg = mysqli_query($koneksi, "SELECT sb.*, 
    COALESCE(b.namaitem, sb.no_item) as nama_display,
    COALESCE(b.satuan, 'PCS') as satuan_display
    FROM tblservis_barang sb
    LEFT JOIN tblitem b ON sb.no_item = b.noitem
    WHERE sb.no_service = '$ref_service_escaped'
    ORDER BY sb.id ASC");
if($q_ref_brg) {
    while($row = mysqli_fetch_assoc($q_ref_brg)) {
        $ref_barang[] = $row;
        $ref_total_barang += floatval($row['total'] ?? 0);
    }
}

// Fetch jasa items from reference service
$ref_jasa = [];
$ref_total_jasa = 0;
$ref_total_waktu = 0;
$q_ref_jasa = mysqli_query($koneksi, "SELECT sj.*,
    COALESCE(i.namaitem, sj.no_item) as nama_display
    FROM tblservis_jasa sj
    LEFT JOIN tblitem i ON sj.no_item = i.noitem
    WHERE sj.no_service = '$ref_service_escaped'
    ORDER BY sj.id ASC");
if($q_ref_jasa) {
    while($row = mysqli_fetch_assoc($q_ref_jasa)) {
        $ref_jasa[] = $row;
        $ref_total_jasa += floatval($row['total'] ?? 0);
        $ref_total_waktu += intval($row['waktu'] ?? 0);
    }
}

// Calculate grand total
$ref_grand_total = $ref_total_barang + $ref_total_jasa;

// Get customer and vehicle info for reference service
$ref_pelanggan = '';
$ref_nopolisi = $ref_service_data['no_polisi'] ?? '';
$ref_kendaraan = '';

if(!empty($ref_service_data['no_pelanggan'])) {
    $q_pel = mysqli_query($koneksi, "SELECT namapelanggan FROM tblpelanggan WHERE nopelanggan='".mysqli_real_escape_string($koneksi, $ref_service_data['no_pelanggan'])."'");
    if($q_pel && $r_pel = mysqli_fetch_assoc($q_pel)) {
        $ref_pelanggan = $r_pel['namapelanggan'];
    }
}

if(!empty($ref_nopolisi)) {
    $q_kend = mysqli_query($koneksi, "SELECT merek, jenis, warna FROM view_cari_kendaraan WHERE nopolisi='".mysqli_real_escape_string($koneksi, $ref_nopolisi)."'");
    if($q_kend && $r_kend = mysqli_fetch_assoc($q_kend)) {
        $ref_kendaraan = trim(($r_kend['merek'] ?? '') . ' ' . ($r_kend['jenis'] ?? ''));
    }
}
?>

<style>
    .ref-service-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px dashed #6c757d;
        border-radius: 12px;
        margin-bottom: 24px;
        overflow: hidden;
    }
    
    .ref-service-header {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .ref-service-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .ref-service-header .ref-badge {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .ref-service-body {
        padding: 20px;
    }
    
    .ref-info-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .ref-info-item {
        text-align: center;
    }
    
    .ref-info-item .label {
        display: block;
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    
    .ref-info-item .value {
        font-size: 14px;
        font-weight: 600;
        color: #343a40;
    }
    
    .ref-section-title {
        font-size: 13px;
        font-weight: 600;
        color: #495057;
        margin: 16px 0 12px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .ref-section-title i {
        color: #6c757d;
    }
    
    .ref-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    
    .ref-table th {
        background: #f1f3f4;
        padding: 10px 12px;
        text-align: left;
        font-weight: 600;
        color: #495057;
        border-bottom: 1px solid #dee2e6;
    }
    
    .ref-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f1f3f4;
        color: #495057;
    }
    
    .ref-table tr:last-child td {
        border-bottom: none;
    }
    
    .ref-table .text-right {
        text-align: right;
    }
    
    .ref-table .text-center {
        text-align: center;
    }
    
    .ref-table tfoot td {
        background: #f8f9fa;
        font-weight: 600;
    }
    
    .ref-summary {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 2px solid #dee2e6;
    }
    
    .ref-summary-item {
        background: white;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }
    
    .ref-summary-item .label {
        font-size: 11px;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    
    .ref-summary-item .value {
        font-size: 16px;
        font-weight: 700;
        color: #495057;
    }
    
    .ref-summary-item.total .value {
        color: #28a745;
    }
    
    .ref-empty {
        text-align: center;
        padding: 20px;
        color: #6c757d;
        font-style: italic;
    }
    
    .ref-collapse-toggle {
        cursor: pointer;
        user-select: none;
    }
    
    .ref-collapse-toggle:hover {
        opacity: 0.9;
    }
    
    .ref-collapsible {
        transition: max-height 0.3s ease-out;
        overflow: hidden;
    }
    
    .ref-collapsible.collapsed {
        max-height: 0 !important;
        padding: 0 !important;
    }
    
    /* List container styles */
    .ref-list-container {
        background: white;
        border-radius: 8px;
        padding: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 16px;
    }
    
    .ref-list-container.rejected {
        background: #fff5f5;
        border: 1px solid #ffcccc;
    }
    
    .ref-list-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .ref-list-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }
    
    .ref-list-item:first-child {
        padding-top: 0;
    }
    
    .ref-list-item.rejected {
        background: rgba(220,53,69,0.05);
    }
    
    .ref-list-icon {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        flex-shrink: 0;
    }
    
    .ref-list-icon.done {
        background: #d4edda;
        color: #28a745;
    }
    
    .ref-list-icon.pending {
        background: #fff3cd;
        color: #856404;
    }
    
    .ref-list-icon.rejected {
        background: #f8d7da;
        color: #dc3545;
    }
    
    .ref-list-content {
        flex: 1;
    }
    
    .ref-list-content strong {
        display: block;
        font-size: 13px;
        color: #343a40;
        margin-bottom: 4px;
    }
    
    .ref-list-content small {
        display: block;
        font-size: 11px;
        color: #6c757d;
    }
    
    .ref-list-content .reject-reason {
        color: #dc3545;
        font-style: italic;
    }
    
    /* Status badges */
    .status-selesai, .status-disetujui { color: #28a745; font-weight: 600; }
    .status-pending, .status-ditemukan { color: #856404; font-weight: 600; }
    .status-ditolak { color: #dc3545; font-weight: 600; }
    
    /* Urgensi colors */
    .urgensi-rendah { color: #28a745; font-weight: 600; }
    .urgensi-sedang { color: #ffc107; font-weight: 600; }
    .urgensi-tinggi { color: #fd7e14; font-weight: 600; }
    .urgensi-kritis { color: #dc3545; font-weight: 600; }
    
    /* Section title variants */
    .ref-section-title.rejected {
        color: #dc3545;
    }
    
    .ref-section-title.rejected i {
        color: #dc3545;
    }
    
    /* Catatan container */
    .ref-catatan-container {
        background: white;
        border-radius: 8px;
        padding: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 16px;
    }
    
    .ref-catatan-item {
        margin-bottom: 12px;
    }
    
    .ref-catatan-item:last-child {
        margin-bottom: 0;
    }
    
    .ref-catatan-item label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    
    .ref-catatan-item p {
        margin: 0;
        font-size: 13px;
        color: #343a40;
        line-height: 1.5;
    }
    
    .ref-catatan-item.internal {
        background: #fff8e1;
        padding: 12px;
        border-radius: 6px;
        border-left: 3px solid #ffc107;
    }
</style>

<div class="ref-service-card">
    <div class="ref-service-header ref-collapse-toggle" onclick="toggleRefServicePanel()">
        <h4>
            <i class="fa fa-history"></i>
            DATA SERVIS SEBELUMNYA (REFERENSI GARANSI)
            <span class="ref-badge"><?= htmlspecialchars($ref_service) ?></span>
        </h4>
        <span style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 12px; opacity: 0.8;">Klik untuk buka/tutup</span>
            <i class="fa fa-chevron-down" id="ref-collapse-icon"></i>
        </span>
    </div>
    
    <div class="ref-service-body ref-collapsible" id="ref-service-body">
        <!-- Info Row -->
        <div class="ref-info-row">
            <div class="ref-info-item">
                <span class="label">No. Service Original</span>
                <span class="value"><?= htmlspecialchars($ref_service) ?></span>
            </div>
            <div class="ref-info-item">
                <span class="label">Tanggal Servis</span>
                <span class="value"><?= date('d/m/Y', strtotime($ref_service_data['tanggal'] ?? 'now')) ?></span>
            </div>
            <div class="ref-info-item">
                <span class="label">Pelanggan</span>
                <span class="value"><?= htmlspecialchars($ref_pelanggan ?: '-') ?></span>
            </div>
            <div class="ref-info-item">
                <span class="label">Kendaraan</span>
                <span class="value"><?= htmlspecialchars($ref_nopolisi) ?> - <?= htmlspecialchars($ref_kendaraan ?: '-') ?></span>
            </div>
        </div>
        
        <!-- Keluhan Section -->
        <?php if(count($ref_keluhan) > 0): ?>
        <div class="ref-section-title">
            <i class="fa fa-comment-alt"></i> Keluhan Pelanggan (<?= count($ref_keluhan) ?> item)
        </div>
        <div class="ref-list-container">
            <?php foreach($ref_keluhan as $k): ?>
            <div class="ref-list-item">
                <span class="ref-list-icon <?= strtolower($k['status'] ?? '') == 'selesai' ? 'done' : 'pending' ?>">
                    <i class="fa <?= strtolower($k['status'] ?? '') == 'selesai' ? 'fa-check' : 'fa-clock' ?>"></i>
                </span>
                <div class="ref-list-content">
                    <strong><?= htmlspecialchars($k['keluhan'] ?? '-') ?></strong>
                    <small>Status: <span class="status-<?= strtolower($k['status'] ?? 'pending') ?>"><?= htmlspecialchars($k['status'] ?? 'pending') ?></span></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Workorder Section -->
        <?php if(count($ref_workorder) > 0): ?>
        <div class="ref-section-title">
            <i class="fa fa-tasks"></i> Work Order (<?= count($ref_workorder) ?> item)
        </div>
        <div class="ref-list-container">
            <?php foreach($ref_workorder as $wo): ?>
            <div class="ref-list-item">
                <span class="ref-list-icon">
                    <i class="fa fa-clipboard-list"></i>
                </span>
                <div class="ref-list-content">
                    <strong><?= htmlspecialchars($wo['nama_workorder_display'] ?? $wo['kode_workorder']) ?></strong>
                    <?php if(!empty($wo['keterangan'])): ?>
                    <small><?= htmlspecialchars($wo['keterangan']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Temuan Section -->
        <?php if(count($ref_temuan) > 0): ?>
        <div class="ref-section-title">
            <i class="fa fa-search"></i> Temuan (<?= count($ref_temuan) ?> item)
        </div>
        <div class="ref-list-container">
            <?php foreach($ref_temuan as $t): ?>
            <div class="ref-list-item">
                <span class="ref-list-icon <?= strtolower($t['status_temuan'] ?? '') == 'selesai' ? 'done' : (strtolower($t['status_temuan'] ?? '') == 'ditolak' ? 'rejected' : 'pending') ?>">
                    <i class="fa <?= strtolower($t['status_temuan'] ?? '') == 'selesai' ? 'fa-check' : (strtolower($t['status_temuan'] ?? '') == 'ditolak' ? 'fa-times' : 'fa-exclamation') ?>"></i>
                </span>
                <div class="ref-list-content">
                    <strong><?= htmlspecialchars($t['nama_temuan_display'] ?? '-') ?></strong>
                    <?php if(!empty($t['deskripsi_temuan'])): ?>
                    <small><?= htmlspecialchars($t['deskripsi_temuan']) ?></small>
                    <?php endif; ?>
                    <small>
                        Urgensi: <span class="urgensi-<?= strtolower($t['tingkat_urgensi'] ?? 'sedang') ?>"><?= htmlspecialchars($t['tingkat_urgensi'] ?? 'sedang') ?></span>
                        | Status: <span class="status-<?= strtolower($t['status_temuan'] ?? 'pending') ?>"><?= htmlspecialchars($t['status_temuan'] ?? 'ditemukan') ?></span>
                    </small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Rejected Items Section -->
        <?php if(count($ref_rejected_items) > 0): ?>
        <div class="ref-section-title rejected">
            <i class="fa fa-ban"></i> Item Ditolak/Tidak Disetujui (<?= count($ref_rejected_items) ?> item)
        </div>
        <div class="ref-list-container rejected">
            <?php foreach($ref_rejected_items as $ri): ?>
            <div class="ref-list-item rejected">
                <span class="ref-list-icon rejected">
                    <i class="fa fa-times"></i>
                </span>
                <div class="ref-list-content">
                    <strong><?= htmlspecialchars($ri['nama_display'] ?? $ri['kode_barang'] ?? $ri['kode_item'] ?? '-') ?></strong>
                    <small>
                        Qty: <?= $ri['quantity'] ?? $ri['qty'] ?? 1 ?>
                        | Harga: Rp <?= number_format($ri['harga_satuan'] ?? $ri['harga'] ?? 0, 0, ',', '.') ?>
                        | Total: Rp <?= number_format($ri['total_harga'] ?? $ri['total'] ?? 0, 0, ',', '.') ?>
                    </small>
                    <?php if(!empty($ri['alasan_reject']) || !empty($ri['keterangan'])): ?>
                    <small class="reject-reason">Alasan: <?= htmlspecialchars($ri['alasan_reject'] ?? $ri['keterangan'] ?? '-') ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Catatan Section -->
        <?php if(!empty($ref_catatan) || !empty($ref_catatan_internal)): ?>
        <div class="ref-section-title">
            <i class="fa fa-sticky-note"></i> Catatan
        </div>
        <div class="ref-catatan-container">
            <?php if(!empty($ref_catatan)): ?>
            <div class="ref-catatan-item">
                <label>Catatan Pelanggan:</label>
                <p><?= nl2br(htmlspecialchars($ref_catatan)) ?></p>
            </div>
            <?php endif; ?>
            <?php if(!empty($ref_catatan_internal)): ?>
            <div class="ref-catatan-item internal">
                <label>Catatan Internal:</label>
                <p><?= nl2br(htmlspecialchars($ref_catatan_internal)) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Barang Section -->
        <div class="ref-section-title">
            <i class="fa fa-cubes"></i> Item Barang (<?= count($ref_barang) ?> item)
        </div>
        
        <?php if(count($ref_barang) > 0): ?>
        <table class="ref-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">Kode</th>
                    <th>Nama Barang</th>
                    <th width="8%" class="text-center">Qty</th>
                    <th width="15%" class="text-right">Harga</th>
                    <th width="15%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 0;
                foreach($ref_barang as $item): 
                    $no++;
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><code style="font-size: 11px;"><?= htmlspecialchars($item['no_item']) ?></code></td>
                    <td><?= htmlspecialchars($item['nama_display']) ?></td>
                    <td class="text-center"><?= $item['quantity'] ?? 1 ?></td>
                    <td class="text-right">Rp <?= number_format($item['harga_jual'] ?? 0, 0, ',', '.') ?></td>
                    <td class="text-right"><strong>Rp <?= number_format($item['total'] ?? 0, 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">TOTAL BARANG</td>
                    <td class="text-right">Rp <?= number_format($ref_total_barang, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="ref-empty">Tidak ada item barang pada servis ini</div>
        <?php endif; ?>
        
        <!-- Jasa Section -->
        <div class="ref-section-title">
            <i class="fa fa-wrench"></i> Item Jasa (<?= count($ref_jasa) ?> item)
        </div>
        
        <?php if(count($ref_jasa) > 0): ?>
        <table class="ref-table">
            <thead>
                <tr>
                    <th width="5%">#</th>
                    <th width="15%">Kode</th>
                    <th>Nama Jasa</th>
                    <th width="10%" class="text-center">Waktu</th>
                    <th width="15%" class="text-right">Harga</th>
                    <th width="15%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 0;
                foreach($ref_jasa as $item): 
                    $no++;
                ?>
                <tr>
                    <td><?= $no ?></td>
                    <td><code style="font-size: 11px;"><?= htmlspecialchars($item['no_item']) ?></code></td>
                    <td><?= htmlspecialchars($item['nama_display']) ?></td>
                    <td class="text-center"><?= ($item['waktu'] ?? 0) ?> mnt</td>
                    <td class="text-right">Rp <?= number_format($item['harga'] ?? 0, 0, ',', '.') ?></td>
                    <td class="text-right"><strong>Rp <?= number_format($item['total'] ?? 0, 0, ',', '.') ?></strong></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">TOTAL JASA</td>
                    <td class="text-right">Rp <?= number_format($ref_total_jasa, 0, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
        <?php else: ?>
        <div class="ref-empty">Tidak ada item jasa pada servis ini</div>
        <?php endif; ?>
        
        <!-- Summary -->
        <div class="ref-summary">
            <div class="ref-summary-item">
                <div class="label">Total Barang</div>
                <div class="value">Rp <?= number_format($ref_total_barang, 0, ',', '.') ?></div>
            </div>
            <div class="ref-summary-item">
                <div class="label">Total Jasa (<?= $ref_total_waktu ?> menit)</div>
                <div class="value">Rp <?= number_format($ref_total_jasa, 0, ',', '.') ?></div>
            </div>
            <div class="ref-summary-item total">
                <div class="label">Grand Total Servis Original</div>
                <div class="value">Rp <?= number_format($ref_grand_total, 0, ',', '.') ?></div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleRefServicePanel() {
    var body = document.getElementById('ref-service-body');
    var icon = document.getElementById('ref-collapse-icon');
    
    if(body.classList.contains('collapsed')) {
        body.classList.remove('collapsed');
        body.style.maxHeight = body.scrollHeight + 'px';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        body.classList.add('collapsed');
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

// Initialize with proper height
document.addEventListener('DOMContentLoaded', function() {
    var body = document.getElementById('ref-service-body');
    if(body) {
        body.style.maxHeight = body.scrollHeight + 'px';
    }
});
</script>
