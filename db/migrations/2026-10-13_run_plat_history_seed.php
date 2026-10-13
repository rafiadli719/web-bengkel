<?php
// B4 nopol lama: isi kendaraan_plat_history satu baris plat aktif per kendaraan yang belum punya riwayat.
// Pakai: php 2026-10-13_run_plat_history_seed.php [up|down]
// up   : INSERT baris is_current=1 alasan 'kendaraan_baru' (tanggal_mulai = hari migrasi) untuk kendaraan tanpa riwayat
// down : hapus baris seed (alasan 'kendaraan_baru', is_current=1, diinput_oleh NULL)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';
if ($mode === 'up') {
    mysqli_query($koneksi, "INSERT INTO kendaraan_plat_history (id_kendaraan, nopolisi, tanggal_mulai, is_current, alasan)
                            SELECT k.id_kendaraan, k.nopolisi, CURDATE(), 1, 'kendaraan_baru'
                            FROM tblkendaraan k
                            LEFT JOIN kendaraan_plat_history h ON h.id_kendaraan = k.id_kendaraan
                            WHERE h.id IS NULL AND k.id_kendaraan IS NOT NULL");
    echo "Up selesai. Baris ditambah: " . mysqli_affected_rows($koneksi) . "\n";
} else {
    mysqli_query($koneksi, "DELETE FROM kendaraan_plat_history WHERE alasan = 'kendaraan_baru' AND is_current = 1 AND diinput_oleh IS NULL");
    echo "Down selesai. Baris dihapus: " . mysqli_affected_rows($koneksi) . "\n";
}
