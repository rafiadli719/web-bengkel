<?php
// Dedupe master item (keputusan Rafi 2026-11-21) + UNIQUE tblitem.noitem.
//  1. Varian huruf besar/kecil digabung ke kode kanonik (versi PST):
//       23233-k44-V00 (PER CVT H.BEAT ORISIN, HPP 33.000)  <- 23233-K44-V00 (baris master varian dihapus)
//       33651-K25-901 (nama/harga identik)                  <- 33651-k25-901
//     Semua tabel transaksi/stok yang menyimpan kode item ikut diubah ke kode kanonik.
//  2. Item uji 10009, DMY01, aa dihapus beserta baris transaksi/stok terkait.
//  3. UNIQUE KEY uk_item_noitem (noitem). tblitem tidak punya PK/id, jadi hanya UNIQUE.
// Tabel gabung_* (mirror) dan tblitem_backup* tidak disentuh.
// Backup: bak_20261121_<tabel> berisi SEMUA baris yang cocok kode (case-insensitive) sebelum diubah. down memulihkan dari backup.
// Pakai: php 2026-11-21_run_master_item_dedupe.php [dry|up|down]
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? '';
if (!in_array($arah, ['dry', 'up', 'down'], true)) { echo "Argumen: dry|up|down\n"; exit(1); }
function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function esc($s) { global $koneksi; return mysqli_real_escape_string($koneksi, $s); }
function tabel_ada($t) { return mysqli_num_rows(qq("SHOW TABLES LIKE '" . esc($t) . "'")) > 0; }
function index_ada($t, $i) { return mysqli_num_rows(qq("SHOW INDEX FROM `$t` WHERE Key_name = '" . esc($i) . "'")) > 0; }

const PFX = 'bak_20261121_';
$KANONIK = ['23233-k44-V00', '33651-K25-901'];          // gabung (varian lain diubah ke ini)
$HAPUS   = ['10009', 'DMY01', 'aa'];                    // item uji
$SEMUA   = array_merge($KANONIK, $HAPUS);
$inList  = "'" . implode("','", array_map('esc', $SEMUA)) . "'";
$inHapus = "'" . implode("','", array_map('esc', $HAPUS)) . "'";

