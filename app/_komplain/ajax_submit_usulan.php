<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_review')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses menyusun usulan.']);
    exit;
}

$id = (int)($_POST['komplain_id'] ?? 0);
$jenis = $_POST['jenis_usulan'] ?? '';
$mekanik = trim($_POST['mekanik_pelaksana_kode'] ?? '');
$tanggal = trim($_POST['rencana_tanggal_kedatangan'] ?? '');
$alasan = trim($_POST['alasan_usulan'] ?? '');

if (!in_array($jenis, ['Terima', 'Tolak'], true)) {
    echo json_encode(['success' => false, 'message' => 'Jenis usulan tidak valid.']);
    exit;
}
if ($jenis === 'Terima' && $mekanik === '') {
    echo json_encode(['success' => false, 'message' => 'Mekanik pelaksana wajib diisi untuk usulan Terima.']);
    exit;
}
if ($jenis === 'Tolak' && $alasan === '') {
    echo json_encode(['success' => false, 'message' => 'Alasan wajib diisi untuk usulan Tolak.']);
    exit;
}

// Scope cabang. Status 'Eskalasi Manajemen' sengaja TIDAK boleh — spec
// (docs/Modul_Penanganan_Komplain_Fit_Motor.md aturan 8) eskalasi mengunci
// aksi Kepala Mekanik/Kepala Cabang sampai Manajemen memutuskan.
$row = ambilKomplainDalamScope($koneksi_komplain, $id);
if (!$row || $row['status'] !== 'Open' || $row['pic_role'] !== 'KEPALA_MEKANIK') {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak ditemukan atau status tidak valid untuk usulan.']);
    exit;
}

$stmt = $koneksi_komplain->prepare(
    "UPDATE tblkomplain SET jenis_usulan = :jenis, mekanik_pelaksana_kode = :mekanik,
     rencana_tanggal_kedatangan = :tanggal, alasan_usulan = :alasan, status = 'Diajukan'
     WHERE id = :id AND status = 'Open'"
);
$stmt->execute([
    ':jenis' => $jenis, ':mekanik' => $mekanik ?: null,
    ':tanggal' => $tanggal ?: null, ':alasan' => $alasan ?: null, ':id' => $id,
]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Komplain tidak ditemukan atau status tidak valid untuk usulan.']);
    exit;
}

catatLogKomplain($koneksi_komplain, $id, 'submit_usulan', 'Open', 'Diajukan', "Usulan: $jenis");
echo json_encode(['success' => true, 'message' => 'Usulan tersimpan.']);
