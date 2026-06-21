<?php
/**
 * Refresh statistik_pelanggan: hitung rata_jarak_kunjungan + estimasi_datang_berikutnya
 * untuk semua pelanggan yang sudah ada data transaksi bayar.
 *
 * Formula dari algoritma FITMOTOR APP.mdb (Access):
 *   rata_jarak = ROUND((tgl_terakhir - tgl_pertama) / (jumlah - 1))
 *   interval   = IF(rata_jarak <= 45, 30, 60)
 *   estimasi   = tgl_terakhir + interval * CEIL((HARI_INI - tgl_terakhir) / interval)
 *
 * URL: http://localhost/web-bengkel/aplikasi/aplikasi/app/refresh_statistik_estimasi.php
 * Dry run: tambah ?dry=1
 * Test limit: tambah ?limit=100
 */

session_start();
if(empty($_SESSION['_iduser'])) {
    if(PHP_SAPI !== 'cli') {
        header("location:../index.php");
        exit;
    }
}

include "../config/koneksi.php";

$dry_run    = isset($_GET['dry']) && $_GET['dry'] === '1';
$limit_mode = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 0;

$start_time = microtime(true);
$updated    = 0;
$skipped    = 0;
$errors     = 0;
$today      = date('Y-m-d');

if(PHP_SAPI !== 'cli') {
    echo "<!DOCTYPE html><html><head><meta charset='utf-8'>";
    echo "<title>Refresh Statistik Estimasi</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#fff;}";
    echo ".ok{color:#27ae60;} .warn{color:#e67e22;} .err{color:#e74c3c;}";
    echo "h2{color:#2c3e50;} pre{background:#f8f8f8;padding:12px;border-radius:4px;border:1px solid #ddd;}";
    echo ".badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:12px;}";
    echo ".badge-ok{background:#d4edda;color:#155724;} .badge-warn{background:#fff3cd;color:#856404;}";
    echo "</style></head><body>";
    echo "<h2>&#128260; Refresh Statistik Estimasi Kunjungan Pelanggan</h2>";
    if($dry_run) echo "<div class='warn'><strong>&#9888; MODE DRY RUN</strong> &mdash; tidak ada yang diubah ke database</div><br>";
    ob_flush(); flush();
}

function logLine($msg, $type='') {
    if(PHP_SAPI === 'cli') {
        echo $msg."\n";
    } else {
        $cls = $type ? " class='$type'" : '';
        echo "<div$cls>" . htmlspecialchars($msg) . "</div>";
        ob_flush(); flush();
    }
}

