<?php
// Tabel pelanggan_alias (usulan master 3.1 di docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md).
// Menyimpan varian nama/telepon per cabang untuk pelanggan yang datanya berbeda antar cabang.
// Hanya MENAMBAH tabel baru; tblpelanggan tidak diubah. down = DROP TABLE.
// perlu_review=1: nama depan (tanpa sapaan) berbeda antar cabang, kemungkinan orang berbeda.
// Sumber: database fitmotor_center (env CENTER_DB_NAME) di server MySQL yang sama.
// Pakai: php 2026-11-26_run_pelanggan_alias.php [dry|up|down]
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1G');
set_time_limit(0);
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? '';
if (!in_array($arah, ['dry', 'up', 'down'], true)) { echo "Argumen: dry|up|down\n"; exit(1); }
function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  " . substr($sql, 0, 300) . "\n"; exit(1); } return $r; }
function esc($s) { global $koneksi; return mysqli_real_escape_string($koneksi, $s); }

if ($arah === 'down') { qq("DROP TABLE IF EXISTS pelanggan_alias"); echo "pelanggan_alias dihapus\n"; exit(0); }

$center = getenv('CENTER_DB_NAME') ?: 'fitmotor_center';
if (!preg_match('/^\w+$/', $center)) { echo "CENTER_DB_NAME tidak valid\n"; exit(1); }

// Bandingkan tanpa spasi, tanda baca, dan huruf besar/kecil.
function rapat($s) { return preg_replace('/[^A-Z0-9]/', '', strtoupper((string)$s)); }
// Nama depan tanpa sapaan; '' berarti isian umum (BAPAK, IBU, -).
function nama_depan($s) {
    static $sapaan = ['BPK', 'BAPAK', 'PAK', 'BP', 'BU', 'IBU', 'MAS', 'MBA', 'MBAK', 'MB', 'MAK', 'SDR', 'SDRI', 'BANG', 'KANG', 'OM', 'TN', 'NY', 'H', 'HJ'];
    foreach (preg_split('/[^A-Z0-9]+/', strtoupper((string)$s), -1, PREG_SPLIT_NO_EMPTY) as $k) {
        if (!in_array($k, $sapaan, true)) { return $k; }
    }
    return '';
}

$cabang = [];
$r = qq("SELECT cabang_ref_kode, kode_cabang FROM tbcabang WHERE cabang_ref_kode IS NOT NULL AND cabang_ref_kode <> ''");
while ($x = mysqli_fetch_row($r)) { $cabang[$x[0]] = $x[1]; }

$web = [];
$r = qq("SELECT nopelanggan FROM tblpelanggan");
while ($x = mysqli_fetch_row($r)) { $web[strtoupper(trim($x[0]))] = $x[0]; }

$data = [];
$r = qq("SELECT NoPelanggan, kode_cabang, NamaPelanggan, Telephone FROM `$center`.tblpelanggan ORDER BY NoPelanggan, kode_cabang, NamaPelanggan, Telephone");
while ($x = mysqli_fetch_row($r)) {
    $id = strtoupper(trim((string)$x[0]));
    if (!isset($cabang[$x[1]], $web[$id])) { continue; }
    $data[$id][$cabang[$x[1]]] = ['nama' => trim((string)$x[2]), 'tel' => trim((string)$x[3]), 'servis' => null];
}
$r = qq("SELECT NoPelanggan, kode_cabang, MAX(Tanggal) FROM `$center`.tblservice GROUP BY NoPelanggan, kode_cabang");
while ($x = mysqli_fetch_row($r)) {
    $id = strtoupper(trim((string)$x[0]));
    if (isset($cabang[$x[1]]) && isset($data[$id][$cabang[$x[1]]])) { $data[$id][$cabang[$x[1]]]['servis'] = $x[2]; }
}

$baris = [];
$beda = $review = 0;
foreach ($data as $id => $per) {
    $nama = $tel = $depan = [];
    foreach ($per as $v) {
        if (rapat($v['nama']) !== '') { $nama[rapat($v['nama'])] = 1; }
        if (rapat($v['tel']) !== '') { $tel[rapat($v['tel'])] = 1; }
        if (nama_depan($v['nama']) !== '') { $depan[nama_depan($v['nama'])] = 1; }
    }
    if (count($nama) < 2 && count($tel) < 2) { continue; }
    $beda++;
    $flag = count($depan) > 1 ? 1 : 0;
    $review += $flag;
    foreach ($per as $cab => $v) { $baris[] = [$web[$id], $cab, $v['nama'], $v['tel'], $v['servis'], $flag]; }
}
echo "pelanggan di center & web: " . count($data) . "\n";
echo "nama/telepon beda antar cabang: $beda pelanggan, perlu_review: $review, baris alias: " . count($baris) . "\n";
if ($arah === 'dry') { exit(0); }

// Tipe dan collation kunci disamakan dengan tblpelanggan agar JOIN tidak gagal diam-diam.
$k = mysqli_fetch_assoc(qq("SHOW FULL COLUMNS FROM tblpelanggan LIKE 'nopelanggan'"));
$charset = explode('_', $k['Collation'])[0];
qq("CREATE TABLE IF NOT EXISTS pelanggan_alias (
    nopelanggan {$k['Type']} CHARACTER SET $charset COLLATE {$k['Collation']} NOT NULL,
    kd_cabang VARCHAR(20) NOT NULL,
    nama VARCHAR(150) NOT NULL DEFAULT '',
    telepon VARCHAR(100) NOT NULL DEFAULT '',
    terakhir_servis DATETIME NULL,
    perlu_review TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (nopelanggan, kd_cabang),
    KEY idx_alias_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=$charset COLLATE={$k['Collation']}");

// Isi ulang penuh supaya varian yang sudah tidak berbeda tidak tertinggal saat dijalankan ulang.
qq("START TRANSACTION");
qq("DELETE FROM pelanggan_alias");
foreach (array_chunk($baris, 500) as $chunk) {
    $v = [];
    foreach ($chunk as $b) {
        // Koneksi latin1 (config/koneksi.php): byte dari center diteruskan apa adanya, tanpa konversi.
        $t = [];
        foreach ([0, 1, 2, 3] as $i) { $t[$i] = esc($b[$i]); }
        $tgl = ($b[4] === null || strpos($b[4], '0000') === 0) ? 'NULL' : "'" . esc($b[4]) . "'";
        $v[] = "('{$t[0]}','{$t[1]}',LEFT('{$t[2]}',150),LEFT('{$t[3]}',100),$tgl,{$b[5]})";
    }
    qq("INSERT INTO pelanggan_alias (nopelanggan, kd_cabang, nama, telepon, terakhir_servis, perlu_review) VALUES " . implode(',', $v));
}
qq("COMMIT");
echo "baris di pelanggan_alias: " . mysqli_fetch_row(qq("SELECT COUNT(*) FROM pelanggan_alias"))[0] . "\n";
