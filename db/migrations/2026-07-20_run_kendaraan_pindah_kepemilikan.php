<?php
// Runner sekali-pakai untuk 2026-07-13_kendaraan_pindah_kepemilikan.sql.
// Pola sama seperti db/migrations/2026-07-17_run_alarm_harga_beli.php:
// dieksekusi lewat browser (bukan CLI), pakai koneksi app yang sama.
// Backup tblkendaraan dibuat dulu via CREATE TABLE ... AS SELECT (snapshot
// di DB yang sama, gampang dicek/di-restore, tidak butuh akses CLI/mysqldump).
include __DIR__ . "/../../config/koneksi.php";

$backupTable = "tblkendaraan_backup_20260720";

$chkBackup = mysqli_query($koneksi, "SHOW TABLES LIKE '$backupTable'");
if ($chkBackup && mysqli_num_rows($chkBackup) > 0) {
    echo "SKIP - backup $backupTable sudah ada, tidak ditimpa\n";
} else {
    if (mysqli_query($koneksi, "CREATE TABLE $backupTable AS SELECT * FROM tblkendaraan")) {
        $cnt = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM $backupTable"));
        echo "OK - backup dibuat: $backupTable (" . $cnt['c'] . " baris)\n";
    } else {
        echo "ERROR backup: " . mysqli_error($koneksi) . "\n";
        exit;
    }
}

$sql = file_get_contents(__DIR__ . "/2026-07-13_kendaraan_pindah_kepemilikan.sql");
if (mysqli_multi_query($koneksi, $sql)) {
    do {
        if ($result = mysqli_store_result($koneksi)) mysqli_free_result($result);
    } while (mysqli_more_results($koneksi) && mysqli_next_result($koneksi));
    if (mysqli_errno($koneksi)) {
        echo "ERROR migrasi (di tengah jalan): " . mysqli_error($koneksi) . "\n";
    } else {
        echo "OK - migrasi kendaraan_pindah_kepemilikan selesai\n";
    }
} else {
    echo "ERROR migrasi: " . mysqli_error($koneksi) . "\n";
}
