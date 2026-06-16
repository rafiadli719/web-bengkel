<?php
/**
 * REDESIGN: Tab Actions (Payment & Mechanic Assignment)
 * Clean & Focused UI v2.0
 */

// Get kepala mekanik harian
$kepala_mekanik_helper = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'get_kepala_mekanik_harian.php';
if(file_exists($kepala_mekanik_helper)) {
    include_once $kepala_mekanik_helper;
    $kepala_mekanik_harian = getKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
    $has_kepala_mekanik_harian = hasKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
} else {
    $kepala_mekanik_harian = null;
    $has_kepala_mekanik_harian = false;
}

// Calculate totals if not set
$total_service = $total_service ?? 0;
$total_barang = $total_barang ?? 0;
$tot = $tot ?? ($total_service + $total_barang);
$discount_amount = $discount_amount ?? 0;
$net = $net ?? $tot;
$bayar = $bayar ?? 0;
$kembalian = $kembalian ?? 0;

// Get auto discount from member
$auto_discount_percent = 0;
if(!empty($no_pelanggan) && function_exists('getDiskonPelanggan')) {
    $auto_discount_percent = getDiskonPelanggan($koneksi, $no_pelanggan);
}

if (!function_exists('buildServiceStaffOptions')) {
    function buildServiceStaffOptions($koneksi, $kodeCabang, array $posisiList, $fallbackTable = '')
    {
        $options = [];
        $kodeCabang = mysqli_real_escape_string($koneksi, (string) $kodeCabang);
        $posisiSafe = array_map(function ($item) use ($koneksi) {
            return "'" . mysqli_real_escape_string($koneksi, (string) $item) . "'";
        }, $posisiList);
        $posisiSql = implode(',', $posisiSafe);

        $sqlPrimary = "SELECT nama_lengkap AS nama
                       FROM tbuser_karyawan
                       WHERE kode_posisi IN ({$posisiSql})
                       AND (
                            tanggal_keluar IS NULL
                            OR CAST(tanggal_keluar AS CHAR(10)) IN ('', '0000-00-00')
                       )
                       AND (kode_cabang = '{$kodeCabang}' OR kode_cabang IN ('CAB001', 'ALL') OR kode_cabang IS NULL OR kode_cabang = '')
                       ORDER BY nama_lengkap";
        $resultPrimary = mysqli_query($koneksi, $sqlPrimary);
        if ($resultPrimary instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($resultPrimary)) {
                $nama = trim((string) ($row['nama'] ?? ''));
                if ($nama !== '') {
                    $options[$nama] = $nama;
                }
            }
        }

        if (!empty($options) || $fallbackTable === '') {
            return array_values($options);
        }

        $fallbackSafe = mysqli_real_escape_string($koneksi, $fallbackTable);
        $sqlFallback = "SELECT nama
                        FROM {$fallbackSafe}
                        WHERE status = 'aktif'
                        ORDER BY nama";
        $resultFallback = mysqli_query($koneksi, $sqlFallback);
        if ($resultFallback instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($resultFallback)) {
                $nama = trim((string) ($row['nama'] ?? ''));
                if ($nama !== '') {
                    $options[$nama] = $nama;
                }
            }
        }

        return array_values($options);
    }
}

$opsi_kepala_mekanik = buildServiceStaffOptions($koneksi, $kd_cabang, ['KM'], 'tblmekanik');
$opsi_admin_service = buildServiceStaffOptions($koneksi, $kd_cabang, ['CS', 'KSR', 'ADM']);
$opsi_mekanik_service = buildServiceStaffOptions($koneksi, $kd_cabang, ['MK'], 'tblmekanik');
?>

<!-- Kepala Mekanik Alert -->
<?php if(!$has_kepala_mekanik_harian): ?>
<div class="rd-alert warning" style="margin-bottom: 20px;">
    <i class="fa fa-exclamation-triangle"></i>
    <div style="flex: 1;">
        <strong>Perhatian!</strong> Belum ada input kepala mekanik untuk hari ini.
    </div>
    <a href="input_kepala_mekanik_harian.php" class="rd-btn sm warning">
        <i class="fa fa-plus"></i> Input Kepala Mekanik
    </a>
