<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_input')) {
    http_response_code(403);
    echo json_encode(['riwayat' => [], 'message' => 'Tidak punya akses.']);
    exit;
}

$nopol = trim($_GET['nopol'] ?? '');
if ($nopol === '') {
    echo json_encode(['riwayat' => []]);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "SELECT no_komplain, kode_kategori, status, tanggal_lapor
     FROM tblkomplain WHERE nopol = :nopol ORDER BY tanggal_lapor DESC LIMIT 10"
);
$stmt->execute([':nopol' => $nopol]);
echo json_encode(['riwayat' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
