<?php
require __DIR__ . '/koneksi_komplain.php';
header('Content-Type: application/json');

if (!cekPermissionKomplain('komplain_input')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Tidak punya akses input komplain.']);
    exit;
}

$nama = trim($_POST['nama_pelanggan'] ?? '');
$hp = trim($_POST['no_hp'] ?? '');
$nopol = trim($_POST['nopol'] ?? '');
$kategori = trim($_POST['kode_kategori'] ?? '');
$channel = trim($_POST['channel_lapor'] ?? '');
$detail = trim($_POST['detail_keluhan'] ?? '');
$noNota = trim($_POST['no_nota_rujukan'] ?? '') ?: null;
$kodeCabang = $kode_cabang_aktif;

if ($nama === '' || $hp === '' || $nopol === '' || $kategori === '' || $channel === '' || $detail === '' || $kodeCabang === null) {
    echo json_encode(['success' => false, 'message' => 'Field wajib belum lengkap.']);
    exit;
}

$stmtKategori = $koneksi_komplain->prepare(
    "SELECT pic_role FROM tblkomplain_kategori WHERE kode_kategori = :k AND is_active = 'active'"
);
$stmtKategori->execute([':k' => $kategori]);
$picRole = $stmtKategori->fetchColumn();
if ($picRole === false) {
    echo json_encode(['success' => false, 'message' => 'Kategori tidak valid.']);
    exit;
}

$pic = $picRole === 'KEPALA_MEKANIK'
    ? getPicRework($koneksi_komplain, $kodeCabang)
    : getPicNonRework($koneksi_komplain, $kodeCabang);

$noKomplain = generateNoKomplain($koneksi_komplain);

$stmt = $koneksi_komplain->prepare(
    "INSERT INTO tblkomplain
     (no_komplain, nama_pelanggan, no_hp, nopol, kode_cabang, kode_kategori, channel_lapor, detail_keluhan, no_nota_rujukan, pic_kode_karyawan, status)
     VALUES (:no, :nama, :hp, :nopol, :cabang, :kategori, :channel, :detail, :nota, :pic, 'Open')"
);
$stmt->execute([
    ':no' => $noKomplain, ':nama' => $nama, ':hp' => $hp, ':nopol' => $nopol,
    ':cabang' => $kodeCabang, ':kategori' => $kategori, ':channel' => $channel,
    ':detail' => $detail, ':nota' => $noNota, ':pic' => $pic,
]);
$komplainId = (int)$koneksi_komplain->lastInsertId();
catatLogKomplain($koneksi_komplain, $komplainId, 'input_baru', null, 'Open', "Diinput via channel $channel");

echo json_encode(['success' => true, 'no_komplain' => $noKomplain, 'message' => 'Komplain tersimpan.']);
