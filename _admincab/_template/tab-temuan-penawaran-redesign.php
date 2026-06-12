<?php
/**
 * Template REDESIGN: Tab Temuan & Penawaran Part
 * Version: 2.0 - Clean & Focused UI
 *
 * Konsep: Progressive Disclosure - Tampilkan informasi secara bertahap
 *
 * Perubahan utama:
 * 1. Sub-tab untuk memisahkan fungsi (Input, Pending, Riwayat)
 * 2. Form input yang lebih simpel
 * 3. Card-based view untuk pending items
 * 4. Accordion untuk daftar temuan
 * 5. Warna yang lebih soft dan konsisten
 */
?>

<style>
/* ============================================
   REDESIGN STYLES - Clean & Focused UI
   ============================================ */

/* Color Palette - Soft & Consistent */
:root {
    --primary-blue: #4A90D9;
    --success-green: #5CB85C;
    --warning-orange: #F0AD4E;
    --danger-red: #D9534F;
    --neutral-gray: #6C757D;
    --bg-light: #F8F9FA;
    --card-white: #FFFFFF;
    --border-light: #E9ECEF;
    --text-dark: #333333;
    --text-muted: #6C757D;
}

/* Sub-Tab Navigation */
.temuan-subtabs {
    display: flex;
    gap: 4px;
    margin-bottom: 20px;
    background: var(--bg-light);
    padding: 4px;
    border-radius: 8px;
}

.temuan-subtab {
    padding: 10px 18px;
    border: none;
    background: transparent;
    color: var(--text-muted);
    font-weight: 500;
    font-size: 13px;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.temuan-subtab:hover {
    color: var(--primary-blue);
    background: rgba(74, 144, 217, 0.08);
}

.temuan-subtab.active {
    color: var(--primary-blue);
    background: var(--card-white);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.temuan-subtab i {
    font-size: 14px;
}

.temuan-subtab .badge {
    font-size: 10px;
    padding: 2px 7px;
    border-radius: 10px;
    font-weight: 600;
    margin-left: 4px;
}

.temuan-subtab .badge-pending {
    background: var(--warning-orange);
    color: white;
}

.temuan-subtab .badge-count {
    background: var(--border-light);
    color: var(--text-muted);
}

/* Sub-Tab Content */
.temuan-tab-content {
    display: none;
}

.temuan-tab-content.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Clean Card Style */
.clean-card {
    background: var(--card-white);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.clean-card-header {
    padding: 16px 20px;
    background: var(--bg-light);
    border-bottom: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clean-card-header h5 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--text-dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.clean-card-body {
    padding: 20px;
}

/* Simplified Form */
.simple-form .form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.simple-form .form-group {
    flex: 1;
    margin-bottom: 0;
    min-width: 200px;
}

.simple-form label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 8px;
    display: block;
}

.simple-form label span {
    color: var(--danger-red);
}

    width: 100%;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 14px;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}

/* Specific fix for Temuan inputs to prevent cut-off */
#form-input-temuan-v2 .form-control {
    padding: 6px 12px;
    height: auto;
    min-height: 38px;
}

.simple-form .form-control:focus {
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(74, 144, 217, 0.1);
    outline: none;
}

.simple-form select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
    padding-right: 36px;
}

.simple-form .btn-primary-soft {
    background: var(--primary-blue);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.simple-form .btn-primary-soft:hover {
    background: #3A7BC8;
}

/* Pending Item Card */
.pending-card {
    background: var(--card-white);
    border: 1px solid var(--border-light);
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: box-shadow 0.2s, border-color 0.2s;
}

.pending-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border-color: var(--primary-blue);
}

.pending-card.selected {
    background: rgba(92, 184, 92, 0.08);
    border-color: var(--success-green);
}

.pending-card-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.pending-card-checkbox {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.pending-card-info h6 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
}

.pending-card-info small {
    color: var(--text-muted);
    font-size: 12px;
}

.pending-card-right {
    display: flex;
    align-items: center;
    gap: 16px;
}

.pending-card-price {
    font-size: 15px;
    font-weight: 600;
    color: var(--primary-blue);
}

.pending-card-actions {
    display: flex;
    gap: 6px;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: opacity 0.2s;
}

.btn-action:hover {
    opacity: 0.8;
}

.btn-action.approve {
    background: var(--success-green);
    color: white;
}

.btn-action.reject {
    background: var(--danger-red);
    color: white;
}

/* Type Badge */
.type-badge {
    padding: 4px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.type-badge.barang {
    background: rgba(74, 144, 217, 0.15);
    color: var(--primary-blue);
}

.type-badge.jasa {
    background: rgba(155, 89, 182, 0.15);
    color: #9B59B6;
}

/* Quick Actions Bar */
.quick-actions {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    background: var(--bg-light);
    border-radius: 8px;
    margin-bottom: 16px;
    align-items: center;
}

.quick-actions label {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    font-size: 13px;
    color: var(--text-muted);
}

.quick-actions .btn-bulk {
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: opacity 0.2s;
}

.quick-actions .btn-bulk:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quick-actions .btn-bulk.approve {
    background: var(--success-green);
    color: white;
}

.quick-actions .btn-bulk.reject {
    background: var(--danger-red);
    color: white;
}

/* Temuan Accordion */
.temuan-accordion {
    border: 1px solid var(--border-light);
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
}

.temuan-accordion-header {
    padding: 16px 20px;
    background: var(--card-white);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    transition: background 0.2s;
}

.temuan-accordion-header:hover {
    background: var(--bg-light);
}

.temuan-accordion-header.expanded {
    border-bottom: 1px solid var(--border-light);
}

.temuan-accordion-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.temuan-accordion-title h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
}

.temuan-accordion-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.temuan-accordion-meta small {
    color: var(--text-muted);
    font-size: 12px;
}

.temuan-accordion-chevron {
    transition: transform 0.2s;
}

.temuan-accordion-header.expanded .temuan-accordion-chevron {
    transform: rotate(180deg);
}

.temuan-accordion-body {
    display: none;
    padding: 20px;
    background: var(--bg-light);
}

.temuan-accordion-body.show {
    display: block;
}

/* Urgensi Indicator */
.urgensi-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}

