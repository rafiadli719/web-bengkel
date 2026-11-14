<?php
// Audit identitas servis: kunci gabungan (no_service, kd_cabang). READ-ONLY.
// Pakai: php scripts/audit_no_service.php [--json] [--kode [file.php ...]] [--monitor]
//   (tanpa opsi) audit data: tabel anak, view, trigger, duplikat induk.
//   --kode  : tambah pemeriksaan kode. Aturan K1: JOIN tblservice ON ... no_service tanpa kd_cabang.
//             Aturan K2: UPDATE/DELETE tabel anak servis dengan WHERE no_service tanpa kd_cabang dan tanpa id.
//             Bila diberi daftar file, hanya file itu yang dipindai (dipakai pre-commit); selain itu seluruh app/.
//   --monitor : cek harian ringkas. Gagal bila: duplikat (no_service,kd_cabang) di induk, view tanpa kd_cabang,
//             atau ada baris anak ber-kd_cabang kosong yang TIDAK tercatat di ns_karantina (tak terlacak).
//   --json  : keluaran JSON (untuk dibandingkan sebelum/sesudah).
// Exit 1 bila ada temuan kritis.
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/koneksi.php';
$json = in_array('--json', $argv, true);
$kode = in_array('--kode', $argv, true);
$monitor = in_array('--monitor', $argv, true);
$dbOff = false; // mode --kode murni (mis. pre-commit) tetap jalan tanpa DB
if (!$koneksi) {
    if ($kode && !$monitor) { $dbOff = true; fwrite(STDERR, "(DB tidak terjangkau: hanya pemeriksaan kode)\n"); }
    else { fwrite(STDERR, "Koneksi gagal\n"); exit(2); }
}
$fileArgs = [];
if ($kode) { $i = array_search('--kode', $argv, true); foreach (array_slice($argv, $i + 1) as $a) { if (substr($a, 0, 2) !== '--') $fileArgs[] = $a; } }

function q($sql) { global $koneksi; $r = mysqli_query($koneksi, $sql); return $r ?: null; }
function scalar($sql) { $r = q($sql); if (!$r) return null; $row = mysqli_fetch_row($r); return $row ? (int)$row[0] : 0; }

$out = ['db' => $dbOff ? '(offline)' : mysqli_fetch_row(q("SELECT DATABASE()"))[0], 'waktu' => date('c'), 'tabel' => [], 'view' => [], 'trigger' => [], 'induk' => [], 'kode' => []];
$kritis = 0;

