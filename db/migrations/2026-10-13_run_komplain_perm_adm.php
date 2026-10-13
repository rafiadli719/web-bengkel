<?php
// Modul Komplain: samakan izin posisi ADM (admin) dengan lokal supaya halaman antrian approval, dashboard,
// eskalasi dan ekspor tidak 403 untuk admin. Server fitmotor1 hanya punya komplain_input + master_kategori.
// Pakai: php 2026-10-13_run_komplain_perm_adm.php [up|down]
// up   : backup tb_master_posisi -> bak_20261013_tb_master_posisi, tambah kode yang belum ada ke ADM
// down : cabut kode yang ditambahkan migrasi ini dari ADM (backup dibiarkan)
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';
$kodeAdm = ['komplain_review', 'komplain_approve_rework', 'komplain_eskalasi_keputusan', 'komplain_dashboard', 'komplain_export'];

if ($mode === 'up') {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bak_20261013_tb_master_posisi AS SELECT * FROM tb_master_posisi");
}
$st = mysqli_prepare($koneksi, "SELECT permissions FROM tb_master_posisi WHERE kode_posisi = 'ADM'");
mysqli_stmt_execute($st);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);
$perm = json_decode((string)($row['permissions'] ?? ''), true);
if (!is_array($perm)) { echo "Posisi ADM / format permissions tidak ditemukan, tidak ada perubahan.\n"; exit(0); }
$baru = $perm;
if ($mode === 'up') {
    foreach ($kodeAdm as $p) { if (!in_array($p, $baru, true)) { $baru[] = $p; } }
} else {
    $baru = array_values(array_filter($baru, function ($p) use ($kodeAdm) { return !in_array($p, $kodeAdm, true); }));
}
if ($baru === $perm) { echo "ADM sudah sesuai, tidak ada perubahan.\n"; exit(0); }
$json = json_encode($baru, JSON_UNESCAPED_UNICODE);
$up = mysqli_prepare($koneksi, "UPDATE tb_master_posisi SET permissions = ? WHERE kode_posisi = 'ADM'");
mysqli_stmt_bind_param($up, 's', $json);
mysqli_stmt_execute($up);
echo "ADM: " . ($mode === 'up' ? 'ditambah' : 'dicabut') . " (" . count($baru) . " izin total)\n";
