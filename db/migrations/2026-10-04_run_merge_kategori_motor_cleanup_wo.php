<?php
// Merge duplikat tbkategori_motor + bersihkan sampah tbworkorderheader.
// Pakai: php 2026-10-04_run_merge_kategori_motor_cleanup_wo.php [up|down]
// up   : backup baris yang disentuh ke tabel bak_20261004_*, remap FK, hapus duplikat/sampah
// down : kembalikan dari tabel bak_20261004_* (reversible)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';

function norm($s) { return strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string)$s)); }
function rows($k, $sql) { $o = []; $r = mysqli_query($k, $sql); while ($x = mysqli_fetch_assoc($r)) $o[] = $x; return $o; }
function tbl_exists($k, $t) { $r = mysqli_query($k, "SHOW TABLES LIKE '" . mysqli_real_escape_string($k, $t) . "'"); return mysqli_num_rows($r) > 0; }

$BAK = 'bak_20261004_';
// Tabel anak yang menyimpan id kategori motor: [tabel, kolom]
$fk = [
    ['tbworkorder_jenis_motor', 'kd_kategori_motor'],
    ['tbitem_jenis_motor', 'kd_kategori_motor'],
    ['tbmaster_tipe_detail', 'id_kategori_motor'],
    ['tbtipe_motor', 'kode_kategori'],
];

if ($mode === 'down') {
    if (!tbl_exists($koneksi, $BAK . 'tbkategori_motor')) { echo "Backup tidak ada, tidak ada yang dikembalikan.\n"; exit(0); }
    mysqli_begin_transaction($koneksi);
    mysqli_query($koneksi, "INSERT IGNORE INTO tbkategori_motor SELECT * FROM {$BAK}tbkategori_motor");
    mysqli_query($koneksi, "DELETE FROM tbworkorder_jenis_motor");
    mysqli_query($koneksi, "INSERT INTO tbworkorder_jenis_motor SELECT * FROM {$BAK}tbworkorder_jenis_motor");
    mysqli_query($koneksi, "REPLACE INTO tbworkorderheader SELECT * FROM {$BAK}tbworkorderheader");
    mysqli_query($koneksi, "INSERT IGNORE INTO tbworkorderdetail SELECT * FROM {$BAK}tbworkorderdetail");
    mysqli_commit($koneksi);
    echo "Down selesai. Tabel {$BAK}* dibiarkan (hapus manual kalau sudah tidak perlu).\n";
    exit(0);
}

// ---------- UP ----------
// 1) Tentukan kategori kanonik per nama ternormalisasi (abaikan spasi/tanda baca).
$kat = rows($koneksi, "SELECT id, kategori, status FROM tbkategori_motor ORDER BY id");
$pakai = [];
foreach (rows($koneksi, "SELECT kode_kategori k, COUNT(*) n FROM tbtipe_motor GROUP BY kode_kategori") as $r) $pakai[(int)$r['k']] = (int)$r['n'];
$grup = [];
foreach ($kat as $k) { if ($k['status'] === '0') continue; $grup[norm($k['kategori'])][] = (int)$k['id']; }
$remap = []; // id duplikat => id kanonik
foreach ($grup as $nama => $ids) {
    if (count($ids) < 2) continue;
    usort($ids, function ($a, $b) use ($pakai) {
        $pa = $pakai[$a] ?? 0; $pb = $pakai[$b] ?? 0;
        return $pa === $pb ? $a <=> $b : $pb <=> $pa;
    });
    $kanon = array_shift($ids);
    foreach ($ids as $d) $remap[$d] = $kanon;
}
echo "Remap kategori (duplikat => kanonik): " . json_encode($remap) . "\n";

