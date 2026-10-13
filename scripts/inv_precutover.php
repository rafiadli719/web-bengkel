<?php
// Pemeriksa kesiapan cutover Moving Average (baca saja, tidak mengubah data).
// Pakai: php scripts/inv_precutover.php [KODE_CABANG]
// Keluaran: daftar OK / TUNGGU / GAGAL. Exit 1 bila ada GAGAL.
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../app/lib/InventoryService.php';
$cab = $argv[1] ?? null;
$gagal = 0;
function cek($status, $teks) { global $gagal; if ($status === 'GAGAL') $gagal++; echo str_pad("[$status]", 9) . $teks . "\n"; }
function q($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); return $r ? mysqli_fetch_row($r) : null; }

// 1. Migrasi
foreach (['inv_location','inv_balance','inv_ledger','inv_config','inv_period','inv_correction_request','inv_journal','inv_journal_line','inv_audit_log','inv_avg_history','inv_claim_receivable','inv_ppv_log','inv_transit'] as $t) {
    $ada = q("SHOW TABLES LIKE '$t'");
    cek($ada ? 'OK' : 'GAGAL', "tabel $t" . ($ada ? '' : ' belum ada: jalankan migrasi inv_core/inv_koreksi'));
}
$kol = q("SHOW COLUMNS FROM tblservis_barang LIKE 'harga_pokok'");
cek($kol ? 'OK' : 'GAGAL', 'kolom tblservis_barang.harga_pokok' . ($kol ? '' : ' belum ada: jalankan migrasi inv_hpp_snapshot'));
$perm = q("SELECT COUNT(*) FROM tb_master_posisi WHERE permissions LIKE '%inv_admin%'");
cek(($perm && $perm[0] > 0) ? 'OK' : 'GAGAL', 'permission inv_* terpasang di posisi' . (($perm && $perm[0] > 0) ? '' : ': jalankan migrasi inv_permissions'));

$kj = q("SHOW COLUMNS FROM tblservis_jasa LIKE 'keterangan'");
cek($kj ? 'OK' : 'GAGAL', 'kolom tblservis_jasa.keterangan' . ($kj ? '' : ' belum ada: jalankan 2026-09-30_run_tblservis_jasa_keterangan.php'));

// 1b. Integritas saldo = jumlah ledger
$svc = new InventoryService($koneksi);
$sc = $svc->selfCheck();
cek(count($sc['selisih']) === 0 ? 'OK' : 'GAGAL', "integritas saldo vs ledger: {$sc['item_dicek']} item dicek, " . count($sc['selisih']) . ' selisih');

// 2. Mode per cabang
$mode = [];
$r = mysqli_query($koneksi, "SELECT kd_cabang, config_value FROM inv_config WHERE config_key='inv_mode'");
while ($r && $x = mysqli_fetch_row($r)) $mode[$x[0]] = $x[1];
foreach ($mode as $k => $v) {
    if ($cab && $k !== $cab) continue;
    $valid = in_array($v, ['off', 'shadow', 'live'], true);
    cek($valid ? 'OK' : 'GAGAL', "mode $k = $v" . ($valid ? '' : ' (nilai tidak dikenal)'));
}

// 3. Syarat per cabang
$daftar = $cab ? [$cab] : array_keys($mode);
foreach ($daftar as $k) {
    $m = $mode[$k] ?? 'off';
    $open = q("SELECT COUNT(*) FROM inv_ledger WHERE kd_cabang='" . mysqli_real_escape_string($koneksi, $k) . "' AND ref_type='OPENING'");
    $per = q("SELECT status FROM inv_period WHERE kd_cabang='" . mysqli_real_escape_string($koneksi, $k) . "' AND period='" . date('Y-m') . "'");
    if ($m === 'off') { cek('TUNGGU', "$k: masih off, aktifkan shadow di Pengaturan bila ini cabang pilot"); continue; }
    cek(($open && $open[0] > 0) ? 'OK' : 'TUNGGU', "$k: opening stock " . (($open && $open[0] > 0) ? "termuat ({$open[0]} baris)" : 'belum dimuat (butuh opname fisik, D-03)'));
    cek(($per && $per[0] === 'open') ? 'OK' : 'TUNGGU', "$k: periode " . date('Y-m') . ' = ' . ($per[0] ?? 'belum diset'));
    if ($m === 'live' && !($open && $open[0] > 0)) cek('GAGAL', "$k: live tanpa opening stock, semua keluar akan ditolak");
    if ($m === 'live' && $per && $per[0] !== 'open') cek('GAGAL', "$k: live tetapi periode " . date('Y-m') . ' tidak open, semua transaksi ditolak');
    $kdE = mysqli_real_escape_string($koneksi, $k);
    $neg = q("SELECT COUNT(*) FROM inv_balance WHERE kd_cabang='$kdE' AND qty_on_hand < 0");
    $nNeg = $neg ? (int)$neg[0] : 0;
    cek($nNeg === 0 ? 'OK' : ($m === 'live' ? 'GAGAL' : 'TUNGGU'), "$k: saldo negatif di inv_balance: $nNeg item");
    $rc = $svc->reconcile($k);
    $bad = (int)$rc['item_selisih'] + (int)$rc['shadow_gagal'];
    cek($bad === 0 ? 'OK' : ($m === 'live' ? 'GAGAL' : 'TUNGGU'), "$k: rekonsiliasi shadow vs tbstok: selisih {$rc['item_selisih']}, shadow gagal {$rc['shadow_gagal']}");
}

// 4. COA
$akun = q("SELECT COUNT(*) FROM inv_config WHERE config_key LIKE 'acct_%' AND config_value<>''");
cek(($akun && $akun[0] > 0) ? 'OK' : 'TUNGGU', 'kode akun COA (D-07) ' . (($akun && $akun[0] > 0) ? "terisi ({$akun[0]})" : 'belum diisi: jurnal belum aktif'));

// 5. Lokasi 001
$l1 = q("SELECT COUNT(*) FROM tbstok WHERE kd_cabang='001'");
cek(($l1 && $l1[0] > 0) ? 'TUNGGU' : 'OK', "kode lokasi 001 di tbstok: " . ($l1 ? $l1[0] : 0) . ' baris' . (($l1 && $l1[0] > 0) ? ' (butuh keputusan arti kode)' : ''));

echo $gagal ? "\nADA GAGAL: perbaiki sebelum lanjut.\n" : "\nTidak ada GAGAL. Item TUNGGU = langkah pemilik / waktu.\n";
exit($gagal ? 1 : 0);
