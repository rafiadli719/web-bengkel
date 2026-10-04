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
// No-show juga berlaku untuk komplain yang sudah Dijadwalkan (pelanggan tidak datang
// sesuai jadwal rework, spec 6.2 poin 6); keputusan lain tetap hanya dari Diajukan.
$statusAsal = $row['status'] ?? '';
$statusBoleh = $statusAsal === 'Diajukan' || ($keputusan === 'No-show' && $statusAsal === 'Dijadwalkan');
if (!$row || !$statusBoleh) {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak dalam status yang bisa diputuskan.']);
    exit;
}

// Semua UPDATE di bawah tetap kunci "AND status = 'Diajukan'" — 2 klik
// bersamaan (mis. Setuju + Minta Revisi) gak boleh sama-sama lolos.
if ($keputusan === 'No-show') {
    // Batas default 7 hari, dapat dikonfigurasi (tblkomplain_setting.noshow_hari).
    $batasHari = max(1, (int)getSettingKomplain($koneksi_komplain, 'noshow_hari', '7'));
    $rencana = $row['rencana_tanggal_kedatangan'] ?? null;
    if (empty($rencana)) {
        echo json_encode(['success' => false, 'message' => 'Belum ada rencana tanggal kedatangan, No-show belum bisa ditetapkan.']);
        exit;
    }
    $terlambat = (int)floor((strtotime(date('Y-m-d')) - strtotime($rencana)) / 86400);
    if ($terlambat <= $batasHari) {
        $sisa = $batasHari - $terlambat + 1;
        echo json_encode(['success' => false, 'message' => "Belum melewati batas No-show ($batasHari hari setelah jadwal $rencana). Tunggu $sisa hari lagi."]);
        exit;
    }
    $stmt = $koneksi_komplain->prepare("UPDATE tblkomplain SET status = 'No-show' WHERE id = :id AND status = :asal");
    $stmt->execute([':id' => $id, ':asal' => $statusAsal]);
    catatLogKomplain($koneksi_komplain, $id, 'no_show', $statusAsal, 'No-show', "Terlambat $terlambat hari dari jadwal $rencana (batas $batasHari hari)");
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
