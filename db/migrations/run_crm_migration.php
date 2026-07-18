<?php
// Set correct error reporting for script
error_reporting(E_ALL);
ini_set('display_errors', 1);

// We need $koneksi
require_once __DIR__ . '/../../config/koneksi.php';

if (!$koneksi) {
    echo "Koneksi gagal: " . mysqli_connect_error() . "\n";
    exit(1);
}

echo "Koneksi berhasil!\n";

$sqlFile = __DIR__ . '/2026-07-11_crm_tiket_terstruktur.sql';
if (!file_exists($sqlFile)) {
    echo "File SQL tidak ditemukan: $sqlFile\n";
    exit(1);
}

$sqlContent = file_get_contents($sqlFile);
// Split queries by semicolon, but handle inline semi-colons inside JSON/strings carefully.
// A simple way is to use mysqli_multi_query.
if (mysqli_multi_query($koneksi, $sqlContent)) {
    do {
        // store first result set
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
