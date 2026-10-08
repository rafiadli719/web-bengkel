<?php
// Moving Average F2-04: kolom snapshot HPP di detail barang servis (tblpenjualan_detail.harga_pokok sudah ada).
// Pakai: php 2026-10-08_run_inv_hpp_snapshot.php [up|down] [force]
// up   : tambah tblservis_barang.harga_pokok DECIMAL(18,2) NOT NULL DEFAULT 0 (idempoten)
// down : hapus kolom itu, hanya bila semua nilainya 0 (atau argumen 'force')
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';
$force = ($argv[2] ?? '') === 'force';

$ada = mysqli_num_rows(mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_barang LIKE 'harga_pokok'")) > 0;

if ($mode === 'down') {
    if (!$ada) { echo "Kolom tidak ada. Tidak ada yang dilakukan.\n"; exit(0); }
    $terisi = (int) mysqli_fetch_row(mysqli_query($koneksi, "SELECT COUNT(*) FROM tblservis_barang WHERE harga_pokok <> 0"))[0];
    if ($terisi > 0 && !$force) { echo "Ada $terisi baris ber-HPP snapshot. Down dibatalkan. Pakai 'force' bila yakin.\n"; exit(1); }
    mysqli_query($koneksi, "ALTER TABLE tblservis_barang DROP COLUMN harga_pokok");
    echo "Down selesai.\n";
    exit(0);
}
if ($ada) { echo "Kolom sudah ada. Up tidak melakukan apa pun.\n"; exit(0); }
mysqli_query($koneksi, "ALTER TABLE tblservis_barang ADD COLUMN harga_pokok DECIMAL(18,2) NOT NULL DEFAULT 0");
echo "Up selesai. tblservis_barang.harga_pokok ditambahkan.\n";
