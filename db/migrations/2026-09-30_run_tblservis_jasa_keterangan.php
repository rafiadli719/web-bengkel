<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
echo "Koneksi berhasil!\n";

// Idempotent: MySQL gak punya ADD COLUMN IF NOT EXISTS.
$ada = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'keterangan'");
if ($ada && mysqli_num_rows($ada) > 0) { echo "Kolom keterangan sudah ada, lewati.\n"; exit(0); }

$sql = file_get_contents(__DIR__ . '/2026-09-30_tblservis_jasa_keterangan.sql');
if (!mysqli_query($koneksi, trim(preg_replace('/^--.*$/m', '', $sql)))) {
    echo "Error: " . mysqli_error($koneksi) . "\n"; exit(1);
}
echo "Migration executed successfully!\n";
