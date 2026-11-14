<?php
// Fase 2 rencana no_service: tambah kd_cabang ke 18 tabel anak servis yang belum punya, lalu backfill.
// Identitas servis = (no_service, kd_cabang). Aturan backfill (tidak menebak):
//   - no_service hanya ada di SATU cabang di tblservice  -> diisi, dicatat di ns_koreksi_log (fase 2).
//   - no_service ada di >1 cabang (ambigu) atau tidak ada induk -> kd_cabang tetap NULL + ns_karantina.
// Kolom dibuat NULL (bukan NOT NULL) karena baris karantina tidak boleh dipalsukan. NOT NULL baru
// dipasang setelah karantina tuntas (lihat runbook). Kolom memakai charset/collation yang sama dengan
// tblservice.kd_cabang agar JOIN memakai indeks dan tidak kena "Illegal mix of collations".
// Pakai: php 2026-11-14_run_no_service_fase2_kd_cabang.php [dry|up|down]   (default dry; up idempotent)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? 'dry';
if (!in_array($arah, ['dry', 'up', 'down'], true)) { echo "Argumen: dry|up|down\n"; exit(1); }

$tabel = [ // tabel => PK
    'master_kedatangan_pelanggan' => 'id_kedatangan', 'promo_usage_log' => 'id', 'tb_antrian_servis' => 'id',
    'tb_log_antrian' => 'id', 'tb_log_cancel_servis' => 'id', 'tb_mekanik_progress' => 'id', 'tb_progress_mekanik' => 'id',
    'tbservis_keluhan' => 'id', 'tbservis_keluhan_status' => 'id', 'tbservis_keluhan_tracking' => 'id',
    'tbservis_penawaran_jasa' => 'id', 'tbservis_penawaran_log' => 'id', 'tbservis_penawaran_part' => 'id',
    'tbservis_pending_items' => 'id', 'tbservis_pengerjaan' => 'id', 'tbservis_temuan' => 'id',
    'tbservis_temuan_activity_log' => 'id', 'tbservis_workorder' => 'id',
];

function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function esc($v) { global $koneksi; return mysqli_real_escape_string($koneksi, (string)$v); }
function punyaKolom($t) { return mysqli_num_rows(qq("SHOW COLUMNS FROM `$t` LIKE 'kd_cabang'")) > 0; }
function punyaIndeks($t, $i) { return mysqli_num_rows(qq("SHOW INDEX FROM `$t` WHERE Key_name='$i'")) > 0; }

if ($arah === 'down') {
    foreach ($tabel as $t => $pk) {
        if (punyaIndeks($t, 'idx_ns_kdcab')) qq("ALTER TABLE `$t` DROP INDEX idx_ns_kdcab");
        if (punyaKolom($t)) qq("ALTER TABLE `$t` DROP COLUMN kd_cabang");
        echo "Down: $t\n";
    }
    if (mysqli_num_rows(qq("SHOW TABLES LIKE 'ns_karantina'")) > 0) qq("DELETE FROM ns_karantina WHERE fase=2");
    if (mysqli_num_rows(qq("SHOW TABLES LIKE 'ns_koreksi_log'")) > 0) qq("DELETE FROM ns_koreksi_log WHERE fase=2");
    echo "Migration down executed successfully!\n";
    exit(0);
}

if (mysqli_num_rows(qq("SHOW TABLES LIKE 'ns_karantina'")) === 0) { echo "Jalankan Fase 1 dulu (ns_karantina belum ada).\n"; exit(1); }

// charset/collation kolom induk
$ci = mysqli_fetch_assoc(qq("SELECT CHARACTER_SET_NAME cs, COLLATION_NAME co, CHARACTER_MAXIMUM_LENGTH len FROM information_schema.COLUMNS
                             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblservice' AND COLUMN_NAME='kd_cabang'"));
$def = "VARCHAR({$ci['len']}) CHARACTER SET {$ci['cs']} COLLATE {$ci['co']} NULL DEFAULT NULL";
echo "Tipe kolom: $def\n";

$nIsi = 0; $nKar = 0;
foreach ($tabel as $t => $pk) {
    if ($arah === 'up') {
        if (!punyaKolom($t)) qq("ALTER TABLE `$t` ADD COLUMN kd_cabang $def AFTER no_service");
        if (!punyaIndeks($t, 'idx_ns_kdcab')) qq("ALTER TABLE `$t` ADD INDEX idx_ns_kdcab (no_service, kd_cabang)");
    }
    $adaKol = punyaKolom($t);
    $cond = $adaKol ? "a.kd_cabang IS NULL" : "1=1";
    $r = qq("SELECT a.no_service, COUNT(*) n, GROUP_CONCAT(a.`$pk`) ids,
               (SELECT GROUP_CONCAT(DISTINCT s.kd_cabang ORDER BY s.kd_cabang) FROM tblservice s WHERE s.no_service = a.no_service) kandidat
             FROM `$t` a WHERE $cond GROUP BY a.no_service");
    $isiT = 0; $karT = 0;
    while ($x = mysqli_fetch_assoc($r)) {
        $kand = $x['kandidat'] === null ? [] : explode(',', $x['kandidat']);
        if (count($kand) === 1) {
            $isiT += $x['n']; $nIsi++;
            if ($arah === 'up') {
                $ids = implode(',', array_map('intval', explode(',', $x['ids'])));
                qq("INSERT INTO ns_koreksi_log (fase,tabel,no_service,kd_lama,kd_baru,row_ids,jumlah_baris)
                    VALUES (2,'$t','" . esc($x['no_service']) . "',NULL,'" . esc($kand[0]) . "','$ids'," . (int)$x['n'] . ")");
                qq("UPDATE `$t` SET kd_cabang='" . esc($kand[0]) . "' WHERE `$pk` IN ($ids) AND kd_cabang IS NULL");
            }
        } else {
            $karT += $x['n']; $nKar++;
            $alasan = count($kand) === 0 ? 'tidak ada induk di tblservice (no_service yatim)' : 'no_service ada di >1 cabang; tidak ada sinyal pasti untuk memilih (tanggal anak tidak cocok dengan tanggal induk)';
            if ($arah === 'up') {
                qq("INSERT IGNORE INTO ns_karantina (fase,tabel,no_service,kd_cabang_lama,kandidat,jumlah_baris,alasan)
                    VALUES (2,'$t','" . esc($x['no_service']) . "','','" . esc($x['kandidat']) . "'," . (int)$x['n'] . ",'" . esc($alasan) . "')");
            }
        }
    }
    echo sprintf("%-32s diisi=%d baris, karantina=%d baris\n", $t, $isiT, $karT);
}
echo "\nRingkas: grup diisi=$nIsi, grup karantina=$nKar. Mode: $arah\n";
if ($arah === 'up') echo "Migration up executed successfully!\n";
