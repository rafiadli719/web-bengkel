<?php
// UNIQUE (noretur) di tblretur_servis_header. Tabel ini dibuat tanpa PK/indeks (2026-07-05_f2b_retur_servis.sql),
// dan nomor lama dibuat dari COUNT(*) sehingga bisa dobel setelah pembatalan (diperbaiki di function_retur.php, MAX).
// UNIQUE hanya pada noretur (bukan noretur+kd_cabang): nomor global, dan detail/approve/stok mencari lewat noretur tanpa cabang.
// Gagal aman bila masih ada nomor dobel. Reversible, idempotent.
// Pakai: php 2026-11-17_run_unique_retur_servis.php [dry|up|down]
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? '';
if (!in_array($arah, ['dry', 'up', 'down'], true)) { echo "Argumen: dry|up|down\n"; exit(1); }
function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function index_ada($t, $i) { return mysqli_num_rows(qq("SHOW INDEX FROM `$t` WHERE Key_name = '$i'")) > 0; }

$t = 'tblretur_servis_header';
$idx = 'uk_rs_h_noretur';
if (mysqli_num_rows(qq("SHOW TABLES LIKE '$t'")) === 0) { echo "Tabel $t tidak ada, lewati\n"; exit(0); }

if ($arah === 'down') {
    if (!index_ada($t, $idx)) { echo "$t: $idx tidak ada\n"; }
    else { qq("ALTER TABLE `$t` DROP INDEX `$idx`"); echo "$t: DROP $idx\n"; }
    echo "DOWN selesai\n"; exit(0);
}

$dob = qq("SELECT noretur, COUNT(*) n, GROUP_CONCAT(DISTINCT kd_cabang) cab FROM `$t` GROUP BY noretur HAVING COUNT(*) > 1");
$jumlah = mysqli_num_rows($dob);
if ($jumlah > 0) {
    echo "ABORT: $jumlah nomor dobel, perbaiki dulu:\n";
    while ($x = mysqli_fetch_assoc($dob)) { echo "  {$x['noretur']} x{$x['n']} cabang {$x['cab']}\n"; }
    exit(1);
}
if (index_ada($t, $idx)) { echo "$t: sudah ada $idx\n"; echo strtoupper($arah) . " selesai\n"; exit(0); }
if ($arah === 'dry') { echo "$t: akan menambah UNIQUE $idx (noretur); nomor dobel 0\n"; echo "DRY selesai\n"; exit(0); }
qq("ALTER TABLE `$t` ADD UNIQUE KEY `$idx` (`noretur`)");
echo "$t: UNIQUE $idx (noretur)\n";
echo "UP selesai\n";
