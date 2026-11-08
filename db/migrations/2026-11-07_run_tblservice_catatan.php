<?php
// Tambah tblservice.catatan (dipakai _handler_status_keluhan_wo.php, servis-input-reguler-jemput.php).
// Sebelumnya kolom tidak ada: catatan "keluhan tidak selesai" hilang diam-diam (mysqli_report OFF).
// Pakai: php 2026-11-07_run_tblservice_catatan.php [up|down]   (default up, idempotent)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? 'up';
$ada = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservice LIKE 'catatan'");
$sudah = $ada && mysqli_num_rows($ada) > 0;

if ($arah === 'down') {
    if (!$sudah) { echo "Kolom catatan tidak ada, lewati.\n"; exit(0); }
    $sql = "ALTER TABLE tblservice DROP COLUMN catatan";
} else {
    if ($sudah) { echo "Kolom catatan sudah ada, lewati.\n"; exit(0); }
    $sql = "ALTER TABLE tblservice ADD COLUMN catatan TEXT NULL DEFAULT NULL";
}
if (!mysqli_query($koneksi, $sql)) { echo "Error: " . mysqli_error($koneksi) . "\n"; exit(1); }
echo "Migration $arah executed successfully!\n";