// tabel BASE dengan kolom kode item (selain tblitem), kecuali mirror/backup
$tabel = [];
$res = qq("SELECT c.TABLE_NAME t, c.COLUMN_NAME k FROM information_schema.COLUMNS c
           JOIN information_schema.TABLES x ON x.TABLE_SCHEMA = c.TABLE_SCHEMA AND x.TABLE_NAME = c.TABLE_NAME
           WHERE c.TABLE_SCHEMA = DATABASE() AND x.TABLE_TYPE = 'BASE TABLE'
             AND LOWER(c.COLUMN_NAME) IN ('noitem','no_item')
             AND c.TABLE_NAME NOT LIKE 'bak%' AND c.TABLE_NAME NOT LIKE 'gabung\\_%'
             AND c.TABLE_NAME NOT LIKE 'tblitem\\_backup%' AND c.TABLE_NAME <> 'tblitem'
           ORDER BY c.TABLE_NAME");
while ($x = mysqli_fetch_assoc($res)) $tabel[] = [$x['t'], $x['k']];
$tabel[] = ['tblitem', 'noitem'];

if ($arah === 'down') {
    // UNIQUE harus dilepas dulu: backup berisi kode dobel
    if (index_ada('tblitem', 'uk_item_noitem')) { qq("ALTER TABLE tblitem DROP INDEX uk_item_noitem"); echo "tblitem: DROP uk_item_noitem\n"; }
    foreach ($tabel as [$t, $c]) {
        $b = PFX . $t;
        if (!tabel_ada($b)) continue;
        qq("DELETE FROM `$t` WHERE `$c` IN ($inList)");
        qq("INSERT INTO `$t` SELECT * FROM `$b`");
        qq("DROP TABLE `$b`");
        echo "$t: dipulihkan dari $b\n";
    }
    if (index_ada('tblitem', 'uk_item_noitem')) { qq("ALTER TABLE tblitem DROP INDEX uk_item_noitem"); echo "tblitem: DROP uk_item_noitem\n"; }
    echo "DOWN selesai\n"; exit(0);
}

echo "Tabel diproses: " . implode(', ', array_map(fn($p) => "{$p[0]}.{$p[1]}", $tabel)) . "\n";
$total = 0;
foreach ($tabel as [$t, $c]) {
    $n = (int) mysqli_fetch_row(qq("SELECT COUNT(*) FROM `$t` WHERE `$c` IN ($inList)"))[0];
    if ($n) echo "  $t: $n baris terkait\n";
    $total += $n;
}
echo "Total baris terkait: $total\n";
if ($arah === 'dry') { echo "DRY selesai (tidak ada perubahan)\n"; exit(0); }

foreach ($tabel as [$t, $c]) {
    $b = PFX . $t;
    if (!tabel_ada($b)) qq("CREATE TABLE `$b` AS SELECT * FROM `$t` WHERE `$c` IN ($inList)");
}
// 1. hapus item uji + transaksinya
foreach ($tabel as [$t, $c]) {
    qq("DELETE FROM `$t` WHERE `$c` IN ($inHapus)");
    $a = mysqli_affected_rows($koneksi);
    if ($a > 0) echo "$t: hapus $a baris item uji\n";
}
// 2. gabung varian huruf ke kode kanonik
foreach ($KANONIK as $kn) {
    $kne = esc($kn);
    // pastikan baris master kanonik ada (pemulihan bila tertinggal setelah down yang gagal)
    if ((int) mysqli_fetch_row(qq("SELECT COUNT(*) FROM tblitem WHERE BINARY noitem = BINARY '$kne'"))[0] === 0) {
        qq("INSERT INTO tblitem SELECT * FROM `" . PFX . "tblitem` WHERE BINARY noitem = BINARY '$kne'");
        echo "tblitem: baris kanonik $kn dipulihkan dari backup\n";
    }
    foreach ($tabel as [$t, $c]) {
        if ($t === 'tblitem') {
            qq("DELETE FROM tblitem WHERE noitem = '$kne' AND BINARY noitem <> BINARY '$kne'");
            $a = mysqli_affected_rows($koneksi);
            if ($a > 0) echo "tblitem: hapus $a baris varian $kn\n";
            continue;
        }
        if ($t === 'tblitem_harga_cabang') {
            // jangan bentrok (noitem, kd_cabang): buang varian bila kanonik sudah punya harga di cabang itu
            qq("DELETE v FROM tblitem_harga_cabang v JOIN tblitem_harga_cabang k
                ON k.kd_cabang = v.kd_cabang AND BINARY k.noitem = BINARY '$kne'
                WHERE v.noitem = '$kne' AND BINARY v.noitem <> BINARY '$kne'");
        }
        qq("UPDATE `$t` SET `$c` = '$kne' WHERE `$c` = '$kne' AND BINARY `$c` <> BINARY '$kne'");
        $a = mysqli_affected_rows($koneksi);
        if ($a > 0) echo "$t: $a baris diubah ke $kn\n";
    }
}
// 3. UNIQUE
$dob = qq("SELECT noitem, COUNT(*) n FROM tblitem GROUP BY noitem HAVING COUNT(*) > 1");
if (mysqli_num_rows($dob) > 0) {
    echo "ABORT UNIQUE: masih ada kode dobel di tblitem (backup ada, jalankan down untuk batal):\n";
    while ($x = mysqli_fetch_assoc($dob)) echo "  {$x['noitem']} x{$x['n']}\n";
    exit(1);
}
if (!index_ada('tblitem', 'uk_item_noitem')) { qq("ALTER TABLE tblitem ADD UNIQUE KEY uk_item_noitem (noitem)"); echo "tblitem: ADD UNIQUE uk_item_noitem\n"; }
echo "UP selesai\n";
