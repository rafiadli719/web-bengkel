<?php
// Fase K bagian 1: kunci gabungan PO (no_order, kd_cabang).
// Alasan: nomor PO Access bentrok lintas cabang (PS26000000007 ada di 5 cabang center).
// up   : (a) backup tabel terkait (bak_fk_*), (b) PO web asli (bukan hasil reload center) diganti seri "PW",
//        (c) tblpo_approval_log.kd_cabang, (d) PK tblorder_header -> (no_order, kd_cabang),
//        (e) FK tbldelivery_order_header & tblpo_approval_log ke kunci gabungan.
// down : kembalikan PK tunggal + FK lama (gagal bila sudah ada no_order dobel lintas cabang). Nomor PW tidak dikembalikan.
// Pakai: php 2026-11-16_run_fase_k_po_composite.php [up|down]
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? '';
if (!in_array($arah, ['up', 'down'], true)) { echo "Argumen: up|down\n"; exit(1); }
function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n  $sql\n"; exit(1); } return $r; }
function satu($sql) { $r = qq($sql); $x = mysqli_fetch_row($r); return $x ? $x[0] : null; }
function pk_kolom($t) {
    $r = qq("SHOW INDEX FROM `$t` WHERE Key_name = 'PRIMARY'"); $k = [];
    while ($x = mysqli_fetch_assoc($r)) { $k[(int)$x['Seq_in_index']] = $x['Column_name']; }
    ksort($k); return implode(',', $k);
}
function fk_ada($t, $n) { return (int)satu("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' AND CONSTRAINT_NAME='$n' AND CONSTRAINT_TYPE='FOREIGN KEY'") > 0; }
function kolom_ada($t, $c) { return mysqli_num_rows(qq("SHOW COLUMNS FROM `$t` LIKE '$c'")) > 0; }
function index_ada($t, $i) { return mysqli_num_rows(qq("SHOW INDEX FROM `$t` WHERE Key_name = '$i'")) > 0; }

