<?php
/**
 * Panel Kanan Kasir — Ringkasan Biaya + Pembayaran + Tombol Aksi
 * Included by: servis-input-reguler.php, servis-input-reguler-jemput.php, servis-garansi.php
 */
$total_service       = $total_service       ?? 0;
$total_barang        = $total_barang        ?? 0;
$tot                 = $tot                 ?? ($total_service + $total_barang);
$discount_amount     = $discount_amount     ?? 0;
$net                 = $net                 ?? $tot;
$bayar               = $bayar               ?? 0;
$kembalian           = $kembalian           ?? 0;
$auto_discount_percent = $auto_discount_percent ?? 0;
if (!$auto_discount_percent && !empty($no_pelanggan) && function_exists('getDiskonPelanggan')) {
    $auto_discount_percent = getDiskonPelanggan($koneksi, $no_pelanggan);
}
$metode_pembayaran = $metode_pembayaran ?? 'Tunai';
$boleh_dp = $boleh_dp ?? 0;
$dp_pending_list = $dp_pending_list ?? [];
$dp_pending_total = $dp_pending_total ?? 0;
?>

<!-- F2-A: Servis Mesin Besar / Part Inden — buka opsi DP (Q9) -->
<div class="ks-dp-toggle" style="background:#fff8e6;border:1px solid #f1d38a;border-radius:6px;padding:7px 10px;margin-bottom:8px;font-size:12px;">
    <label style="display:flex;align-items:center;gap:6px;margin:0;cursor:pointer;">
        <input type="checkbox" id="chk_boleh_dp" <?= $boleh_dp ? 'checked' : '' ?>
               onchange="toggleBolehDp(this.checked)">
        <span>Servis Mesin Besar / Part Inden (bisa DP)</span>
    </label>
    <?php if (!empty($no_service)): ?>
    <div id="dp-section" style="margin-top:6px; <?= $boleh_dp ? '' : 'display:none;' ?>">
        <?php if (!empty($dp_pending_list)): ?>
            <?php foreach ($dp_pending_list as $dp): ?>
            <div style="display:flex;justify-content:space-between;align-items:center;background:#fff;border-radius:4px;padding:4px 6px;margin-bottom:4px;">
                <span><?= htmlspecialchars($dp['no_dp']) ?> — Rp <?= number_format($dp['jumlah_dp'],0,',','.') ?></span>
                <button type="button" class="ks-btn-action danger" style="padding:2px 6px;font-size:10px;" onclick="batalkanDp('<?= htmlspecialchars($dp['no_dp']) ?>')">Batalkan</button>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <button type="button" class="ks-btn-action info" style="width:100%;margin-top:2px;" onclick="bukaCatatDp()">
            <i class="fa fa-hand-holding-usd"></i> Catat DP
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Ringkasan Biaya -->
<p class="ks-section-hdr"><i class="fa fa-receipt"></i> Ringkasan Biaya</p>
<div class="ks-ringkasan">
    <div class="ks-ring-row">
        <span class="r-label">Jasa Service</span>
        <span class="r-val">Rp <?= number_format($total_service,0,',','.') ?></span>
    </div>
    <div class="ks-ring-row">
        <span class="r-label">Suku Cadang</span>
        <span class="r-val">Rp <?= number_format($total_barang,0,',','.') ?></span>
    </div>
</div>
<div class="ks-ring-subtotal">
    <span>Subtotal</span>
    <span>Rp <?= number_format($tot,0,',','.') ?></span>
</div>

<!-- Diskon Member (readonly, otomatis) -->
<div class="ks-diskon-row">
    <span class="dr-label">Diskon Member</span>
    <input type="number" class="dr-input" id="txtdiskon_member_v2" name="txtdiskon_member"
           value="<?= $auto_discount_percent ?>" readonly style="background:#f8f9fb;color:#aaa;">
    <span class="dr-suffix">%</span>
    <span class="dr-val" style="color:#8a94a6;font-size:10px;">otomatis</span>
</div>

<!-- Diskon Tambahan -->
<div class="ks-diskon-row">
    <span class="dr-label">Diskon Tambahan</span>
    <input type="number" class="dr-input" id="txtpotfaktur_persen_v2" name="txtpotfaktur_persen"
           value="0" min="0" max="100" step="0.01" onchange="hitungTotalV2()">
    <span class="dr-suffix">%</span>
    <span class="dr-val" id="ks-diskon-nom">-Rp 0</span>
</div>

