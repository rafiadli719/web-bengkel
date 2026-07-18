<?php
/**
 * Tab "Riwayat Servis Asal" — khusus servis-garansi.php.
 * Tampilkan detail pengerjaan servis asal (ref_no_service_original): keluhan,
 * work order, temuan & penawaran (approved/pending/ditolak), item barang, item jasa.
 * Read-only — tidak ada handler edit di sini.
 */
if (empty($ref_service) || empty($ref_service_data)) {
    echo '<div class="rd-empty-state">
            <i class="fa fa-info-circle"></i>
            <h6>Belum Terhubung ke Service Asal</h6>
            <p>Garansi ini belum terhubung ke service asal (bukan hasil klik "Buat Service Garansi" dari carinopol).</p>
          </div>';
    return;
}

$rs = mysqli_real_escape_string($koneksi, $ref_service);

$q_keluhan = mysqli_query($koneksi, "SELECT * FROM tbservis_keluhan_status WHERE no_service='$rs' ORDER BY id");
$q_wo = mysqli_query($koneksi, "SELECT sw.*, wh.nama_wo FROM tbservis_workorder sw
    LEFT JOIN tbworkorderheader wh ON sw.kode_wo = wh.kode_wo
    WHERE sw.no_service='$rs' ORDER BY sw.id");
$q_temuan = mysqli_query($koneksi, "SELECT * FROM tbservis_pending_items WHERE no_service='$rs' ORDER BY FIELD(status_approval,'pending','disetujui','ditolak'), id");
$q_barang = mysqli_query($koneksi, "SELECT b.*, i.namaitem FROM tblservis_barang b
    LEFT JOIN tblitem i ON b.no_item = i.noitem
    WHERE b.no_service='$rs' ORDER BY b.nobaris");
$q_jasa = mysqli_query($koneksi, "SELECT j.*, i.namaitem FROM tblservis_jasa j
    LEFT JOIN tblitem i ON j.no_item = i.noitem
    WHERE j.no_service='$rs' ORDER BY j.nobaris");

$rows_keluhan = $q_keluhan ? mysqli_fetch_all($q_keluhan, MYSQLI_ASSOC) : [];
$rows_wo      = $q_wo ? mysqli_fetch_all($q_wo, MYSQLI_ASSOC) : [];
$rows_temuan  = $q_temuan ? mysqli_fetch_all($q_temuan, MYSQLI_ASSOC) : [];
$rows_barang  = $q_barang ? mysqli_fetch_all($q_barang, MYSQLI_ASSOC) : [];
$rows_jasa    = $q_jasa ? mysqli_fetch_all($q_jasa, MYSQLI_ASSOC) : [];

$total_barang_asal = array_sum(array_column($rows_barang, 'total'));
$total_jasa_asal   = array_sum(array_column($rows_jasa, 'total'));
$total_asal        = $total_barang_asal + $total_jasa_asal;
?>

<!-- Header info service asal -->
<div class="rd-card primary" style="margin-bottom:16px;">
    <div class="rd-card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <div>
            <div class="rd-text-muted" style="font-size:10px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:2px;">
                <i class="fa fa-link"></i> Service Asal
            </div>
            <div style="font-size:15px;font-weight:700;"><?= htmlspecialchars($ref_service) ?></div>
        </div>
        <div class="rd-text-muted" style="font-size:12px;">
            <i class="fa fa-calendar"></i> <?= htmlspecialchars(date('d/m/Y', strtotime($ref_service_data['tanggal'] ?? 'now'))) ?>
        </div>
    </div>
</div>

<!-- Summary Stats -->
<div class="rd-stats-row">
    <div class="rd-stat-box">
        <div class="icon primary"><i class="fa fa-comment-medical"></i></div>
        <div class="value"><?= count($rows_keluhan) ?></div>
        <div class="label">Keluhan</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon info"><i class="fa fa-clipboard-list"></i></div>
        <div class="value"><?= count($rows_wo) ?></div>
        <div class="label">Work Order</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon warning"><i class="fa fa-search-plus"></i></div>
        <div class="value"><?= count($rows_temuan) ?></div>
        <div class="label">Temuan/Penawaran</div>
    </div>
    <div class="rd-stat-box">
        <div class="icon success"><i class="fa fa-money-bill"></i></div>
        <div class="value">Rp <?= number_format($total_asal, 0, ',', '.') ?></div>
        <div class="label">Total Servis Asal</div>
    </div>
</div>

<!-- KELUHAN -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-comment-medical"></i> Keluhan</h5>
        <span class="rd-badge primary"><?= count($rows_keluhan) ?></span>
    </div>
    <div class="rd-card-body" style="padding:0;">
        <?php if (!empty($rows_keluhan)): ?>
        <div class="rd-table-wrapper" style="border:none;border-radius:0;">
            <table class="rd-table">
                <thead><tr><th>Keluhan</th><th width="20%">Kategori</th><th width="18%" class="text-center">Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows_keluhan as $k):
                    $st = $k['status_pengerjaan'] ?? '-';
                    $stBadge = $st === 'selesai' ? 'success' : ($st === 'diproses' ? 'warning' : 'neutral');
                ?>
                    <tr>
                        <td><?= htmlspecialchars($k['keluhan']) ?></td>
                        <td><?= htmlspecialchars($k['kategori'] ?? '-') ?></td>
                        <td class="text-center"><span class="rd-badge <?= $stBadge ?>"><?= htmlspecialchars(ucfirst($st)) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding:20px;">
            <i class="fa fa-comment-slash"></i>
            <p>Tidak ada data keluhan</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- WORK ORDER -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-clipboard-list"></i> Work Order</h5>
        <span class="rd-badge info"><?= count($rows_wo) ?></span>
    </div>
    <div class="rd-card-body" style="padding:0;">
        <?php if (!empty($rows_wo)): ?>
        <div class="rd-table-wrapper" style="border:none;border-radius:0;">
            <table class="rd-table">
                <thead><tr><th width="20%">Kode WO</th><th>Nama WO</th><th width="18%" class="text-center">Status</th></tr></thead>
                <tbody>
                <?php foreach ($rows_wo as $w):
                    $badge = $w['status_pengerjaan'] === 'selesai' ? 'success' : ($w['status_pengerjaan'] === 'tidak_selesai' ? 'danger' : 'warning');
                ?>
                    <tr>
                        <td><code><?= htmlspecialchars($w['kode_wo']) ?></code></td>
                        <td><?= htmlspecialchars($w['nama_wo'] ?? '-') ?></td>
                        <td class="text-center"><span class="rd-badge <?= $badge ?>"><?= htmlspecialchars($w['status_pengerjaan']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding:20px;">
            <i class="fa fa-clipboard"></i>
            <p>Tidak ada data work order</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- TEMUAN & PENAWARAN -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-search-plus"></i> Temuan & Penawaran</h5>
        <span class="rd-badge warning"><?= count($rows_temuan) ?></span>
    </div>
    <div class="rd-card-body" style="padding:0;">
        <?php if (!empty($rows_temuan)): ?>
        <div class="rd-table-wrapper" style="border:none;border-radius:0;">
            <table class="rd-table">
                <thead><tr><th>Item</th><th width="12%">Tipe</th><th width="8%" class="text-center">Qty</th><th width="15%" class="text-right">Total</th><th width="15%" class="text-center">Status</th></tr></thead>
                <tbody>
                <?php
                $badgeMap = ['disetujui' => 'success', 'ditolak' => 'danger', 'pending' => 'warning'];
                foreach ($rows_temuan as $t):
                    $badge = $badgeMap[$t['status_approval']] ?? 'neutral';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($t['nama_item']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($t['tipe'])) ?></td>
                        <td class="text-center"><?= (int)$t['quantity'] ?></td>
                        <td class="text-right">Rp <?= number_format((float)$t['total'], 0, ',', '.') ?></td>
                        <td class="text-center"><span class="rd-badge <?= $badge ?>"><?= htmlspecialchars(ucfirst($t['status_approval'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding:20px;">
            <i class="fa fa-search"></i>
            <p>Tidak ada temuan/penawaran</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ITEM BARANG -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-box"></i> Item Barang / Suku Cadang</h5>
        <span class="rd-badge primary"><?= count($rows_barang) ?> Item</span>
    </div>
    <div class="rd-card-body" style="padding:0;">
        <?php if (!empty($rows_barang)): ?>
        <div class="rd-table-wrapper" style="border:none;border-radius:0;">
            <table class="rd-table">
                <thead><tr><th>Nama Item</th><th width="8%" class="text-center">Qty</th><th width="15%" class="text-right">Harga</th><th width="15%" class="text-right">Total</th><th width="18%">Ket</th></tr></thead>
                <tbody>
                <?php foreach ($rows_barang as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['namaitem'] ?? $b['no_item']) ?></td>
                        <td class="text-center"><?= (int)$b['quantity'] ?></td>
                        <td class="text-right">Rp <?= number_format((float)$b['harga_jual'], 0, ',', '.') ?></td>
                        <td class="text-right"><strong>Rp <?= number_format((float)$b['total'], 0, ',', '.') ?></strong></td>
                        <td><small class="rd-text-muted"><?= htmlspecialchars($b['keterangan'] ?? '-') ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>TOTAL BARANG</strong></td>
                        <td class="text-right"><strong style="color:var(--rd-primary);">Rp <?= number_format($total_barang_asal, 0, ',', '.') ?></strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding:20px;">
            <i class="fa fa-box-open"></i>
            <p>Tidak ada item barang</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ITEM JASA -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-tools"></i> Item Jasa Service</h5>
        <span class="rd-badge primary"><?= count($rows_jasa) ?> Item</span>
    </div>
    <div class="rd-card-body" style="padding:0;">
        <?php if (!empty($rows_jasa)): ?>
        <div class="rd-table-wrapper" style="border:none;border-radius:0;">
            <table class="rd-table">
                <thead><tr><th>Nama Jasa</th><th width="12%" class="text-center">Waktu</th><th width="15%" class="text-right">Harga</th><th width="15%" class="text-right">Total</th></tr></thead>
                <tbody>
                <?php foreach ($rows_jasa as $j): ?>
                    <tr>
                        <td><?= htmlspecialchars($j['namaitem'] ?? $j['no_item']) ?></td>
                        <td class="text-center"><?= (int)$j['waktu'] ?> mnt</td>
                        <td class="text-right">Rp <?= number_format((float)$j['harga'], 0, ',', '.') ?></td>
                        <td class="text-right"><strong>Rp <?= number_format((float)$j['total'], 0, ',', '.') ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-right"><strong>TOTAL JASA</strong></td>
                        <td class="text-right"><strong style="color:var(--rd-primary);">Rp <?= number_format($total_jasa_asal, 0, ',', '.') ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding:20px;">
            <i class="fa fa-tools"></i>
            <p>Tidak ada item jasa</p>
        </div>
        <?php endif; ?>
    </div>
</div>
