<?php
declare(strict_types=1);

// Sumber: web_kasir/api_edit_nominal_pengambilan.php + api_pelunasan_manual.php +
// api_validate_pelunasan_document.php — 3 endpoint JSON POST-only terpisah di
// web_kasir, digabung jadi satu file di sini (dispatch via $_POST['action'])
// sesuai daftar target file Task 14 di plan. Gerbang asli role
// ['kasir','admin','super_admin','gudang','pengadaan'] — di fitmotor cuma ada
// KSR/KEU/ADM (roleMap koneksi_kasir.php), gudang/pengadaan gak ada padanan;
// baseline kasir_menu_read (sudah dicek koneksi_kasir.php) sudah cukup ketat
// karena legacy_session_kasir['role'] cuma pernah 'kasir'|'admin'|'super_admin'.
require_once __DIR__ . '/koneksi_kasir.php';

header('Content-Type: application/json; charset=utf-8');

if (!in_array($legacy_session_kasir['role'] ?? '', ['kasir', 'admin', 'super_admin'], true)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Session login tidak valid.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

require_once __DIR__ . '/process_pengadaan_verification.php';

$action = trim($_POST['action'] ?? '');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new RuntimeException('Metode request tidak valid.');
    }

    switch ($action) {
        // --- Sumber: api_edit_nominal_pengambilan.php ---
        case 'edit_nominal':
            $kodePengambilan = trim($_POST['kode_pengambilan'] ?? '');
            $nominalRaw      = trim($_POST['nominal_baru'] ?? '');
            $alasan          = trim($_POST['alasan'] ?? '');
            $roleInput       = trim($_POST['role'] ?? '');

            if ($kodePengambilan === '') {
                throw new RuntimeException('Kode pengambilan wajib diisi.');
            }
            if ($nominalRaw === '') {
                throw new RuntimeException('Nominal baru wajib diisi.');
            }
            if ($alasan === '') {
                throw new RuntimeException('Alasan perubahan wajib diisi.');
            }

            $nominal = (float)str_replace(['.', ','], ['', '.'], $nominalRaw);
            if ($nominal < 0) {
                throw new RuntimeException('Nominal tidak boleh negatif.');
            }

            $role = $legacy_session_kasir['role'];
            $editRole = in_array($role, ['admin', 'super_admin'], true) ? 'keuangan' : 'penerima';
            if ($roleInput === 'penerima') {
                $editRole = 'penerima';
            }

            editPengambilanNominal(
                $pdo,
                $kodePengambilan,
                $nominal,
                $alasan,
                $kode_karyawan_aktif,
                $editRole
            );

            echo json_encode([
                'success' => true,
                'message' => 'Nominal pengambilan berhasil diperbarui.',
            ], JSON_UNESCAPED_UNICODE);
            break;

        // --- Sumber: api_pelunasan_manual.php ---
        case 'pelunasan_manual':
            $kodePengambilan = trim($_POST['kode_pengambilan'] ?? '');
            $nominalRaw      = trim($_POST['nominal'] ?? '');
            $noTrx           = trim($_POST['no_transaksi'] ?? '');
            $tglTransfer     = trim($_POST['tanggal_transfer'] ?? '');
            $catatan         = trim($_POST['catatan'] ?? '');

            if ($kodePengambilan === '') {
                throw new RuntimeException('Kode pengambilan wajib diisi.');
            }
            if ($nominalRaw === '') {
                throw new RuntimeException('Nominal pelunasan wajib diisi.');
            }
            if ($tglTransfer === '') {
                throw new RuntimeException('Tanggal transfer wajib diisi.');
            }

            $remaining = getPengambilanRemainingAmount($pdo, $kodePengambilan);

            $nominal = (float)str_replace(['.', ','], ['', '.'], $nominalRaw);
            if ($remaining <= 0.0) {
                $nominal = 0.0;
            } else {
                if ($nominal <= 0) {
                    throw new RuntimeException('Nominal harus lebih besar dari nol.');
                }
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglTransfer)) {
                throw new RuntimeException('Format tanggal transfer tidak valid (YYYY-MM-DD).');
            }

            $documentMeta = null;
            if (!empty($_FILES['bukti_tf']) && $_FILES['bukti_tf']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['bukti_tf'];
                $originalName = (string)($uploadedFile['name'] ?? 'dokumen');
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($extension, ['pdf', 'docx', 'jpg', 'jpeg', 'png'], true)) {
                    throw new RuntimeException('File bukti TF harus berupa PDF, DOCX, JPG, JPEG, atau PNG.');
                }

                if ((int)($uploadedFile['size'] ?? 0) > 8 * 1024 * 1024) {
                    throw new RuntimeException('Ukuran file maksimal 8 MB.');
                }

                $tmpUploadDir = getPelunasanDocumentStorageRoot() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'files';
                ensurePelunasanDocumentDirectory($tmpUploadDir);

                $storedFile = $tmpUploadDir . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.' . $extension;
                $tmpName = (string)($uploadedFile['tmp_name'] ?? '');
                $moved = is_uploaded_file($tmpName)
                    ? move_uploaded_file($tmpName, $storedFile)
                    : @rename($tmpName, $storedFile);

                if (!$moved && !@copy($tmpName, $storedFile)) {
                    throw new RuntimeException('Gagal menyimpan file upload bukti TF.');
                }

                $token = storePelunasanDocumentToken([
                    'stored_file' => $storedFile,
                    'original_name' => $originalName,
                    'mime_type' => (string)($uploadedFile['type'] ?? 'application/octet-stream'),
                    'file_type' => $extension,
                    'parsed' => [],
                    'validation' => ['ok' => true],
                    'expected' => [],
                    'uploaded_by' => $kode_karyawan_aktif,
                ]);

                $documentMeta = finalizePelunasanDocumentToken($token, $kodePengambilan);
            }

            $paymentId = recordManualPelunasan(
                $pdo,
                $kodePengambilan,
                $nominal,
                $noTrx,
                $tglTransfer,
                $catatan,
                $kode_karyawan_aktif,
                $documentMeta
            );

            $remaining = getPengambilanRemainingAmount($pdo, $kodePengambilan);

            echo json_encode([
                'success'     => true,
                'message'     => 'Pelunasan manual berhasil disimpan.',
                'payment_id'  => $paymentId,
                'sisa_hutang' => $remaining,
                'lunas'       => $remaining <= 0.0,
            ], JSON_UNESCAPED_UNICODE);
            break;

        // --- Sumber: api_validate_pelunasan_document.php ---
        case 'validate_document':
            $kodePengambilan = trim($_POST['kode_pengambilan'] ?? '');
            if ($kodePengambilan === '') {
                throw new RuntimeException('Kode validasi pengambilan wajib diisi.');
            }

            if (empty($_FILES['dokumen_mutasi']) || !is_array($_FILES['dokumen_mutasi'])) {
                throw new RuntimeException('File dokumen mutasi wajib diupload.');
            }

            $uploadedFile = $_FILES['dokumen_mutasi'];
            if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Upload dokumen gagal. Kode error: ' . (int)$uploadedFile['error']);
            }

            $originalName = (string)($uploadedFile['name'] ?? 'dokumen');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (!in_array($extension, ['pdf', 'docx', 'jpg', 'jpeg', 'png'], true)) {
                throw new RuntimeException('File harus berupa PDF, DOCX, JPG, JPEG, atau PNG.');
            }

            if ((int)($uploadedFile['size'] ?? 0) > 8 * 1024 * 1024) {
                throw new RuntimeException('Ukuran file maksimal 8 MB.');
            }

            $mimeType = (string)($uploadedFile['type'] ?? 'application/octet-stream');
            $tmpUploadDir = getPelunasanDocumentStorageRoot() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'files';
            ensurePelunasanDocumentDirectory($tmpUploadDir);

            $storedFile = $tmpUploadDir . DIRECTORY_SEPARATOR . bin2hex(random_bytes(12)) . '.' . $extension;
            if (!move_uploaded_file((string)$uploadedFile['tmp_name'], $storedFile)) {
                throw new RuntimeException('Gagal menyimpan file upload sementara.');
            }

            $reader = new DocumentReader();
            try {
                $parsed = $reader->read($storedFile);
            } catch (Throwable $ocrException) {
                @unlink($storedFile);
                error_log('[OCR ERROR] ' . $ocrException->getMessage());
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'ocr_unavailable' => true,
                    'message' => 'OCR tidak tersedia di server (' . $ocrException->getMessage() . '). Silakan gunakan Input Manual Pelunasan.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $validation = validateDetectedPelunasanDocument($pdo, $kodePengambilan, $parsed, null);
            $expected = $validation['expected'];

            if (!$validation['ok']) {
                @unlink($storedFile);
                http_response_code(422);
                echo json_encode([
                    'success' => false,
                    'message' => implode(' ', $validation['errors']),
                    'data' => [
                        'pengirim' => $parsed['pengirim'] ?? null,
                        'penerima' => $parsed['penerima'] ?? null,
                        'nominal' => $parsed['nominal'] ?? null,
                        'confidence' => $parsed['confidence'] ?? 'low',
                    ],
                    'parsed' => $parsed,
                    'validation' => $validation,
                    'expected' => $expected,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
            }

            $token = storePelunasanDocumentToken([
                'stored_file' => $storedFile,
                'original_name' => $originalName,
                'mime_type' => $mimeType,
                'file_type' => $parsed['file_type'] ?? $extension,
                'parsed' => $parsed,
                'validation' => $validation,
                'expected' => $expected,
                'uploaded_by' => $kode_karyawan_aktif,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Dokumen pelunasan berhasil diverifikasi.',
                'token' => $token,
                'data' => [
                    'pengirim' => $parsed['pengirim'] ?? null,
                    'penerima' => $parsed['penerima'] ?? null,
                    'nominal' => $parsed['nominal'] ?? null,
                    'confidence' => $parsed['confidence'] ?? 'low',
                ],
                'parsed' => $parsed,
                'validation' => $validation,
                'expected' => $expected,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;

        default:
            throw new RuntimeException('Action tidak dikenali: ' . $action);
    }
} catch (Throwable $exception) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
