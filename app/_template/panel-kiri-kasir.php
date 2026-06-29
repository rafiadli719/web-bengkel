<?php
/**
 * Panel Kiri Kasir — Info Kendaraan + Keluhan Aktif + Mekanik
 * Included by: servis-input-reguler.php, servis-input-reguler-jemput.php, servis-input-garansi.php
 */

// ---- Kepala Mekanik Harian ----
$_kmh_helper = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'get_kepala_mekanik_harian.php';
if (file_exists($_kmh_helper)) {
    include_once $_kmh_helper;
    $kepala_mekanik_harian     = getKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
    $has_kepala_mekanik_harian = hasKepalaMetanikHarian($koneksi, $kd_cabang, isset($tanggal_srv) ? $tanggal_srv : null);
} else {
    $kepala_mekanik_harian     = null;
    $has_kepala_mekanik_harian = false;
}

// ---- Staff Options ----
if (!function_exists('buildServiceStaffOptions')) {
    function buildServiceStaffOptions($koneksi, $kodeCabang, array $posisiList, $fallbackTable = '')
    {
        $options    = [];
        $kodeSafe   = mysqli_real_escape_string($koneksi, (string) $kodeCabang);
        $posisiSafe = array_map(function ($p) use ($koneksi) {
            return "'" . mysqli_real_escape_string($koneksi, (string) $p) . "'";
        }, $posisiList);
        $posisiSql = implode(',', $posisiSafe);
        $sql = "SELECT nama_lengkap AS nama FROM tbuser_karyawan
                WHERE kode_posisi IN ({$posisiSql})
                  AND (tanggal_keluar IS NULL OR CAST(tanggal_keluar AS CHAR(10)) IN ('','0000-00-00'))
                  AND (kode_cabang='{$kodeSafe}' OR kode_cabang IN ('CAB001','ALL') OR kode_cabang IS NULL OR kode_cabang='')
                ORDER BY nama_lengkap";
        $res = mysqli_query($koneksi, $sql);
        if ($res instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($res)) {
                $n = trim((string)($row['nama'] ?? ''));
                if ($n !== '') $options[$n] = $n;
            }
        }
        if (!empty($options) || $fallbackTable === '') return array_values($options);
        $fb  = mysqli_real_escape_string($koneksi, $fallbackTable);
        $res2 = mysqli_query($koneksi, "SELECT nama FROM {$fb} WHERE status='aktif' ORDER BY nama");
        if ($res2 instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($res2)) {
                $n = trim((string)($row['nama'] ?? ''));
                if ($n !== '') $options[$n] = $n;
            }
        }
        return array_values($options);
    }
}

$opsi_kepala_mekanik  = buildServiceStaffOptions($koneksi, $kd_cabang, ['KM'], 'tblmekanik');
$opsi_admin_service   = buildServiceStaffOptions($koneksi, $kd_cabang, ['CS','KSR','ADM']);
$opsi_mekanik_service = buildServiceStaffOptions($koneksi, $kd_cabang, ['MK'], 'tblmekanik');

// ---- Member info ----
$_minfo = [];
if (!empty($kode_pelanggan) && function_exists('getMemberCategoryInfo')) {
    $_minfo = getMemberCategoryInfo($koneksi, $kode_pelanggan);
}