// Ambil semua no_pelanggan yang punya transaksi bayar
$sql_limit_str = $limit_mode ? "LIMIT $limit_mode" : "";
$sql_pel = mysqli_query($koneksi,
    "SELECT DISTINCT no_pelanggan
     FROM tblservice
     WHERE status_servis = 'bayar'
       AND no_pelanggan IS NOT NULL
       AND no_pelanggan != ''
     ORDER BY no_pelanggan
     $sql_limit_str");

$total_pel = mysqli_num_rows($sql_pel);
logLine("Total pelanggan dengan transaksi bayar: $total_pel");
if($limit_mode) logLine("  (dibatasi $limit_mode untuk test)", 'warn');
logLine("Memproses...");
if($dry_run) logLine("  (dry run — hanya tampilkan hasil kalkulasi)");
logLine("---");

while($row_pel = mysqli_fetch_assoc($sql_pel)) {
    $no_pel     = $row_pel['no_pelanggan'];
    $no_pel_esc = mysqli_real_escape_string($koneksi, $no_pel);

    // Hitung statistik dari tblservice
    $sql_stat = mysqli_query($koneksi,
        "SELECT
            COUNT(*) AS jml,
            COALESCE(SUM(IFNULL(total_akhir, IFNULL(total_grand, IFNULL(`total`, 0)))), 0) AS nominal,
            MIN(tanggal) AS tgl_pertama,
            MAX(tanggal) AS tgl_terakhir,
            COUNT(DISTINCT no_polisi) AS jml_motor
         FROM tblservice
         WHERE no_pelanggan = '$no_pel_esc'
           AND status_servis = 'bayar'");

    if(!$sql_stat) { $errors++; continue; }
    $stat = mysqli_fetch_assoc($sql_stat);

    $jml          = (int)$stat['jml'];
    $nominal      = (float)$stat['nominal'];
    $tgl_pertama  = $stat['tgl_pertama'];
    $tgl_terakhir = $stat['tgl_terakhir'];
    $jml_motor    = (int)$stat['jml_motor'];

    if($jml === 0 || !$tgl_terakhir) { $skipped++; continue; }

    // Rata-rata jarak kunjungan (hari)
    $rata_jarak = 0.0;
    if($jml > 1 && $tgl_pertama && $tgl_terakhir) {
        $diff_hari  = max(0, (strtotime($tgl_terakhir) - strtotime($tgl_pertama)) / 86400);
        $rata_jarak = round($diff_hari / ($jml - 1), 2);
    }

    // Interval tier: <=45 hari → 30, >45 hari → 60
    $interval = ($rata_jarak > 0 && $rata_jarak <= 45) ? 30 : 60;

    // Estimasi kunjungan berikutnya (formula Access)
    $lama_float = (strtotime($today) - strtotime($tgl_terakhir)) / 86400;
    $lama_int   = (int)max(0, $lama_float);
    if($lama_float >= 0) {
        $multiplier = (int)ceil(max(1, $lama_float) / $interval);
        $estimasi   = date('Y-m-d', strtotime($tgl_terakhir) + ($interval * $multiplier * 86400));
    } else {
        $estimasi   = date('Y-m-d', strtotime($tgl_terakhir) + ($interval * 86400));
    }

    // Tier member
    if($nominal >= 10000000)     $tier = 'Platinum';
    elseif($nominal >= 5000000)  $tier = 'Gold';
    elseif($nominal >= 2000000)  $tier = 'Silver';
    else                         $tier = 'Bronze';

    $rata_rata = $jml > 0 ? round($nominal / $jml, 2) : 0;

    if($dry_run) {
        logLine("  DRY | $no_pel | kunjungan=$jml | rata={$rata_jarak}hr | interval={$interval}hr | estimasi=$estimasi | tier=$tier", 'warn');
        $updated++;
        continue;
    }

    $res = mysqli_query($koneksi,
        "INSERT INTO statistik_pelanggan
            (no_pelanggan, total_transaksi, total_nominal, jumlah_kunjungan, kedatangan_terakhir,
             rata_rata_transaksi, status_member, tanggal_pertama_transaksi, tanggal_terakhir_transaksi,
             total_motor, lama_tidak_datang, rata_jarak_kunjungan, estimasi_datang_berikutnya)
         VALUES
            ('$no_pel_esc', $jml, $nominal, $jml, $jml,
             $rata_rata, '$tier', '$tgl_pertama', '$tgl_terakhir',
             $jml_motor, $lama_int, $rata_jarak, '$estimasi')
         ON DUPLICATE KEY UPDATE
            total_transaksi              = $jml,
            total_nominal                = $nominal,
            jumlah_kunjungan             = $jml,
            kedatangan_terakhir          = $jml,
            rata_rata_transaksi          = $rata_rata,
            status_member                = '$tier',
            tanggal_pertama_transaksi    = '$tgl_pertama',
            tanggal_terakhir_transaksi   = '$tgl_terakhir',
            total_motor                  = $jml_motor,
            lama_tidak_datang            = $lama_int,
            rata_jarak_kunjungan         = $rata_jarak,
            estimasi_datang_berikutnya   = '$estimasi',
            updated_at                   = NOW()");

    if($res) {
        $updated++;
    } else {
        $errors++;
        logLine("  ERROR $no_pel: ".mysqli_error($koneksi), 'err');
    }
}

$elapsed = round(microtime(true) - $start_time, 2);

logLine("---");
logLine("Selesai dalam {$elapsed} detik", 'ok');
logLine("Diproses : $updated / $total_pel", 'ok');
logLine("Dilewati : $skipped");
if($errors > 0) logLine("Error    : $errors", 'err');

if(PHP_SAPI !== 'cli') {
    echo "<br>";

    // Tampilkan hasil verifikasi
    $r = mysqli_query($koneksi,
        "SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN estimasi_datang_berikutnya IS NOT NULL AND estimasi_datang_berikutnya != '0000-00-00' THEN 1 ELSE 0 END) AS ada_estimasi,
            SUM(CASE WHEN rata_jarak_kunjungan > 0 THEN 1 ELSE 0 END) AS ada_interval,
            SUM(CASE WHEN jumlah_kunjungan > 1 THEN 1 ELSE 0 END) AS lebih_1x
         FROM statistik_pelanggan");

    if($r) {
        $chk = mysqli_fetch_assoc($r);
        echo "<pre>";
        echo "=== Verifikasi statistik_pelanggan ===\n";
        echo "Total record           : " . number_format($chk['total']) . "\n";
        echo "Ada estimasi berikutnya: " . number_format($chk['ada_estimasi']) . "\n";
        echo "Ada interval rata-rata : " . number_format($chk['ada_interval']) . "\n";
        echo "Pernah datang lebih 1x : " . number_format($chk['lebih_1x']) . "\n";
        echo "</pre>";
    }

    echo "<div style='margin-top:15px;'>";
    echo "<a href='servis-carinopol.php' style='margin-right:10px;'>&#8592; Cari Nopol</a> &nbsp;|&nbsp; ";
    echo "<a href='lap_rekap_kunjungan.php' style='margin-left:10px;'>Rekap Kunjungan &#8594;</a>";
    echo "</div>";

    echo "</body></html>";
}