if ($arah === 'up') {
    if (pk_kolom('tblorder_header') === 'no_order,kd_cabang') { echo "Sudah up (PK gabungan). Selesai.\n"; exit(0); }
    // sanity: DO.kd_cabang harus = cabang PO-nya, kalau tidak FK gabungan akan gagal
    $bad = (int)satu("SELECT COUNT(*) FROM tbldelivery_order_header d JOIN tblorder_header h ON h.no_order=d.no_po WHERE h.kd_cabang<>d.kd_cabang");
    if ($bad > 0) { echo "ABORT: $bad DO dengan kd_cabang beda dari PO-nya\n"; exit(1); }

    foreach (['tblorder_header', 'tblorder_detail', 'tbldelivery_order_header', 'tblpo_approval_log'] as $t) {
        $b = "bak_fk_" . $t;
        if (mysqli_num_rows(qq("SHOW TABLES LIKE '$b'")) === 0) { qq("CREATE TABLE `$b` AS SELECT * FROM `$t`"); echo "Backup $b\n"; }
    }

    // (b) PO web asli -> seri PW. "Web asli" = tidak ada di center untuk cabang yang sama, ATAU dibuat user uji.
    // Daftar eksplisit dari hasil audit 2026-11-16 (PST x4 + PO uji e2e PESALAKAN).
    $rename = [
        ['PS25000000001', 'PST'], ['PS25000000003', 'PST'], ['PS25000000005', 'PST'], ['PS26000000007', 'PST'],
        ['PS26000000804', 'PESALAKAN'],
    ];
    qq("ALTER TABLE tbldelivery_order_header DROP FOREIGN KEY fk_do_po");
    qq("ALTER TABLE tblpo_approval_log DROP FOREIGN KEY tblpo_approval_log_ibfk_1");
    $seq = (int)satu("SELECT COALESCE(MAX(CAST(SUBSTRING(no_order,5) AS UNSIGNED)),0) FROM tblorder_header WHERE no_order LIKE 'PW%'");
    foreach ($rename as [$lama, $cab]) {
        if ((int)satu("SELECT COUNT(*) FROM tblorder_header WHERE no_order='$lama' AND kd_cabang='$cab'") === 0) { echo "  lewati $lama/$cab (tidak ada)\n"; continue; }
        $seq++;
        $baru = 'PW' . substr(date('Y'), 2, 2) . sprintf('%09d', $seq);
        qq("UPDATE tblorder_header SET no_order='$baru' WHERE no_order='$lama' AND kd_cabang='$cab'");
        qq("UPDATE tblorder_detail SET no_order='$baru' WHERE no_order='$lama' AND kd_cabang='$cab'");
        qq("UPDATE tbldelivery_order_header SET no_po='$baru' WHERE no_po='$lama' AND kd_cabang='$cab'");
        // approval log belum punya kd_cabang: hanya PO unik per nomor saat ini (sebelum PK gabungan), aman
        qq("UPDATE tblpo_approval_log SET no_po='$baru' WHERE no_po='$lama'");
        echo "  $lama ($cab) -> $baru\n";
    }

    // (c) kd_cabang log approval
    if (!kolom_ada('tblpo_approval_log', 'kd_cabang')) {
        qq("ALTER TABLE tblpo_approval_log ADD COLUMN kd_cabang VARCHAR(10) NOT NULL DEFAULT '' AFTER no_po");
    }
    qq("UPDATE tblpo_approval_log l JOIN tblorder_header h ON h.no_order=l.no_po SET l.kd_cabang=h.kd_cabang WHERE l.kd_cabang=''");
    $kosong = (int)satu("SELECT COUNT(*) FROM tblpo_approval_log WHERE kd_cabang=''");
    if ($kosong > 0) { echo "ABORT: $kosong log approval tanpa PO induk\n"; exit(1); }

    // (d)(e) PK + FK gabungan
    qq("ALTER TABLE tblorder_header DROP PRIMARY KEY, ADD PRIMARY KEY (no_order, kd_cabang)");
    if (!index_ada('tbldelivery_order_header', 'idx_do_po_cab')) { qq("ALTER TABLE tbldelivery_order_header ADD INDEX idx_do_po_cab (no_po, kd_cabang)"); }
    if (!index_ada('tblpo_approval_log', 'idx_apv_po_cab')) { qq("ALTER TABLE tblpo_approval_log ADD INDEX idx_apv_po_cab (no_po, kd_cabang)"); }
    qq("ALTER TABLE tbldelivery_order_header ADD CONSTRAINT fk_do_po FOREIGN KEY (no_po, kd_cabang) REFERENCES tblorder_header (no_order, kd_cabang)");
    qq("ALTER TABLE tblpo_approval_log ADD CONSTRAINT tblpo_approval_log_ibfk_1 FOREIGN KEY (no_po, kd_cabang) REFERENCES tblorder_header (no_order, kd_cabang) ON DELETE CASCADE");
    echo "UP selesai. PK tblorder_header = " . pk_kolom('tblorder_header') . "\n";
} else {
    if (pk_kolom('tblorder_header') !== 'no_order,kd_cabang') { echo "Sudah down. Selesai.\n"; exit(0); }
    $dob = (int)satu("SELECT COUNT(*) FROM (SELECT no_order FROM tblorder_header GROUP BY no_order HAVING COUNT(*)>1) x");
    if ($dob > 0) { echo "ABORT: $dob no_order dobel lintas cabang, down tidak mungkin\n"; exit(1); }
    qq("ALTER TABLE tbldelivery_order_header DROP FOREIGN KEY fk_do_po");
    qq("ALTER TABLE tblpo_approval_log DROP FOREIGN KEY tblpo_approval_log_ibfk_1");
    qq("ALTER TABLE tblorder_header DROP PRIMARY KEY, ADD PRIMARY KEY (no_order)");
    qq("ALTER TABLE tbldelivery_order_header ADD CONSTRAINT fk_do_po FOREIGN KEY (no_po) REFERENCES tblorder_header (no_order)");
    qq("ALTER TABLE tblpo_approval_log ADD CONSTRAINT tblpo_approval_log_ibfk_1 FOREIGN KEY (no_po) REFERENCES tblorder_header (no_order) ON DELETE CASCADE");
    echo "DOWN selesai.\n";
}
