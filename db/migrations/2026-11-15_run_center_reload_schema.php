<?php
// Persiapan skema untuk muat ulang data per cabang dari fitmotor_center
// (docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md).
// Semua perubahan aditif dan reversible:
//   1. kolom kd_cabang di tblhutang_detail & tblpiutang_detail (nomor transaksi bentrok lintas cabang)
//   2. index (nomor, kd_cabang) di tabel transaksi yang belum punya, plus tblpembelian_detail(no_item,id)
//      supaya trigger trg_alarm_harga_beli tidak full scan per baris
//   3. (PK tblorder_header sengaja belum diubah, lihat catatan di blok up)
//   4. tabel center_reload_log (jejak tiap muat ulang)
// Pakai: php 2026-11-15_run_center_reload_schema.php [up|down]
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? '';
if (!in_array($arah, ['up', 'down'], true)) { echo "Argumen: up|down\n"; exit(1); }

function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function kolom_ada($t, $c) { $r = qq("SHOW COLUMNS FROM `$t` LIKE '$c'"); return mysqli_num_rows($r) > 0; }
function index_ada($t, $i) { $r = qq("SHOW INDEX FROM `$t` WHERE Key_name = '$i'"); return mysqli_num_rows($r) > 0; }
function pk_kolom($t) {
    $r = qq("SHOW INDEX FROM `$t` WHERE Key_name = 'PRIMARY'"); $k = [];
    while ($x = mysqli_fetch_assoc($r)) { $k[(int)$x['Seq_in_index']] = $x['Column_name']; }
    ksort($k); return implode(',', $k);
}

$kolomBaru = ['tblhutang_detail', 'tblpiutang_detail'];
$indexBaru = [
    ['tblpembelian_header', 'idx_cr_beli_h', 'notransaksi, kd_cabang'],
    ['tblpembelian_detail', 'idx_cr_beli_d', 'no_transaksi, kd_cabang'],
    ['tblpembelian_detail', 'idx_cr_beli_d_item', 'no_item, id'],
    ['tblpenjualan_header', 'idx_cr_jual_h', 'notransaksi, kd_cabang'],
    ['tblpenjualan_detail', 'idx_cr_jual_d', 'no_transaksi, kd_cabang'],
    ['tblorder_detail', 'idx_cr_po_d', 'no_order, kd_cabang'],
    ['tblorderjual_header', 'idx_cr_so_h', 'no_order, kd_cabang'],
    ['tblorderjual_detail', 'idx_cr_so_d', 'no_order, kd_cabang'],
    ['tblhutang_header', 'idx_cr_hut_h', 'no_transaksi, kd_cabang'],
    ['tblhutang_detail', 'idx_cr_hut_d', 'no_transaksi, kd_cabang'],
    ['tblpiutang_header', 'idx_cr_piu_h', 'no_transaksi, kd_cabang'],
    ['tblpiutang_detail', 'idx_cr_piu_d', 'no_transaksi, kd_cabang'],
    ['tblretur_pembelian_header', 'idx_cr_rb_h', 'noretur, kd_cabang'],
    ['tblretur_pembelian_detail', 'idx_cr_rb_d', 'no_retur, kd_cabang'],
    ['tbitem_masuk_header', 'idx_cr_im_h', 'no_transaksi, kd_cabang'],
    ['tbitem_masuk_detail', 'idx_cr_im_d', 'no_transaksi, kd_cabang'],
    ['tbitem_keluar_header', 'idx_cr_ik_h', 'no_transaksi, kd_cabang'],
    ['tbitem_keluar_detail', 'idx_cr_ik_d', 'no_transaksi, kd_cabang'],
    ['tblkas_keluar_masuk', 'idx_cr_kas', 'kode_km, kd_cabang'],
    ['tblservice_advisor', 'idx_cr_adv', 'no_service, kd_cabang'],
    ['tbstok', 'idx_cr_stok_cab_item_tgl', 'kd_cabang, no_item, tanggal'],
];

