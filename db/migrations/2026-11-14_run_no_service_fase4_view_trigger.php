<?php
// Fase 4 rencana no_service: view dan trigger memakai kunci gabungan (no_service, kd_cabang).
//  - 8 view: setiap join "x.no_service = y.no_service" ditambah "x.kd_cabang = y.kd_cabang";
//    kolom kd_cabang ditambahkan ke keluaran view (kecuali ringkasan per pelanggan).
//  - trg_after_service_bayar: cek kunjungan, hitung item, dan INSERT master_kedatangan_pelanggan memakai kd_cabang.
// DDL asli disimpan di ns_ddl_backup (sekali) sehingga 'down' memulihkan persis.
// Pakai: php 2026-11-14_run_no_service_fase4_view_trigger.php [dry|up|down]   (default dry)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
$arah = $argv[1] ?? 'dry';
if (!in_array($arah, ['dry', 'up', 'down'], true)) { echo "Argumen: dry|up|down\n"; exit(1); }

function qq($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); if (!$r) { echo "SQL error: " . mysqli_error($koneksi) . "\n"; exit(1); } return $r; }
function esc($v) { global $koneksi; return mysqli_real_escape_string($koneksi, (string)$v); }
function tanpaDefiner($ddl) { return preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s*/i', '', $ddl); }

// alias tabel utama yang kd_cabang-nya ditambahkan ke keluaran view (null = jangan tambah)
$views = [
    'view_penawaran_part_lengkap' => 'spp', 'view_ringkasan_kedatangan_pelanggan' => null, 'view_riwayat_kedatangan_detail' => 'k',
    'view_servis_keluhan_lengkap' => 'k', 'view_servis_status_summary' => 's', 'view_servis_temuan_lengkap' => 't',
    'view_servis_workorder_lengkap' => 'wo', 'view_temuan_penawaran_summary' => 't',
];

qq("CREATE TABLE IF NOT EXISTS ns_ddl_backup (
    nama VARCHAR(128) NOT NULL, tipe ENUM('VIEW','TRIGGER') NOT NULL, ddl MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (nama, tipe)) ENGINE=InnoDB DEFAULT CHARSET=latin1");

function ambilDdl($nama, $tipe) {
    if ($tipe === 'VIEW') { $r = mysqli_fetch_assoc(qq("SHOW CREATE VIEW `$nama`")); return $r['Create View']; }
    $r = mysqli_fetch_assoc(qq("SHOW CREATE TRIGGER `$nama`")); return $r['SQL Original Statement'];
}
function simpanAsli($nama, $tipe) {
    $ada = mysqli_fetch_row(qq("SELECT COUNT(*) FROM ns_ddl_backup WHERE nama='" . esc($nama) . "' AND tipe='$tipe'"));
    if ($ada[0] == 0) qq("INSERT INTO ns_ddl_backup (nama,tipe,ddl) VALUES ('" . esc($nama) . "','$tipe','" . esc(ambilDdl($nama, $tipe)) . "')");
}

if ($arah === 'down') {
    $r = qq("SELECT nama, tipe, ddl FROM ns_ddl_backup");
    while ($x = mysqli_fetch_assoc($r)) {
        if ($x['tipe'] === 'VIEW') {
            qq(preg_replace('/^CREATE\s+/i', 'CREATE OR REPLACE ', tanpaDefiner($x['ddl'])));
        } else {
            qq("DROP TRIGGER IF EXISTS `{$x['nama']}`"); qq(tanpaDefiner($x['ddl']));
        }
        echo "Pulih: {$x['tipe']} {$x['nama']}\n";
    }
    echo "Migration down executed successfully!\n"; exit(0);
}

// ---- transformasi view ----
function ubahView($nama, $alias, $ddl) {
    $ddl = tanpaDefiner($ddl);
    if (!preg_match('/\sAS\s+(select\s.*)$/is', $ddl, $m)) { echo "GAGAL parse $nama\n"; exit(1); }
    $sel = $m[1];
    // join antar tabel: (a.no_service = b.no_service)
    $sel = preg_replace('/\(\(`(\w+)`\.`no_service` = `(\w+)`\.`no_service`\)\)/', '(((`$1`.`no_service` = `$2`.`no_service`) and (`$1`.`kd_cabang` = `$2`.`kd_cabang`)))', $sel, -1, $nJoin);
    if ($nama === 'view_ringkasan_kedatangan_pelanggan') {
        $sel = str_replace('count(distinct `k`.`no_service`)', "count(distinct concat(`k`.`no_service`,'|',coalesce(`k`.`kd_cabang`,'')))", $sel, $nJoin2);
        if ($nJoin2 !== 1) { echo "GAGAL ringkasan (count distinct)\n"; exit(1); }
    } else {
        // tambah kolom kd_cabang sebelum FROM utama (kemunculan terakhir ' from ')
        $p = strrpos($sel, ' from ');
        $sel = substr($sel, 0, $p) . ",`$alias`.`kd_cabang` AS `kd_cabang`" . substr($sel, $p);
        if ($nama === 'view_servis_status_summary') {
            $sel = str_replace('group by `s`.`no_service`,', 'group by `s`.`no_service`,`s`.`kd_cabang`,', $sel, $nG);
            if ($nG !== 1) { echo "GAGAL status_summary (group by)\n"; exit(1); }
        }
    }
    return [$sel, $nJoin];
}

foreach ($views as $v => $alias) {
    $asli = ambilDdl($v, 'VIEW');
    if ($arah === 'up') simpanAsli($v, 'VIEW');
    // selalu transformasi dari DDL ASLI (idempotent)
    $dasar = $arah === 'up'
        ? mysqli_fetch_row(qq("SELECT ddl FROM ns_ddl_backup WHERE nama='" . esc($v) . "' AND tipe='VIEW'"))[0] : $asli;
    list($sel, $nJoin) = ubahView($v, $alias, $dasar);
    $sebelum = mysqli_fetch_row(qq("SELECT COUNT(*) FROM `$v`"))[0];
    echo sprintf("%-40s join diubah=%d kolom_kd_cabang=%s\n", $v, $nJoin, $alias ? 'ya' : 'tidak');
    if ($arah === 'up') {
        qq("CREATE OR REPLACE SQL SECURITY DEFINER VIEW `$v` AS $sel");
        $sesudah = mysqli_fetch_row(qq("SELECT COUNT(*) FROM `$v`"))[0];
        echo "    baris: $sebelum -> $sesudah\n";
    }
}

// ---- trigger ----
$tg = 'trg_after_service_bayar';
if ($arah === 'up') simpanAsli($tg, 'TRIGGER');
$dasar = $arah === 'up' ? mysqli_fetch_row(qq("SELECT ddl FROM ns_ddl_backup WHERE nama='$tg' AND tipe='TRIGGER'"))[0] : ambilDdl($tg, 'TRIGGER');
$t = tanpaDefiner($dasar);
$subs = [
    ['/(FROM master_kedatangan_pelanggan\s+WHERE no_service = NEW\.no_service)(\s*\)\s*THEN)/', '$1 AND kd_cabang = NEW.kd_cabang$2'],
    ['/\(SELECT COUNT\(\*\) FROM tblservis_barang WHERE no_service = NEW\.no_service\)/', '(SELECT COUNT(*) FROM tblservis_barang WHERE no_service = NEW.no_service AND kd_cabang = NEW.kd_cabang)'],
    ['/\(SELECT COUNT\(\*\) FROM tblservis_jasa WHERE no_service = NEW\.no_service\)/', '(SELECT COUNT(*) FROM tblservis_jasa WHERE no_service = NEW.no_service AND kd_cabang = NEW.kd_cabang)'],
    ['/(INSERT INTO master_kedatangan_pelanggan\s*\(\s*no_pelanggan,\s*no_service,)/', '$1 kd_cabang,'],
    ['/(\)\s*VALUES\s*\(\s*NEW\.no_pelanggan,\s*NEW\.no_service,)(\s*v_kedatangan_ke)/', '$1 NEW.kd_cabang,$2'],
];
foreach ($subs as $i => $s) {
    $t = preg_replace($s[0], $s[1], $t, -1, $c);
    if ($c !== 1) { echo "GAGAL trigger substitusi #$i (cocok=$c)\n"; exit(1); }
}
echo "trigger $tg: 5 substitusi OK\n";
if ($arah === 'up') {
    qq("DROP TRIGGER IF EXISTS `$tg`");
    if (!mysqli_query($koneksi, $t)) {
        echo "SQL error trigger: " . mysqli_error($koneksi) . "\nMemulihkan trigger asli...\n";
        qq(tanpaDefiner($dasar)); // jangan sampai trigger hilang
        exit(1);
    }
    echo "Migration up executed successfully!\n";
}
else echo "Mode dry: tidak ada perubahan.\n";
