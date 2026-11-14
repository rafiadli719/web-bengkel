<?php
// Fase 1 rencana no_service (docs/planning/PLAN_NO_SERVICE_COMPOSITE_KEY_2026-11-09.md):
// bersihkan tabel anak yang SUDAH punya kd_cabang. Identitas servis = (no_service, kd_cabang).
// Aturan (tidak menebak):
//   A. kd_cabang kosong + no_service hanya ada di SATU cabang di tblservice -> diisi, dicatat di ns_koreksi_log.
//   B. kd_cabang terisi tapi pasangan (no_service,kd_cabang) tidak ada induk -> ns_karantina (data tidak diubah).
//   C. kd_cabang kosong + tidak ada induk / induk >1 cabang -> ns_karantina.
// Pakai: php 2026-11-14_run_no_service_fase1_bersih.php [dry|up|down]   (default dry; up idempotent)
// down: kembalikan kd_cabang baris yang diisi (berdasar id di ns_koreksi_log) dan hapus karantina fase 1.
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? 'dry';
if (!in_array($arah, ['dry', 'up', 'down'], true)) { echo "Argumen: dry|up|down\n"; exit(1); }

// tabel => kolom PK (null bila tidak ada; hanya tabel ber-PK yang boleh dikoreksi otomatis)
$tabel = [
    'tblservis_barang' => 'id', 'tblservis_jasa' => 'id', 'tblservice_advisor' => null, 'servis_komisi' => null,
    'tb_dp_servis' => null, 'tblretur_servis_header' => null, 'tblretur_servis_detail' => null, 'tb_approval_diskon' => null,
];

function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function esc($v) { global $koneksi; return mysqli_real_escape_string($koneksi, (string)$v); }

if ($arah === 'down') {
    $r = qq("SHOW TABLES LIKE 'ns_koreksi_log'");
    if (mysqli_num_rows($r) === 0) { echo "ns_koreksi_log tidak ada, lewati.\n"; exit(0); }
    $r = qq("SELECT id, tabel, row_ids, kd_lama, kd_baru FROM ns_koreksi_log WHERE fase=1 AND dibatalkan=0");
    while ($x = mysqli_fetch_assoc($r)) {
        $ids = implode(',', array_map('intval', explode(',', $x['row_ids'])));
        qq("UPDATE `{$x['tabel']}` SET kd_cabang='" . esc($x['kd_lama']) . "' WHERE id IN ($ids) AND kd_cabang='" . esc($x['kd_baru']) . "'");
        qq("UPDATE ns_koreksi_log SET dibatalkan=1 WHERE id=" . (int)$x['id']);
        echo "Batal: {$x['tabel']} id[$ids] -> '{$x['kd_lama']}'\n";
    }
    qq("DELETE FROM ns_karantina WHERE fase=1");
    echo "Migration down executed successfully!\n";
    exit(0);
}

if ($arah === 'up') {
    qq("CREATE TABLE IF NOT EXISTS ns_karantina (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fase TINYINT NOT NULL DEFAULT 1,
        tabel VARCHAR(64) NOT NULL,
        no_service VARCHAR(50) NOT NULL,
        kd_cabang_lama VARCHAR(10) NOT NULL DEFAULT '',
        kandidat VARCHAR(255) NULL,
        jumlah_baris INT NOT NULL DEFAULT 0,
        alasan VARCHAR(255) NOT NULL,
        status ENUM('open','resolved') NOT NULL DEFAULT 'open',
        keterangan VARCHAR(255) NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_ns_karantina (tabel, no_service, kd_cabang_lama),
        KEY idx_ns_karantina_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
    qq("CREATE TABLE IF NOT EXISTS ns_koreksi_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fase TINYINT NOT NULL DEFAULT 1,
        tabel VARCHAR(64) NOT NULL,
        no_service VARCHAR(50) NOT NULL,
        kd_lama VARCHAR(10) NULL,
        kd_baru VARCHAR(10) NOT NULL,
        row_ids TEXT NOT NULL,
        jumlah_baris INT NOT NULL,
        dibatalkan TINYINT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
}

$nIsi = 0; $nKar = 0;
foreach ($tabel as $t => $pk) {
    $r = qq("SELECT a.no_service, a.kd_cabang, COUNT(*) n" . ($pk ? ", GROUP_CONCAT(a.`$pk`) ids" : ", NULL ids") . ",
               (SELECT GROUP_CONCAT(DISTINCT s.kd_cabang) FROM tblservice s WHERE s.no_service = a.no_service) kandidat
             FROM `$t` a
             WHERE a.kd_cabang IS NULL OR a.kd_cabang = ''
                OR NOT EXISTS (SELECT 1 FROM tblservice s WHERE s.no_service = a.no_service AND s.kd_cabang = a.kd_cabang)
             GROUP BY a.no_service, a.kd_cabang");
    while ($x = mysqli_fetch_assoc($r)) {
        $kosong = ($x['kd_cabang'] === null || $x['kd_cabang'] === '');
        $kand = $x['kandidat'] === null ? [] : explode(',', $x['kandidat']);
        if ($kosong && count($kand) === 1 && $pk) {
            echo sprintf("ISI   %-24s %-16s '%s' -> %s (%d baris)\n", $t, $x['no_service'], $x['kd_cabang'], $kand[0], $x['n']);
            $nIsi++;
            if ($arah === 'up') {
                $ids = implode(',', array_map('intval', explode(',', $x['ids'])));
                qq("INSERT INTO ns_koreksi_log (fase,tabel,no_service,kd_lama,kd_baru,row_ids,jumlah_baris)
                    VALUES (1,'$t','" . esc($x['no_service']) . "'," . ($x['kd_cabang'] === null ? "NULL" : "'" . esc($x['kd_cabang']) . "'") . ",'" . esc($kand[0]) . "','$ids'," . (int)$x['n'] . ")");
                qq("UPDATE `$t` SET kd_cabang='" . esc($kand[0]) . "' WHERE `$pk` IN ($ids) AND (kd_cabang IS NULL OR kd_cabang='')");
            }
        } else {
            $alasan = $kosong
                ? (count($kand) === 0 ? 'kd_cabang kosong, tidak ada induk di tblservice' : 'kd_cabang kosong, induk ada di >1 cabang')
                : (count($kand) === 0 ? 'tidak ada induk di tblservice (no_service yatim)' : 'pasangan (no_service,kd_cabang) tidak ada induk; no_service ada di cabang lain');
            echo sprintf("KARAN %-24s %-16s '%s' kandidat[%s] (%d baris) : %s\n", $t, $x['no_service'], $x['kd_cabang'], $x['kandidat'], $x['n'], $alasan);
            $nKar++;
            if ($arah === 'up') {
                qq("INSERT IGNORE INTO ns_karantina (fase,tabel,no_service,kd_cabang_lama,kandidat,jumlah_baris,alasan)
                    VALUES (1,'$t','" . esc($x['no_service']) . "','" . esc($x['kd_cabang']) . "'," . ($x['kandidat'] === null ? "NULL" : "'" . esc($x['kandidat']) . "'") . "," . (int)$x['n'] . ",'" . esc($alasan) . "')");
            }
        }
    }
}
echo "\nRingkas: isi=$nIsi grup, karantina=$nKar grup. Mode: $arah\n";
if ($arah === 'up') echo "Migration up executed successfully!\n";