.urgensi-dot.rendah { background: var(--success-green); }
.urgensi-dot.sedang { background: var(--warning-orange); }
.urgensi-dot.tinggi { background: #E67E22; }
.urgensi-dot.kritis { background: var(--danger-red); }

/* Status Badge */
.status-badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.status-badge.pending { background: rgba(240, 173, 78, 0.15); color: #D68910; }
.status-badge.disetujui { background: rgba(92, 184, 92, 0.15); color: #27AE60; }
.status-badge.ditolak { background: rgba(217, 83, 79, 0.15); color: #C0392B; }
.status-badge.ditemukan { background: rgba(74, 144, 217, 0.15); color: var(--primary-blue); }

/* Part Offer Item */
.part-offer {
    background: var(--card-white);
    border: 1px solid var(--border-light);
    border-radius: 6px;
    padding: 12px 16px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.part-offer-info {
    flex: 1;
}

.part-offer-info h6 {
    margin: 0 0 4px 0;
    font-size: 13px;
    font-weight: 600;
}

.part-offer-info small {
    color: var(--text-muted);
    font-size: 11px;
}

.part-offer-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

/* Summary Stats */
.summary-stats {
    display: flex;
    gap: 16px;
    padding: 12px 16px;
    background: var(--bg-light);
    border-radius: 6px;
    margin-top: 12px;
}

.summary-stat {
    text-align: center;
    flex: 1;
}

.summary-stat small {
    display: block;
    color: var(--text-muted);
    font-size: 11px;
    margin-bottom: 4px;
}

.summary-stat strong {
    font-size: 18px;
}

.summary-stat strong.pending { color: var(--warning-orange); }
.summary-stat strong.approved { color: var(--success-green); }

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state h6 {
    margin: 0 0 8px 0;
    color: var(--text-dark);
}

/* Fast Moves Modal Redesign */
.fastmoves-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
}

.fastmoves-category {
    padding: 8px 16px;
    border: 1px solid var(--border-light);
    border-radius: 20px;
    background: var(--card-white);
    color: var(--text-muted);
    font-size: 13px;
    cursor: pointer;
    transition: all 0.2s;
}

.fastmoves-category:hover,
.fastmoves-category.active {
    background: var(--primary-blue);
    color: white;
    border-color: var(--primary-blue);
}

.fastmoves-part-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border: 1px solid var(--border-light);
    border-radius: 6px;
    margin-bottom: 8px;
    background: var(--card-white);
}

.fastmoves-part-info h6 {
    margin: 0 0 4px 0;
    font-size: 14px;
}

.fastmoves-part-info small {
    color: var(--text-muted);
}

.fastmoves-part-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

.fastmoves-part-actions input[type="number"] {
    width: 60px;
    text-align: center;
    padding: 6px;
    border: 1px solid var(--border-light);
    border-radius: 4px;
}

/* Temuan Redesign Container */
.temuan-redesign-container {
    background: var(--card-white);
    border-radius: 8px;
    padding: 20px;
}

/* Fix for rd-input-group with local classes */
.rd-input-group {
    display: flex;
    width: 100%;
}
.rd-input-group .form-control {
    flex: 1;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
.rd-input-group .btn-primary-soft {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
    flex-shrink: 0;
}

/* Fix rd-form-row for Urgensi & Jenis */
.rd-form-row {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.rd-form-group {
    min-width: 150px;
}

.rd-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 8px;
    display: block;
}

.rd-select {
    width: 100%;
    min-width: 150px;
}

/* Fix dropdown overflow */
.clean-card-body {
    overflow: visible !important;
}

.clean-card {
    overflow: visible !important;
}

/* Ensure select dropdown shows properly */
.simple-form select.form-control,
.rd-select {
    position: relative;
    z-index: 1;
}

/* Responsive */
@media (max-width: 768px) {
    .temuan-subtabs {
        overflow-x: auto;
    }

    .temuan-subtab {
        white-space: nowrap;
        padding: 10px 16px;
    }

    .simple-form .form-row {
        flex-direction: column;
    }

    .simple-form .form-group {
        min-width: 100% !important;
        flex: 1 1 100% !important;
    }

    .pending-card {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }

    .pending-card-right {
        width: 100%;
        justify-content: space-between;
    }
}
</style>

<?php
// Count data untuk badge
$count_pending_wo = 0;
$count_history_wo = 0;
$count_temuan = 0;

// Query pending items dari Work Order
$sql_pending = "SELECT COUNT(*) as total FROM tbservis_pending_items
                WHERE no_service = '$no_service' AND status_approval = 'pending'";
$result_count = mysqli_query($koneksi, $sql_pending);
if($result_count) {
    $count_pending_wo = mysqli_fetch_array($result_count)['total'];
}

// Query history items
$sql_history = "SELECT COUNT(*) as total FROM tbservis_pending_items
                WHERE no_service = '$no_service' AND status_approval IN ('disetujui','ditolak')";
$result_count = mysqli_query($koneksi, $sql_history);
if($result_count) {
    $count_history_wo = mysqli_fetch_array($result_count)['total'];
}

// Query temuan
$sql_temuan_count = "SELECT COUNT(*) as total FROM tbservis_temuan WHERE no_service='$no_service'";
$result_count = mysqli_query($koneksi, $sql_temuan_count);
if($result_count) {
    $count_temuan = mysqli_fetch_array($result_count)['total'];
}
?>

<!-- ============================================ -->
<!-- REDESIGNED TAB: Temuan & Penawaran -->
<!-- ============================================ -->

<?php
// Auto-focus pending tab logic
$count_pending_wo = 0;
if(!empty($no_service)) {
    $q_cp = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbservis_pending_items WHERE no_service='$no_service' AND status_approval='pending'");
    if($q_cp) { $row_cp = mysqli_fetch_assoc($q_cp); $count_pending_wo = $row_cp['total']; }
}
$active_subtab = ($count_pending_wo > 0) ? 'tab-pending' : 'tab-input';
?>
<div class="temuan-redesign-container">

    <!-- Sub-Tab Navigation with Export Button -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 20px;">
        <div class="temuan-subtabs" style="margin-bottom: 0;">
            <button type="button" class="temuan-subtab <?= $active_subtab == 'tab-input' ? 'active' : '' ?>" data-target="tab-input">
                <i class="fa fa-plus-circle"></i>
                Input Temuan
            </button>
            <button type="button" class="temuan-subtab <?= $active_subtab == 'tab-pending' ? 'active' : '' ?>" data-target="tab-pending">
                <i class="fa fa-clock-o"></i>
                Pending
                <?php if($count_pending_wo > 0): ?>
                <span class="badge badge-pending"><?= $count_pending_wo ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="temuan-subtab" data-target="tab-temuan">
                <i class="fa fa-list-alt"></i>
                Daftar Temuan
                <?php if($count_temuan > 0): ?>
                <span class="badge badge-count"><?= $count_temuan ?></span>
                <?php endif; ?>
            </button>
            <button type="button" class="temuan-subtab" data-target="tab-history">
                <i class="fa fa-history"></i>
                Riwayat
                <?php if($count_history_wo > 0): ?>
                <span class="badge badge-count"><?= $count_history_wo ?></span>
                <?php endif; ?>
            </button>
        </div>
        
        <!-- Export Estimasi Button -->
        <a href="temuan-penawaran-export.php?format=estimasi&no_service=<?= $no_service ?>" 
           target="_blank"
           class="btn-export-estimasi"
           title="Export Estimasi Biaya Servis ke PDF">
            <i class="fa fa-file-pdf-o"></i>
            Export Estimasi PDF
        </a>
    </div>
    
    <style>
    .btn-export-estimasi {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 16px;
        background: linear-gradient(135deg, #d32f2f, #b71c1c);
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(211, 47, 47, 0.3);
    }
    .btn-export-estimasi:hover {
        background: linear-gradient(135deg, #b71c1c, #8b0000);
        color: white;
        text-decoration: none;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(211, 47, 47, 0.4);
    }
    .btn-export-estimasi i {
        font-size: 14px;
    }
    </style>

    <!-- ========================================== -->
    <!-- TAB 1: Input Temuan (Simplified Form) -->
    <!-- ========================================== -->
    <div id="tab-input" class="temuan-tab-content <?= $active_subtab == 'tab-input' ? 'active' : '' ?>">
        <div class="clean-card">
            <div class="clean-card-header">
                <h5><i class="fa fa-search-plus"></i> Input Temuan Baru</h5>
            </div>
            <div class="clean-card-body">
                <form id="form-input-temuan-v2" class="simple-form" method="POST">
                    <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">
                    <input type="hidden" name="btnaddtemuan" value="1">
                    <input type="hidden" name="kode_temuan" id="kode_temuan_v2" value="">
                    <input type="hidden" name="nama_temuan" id="nama_temuan_v2" value="">
                    <input type="hidden" name="selected_parts_payload" id="selected_parts_payload_v2" value="">
                    <input type="hidden" name="selected_jasa_payload" id="selected_jasa_payload_v2" value="">

                    <!-- Row 1: Keluhan -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Keluhan Terkait <span style="color: var(--danger-red);">*</span></label>
                            <select name="keluhan_id" id="keluhan_id_v2" class="form-control">
                                <option value="">-- Pilih Keluhan --</option>
                                <?php
                                $sql_keluhan = mysqli_query($koneksi, "SELECT id, keluhan, status_pengerjaan
                                                                        FROM tbservis_keluhan_status
                                                                        WHERE no_service='$no_service'
                                                                        ORDER BY id ASC");
                                while($keluhan = mysqli_fetch_array($sql_keluhan)) {
                                    $status_icon = $keluhan['status_pengerjaan'] == 'selesai' ? ' ✓' : '';
                                    echo "<option value='{$keluhan['id']}'>" . htmlspecialchars($keluhan['keluhan']) . $status_icon . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- Row 2: Temuan -->
                    <!-- Row 2: Temuan -->
                    <div style="margin-bottom: 16px;">
                        <label style="display:block; margin-bottom: 8px; font-weight: 600;">Temuan <span style="color: var(--danger-red);">*</span></label>
                        <div class="rd-input-group" style="display: flex; width: 100%;">
                            <input type="text" id="nama_temuan_display_v2" class="form-control"
                                   placeholder="Ketik atau pilih temuan..." style="flex: 1; border-top-right-radius: 0; border-bottom-right-radius: 0;">
                            <button type="button" class="btn-primary-soft" onclick="$('#modalSearchTemuan').modal('show');" style="white-space: nowrap; border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                <i class="fa fa-search"></i> Cari
                            </button>
                        </div>
                    </div>

                    <!-- Row 3: Urgensi & Jenis Perbaikan -->
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display:block; margin-bottom: 8px; font-weight: 600;">Urgensi</label>
                            <select name="tingkat_urgensi" id="tingkat_urgensi_v2" class="form-control" style="width: 100%;">
                                <option value="rendah">Rendah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="tinggi">Tinggi</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; margin-bottom: 8px; font-weight: 600;">Jenis Perbaikan</label>
                            <select name="jenis_perbaikan" id="jenis_perbaikan_v2" class="form-control" style="width: 100%;"
                                    onchange="togglePartSuggestionV2(this.value)">
                                <option value="setting">Hanya Setting/Servis</option>
                                <option value="penggantian_part">Penggantian Part</option>
                            </select>
                        </div>
                    </div>

                    <!-- Area Jasa yang Disarankan (untuk jenis_perbaikan = setting) -->
                    <div id="area-suggested-jasa-v2" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <label style="margin: 0;"><i class="fa fa-wrench"></i> Jasa yang Disarankan</label>
                            <small class="text-muted">Centang jasa yang akan ditambahkan bersama temuan</small>
                        </div>
                        <div id="suggested-jasa-container-v2">
                            <div class="empty-state" style="padding: 20px;">
                                <i class="fa fa-info-circle"></i>
                                <p style="margin: 0; font-size: 13px;">Pilih temuan untuk melihat saran jasa</p>
                            </div>
                        </div>
                    </div>

                    <!-- Area Penawaran Part (for jenis_perbaikan = penggantian_part) -->
                    <div id="area-penawaran-part-v2" style="display: none; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border-light);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                            <label style="margin: 0;"><i class="fa fa-shopping-cart"></i> Part yang Disarankan</label>
                            <small class="text-muted">Centang part yang akan ditambahkan bersama temuan</small>
                        </div>
                        <div id="suggested-parts-container-v2">
                            <div class="empty-state" style="padding: 20px;">
                                <i class="fa fa-info-circle"></i>
                                <p style="margin: 0; font-size: 13px;">Pilih temuan untuk melihat saran part</p>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Items Summary -->
                    <div id="selected-items-summary-v2" style="display: none; margin-top: 16px; padding: 12px; background: #e8f5e9; border-radius: 8px; border: 1px solid #c8e6c9;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <i class="fa fa-check-circle" style="color: #4caf50; font-size: 18px;"></i>
                            <div>
                                <strong id="selected-count-text-v2">0 item dipilih</strong>
                                <p style="margin: 0; font-size: 12px; color: #666;">Item yang dipilih akan otomatis ditambahkan ke daftar temuan & penawaran saat Simpan Temuan</p>
                            </div>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div style="margin-top: 20px; text-align: right;">
                        <button type="button" class="btn-primary-soft" onclick="submitTemuanV2()">
                            <i class="fa fa-save"></i> Simpan Temuan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tambah Penawaran Part (Collapsed by default) -->
        <div class="clean-card">
            <div class="clean-card-header" style="cursor: pointer;" onclick="togglePenawaranForm()">
                <h5><i class="fa fa-shopping-cart"></i> Tambah Penawaran Part Langsung</h5>
                <i class="fa fa-chevron-down" id="penawaran-form-chevron"></i>
            </div>
            <div class="clean-card-body" id="penawaran-form-body" style="display: none;">
                <form id="formTambahPenawaranV2" class="simple-form" method="POST">
                    <input type="hidden" name="txtnosrv" value="<?= $no_service ?>">
                    <input type="hidden" name="btnaddpenawaran" value="1">

                    <div class="form-row">
                        <div class="form-group">
                            <label>Temuan Terkait</label>
                            <select name="temuan_id" class="form-control">
                                <option value="">-- Tanpa Temuan --</option>
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
                        <div class="form-group" style="flex: 0 0 auto;">
                            <label>&nbsp;</label>
                            <div style="display: flex; gap: 8px;">
                                <button type="button" class="btn-primary-soft" onclick="$('#modalFastMovesV2').modal('show');">
                                    <i class="fa fa-bolt"></i> Fast Moves
                                </button>
                                <button type="button" style="padding: 10px 16px; background: var(--warning-orange); color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 500;"
                                        onclick="$('#modalInputCustom').modal('show');">
                                    <i class="fa fa-plus-circle"></i> Barang Custom
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="flex: 0 0 150px;">
                            <label>Kode Part</label>
                            <input type="text" class="form-control" id="kode_barang_penawaran_v2" name="kode_barang" placeholder="Kode" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Part</label>
                            <input type="text" class="form-control" id="nama_barang_penawaran_v2" name="nama_barang" placeholder="Nama part" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Harga</label>
                            <input type="number" class="form-control" id="harga_satuan_penawaran_v2" name="harga_satuan" placeholder="0" min="0" required>
                        </div>
                        <div class="form-group" style="flex: 0 0 100px;">
                            <label>Qty</label>
                            <input type="number" class="form-control" id="quantity_penawaran_v2" name="quantity" value="1" min="1" required>
                        </div>
                        <div class="form-group">
                            <label>Total</label>
                            <input type="text" class="form-control" id="total_penawaran_display_v2" value="Rp 0" readonly style="background: var(--bg-light);">
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <button type="submit" class="btn-primary-soft">
                            <i class="fa fa-save"></i> Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- TAB 2: Pending Items -->
    <!-- ========================================== -->
    <div id="tab-pending" class="temuan-tab-content <?= $active_subtab == 'tab-pending' ? 'active' : '' ?>">
        <?php
        // Query pending items
        $sql_pending = "SELECT pi.*, sw.kode_wo, wh.nama_wo
                        FROM tbservis_pending_items pi
                        LEFT JOIN tbservis_workorder sw ON pi.wo_id = sw.id
                        LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                        WHERE pi.no_service = '$no_service' AND pi.status_approval = 'pending'
                        ORDER BY pi.created_at DESC";
        $result_pending = mysqli_query($koneksi, $sql_pending);
        $total_pending = $result_pending ? mysqli_num_rows($result_pending) : 0;

        if($total_pending > 0):
        ?>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <label>
                <input type="checkbox" id="select_all_pending_v2" onclick="toggleSelectAllPendingV2(this)">
                Pilih Semua
            </label>
            <div style="flex: 1;"></div>
            <button type="button" class="btn-bulk approve" id="btnBulkApproveV2" onclick="bulkApprovePendingV2()" disabled>
                <i class="fa fa-check"></i> Setujui (<span id="selected_count_v2">0</span>)
            </button>
            <button type="button" class="btn-bulk reject" id="btnBulkRejectV2" onclick="bulkRejectPendingV2()" disabled>
                <i class="fa fa-times"></i> Tolak
            </button>
        </div>

        <!-- Pending Cards -->
        <div id="pending-cards-container">
            <?php
            mysqli_data_seek($result_pending, 0);
            while($pending = mysqli_fetch_array($result_pending)):
                $wo_display = $pending['nama_wo'] ?: $pending['kode_wo'];
            ?>
            <div class="pending-card" data-id="<?= $pending['id'] ?>">
                <div class="pending-card-left">
                    <input type="checkbox" class="pending-card-checkbox pending-select-v2"
                           value="<?= $pending['id'] ?>" onchange="updatePendingSelectionV2()">
                    <div class="pending-card-info">
                        <h6><?= htmlspecialchars($pending['nama_item']) ?></h6>
                        <small>
                            <span class="type-badge <?= $pending['tipe'] ?>"><?= ucfirst($pending['tipe']) ?></span>
                            &nbsp;<?= htmlspecialchars($wo_display) ?>
                            &nbsp;|&nbsp;Qty: <?= $pending['quantity'] ?>
                        </small>
                    </div>
                </div>
                <div class="pending-card-right">
                    <span class="pending-card-price">Rp <?= number_format($pending['total'], 0, ',', '.') ?></span>
                    <div class="pending-card-actions">
                            <button type="button" class="btn-action approve"
                                    onclick="approveSingleItemV2('<?= $pending['id'] ?>', '<?= $no_service ?>')" 
                                    title="Setujui">
                                <i class="fa fa-check"></i>
                            </button>
                        <button type="button" class="btn-action reject"
                                onclick="showRejectModalV2(<?= $pending['id'] ?>, '<?= addslashes($pending['nama_item']) ?>')"
                                title="Tolak">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-check-circle"></i>
            <h6>Tidak Ada Item Pending</h6>
            <p>Semua item dari Work Order sudah diproses</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================== -->
    <!-- TAB 3: Daftar Temuan -->
    <!-- ========================================== -->
    <div id="tab-temuan" class="temuan-tab-content">
        <?php
        // Check if tbservis_penawaran_jasa table exists
        $check_jasa_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'tbservis_penawaran_jasa'");
        $jasa_table_exists = ($check_jasa_table && mysqli_num_rows($check_jasa_table) > 0);

        // Query temuan dengan penawaran part dan jasa
        if($jasa_table_exists) {
            $sql_temuan = "SELECT t.*, k.keluhan,
                            COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display,
                            mt.kategori as kategori_temuan,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id) as total_penawaran_part,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='pending') as pending_part_count,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='disetujui') as approved_part_count,
                            (SELECT COUNT(*) FROM tbservis_penawaran_jasa WHERE temuan_id = t.id) as total_penawaran_jasa,
                            (SELECT COUNT(*) FROM tbservis_penawaran_jasa WHERE temuan_id = t.id AND status_penawaran='pending') as pending_jasa_count,
                            (SELECT COUNT(*) FROM tbservis_penawaran_jasa WHERE temuan_id = t.id AND status_penawaran='disetujui') as approved_jasa_count
                          FROM tbservis_temuan t
                          LEFT JOIN tbservis_keluhan_status k ON t.keluhan_id = k.id
                          LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                          WHERE t.no_service = '$no_service'
                          ORDER BY t.created_at DESC";
        } else {
            // Fallback query without jasa table
            $sql_temuan = "SELECT t.*, k.keluhan,
                            COALESCE(mt.nama_temuan, t.temuan_custom) as nama_temuan_display,
                            mt.kategori as kategori_temuan,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id) as total_penawaran_part,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='pending') as pending_part_count,
                            (SELECT COUNT(*) FROM tbservis_penawaran_part WHERE temuan_id = t.id AND status_penawaran='disetujui') as approved_part_count,
                            0 as total_penawaran_jasa,
                            0 as pending_jasa_count,
                            0 as approved_jasa_count
                          FROM tbservis_temuan t
                          LEFT JOIN tbservis_keluhan_status k ON t.keluhan_id = k.id
                          LEFT JOIN tbmaster_temuan mt ON t.kode_temuan = mt.kode_temuan
                          WHERE t.no_service = '$no_service'
                          ORDER BY t.created_at DESC";
        }
        $result_temuan = mysqli_query($koneksi, $sql_temuan);

        if($result_temuan && mysqli_num_rows($result_temuan) > 0):
            $no_temuan = 0;
            while($temuan = mysqli_fetch_array($result_temuan)):
                $no_temuan++;
        ?>

        <div class="temuan-accordion">
            <div class="temuan-accordion-header" onclick="toggleAccordionV2(this)">
                <div class="temuan-accordion-title">
                    <span class="urgensi-dot <?= $temuan['tingkat_urgensi'] ?>"></span>
                    <h6>#<?= $no_temuan ?>: <?= htmlspecialchars($temuan['nama_temuan_display']) ?></h6>
                    <?php if($temuan['kategori_temuan']): ?>
                    <span class="status-badge ditemukan"><?= $temuan['kategori_temuan'] ?></span>
                    <?php endif; ?>
                    <span class="status-badge <?= $temuan['status_temuan'] ?>"><?= ucfirst($temuan['status_temuan']) ?></span>
                </div>
                <div class="temuan-accordion-meta">
                    <?php if($temuan['total_penawaran_part'] > 0): ?>
                    <small><i class="fa fa-shopping-cart"></i> <?= $temuan['total_penawaran_part'] ?> part</small>
                    <?php endif; ?>
                    <?php if($temuan['total_penawaran_jasa'] > 0): ?>
                    <small><i class="fa fa-wrench"></i> <?= $temuan['total_penawaran_jasa'] ?> jasa</small>
                    <?php endif; ?>
                    <small><?= date('d/m/Y H:i', strtotime($temuan['created_at'])) ?></small>
                    <i class="fa fa-chevron-down temuan-accordion-chevron"></i>
                </div>
            </div>

            <div class="temuan-accordion-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <!-- Left: Info -->
                    <div>
                        <table style="width: 100%; font-size: 13px;">
                            <tr>
                                <td style="padding: 6px 0; color: var(--text-muted); width: 120px;">Keluhan:</td>
                                <td style="padding: 6px 0;"><strong><?= htmlspecialchars($temuan['keluhan']) ?></strong></td>
                            </tr>
                            <tr>
                                <td style="padding: 6px 0; color: var(--text-muted);">Jenis:</td>
                                <td style="padding: 6px 0;">
                                    <?= $temuan['jenis_perbaikan'] == 'setting' ? 'Setting/Servis' : 'Penggantian Part' ?>
                                </td>
                            </tr>
                        </table>

                        <?php if($temuan['deskripsi_temuan']): ?>
                        <div style="margin-top: 12px; padding: 12px; background: var(--card-white); border-radius: 6px; font-size: 13px;">
                            <strong>Deskripsi:</strong><br>
                            <?= nl2br(htmlspecialchars($temuan['deskripsi_temuan'])) ?>
                        </div>
                        <?php endif; ?>

                        <div style="margin-top: 16px; display: flex; gap: 8px;">
                            <?php if($temuan['jenis_perbaikan'] == 'penggantian_part'): ?>
                            <button type="button" class="btn-primary-soft" style="padding: 6px 12px; font-size: 12px;"
                                    onclick="addMoreParts(<?= $temuan['id'] ?>)">
                                <i class="fa fa-plus"></i> Tambah Part
                            </button>
                            <?php endif; ?>
                            <button type="button" style="padding: 6px 12px; font-size: 12px; background: var(--neutral-gray); color: white; border: none; border-radius: 6px; cursor: pointer;"
                                    onclick="editTemuan(<?= $temuan['id'] ?>)">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <button type="button" style="padding: 6px 12px; font-size: 12px; background: var(--danger-red); color: white; border: none; border-radius: 6px; cursor: pointer;"
                                    onclick="deleteTemuan(<?= $temuan['id'] ?>)">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>

                    <!-- Right: Penawaran Part & Jasa -->
                    <div>
                        <!-- Penawaran Part -->
                        <h6 style="margin: 0 0 12px 0; font-size: 13px; color: var(--text-muted);">
                            <i class="fa fa-shopping-cart"></i> Penawaran Part
                        </h6>

                        <?php
                        $sql_penawaran = "SELECT * FROM tbservis_penawaran_part WHERE temuan_id = '{$temuan['id']}' ORDER BY created_at ASC";
                        $result_penawaran = mysqli_query($koneksi, $sql_penawaran);

                        if(mysqli_num_rows($result_penawaran) > 0):
                            while($penawaran = mysqli_fetch_array($result_penawaran)):
                        ?>
                        <div class="part-offer">
                            <div class="part-offer-info">
                                <h6><?= htmlspecialchars($penawaran['nama_barang']) ?></h6>
                                <small>Kode: <?= $penawaran['kode_barang'] ?> | Qty: <?= $penawaran['quantity'] ?></small>
                            </div>
                            <div class="part-offer-actions">
                                <strong style="color: var(--primary-blue);">Rp <?= number_format($penawaran['total_harga'], 0, ',', '.') ?></strong>
                                <?php if($penawaran['status_penawaran'] == 'pending'): ?>
                                <button type="button" class="btn-action approve" style="width: 28px; height: 28px;"
                                        onclick="approvePenawaran(<?= $penawaran['id'] ?>)">
                                    <i class="fa fa-check"></i>
                                </button>
                                <button type="button" class="btn-action reject" style="width: 28px; height: 28px;"
                                        onclick="rejectPenawaran(<?= $penawaran['id'] ?>)">
                                    <i class="fa fa-times"></i>
                                </button>
                                <?php else: ?>
                                <span class="status-badge <?= $penawaran['status_penawaran'] ?>"><?= ucfirst($penawaran['status_penawaran']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                            endwhile;
                        else:
                            if($temuan['jenis_perbaikan'] == 'penggantian_part'):
                        ?>
                        <div style="padding: 10px; text-align: center; color: var(--text-muted); font-size: 12px;">
                            <i class="fa fa-info-circle"></i> Belum ada penawaran part
                        </div>
                        <?php else: ?>
                        <div style="padding: 10px; text-align: center; color: var(--text-muted); font-size: 12px;">
                            <i class="fa fa-minus"></i> Tidak ada penawaran part
                        </div>
                        <?php
                            endif;
                        endif;
                        ?>

                        <?php if($temuan['total_penawaran_part'] > 0): ?>
                        <div class="summary-stats" style="margin-bottom: 16px;">
                            <div class="summary-stat">
                                <small>Pending</small>
                                <strong class="pending"><?= $temuan['pending_part_count'] ?></strong>
                            </div>
                            <div class="summary-stat">
                                <small>Disetujui</small>
                                <strong class="approved"><?= $temuan['approved_part_count'] ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Penawaran Jasa -->
                        <h6 style="margin: 16px 0 12px 0; font-size: 13px; color: var(--text-muted); padding-top: 12px; border-top: 1px dashed var(--border-light);">
                            <i class="fa fa-wrench"></i> Penawaran Jasa
                        </h6>

                        <?php
                        if($jasa_table_exists) {
                            $sql_penawaran_jasa = "SELECT * FROM tbservis_penawaran_jasa WHERE temuan_id = '{$temuan['id']}' ORDER BY created_at ASC";
                            $result_penawaran_jasa = mysqli_query($koneksi, $sql_penawaran_jasa);
                        } else {
                            $result_penawaran_jasa = false;
                        }

                        if($result_penawaran_jasa && mysqli_num_rows($result_penawaran_jasa) > 0):
                            while($penawaran_jasa = mysqli_fetch_array($result_penawaran_jasa)):
                        ?>
                        <div class="part-offer" style="border-left: 3px solid #9B59B6;">
                            <div class="part-offer-info">
                                <h6><?= htmlspecialchars($penawaran_jasa['nama_jasa']) ?></h6>
                                <small>Kode: <?= $penawaran_jasa['kode_jasa'] ?><?= $penawaran_jasa['waktu_estimasi'] ? ' | '.$penawaran_jasa['waktu_estimasi'].' mnt' : '' ?></small>
                            </div>
                            <div class="part-offer-actions">
                                <strong style="color: #9B59B6;">Rp <?= number_format($penawaran_jasa['harga'], 0, ',', '.') ?></strong>
                                <?php if($penawaran_jasa['status_penawaran'] == 'pending'): ?>
                                <button type="button" class="btn-action approve" style="width: 28px; height: 28px;"
                                        onclick="approvePenawaranJasa(<?= $penawaran_jasa['id'] ?>)">
                                    <i class="fa fa-check"></i>
                                </button>
                                <button type="button" class="btn-action reject" style="width: 28px; height: 28px;"
                                        onclick="rejectPenawaranJasa(<?= $penawaran_jasa['id'] ?>)">
                                    <i class="fa fa-times"></i>
                                </button>
                                <?php else: ?>
                                <span class="status-badge <?= $penawaran_jasa['status_penawaran'] ?>"><?= ucfirst($penawaran_jasa['status_penawaran']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <div style="padding: 10px; text-align: center; color: var(--text-muted); font-size: 12px;">
                            <i class="fa fa-info-circle"></i> Belum ada penawaran jasa
                        </div>
                        <?php endif; ?>

                        <?php if($temuan['total_penawaran_jasa'] > 0): ?>
                        <div class="summary-stats">
                            <div class="summary-stat">
                                <small>Pending</small>
                                <strong class="pending"><?= $temuan['pending_jasa_count'] ?></strong>
                            </div>
                            <div class="summary-stat">
                                <small>Disetujui</small>
                                <strong class="approved"><?= $temuan['approved_jasa_count'] ?></strong>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
            endwhile;
        else:
        ?>
        <div class="empty-state">
            <i class="fa fa-search"></i>
            <h6>Belum Ada Temuan</h6>
            <p>Silakan input temuan di tab "Input Temuan"</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- ========================================== -->
    <!-- TAB 4: Riwayat -->
    <!-- ========================================== -->
    <div id="tab-history" class="temuan-tab-content">
        <?php
        $sql_history = "SELECT pi.*, sw.kode_wo, wh.nama_wo
                        FROM tbservis_pending_items pi
                        LEFT JOIN tbservis_workorder sw ON pi.wo_id = sw.id
                        LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
                        WHERE pi.no_service = '$no_service' AND pi.status_approval IN ('disetujui','ditolak')
                        ORDER BY pi.created_at DESC";
        $result_history = mysqli_query($koneksi, $sql_history);

        if(mysqli_num_rows($result_history) > 0):
        ?>
        <div class="clean-card">
            <div class="clean-card-header">
                <h5><i class="fa fa-history"></i> Riwayat Approval Work Order</h5>
            </div>
            <div class="clean-card-body" style="padding: 0;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                    <thead>
                        <tr style="background: var(--bg-light);">
                            <th style="padding: 12px 16px; text-align: left; font-weight: 600;">Item</th>
                            <th style="padding: 12px 16px; text-align: center; width: 100px;">Tipe</th>
                            <th style="padding: 12px 16px; text-align: center; width: 80px;">Qty</th>
                            <th style="padding: 12px 16px; text-align: right; width: 120px;">Total</th>
                            <th style="padding: 12px 16px; text-align: center; width: 100px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($h = mysqli_fetch_array($result_history)): ?>
                        <tr style="border-bottom: 1px solid var(--border-light);">
                            <td style="padding: 12px 16px;">
                                <strong><?= htmlspecialchars($h['nama_item']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= htmlspecialchars($h['nama_wo'] ?: $h['kode_wo']) ?></small>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="type-badge <?= $h['tipe'] ?>"><?= ucfirst($h['tipe']) ?></span>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;"><?= $h['quantity'] ?></td>
                            <td style="padding: 12px 16px; text-align: right; font-weight: 600;">
                                Rp <?= number_format($h['total'], 0, ',', '.') ?>
                            </td>
                            <td style="padding: 12px 16px; text-align: center;">
                                <span class="status-badge <?= $h['status_approval'] ?>"><?= ucfirst($h['status_approval']) ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fa fa-history"></i>
            <h6>Belum Ada Riwayat</h6>
            <p>Riwayat approval akan muncul di sini</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<!-- Modal Reject -->
<div class="modal fade" id="modalRejectItemV2" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div id="wrapper-reject-v2">
                <input type="hidden" name="btnreject_wo_item" value="1">
                <input type="hidden" id="reject_item_id_v2" value="">
                
                <div class="modal-header" style="background: var(--danger-red); color: white;">
                    <button type="button" class="close" data-dismiss="modal" style="color: white;">&times;</button>
                    <h5 class="modal-title"><i class="fa fa-times-circle"></i> Tolak Item</h5>
                </div>
                <div class="modal-body">
                    <p style="margin-bottom: 16px;"><strong id="reject_item_name_v2"></strong></p>
                    <div class="form-group">
                        <label>Alasan</label>
                        <select id="alasan_reject_v2" class="form-control">
                            <option value="">-- Pilih --</option>
                            <option value="customer_tidak_mau">Customer tidak setuju</option>
                            <option value="stok_cabang_kosong">Stok tidak ada</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Keterangan (opsional)</label>
                        <textarea id="keterangan_reject_v2" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-sm" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="submitRejectItemV2()">Tolak</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// ============================================
// JAVASCRIPT FUNCTIONS - Redesign V2
// ============================================

// Sub-tab navigation
document.querySelectorAll('.temuan-subtab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        // Remove active from all tabs
        document.querySelectorAll('.temuan-subtab').forEach(function(t) {
            t.classList.remove('active');
        });
        document.querySelectorAll('.temuan-tab-content').forEach(function(c) {
            c.classList.remove('active');
        });

        // Add active to clicked tab
        this.classList.add('active');
        var target = this.getAttribute('data-target');
        document.getElementById(target).classList.add('active');
    });
});

// Toggle penawaran form
function togglePenawaranForm() {
    var body = document.getElementById('penawaran-form-body');
    var chevron = document.getElementById('penawaran-form-chevron');
    if(body.style.display === 'none') {
        body.style.display = 'block';
        chevron.classList.add('fa-chevron-up');
        chevron.classList.remove('fa-chevron-down');
    } else {
        body.style.display = 'none';
        chevron.classList.add('fa-chevron-down');
        chevron.classList.remove('fa-chevron-up');
    }
}

// Toggle accordion
function toggleAccordionV2(header) {
    var body = header.nextElementSibling;
    header.classList.toggle('expanded');
    body.classList.toggle('show');
}

// Toggle part suggestion area
function togglePartSuggestionV2(value) {
    var areaPart = document.getElementById('area-penawaran-part-v2');
    var areaJasa = document.getElementById('area-suggested-jasa-v2');
    var kodeTemuan = document.getElementById('kode_temuan_v2').value;
    var namaTemuan = document.getElementById('nama_temuan_display_v2').value.trim();

    // Always show jasa area if temuan is selected
    if(namaTemuan) {
        areaJasa.style.display = 'block';
        if(kodeTemuan) {
            loadSuggestedJasaV2(kodeTemuan);
        } else {
            loadSuggestedJasaV2(namaTemuan); // fallback to search by name
        }
    } else {
        areaJasa.style.display = 'none';
    }

    if(value === 'penggantian_part') {
        areaPart.style.display = 'block';
        // Auto-load suggested parts if temuan sudah dipilih
        if(kodeTemuan) {
            loadSuggestedPartsV2(kodeTemuan);
        } else if(namaTemuan) {
            loadSuggestedPartsV2(namaTemuan);
        }
    } else {
        areaPart.style.display = 'none';
    }

    updateSelectedItemsSummary();
}

// Load suggested jasa from master mapping
function loadSuggestedJasaV2(kodeTemuan) {
    var container = document.getElementById('suggested-jasa-container-v2');
    if(!container) return;

    container.innerHTML = '<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin"></i> Memuat saran jasa...</div>';

    jQuery.ajax({
        url: '_handler_temuan_penawaran.php',
        type: 'GET',
        data: { action: 'get_suggested_jasa', kode_temuan: kodeTemuan },
        dataType: 'json',
        success: function(response) {
            if(response.success && response.data && response.data.length > 0) {
                var html = '<div class="suggested-jasa-list">';
                html += '<table class="table table-sm table-hover" style="margin: 0; font-size: 13px;">';
                html += '<thead style="background: var(--bg-light);"><tr>';
                html += '<th style="width:40px;"><input type="checkbox" onclick="toggleAllJasaV2(this)" title="Pilih semua"></th>';
                html += '<th>Kode</th><th>Nama Jasa</th><th class="text-right">Harga</th><th class="text-center">Waktu</th>';
                html += '</tr></thead><tbody>';

                response.data.forEach(function(jasa, idx) {
                    var isPrimary = jasa.is_primary == 1 ? '<span class="badge badge-success" style="font-size:9px;">Utama</span>' : '';
                    var waktuText = jasa.waktu_estimasi ? jasa.waktu_estimasi + ' menit' : '-';

                    html += '<tr>';
                    html += '<td><input type="checkbox" class="jasa-checkbox-v2" data-kode="' + jasa.noitem + '" data-nama="' + escapeHtml(jasa.namaitem || jasa.noitem) + '" data-harga="' + (jasa.hargajual || 0) + '" data-waktu="' + (jasa.waktu_estimasi || 0) + '" data-mapping-id="' + (jasa.mapping_id || '') + '" onchange="updateSelectedItemsSummary()" ' + (jasa.is_primary == 1 ? 'checked' : '') + '></td>';
                    html += '<td><code>' + jasa.noitem + '</code> ' + isPrimary + '</td>';
                    html += '<td>' + (jasa.namaitem || jasa.noitem);
                    if(jasa.keterangan) {
                        html += '<br><small class="text-muted">' + jasa.keterangan + '</small>';
                    }
                    html += '</td>';
                    html += '<td class="text-right">Rp ' + parseInt(jasa.hargajual || 0).toLocaleString('id-ID') + '</td>';
                    html += '<td class="text-center">' + waktuText + '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
                updateSelectedItemsSummary();
            } else {
                container.innerHTML = '<div class="empty-state" style="padding: 20px;"><i class="fa fa-info-circle text-muted"></i><p style="margin: 0; font-size: 13px;">Tidak ada mapping jasa untuk temuan ini.</p></div>';
            }
        },
        error: function() {
            container.innerHTML = '<div class="empty-state" style="padding: 20px;"><i class="fa fa-info-circle text-muted"></i><p style="margin: 0; font-size: 13px;">Tidak ada mapping jasa untuk temuan ini.</p></div>';
        }
    });
}

// Load suggested parts from master mapping (with checkbox)
function loadSuggestedPartsV2(kodeTemuan) {
    var container = document.getElementById('suggested-parts-container-v2');
    if(!container) return;

    container.innerHTML = '<div class="text-center" style="padding: 20px;"><i class="fa fa-spinner fa-spin"></i> Memuat saran part...</div>';

    jQuery.ajax({
        url: 'ajax-get-suggested-parts.php',
        type: 'POST',
        data: { kode_temuan: kodeTemuan },
        dataType: 'json',
        success: function(response) {
            if(response.success && response.data && response.data.length > 0) {
                var html = '<div class="suggested-parts-list">';
                html += '<table class="table table-sm table-hover" style="margin: 0; font-size: 13px;">';
                html += '<thead style="background: var(--bg-light);"><tr>';
                html += '<th style="width:40px;"><input type="checkbox" onclick="toggleAllPartsV2(this)" title="Pilih semua"></th>';
                html += '<th>Kode</th><th>Nama Part</th><th class="text-right">Harga</th><th class="text-center">Stok</th><th class="text-center">Qty</th>';
                html += '</tr></thead><tbody>';

                response.data.forEach(function(part) {
                    var isPrimary = part.is_primary == 1 ? '<span class="badge badge-success" style="font-size:9px;">Utama</span>' : '';
                    var stokBadge = '<span class="badge badge-' + part.status_stok_color + '">' + part.stok + '</span>';

                    html += '<tr>';
                    html += '<td><input type="checkbox" class="part-checkbox-v2" data-kode="' + part.kode_barang + '" data-nama="' + escapeHtml(part.nama_barang) + '" data-harga="' + part.harga_jual + '" data-mapping-id="' + (part.mapping_id || '') + '" data-prioritas="' + (part.prioritas || 1) + '" onchange="updateSelectedItemsSummary()" ' + (part.is_primary == 1 ? 'checked' : '') + '></td>';
                    html += '<td><code>' + part.kode_barang + '</code> ' + isPrimary + '</td>';
                    html += '<td>' + part.nama_barang;
                    if(part.keterangan) {
                        html += '<br><small class="text-muted">' + part.keterangan + '</small>';
                    }
                    html += '</td>';
                    html += '<td class="text-right">Rp ' + part.harga_jual_formatted + '</td>';
                    html += '<td class="text-center">' + stokBadge + '</td>';
                    html += '<td class="text-center" style="width:60px;"><input type="number" class="form-control form-control-sm suggested-qty-v2" value="' + part.qty_default + '" min="1" style="width:50px; padding:2px 5px; text-align:center;" onchange="updateSelectedItemsSummary()"></td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                container.innerHTML = html;
                updateSelectedItemsSummary();
            } else {
                container.innerHTML = '<div class="empty-state" style="padding: 20px;"><i class="fa fa-info-circle text-muted"></i><p style="margin: 0; font-size: 13px;">Tidak ada mapping part untuk temuan ini.<br><small>Gunakan Fast Moves atau Barang Custom untuk menambahkan part.</small></p></div>';
            }
        },
        error: function() {
            container.innerHTML = '<div class="empty-state" style="padding: 20px;"><i class="fa fa-exclamation-triangle text-warning"></i><p style="margin: 0; font-size: 13px;">Gagal memuat saran part</p></div>';
        }
    });
}

// Toggle all checkboxes for parts
function toggleAllPartsV2(masterCheckbox) {
    jQuery('.part-checkbox-v2').prop('checked', masterCheckbox.checked);
    updateSelectedItemsSummary();
}

// Toggle all checkboxes for jasa
function toggleAllJasaV2(masterCheckbox) {
    jQuery('.jasa-checkbox-v2').prop('checked', masterCheckbox.checked);
    updateSelectedItemsSummary();
}

// Update selected items summary and hidden payloads
function updateSelectedItemsSummary() {
    var selectedParts = [];
    var selectedJasa = [];

    // Collect selected parts
    jQuery('.part-checkbox-v2:checked').each(function() {
        var $cb = jQuery(this);
        var $row = $cb.closest('tr');
        var qty = $row.find('.suggested-qty-v2').val() || 1;
        selectedParts.push({
            kode_barang: $cb.data('kode'),
            nama_barang: $cb.data('nama'),
            harga_satuan: $cb.data('harga'),
            quantity: parseInt(qty),
            mapping_id: $cb.data('mapping-id'),
            prioritas: $cb.data('prioritas')
        });
    });

    // Collect selected jasa
    jQuery('.jasa-checkbox-v2:checked').each(function() {
        var $cb = jQuery(this);
        selectedJasa.push({
            kode_jasa: $cb.data('kode'),
            nama_jasa: $cb.data('nama'),
            harga: $cb.data('harga'),
            waktu_estimasi: $cb.data('waktu'),
            mapping_id: $cb.data('mapping-id')
        });
    });

    // Update hidden payloads
    jQuery('#selected_parts_payload_v2').val(selectedParts.length > 0 ? JSON.stringify(selectedParts) : '');
    jQuery('#selected_jasa_payload_v2').val(selectedJasa.length > 0 ? JSON.stringify(selectedJasa) : '');

    // Update summary
    var totalCount = selectedParts.length + selectedJasa.length;
    var summaryDiv = document.getElementById('selected-items-summary-v2');
    var countText = document.getElementById('selected-count-text-v2');

    if(totalCount > 0) {
        summaryDiv.style.display = 'block';
        var text = [];
        if(selectedParts.length > 0) text.push(selectedParts.length + ' part');
        if(selectedJasa.length > 0) text.push(selectedJasa.length + ' jasa');
        countText.textContent = text.join(' + ') + ' dipilih';
    } else {
        summaryDiv.style.display = 'none';
    }
}

// Helper: Escape HTML
function escapeHtml(text) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}

// Add suggested part to penawaran form
function addSuggestedPartToPenawaran(kode, nama, harga, btn) {
    var row = jQuery(btn).closest('tr');
    var qty = row.find('.suggested-qty').val() || 1;

    // Show penawaran form if collapsed
    var penawaranBody = document.getElementById('penawaran-form-body');
    if(penawaranBody && penawaranBody.style.display === 'none') {
        penawaranBody.style.display = 'block';
        document.getElementById('penawaran-form-chevron').classList.remove('fa-chevron-down');
        document.getElementById('penawaran-form-chevron').classList.add('fa-chevron-up');
    }

    // Fill penawaran form
    jQuery('#kode_barang_penawaran_v2').val(kode);
    jQuery('#nama_barang_penawaran_v2').val(nama);
    jQuery('#harga_satuan_penawaran_v2').val(harga);
    jQuery('#quantity_penawaran_v2').val(qty);

    // Calculate total
    var total = parseInt(harga) * parseInt(qty);
    jQuery('#total_penawaran_display_v2').val('Rp ' + total.toLocaleString('id-ID'));

    // Visual feedback
    jQuery('#kode_barang_penawaran_v2, #nama_barang_penawaran_v2, #harga_satuan_penawaran_v2').css('backgroundColor', '#d4edda');
    setTimeout(function() {
        jQuery('#kode_barang_penawaran_v2, #nama_barang_penawaran_v2, #harga_satuan_penawaran_v2').css('backgroundColor', '');
    }, 1500);

    // Visual feedback on button
    jQuery(btn).html('<i class="fa fa-check"></i>').prop('disabled', true);
    setTimeout(function() {
        jQuery(btn).html('<i class="fa fa-plus"></i>').prop('disabled', false);
    }, 1500);

    // Scroll to penawaran form
    jQuery('html, body').animate({
        scrollTop: jQuery('#penawaran-form-body').offset().top - 100
    }, 300);
}

// Select all pending
function toggleSelectAllPendingV2(checkbox) {
    var items = document.querySelectorAll('.pending-select-v2');
    items.forEach(function(item) {
        item.checked = checkbox.checked;
        var card = item.closest('.pending-card');
        if(checkbox.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
    updatePendingSelectionV2();
}

// Update selection count
function updatePendingSelectionV2() {
    var selected = document.querySelectorAll('.pending-select-v2:checked');
    var count = selected.length;
    document.getElementById('selected_count_v2').textContent = count;
    document.getElementById('btnBulkApproveV2').disabled = count === 0;
    document.getElementById('btnBulkRejectV2').disabled = count === 0;

    // Update card selected state
    document.querySelectorAll('.pending-select-v2').forEach(function(cb) {
        var card = cb.closest('.pending-card');
        if(cb.checked) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
}

// Show reject modal
function showRejectModalV2(id, name) {
    document.getElementById('reject_item_id_v2').value = id;
    document.getElementById('reject_item_name_v2').textContent = name;
    $('#modalRejectItemV2').modal('show');
}

// Submit temuan V2
function submitTemuanV2() {
    var form = document.getElementById('form-input-temuan-v2');
    var keluhanId = document.getElementById('keluhan_id_v2').value;
    var namaTemuan = document.getElementById('nama_temuan_display_v2').value.trim();

    if(!keluhanId) {
        alert('Pilih keluhan terlebih dahulu!');
        document.getElementById('keluhan_id_v2').focus();
        return;
    }

    if(!namaTemuan) {
        alert('Isi nama temuan!');
        document.getElementById('nama_temuan_display_v2').focus();
        return;
    }

    // Set hidden field
    document.getElementById('nama_temuan_v2').value = namaTemuan;

    if(confirm('Simpan temuan ini?')) {
        form.submit();
    }
}

// Calculate total penawaran V2
function hitungTotalPenawaranV2() {
    var hEl = document.getElementById('harga_satuan_penawaran_v2');
    var qEl = document.getElementById('quantity_penawaran_v2');
    var totEl = document.getElementById('total_penawaran_display_v2');
    if(!hEl || !qEl || !totEl) return;
    var h = parseInt(hEl.value || 0);
    var q = parseInt(qEl.value || 1);
    var total = h * q;
    totEl.value = 'Rp ' + total.toLocaleString('id-ID');
}

// Event listeners for penawaran calculation
var hargaEl = document.getElementById('harga_satuan_penawaran_v2');
var qtyEl = document.getElementById('quantity_penawaran_v2');
if(hargaEl) hargaEl.addEventListener('input', hitungTotalPenawaranV2);
if(qtyEl) qtyEl.addEventListener('input', hitungTotalPenawaranV2);

// Bulk approve
function bulkApprovePendingV2() {
    var selected = document.querySelectorAll('.pending-select-v2:checked');
    if(selected.length === 0) {
        alert('Pilih minimal satu item');
        return;
    }

    if(!confirm('Setujui ' + selected.length + ' item terpilih?')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="txtnosrv" value="<?= $no_service ?>">' +
                     '<input type="hidden" name="btnapprove_wo_bulk" value="1">';

    selected.forEach(function(cb) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'selected_item_ids[]';
        input.value = cb.value;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
}

// Single approve pending V2
function approveSingleItemV2(id, no_service) {
    if(!confirm('Setujui item ini?')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="txtnosrv" value="' + no_service + '">' +
                     '<input type="hidden" name="btnapprove_wo_item" value="1">' +
                     '<input type="hidden" name="pending_item_id" value="' + id + '">';
    
    document.body.appendChild(form);
    form.submit();
}

// Submit Reject Item V2
function submitRejectItemV2() {
    var id = document.getElementById('reject_item_id_v2').value;
    var alasan = document.getElementById('alasan_reject_v2').value;
    var keterangan = document.getElementById('keterangan_reject_v2').value;
    var no_service = '<?= $no_service ?>';

    if(!alasan) {
        alert('Pilih alasan penolakan!');
        return;
    }

    if(!confirm('Tolak item ini?')) return;

    var form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="txtnosrv" value="' + no_service + '">' +
                     '<input type="hidden" name="btnreject_wo_item" value="1">' +
                     '<input type="hidden" name="pending_item_id" value="' + id + '">' +
                     '<input type="hidden" name="alasan_reject" value="' + alasan + '">' +
                     '<input type="hidden" name="keterangan_reject" value="' + keterangan + '">';
    
    document.body.appendChild(form);
    form.submit();
}

// Functions from original (kept for compatibility)
function approvePenawaran(id) {
    if(confirm('Setujui penawaran part ini?')) {
        window.location = 'servis-penawaran-approve.php?id=' + id + '&snoserv=<?= $no_service ?>';
    }
}

function rejectPenawaran(id) {
    var alasan = prompt('Alasan penolakan:\n1. Customer tidak mau\n2. Stok kosong\n3. Harga tidak cocok\n4. Lainnya\n\nKetik nomor:');
    if(alasan) {
        var map = {'1': 'customer_tidak_mau', '2': 'stok_bengkel_kosong', '3': 'harga_tidak_cocok', '4': 'lainnya'};
        window.location = 'servis-penawaran-reject.php?id=' + id + '&alasan=' + (map[alasan] || 'lainnya') + '&snoserv=<?= $no_service ?>';
    }
}

// Approve/Reject Penawaran Jasa
function approvePenawaranJasa(id) {
    if(confirm('Setujui penawaran jasa ini?\nJasa akan ditambahkan ke Tab Item Jasa.')) {
        window.location = 'servis-penawaran-jasa-approve.php?id=' + id + '&snoserv=<?= $no_service ?>';
    }
}

function rejectPenawaranJasa(id) {
    var alasan = prompt('Alasan penolakan:\n1. Customer tidak mau\n2. Harga tidak cocok\n3. Sudah ada jasa serupa\n4. Lainnya\n\nKetik nomor:');
    if(alasan) {
        var map = {'1': 'customer_tidak_mau', '2': 'harga_tidak_cocok', '3': 'sudah_ada_jasa', '4': 'lainnya'};
        window.location = 'servis-penawaran-jasa-reject.php?id=' + id + '&alasan=' + (map[alasan] || 'lainnya') + '&snoserv=<?= $no_service ?>';
    }
}

function addMoreParts(temuanId) {
    if(typeof jQuery !== 'undefined' && jQuery('#modalAddPartToTemuan').length) {
        jQuery('#modal_add_part_temuan_id').val(temuanId);
        
        // Load temuan name via AJAX
        jQuery.ajax({
            url: '_handler_temuan_penawaran.php',
            type: 'GET',
            data: { action: 'get_temuan_info', id: temuanId },
            dataType: 'json',
            success: function(response) {
                if(response.success && response.data) {
                    jQuery('#add_part_temuan_nama').text(response.data.nama_temuan || 'Temuan #' + temuanId);
                }
            }
        });
        
        jQuery('#modalAddPartToTemuan').modal('show');
    } else {
        alert('Modal tidak tersedia. Silakan muat ulang halaman.');
    }
}

function editTemuan(temuanId) {
    if(typeof jQuery !== 'undefined' && jQuery('#modalEditTemuan').length) {
        // Load temuan data and show modal via the modal's own function
        if(typeof window.loadTemuanForEdit === 'function') {
            window.loadTemuanForEdit(temuanId);
            jQuery('#modalEditTemuan').modal('show');
        } else {
            // Fallback: Load via AJAX directly
            jQuery.ajax({
                url: '_handler_temuan_penawaran.php',
                type: 'GET',
                data: { action: 'get_temuan_info', id: temuanId },
                dataType: 'json',
                success: function(response) {
                    if(response.success && response.data) {
                        var data = response.data;
                        jQuery('#edit_temuan_id').val(data.id);
                        jQuery('#edit_kode_temuan').val(data.kode_temuan || '');
                        jQuery('#edit_nama_temuan_display').val(data.nama_temuan || '');
                        jQuery('#edit_temuan_deskripsi').val(data.deskripsi_temuan || '');
                        jQuery('#edit_temuan_jenis').val(data.jenis_perbaikan);
                        jQuery('#edit_temuan_urgensi').val(data.tingkat_urgensi);
                        jQuery('#modalEditTemuan').modal('show');
                    } else {
                        alert('Error: ' + (response.message || 'Gagal memuat data temuan'));
                    }
                },
                error: function() {
                    alert('Gagal menghubungi server');
                }
            });
        }
    } else {
        alert('Modal tidak tersedia. Silakan muat ulang halaman.');
    }
}

function deleteTemuan(temuanId) {
    if(confirm('Hapus temuan ini?\nSemua penawaran part terkait juga akan dihapus.')) {
        window.location = 'servis-temuan-delete.php?id=' + temuanId + '&snoserv=<?= $no_service ?>';
    }
}

// Callback untuk modal search temuan
window.onTemuanSelected = function(kode, nama, kategori, urgensi) {
    console.log('onTemuanSelected called:', {kode: kode, nama: nama, kategori: kategori, urgensi: urgensi});

    // Set hidden fields
    document.getElementById('kode_temuan_v2').value = kode || '';
    document.getElementById('nama_temuan_v2').value = nama || '';
    document.getElementById('nama_temuan_display_v2').value = nama || '';

    // Set urgensi if provided
    if(urgensi) {
        var urgensiSelect = document.getElementById('tingkat_urgensi_v2');
        if(urgensiSelect) {
            urgensiSelect.value = urgensi;
        }
    }

    // Visual feedback
    var displayField = document.getElementById('nama_temuan_display_v2');
    displayField.style.backgroundColor = '#d4edda';
    setTimeout(function() {
        displayField.style.backgroundColor = '';
    }, 1500);
};

// Callback untuk input manual temuan
window.onTemuanManualCreated = function(kode, nama, kategori, urgensi, deskripsi) {
    console.log('onTemuanManualCreated called:', {kode: kode, nama: nama});

    // Set hidden fields dengan prefix CUSTOM- untuk manual
    document.getElementById('kode_temuan_v2').value = '';
    document.getElementById('nama_temuan_v2').value = nama || '';
    document.getElementById('nama_temuan_display_v2').value = nama || '';

    // Set urgensi if provided
    if(urgensi) {
        var urgensiSelect = document.getElementById('tingkat_urgensi_v2');
        if(urgensiSelect) {
            urgensiSelect.value = urgensi;
        }
    }

    // Visual feedback
    var displayField = document.getElementById('nama_temuan_display_v2');
    displayField.style.backgroundColor = '#fff3cd';
    setTimeout(function() {
        displayField.style.backgroundColor = '';
    }, 1500);
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    hitungTotalPenawaranV2();
});
</script>

<?php
// Include Modal Tambah Part ke Temuan
include __DIR__ . '/modal-add-part-to-temuan.php';

// Include Modal Edit Temuan
include __DIR__ . '/modal-edit-temuan.php';
?>
