<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

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