if (!$dbOff) {
// Induk
$out['induk']['total'] = scalar("SELECT COUNT(*) FROM tblservice");
$out['induk']['no_service_ganda_lintas_cabang'] = scalar("SELECT COUNT(*) FROM (SELECT no_service FROM tblservice GROUP BY no_service HAVING COUNT(DISTINCT kd_cabang)>1) t");
$out['induk']['duplikat_kunci_gabungan'] = scalar("SELECT COUNT(*) FROM (SELECT no_service,kd_cabang FROM tblservice GROUP BY no_service,kd_cabang HAVING COUNT(*)>1) t");
$out['induk']['kd_cabang_kosong'] = scalar("SELECT COUNT(*) FROM tblservice WHERE kd_cabang IS NULL OR kd_cabang=''");
if ($out['induk']['duplikat_kunci_gabungan'] > 0) $kritis++;

// Tabel anak
$rs = q("SELECT DISTINCT c.TABLE_NAME FROM information_schema.COLUMNS c
         JOIN information_schema.TABLES t ON t.TABLE_SCHEMA=c.TABLE_SCHEMA AND t.TABLE_NAME=c.TABLE_NAME AND t.TABLE_TYPE='BASE TABLE'
         WHERE c.TABLE_SCHEMA=DATABASE() AND c.COLUMN_NAME='no_service'
           AND c.TABLE_NAME NOT LIKE 'bak\\_%' AND c.TABLE_NAME NOT LIKE 'ns\\_%' AND c.TABLE_NAME<>'tblservice'
         ORDER BY c.TABLE_NAME");
while ($r = mysqli_fetch_row($rs)) {
    $t = $r[0];
    $punya = scalar("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t' AND COLUMN_NAME='kd_cabang'") > 0;
    $row = ['punya_kd_cabang' => $punya, 'total' => scalar("SELECT COUNT(*) FROM `$t`")];
    $amb = "no_service IN (SELECT no_service FROM tblservice GROUP BY no_service HAVING COUNT(DISTINCT kd_cabang)>1)";
    if ($punya) {
        $row['kosong'] = scalar("SELECT COUNT(*) FROM `$t` WHERE kd_cabang IS NULL OR kd_cabang=''");
        $row['yatim'] = scalar("SELECT COUNT(*) FROM `$t` a WHERE a.kd_cabang IS NOT NULL AND a.kd_cabang<>'' AND NOT EXISTS (SELECT 1 FROM tblservice s WHERE s.no_service=a.no_service AND s.kd_cabang=a.kd_cabang)");
        $row['no_service_tanpa_induk'] = scalar("SELECT COUNT(*) FROM `$t` a WHERE NOT EXISTS (SELECT 1 FROM tblservice s WHERE s.no_service=a.no_service)");
    } else {
        $row['ambigu'] = scalar("SELECT COUNT(*) FROM `$t` WHERE $amb");
        $row['no_service_tanpa_induk'] = scalar("SELECT COUNT(*) FROM `$t` a WHERE NOT EXISTS (SELECT 1 FROM tblservice s WHERE s.no_service=a.no_service)");
    }
    $out['tabel'][$t] = $row;
}

// View yang memakai no_service tanpa kd_cabang
$rs = q("SELECT TABLE_NAME, VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME NOT LIKE 'bak\\_%'");
while ($r = mysqli_fetch_row($rs)) {
    $d = strtolower($r[1]);
    if (strpos($d, 'no_service') === false) continue;
    $out['view'][$r[0]] = (strpos($d, 'kd_cabang') !== false) ? 'ok_ada_kd_cabang' : 'TANPA_kd_cabang';
}
// Trigger
$rs = q("SELECT TRIGGER_NAME, EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND LOWER(ACTION_STATEMENT) LIKE '%no_service%'");
while ($r = mysqli_fetch_row($rs)) $out['trigger'][$r[0]] = $r[1];

// Karantina (bila sudah dibuat Fase 1/2)
if (scalar("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ns_karantina'") > 0)
    $out['karantina'] = scalar("SELECT COUNT(*) FROM ns_karantina WHERE status='open'");

} else { // tanpa DB: daftar tabel anak statis untuk aturan K2
    foreach (['master_kedatangan_pelanggan','promo_usage_log','tb_antrian_servis','tb_log_antrian','tb_log_cancel_servis','tb_mekanik_progress','tb_progress_mekanik','tbservis_keluhan','tbservis_keluhan_status','tbservis_keluhan_tracking','tbservis_penawaran_jasa','tbservis_penawaran_log','tbservis_penawaran_part','tbservis_pending_items','tbservis_pengerjaan','tbservis_temuan','tbservis_temuan_activity_log','tbservis_workorder','tblservis_barang','tblservis_jasa','tblservice_advisor','servis_komisi','tb_dp_servis','tblretur_servis_header','tblretur_servis_detail','tb_approval_diskon'] as $_t) $out['tabel'][$_t] = ['punya_kd_cabang' => true, 'total' => 0];
}
// Pemeriksaan kode (Fase 5)
$tabelAnak = array_keys($out['tabel']);
$tabelAnakServis = array_filter($tabelAnak, function ($t) { return strpos($t, 'stg_') !== 0 && $t !== 'konsolidasi_service' && $t !== 'tblservice_backup'; });
if ($kode) {
    $root = realpath(__DIR__ . '/..');
    $files = [];
    if ($fileArgs) { foreach ($fileArgs as $a) { $p = realpath($a) ?: realpath($root . '/' . $a); if ($p && substr($p, -4) === '.php') $files[] = $p; } }
    else {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/app', FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) { if (substr($f, -4) === '.php') $files[] = (string)$f; }
    }
    $alt = implode('|', array_map('preg_quote', $tabelAnakServis));
    foreach ($files as $f) {
        $p = str_replace('\\', '/', $f);
        if (preg_match('#/(archive|_archive|vendor|node_modules)/#', $p)) continue;
        $src = file_get_contents($f);
        $rel = strpos($p, $root) === 0 ? substr($p, strlen($root) + 1) : $p;
        // K1: JOIN tblservice ON ... no_service ... tanpa kd_cabang dalam klausa ON yang sama
        if (stripos($src, 'tblservice') !== false && preg_match_all('/\bJOIN\s+`?tblservice`?\s+(?:AS\s+)?\w*\s+ON\s+([^;]{0,300}?)(?=\bWHERE\b|\bJOIN\b|\bLEFT\b|\bGROUP\b|\bORDER\b|\bLIMIT\b|["\';]|$)/is', $src, $m)) {
            foreach ($m[1] as $on) {
                if (stripos($on, 'no_service') !== false && stripos($on, 'kd_cabang') === false && strpos($src, 'ns-audit-ignore') === false)
                    $out['kode'][] = "K1 $rel :: JOIN tblservice ON " . trim(preg_replace('/\s+/', ' ', $on));
            }
        }
        // K2: UPDATE/DELETE tabel anak dengan WHERE no_service (tanpa kd_cabang, tanpa kunci id)
        if (preg_match_all('/\b(?:UPDATE|DELETE\s+FROM)\s+`?(' . $alt . ')`?\b([^;]{0,700}?)(?="\s*[;)]|;|$)/is', $src, $m2, PREG_SET_ORDER)) {
            foreach ($m2 as $mm) {
                $stmt = $mm[2];
                if (!preg_match('/\bWHERE\b/i', $stmt)) continue;
                $where = substr($stmt, stripos($stmt, 'WHERE'));
                if (preg_match('/\bno_service\b/i', $where) && !preg_match('/\bkd_cabang\b/i', $where) && !preg_match('/\b(?:id|wo_id|keluhan_id|temuan_id)\s*=/i', $where))
                    $out['kode'][] = "K2 $rel :: {$mm[1]} WHERE " . trim(preg_replace('/\s+/', ' ', substr($where, 5, 120)));
            }
        }
    }
    if ($out['kode']) $kritis++;
}

// Monitor harian
if ($monitor) {
    $out['monitor'] = ['tak_terlacak' => [], 'view_tanpa_kd_cabang' => []];
    $adaKar = scalar("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='ns_karantina'") > 0;
    foreach ($tabelAnakServis as $t) {
        if (empty($out['tabel'][$t]['punya_kd_cabang'])) continue;
        $kar = $adaKar ? "AND NOT EXISTS (SELECT 1 FROM ns_karantina q WHERE q.tabel='$t' AND q.no_service=a.no_service AND q.status='open')" : '';
        $n = scalar("SELECT COUNT(*) FROM `$t` a WHERE (a.kd_cabang IS NULL OR a.kd_cabang='') $kar");
        if ($n > 0) $out['monitor']['tak_terlacak'][$t] = $n;
    }
    foreach ($out['view'] as $v => $st) if ($st !== 'ok_ada_kd_cabang') $out['monitor']['view_tanpa_kd_cabang'][] = $v;
    if ($out['monitor']['tak_terlacak'] || $out['monitor']['view_tanpa_kd_cabang']) $kritis++;
}

if ($json) { echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), "\n"; exit($kritis ? 1 : 0); }

