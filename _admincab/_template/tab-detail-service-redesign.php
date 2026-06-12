<?php
/**
 * REDESIGN: Tab Detail Service
 * Clean & Focused UI v2.0
 */

// Helper function untuk mendapatkan info kategori member
if(!function_exists('getMemberCategoryInfo')) {
    function getMemberCategoryInfo($koneksi, $kode_pelanggan) {
        $result = [
            'kategori' => 'REGULAR',
            'diskon' => 0,
            'badge_class' => 'neutral'
        ];

        if(empty($kode_pelanggan)) {
            return $result;
        }

        $customer = function_exists('fitmotorFindCustomerForService')
            ? fitmotorFindCustomerForService($koneksi, $kode_pelanggan, $GLOBALS['no_polisi'] ?? '')
            : null;

        if($customer) {
            $kategoriLabel = trim((string) ($customer['status_member'] ?? $customer['grup'] ?? $customer['kgrup'] ?? 'REGULAR'));
            $kategori = strtoupper($kategoriLabel);
            $diskon = floatval($customer['diskon_persen'] ?? $customer['potongan'] ?? 0);

            // Tentukan badge class berdasarkan kategori
            $badge_class = 'neutral';
            if(stripos($kategori, 'GOLD') !== false || stripos($kategori, 'VIP') !== false) {
                $badge_class = 'gold';
            } elseif(stripos($kategori, 'SILVER') !== false) {
                $badge_class = 'silver';
            } elseif(stripos($kategori, 'BRONZE') !== false) {
                $badge_class = 'bronze';
            } elseif(stripos($kategori, 'MEMBER') !== false) {
                $badge_class = 'info';
            }

            $result['kategori'] = $kategori ?: 'REGULAR';
            $result['diskon'] = $diskon;
            $result['badge_class'] = $badge_class;
        }

        return $result;
    }
}
?>

