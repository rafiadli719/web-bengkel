<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_approve_rework')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses approval.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$keputusan = $_POST['keputusan'] ?? '';

$stmtCek = $koneksi_komplain->prepare("SELECT status, jenis_usulan, jumlah_revisi FROM tblkomplain WHERE id = :id");
$stmtCek->execute([':id' => $id]);
$row = $stmtCek->fetch(PDO::FETCH_ASSOC);
if (!$row || $row['status'] !== 'Diajukan') {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak dalam status Diajukan.']);
    exit;
}

if ($keputusan === 'No-show') {
    $stmt = $koneksi_komplain->prepare("UPDATE tblkomplain SET status = 'No-show' WHERE id = :id");
    $stmt->execute([':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'no_show', $row['status'], 'No-show');
    echo json_encode(['success' => true, 'message' => 'Status diubah jadi No-show.']);
    exit;
}

if ($keputusan === 'Setuju') {
    $statusBaru = $row['jenis_usulan'] === 'Terima' ? 'Dijadwalkan' : 'Ditutup - Ditolak';
    $stmt = $koneksi_komplain->prepare(
        "UPDATE tblkomplain SET status = :status, keputusan_kepala_cabang = 'Setuju' WHERE id = :id"
    );
    $stmt->execute([':status' => $statusBaru, ':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'approve', 'Diajukan', $statusBaru);
    echo json_encode(['success' => true, 'message' => "Status diubah jadi $statusBaru."]);
    exit;
}

if ($keputusan === 'Minta Revisi') {
    $revisiBaru = $row['jumlah_revisi'] + 1;
    if ($revisiBaru >= 3) {
        $stmt = $koneksi_komplain->prepare(
            "UPDATE tblkomplain SET status = 'Eskalasi Manajemen', jumlah_revisi = :revisi, keputusan_kepala_cabang = 'Minta Revisi' WHERE id = :id"
        );
        $stmt->execute([':revisi' => $revisiBaru, ':id' => $id]);
        catatLogKomplain($koneksi_komplain, $id, 'eskalasi_otomatis', 'Diajukan', 'Eskalasi Manajemen', "Revisi ke-$revisiBaru");
        echo json_encode(['success' => true, 'message' => 'Revisi ke-3, otomatis eskalasi ke Manajemen.']);
        exit;
    }
    $stmt = $koneksi_komplain->prepare(
        "UPDATE tblkomplain SET status = 'Open', jumlah_revisi = :revisi, keputusan_kepala_cabang = 'Minta Revisi' WHERE id = :id"
    );
    $stmt->execute([':revisi' => $revisiBaru, ':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'minta_revisi', 'Diajukan', 'Open', "Revisi ke-$revisiBaru");
    echo json_encode(['success' => true, 'message' => "Dikembalikan ke Kepala Mekanik (revisi ke-$revisiBaru)."]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Keputusan tidak valid.']);