</div>
<?php else: ?>
<div class="rd-alert success" style="margin-bottom: 20px;">
    <i class="fa fa-check-circle"></i>
    <div style="flex: 1;">
        <strong>Kepala Mekanik Hari Ini:</strong>
        <?= htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_1']) ?>
        <?php if($kepala_mekanik_harian['kepala_mekanik_2']): ?>
        & <?= htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_2']) ?>
        <span class="rd-badge success">Backup</span>
        <?php endif; ?>
    </div>
    <button type="button" class="rd-btn sm outline-success" onclick="autoFillKepalaMetanik(true)">
        <i class="fa fa-magic"></i> Auto Fill
    </button>
</div>
<?php endif; ?>

<!-- Payment Section -->
<div class="rd-card success">
    <div class="rd-card-header">
        <h5><i class="fa fa-money-bill-wave"></i> Pembayaran Service</h5>
    </div>
    <div class="rd-card-body">
        <!-- Customer Info -->
        <div class="rd-alert info" style="margin-bottom: 20px;">
            <i class="fa fa-user"></i>
            <div>
                <strong>Pelanggan:</strong> <?= htmlspecialchars($namapelanggan ?? '-') ?>
                <?php if($auto_discount_percent > 0): ?>
                <span class="rd-badge gold" style="margin-left: 8px;">
                    Member <?= $auto_discount_percent ?>% OFF
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Left Column -->
            <div>
                <div class="rd-form-group">
                    <label class="rd-label">Total Jasa</label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon">Rp</span>
                        <input type="text" class="rd-input text-right" id="txttotal_jasa_v2" name="txttotal_jasa"
                               value="<?= number_format($total_service, 0, ',', '.') ?>" readonly
                               style="background: var(--rd-bg-light);">
                    </div>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label">Total Barang</label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon">Rp</span>
                        <input type="text" class="rd-input text-right" id="txttotal_barang_v2" name="txttotal_barang"
                               value="<?= number_format($total_barang, 0, ',', '.') ?>" readonly
                               style="background: var(--rd-bg-light);">
                    </div>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label"><strong>Subtotal</strong></label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon">Rp</span>
                        <input type="text" class="rd-input text-right" id="txttotal_v2" name="txttotal"
                               value="<?= number_format($tot, 0, ',', '.') ?>" readonly
                               style="background: var(--rd-bg-light); font-weight: 600;">
                    </div>
                </div>

                <div class="rd-divider"></div>

                <div class="rd-form-group">
                    <label class="rd-label">Diskon Member (%)</label>
                    <div class="rd-input-group">
                        <input type="number" class="rd-input" id="txtdiskon_member_v2" name="txtdiskon_member"
                               value="<?= $auto_discount_percent ?>" readonly
                               style="background: var(--rd-bg-light);">
                        <span class="rd-input-addon">%</span>
                    </div>
                    <small class="rd-text-muted"><i class="fa fa-info-circle"></i> Otomatis dari kategori member</small>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label">Diskon Tambahan (%)</label>
                    <div class="rd-input-group">
                        <input type="number" class="rd-input" id="txtpotfaktur_persen_v2" name="txtpotfaktur_persen"
                               value="0" min="0" max="100" step="0.01" onchange="hitungTotalV2()">
                        <span class="rd-input-addon">%</span>
                    </div>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label">Total Diskon (Rp)</label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon">Rp</span>
                        <input type="text" class="rd-input text-right" id="txtpotfaktur_nom_v2" name="txtpotfaktur_nom"
                               value="<?= number_format($discount_amount, 0, ',', '.') ?>" readonly
                               style="background: var(--rd-bg-light);">
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <div class="rd-form-group">
                    <label class="rd-label">PPN (%)</label>
                    <div class="rd-input-group">
                        <input type="number" class="rd-input" id="txtpajak_persen_v2" name="txtpajak_persen"
                               value="0" min="0" max="100" step="0.01" onchange="hitungTotalV2()">
                        <span class="rd-input-addon">%</span>
                    </div>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label">PPN (Rp)</label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon">Rp</span>
                        <input type="text" class="rd-input text-right" id="txtpajak_nom_v2" name="txtpajak_nom"
                               value="0" readonly style="background: var(--rd-bg-light);">
                    </div>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label" style="font-size: 15px;"><strong>Total Bayar</strong></label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon" style="background: var(--rd-success); color: white;">Rp</span>
                        <input type="text" class="rd-input text-right" id="txtnet_v2" name="txtnet"
                               value="<?= number_format($net, 0, ',', '.') ?>" readonly
                               style="font-size: 18px; font-weight: 700; background: rgba(92, 184, 92, 0.1); color: var(--rd-success);">
                    </div>
                </div>

                <div class="rd-divider"></div>

                <div class="rd-form-group">
                    <label class="rd-label">Metode Pembayaran</label>
                    <select class="rd-input" id="metode_pembayaran_v2" name="metode_pembayaran" onchange="toggleBuktiV2()">
                        <option value="Tunai" <?= (isset($metode_pembayaran) && $metode_pembayaran == 'Tunai') ? 'selected' : '' ?>>💵 Tunai</option>
                        <option value="Transfer Bank" <?= (isset($metode_pembayaran) && $metode_pembayaran == 'Transfer Bank') ? 'selected' : '' ?>>🏦 Transfer Bank</option>
                        <option value="Kartu Kredit" <?= (isset($metode_pembayaran) && $metode_pembayaran == 'Kartu Kredit') ? 'selected' : '' ?>>💳 Kartu Kredit</option>
                        <option value="Kartu Debit" <?= (isset($metode_pembayaran) && $metode_pembayaran == 'Kartu Debit') ? 'selected' : '' ?>>💳 Kartu Debit</option>
                        <option value="E-Wallet" <?= (isset($metode_pembayaran) && $metode_pembayaran == 'E-Wallet') ? 'selected' : '' ?>>📱 E-Wallet</option>
                        <option value="QRIS" <?= (isset($metode_pembayaran) && $metode_pembayaran == 'QRIS') ? 'selected' : '' ?>>📷 QRIS</option>
                    </select>
                </div>

                <div class="rd-form-group" id="bukti_pembayaran_group_v2" style="display: none;">
                    <label class="rd-label">Bukti Pembayaran</label>
                    <input type="file" class="rd-input" name="bukti_pembayaran" accept="image/*,.pdf">
                    <small class="rd-text-muted">Upload bukti transfer (JPG, PNG, PDF - Max 2MB)</small>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label">Jumlah Bayar</label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon">Rp</span>
                        <input type="text" class="rd-input text-right" id="txtbayar_v2" name="txtbayar"
                               value="<?= number_format($bayar, 0, ',', '.') ?>" onkeyup="hitungKembalianV2()">
                    </div>
                </div>

                <div class="rd-form-group">
                    <label class="rd-label">Kembalian</label>
                    <div class="rd-input-group">
                        <span class="rd-input-addon">Rp</span>
                        <input type="text" class="rd-input text-right" id="txtkembalian_v2" name="txtkembalian"
                               value="<?= number_format($kembalian, 0, ',', '.') ?>" readonly
                               style="background: rgba(91, 192, 222, 0.1); color: var(--rd-info); font-weight: 600;">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mechanic Assignment Section -->
