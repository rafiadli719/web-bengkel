<?php
declare(strict_types=1);

// Sumber: web_kasir/api_setor_bank_pengambilan.php — endpoint JSON POST-only,
// eksekusi "Setor ke Bank" untuk satu record pengambilan_setoran. Gerbang asli
// role ['admin','super_admin'] (KEU/ADM di fitmotor) — dipetakan ke izin
// kasir_approve (Task 10: satu-satunya kode yang dipegang persis ADM+KEU, bukan KSR).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

header('Content-Type: application/json; charset=utf-8');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/process_pengadaan_verification.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Metode request tidak valid.');
    }

    $kodePengambilan = trim($_POST['kode_pengambilan'] ?? '');
    $tglPenyerahan   = trim($_POST['tanggal_penyerahan'] ?? '');
    $tglRiil         = trim($_POST['tanggal_riil'] ?? '');

    if ($kodePengambilan === '') {
        throw new RuntimeException('Kode pengambilan wajib diisi.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglPenyerahan)) {
        throw new RuntimeException('Tanggal penyerahan tidak valid. Format: YYYY-MM-DD.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglRiil)) {
        throw new RuntimeException('Tanggal riil setor bank tidak valid. Format: YYYY-MM-DD.');
    }

    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode pengambilan tidak ditemukan.');
    }

    if (($root['status_setor_bank'] ?? 'belum') === 'disetor') {
        throw new RuntimeException('Pengambilan ini sudah disetor ke bank sebelumnya.');
    }

    $kodeSetoran = 'SKB-' . date('Ymd') . '-' . strtoupper(substr($kodePengambilan, -6));
    $karyawan    = $kode_karyawan_aktif;

    $idSetoranBank = executeSetorBank($pdo, $kodePengambilan, $tglPenyerahan, $tglRiil, $kodeSetoran, $karyawan);

    echo json_encode([
        'success'          => true,
        'message'          => 'Pengambilan berhasil disetor ke bank.',
        'id_setoran_bank'  => $idSetoranBank,
        'kode_pengambilan' => $kodePengambilan,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
