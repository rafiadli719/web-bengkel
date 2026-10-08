<?php
// F1-01 Moving Average: tabel inti inv_*.
// Pakai: php 2026-10-08_run_inv_core.php [up|down] [force]
// up   : buat tabel (IF NOT EXISTS), isi inv_location dari alias lama, inv_mode default 'off' per cabang
// down : DROP tabel inv_* yang dibuat migrasi ini (hanya bila inv_ledger kosong, kecuali argumen 'force')
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';
$force = ($argv[2] ?? '') === 'force';

$tabel = ['inv_audit_log', 'inv_config', 'inv_avg_history', 'inv_ledger', 'inv_balance', 'inv_location'];

if ($mode === 'down') {
    try {
        $r = mysqli_query($koneksi, "SELECT COUNT(*) c FROM inv_ledger");
        $n = (int) mysqli_fetch_assoc($r)['c'];
    } catch (mysqli_sql_exception $e) { $n = 0; }
    if ($n > 0 && !$force) {
        echo "inv_ledger berisi $n baris. Down dibatalkan. Pakai argumen 'force' bila yakin.\n";
        exit(1);
    }
    foreach ($tabel as $t) mysqli_query($koneksi, "DROP TABLE IF EXISTS `$t`");
    echo "Down selesai.\n";
    exit(0);
}

$sql = file_get_contents(__DIR__ . '/2026-10-08_inv_core.sql');
mysqli_multi_query($koneksi, $sql);
do { if ($r = mysqli_store_result($koneksi)) mysqli_free_result($r); } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));

// Alias lokasi -> kode kanonik (D-01). 001 dan '' sengaja tidak dipetakan.
$alias = [
    ['CIKDITIRO', 'CIKDITIRO', 0], ['CIKDITIRO', 'CDT', 0],
    ['PACUL', 'PACUL', 0],         ['PACUL', 'PCL', 0],
    ['PESALAKAN', 'PESALAKAN', 0], ['PESALAKAN', 'PSL', 0],
    ['TRAYEMAN', 'TRAYEMAN', 0],   ['TRAYEMAN', 'TRM', 0],
    ['PST', 'PST', 1],
];
$st = mysqli_prepare($koneksi, "INSERT IGNORE INTO inv_location (kode, alias, is_pusat) VALUES (?,?,?)");
foreach ($alias as [$k, $a, $p]) { mysqli_stmt_bind_param($st, 'ssi', $k, $a, $p); mysqli_stmt_execute($st); }

// inv_mode default 'off' untuk setiap cabang di tbcabang
$cab = mysqli_query($koneksi, "SELECT kode_cabang FROM tbcabang");
$st2 = mysqli_prepare($koneksi, "INSERT IGNORE INTO inv_config (kd_cabang, config_key, config_value, updated_by) VALUES (?, 'inv_mode', 'off', 'migrasi')");
while ($c = mysqli_fetch_assoc($cab)) { mysqli_stmt_bind_param($st2, 's', $c['kode_cabang']); mysqli_stmt_execute($st2); }
mysqli_query($koneksi, "INSERT IGNORE INTO inv_config (kd_cabang, config_key, config_value, updated_by) VALUES ('*', 'transit_aging_hari', '3', 'migrasi')");

echo "Up selesai. Tabel inv_* siap, inv_mode=off untuk semua cabang.\n";
