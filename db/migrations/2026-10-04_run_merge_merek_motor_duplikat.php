<?php
// K3 audit UI/UX: merge merek motor duplikat (HONDA/YAMAHA/SUZUKI/KAWASAKI ganda) di tbpabrik_motor.
// Pakai: php 2026-10-04_run_merge_merek_motor_duplikat.php [up|down]
// up   : backup, remap FK ke id kanonik (paling banyak dipakai, lalu id terkecil), hapus duplikat
// down : kembalikan dari tabel bak_20261004b_*
// Orphan legacy (id 1-4 yang tidak ada barisnya) SENGAJA tidak disentuh: pemetaannya keputusan bisnis.
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';

function rows($k, $sql) { $o = []; $r = mysqli_query($k, $sql); while ($x = mysqli_fetch_assoc($r)) $o[] = $x; return $o; }
function tbl_exists($k, $t) { $r = mysqli_query($k, "SHOW TABLES LIKE '" . mysqli_real_escape_string($k, $t) . "'"); return mysqli_num_rows($r) > 0; }

$BAK = 'bak_20261004b_';
// [tabel, kolom FK, kolom kunci baris untuk restore]
$fk = [
    ['tblkendaraan', 'kode_merek', 'nopolisi'],
    ['tbtipe_motor', 'kode_pabrik', 'kode_tipe'],
    ['tblitem', 'kd_pabrik', 'noitem'],
    ['tblsupplier_spart', 'id_pabrik', 'id'],
    ['tblpelanggan', 'merek_id', 'nopelanggan'],      // FK nyata
    ['tbmaster_tipe_header', 'id_brand', 'id'],        // FK nyata
];

if ($mode === 'down') {
    if (!tbl_exists($koneksi, $BAK . 'tbpabrik_motor')) { echo "Backup tidak ada.\n"; exit(0); }
    mysqli_begin_transaction($koneksi);
    mysqli_query($koneksi, "INSERT IGNORE INTO tbpabrik_motor SELECT * FROM {$BAK}tbpabrik_motor");
    foreach ($fk as [$t, $c, $pk]) {
        if (!tbl_exists($koneksi, $BAK . $t)) continue;
        mysqli_query($koneksi, "UPDATE `$t` x JOIN {$BAK}$t b ON b.`$pk` = x.`$pk` SET x.`$c` = b.`$c` WHERE x.`$c` <> b.`$c`");
    }
    mysqli_commit($koneksi);
    echo "Down selesai. Tabel {$BAK}* dibiarkan.\n";
    exit(0);
}

$pakai = [];
foreach ($fk as [$t, $c, $pk]) {
    if (!tbl_exists($koneksi, $t)) continue;
    foreach (rows($koneksi, "SELECT `$c` k, COUNT(*) n FROM `$t` GROUP BY `$c`") as $r) {
        $pakai[(int)$r['k']] = ($pakai[(int)$r['k']] ?? 0) + (int)$r['n'];
    }
}
$grup = [];
foreach (rows($koneksi, "SELECT id, merek, kode_brand, status FROM tbpabrik_motor WHERE status='1'") as $m) {
    $grup[strtoupper(trim($m['merek'])) . '|' . strtoupper(trim((string)$m['kode_brand']))][] = (int)$m['id'];
}
$remap = [];
foreach ($grup as $ids) {
    if (count($ids) < 2) continue;
    usort($ids, function ($a, $b) use ($pakai) {
        $pa = $pakai[$a] ?? 0; $pb = $pakai[$b] ?? 0;
        return $pa === $pb ? $a <=> $b : $pb <=> $pa;
    });
    $kanon = array_shift($ids);
    foreach ($ids as $d) $remap[$d] = $kanon;
}
echo "Remap merek (duplikat => kanonik): " . json_encode($remap) . "\n";
if (!$remap) { echo "Tidak ada yang perlu diubah.\n"; exit(0); }

if (!tbl_exists($koneksi, $BAK . 'tbpabrik_motor')) {
    mysqli_query($koneksi, "CREATE TABLE {$BAK}tbpabrik_motor AS SELECT * FROM tbpabrik_motor");
    $ids = implode(',', array_keys($remap));
    foreach ($fk as [$t, $c, $pk]) {
        if (!tbl_exists($koneksi, $t)) continue;
        mysqli_query($koneksi, "CREATE TABLE {$BAK}$t AS SELECT * FROM `$t` WHERE `$c` IN ($ids)");
    }
    echo "Backup dibuat ({$BAK}*).\n";
}

mysqli_begin_transaction($koneksi);
try {
    foreach ($remap as $dup => $kanon) {
        foreach ($fk as [$t, $c, $pk]) {
            if (!tbl_exists($koneksi, $t)) continue;
            mysqli_query($koneksi, "UPDATE `$t` SET `$c`=$kanon WHERE `$c`=$dup");
        }
    }
    foreach (array_keys($remap) as $dup) {
        $sisa = 0;
        foreach ($fk as [$t, $c, $pk]) {
            if (!tbl_exists($koneksi, $t)) continue;
            $sisa += (int)rows($koneksi, "SELECT COUNT(*) n FROM `$t` WHERE `$c`=$dup")[0]['n'];
        }
        if ($sisa > 0) throw new Exception("Merek $dup masih direferensikan ($sisa baris)");
        mysqli_query($koneksi, "DELETE FROM tbpabrik_motor WHERE id=$dup");
    }
    mysqli_commit($koneksi);
} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    echo "GAGAL, rollback: " . $e->getMessage() . "\n"; exit(1);
}
echo "Migration executed successfully!\n";