echo "== AUDIT no_service ({$out['db']}) {$out['waktu']} ==\n";
foreach ($out['induk'] as $k => $v) echo sprintf("  induk.%-36s %s\n", $k, $v);
echo "\n-- Tabel anak --\n";
printf("  %-34s %-6s %8s %8s %8s %8s\n", 'tabel', 'kdcab', 'total', 'kosong', 'yatim', 'ambigu');
foreach ($out['tabel'] as $t => $r) {
    printf("  %-34s %-6s %8d %8s %8s %8s\n", $t, $r['punya_kd_cabang'] ? 'ya' : 'TIDAK', $r['total'],
        $r['kosong'] ?? '-', $r['yatim'] ?? ($r['no_service_tanpa_induk']), $r['ambigu'] ?? '-');
}
echo "\n-- View --\n";
foreach ($out['view'] as $v => $s) echo "  $v : $s\n";
echo "\n-- Trigger --\n";
foreach ($out['trigger'] as $tg => $tb) echo "  $tg ($tb)\n";
if (isset($out['karantina'])) echo "\nKarantina open: {$out['karantina']}\n";
if ($kode) { echo "\n-- Kode berisiko (" . count($out['kode']) . ") --\n"; foreach ($out['kode'] as $k) echo "  $k\n"; }
if ($monitor) {
    echo "\n-- Monitor --\n";
    echo "  baris anak ber-kd_cabang kosong TAK terlacak karantina: " . (array_sum($out['monitor']['tak_terlacak']) ?: 0) . "\n";
    foreach ($out['monitor']['tak_terlacak'] as $t => $n) echo "    $t: $n\n";
    echo "  view tanpa kd_cabang: " . count($out['monitor']['view_tanpa_kd_cabang']) . "\n";
    foreach ($out['monitor']['view_tanpa_kd_cabang'] as $v) echo "    $v\n";
    echo "  duplikat kunci gabungan induk: {$out['induk']['duplikat_kunci_gabungan']}\n";
    echo $kritis ? "STATUS: GAGAL\n" : "STATUS: OK\n";
}
exit($kritis ? 1 : 0);
