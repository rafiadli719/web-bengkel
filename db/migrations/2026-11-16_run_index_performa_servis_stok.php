<?php
// Indeks performa pasca reload center: join detail servis memakai (no_service, kd_cabang),
// kartu stok/filter tahun memakai tbstok.tanggal. Hanya menambah indeks (aman, reversible).
// Pakai: php 2026-11-16_run_index_performa_servis_stok.php [up|down]
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? '';
if (!in_array($arah, ['up', 'down'], true)) { echo "Argumen: up|down\n"; exit(1); }
function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function index_ada($t, $i) { return mysqli_num_rows(qq("SHOW INDEX FROM `$t` WHERE Key_name = '$i'")) > 0; }
$daftar = [
    ['tblservis_barang', 'idx_nosvc_cab', '`no_service`, `kd_cabang`'],
    ['tblservis_jasa', 'idx_nosvc_cab', '`no_service`, `kd_cabang`'],
    ['tbstok', 'idx_tanggal', '`tanggal`'],
];
foreach ($daftar as [$t, $idx, $kol]) {
    if ($arah === 'up') {
        if (index_ada($t, $idx)) { echo "$t: sudah ada $idx\n"; continue; }
        qq("ALTER TABLE `$t` ADD INDEX `$idx` ($kol)");
        echo "$t: + $idx ($kol)\n";
    } else {
        if (!index_ada($t, $idx)) { continue; }
        qq("ALTER TABLE `$t` DROP INDEX `$idx`");
        echo "$t: - $idx\n";
    }
}
echo strtoupper($arah) . " selesai\n";
