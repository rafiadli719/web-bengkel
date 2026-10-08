<?php
// Moving Average: permission modul Persediaan di tb_master_posisi.permissions (JSON array).
// Pakai: php 2026-10-08_run_inv_permissions.php [up|down]
// up   : backup tb_master_posisi -> bak_20261008_tb_master_posisi, tambah kode inv_* bila belum ada
// down : cabut kode inv_* dari semua posisi (backup dibiarkan)
// ADM: inv_admin, inv_opening, inv_laporan, inv_view_all, inv_koreksi (maker). MNG: laporan, view_all, inv_approve (checker).
// KEU: laporan, view_all, inv_approve, inv_finance (checker revaluasi/Finance HQ).
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../../config/koneksi.php';
if (!$koneksi) { echo "Koneksi gagal: " . mysqli_connect_error() . "\n"; exit(1); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mode = $argv[1] ?? 'up';

$grant = [
    'ADM' => ['inv_admin', 'inv_opening', 'inv_laporan', 'inv_view_all', 'inv_koreksi'],
    'MNG' => ['inv_laporan', 'inv_view_all', 'inv_approve'],
    'KEU' => ['inv_laporan', 'inv_view_all', 'inv_approve', 'inv_finance'],
];
$semuaKode = ['inv_admin', 'inv_opening', 'inv_laporan', 'inv_view_all', 'inv_koreksi', 'inv_approve', 'inv_finance'];

if ($mode === 'up') {
    mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS bak_20261008_tb_master_posisi AS SELECT * FROM tb_master_posisi");
}

$res = mysqli_query($koneksi, "SELECT kode_posisi, permissions FROM tb_master_posisi");
$ubah = 0;
while ($row = mysqli_fetch_assoc($res)) {
    $kode = $row['kode_posisi'];
    $perm = json_decode((string) $row['permissions'], true);
    if (!is_array($perm)) {
        continue;
    }
    $baru = $perm;
    if ($mode === 'up') {
        foreach ($grant[$kode] ?? [] as $p) {
            if (!in_array($p, $baru, true)) {
                $baru[] = $p;
            }
        }
    } else {
        $baru = array_values(array_filter($baru, function ($p) use ($semuaKode) { return !in_array($p, $semuaKode, true); }));
    }
    if ($baru !== $perm) {
        $json = json_encode($baru, JSON_UNESCAPED_UNICODE);
        $st = mysqli_prepare($koneksi, "UPDATE tb_master_posisi SET permissions = ? WHERE kode_posisi = ?");
        mysqli_stmt_bind_param($st, 'ss', $json, $kode);
        mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        $ubah++;
        echo "  $kode: " . ($mode === 'up' ? 'ditambah' : 'dicabut') . "\n";
    }
}
echo ucfirst($mode) . " selesai. Posisi berubah: $ubah\n";
