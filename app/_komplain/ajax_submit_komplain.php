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
$noServiceAsli = trim($_POST['no_service_asli'] ?? '');
$kodeCabang = $kode_cabang_aktif;

if ($nama === '' || $hp === '' || $nopol === '' || $kategori === '' || $channel === '' || $detail === '' || $noServiceAsli === '' || $kodeCabang === null) {
    echo json_encode(['success' => false, 'message' => 'Field wajib belum lengkap (termasuk No Service Asli).']);
    exit;
}

// No Service Asli wajib valid & punya nopol yang sama — ini yang jadi
// ref_service kalau komplain berujung REWORK, biar servis rework otomatis
// masuk jalur garansi (is_garansi=1), bukan servis reguler/jemput.
$stmtService = $koneksi_komplain->prepare("SELECT no_polisi FROM tblservice WHERE no_service = :ns");
$stmtService->execute([':ns' => $noServiceAsli]);
$servicePolisi = $stmtService->fetchColumn();
if ($servicePolisi === false) {
    echo json_encode(['success' => false, 'message' => 'No Service Asli tidak ditemukan.']);
    exit;
}
if (strcasecmp($servicePolisi, $nopol) !== 0) {
    echo json_encode(['success' => false, 'message' => 'No Service Asli tidak cocok dengan Nopol yang diinput.']);
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

// generateNoKomplain() pakai COUNT()+INSERT terpisah (no lock) - 2 submit
// bersamaan bisa dapet nomor sama & tabrak UNIQUE constraint. Retry beberapa
// kali dengan nomor baru daripada biarin PDOException nembus jadi fatal error
// (ditemukan final code review, bukan di plan asli).
$noKomplain = null;
$komplainId = null;
$maxPercobaan = 3;
for ($percobaan = 1; $percobaan <= $maxPercobaan; $percobaan++) {
    $noKomplain = generateNoKomplain($koneksi_komplain);
    try {
        $stmt = $koneksi_komplain->prepare(
            "INSERT INTO tblkomplain
             (no_komplain, nama_pelanggan, no_hp, nopol, kode_cabang, kode_kategori, channel_lapor, detail_keluhan, no_service_asli, pic_kode_karyawan, status)
             VALUES (:no, :nama, :hp, :nopol, :cabang, :kategori, :channel, :detail, :noservice, :pic, 'Open')"
        );
        $stmt->execute([
            ':no' => $noKomplain, ':nama' => $nama, ':hp' => $hp, ':nopol' => $nopol,
            ':cabang' => $kodeCabang, ':kategori' => $kategori, ':channel' => $channel,
            ':detail' => $detail, ':noservice' => $noServiceAsli, ':pic' => $pic,
        ]);
        $komplainId = (int)$koneksi_komplain->lastInsertId();
        break;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000' && $percobaan < $maxPercobaan) {
            continue; // duplicate no_komplain, coba nomor berikutnya
        }
        error_log('[komplain] insert gagal: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Gagal menyimpan komplain, coba lagi.']);
        exit;
    }
}
catatLogKomplain($koneksi_komplain, $komplainId, 'input_baru', null, 'Open', "Diinput via channel $channel");

echo json_encode(['success' => true, 'no_komplain' => $noKomplain, 'message' => 'Komplain tersimpan.']);
