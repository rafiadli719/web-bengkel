<?php
/**
 * Tes InventoryService (Moving Average). Jalankan:
 *   php.exe -d extension=php_mysqli.dll -f tests/inventory/run_inventory_tests.php
 * Memakai item ZZTEST-* di DB lokal dan membersihkannya sendiri. Jangan jalankan di produksi.
 * Mode worker (internal, konkurensi): ... -f run_inventory_tests.php worker <item> <lokasi> <n>
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../app/lib/InventoryService.php';

if (!$koneksi) { echo "Koneksi gagal\n"; exit(1); }

$svc = new InventoryService($koneksi);

if (($argv[1] ?? '') === 'worker') {
    // satu proses mencoba menjual 1 unit; cetak OK / GAGAL
    try {
        $svc->postOut($argv[3], $argv[2], 1, 'ZZTEST-CC', 'CC-' . getmypid() . '-' . $argv[4], ['user' => 'tes']);
        echo "OK\n";
    } catch (InventoryException $e) {
        echo "GAGAL:" . $e->kode . "\n";
    }
    exit(0);
}

$lulus = 0; $gagal = 0;
function cek($nama, $kondisi, $info = '') {
    global $lulus, $gagal;
    if ($kondisi) { $lulus++; echo "  [OK]   $nama\n"; }
    else { $gagal++; echo "  [GAGAL] $nama $info\n"; }
}
function bal($k, $lok, $item) {
    $r = mysqli_query($k, "SELECT * FROM inv_balance WHERE kd_cabang='" . mysqli_real_escape_string($k, $lok) . "' AND no_item='" . mysqli_real_escape_string($k, $item) . "'");
    return $r ? mysqli_fetch_assoc($r) : null;
}
function hitung($k, $sql) { $r = mysqli_query($k, $sql); return (int) mysqli_fetch_row($r)[0]; }
function bersih($k) {
    mysqli_query($k, "DELETE FROM inv_ledger WHERE no_item LIKE 'ZZTEST-%'");
    mysqli_query($k, "DELETE FROM inv_avg_history WHERE no_item LIKE 'ZZTEST-%'");
    mysqli_query($k, "DELETE FROM inv_balance WHERE no_item LIKE 'ZZTEST-%'");
    mysqli_query($k, "DELETE FROM tbstok WHERE no_item LIKE 'ZZTEST-%'");
    mysqli_query($k, "DELETE FROM inv_audit_log WHERE ref LIKE 'ZZT-%' OR ref LIKE 'ZZTEST-%'");
}

bersih($koneksi);
$modeAwal = [];
foreach (['PST', 'PACUL'] as $l) $modeAwal[$l] = $svc->getMode($l);

echo "== Rumus average (PRD §6.1)\n";
$r = $svc->postIn('PST', 'ZZTEST-A', 10, '1000', 'TES', 'T1');
cek('Test1: stok 0 lalu masuk -> average = harga masuk', $r['avg_after'] === '1000.00' && $r['qty_after'] === '10.000');
$r = $svc->postIn('PST', 'ZZTEST-A', 10, '1200', 'TES', 'T2');
cek('Test2: masuk kedua -> average tertimbang 1100', $r['avg_after'] === '1100.00' && $r['value_after'] === '22000.00');
$r = $svc->postOut('PST', 'ZZTEST-A', 5, 'TES', 'T3');
cek('Test3: keluar -> HPP = qty x average (5500), average tetap 1100', $r['hpp_total'] === '5500.00' && $r['avg_after'] === '1100.00' && $r['qty_after'] === '15.000');
$r = $svc->postOut('PST', 'ZZTEST-A', 15, 'TES', 'T4');
cek('Test4: keluar semua -> nilai persediaan 0 tanpa residu', $r['value_after'] === '0.00' && $r['qty_after'] === '0.000' && $r['avg_after'] === '0.00');
$r = $svc->postIn('PST', 'ZZTEST-A', 4, '900', 'TES', 'T5');
cek('Test13: stok 0 lalu masuk lagi -> average = harga masuk baru (900)', $r['avg_after'] === '900.00');

echo "== Stok minus & validasi (BR-05, V-01)\n";
$sebelum = bal($koneksi, 'PST', 'ZZTEST-A');
$kode = ''; $tersedia = null;
try { $svc->postOut('PST', 'ZZTEST-A', 5, 'TES', 'T6'); } catch (InventoryException $e) { $kode = $e->kode; $tersedia = $e->tersedia; }
$sesudah = bal($koneksi, 'PST', 'ZZTEST-A');
cek('Test16: keluar melebihi saldo ditolak (stok_tidak_cukup)', $kode === 'stok_tidak_cukup' && $tersedia === '4.000');
cek('Test16b: saldo tidak berubah setelah ditolak', $sebelum['qty_on_hand'] === $sesudah['qty_on_hand'] && $sebelum['version'] === $sesudah['version']);
cek('Test16c: tidak ada baris ledger untuk transaksi ditolak', hitung($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE ref_no='T6'") === 0);
$kode = '';
try { $svc->postOut('PST', 'ZZTEST-NOITEM', 1, 'TES', 'T7'); } catch (InventoryException $e) { $kode = $e->kode; }
cek('Item belum punya saldo -> ditolak', $kode === 'stok_tidak_cukup');
foreach ([['qty 0', 0, 10], ['qty negatif', -1, 10], ['biaya negatif', 1, -5]] as [$n, $q, $c]) {
    $kode = '';
    try { $svc->postIn('PST', 'ZZTEST-B', $q, $c, 'TES', 'V-' . $n); } catch (InventoryException $e) { $kode = $e->kode; }
    cek("Validasi: $n ditolak", $kode !== '');
}
$kode = '';
try { $svc->postIn('XXX', 'ZZTEST-B', 1, 10, 'TES', 'V-lok'); } catch (InventoryException $e) { $kode = $e->kode; }
cek('Lokasi tidak dikenal ditolak', $kode === 'lokasi_tidak_dikenal');
$kode = '';
try { $svc->postIn('PST', '', 1, 10, 'TES', 'V-item'); } catch (InventoryException $e) { $kode = $e->kode; }
cek('Item kosong ditolak', $kode === 'param_kurang');
mysqli_report(MYSQLI_REPORT_OFF);
$dbBlok = !@mysqli_query($koneksi, "UPDATE inv_balance SET qty_on_hand = -1 WHERE no_item='ZZTEST-A'");
cek('Defense in depth: DB CHECK menolak saldo negatif langsung', $dbBlok);

echo "== Idempotency (V-07)\n";
$a = $svc->postIn('PST', 'ZZTEST-C', 10, '500', 'TES', 'IDEM');
$b = $svc->postIn('PST', 'ZZTEST-C', 10, '500', 'TES', 'IDEM');
cek('Panggilan ulang dengan referensi sama dikenali', $b['idempotent'] === true && $a['ledger_id'] === $b['ledger_id']);
cek('Saldo tidak digandakan', bal($koneksi, 'PST', 'ZZTEST-C')['qty_on_hand'] === '10.000');

echo "== Pembulatan half-up (D-08, Test15)\n";
$svc->postIn('PST', 'ZZTEST-D', 1, '0.01', 'TES', 'R1');
$r = $svc->postIn('PST', 'ZZTEST-D', 1, '0.02', 'TES', 'R2');
cek('0.03 / 2 = 0.015 dibulatkan ke 0.02', $r['avg_after'] === '0.02');
$svc->postIn('PST', 'ZZTEST-E', 20, '10000', 'TES', 'R3');
$r = $svc->postIn('PST', 'ZZTEST-E', 30, '11000', 'TES', 'R4');
cek('Campuran 20@10000 + 30@11000 -> 10600', $r['avg_after'] === '10600.00' && $r['value_after'] === '530000.00');
$r = $svc->postIn('PST', 'ZZTEST-F', 50, '10666.67', 'TES', 'R5');
cek('PRD §7: 50 x 10.666,67 -> total_value otoritatif 533333.50', $r['value_after'] === '533333.50' && $r['avg_after'] === '10666.67');
$svc->postOut('PST', 'ZZTEST-F', 49, 'TES', 'R6');
$r2 = $svc->postOut('PST', 'ZZTEST-F', 1, 'TES', 'R7');
cek('Sisa terakhir menghabiskan nilai (tanpa residu pembulatan)', $r2['value_after'] === '0.00');
$r = $svc->postIn('PST', 'ZZTEST-G', '1.5', '1000', 'TES', 'Q1');
cek('Qty pecahan 1.5 didukung (DECIMAL 14,3)', $r['qty_after'] === '1.500' && $r['value_after'] === '1500.00');

echo "== Per lokasi & alias (BR-10, D-01)\n";
$svc->postIn('PST', 'ZZTEST-H', 10, '1000', 'TES', 'L1');
$svc->postIn('PCL', 'ZZTEST-H', 10, '2000', 'TES', 'L2');
cek('Alias PCL dipetakan ke PACUL', bal($koneksi, 'PACUL', 'ZZTEST-H') !== null && bal($koneksi, 'PCL', 'ZZTEST-H') === null);
cek('Average terpisah per lokasi', bal($koneksi, 'PST', 'ZZTEST-H')['average_cost'] === '1000.00' && bal($koneksi, 'PACUL', 'ZZTEST-H')['average_cost'] === '2000.00');
cek('Keluar di satu lokasi tidak melihat stok lokasi lain', (function () use ($svc) {
    try { $svc->postOut('PACUL', 'ZZTEST-H', 11, 'TES', 'L3'); return false; } catch (InventoryException $e) { return $e->kode === 'stok_tidak_cukup'; }
})());

echo "== Average history & ledger (BR-11)\n";
cek('Average history tercatat saat average berubah', hitung($koneksi, "SELECT COUNT(*) FROM inv_avg_history WHERE no_item='ZZTEST-A'") >= 3);
cek('Setiap posting punya baris ledger', hitung($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE no_item='ZZTEST-A'") === 5);
$l = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT transaction_date FROM inv_ledger WHERE ref_no='T1'"));
cek('Tanggal transaksi = tanggal server (BR-06)', $l['transaction_date'] === date('Y-m-d'));

echo "== Proyeksi ke tbstok (F1-06)\n";
$svc->postIn('PST', 'ZZTEST-P', 7, '100', 'TES', 'P1', ['tbstok_tipe' => '2', 'keterangan' => 'tes proyeksi']);
$svc->postOut('PST', 'ZZTEST-P', 2, 'TES', 'P2', ['tbstok_tipe' => '3', 'keterangan' => 'tes proyeksi']);
$ts = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(masuk-keluar) s, COUNT(*) n FROM tbstok WHERE no_item='ZZTEST-P'"));
cek('tbstok = 2 baris, saldo 5 = inv_balance', (int) $ts['n'] === 2 && (int) $ts['s'] === 5 && bal($koneksi, 'PST', 'ZZTEST-P')['qty_on_hand'] === '5.000');
$kode = '';
try { $svc->postIn('PST', 'ZZTEST-P', '1.5', '100', 'TES', 'P3', ['tbstok_tipe' => '2']); } catch (InventoryException $e) { $kode = $e->kode; }
cek('Qty pecahan + proyeksi ditolak dan di-rollback penuh (V-04)', $kode === 'proyeksi_qty_pecahan'
    && hitung($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE ref_no='P3'") === 0
    && bal($koneksi, 'PST', 'ZZTEST-P')['qty_on_hand'] === '5.000');

echo "== Mode off / shadow / live (F1-08)\n";
$svc->setMode('PST', 'off', 'tes');
cek('Mode off: shadowIn tidak melakukan apa pun', $svc->shadowIn('PST', 'ZZTEST-S', 5, 100, 'TES', 'S1') === null && bal($koneksi, 'PST', 'ZZTEST-S') === null);
$svc->setMode('PST', 'shadow', 'tes');
$r = $svc->shadowIn('PST', 'ZZTEST-S', 5, 100, 'TES', 'S2');
cek('Mode shadow: posting masuk tercatat', $r !== null && bal($koneksi, 'PST', 'ZZTEST-S')['qty_on_hand'] === '5.000');
$r = $svc->shadowOut('PST', 'ZZTEST-S', 9, 'ZZT-SH', 'S3', ['user' => 'tes']);
cek('Shadow: keluar > saldo tidak melempar error (alur lama aman)', $r === null);
cek('Shadow: penolakan tercatat di inv_audit_log', hitung($koneksi, "SELECT COUNT(*) FROM inv_audit_log WHERE action='shadow_gagal' AND ref='ZZT-SH:S3'") === 1);
$svc->setMode('PST', 'live', 'tes');
$kode = '';
try { $svc->shadowOut('PST', 'ZZTEST-S', 9, 'TES', 'S4'); } catch (InventoryException $e) { $kode = $e->kode; }
cek('Mode live: penolakan stok dilempar ke pemanggil', $kode === 'stok_tidak_cukup');
$kode = '';
try { $svc->setMode('PST', 'ngawur', 'tes'); } catch (InventoryException $e) { $kode = $e->kode; }
cek('Mode tidak valid ditolak', $kode === 'mode_invalid');
cek('Lokasi tak dikenal dianggap off', $svc->getMode('XXX') === 'off');

echo "== Konkurensi (F1-10, Test 43-45)\n";
$svc->postIn('PST', 'ZZTEST-K', 5, '100', 'TES', 'K0');
$procs = [];
$cmd = '"' . PHP_BINARY . '" -d extension=php_mysqli.dll -d extension_dir="' . ini_get('extension_dir') . '" -f "' . __FILE__ . '" worker ZZTEST-K PST ';
for ($i = 0; $i < 12; $i++) {
    $p = proc_open($cmd . $i, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $procs[] = [$p, $pipes];
}
$ok = 0; $tolak = 0; $lain = 0;
foreach ($procs as [$p, $pipes]) {
    $out = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]); fclose($pipes[2]); proc_close($p);
    if ($out === 'OK') $ok++; elseif (strpos($out, 'GAGAL:stok_tidak_cukup') === 0) $tolak++; else $lain++;
}
$sisa = bal($koneksi, 'PST', 'ZZTEST-K');
cek("12 penjual paralel, stok 5: tepat 5 berhasil (ok=$ok, ditolak=$tolak, lain=$lain)", $ok === 5 && $tolak === 7 && $lain === 0);
cek('Saldo akhir 0, nilai 0, tidak pernah negatif', $sisa['qty_on_hand'] === '0.000' && $sisa['total_value'] === '0.00');
cek('Ledger konsisten: 5 baris OUT', hitung($koneksi, "SELECT COUNT(*) FROM inv_ledger WHERE no_item='ZZTEST-K' AND qty_out>0") === 5);

// bersihkan & kembalikan mode
bersih($koneksi);
foreach ($modeAwal as $l => $m) $svc->setMode($l, $m, 'tes-restore');
mysqli_query($koneksi, "DELETE FROM inv_audit_log WHERE actor IN ('tes','tes-restore')");

echo "\nHasil: $lulus lulus, $gagal gagal\n";
exit($gagal === 0 ? 0 : 1);
