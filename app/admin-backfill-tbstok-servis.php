<?php
/**
 * ADMIN TOOL: Backfill tbstok tipe='4' untuk data servis migrasi Access
 * Hanya boleh diakses oleh admin. Eksekusi dalam batch 500 service per request.
 * Jalankan berulang kali hingga selesai (progress tampil di layar).
 */
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}
include "../config/koneksi.php";

// Hanya admin
$cek_level = mysqli_query($koneksi, "SELECT user_akses FROM tbuser WHERE id='".intval($_SESSION['_iduser'])."' LIMIT 1");
$lvl = mysqli_fetch_assoc($cek_level);
if (!$lvl || $lvl['user_akses'] !== 'admin') {
    die('Akses ditolak. Hanya admin yang boleh menjalankan script ini.');
}

$batch_size = 500;
$mode = $_GET['run'] ?? '';

// Hitung sisa
$q_sisa = mysqli_query($koneksi, "
    SELECT COUNT(DISTINCT sb.no_service) AS sisa
    FROM tblservis_barang sb
    JOIN tblservice s ON sb.no_service = s.no_service
    WHERE s.status_servis = 'bayar'
    AND NOT EXISTS (SELECT 1 FROM tbstok st WHERE st.no_transaksi = sb.no_service AND st.tipe = '4')
");
$sisa = (int)(mysqli_fetch_assoc($q_sisa)['sisa'] ?? 0);

$q_total = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM tbstok WHERE tipe='4'");
$total_stok = (int)(mysqli_fetch_assoc($q_total)['total'] ?? 0);

if ($mode === 'batch') {
    // Ambil batch no_service yang belum punya tbstok.
    // GROUP BY no_service + MIN() mencegah cross-join duplikat untuk no_service yang muncul
    // di tblservice lebih dari sekali akibat migrasi multi-cabang.
    $q_batch = mysqli_query($koneksi, "
        SELECT sb.no_service,
               MIN(s.tanggal) AS tanggal,
               MIN(s.kd_cabang) AS kd_cabang
        FROM tblservis_barang sb
        JOIN tblservice s ON sb.no_service = s.no_service
        WHERE s.status_servis = 'bayar'
        AND NOT EXISTS (SELECT 1 FROM tbstok st WHERE st.no_transaksi = sb.no_service AND st.tipe = '4')
        GROUP BY sb.no_service
        LIMIT $batch_size
    ");

    $inserted = 0;
    $services_done = 0;
    while ($srv = mysqli_fetch_assoc($q_batch)) {
        $no_srv = mysqli_real_escape_string($koneksi, $srv['no_service']);
        $tgl    = mysqli_real_escape_string($koneksi, $srv['tanggal'] ?: date('Y-m-d'));
        $kd_cab = mysqli_real_escape_string($koneksi, $srv['kd_cabang']);

        $q_items = mysqli_query($koneksi, "SELECT no_item, quantity FROM tblservis_barang WHERE no_service='$no_srv'");
        $vals = [];
        while ($item = mysqli_fetch_assoc($q_items)) {
            $no_item = mysqli_real_escape_string($koneksi, $item['no_item']);
            $qty     = (int)$item['quantity'];
            $vals[]  = "('4','$no_srv','$no_item','$tgl','0','$qty','Servis','$kd_cab')";
        }
        if (!empty($vals)) {
            mysqli_query($koneksi, "INSERT IGNORE INTO tbstok
                (tipe, no_transaksi, no_item, tanggal, masuk, keluar, keterangan, kd_cabang)
                VALUES " . implode(',', $vals));
            $inserted += mysqli_affected_rows($koneksi);
        }
        $services_done++;
    }

    header('Content-Type: application/json');
    echo json_encode([
        'ok'           => true,
        'services_done'=> $services_done,
        'rows_inserted'=> $inserted,
        'sisa'         => max(0, $sisa - $services_done),
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Backfill tbstok Servis</title>
<link rel="stylesheet" href="assets/css/bootstrap.min.css">
<style>
body { padding: 30px; }
#log { height: 300px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; font-family: monospace; font-size: 12px; background: #f9f9f9; }
</style>
</head>
<body>
<h3>Backfill tbstok Servis (tipe='4')</h3>
<p>Script ini mengisi <code>tbstok</code> untuk data servis lama dari migrasi Access yang belum punya entri stok.</p>
<div class="row" style="margin-bottom:15px;">
    <div class="col-md-4">
        <div class="panel panel-default"><div class="panel-body text-center">
            <h4 id="sisa"><?= number_format($sisa) ?></h4>
            <small>Service belum dibackfill</small>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default"><div class="panel-body text-center">
            <h4 id="total_stok"><?= number_format($total_stok) ?></h4>
            <small>Total tbstok tipe='4' saat ini</small>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="panel panel-default"><div class="panel-body text-center">
            <h4 id="rows_inserted">0</h4>
            <small>Baris diinsert sesi ini</small>
        </div></div>
    </div>
</div>
<?php if ($mode === 'cleanup'): ?>
<?php
    // Hapus baris duplikat dari test (id 267, 269 — cross-join artifact)
    mysqli_query($koneksi, "DELETE FROM tbstok WHERE id IN (267, 269) AND tipe='4' AND no_transaksi='SV20000001549' AND kd_cabang='PCL'");
    echo '<div class="alert alert-success">Duplikat test berhasil dihapus ('.mysqli_affected_rows($koneksi).' baris)</div>';
?>
<?php endif; ?>
<a href="admin-backfill-tbstok-servis.php?run=cleanup" class="btn btn-warning btn-sm">Hapus 2 baris duplikat test</a>
&nbsp;
<button id="btnStart" class="btn btn-primary" onclick="startBackfill()">Mulai Backfill (batch <?= $batch_size ?>/request)</button>
<button id="btnStop" class="btn btn-danger" style="display:none;" onclick="stopBackfill()">Stop</button>
<br><br>
<div id="log"></div>

<script>
var running = false;
var totalInserted = 0;
var sisa = <?= $sisa ?>;
var totalStok = <?= $total_stok ?>;

function log(msg) {
    var el = document.getElementById('log');
    el.innerHTML += msg + '<br>';
    el.scrollTop = el.scrollHeight;
}

function startBackfill() {
    if (running) return;
    running = true;
    document.getElementById('btnStart').style.display = 'none';
    document.getElementById('btnStop').style.display = '';
    log('[' + new Date().toLocaleTimeString() + '] Backfill dimulai. Sisa: ' + sisa.toLocaleString() + ' service...');
    runBatch();
}

function stopBackfill() {
    running = false;
    document.getElementById('btnStop').style.display = 'none';
    document.getElementById('btnStart').style.display = '';
    log('[' + new Date().toLocaleTimeString() + '] Dihentikan manual.');
}

function runBatch() {
    if (!running) return;
    fetch('admin-backfill-tbstok-servis.php?run=batch')
        .then(r => r.json())
        .then(data => {
            totalInserted += data.rows_inserted;
            totalStok += data.rows_inserted;
            sisa = data.sisa;
            document.getElementById('sisa').textContent = sisa.toLocaleString();
            document.getElementById('rows_inserted').textContent = totalInserted.toLocaleString();
            document.getElementById('total_stok').textContent = totalStok.toLocaleString();
            log('[' + new Date().toLocaleTimeString() + '] Batch: ' + data.services_done + ' service, +' + data.rows_inserted + ' baris | Sisa: ' + sisa.toLocaleString());
            if (sisa <= 0 || data.services_done === 0) {
                running = false;
                document.getElementById('btnStop').style.display = 'none';
                document.getElementById('btnStart').style.display = '';
                log('[' + new Date().toLocaleTimeString() + '] SELESAI. Total diinsert sesi ini: ' + totalInserted.toLocaleString() + ' baris.');
            } else {
                setTimeout(runBatch, 300);
            }
        })
        .catch(function(err) {
            log('ERROR: ' + err);
            running = false;
        });
}
</script>
</body>
</html>