// 2) WO sampah: hanya hapus kalau nama cocok DAN tidak dipakai servis/keluhan.
$hapusWo = [];
foreach ([['WO0004', "nama_wo='2444'"], ['WO0006', "nama_wo='coba'"], ['WO0007', "nama_wo LIKE 'DUMMYQA%'"]] as [$kode, $cond]) {
    $ada = rows($koneksi, "SELECT kode_wo FROM tbworkorderheader WHERE kode_wo='$kode' AND $cond");
    if (!$ada) continue;
    $pakaiServis = rows($koneksi, "SELECT COUNT(*) n FROM tbservis_workorder WHERE kode_wo='$kode'")[0]['n'];
    $pakaiKel = rows($koneksi, "SELECT COUNT(*) n FROM tbmaster_keluhan_workorder WHERE kode_workorder='$kode'")[0]['n'];
    $pakaiNested = rows($koneksi, "SELECT COUNT(*) n FROM tbworkorderdetail WHERE kode_barang='$kode' AND kode_wo<>'$kode'")[0]['n'];
    if ($pakaiServis + $pakaiKel + $pakaiNested > 0) { echo "SKIP $kode: masih dipakai.\n"; continue; }
    $hapusWo[] = $kode;
}
echo "WO dihapus: " . json_encode($hapusWo) . "\n";
$perbaikiWo3 = count(rows($koneksi, "SELECT 1 FROM tbworkorderheader WHERE kode_wo='WO0003' AND nama_wo=''")) > 0;

if (!$remap && !$hapusWo && !$perbaikiWo3) { echo "Tidak ada yang perlu diubah.\n"; exit(0); }

// 3) Backup (sekali; DDL implicit commit jadi di luar transaksi)
if (!tbl_exists($koneksi, $BAK . 'tbkategori_motor')) {
    mysqli_query($koneksi, "CREATE TABLE {$BAK}tbkategori_motor AS SELECT * FROM tbkategori_motor");
    mysqli_query($koneksi, "CREATE TABLE {$BAK}tbworkorder_jenis_motor AS SELECT * FROM tbworkorder_jenis_motor");
    mysqli_query($koneksi, "CREATE TABLE {$BAK}tbworkorderheader AS SELECT * FROM tbworkorderheader");
    mysqli_query($koneksi, "CREATE TABLE {$BAK}tbworkorderdetail AS SELECT * FROM tbworkorderdetail");
    echo "Backup dibuat ({$BAK}*).\n";
}

mysqli_begin_transaction($koneksi);
try {
    foreach ($remap as $dup => $kanon) {
        foreach ($fk as [$t, $c]) {
            if (!tbl_exists($koneksi, $t)) continue;
            mysqli_query($koneksi, "UPDATE `$t` SET `$c`=$kanon WHERE `$c`=$dup");
        }
    }
    // Dedupe mapping WO setelah remap
    mysqli_query($koneksi, "DELETE a FROM tbworkorder_jenis_motor a JOIN tbworkorder_jenis_motor b
        ON a.kode_wo=b.kode_wo AND a.kd_kategori_motor=b.kd_kategori_motor AND a.kd_jenis_motor=b.kd_jenis_motor AND a.id>b.id");
    // Hapus baris kategori duplikat hanya kalau sudah tidak ada referensi
    foreach (array_keys($remap) as $dup) {
        $sisa = 0;
        foreach ($fk as [$t, $c]) {
            if (!tbl_exists($koneksi, $t)) continue;
            $sisa += (int)rows($koneksi, "SELECT COUNT(*) n FROM `$t` WHERE `$c`=$dup")[0]['n'];
        }
        if ($sisa > 0) throw new Exception("Kategori $dup masih direferensikan ($sisa baris)");
        mysqli_query($koneksi, "DELETE FROM tbkategori_motor WHERE id=$dup");
    }
    foreach ($hapusWo as $kode) {
        mysqli_query($koneksi, "DELETE FROM tbworkorder_jenis_motor WHERE kode_wo='$kode'");
        mysqli_query($koneksi, "DELETE FROM tbworkorderdetail WHERE kode_wo='$kode'");
        mysqli_query($koneksi, "DELETE FROM tbworkorderheader WHERE kode_wo='$kode'");
    }
    if ($perbaikiWo3) {
        mysqli_query($koneksi, "UPDATE tbworkorderheader SET nama_wo='GANTI KARET VAKUM' WHERE kode_wo='WO0003' AND nama_wo=''");
    }
    mysqli_commit($koneksi);
} catch (Throwable $e) {
    mysqli_rollback($koneksi);
    echo "GAGAL, rollback: " . $e->getMessage() . "\n"; exit(1);
}
echo "Migration executed successfully!\n";
