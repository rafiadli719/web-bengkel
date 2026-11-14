<?php
// Fase 6 rencana no_service: foreign key gabungan (no_service, kd_cabang) -> tblservice(no_service, kd_cabang).
// Cakupan: 13 tabel anak OPERASIONAL yang hanya ditulis aplikasi web (bukan sinkronisasi Access, yang urutan
// tulis induk/anaknya tidak terjamin -> tblservis_barang/jasa/advisor sengaja TIDAK diberi FK).
// Tabel log audit (tb_log_*, *_log, promo_usage_log) sengaja tanpa FK agar riwayat tetap ada bila servis dihapus.
// Syarat FK: charset/collation kolom sama dengan induk -> no_service anak yang utf8mb4 diubah ke latin1
// (nilai berupa kode ASCII 'SV...'); definisi asli disimpan di ns_fk_log untuk 'down'.
// Baris dengan kd_cabang NULL (karantina) tidak dicek FK (MATCH SIMPLE) -> tetap aman.
// ON DELETE/UPDATE CASCADE: anak operasional tak bermakna tanpa servisnya (servis-delete.php tetap berfungsi).
// Pakai: php 2026-11-14_run_no_service_fase6_foreign_key.php [dry|up|down]   (default dry; up idempotent)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? 'dry';
if (!in_array($arah, ['dry', 'up', 'down'], true)) { echo "Argumen: dry|up|down\n"; exit(1); }

$tabel = ['master_kedatangan_pelanggan','tb_antrian_servis','tb_mekanik_progress','tb_progress_mekanik','tbservis_keluhan',
    'tbservis_keluhan_status','tbservis_keluhan_tracking','tbservis_penawaran_jasa','tbservis_penawaran_part',
    'tbservis_pending_items','tbservis_pengerjaan','tbservis_temuan','tbservis_workorder'];

function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function esc($v) { global $koneksi; return mysqli_real_escape_string($koneksi, (string)$v); }
function fkAda($t) { return mysqli_num_rows(qq("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$t' AND CONSTRAINT_NAME='fk_ns_$t' AND CONSTRAINT_TYPE='FOREIGN KEY'")) > 0; }

qq("CREATE TABLE IF NOT EXISTS ns_fk_log (
    tabel VARCHAR(64) NOT NULL PRIMARY KEY, col_ddl_asli VARCHAR(255) NOT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1");

if ($arah === 'down') {
    foreach ($tabel as $t) {
        if (fkAda($t)) { qq("ALTER TABLE `$t` DROP FOREIGN KEY `fk_ns_$t`"); echo "Lepas FK: $t\n"; }
        $r = qq("SELECT col_ddl_asli FROM ns_fk_log WHERE tabel='$t'");
        if ($x = mysqli_fetch_assoc($r)) { qq("ALTER TABLE `$t` MODIFY no_service {$x['col_ddl_asli']}"); qq("DELETE FROM ns_fk_log WHERE tabel='$t'"); echo "Pulihkan kolom: $t\n"; }
    }
    echo "Migration down executed successfully!\n"; exit(0);
}

$p = mysqli_fetch_assoc(qq("SELECT CHARACTER_SET_NAME cs, COLLATION_NAME co FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='tblservice' AND COLUMN_NAME='no_service'"));
$nOk = 0;
foreach ($tabel as $t) {
    if (fkAda($t)) { echo sprintf("%-30s FK sudah ada, lewati\n", $t); $nOk++; continue; }
    $yatim = mysqli_fetch_row(qq("SELECT COUNT(*) FROM `$t` a WHERE a.kd_cabang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM tblservice s WHERE s.no_service=a.no_service AND s.kd_cabang=a.kd_cabang)"))[0];
    if ($yatim > 0) { echo sprintf("%-30s DILEWATI: %d baris yatim ber-kd_cabang (selesaikan dulu)\n", $t, $yatim); continue; }
    $c = mysqli_fetch_assoc(qq("SELECT COLUMN_TYPE ty, CHARACTER_SET_NAME cs, COLLATION_NAME co, IS_NULLABLE n, COLUMN_DEFAULT d FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' AND COLUMN_NAME='no_service'"));
    $perluUbah = ($c['cs'] !== $p['cs'] || $c['co'] !== $p['co']);
    echo sprintf("%-30s ubah_charset=%s\n", $t, $perluUbah ? "ya ({$c['cs']}/{$c['co']} -> {$p['cs']}/{$p['co']})" : 'tidak');
    if ($arah !== 'up') continue;
    if ($perluUbah) {
        $asli = "{$c['ty']} CHARACTER SET {$c['cs']} COLLATE {$c['co']} " . ($c['n'] === 'NO' ? 'NOT NULL' : 'NULL') . ($c['d'] !== null ? " DEFAULT '" . esc($c['d']) . "'" : '');
        qq("INSERT IGNORE INTO ns_fk_log (tabel, col_ddl_asli) VALUES ('$t','" . esc($asli) . "')");
        qq("ALTER TABLE `$t` MODIFY no_service {$c['ty']} CHARACTER SET {$p['cs']} COLLATE {$p['co']} " . ($c['n'] === 'NO' ? 'NOT NULL' : 'NULL') . ($c['d'] !== null ? " DEFAULT '" . esc($c['d']) . "'" : ''));
    }
    qq("ALTER TABLE `$t` ADD CONSTRAINT `fk_ns_$t` FOREIGN KEY (no_service, kd_cabang) REFERENCES tblservice (no_service, kd_cabang) ON DELETE CASCADE ON UPDATE CASCADE");
    echo "    FK dipasang\n"; $nOk++;
}
echo "\nRingkas: FK aktif/dipasang = $nOk dari " . count($tabel) . " tabel. Mode: $arah\n";
if ($arah === 'up') echo "Migration up executed successfully!\n";