<!-- PPN -->
<div class="ks-ppn-row">
    <span class="pr-label">PPN</span>
    <input type="number" class="pr-input" id="txtpajak_persen_v2" name="txtpajak_persen"
           value="0" min="0" max="100" step="0.01" onchange="hitungTotalV2()">
    <span class="pr-suffix">%</span>
    <span class="pr-val" id="ks-ppn-nom">+Rp 0</span>
</div>

<!-- Hidden fields untuk handler PHP (nama identik dengan tab-actions) -->
<input type="hidden" id="txttotal_jasa_v2"    name="txttotal_jasa"    value="<?= number_format($total_service,0,',','.') ?>">
<input type="hidden" id="txttotal_barang_v2"  name="txttotal_barang"  value="<?= number_format($total_barang,0,',','.') ?>">
<input type="hidden" id="txttotal_v2"         name="txttotal"         value="<?= number_format($tot,0,',','.') ?>">
<input type="hidden" id="txtpotfaktur_nom_v2" name="txtpotfaktur_nom" value="<?= number_format($discount_amount,0,',','.') ?>">
<input type="hidden" id="txtpajak_nom_v2"     name="txtpajak_nom"     value="0">
<input type="hidden" id="txtnet_v2"           name="txtnet"           value="<?= number_format($net,0,',','.') ?>">
<input type="hidden" id="dp_pending_total_v2" value="<?= number_format($dp_pending_total,0,',','.') ?>">

<?php if ($dp_pending_total > 0): ?>
<div class="ks-diskon-row" style="color:#2980b9;">
    <span class="dr-label">Sudah DP</span>
    <span class="dr-val">-Rp <?= number_format($dp_pending_total,0,',','.') ?></span>
</div>
<?php endif; ?>

<!-- Total Bayar Box -->
<div class="ks-total-bayar-box">
    <span class="ks-total-bayar-label">Total Bayar<?= $dp_pending_total > 0 ? ' (setelah DP)' : '' ?></span>
    <span class="ks-total-bayar-val" id="ks-total-bayar-display">
        Rp <?= number_format(max(0, $net - $dp_pending_total),0,',','.') ?>
    </span>
</div>

<!-- Multi-Payment (F1-C) -->
<p class="ks-section-hdr" style="margin-top:4px;"><i class="fa fa-wallet"></i> Pembayaran</p>
<input type="hidden" id="metode_pembayaran_v2" name="metode_pembayaran" value="Tunai">

<!-- Tunai -->
<div class="ks-pay-row" style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
    <span class="ks-pay-label" style="width:70px;font-size:11px;font-weight:600;color:#27ae60;"><i class="fa fa-money-bill-wave"></i> Tunai</span>
    <div class="ks-inp-wrap" style="flex:1;display:flex;align-items:center;border:1px solid #ddd;border-radius:5px;overflow:hidden;">
        <span class="rp-pre" style="padding:4px 6px;background:#f5f5f5;font-size:10px;color:#666;">Rp</span>
        <input type="text" id="bayar_tunai_v2" name="bayar_tunai" value="0"
               oninput="hitungMultiPayV2()" placeholder="0"
               style="flex:1;border:none;padding:5px 6px;font-size:12px;text-align:right;outline:none;">
    </div>
</div>

<!-- Transfer -->
<div class="ks-pay-row" style="display:flex;align-items:center;gap:6px;margin-bottom:3px;">
    <span class="ks-pay-label" style="width:70px;font-size:11px;font-weight:600;color:#2980b9;"><i class="fa fa-university"></i> Transfer</span>
    <div class="ks-inp-wrap" style="flex:1;display:flex;align-items:center;border:1px solid #ddd;border-radius:5px;overflow:hidden;">
        <span class="rp-pre" style="padding:4px 6px;background:#f5f5f5;font-size:10px;color:#666;">Rp</span>
        <input type="text" id="bayar_transfer_v2" name="bayar_transfer" value="0"
               oninput="hitungMultiPayV2()" placeholder="0"
               style="flex:1;border:none;padding:5px 6px;font-size:12px;text-align:right;outline:none;">
    </div>
</div>
<div id="ref_transfer_group" style="display:none;padding:0 0 5px 76px;">
    <input type="text" id="ref_transfer_v2" name="ref_transfer" placeholder="No. referensi transfer (opsional)"
           style="width:100%;font-size:11px;padding:3px 6px;border:1px solid #ddd;border-radius:4px;">
</div>

