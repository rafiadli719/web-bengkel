<?php
// Fase K bagian 3: UNIQUE (nomor, kd_cabang) di header transaksi non-servis.
// Index idx_cr_* (non-unique, migrasi 2026-11-15) diganti uk_fk_* UNIQUE. Gagal aman bila ada dobel.
// Pakai: php 2026-11-16_run_fase_k_unique_header.php [up|down]
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? '';
if (!in_array($arah, ['up', 'down'], true)) { echo "Argumen: up|down\n"; exit(1); }
function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function index_ada($t, $i) { return mysqli_num_rows(qq("SHOW INDEX FROM `$t` WHERE Key_name = '$i'")) > 0; }
// [tabel, index lama, index baru, kolom nomor]
$daftar = [
    ['tblpembelian_header', 'idx_cr_beli_h', 'uk_fk_beli_h', 'notransaksi'],
    ['tblpenjualan_header', 'idx_cr_jual_h', 'uk_fk_jual_h', 'notransaksi'],
    ['tblorderjual_header', 'idx_cr_so_h', 'uk_fk_so_h', 'no_order'],
    ['tblhutang_header', 'idx_cr_hut_h', 'uk_fk_hut_h', 'no_transaksi'],
    ['tblpiutang_header', 'idx_cr_piu_h', 'uk_fk_piu_h', 'no_transaksi'],
    ['tblretur_pembelian_header', 'idx_cr_rb_h', 'uk_fk_rb_h', 'noretur'],
    ['tbitem_masuk_header', 'idx_cr_im_h', 'uk_fk_im_h', 'no_transaksi'],
    ['tbitem_keluar_header', 'idx_cr_ik_h', 'uk_fk_ik_h', 'no_transaksi'],
    ['tblkas_keluar_masuk', 'idx_cr_kas', 'uk_fk_kas', 'kode_km'],
];
foreach ($daftar as [$t, $lama, $baru, $kol]) {
    if ($arah === 'up') {
        if (index_ada($t, $baru)) { echo "$t: sudah ada $baru\n"; continue; }
        $dob = mysqli_fetch_row(qq("SELECT COUNT(*) FROM (SELECT 1 FROM `$t` GROUP BY `$kol`, kd_cabang HAVING COUNT(*)>1) x"))[0];
        if ((int)$dob > 0) { echo "ABORT $t: $dob nomor dobel dalam satu cabang\n"; exit(1); }
        $drop = index_ada($t, $lama) ? "DROP INDEX `$lama`, " : '';
        qq("ALTER TABLE `$t` {$drop}ADD UNIQUE KEY `$baru` (`$kol`, kd_cabang)");
        echo "$t: UNIQUE $baru ($kol, kd_cabang)\n";
    } else {
        if (!index_ada($t, $baru)) { continue; }
        qq("ALTER TABLE `$t` DROP INDEX `$baru`, ADD INDEX `$lama` (`$kol`, kd_cabang)");
        echo "$t: kembali ke $lama\n";
    }
}
echo strtoupper($arah) . " selesai\n";
