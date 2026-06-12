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

        // Query ke tabel pelanggan untuk mendapatkan kategori
        $sql = mysqli_query($koneksi, "SELECT kategori, potongan FROM tblpelanggan WHERE kode_pelanggan='".mysqli_real_escape_string($koneksi, $kode_pelanggan)."'");

        if($sql && $row = mysqli_fetch_array($sql)) {
            $kategori = strtoupper($row['kategori'] ?? 'REGULAR');
            $diskon = floatval($row['potongan'] ?? 0);

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
        <span class="rd-badge solid-<?= $status_servis == 'bayar' ? 'success' : ($status_servis == 'proses' ? 'warning' : 'info') ?>">
            <?= strtoupper($status_servis) ?>
        </span>
    </div>
    <div class="rd-card-body">
        <!-- Service Info Grid -->
        <div class="rd-info-grid" style="margin-bottom: 24px;">
            <div class="rd-info-item">
                <span class="label">No. Service</span>
                <span class="value lg primary"><?= htmlspecialchars($no_service) ?></span>
            </div>
            <div class="rd-info-item">
                <span class="label">Tanggal</span>
                <span class="value"><?= htmlspecialchars($tanggal) ?></span>
            </div>
            <div class="rd-info-item">
                <span class="label">Jam</span>
                <span class="value"><?= htmlspecialchars($jam) ?></span>
            </div>
            <div class="rd-info-item">
                <span class="label">Tipe Service</span>
                <span class="value">
                    <span class="rd-badge info">REGULER</span>
                </span>
            </div>
        </div>

        <div class="rd-divider"></div>

        <!-- Customer & Vehicle Info -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Customer Info -->
            <div class="rd-card" style="margin-bottom: 0;">
                <div class="rd-card-header" style="padding: 12px 16px;">
                    <h5 style="font-size: 13px;"><i class="fa fa-user"></i> Data Pelanggan</h5>
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
            <div class="rd-card" style="margin-bottom: 0;">
                <div class="rd-card-header" style="padding: 12px 16px;">
                    <h5 style="font-size: 13px;"><i class="fa fa-motorcycle"></i> Data Kendaraan</h5>
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

<!-- Keluhan Section -->
<div class="rd-card warning">
    <div class="rd-card-header collapsible" onclick="toggleCardBody(this)">
        <h5>
            <i class="fa fa-comment-alt"></i> Keluhan Pelanggan
            <?php
            $count_keluhan = 0;
            $sql_count_kel = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tbservis_keluhan_status WHERE no_service='$no_service'");
            if($sql_count_kel) {
                $count_keluhan = mysqli_fetch_array($sql_count_kel)['total'];
            }
            ?>
            <span class="rd-badge solid-warning"><?= $count_keluhan ?> Keluhan</span>
        </h5>
        <i class="fa fa-chevron-down rd-collapse-icon"></i>
    </div>
    <div class="rd-card-body">
        <?php
        $sql_keluhan = mysqli_query($koneksi, "SELECT * FROM tbservis_keluhan_status WHERE no_service='$no_service' ORDER BY id ASC");
        if($sql_keluhan && mysqli_num_rows($sql_keluhan) > 0):
        ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php
            $no_kel = 0;
            while($kel = mysqli_fetch_array($sql_keluhan)):
                $no_kel++;
                $status_class = $kel['status_pengerjaan'] == 'selesai' ? 'success' : ($kel['status_pengerjaan'] == 'proses' ? 'warning' : 'neutral');
            ?>
            <div class="rd-flex-between" style="padding: 12px 16px; background: var(--rd-bg-light); border-radius: var(--rd-radius-sm); border-left: 3px solid var(--rd-<?= $status_class ?>);">
                <div class="rd-flex rd-gap-12">
                    <span class="rd-badge neutral">#<?= $no_kel ?></span>
                    <span style="font-weight: 500;"><?= htmlspecialchars($kel['keluhan']) ?></span>
                </div>
                <div class="rd-flex rd-gap-8">
                    <span class="rd-badge <?= $status_class ?>"><?= ucfirst($kel['status_pengerjaan']) ?></span>
                    <button type="button" class="rd-btn xs outline-neutral" onclick="updateStatusKeluhan(<?= $kel['id'] ?>)">
                        <i class="fa fa-edit"></i>
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="rd-empty-state" style="padding: 20px;">
            <i class="fa fa-comment-slash"></i>
            <p>Belum ada keluhan tercatat</p>
        </div>
        <?php endif; ?>

        <div class="rd-divider"></div>

        <div class="rd-flex-end rd-gap-8">
            <button type="button" class="rd-btn sm outline-primary" onclick="$('#modalSearchKeluhan').modal('show');">
                <i class="fa fa-search"></i> Cari Keluhan
            </button>
            <button type="button" class="rd-btn sm primary" onclick="$('#modalTambahKeluhanBaru').modal('show');">
                <i class="fa fa-plus"></i> Tambah Keluhan
            </button>
        </div>
    </div>
</div>

<!-- KM & Notes Section -->
<div class="rd-card">
    <div class="rd-card-header">
        <h5><i class="fa fa-tachometer-alt"></i> Kilometer & Catatan</h5>
    </div>
    <div class="rd-card-body">
        <div class="rd-form-row">
            <div class="rd-form-group">
                <label class="rd-label">KM Saat Ini</label>
                <input type="number" name="txtkm_skr" class="rd-input" value="<?= htmlspecialchars($km_skr) ?>" placeholder="Masukkan KM saat ini">
            </div>
            <div class="rd-form-group">
                <label class="rd-label">KM Berikutnya</label>
                <input type="number" name="txtkm_next" class="rd-input" value="<?= htmlspecialchars($km_berikut) ?>" placeholder="KM service berikutnya" readonly style="background: var(--rd-bg-light);">
            </div>
        </div>
        <div class="rd-form-group">
            <label class="rd-label">Catatan Tambahan</label>
            <textarea name="catatan_service" class="rd-input" rows="3" placeholder="Catatan tambahan untuk service ini..."><?= htmlspecialchars($catatan_service ?? '') ?></textarea>
        </div>
    </div>
</div>

<!-- JavaScript functions sudah didefinisikan di main page -->