<div class="rd-card primary">
    <div class="rd-card-header">
        <h5><i class="fa fa-info-circle"></i> Informasi Service</h5>
        <?php $___status_sv = isset($status_servis) && $status_servis !== '' ? $status_servis : 'datang'; ?>
        <span class="rd-badge solid-<?= $___status_sv == 'bayar' ? 'success' : ($___status_sv == 'proses' ? 'warning' : 'info') ?>">
            <?= strtoupper($___status_sv) ?>
        </span>
    </div>
    <div class="rd-card-body">
        <!-- Service Info Grid - Fixed 4 columns for alignment -->
        <div class="rd-info-grid" style="margin-bottom: 24px; display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
            <div class="rd-info-item" style="text-align: center;">
                <span class="label" style="display: block; margin-bottom: 8px;">NO. SERVICE</span>
                <span class="value lg primary" style="font-size: 16px; font-weight: 700; color: #4A90D9;"><?= htmlspecialchars($no_service ?? '') ?></span>
            </div>
            <div class="rd-info-item" style="text-align: center;">
                <span class="label" style="display: block; margin-bottom: 8px;">TANGGAL</span>
                <span class="value" style="font-size: 15px;"><?= htmlspecialchars($tanggal ?? '') ?></span>
            </div>
            <div class="rd-info-item" style="text-align: center;">
                <span class="label" style="display: block; margin-bottom: 8px;">JAM</span>
                <span class="value" style="font-size: 15px;"><?= htmlspecialchars($jam ?? '') ?></span>
            </div>
            <div class="rd-info-item" style="text-align: center;">
                <span class="label" style="display: block; margin-bottom: 8px;">TIPE SERVICE</span>
                <span class="value">
                    <span class="rd-badge info">REGULER</span>
                </span>
            </div>
        </div>

        <div class="rd-divider"></div>

        <!-- Customer & Vehicle Info - Two column layout with equal heights -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
            <!-- Customer Info -->
            <div class="rd-card" style="margin-bottom: 0; height: 100%;">
                <div class="rd-card-header" style="padding: 12px 16px;">
                    <h5 style="font-size: 13px; margin: 0;"><i class="fa fa-user"></i> Data Pelanggan</h5>
                    <?php if(!empty($kode_pelanggan)): ?>
                    <button type="button" class="rd-btn xs outline-primary" onclick="showStatistikPelanggan()">
                        <i class="fa fa-chart-bar"></i> Statistik
                    </button>
                    <?php endif; ?>
                </div>
                <div class="rd-card-body" style="padding: 16px;">
                    <div class="rd-info-grid" style="grid-template-columns: 1fr;">
                        <div class="rd-info-item">
                            <span class="label">Kode Pelanggan</span>
                            <span class="value"><?= htmlspecialchars($kode_pelanggan) ?: '-' ?></span>
                        </div>
                        <div class="rd-info-item">
                            <span class="label">Nama</span>
                            <span class="value" style="font-size: 16px; font-weight: 600;">
                                <?= htmlspecialchars($namapelanggan) ?: '-' ?>
                            </span>
                        </div>
                        <?php if(!empty($kode_pelanggan)): ?>
                        <?php
                        // Get member info
                        $member_info = getMemberCategoryInfo($koneksi, $kode_pelanggan);
                        ?>
                        <div class="rd-info-item">
                            <span class="label">Kategori Member</span>
                            <span class="value">
                                <span class="rd-badge <?= $member_info['badge_class'] ?? 'neutral' ?>">
                                    <?= $member_info['kategori'] ?? 'REGULAR' ?>
                                </span>
                                <?php if(($member_info['diskon'] ?? 0) > 0): ?>
                                <span class="rd-tag" style="margin-left: 6px;">
                                    Diskon <?= $member_info['diskon'] ?>%
                                </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if(empty($kode_pelanggan)): ?>
                    <div class="rd-alert warning" style="margin-top: 12px; margin-bottom: 0;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <div>Pelanggan belum dipilih. Silakan pilih pelanggan terlebih dahulu.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Vehicle Info -->
            <div class="rd-card" style="margin-bottom: 0; height: 100%;">
                <div class="rd-card-header" style="padding: 12px 16px;">
                    <h5 style="font-size: 13px; margin: 0;"><i class="fa fa-motorcycle"></i> Data Kendaraan</h5>
                    <?php if(!empty($no_polisi)): ?>
                    <button type="button" class="rd-btn xs outline-primary" onclick="showRiwayatKendaraan()">
                        <i class="fa fa-history"></i> Riwayat
                    </button>
                    <?php endif; ?>
                </div>
                <div class="rd-card-body" style="padding: 16px;">
                    <div class="rd-info-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="rd-info-item">
                            <span class="label">No. Polisi</span>
                            <span class="value" style="font-size: 18px; font-weight: 700; color: var(--rd-primary);">
                                <?= htmlspecialchars($no_polisi) ?: '-' ?>
                            </span>
                        </div>
                        <div class="rd-info-item">
                            <span class="label">Jenis / Merek</span>
                            <span class="value"><?= htmlspecialchars($jenis . ' ' . $merek) ?: '-' ?></span>
                        </div>
                        <div class="rd-info-item">
                            <span class="label">Warna</span>
                            <span class="value"><?= htmlspecialchars($warna) ?: '-' ?></span>
                        </div>
                        <div class="rd-info-item">
                            <span class="label">Tahun</span>
                            <span class="value"><?= htmlspecialchars($tahun_kendaraan ?? '-') ?></span>
                        </div>
                    </div>

                    <?php if(!empty($no_rangka) || !empty($no_mesin)): ?>
                    <div class="rd-divider" style="margin: 12px 0;"></div>
                    <div class="rd-info-grid" style="grid-template-columns: 1fr 1fr;">
                        <div class="rd-info-item">
                            <span class="label">No. Rangka</span>
                            <span class="value" style="font-size: 12px; font-family: monospace;">
                                <?= htmlspecialchars($no_rangka) ?: '-' ?>
                            </span>
                        </div>
                        <div class="rd-info-item">
                            <span class="label">No. Mesin</span>
                            <span class="value" style="font-size: 12px; font-family: monospace;">
                                <?= htmlspecialchars($no_mesin) ?: '-' ?>
                            </span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if(empty($no_polisi)): ?>
                    <div class="rd-alert warning" style="margin-top: 12px; margin-bottom: 0;">
                        <i class="fa fa-exclamation-triangle"></i>
                        <div>Kendaraan belum dipilih.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
if (typeof window.toggleCardBody !== 'function') {
    window.toggleCardBody = function(header) {
        var body = header ? header.nextElementSibling : null;
        var icon = header ? header.querySelector('.rd-collapse-icon') : null;
        if (!header || !body) {
            return;
        }
        header.classList.toggle('collapsed');
        var isCollapsed = header.classList.contains('collapsed');
        body.style.display = isCollapsed ? 'none' : 'block';
        if (icon) {
            icon.style.transform = isCollapsed ? 'rotate(-90deg)' : 'rotate(0deg)';
        }
    };
}

if (typeof window.showStatistikPelanggan !== 'function') {
    window.showStatistikPelanggan = function() {
        var modal = document.getElementById('modalStatistikPelanggan');
        if (modal && window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modal).modal('show');
            return;
        }
        <?php if(!empty($kode_pelanggan)): ?>
        window.open('detail_pelanggan.php?nopelanggan=<?= urlencode($kode_pelanggan) ?>', '_blank');
        <?php else: ?>
        alert('Statistik pelanggan belum tersedia');
        <?php endif; ?>
    };
}