<!-- QRIS -->
<div class="ks-pay-row" style="display:flex;align-items:center;gap:6px;margin-bottom:5px;">
    <span class="ks-pay-label" style="width:70px;font-size:11px;font-weight:600;color:#8e44ad;"><i class="fa fa-qrcode"></i> QRIS</span>
    <div class="ks-inp-wrap" style="flex:1;display:flex;align-items:center;border:1px solid #ddd;border-radius:5px;overflow:hidden;">
        <span class="rp-pre" style="padding:4px 6px;background:#f5f5f5;font-size:10px;color:#666;">Rp</span>
        <input type="text" id="bayar_qris_v2" name="bayar_qris" value="0"
               oninput="hitungMultiPayV2()" placeholder="0"
               style="flex:1;border:none;padding:5px 6px;font-size:12px;text-align:right;outline:none;">
    </div>
</div>

<!-- Bukti upload (opsional, untuk transfer/QRIS) -->
<div id="bukti_pembayaran_group_v2" style="display:none;">
    <div class="ks-bukti-group">
        <label><i class="fa fa-paperclip"></i> Bukti Pembayaran (opsional)</label>
        <input type="file" name="bukti_pembayaran" accept="image/*,.pdf"
               style="width:100%;font-size:11px;margin-top:4px;">
        <div style="font-size:10px;color:#92400e;margin-top:3px;">JPG/PNG/PDF — maks. 2MB</div>
    </div>
</div>

<!-- Ringkasan sisa -->
<div style="background:#f0f9f4;border:1px solid #c3e6cb;border-radius:6px;padding:7px 10px;margin:5px 0;font-size:12px;">
    <div style="display:flex;justify-content:space-between;">
        <span>Total Terbayar</span>
        <span id="ks-terbayar-display" style="font-weight:600;color:#27ae60;">Rp 0</span>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:3px;">
        <span id="ks-sisa-label">Sisa</span>
        <span id="ks-sisa-display" style="font-weight:600;color:#e74c3c;">Rp <?= number_format($net,0,',','.') ?></span>
    </div>
</div>

<!-- Hidden fields untuk backward compat payment handler -->
<input type="hidden" id="txtbayar_v2"     name="txtbayar"     value="0">
<input type="hidden" id="txtkembalian_v2" name="txtkembalian" value="0">

<!-- Tombol PROSES BAYAR -->
<button type="submit" name="btnbayar" class="ks-btn-bayar"
        onclick="return validateMechanicPersen(event)" style="margin-top:4px;">
    <i class="fa fa-check-circle"></i> PROSES BAYAR
</button>

<!-- Tombol Simpan Draft -->
<button type="submit" name="btnsimpan" class="ks-btn-simpan"
        onclick="return validateMechanicPersen(event)">
    <i class="fa fa-save"></i> Simpan Draft
</button>

<!-- Tombol Sekunder -->
<div class="ks-right-secondary-btns">
    <button type="button" class="ks-btn-action danger" onclick="cancelServiceV2()">
        <i class="fa fa-times"></i> Cancel
    </button>
    <button type="button" class="ks-btn-action info" onclick="printEstimasiV2()">
        <i class="fa fa-print"></i> Estimasi
    </button>
</div>

<script>
function _ksFormat(n) { return Math.round(n).toLocaleString('id-ID'); }
function _ksParse(s)  { if(!s) return 0; return parseFloat(s.toString().replace(/\./g,'').replace(/,/g,'').replace(/[^0-9]/g,''))||0; }

function hitungTotalV2() {
    var sub   = _ksParse($('#txttotal_v2').val())              || 0;
    var dPct  = parseFloat($('#txtpotfaktur_persen_v2').val()) || 0;
    var pPct  = parseFloat($('#txtpajak_persen_v2').val())     || 0;
    var dNom  = sub * dPct / 100;
    var after = sub - dNom;
    var pNom  = after * pPct / 100;
    var net   = after + pNom;

    $('#txtpotfaktur_nom_v2').val(_ksFormat(dNom));
    $('#txtpajak_nom_v2').val(_ksFormat(pNom));
    $('#txtnet_v2').val(_ksFormat(net));

    var dpPending = _ksParse($('#dp_pending_total_v2').val()) || 0;
    var netSetelahDp = Math.max(0, net - dpPending);

    $('#ks-diskon-nom').text('-Rp ' + _ksFormat(dNom));
    $('#ks-ppn-nom').text('+Rp ' + _ksFormat(pNom));
    $('#ks-total-bayar-display').text('Rp ' + _ksFormat(netSetelahDp));
    $('#ks-sisa-display').text('Rp ' + _ksFormat(netSetelahDp));

    var lv = document.getElementById('ks-live-total');
    if (lv) lv.textContent = 'Rp ' + _ksFormat(net);

    hitungMultiPayV2();
}