<div class="rd-card purple">
    <div class="rd-card-header">
        <h5><i class="fa fa-users-cog"></i> Penugasan Mekanik</h5>
    </div>
    <div class="rd-card-body">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <!-- Left Column -->
            <div>
                <!-- Kepala Mekanik 1 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-user-tie"></i> Kepala Mekanik 1</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cbokepala_mekanik1" id="cbokepala_mekanik1_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_kepala1_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_kepala_mekanik as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($kepala_mekanik1) && $kepala_mekanik1 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_kepala1" id="txtpersen_kepala1_v2" class="rd-input text-center"
                                   value="<?= $persen_kepala1 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>

                <!-- Admin 1 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-user-edit"></i> Admin/Kasir 1</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cboadmin1" id="cboadmin1_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_admin1_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_admin_service as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($admin1) && $admin1 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_admin1" id="txtpersen_admin1_v2" class="rd-input text-center"
                                   value="<?= $persen_admin1 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>

                <!-- Mekanik 1 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-wrench"></i> Mekanik 1</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cbomekanik1" id="cbomekanik1_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_mekanik1_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_mekanik_service as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($mekanik1) && $mekanik1 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_mekanik1" id="txtpersen_mekanik1_v2" class="rd-input text-center"
                                   value="<?= $persen_kerja1 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>

                <!-- Mekanik 2 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-wrench"></i> Mekanik 2</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cbomekanik2" id="cbomekanik2_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_mekanik2_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_mekanik_service as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($mekanik2) && $mekanik2 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_mekanik2" id="txtpersen_mekanik2_v2" class="rd-input text-center"
                                   value="<?= $persen_kerja2 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <!-- Kepala Mekanik 2 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-user-tie"></i> Kepala Mekanik 2</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cbokepala_mekanik2" id="cbokepala_mekanik2_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_kepala2_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_kepala_mekanik as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($kepala_mekanik2) && $kepala_mekanik2 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_kepala2" id="txtpersen_kepala2_v2" class="rd-input text-center"
                                   value="<?= $persen_kepala2 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>

                <!-- Admin 2 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-user-edit"></i> Admin/Kasir 2</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cboadmin2" id="cboadmin2_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_admin2_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_admin_service as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($admin2) && $admin2 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_admin2" id="txtpersen_admin2_v2" class="rd-input text-center"
                                   value="<?= $persen_admin2 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>

                <!-- Mekanik 3 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-wrench"></i> Mekanik 3</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cbomekanik3" id="cbomekanik3_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_mekanik3_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_mekanik_service as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($mekanik3) && $mekanik3 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_mekanik3" id="txtpersen_mekanik3_v2" class="rd-input text-center"
                                   value="<?= $persen_kerja3 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>

                <!-- Mekanik 4 -->
                <div class="rd-form-group">
                    <label class="rd-label"><i class="fa fa-wrench"></i> Mekanik 4</label>
                    <div class="rd-flex rd-gap-8">
                        <select name="cbomekanik4" id="cbomekanik4_v2" class="rd-input" style="flex: 1;" onchange="set100Percent(this, 'txtpersen_mekanik4_v2')">
                            <option value="">- Pilih -</option>
                            <?php foreach($opsi_mekanik_service as $nama_staff): ?>
                            <option value="<?= htmlspecialchars($nama_staff, ENT_QUOTES) ?>" <?= (isset($mekanik4) && $mekanik4 == $nama_staff) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($nama_staff) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="rd-input-group" style="flex: 0 0 100px;">
                            <input type="number" name="txtpersen_mekanik4" id="txtpersen_mekanik4_v2" class="rd-input text-center"
                                   value="<?= $persen_kerja4 ?? 0 ?>" min="0" max="100">
                            <span class="rd-input-addon">%</span>
                        </div>
                    </div>
                </div>
            </div>
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
            <div class="rd-form-group" style="display:none;">
                <label class="rd-label">KM Saat Ini</label>
                <div class="rd-input-group">
                    <input type="number" class="rd-input" id="txtkm_skr_v2" name="txtkm_skr"
                           value="<?= $km_skr ?? 0 ?>" min="0">
                    <span class="rd-input-addon">KM</span>
                </div>
            </div>
            <div class="rd-form-group">
                <label class="rd-label">KM Service Berikutnya</label>
                <div class="rd-input-group">
                    <input type="number" class="rd-input" id="txtkm_next_v2" name="txtkm_next"
                           value="<?= $km_berikut ?? 0 ?>">
                    <span class="rd-input-addon">KM</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="rd-card" style="background: var(--rd-bg-light);">
    <div class="rd-card-body" style="padding: 20px;">
        <div class="rd-flex-between">
            <div class="rd-flex rd-gap-8">
                <button type="button" class="rd-btn outline-danger" onclick="cancelServiceV2()">
                    <i class="fa fa-times"></i> Cancel Service
                </button>
                <button type="button" class="rd-btn outline-primary" onclick="printEstimasiV2()">
                    <i class="fa fa-print"></i> Print Estimasi
                </button>
            </div>
            <div class="rd-flex rd-gap-8">
                <button type="submit" name="btnsimpan" class="rd-btn primary" style="padding: 12px 24px;" onclick="return validateMechanicPersen(event)">
                    <i class="fa fa-save"></i> Simpan
                </button>
                <button type="submit" name="btnbayar" class="rd-btn success" style="padding: 12px 24px;" onclick="return validateMechanicPersen(event)">
                    <i class="fa fa-check-circle"></i> Proses Bayar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle bukti pembayaran