if ($arah === 'up') {
    foreach ($kolomBaru as $t) {
        if (!kolom_ada($t, 'kd_cabang')) {
            qq("ALTER TABLE `$t` ADD COLUMN kd_cabang VARCHAR(10) NOT NULL DEFAULT '' AFTER no_transaksi");
            echo "Kolom $t.kd_cabang ditambah\n";
        }
    }
    foreach ($indexBaru as [$t, $i, $c]) {
        if (!index_ada($t, $i)) { qq("ALTER TABLE `$t` ADD INDEX `$i` ($c)"); echo "Index $t.$i ditambah\n"; }
    }
    // PK tblorder_header TIDAK diubah di sini: dirujuk FK tbldelivery_order_header.fk_do_po dan
    // tblpo_approval_log (no_po -> no_order). Perubahan ke (no_order, kd_cabang) dikerjakan di fase
    // kunci gabungan PO (wajib sebelum reload cabang ke-2). Reload satu cabang aman: nomor PO unik per cabang.
    qq("CREATE TABLE IF NOT EXISTS center_reload_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        run_id VARCHAR(40) NOT NULL,
        kd_cabang VARCHAR(10) NOT NULL,
        dataset VARCHAR(64) NOT NULL,
        aksi VARCHAR(20) NOT NULL,
        baris_hapus INT NOT NULL DEFAULT 0,
        baris_isi INT NOT NULL DEFAULT 0,
        baris_lewat INT NOT NULL DEFAULT 0,
        tabel_backup VARCHAR(64) NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'ok',
        catatan TEXT NULL,
        mulai DATETIME NOT NULL,
        selesai DATETIME NULL,
        KEY idx_crl_run (run_id),
        KEY idx_crl_cab (kd_cabang, dataset)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    echo "Migration up executed successfully!\n";
    exit(0);
}

// down
$r = qq("SELECT COUNT(*) n FROM (SELECT no_order FROM tblorder_header GROUP BY no_order HAVING COUNT(*) > 1) x");
$dup = (int)mysqli_fetch_assoc($r)['n'];
if (pk_kolom('tblorder_header') === 'no_order,kd_cabang') {
    if ($dup > 0) { echo "Tolak down: $dup no_order dobel lintas cabang, PK lama tidak bisa dipulihkan. Kosongkan data reload dulu.\n"; exit(1); }
    qq("ALTER TABLE tblorder_header DROP PRIMARY KEY, ADD PRIMARY KEY (no_order)");
    echo "PK tblorder_header -> (no_order)\n";
}
foreach ($indexBaru as [$t, $i, $c]) {
    if (index_ada($t, $i)) { qq("ALTER TABLE `$t` DROP INDEX `$i`"); echo "Index $t.$i dihapus\n"; }
}
foreach ($kolomBaru as $t) {
    if (kolom_ada($t, 'kd_cabang')) {
        $r = qq("SELECT COUNT(*) n FROM `$t` WHERE kd_cabang <> ''");
        if ((int)mysqli_fetch_assoc($r)['n'] > 0) { echo "Lewati drop $t.kd_cabang: masih ada baris berisi cabang.\n"; continue; }
        qq("ALTER TABLE `$t` DROP COLUMN kd_cabang"); echo "Kolom $t.kd_cabang dihapus\n";
    }
}
$r = qq("SELECT COUNT(*) n FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'center_reload_log'");
if ((int)mysqli_fetch_assoc($r)['n'] > 0) {
    $r = qq("SELECT COUNT(*) n FROM center_reload_log");
    if ((int)mysqli_fetch_assoc($r)['n'] === 0) { qq("DROP TABLE center_reload_log"); echo "Tabel center_reload_log dihapus\n"; }
    else { echo "center_reload_log berisi jejak reload, tidak dihapus.\n"; }
}
echo "Migration down executed successfully!\n";
