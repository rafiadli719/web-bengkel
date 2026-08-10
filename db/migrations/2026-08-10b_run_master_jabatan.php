<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/koneksi.php';

if (!$koneksi) {
    echo "Koneksi gagal: " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "Koneksi berhasil!\n";

$sqlFile = __DIR__ . '/2026-08-10b_master_jabatan_dan_rename_karyawan.sql';
if (!file_exists($sqlFile)) {
    echo "File SQL tidak ditemukan: $sqlFile\n";
    exit(1);
}

$sqlContent = file_get_contents($sqlFile);
if (mysqli_multi_query($koneksi, $sqlContent)) {
    do {
        if ($result = mysqli_store_result($koneksi)) {
            mysqli_free_result($result);
        }
        echo ".";
    } while (mysqli_next_result($koneksi));

    if (mysqli_errno($koneksi)) {
        echo "\nError during migration execution: " . mysqli_error($koneksi) . "\n";
        exit(1);
    }
    echo "\nMigration executed successfully!\n";
} else {
    echo "\nFailed to start multi-query: " . mysqli_error($koneksi) . "\n";
    exit(1);
}