function toggleBuktiV2() {
    var metode = $('#metode_pembayaran_v2').val();
    if(metode === 'Tunai') {
        $('#bukti_pembayaran_group_v2').hide();
    } else {
        $('#bukti_pembayaran_group_v2').show();
    }
}

// Calculate total
function hitungTotalV2() {
    // Get values
    var subtotal = parseRupiah($('#txttotal_v2').val()) || 0;
    var diskonTambahan = parseFloat($('#txtpotfaktur_persen_v2').val()) || 0;
    var ppnPersen = parseFloat($('#txtpajak_persen_v2').val()) || 0;

    // Diskon member sudah diterapkan per-item (txttotal_jasa/txttotal_barang sudah net diskon member),
    // jadi hanya Diskon Tambahan yang dipotong di level invoice agar tidak dobel.
    var totalDiskon = subtotal * (diskonTambahan / 100);
    var subtotalSetelahDiskon = subtotal - totalDiskon;

    // Calculate PPN
    var ppnNominal = subtotalSetelahDiskon * (ppnPersen / 100);

    // Calculate net
    var net = subtotalSetelahDiskon + ppnNominal;

    // Update fields
    $('#txtpotfaktur_nom_v2').val(formatRupiah(totalDiskon));
    $('#txtpajak_nom_v2').val(formatRupiah(ppnNominal));
    $('#txtnet_v2').val(formatRupiah(net));
}

