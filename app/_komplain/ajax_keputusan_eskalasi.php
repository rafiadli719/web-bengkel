<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_eskalasi_keputusan')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses keputusan eskalasi.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$keputusan = $_POST['keputusan_final'] ?? '';

if (!in_array($keputusan, ['Dijadwalkan', 'Ditutup - Ditolak'], true)) {
    echo json_encode(['success' => false, 'message' => 'Keputusan tidak valid.']);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "UPDATE tblkomplain SET status = :status WHERE id = :id AND status = 'Eskalasi Manajemen'"
);
$stmt->execute([':status' => $keputusan, ':id' => $id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Komplain bukan status Eskalasi Manajemen.']);
    exit;
}

catatLogKomplain($koneksi_komplain, $id, 'keputusan_eskalasi', 'Eskalasi Manajemen', $keputusan, 'Keputusan final Manajemen');
echo json_encode(['success' => true, 'message' => "Keputusan final: $keputusan."]);