// ---- Keluhan aktif ----
$_kel_list = [];
if (!empty($no_service)) {
    $q = mysqli_query($koneksi, "SELECT keluhan, status_pengerjaan FROM tbservis_keluhan_status
                                  WHERE no_service='" . mysqli_real_escape_string($koneksi,$no_service) . "'
                                  ORDER BY id ASC");
    if ($q) { while ($r = mysqli_fetch_assoc($q)) $_kel_list[] = $r; }
}
?>

<!-- Nopol -->
<p class="ks-section-hdr"><i class="fa fa-motorcycle"></i> Kendaraan</p>
<div class="ks-plat-card">
    <?php if(!empty($no_polisi)): ?>
    <span class="ks-plat-val"><?= htmlspecialchars($no_polisi) ?></span>
    <span class="ks-plat-sub"><?= htmlspecialchars(trim(($jenis ?? '') . ' ' . ($merek ?? ''))) ?></span>
    <a href="#" class="ks-riwayat-btn" onclick="showRiwayatKendaraan();return false;" title="Riwayat Kendaraan">
        <i class="fa fa-history"></i>
    </a>
    <?php else: ?>
    <span class="ks-plat-none"><i class="fa fa-exclamation-triangle"></i> Kendaraan belum dipilih</span>
    <?php endif; ?>
</div>

<?php if(!empty($no_polisi)): ?>
<div class="ks-vehicle-grid">
    <div class="vg-item"><span class="vg-label">Warna</span><span class="vg-val"><?= htmlspecialchars($warna ?? '-') ?></span></div>
    <div class="vg-item"><span class="vg-label">Tahun</span><span class="vg-val"><?= htmlspecialchars($tahun_kendaraan ?? '-') ?></span></div>
    <?php if(!empty($no_rangka)): ?>
    <div class="vg-item" style="grid-column:span 2;">
        <span class="vg-label">No. Rangka</span>
        <span class="vg-val" style="font-size:10px;font-family:monospace;"><?= htmlspecialchars($no_rangka) ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Pelanggan -->
<p class="ks-section-hdr"><i class="fa fa-user"></i> Pelanggan</p>
<div class="ks-pelanggan-card" style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;">
    <div>
        <div class="ks-pelanggan-nama"><?= htmlspecialchars($namapelanggan ?: '-') ?></div>
        <?php if(!empty($kode_pelanggan)): ?>
        <div class="ks-pelanggan-meta"><?= htmlspecialchars($kode_pelanggan) ?></div>
        <?php endif; ?>
        <?php if(!empty($_minfo)): ?>
        <div class="ks-pelanggan-badges">
            <span class="ks-member-badge <?= $_minfo['badge_class'] ?? 'neutral' ?>">
                <?= htmlspecialchars($_minfo['kategori'] ?? 'REGULAR') ?>
            </span>
            <?php if(($_minfo['diskon'] ?? 0) > 0): ?>
            <span class="ks-member-badge neutral">Diskon <?= $_minfo['diskon'] ?>%</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php if(!empty($kode_pelanggan)): ?>
    <button type="button" class="ks-btn-mini outline" style="flex-shrink:0;padding:3px 7px;"
            onclick="showStatistikPelanggan()" title="Statistik Pelanggan">
        <i class="fa fa-chart-bar"></i>
    </button>
    <?php endif; ?>
</div>

<!-- Service Info -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:3px 8px;padding:4px 0;font-size:11px;">
    <div>
        <div style="font-size:9px;color:#8a94a6;text-transform:uppercase;letter-spacing:.05em;">No. Service</div>
        <div style="font-weight:700;color:#4a90d9;"><?= htmlspecialchars($no_service ?: '-') ?></div>
    </div>
    <div>
        <div style="font-size:9px;color:#8a94a6;text-transform:uppercase;letter-spacing:.05em;">Tipe</div>
        <div style="font-weight:600;color:#333;"><?= htmlspecialchars($tipe_servis ?? ($tipe ?? '-')) ?></div>
    </div>
    <div>
        <div style="font-size:9px;color:#8a94a6;text-transform:uppercase;letter-spacing:.05em;">Tanggal</div>
        <div style="font-weight:600;color:#333;"><?= isset($tanggal) && $tanggal ? htmlspecialchars($tanggal) : '-' ?></div>
    </div>
    <div>
        <div style="font-size:9px;color:#8a94a6;text-transform:uppercase;letter-spacing:.05em;">Status</div>
        <span class="ks-status-pill <?= htmlspecialchars($status_servis ?? 'datang') ?>">
            <?= strtoupper($status_servis ?? 'datang') ?>
        </span>
    </div>
</div>

<!-- KM -->
<p class="ks-section-hdr"><i class="fa fa-tachometer-alt"></i> Kilometer</p>
<div class="ks-km-row">
    <div class="ks-km-group">
        <label for="txtkm_skr_v2">KM Saat Ini</label>
        <input type="number" class="ks-km-input" id="txtkm_skr_v2" name="txtkm_skr"
               value="<?= (int)($km_skr ?? 0) ?>" min="0">
    </div>
    <div class="ks-km-group">
        <label for="txtkm_next_v2">KM Berikutnya</label>
        <input type="number" class="ks-km-input" id="txtkm_next_v2" name="txtkm_next"
               value="<?= (int)($km_berikut ?? 0) ?>">
    </div>
</div>

<!-- Keluhan Aktif Servis Ini -->
<?php if(!empty($no_service)): ?>
<p class="ks-section-hdr">
    <i class="fa fa-comment-alt"></i> Keluhan Servis Ini
    <?php if(!empty($_kel_list)): ?>
    <span class="ks-badge warning" style="margin-left:4px;"><?= count($_kel_list) ?></span>
    <?php endif; ?>
</p>
<div>
    <input type="hidden" name="kode_keluhan" id="kode_keluhan_v2" />
    <div class="ks-keluhan-input-row">
        <input type="text" name="txtkeluhan" id="txtkeluhan_v2" class="ks-keluhan-input"
               placeholder="Ketik atau pilih keluhan...">
        <button type="button" class="ks-btn-srch-keluhan"
                onclick="$('#modal-search-keluhan').modal('show')" title="Cari dari master">
            <i class="fa fa-search"></i>
        </button>
        <button class="ks-btn-add-keluhan" type="submit" name="btnaddkeluhan"
                onclick="return validateKeluhanV2()">
            <i class="fa fa-plus"></i>
        </button>
    </div>
    <?php if(!empty($_kel_list)): ?>
    <div class="ks-keluhan-list">
        <?php foreach($_kel_list as $i => $kl):
            $kl_st  = $kl['status_pengerjaan'] ?? 'datang';
            $kl_cls = in_array($kl_st,['selesai','diproses','tidak_selesai']) ? $kl_st : 'datang';
        ?>
        <div class="ks-keluhan-item <?= $kl_cls ?>">
            <span class="ks-keluhan-no"><?= $i+1 ?></span>
            <span class="ks-keluhan-teks"><?= htmlspecialchars($kl['keluhan']) ?></span>
            <span class="ks-keluhan-badge <?= $kl_cls ?>"><?= ucfirst(str_replace('_',' ',$kl_st)) ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Kepala Mekanik Harian Info -->
<p class="ks-section-hdr"><i class="fa fa-users-cog"></i> Mekanik</p>
<?php if(!$has_kepala_mekanik_harian): ?>
<div class="ks-km-harian warning">
    <i class="fa fa-exclamation-triangle"></i>
    <span style="flex:1;font-size:10px;">KM hari ini belum diisi</span>
    <a href="input_kepala_mekanik_harian.php" class="ks-km-harian-link"><i class="fa fa-plus"></i> Input</a>
</div>
<?php else: ?>
<div class="ks-km-harian ok">
    <i class="fa fa-check-circle"></i>
    <span style="flex:1;font-size:10px;">
        <strong><?= htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_1'] ?? '') ?></strong>
        <?php if(!empty($kepala_mekanik_harian['kepala_mekanik_2'])): ?>
        &amp; <?= htmlspecialchars($kepala_mekanik_harian['kepala_mekanik_2']) ?>
        <?php endif; ?>
    </span>
    <button type="button" class="ks-btn-mini success" style="font-size:9px;padding:2px 6px;"
            onclick="autoFillKepalaMetanik(false)" title="Auto isi">
        <i class="fa fa-magic"></i>
    </button>
</div>
<?php endif; ?>

<!-- Mekanik Assignment -->
<div class="ks-mekanik-block">
    <span class="ks-mekanik-group-hdr">Kepala Mekanik</span>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3px;">
        <div>
            <div class="ks-staff-label">KM 1</div>
            <div class="ks-staff-row">
                <select name="cbokepala_mekanik1" id="cbokepala_mekanik1_v2" onchange="autoDistributePersenGroup('km')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_kepala_mekanik as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($kepala_mekanik1)&&$kepala_mekanik1==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_kepala1" id="txtpersen_kepala1_v2" value="<?= $persen_kepala1 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
        <div>
            <div class="ks-staff-label">KM 2</div>
            <div class="ks-staff-row">
                <select name="cbokepala_mekanik2" id="cbokepala_mekanik2_v2" onchange="autoDistributePersenGroup('km')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_kepala_mekanik as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($kepala_mekanik2)&&$kepala_mekanik2==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_kepala2" id="txtpersen_kepala2_v2" value="<?= $persen_kepala2 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
    </div>

    <span class="ks-mekanik-group-hdr">Admin / Kasir</span>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3px;">
        <div>
            <div class="ks-staff-label">Admin 1</div>
            <div class="ks-staff-row">
                <select name="cboadmin1" id="cboadmin1_v2" onchange="autoDistributePersenGroup('admin')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_admin_service as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($admin1)&&$admin1==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_admin1" id="txtpersen_admin1_v2" value="<?= $persen_admin1 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
        <div>
            <div class="ks-staff-label">Admin 2</div>
            <div class="ks-staff-row">
                <select name="cboadmin2" id="cboadmin2_v2" onchange="autoDistributePersenGroup('admin')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_admin_service as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($admin2)&&$admin2==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_admin2" id="txtpersen_admin2_v2" value="<?= $persen_admin2 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
    </div>

    <span class="ks-mekanik-group-hdr">Mekanik</span>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:3px;">
        <div>
            <div class="ks-staff-label">MK 1</div>
            <div class="ks-staff-row">
                <select name="cbomekanik1" id="cbomekanik1_v2" onchange="autoDistributePersenGroup('mekanik')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_mekanik_service as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($mekanik1)&&$mekanik1==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_mekanik1" id="txtpersen_mekanik1_v2" value="<?= $persen_kerja1 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
        <div>
            <div class="ks-staff-label">MK 2</div>
            <div class="ks-staff-row">
                <select name="cbomekanik2" id="cbomekanik2_v2" onchange="autoDistributePersenGroup('mekanik')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_mekanik_service as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($mekanik2)&&$mekanik2==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_mekanik2" id="txtpersen_mekanik2_v2" value="<?= $persen_kerja2 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
        <div>
            <div class="ks-staff-label">MK 3</div>
            <div class="ks-staff-row">
                <select name="cbomekanik3" id="cbomekanik3_v2" onchange="autoDistributePersenGroup('mekanik')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_mekanik_service as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($mekanik3)&&$mekanik3==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_mekanik3" id="txtpersen_mekanik3_v2" value="<?= $persen_kerja3 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
        <div>
            <div class="ks-staff-label">MK 4</div>
            <div class="ks-staff-row">
                <select name="cbomekanik4" id="cbomekanik4_v2" onchange="autoDistributePersenGroup('mekanik')">
                    <option value="">- Pilih -</option>
                    <?php foreach($opsi_mekanik_service as $ns): ?>
                    <option value="<?= htmlspecialchars($ns,ENT_QUOTES) ?>" <?= (isset($mekanik4)&&$mekanik4==$ns)?'selected':'' ?>><?= htmlspecialchars($ns) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="txtpersen_mekanik4" id="txtpersen_mekanik4_v2" value="<?= $persen_kerja4 ?? 0 ?>" min="0" max="100" title="%">
            </div>
        </div>
    </div>
</div>

<!-- Left Footer Actions -->
<div class="ks-left-actions">
    <button type="button" class="ks-btn-mini primary" id="btnSaveMechanicData"
            onclick="saveMechanicDataV2()">
        <i class="fa fa-user-cog"></i> Simpan Mekanik
    </button>
    <button type="button" class="ks-btn-mini info"
            onclick="printEstimasiV2()">
        <i class="fa fa-print"></i> Estimasi
    </button>
</div>

<script>
function autoFillKepalaMetanik(showAlert) {
    <?php if($has_kepala_mekanik_harian && $kepala_mekanik_harian): ?>
    $('#cbokepala_mekanik1_v2').val('<?= addslashes($kepala_mekanik_harian['kepala_mekanik_1'] ?? '') ?>').trigger('change');
    <?php if(!empty($kepala_mekanik_harian['kepala_mekanik_2'])): ?>
    $('#cbokepala_mekanik2_v2').val('<?= addslashes($kepala_mekanik_harian['kepala_mekanik_2']) ?>').trigger('change');
    <?php endif; ?>
    autoDistributePersenGroup('km');
    if (showAlert) alert('Kepala mekanik berhasil diisi otomatis!');
    <?php else: ?>
    if (showAlert) alert('Tidak ada data kepala mekanik hari ini');
    <?php endif; ?>
}

function autoDistributePersenGroup(group) {
    var groups = {
        km:     { selects:['cbokepala_mekanik1_v2','cbokepala_mekanik2_v2'],
                  persens:['txtpersen_kepala1_v2','txtpersen_kepala2_v2'] },
        admin:  { selects:['cboadmin1_v2','cboadmin2_v2'],
                  persens:['txtpersen_admin1_v2','txtpersen_admin2_v2'] },
        mekanik:{ selects:['cbomekanik1_v2','cbomekanik2_v2','cbomekanik3_v2','cbomekanik4_v2'],
                  persens:['txtpersen_mekanik1_v2','txtpersen_mekanik2_v2','txtpersen_mekanik3_v2','txtpersen_mekanik4_v2'] }
    };
    var g = groups[group]; if (!g) return;
    var active = [];
    g.selects.forEach(function(id,i){ if(($('#'+id).val()||'')!=='') active.push(i); });
    if (!active.length) { g.persens.forEach(function(id){ $('#'+id).val(0); }); return; }
    var per = Math.floor(100/active.length), rem = 100-(per*active.length);
    g.persens.forEach(function(id,i){
        var idx = active.indexOf(i);
        $('#'+id).val(idx===-1 ? 0 : per+(idx===0?rem:0));
    });
}

function validateMechanicPersen(e) {
    var km1=parseFloat($('#txtpersen_kepala1_v2').val())||0,
        km2=parseFloat($('#txtpersen_kepala2_v2').val())||0,
        ad1=parseFloat($('#txtpersen_admin1_v2').val())||0,
        ad2=parseFloat($('#txtpersen_admin2_v2').val())||0,
        mk1=parseFloat($('#txtpersen_mekanik1_v2').val())||0,
        mk2=parseFloat($('#txtpersen_mekanik2_v2').val())||0,
        mk3=parseFloat($('#txtpersen_mekanik3_v2').val())||0,
        mk4=parseFloat($('#txtpersen_mekanik4_v2').val())||0;
    var hasKM =($('#cbokepala_mekanik1_v2').val()||'')!==''||($('#cbokepala_mekanik2_v2').val()||'')!=='';
    var hasAd =($('#cboadmin1_v2').val()||'')!==''||($('#cboadmin2_v2').val()||'')!=='';
    var hasMK =($('#cbomekanik1_v2').val()||'')!==''||($('#cbomekanik2_v2').val()||'')!==''
              ||($('#cbomekanik3_v2').val()||'')!==''||($('#cbomekanik4_v2').val()||'')!=='';
    if (!hasKM) { alert('Kepala Mekanik wajib diisi.'); e.preventDefault(); return false; }
    if (Math.abs((km1+km2)-100)>0.01) { alert('Total % Kepala Mekanik harus 100% (sekarang: '+(km1+km2)+'%)'); e.preventDefault(); return false; }
    if (hasAd && Math.abs((ad1+ad2)-100)>0.01) { alert('Total % Admin/Kasir harus 100% (sekarang: '+(ad1+ad2)+'%)'); e.preventDefault(); return false; }
    if (hasMK && Math.abs((mk1+mk2+mk3+mk4)-100)>0.01) { alert('Total % Mekanik harus 100% (sekarang: '+(mk1+mk2+mk3+mk4)+'%)'); e.preventDefault(); return false; }
    return true;
}

function saveMechanicDataV2() {
    var noService = '<?= addslashes($no_service ?? '') ?>';
    if (!noService) { alert('Nomor service tidak ditemukan!'); return; }
    var btn = $('#btnSaveMechanicData');
    btn.prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');
    $.ajax({
        url:'_ajax/save_mechanic_data.php', type:'POST', dataType:'json',
        data:{
            no_service:noService,
            kepala_mekanik1:$('#cbokepala_mekanik1_v2').val()||'', persen_kepala1:$('#txtpersen_kepala1_v2').val()||0,
            kepala_mekanik2:$('#cbokepala_mekanik2_v2').val()||'', persen_kepala2:$('#txtpersen_kepala2_v2').val()||0,
            admin1:$('#cboadmin1_v2').val()||'',   persen_admin1:$('#txtpersen_admin1_v2').val()||0,
            admin2:$('#cboadmin2_v2').val()||'',   persen_admin2:$('#txtpersen_admin2_v2').val()||0,
            mekanik1:$('#cbomekanik1_v2').val()||'', persen_mekanik1:$('#txtpersen_mekanik1_v2').val()||0,
            mekanik2:$('#cbomekanik2_v2').val()||'', persen_mekanik2:$('#txtpersen_mekanik2_v2').val()||0,
            mekanik3:$('#cbomekanik3_v2').val()||'', persen_mekanik3:$('#txtpersen_mekanik3_v2').val()||0,
            mekanik4:$('#cbomekanik4_v2').val()||'', persen_mekanik4:$('#txtpersen_mekanik4_v2').val()||0,
            km_skr:$('#txtkm_skr_v2').val()||0, km_berikut:$('#txtkm_next_v2').val()||0
        },
        success:function(r){
            btn.prop('disabled',false).html('<i class="fa fa-user-cog"></i> Simpan Mekanik');
            alert(r.status==='success'?'Data mekanik berhasil disimpan!':'Error: '+r.message);
        },
        error:function(x,s,err){
            btn.prop('disabled',false).html('<i class="fa fa-user-cog"></i> Simpan Mekanik');
            alert('Gagal menyimpan: '+err);
        }
    });
}

function printEstimasiV2() {
    window.open('servis-estimasi-pdf.php?no=<?= addslashes($no_service ?? '') ?>','_blank');
}

if (typeof window.validateKeluhanV2 !== 'function') {
    window.validateKeluhanV2 = function() {
        var v = ($('#txtkeluhan_v2').val()||'').trim();
        if (!v) { alert('Keluhan tidak boleh kosong!'); return false; }
        return true;
    };
}
if (typeof window.showRiwayatKendaraan !== 'function') {
    window.showRiwayatKendaraan = function() {
        var m = document.getElementById('modalRiwayatKendaraan');
        if (m && window.jQuery && typeof $(m).modal === 'function') $(m).modal('show');
    };
}
if (typeof window.showStatistikPelanggan !== 'function') {
    window.showStatistikPelanggan = function() {
        var m = document.getElementById('modalStatistikPelanggan');
        if (m && window.jQuery && typeof $(m).modal === 'function') $(m).modal('show');
    };
}
</script>
