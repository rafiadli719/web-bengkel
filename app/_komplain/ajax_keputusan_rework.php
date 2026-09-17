<?php
require __DIR__ . '/koneksi_komplain.php';
require_once __DIR__ . '/../function_servis.php';
require_once __DIR__ . '/../_include_kategori_member.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_approve_rework')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses approval.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$keputusan = $_POST['keputusan'] ?? '';

$stmtCek = $koneksi_komplain->prepare("SELECT status, jenis_usulan, jumlah_revisi, no_service_asli, nopol, detail_keluhan FROM tblkomplain WHERE id = :id");
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

    $noServiceRework = null;
    $pesanTambahan = '';
    if ($row['jenis_usulan'] === 'Terima') {
        // REWORK disetujui wajib masuk jalur garansi (is_garansi=1, prioritas
        // urgent) — sama seperti servis-garansi.php, BUKAN servis reguler/jemput.
        if (empty($row['no_service_asli'])) {
            echo json_encode(['success' => false, 'message' => 'Komplain ini tidak punya No Service Asli, gak bisa dibuatkan servis garansi. Hubungi admin IT.']);
            exit;
        }
        $qRef = mysqli_query($koneksi, "SELECT no_pelanggan, no_polisi FROM tblservice WHERE no_service = '" . mysqli_real_escape_string($koneksi, $row['no_service_asli']) . "'");
        $refData = $qRef ? mysqli_fetch_assoc($qRef) : null;
        if (!$refData) {
            echo json_encode(['success' => false, 'message' => 'Service asli (' . $row['no_service_asli'] . ') tidak ditemukan di tblservice.']);
            exit;
        }
        $garansi = createServisGaransi(
            $koneksi,
            $row['no_service_asli'],
            $refData['no_pelanggan'],
            $refData['no_polisi'],
            'REWORK Komplain: ' . $row['detail_keluhan'],
            $kode_cabang_aktif,
            $id_user_aktif
        );
        if (!$garansi['success']) {
            echo json_encode(['success' => false, 'message' => 'Gagal membuat servis garansi: ' . $garansi['message']]);
            exit;
        }
        $noServiceRework = $garansi['no_service'];
        $pesanTambahan = " Servis garansi dibuat: {$garansi['no_service']} (antrian #{$garansi['no_antrian']}, prioritas urgent).";
    }

    $stmt = $koneksi_komplain->prepare(
        "UPDATE tblkomplain SET status = :status, keputusan_kepala_cabang = 'Setuju', no_service_rework = :noservicerework WHERE id = :id"
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
