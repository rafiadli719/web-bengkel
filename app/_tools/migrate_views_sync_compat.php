<?php
/**
 * Migration: Views Compatibility untuk Sinkronisasi MS Access
 *
 * Menerapkan 2 perubahan view ke live DB:
 * 1. view_service  → ubah INNER JOIN ke LEFT JOIN (riwayat sync tidak hilang)
 * 2. view_cari_kendaraan → tambah fallback merek dari tipe teks (kendaraan sync)
 *
 * Jalankan sekali via browser: panel/_tools/migrate_views_sync_compat.php?confirm=1
 */

$baseDir = dirname(__DIR__, 2);
require_once $baseDir . '/config/koneksi.php';

header('Content-Type: text/html; charset=utf-8');

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if (!isset($koneksi) || !$koneksi) {
    http_response_code(500);
    echo '<h3>Koneksi database gagal.</h3>';
    exit;
}

$confirm = isset($_GET['confirm']) ? (string)$_GET['confirm'] : '';
if ($confirm !== '1') {
    echo '<h3>Migration: Views Sync Compatibility</h3>';
    echo '<p>Script ini akan mengubah 2 view di database:</p>';
    echo '<ol>';
    echo '<li><strong>view_service</strong> &mdash; ganti INNER JOIN ke LEFT JOIN supaya riwayat servis dari MS Access tidak hilang jika pelanggan belum tersync</li>';
    echo '<li><strong>view_cari_kendaraan</strong> &mdash; tambah fallback merek dari teks tipe kendaraan untuk data yang di-sync dari MS Access</li>';
    echo '</ol>';
    echo '<p><a href="?confirm=1" style="color:red;font-weight:bold;">Klik untuk jalankan migration</a></p>';
    exit;
}

$steps = [
    [
        'label' => 'DROP view_service',
        'sql'   => 'DROP VIEW IF EXISTS `view_service`',
    ],
    [
        'label' => 'CREATE view_service (LEFT JOIN)',
        'sql'   => "CREATE VIEW `view_service` AS
SELECT
    s.no_service,
    s.tanggal,
    DATE_FORMAT(s.tanggal, '%d/%m/%Y') AS tanggal_trx,
    s.jam,
    s.no_pelanggan,
    s.no_polisi,
    s.total_grand,
    s.kd_cabang,
    s.status,
    COALESCE(p.namapelanggan, s.no_pelanggan) AS namapelanggan
FROM tblservice s
LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan",
    ],
    [
        'label' => 'DROP view_cari_kendaraan',
        'sql'   => 'DROP VIEW IF EXISTS `view_cari_kendaraan`',
    ],
    [
        'label' => 'CREATE view_cari_kendaraan (merek fallback)',
        'sql'   => "CREATE VIEW `view_cari_kendaraan` AS
SELECT
    k.nopolisi,
    k.pemilik,
    k.alamat,
    k.kode_merek,
    k.tipe,
    k.kode_tipe,
    k.jenis,
    k.kode_jenis,
    k.tahun_buat,
    k.tahun_rakit,
    k.silinder,
    k.warna,
    k.kode_warna,
    k.no_rangka,
    k.no_mesin,
    k.note,
    COALESCE(
        NULLIF(pm.merek, ''),
        CASE
            WHEN (k.kode_merek = '0' OR k.kode_merek IS NULL OR k.kode_merek = '')
            THEN TRIM(SUBSTRING_INDEX(k.tipe, ' ', 1))
            ELSE ''
        END
    ) AS merek
FROM tblkendaraan k
LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
ORDER BY k.nopolisi",
    ],
];

echo '<h3>Migration: Views Sync Compatibility</h3>';
echo '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-family:monospace">';
echo '<thead><tr><th width="30%">Step</th><th width="10%">Status</th><th>Detail</th></tr></thead><tbody>';

$allOk = true;
foreach ($steps as $step) {
    $ok  = mysqli_query($koneksi, $step['sql']);
    $err = $ok ? '' : mysqli_error($koneksi);
    $bg  = $ok ? '#e8fff0' : '#ffe8e8';
    if (!$ok) $allOk = false;
    echo '<tr style="background:' . h($bg) . '">';
    echo '<td>' . h($step['label']) . '</td>';
    echo '<td>' . ($ok ? '&#x2705; OK' : '&#x274C; FAIL') . '</td>';
    echo '<td>' . ($err ? '<span style="color:red">' . h($err) . '</span>' : 'Berhasil') . '</td>';
    echo '</tr>';
}

echo '</tbody></table><br>';
if ($allOk) {
    echo '<div style="padding:10px;background:#e8fff0;border:1px solid #0a0">&#x2705; Semua migration berhasil. View sudah diperbarui di database.</div>';
} else {
    echo '<div style="padding:10px;background:#ffe8e8;border:1px solid #a00">&#x274C; Ada step yang gagal. Periksa error di atas.</div>';
}
