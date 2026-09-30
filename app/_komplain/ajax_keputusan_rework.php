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

$row = ambilKomplainDalamScope($koneksi_komplain, $id);
if (!$row || $row['status'] !== 'Diajukan') {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak dalam status Diajukan.']);
    exit;
}

// Semua UPDATE di bawah tetap kunci "AND status = 'Diajukan'" — 2 klik
// bersamaan (mis. Setuju + Minta Revisi) gak boleh sama-sama lolos.
if ($keputusan === 'No-show') {
    $stmt = $koneksi_komplain->prepare("UPDATE tblkomplain SET status = 'No-show' WHERE id = :id AND status = 'Diajukan'");
    $stmt->execute([':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'no_show', $row['status'], 'No-show');
    echo json_encode(['success' => true, 'message' => 'Status diubah jadi No-show.']);
    exit;
}

if ($keputusan === 'Setuju') {
    $statusBaru = $row['jenis_usulan'] === 'Terima' ? 'Dijadwalkan' : 'Ditutup - Ditolak';

    $noServiceRework = null;
    $pesanTambahan = '';
    if ($row['jenis_usulan'] === 'Terima') {
        // REWORK disetujui wajib masuk jalur garansi (is_garansi=1, prioritas
        // urgent) — sama seperti servis-garansi.php, BUKAN servis reguler/jemput.
        $garansi = buatServisGaransiDariKomplain($row);
        if (!$garansi['success']) {
            echo json_encode($garansi);
            exit;
        }
        $noServiceRework = $garansi['no_service'];
        $pesanTambahan = " Servis garansi dibuat: {$garansi['no_service']} (antrian #{$garansi['no_antrian']}, prioritas urgent).";
    }

    $stmt = $koneksi_komplain->prepare(
        "UPDATE tblkomplain SET status = :status, keputusan_kepala_cabang = 'Setuju', no_service_rework = :noservicerework WHERE id = :id AND status = 'Diajukan'"
    );
    $stmt->execute([':status' => $statusBaru, ':noservicerework' => $noServiceRework, ':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'approve', 'Diajukan', $statusBaru, $noServiceRework ? "Servis garansi: $noServiceRework" : '');
    echo json_encode(['success' => true, 'message' => "Status diubah jadi $statusBaru." . $pesanTambahan]);
    exit;
}

if ($keputusan === 'Minta Revisi') {
    $revisiBaru = $row['jumlah_revisi'] + 1;
    if ($revisiBaru >= 3) {
        $stmt = $koneksi_komplain->prepare(
            "UPDATE tblkomplain SET status = 'Eskalasi Manajemen', jumlah_revisi = :revisi, keputusan_kepala_cabang = 'Minta Revisi' WHERE id = :id AND status = 'Diajukan'"
        );
        $stmt->execute([':revisi' => $revisiBaru, ':id' => $id]);
        catatLogKomplain($koneksi_komplain, $id, 'eskalasi_otomatis', 'Diajukan', 'Eskalasi Manajemen', "Revisi ke-$revisiBaru");
        echo json_encode(['success' => true, 'message' => 'Revisi ke-3, otomatis eskalasi ke Manajemen.']);
        exit;
    }
    $stmt = $koneksi_komplain->prepare(
        "UPDATE tblkomplain SET status = 'Open', jumlah_revisi = :revisi, keputusan_kepala_cabang = 'Minta Revisi' WHERE id = :id AND status = 'Diajukan'"
    );
    $stmt->execute([':revisi' => $revisiBaru, ':id' => $id]);
    catatLogKomplain($koneksi_komplain, $id, 'minta_revisi', 'Diajukan', 'Open', "Revisi ke-$revisiBaru");
    echo json_encode(['success' => true, 'message' => "Dikembalikan ke Kepala Mekanik (revisi ke-$revisiBaru)."]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Keputusan tidak valid.']);
