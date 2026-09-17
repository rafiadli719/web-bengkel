<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_input')) {
    http_response_code(403);
    echo json_encode(['servis' => [], 'message' => 'Tidak punya akses.']);
    exit;
}

$nopol = trim($_GET['nopol'] ?? '');
if ($nopol === '') {
    echo json_encode(['servis' => []]);
    exit;
}

// tblservice ada di DB yang sama dgn tblkomplain (lihat koneksi_komplain.php),
// jadi bisa query langsung lewat PDO $koneksi_komplain.
$stmt = $koneksi_komplain->prepare(
    "SELECT no_service, tanggal, keterangan, status_servis
     FROM tblservice WHERE no_polisi = :nopol ORDER BY tanggal DESC, no_service DESC LIMIT 15"
);
$stmt->execute([':nopol' => $nopol]);
echo json_encode(['servis' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
