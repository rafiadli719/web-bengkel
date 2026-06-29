<?php
/**
 * Panel Kanan Kasir — Ringkasan Biaya + Pembayaran + Tombol Aksi
 * Included by: servis-input-reguler.php, servis-input-reguler-jemput.php, servis-input-garansi.php
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
?>

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

<!-- Total Bayar Box -->
<div class="ks-total-bayar-box">
    <span class="ks-total-bayar-label">Total Bayar</span>
    <span class="ks-total-bayar-val" id="ks-total-bayar-display">
        Rp <?= number_format($net,0,',','.') ?>
    </span>
</div>

<!-- Metode Pembayaran -->
<p class="ks-section-hdr" style="margin-top:4px;"><i class="fa fa-wallet"></i> Pembayaran</p>
<select class="ks-metode-select" id="metode_pembayaran_v2" name="metode_pembayaran"
        onchange="toggleBuktiV2()">
    <option value="Tunai"         <?= $metode_pembayaran=='Tunai'?'selected':''         ?>>💵 Tunai</option>
    <option value="Transfer Bank" <?= $metode_pembayaran=='Transfer Bank'?'selected':'' ?>>🏦 Transfer Bank</option>
    <option value="QRIS"          <?= $metode_pembayaran=='QRIS'?'selected':''          ?>>📷 QRIS</option>
    <option value="E-Wallet"      <?= $metode_pembayaran=='E-Wallet'?'selected':''      ?>>📱 E-Wallet</option>
    <option value="Kartu Kredit"  <?= $metode_pembayaran=='Kartu Kredit'?'selected':''  ?>>💳 Kartu Kredit</option>
    <option value="Kartu Debit"   <?= $metode_pembayaran=='Kartu Debit'?'selected':''   ?>>💳 Kartu Debit</option>
</select>

<!-- Bukti upload (tampil jika non-tunai) -->
<div id="bukti_pembayaran_group_v2" style="display:none;">
    <div class="ks-bukti-group">
        <label><i class="fa fa-paperclip"></i> Bukti Pembayaran</label>
        <input type="file" name="bukti_pembayaran" accept="image/*,.pdf"
               style="width:100%;font-size:11px;margin-top:4px;">
        <div style="font-size:10px;color:#92400e;margin-top:3px;">JPG/PNG/PDF — maks. 2MB</div>
    </div>
</div>

<!-- Jumlah Bayar + Kembalian -->
<div class="ks-tunai-row">
    <div class="ks-inp-group">
        <label>Jumlah Bayar</label>
        <div class="ks-inp-wrap">
            <span class="rp-pre">Rp</span>
            <input type="text" id="txtbayar_v2" name="txtbayar"
                   value="<?= number_format($bayar,0,',','.') ?>"
                   onkeyup="hitungKembalianV2()">
        </div>
    </div>
    <div class="ks-inp-group">
        <label>Kembalian</label>
        <div class="ks-inp-wrap kembalian">
            <span class="rp-pre">Rp</span>
            <input type="text" id="txtkembalian_v2" name="txtkembalian"
                   value="<?= number_format($kembalian,0,',','.') ?>" readonly>
        </div>
    </div>
</div>

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
function toggleBuktiV2() {
    var m = ($('#metode_pembayaran_v2').val()||'');
    $('#bukti_pembayaran_group_v2').toggle(m !== 'Tunai');
}

function _ksFormat(n) { return Math.round(n).toLocaleString('id-ID'); }
function _ksParse(s)  { if(!s) return 0; return parseInt(s.toString().replace(/\./g,'').replace(/,/g,''))||0; }

function hitungTotalV2() {
    var sub   = _ksParse($('#txttotal_v2').val())   || 0;
    var dPct  = parseFloat($('#txtpotfaktur_persen_v2').val()) || 0;
    var pPct  = parseFloat($('#txtpajak_persen_v2').val())     || 0;
    var dNom  = sub * dPct / 100;
    var after = sub - dNom;
    var pNom  = after * pPct / 100;
    var net   = after + pNom;

    $('#txtpotfaktur_nom_v2').val(_ksFormat(dNom));
    $('#txtpajak_nom_v2').val(_ksFormat(pNom));
    $('#txtnet_v2').val(_ksFormat(net));

    $('#ks-diskon-nom').text('-Rp ' + _ksFormat(dNom));
    $('#ks-ppn-nom').text('+Rp ' + _ksFormat(pNom));
    $('#ks-total-bayar-display').text('Rp ' + _ksFormat(net));

    var lv = document.getElementById('ks-live-total');
    if (lv) lv.textContent = 'Rp ' + _ksFormat(net);
}

function hitungKembalianV2() {
    var net  = _ksParse($('#txtnet_v2').val())   || 0;
    var bayar = _ksParse($('#txtbayar_v2').val()) || 0;
    $('#txtkembalian_v2').val(_ksFormat(Math.max(0, bayar - net)));
}

function cancelServiceV2() {
    var m = $('#modalCancelService');
    if (m.length) { m.modal('show'); }
    else if (confirm('Yakin ingin membatalkan service ini?')) { alert('Modal cancel tidak tersedia.'); }
}

function printEstimasiV2() {
    window.open('servis-estimasi-pdf.php?no=<?= addslashes($no_service ?? '') ?>','_blank');
}

$(document).ready(function() {
    toggleBuktiV2();
    hitungTotalV2();
});
</script>
