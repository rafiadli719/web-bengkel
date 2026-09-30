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

$row = ambilKomplainDalamScope($koneksi_komplain, $id, true);
if (!$row || $row['status'] !== 'Eskalasi Manajemen') {
    echo json_encode(['success' => false, 'message' => 'Komplain bukan status Eskalasi Manajemen.']);
    exit;
}

// Eskalasi cuma lahir dari jalur REWORK (revisi ke-3). Keputusan
// "Dijadwalkan" = REWORK diterima, jadi wajib dibuatkan servis garansi sama
// seperti approval Kepala Cabang — sebelumnya status berubah doang tanpa
// servis, motor gak pernah masuk antrian bengkel (ditemukan E2E 2026-09-30).
$noServiceRework = null;
$pesanTambahan = '';
if ($keputusan === 'Dijadwalkan') {
    $garansi = buatServisGaransiDariKomplain($row);
    if (!$garansi['success']) {
        echo json_encode($garansi);
        exit;
    }
    $noServiceRework = $garansi['no_service'];
    $pesanTambahan = " Servis garansi dibuat: {$garansi['no_service']} (antrian #{$garansi['no_antrian']}, prioritas urgent).";
}

$stmt = $koneksi_komplain->prepare(
    "UPDATE tblkomplain SET status = :status, no_service_rework = COALESCE(:noservicerework, no_service_rework)
     WHERE id = :id AND status = 'Eskalasi Manajemen'"
);
$stmt->execute([':status' => $keputusan, ':noservicerework' => $noServiceRework, ':id' => $id]);

catatLogKomplain($koneksi_komplain, $id, 'keputusan_eskalasi', 'Eskalasi Manajemen', $keputusan,
    'Keputusan final Manajemen' . ($noServiceRework ? " — servis garansi: $noServiceRework" : ''));
echo json_encode(['success' => true, 'message' => "Keputusan final: $keputusan." . $pesanTambahan]);