if (typeof window.showRiwayatKendaraan !== 'function') {
    window.showRiwayatKendaraan = function() {
        var modal = document.getElementById('modalRiwayatKendaraan');
        if (modal && window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery(modal).modal('show');
            return;
        }
        var target = document.querySelector('.rd-card.warning');
        if (target && typeof target.scrollIntoView === 'function') {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            return;
        }
        alert('Riwayat kendaraan belum tersedia');
    };
}
</script>

<!-- Keluhan Section (Read-only: showing previous service complaints only) -->
<div class="rd-card warning">
    <div class="rd-card-header collapsible" onclick="toggleCardBody(this)">
        <h5>
            <i class="fa fa-comment-alt"></i> Keluhan Pelanggan (Servis Sebelumnya)
            <?php
            // Cari no_service terakhir (sebelum transaksi ini) untuk kendaraan yang sama
            $prev_no_service = '';
            if (!empty($no_polisi)) {
                $q_prev = mysqli_query($koneksi, "SELECT no_service FROM tblservice 
                                                  WHERE no_polisi='".mysqli_real_escape_string($koneksi,$no_polisi)."' 
                                                    AND no_service <> '".mysqli_real_escape_string($koneksi,$no_service)."'
                                                    AND status_servis IN ('selesai','bayar')
                                                  ORDER BY tanggal DESC, no_service DESC LIMIT 1");
                if ($q_prev && ($r_prev = mysqli_fetch_array($q_prev))) { $prev_no_service = $r_prev['no_service']; }
            }

            $count_keluhan = 0;
            if (!empty($prev_no_service)) {
                $sql_count_kel = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbservis_keluhan_status WHERE no_service='".mysqli_real_escape_string($koneksi,$prev_no_service)."'");
                if($sql_count_kel) { $count_keluhan = (int) (mysqli_fetch_array($sql_count_kel)['total'] ?? 0); }
            }
            ?>
            <span class="rd-badge solid-warning"><?= $count_keluhan ?> Keluhan</span>
        </h5>
        <i class="fa fa-chevron-down rd-collapse-icon"></i>
    </div>
    <div class="rd-card-body">
        <?php if(empty($prev_no_service)): ?>
            <div class="rd-empty-state" style="padding: 20px;">
                <i class="fa fa-info-circle"></i>
                <p>Tidak ada riwayat servis sebelumnya untuk kendaraan ini.</p>
            </div>
        <?php else: ?>
            <?php
            $sql_keluhan = mysqli_query($koneksi, "SELECT keluhan, status_pengerjaan FROM tbservis_keluhan_status 
                                                  WHERE no_service='".mysqli_real_escape_string($koneksi,$prev_no_service)."' ORDER BY id ASC");
            if($sql_keluhan && mysqli_num_rows($sql_keluhan) > 0):
            ?>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php
                $no_kel = 0;
                while($kel = mysqli_fetch_array($sql_keluhan)):
                    $no_kel++;
                    $status = $kel['status_pengerjaan'] ?? 'datang';
                    $status_class = $status == 'selesai' ? 'success' : ($status == 'diproses' ? 'warning' : 'neutral');
                ?>
                <div class="rd-flex-between" style="padding: 12px 16px; background: var(--rd-bg-light); border-radius: var(--rd-radius-sm); border-left: 3px solid var(--rd-<?= $status_class ?>);">
                    <div class="rd-flex rd-gap-12">
                        <span class="rd-badge neutral">#<?= $no_kel ?></span>
                        <span style="font-weight: 500;"><?= htmlspecialchars($kel['keluhan']) ?></span>
                    </div>
                    <div class="rd-flex rd-gap-8">
                        <span class="rd-badge <?= $status_class ?>"><?= ucfirst($status) ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php else: ?>
            <div class="rd-empty-state" style="padding: 20px;">
                <i class="fa fa-comment-slash"></i>
                <p>Servis sebelumnya tidak memiliki keluhan tercatat.</p>
            </div>
            <?php endif; ?>
            <div class="rd-divider" style="margin-top:12px;"></div>
            <small class="rd-text-muted">Keluhan pada tab ini bersifat informatif (riwayat). Penambahan keluhan baru dilakukan melalui tab <strong>Work Order</strong>.</small>
        <?php endif; ?>
    </div>
</div>

<!-- Note: KM & Catatan sudah tersedia di Tab Actions/Pembayaran -->
<!-- JavaScript functions sudah didefinisikan di main page -->
