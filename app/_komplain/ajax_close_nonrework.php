<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_close_nonrework')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses closing.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$tindakLanjut = trim($_POST['tindak_lanjut_penanganan'] ?? '');

if ($tindakLanjut === '') {
    echo json_encode(['success' => false, 'message' => 'Tindak Lanjut Penanganan wajib diisi.']);
    exit;
}

// Scope cabang + cuma kategori non-REWORK (pic KEPALA_CABANG). Tanpa cek ini
// komplain REWORK yang masih Open bisa ditutup "Selesai" lewat sini dan
// melompati alur usulan Kepala Mekanik -> approval (ditemukan E2E 2026-09-30).
$row = ambilKomplainDalamScope($koneksi_komplain, $id);
if (!$row || $row['pic_role'] !== 'KEPALA_CABANG') {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak ditemukan atau bukan kategori non-REWORK.']);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "UPDATE tblkomplain SET status = 'Selesai', tindak_lanjut_penanganan = :tl, tanggal_ditindaklanjuti = CURDATE()
     WHERE id = :id AND status = 'Open'"
);
$stmt->execute([':tl' => $tindakLanjut, ':id' => $id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak ditemukan atau bukan status Open.']);
    exit;
}

catatLogKomplain($koneksi_komplain, $id, 'close_nonrework', 'Open', 'Selesai', $tindakLanjut);
echo json_encode(['success' => true, 'message' => 'Komplain ditutup Selesai.']);