// Calculate kembalian
function hitungKembalianV2() {
    var net = parseRupiah($('#txtnet_v2').val()) || 0;
    var bayar = parseRupiah($('#txtbayar_v2').val()) || 0;
    var kembalian = bayar - net;

    if(kembalian < 0) kembalian = 0;
    $('#txtkembalian_v2').val(formatRupiah(kembalian));
}

// Format helpers
function formatRupiah(angka) {
    return angka.toLocaleString('id-ID');
}

function parseRupiah(str) {
    if(!str) return 0;
    return parseInt(str.toString().replace(/\./g, '').replace(/,/g, '')) || 0;
}

// Auto fill kepala mekanik
function autoFillKepalaMetanik(showAlert) {
    <?php if($has_kepala_mekanik_harian && $kepala_mekanik_harian): ?>
    $('#cbokepala_mekanik1_v2').val('<?= addslashes($kepala_mekanik_harian['kepala_mekanik_1'] ?? '') ?>');
    <?php if($kepala_mekanik_harian['kepala_mekanik_2']): ?>
    $('#cbokepala_mekanik2_v2').val('<?= addslashes($kepala_mekanik_harian['kepala_mekanik_2']) ?>');
    <?php endif; ?>
    if(showAlert) alert('Kepala mekanik berhasil diisi otomatis!');
    <?php else: ?>
    if(showAlert) alert('Tidak ada data kepala mekanik hari ini');
    <?php endif; ?>
}

