<?php
/**
 * Hitung ulang statistik_pelanggan untuk pelanggan yang servisnya dimuat ulang dari fitmotor_center.
 * Rencana: docs/planning/PLAN_RELOAD_CENTER_PER_CABANG_2026-11-15.md (langkah setelah reload cabang).
 *
 * Memakai fungsi jalur live (updateStatistikPelangganAfterPayment, dipanggil saat bayar servis),
 * lalu floor tier dari grup pelanggan Access (fitmotorApplyMemberTierFloor) supaya tier tidak turun
 * di bawah grup yang sudah disinkron.
 *
 * Pakai:
 *   php refresh_statistik_cabang.php --cabang=PESALAKAN --mode=run [--tag=1115]
 *   php refresh_statistik_cabang.php --cabang=PESALAKAN --mode=rollback [--tag=1115]
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);
mysqli_report(MYSQLI_REPORT_OFF);
ini_set('error_log', 'NUL');   // fungsi statistik menulis error_log per pelanggan; jangan banjiri stderr

$opt = [];
foreach (array_slice($argv, 1) as $a) { if (preg_match('/^--([a-z_]+)=(.*)$/', $a, $m)) { $opt[$m[1]] = $m[2]; } }
$CAB  = strtoupper(trim($opt['cabang'] ?? ''));
$MODE = $opt['mode'] ?? '';
$TAG  = preg_replace('/[^0-9a-z]/i', '', $opt['tag'] ?? date('md'));
if (!in_array($CAB, ['PESALAKAN', 'PACUL', 'CIKDITIRO', 'TRAYEMAN', 'PST'], true) || !in_array($MODE, ['run', 'rollback'], true)) {
    fwrite(STDERR, "Pakai: --cabang=PESALAKAN|PACUL|CIKDITIRO|TRAYEMAN|PST --mode=run|rollback [--tag=MMDD]\n"); exit(1);
}

require __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { fwrite(STDERR, "Koneksi gagal\n"); exit(1); }
require_once __DIR__ . '/../../app/_include_statistik_pelanggan.php';
require_once __DIR__ . '/../../app/_include_kategori_member.php';

function q($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if ($r === false) { fwrite(STDERR, "SQL: " . mysqli_error($koneksi) . "\n  $sql\n"); exit(1); } return $r; }
function v($sql) { $x = mysqli_fetch_row(q($sql)); return $x ? $x[0] : null; }
function e($s) { global $koneksi; return mysqli_real_escape_string($koneksi, (string)$s); }
function ada($t) { return (int)v("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . e($t) . "'") > 0; }

$bakServis = substr("bak_cr{$TAG}_{$CAB}_tblservice", 0, 64);
$bakStat   = substr("bak_cr{$TAG}_{$CAB}_statistik_pelanggan", 0, 64);

if ($MODE === 'rollback') {
    if (!ada($bakStat)) { fwrite(STDERR, "Backup $bakStat tidak ada\n"); exit(1); }
    q("DELETE sp FROM statistik_pelanggan sp JOIN `$bakStat` b ON b.no_pelanggan = sp.no_pelanggan");
    q("INSERT INTO statistik_pelanggan SELECT * FROM `$bakStat`");
    echo "Rollback: statistik dipulihkan dari $bakStat (" . v("SELECT COUNT(*) FROM `$bakStat`") . " baris)\n";
    exit(0);
}

// 1. daftar pelanggan terdampak: servis cabang ini sekarang + servis cabang ini sebelum reload
q("DROP TEMPORARY TABLE IF EXISTS tmp_rs_pel");
q("CREATE TEMPORARY TABLE tmp_rs_pel (no_pelanggan VARCHAR(50) PRIMARY KEY) ENGINE=InnoDB DEFAULT CHARSET=latin1");
q("INSERT IGNORE INTO tmp_rs_pel SELECT DISTINCT no_pelanggan FROM tblservice WHERE kd_cabang = '" . e($CAB) . "' AND no_pelanggan <> ''");
if (ada($bakServis)) { q("INSERT IGNORE INTO tmp_rs_pel SELECT DISTINCT no_pelanggan FROM `$bakServis` WHERE no_pelanggan <> ''"); }
$total = (int)v("SELECT COUNT(*) FROM tmp_rs_pel");
echo "Pelanggan terdampak $CAB: $total\n";

// 2. backup statistik sekali (tidak ditimpa bila diulang)
if (!ada($bakStat)) {
    q("CREATE TABLE `$bakStat` LIKE statistik_pelanggan");
    q("INSERT INTO `$bakStat` SELECT sp.* FROM statistik_pelanggan sp JOIN tmp_rs_pel t ON t.no_pelanggan = sp.no_pelanggan");
    echo "Backup: $bakStat (" . v("SELECT COUNT(*) FROM `$bakStat`") . " baris)\n";
} else {
    echo "Backup lama dipakai: $bakStat\n";
}

// 3. hitung ulang
$r = q("SELECT t.no_pelanggan, IFNULL(p.kgrup, '') kgrup FROM tmp_rs_pel t LEFT JOIN tblpelanggan p ON p.nopelanggan = t.no_pelanggan");
$rows = [];
while ($x = mysqli_fetch_assoc($r)) { $rows[] = $x; }
$n = 0; $gagal = 0; $mulai = microtime(true);
foreach ($rows as $x) {
    if (!updateStatistikPelangganAfterPayment($koneksi, $x['no_pelanggan'])) { $gagal++; continue; }
    if ($x['kgrup'] !== '') { fitmotorApplyMemberTierFloor($koneksi, $x['no_pelanggan'], $x['kgrup']); }
    if (++$n % 2000 === 0) { printf("  %d/%d (%.0f dtk)\n", $n, $total, microtime(true) - $mulai); }
}
printf("Selesai: %d dihitung, %d gagal, %.0f dtk\n", $n, $gagal, microtime(true) - $mulai);

// 4. ringkasan perubahan tier dan kunjungan
$urut = "FIELD(%s,'Bronze','Silver','Gold','Platinum')";
$x = mysqli_fetch_assoc(q("SELECT
        SUM(" . sprintf($urut, 'sp.status_member') . " > " . sprintf($urut, 'b.status_member') . ") naik,
        SUM(" . sprintf($urut, 'sp.status_member') . " < " . sprintf($urut, 'b.status_member') . ") turun,
        SUM(sp.jumlah_kunjungan <> b.jumlah_kunjungan) kunjungan_berubah,
        SUM(sp.jumlah_kunjungan) kunjungan_baru, SUM(b.jumlah_kunjungan) kunjungan_lama
    FROM statistik_pelanggan sp JOIN `$bakStat` b ON b.no_pelanggan = sp.no_pelanggan"));
echo "Tier naik {$x['naik']}, turun {$x['turun']}; kunjungan berubah {$x['kunjungan_berubah']} pelanggan (total {$x['kunjungan_lama']} -> {$x['kunjungan_baru']})\n";
$baru = (int)v("SELECT COUNT(*) FROM tmp_rs_pel t JOIN statistik_pelanggan sp ON sp.no_pelanggan = t.no_pelanggan LEFT JOIN `$bakStat` b ON b.no_pelanggan = t.no_pelanggan WHERE b.no_pelanggan IS NULL");
echo "Baris statistik baru: $baru\n";
$r = q("SELECT sp.status_member, COUNT(*) FROM statistik_pelanggan sp JOIN tmp_rs_pel t ON t.no_pelanggan = sp.no_pelanggan GROUP BY 1 ORDER BY " . sprintf($urut, 'sp.status_member'));
while ($y = mysqli_fetch_row($r)) { echo "  {$y[0]}: {$y[1]}\n"; }
