<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
echo "Koneksi berhasil!\n";

// Idempotent: CREATE IF NOT EXISTS + INSERT IGNORE.
$sql = preg_replace('/^--.*$/m', '', file_get_contents(__DIR__ . '/2026-10-04_komplain_setting.sql'));
foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
    if (!mysqli_query($koneksi, $stmt)) { echo "Error: " . mysqli_error($koneksi) . "\n"; exit(1); }
}
echo "Migration executed successfully!\n";