// Cancel service
function cancelServiceV2() {
    if(confirm('Yakin ingin membatalkan service ini?')) {
        if($('#modalCancelService').length) {
            $('#modalCancelService').modal('show');
        } else {
            alert('Modal cancel tidak tersedia');
        }
    }
}

// Print estimasi
function printEstimasiV2() {
    window.open('servis-estimasi-pdf.php?no=<?= $no_service ?>', '_blank');
}

// Initialize
$(document).ready(function() {
    toggleBuktiV2();
    hitungTotalV2();
});

// Auto set 100% logic
function set100Percent(selectEl, targetId) {
    if(selectEl.value !== "") {
        $('#' + targetId).val(100);
    } else {
        // Optional: Reset to 0 if cleared, or leave as is? 
        // User asked "jika pilih sudah otomatis 100%", implying connection.
        // Let's reset to 0 to be safe/clean.
        $('#' + targetId).val(0);
    }
}

// Validate Mechanic Percentages
function validateMechanicPersen(e) {
    // Collect specific groups
    var km1 = parseFloat($('#txtpersen_kepala1_v2').val()) || 0;
    var km2 = parseFloat($('#txtpersen_kepala2_v2').val()) || 0;
    
    var adm1 = parseFloat($('#txtpersen_admin1_v2').val()) || 0;
    var adm2 = parseFloat($('#txtpersen_admin2_v2').val()) || 0;
    
    var mk1 = parseFloat($('#txtpersen_mekanik1_v2').val()) || 0;
    var mk2 = parseFloat($('#txtpersen_mekanik2_v2').val()) || 0;
    var mk3 = parseFloat($('#txtpersen_mekanik3_v2').val()) || 0;
    var mk4 = parseFloat($('#txtpersen_mekanik4_v2').val()) || 0;

    // Check KM
    var hasKM = $('#cbokepala_mekanik1_v2').val() || $('#cbokepala_mekanik2_v2').val();
    // User requested strict warning for Admin & Mekanik "jika 0%".
    // Assuming KM is also mandatory if following the pattern, OR we leave KM flexible?
    // "mekanik dan admin" was specifically mentioned.
    // Let's make KM mandatory too for consistency, or keep "if(hasKM)"?
    // Let's keep KM flexible (only if selected) unless told otherwise, 
    // BUT Admin and Mechanic are definitely mandatory now.
    
    // Actually, usually KM is mandatory daily. Let's make it mandatory 
    // IF the user considers KM as "Mekanik" in their sentence. 
    // To be safe, I will enforce it for Admin and Mechanic (Worker) as requested.
    
    // Check KM - MANDATORY (selaras dengan validasi server: 'Kepala Mekanik wajib diisi')
    if(!hasKM) {
        alert('Kepala Mekanik wajib diisi.');
        e.preventDefault();
        return false;
    }
    if(Math.abs((km1 + km2) - 100) > 0.01) {
        alert('Total Persentase KEPALA MEKANIK harus 100% (Saat ini: ' + (km1+km2) + '%)');
        e.preventDefault();
        return false;
    }

    // Check Admin - MANDATORY
    if(Math.abs((adm1 + adm2) - 100) > 0.01) {
        alert('Total Persentase ADMIN/KASIR harus 100% (Saat ini: ' + (adm1+adm2) + '%)');
        e.preventDefault();
        return false;
    }

    // Check Mekanik - MANDATORY
    if(Math.abs((mk1 + mk2 + mk3 + mk4) - 100) > 0.01) {
        alert('Total Persentase MEKANIK harus 100% (Saat ini: ' + (mk1+mk2+mk3+mk4) + '%)');
        e.preventDefault();
        return false;
    }

    return true;
}
</script>