function hitungMultiPayV2() {
    var net      = _ksParse($('#txtnet_v2').val()) || 0;
    var dpPending = _ksParse($('#dp_pending_total_v2').val()) || 0;
    var netSetelahDp = Math.max(0, net - dpPending);
    var tunai    = _ksParse($('#bayar_tunai_v2').val())    || 0;
    var transfer = _ksParse($('#bayar_transfer_v2').val()) || 0;
    var qris     = _ksParse($('#bayar_qris_v2').val())     || 0;
    var total    = tunai + transfer + qris;
    var sisa     = netSetelahDp - total;
    var kembalian = Math.max(0, total - netSetelahDp);

    $('#ks-terbayar-display').text('Rp ' + _ksFormat(total));
    if (sisa > 0) {
        $('#ks-sisa-display').text('Rp ' + _ksFormat(sisa)).css('color','#e74c3c');
        $('#ks-sisa-label').text('Sisa');
    } else {
        $('#ks-sisa-display').text('Rp ' + _ksFormat(kembalian)).css('color','#2980b9');
        $('#ks-sisa-label').text('Kembalian');
    }

    // Derive metode
    var parts = [];
    if (tunai > 0)    parts.push('Tunai');
    if (transfer > 0) parts.push('Transfer Bank');
    if (qris > 0)     parts.push('QRIS');
    var metode = parts.length > 1 ? 'Mix' : (parts[0] || 'Tunai');
    $('#metode_pembayaran_v2').val(metode);

    // Hidden fields untuk handler lama
    $('#txtbayar_v2').val(total);
    $('#txtkembalian_v2').val(kembalian);

    // Tampilkan bukti & ref transfer
    var adaNonTunai = (transfer > 0 || qris > 0);
    $('#bukti_pembayaran_group_v2').toggle(adaNonTunai);
    $('#ref_transfer_group').toggle(transfer > 0);
}

function cancelServiceV2() {
    var m = $('#modalCancelService');
    if (m.length) { m.modal('show'); }
    else if (confirm('Yakin ingin membatalkan service ini?')) { alert('Modal cancel tidak tersedia.'); }
}

function printEstimasiV2() {
    window.open('servis-estimasi-pdf.php?no_service=<?= addslashes($no_service ?? '') ?>','_blank');
}

// F2-A: Toggle servis mesin besar / part inden (Q9)
function toggleBolehDp(checked) {
    $.post('_ajax/ajax-toggle-boleh-dp.php', {
        no_service: '<?= addslashes($no_service ?? '') ?>',
        boleh_dp: checked ? 1 : 0
    }, function(res) {
        if (res.success) {
            $('#dp-section').toggle(!!checked);
        } else {
            alert(res.message || 'Gagal update penanda servis besar');
        }
    }, 'json');
}

function bukaCatatDp() {
    var nominal = prompt('Nominal DP (minimal 50% dari total berjalan):');
    if (nominal === null) return;
    var jumlah = parseFloat(nominal.toString().replace(/\./g,'').replace(/,/g,'').replace(/[^0-9]/g,'')) || 0;
    if (jumlah <= 0) { alert('Nominal DP tidak valid'); return; }
    $.post('_ajax/ajax-catat-dp.php', {
        no_service: '<?= addslashes($no_service ?? '') ?>',
        jumlah_dp: jumlah
    }, function(res) {
        if (res.success) {
            alert('DP tercatat: ' + res.no_dp + ' — Rp ' + _ksFormat(res.jumlah_dp));
            window.location.reload();
        } else {
            alert(res.message || 'Gagal catat DP');
        }
    }, 'json');
}

function batalkanDp(noDp) {
    if (!confirm('Batalkan DP ' + noDp + '? DP dikembalikan penuh ke customer.')) return;
    $.post('_ajax/ajax-batal-dp.php', { no_dp: noDp }, function(res) {
        if (res.success) {
            alert(res.message);
            window.location.reload();
        } else {
            alert(res.message || 'Gagal batalkan DP');
        }
    }, 'json');
}

$(document).ready(function() {
    hitungTotalV2();
});
</script>
