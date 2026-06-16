<?php
$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/config/koneksi.php';

header('Content-Type: text/html; charset=utf-8');

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

if (!isset($koneksi) || !$koneksi) {
    http_response_code(500);
    echo '<h3>Koneksi database gagal.</h3>';
    exit;
}

$sqlFile = $baseDir . '/create_all_views.sql';
if (!is_file($sqlFile)) {
    http_response_code(404);
    echo '<h3>File tidak ditemukan: ' . h($sqlFile) . '</h3>';
    exit;
}

$confirm = isset($_GET['confirm']) ? (string)$_GET['confirm'] : '';
if ($confirm !== '1') {
    echo '<h3>Run CREATE VIEW</h3>';
    echo '<p>Tool ini akan menjalankan semua statement di <code>' . h($sqlFile) . '</code>.</p>';
    echo '<p><a href="?confirm=1">Klik untuk jalankan</a></p>';
    exit;
}

$sql = file_get_contents($sqlFile);
$sql = str_replace("\r\n", "\n", $sql);
$sql = preg_replace('/^\s*--.*$/m', '', $sql);
$sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

$parts = explode(';', $sql);
$results = [];

foreach ($parts as $idx => $stmt) {
    $stmt = trim($stmt);
    if ($stmt === '') {
        continue;
    }

    $ok = mysqli_query($koneksi, $stmt);
    $results[] = [
        'no' => count($results) + 1,
        'ok' => (bool)$ok,
        'error' => $ok ? '' : mysqli_error($koneksi),
        'sql' => $stmt,
    ];
}

$total = count($results);
$success = 0;
$failed = 0;
foreach ($results as $r) {
    if ($r['ok']) {
        $success++;
    } else {
        $failed++;
    }
}

echo '<h3>Hasil eksekusi create_all_views.sql</h3>';
echo '<p>Total: ' . h($total) . ' | Sukses: ' . h($success) . ' | Gagal: ' . h($failed) . '</p>';

echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%">';
echo '<thead><tr>';
echo '<th style="width:60px">No</th>';
echo '<th style="width:90px">Status</th>';
echo '<th style="width:320px">Error</th>';
echo '<th>SQL</th>';
echo '</tr></thead><tbody>';

foreach ($results as $r) {
    $status = $r['ok'] ? 'OK' : 'FAIL';
    $bg = $r['ok'] ? '#e8fff0' : '#ffe8e8';
    echo '<tr style="background:' . h($bg) . '">';
    echo '<td>' . h($r['no']) . '</td>';
    echo '<td><b>' . h($status) . '</b></td>';
    echo '<td><code>' . h($r['error']) . '</code></td>';
    echo '<td><pre style="white-space:pre-wrap; margin:0">' . h($r['sql']) . '</pre></td>';
    echo '</tr>';
}

echo '</tbody></table>';
