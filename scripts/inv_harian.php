<?php
/**
 * Pekerjaan harian Moving Average (F1-09, F5-05). Jalankan dari CLI atau cron:
 *   php scripts/inv_harian.php            (laporan ke layar, kode keluar 1 bila ada masalah)
 *   php scripts/inv_harian.php --jurnal   (sekalian jurnalkan baris tertunda bila COA sudah diisi)
 *
 * Isi: (1) integritas saldo = jumlah ledger, (2) rekonsiliasi shadow vs tbstok per cabang bukan-off,
 * (3) rekonsiliasi jurnal vs saldo (bila COA terisi), (4) barang dalam perjalanan melewati batas hari.
 * Hanya membaca, kecuali --jurnal (menulis inv_journal) dan satu baris ringkasan di inv_audit_log.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Hanya CLI.\n");
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../app/lib/InventoryCorrection.php';
require_once __DIR__ . '/../app/lib/InventoryJournal.php';
if (!$koneksi) { echo "Koneksi gagal\n"; exit(2); }

$svc = new InventoryService($koneksi);
$kor = new InventoryCorrection($svc, $koneksi);
$jur = new InventoryJournal($svc, $koneksi);
$masalah = 0;

echo "== Moving Average harian " . date('Y-m-d H:i:s') . "\n";

if (in_array('--jurnal', $argv, true)) {
    $h = $jur->postPending();
    echo "Jurnal: {$h['jurnal']} dibuat, {$h['dilewati']} dilewati (COA belum lengkap atau jenis tanpa jurnal)\n";
}

$c = $svc->selfCheck();
echo "Integritas: {$c['item_dicek']} saldo dicek, " . count($c['selisih']) . " selisih\n";
foreach (array_slice($c['selisih'], 0, 20) as $s) {
    echo "  ! {$s['lokasi']} {$s['item']} qty {$s['selisih_qty']} nilai {$s['selisih_nilai']}\n";
}
$masalah += count($c['selisih']);

$cab = mysqli_query($koneksi, "SELECT kode_cabang FROM tbcabang ORDER BY kode_cabang");
while ($x = mysqli_fetch_assoc($cab)) {
    $kd = $x['kode_cabang'];
    $mode = $svc->getMode($kd);
    if ($mode === InventoryService::MODE_OFF) {
        continue;
    }
    $r = $svc->reconcile($kd);
    echo "Shadow $kd ($mode): item lama {$r['item_tbstok']}, item baru {$r['item_inv']}, selisih {$r['item_selisih']}, shadow gagal {$r['shadow_gagal']}\n";
    foreach (array_slice($r['selisih'], 0, 10) as $s) {
        echo "  ~ {$s['no_item']} tbstok {$s['tbstok']} inv {$s['inv']}\n";
    }
    // selisih shadow dilaporkan tetapi tidak dihitung masalah: wajar sebelum opening stock dimuat
}

$rk = $jur->rekonJurnal();
if (array_key_exists('catatan', $rk)) {
    echo "Jurnal: {$rk['catatan']}\n";
} else {
    echo "Jurnal vs saldo: jurnal {$rk['jurnal']}, saldo {$rk['balance']}, belum dijurnal {$rk['belum_dijurnal']} => " . ($rk['sama'] ? 'SAMA' : 'BEDA') . "\n";
    if (!$rk['sama']) { $masalah++; }
}

$aging = $kor->agingTransit();
echo "Transit lewat batas: " . count($aging) . "\n";
foreach (array_slice($aging, 0, 10) as $t) {
    echo "  ! {$t['no_transfer']} {$t['no_item']} {$t['kd_asal']}->{$t['kd_tujuan']} {$t['umur_hari']} hari\n";
}
$masalah += count($aging);

$svc->audit('cron', 'recon_harian', date('Y-m-d'), $masalah === 0 ? 'sehat' : "masalah=$masalah", null,
    ['integritas_selisih' => count($c['selisih']), 'transit_lewat' => count($aging)]);
echo $masalah === 0 ? "HASIL: sehat\n" : "HASIL: $masalah masalah perlu ditinjau\n";
exit($masalah === 0 ? 0 : 1);
