<?php
/**
 * Helper dan fondasi verifikasi untuk alur pengambilan setoran pengadaan.
 */

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/services/pelunasan_hutang/ExtractorService.php';
require_once __DIR__ . '/services/pelunasan_hutang/ParserService.php';
require_once __DIR__ . '/services/pelunasan_hutang/OCRService.php';
require_once __DIR__ . '/services/pelunasan_hutang/DocumentReader.php';

function normalizePengambilanNoRekening(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }

    $digits = preg_replace('/\D+/', '', $value);
    if ($digits !== '') {
        return $digits;
    }

    return strtoupper($value);
}

function resolvePengambilanClassification(array $record): string
{
    if (!empty($record['klasifikasi'])) {
        return $record['klasifikasi'] === 'hutang' ? 'hutang' : 'internal';
    }

    return (($record['jenis_rekening'] ?? 'internal') === 'eksternal') ? 'hutang' : 'internal';
}

function getRekeningCabangById(PDO $pdo, int $rekeningId): ?array
{
    $stmt = $pdo->prepare("SELECT mr.*, c.nama_cabang
                           FROM master_rekening_cabang_closing_kasir mr
                           LEFT JOIN tbcabang c ON c.cabang_ref_kode = mr.kode_cabang
                           WHERE mr.id = ?
                           LIMIT 1");
    $stmt->execute([$rekeningId]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getCabangPenerimaRekening(PDO $pdo, string $kodeCabang): ?array
{
    $stmt = $pdo->prepare("SELECT mr.*, c.nama_cabang
                           FROM master_rekening_cabang_closing_kasir mr
                           LEFT JOIN tbcabang c ON c.cabang_ref_kode = mr.kode_cabang
                           WHERE mr.kode_cabang = ?
                             AND mr.status = 'active'
                           ORDER BY (mr.jenis_rekening = 'Milik Sendiri') DESC, mr.id ASC
                           LIMIT 1");
    $stmt->execute([$kodeCabang]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getCabangPenerimaOptions(PDO $pdo): array
{
    $sql = "SELECT mr.kode_cabang,
                   c.nama_cabang,
                   mr.nama_bank,
                   mr.nama_rekening,
                   mr.no_rekening,
                   mr.jenis_rekening
            FROM master_rekening_cabang_closing_kasir mr
            JOIN tbcabang c ON c.cabang_ref_kode = mr.kode_cabang
            WHERE mr.status = 'active'
            ORDER BY c.nama_cabang";

    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function classifyPengambilan(?string $noRekeningPeminjam, ?string $noRekeningPenerima): string
{
    $peminjam = normalizePengambilanNoRekening($noRekeningPeminjam);
    $penerima = normalizePengambilanNoRekening($noRekeningPenerima);

    if ($peminjam === '' || $penerima === '') {
        throw new InvalidArgumentException('Nomor rekening peminjam atau penerima tidak boleh kosong.');
    }

    return $peminjam === $penerima ? 'internal' : 'hutang';
}

function ensurePengambilanEditLogTable(PDO $pdo): void
{
    static $ensured = false;
    if ($ensured) {
        return;
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS pengambilan_setoran_edit_log_closing_kasir (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        kode_pengambilan VARCHAR(64) NOT NULL,
        edited_by_role VARCHAR(50) NOT NULL DEFAULT '',
        edited_by_kode_karyawan VARCHAR(64) DEFAULT NULL,
        field_changed VARCHAR(100) NOT NULL DEFAULT 'nominal_diambil',
        old_value TEXT DEFAULT NULL,
        new_value TEXT DEFAULT NULL,
        pemasukan_kasir_id_invalidated BIGINT DEFAULT NULL,
        alasan TEXT DEFAULT NULL,
        dibaca TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $requiredColumns = [
        'edited_by_role' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN edited_by_role VARCHAR(50) NOT NULL DEFAULT '' AFTER kode_pengambilan",
        'edited_by_kode_karyawan' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN edited_by_kode_karyawan VARCHAR(64) DEFAULT NULL AFTER edited_by_role",
        'field_changed' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN field_changed VARCHAR(100) NOT NULL DEFAULT 'nominal_diambil' AFTER edited_by_kode_karyawan",
        'old_value' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN old_value TEXT DEFAULT NULL AFTER field_changed",
        'new_value' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN new_value TEXT DEFAULT NULL AFTER old_value",
        'pemasukan_kasir_id_invalidated' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN pemasukan_kasir_id_invalidated BIGINT DEFAULT NULL AFTER new_value",
        'alasan' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN alasan TEXT DEFAULT NULL AFTER pemasukan_kasir_id_invalidated",
        'dibaca' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN dibaca TINYINT(1) NOT NULL DEFAULT 0 AFTER alasan",
        'created_at' => "ALTER TABLE pengambilan_setoran_edit_log_closing_kasir ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER dibaca",
    ];

    $existingColumns = [];
    foreach ($pdo->query("SHOW COLUMNS FROM pengambilan_setoran_edit_log_closing_kasir")->fetchAll(PDO::FETCH_ASSOC) as $columnRow) {
        $existingColumns[$columnRow['Field']] = true;
    }
    foreach ($requiredColumns as $column => $sql) {
        if (!isset($existingColumns[$column])) {
            $pdo->exec($sql);
        }
    }

    $requiredIndexes = [
        'idx_pengambilan_setoran_edit_log_kode' => "CREATE INDEX idx_pengambilan_setoran_edit_log_kode ON pengambilan_setoran_edit_log_closing_kasir (kode_pengambilan)",
        'idx_pengambilan_setoran_edit_log_dibaca' => "CREATE INDEX idx_pengambilan_setoran_edit_log_dibaca ON pengambilan_setoran_edit_log_closing_kasir (dibaca)",
    ];

    $existingIndexes = [];
    foreach ($pdo->query("SHOW INDEX FROM pengambilan_setoran_edit_log_closing_kasir")->fetchAll(PDO::FETCH_ASSOC) as $indexRow) {
        $existingIndexes[$indexRow['Key_name']] = true;
    }
    foreach ($requiredIndexes as $indexName => $sql) {
        if (!isset($existingIndexes[$indexName])) {
            $pdo->exec($sql);
        }
    }

    $ensured = true;
}

function generateKodePelunasan(PDO $pdo): string
{
    $year = date('Y');
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING_INDEX(kode_validasi_input, '-', -1) AS UNSIGNED))
                           FROM pengambilan_setoran_pembayaran_closing_kasir
                           WHERE kode_validasi_input REGEXP ?
                             AND sumber = 'manual'");
    $stmt->execute(['^PLN-' . $year . '-[0-9]{4}$']);
    $max = (int)$stmt->fetchColumn();
    return 'PLN-' . $year . '-' . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
}

function generateKodePengambilan(PDO $pdo, ?string $parentKodePengambilan = null): string
{
    if ($parentKodePengambilan) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengambilan_setoran_closing_kasir WHERE parent_kode_pengambilan = ?");
        $stmt->execute([$parentKodePengambilan]);
        $next = (int)$stmt->fetchColumn() + 1;

        return $parentKodePengambilan . '-T' . $next;
    }

    $year = date('Y');
    $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(kode_pengambilan, 10, 4) AS UNSIGNED))
                           FROM pengambilan_setoran_closing_kasir
                           WHERE kode_pengambilan REGEXP ?");
    $stmt->execute(['^PGA-' . $year . '-[0-9]{4}$']);
    $max = (int)$stmt->fetchColumn();

    return 'PGA-' . $year . '-' . str_pad((string)($max + 1), 4, '0', STR_PAD_LEFT);
}

function getPengambilanRootRecord(PDO $pdo, string $kodePengambilan): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1");
    $stmt->execute([$kodePengambilan]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        return null;
    }

    if (!empty($row['parent_kode_pengambilan'])) {
        $stmtParent = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1");
        $stmtParent->execute([$row['parent_kode_pengambilan']]);
        $parent = $stmtParent->fetch(PDO::FETCH_ASSOC);
        return $parent ?: $row;
    }

    return $row;
}

function getPengambilanGroupCodes(PDO $pdo, string $rootKodePengambilan): array
{
    $codes = [$rootKodePengambilan];

    $stmt = $pdo->prepare("SELECT kode_pengambilan
                           FROM pengambilan_setoran_closing_kasir
                           WHERE parent_kode_pengambilan = ?
                           ORDER BY created_at ASC, id ASC");
    $stmt->execute([$rootKodePengambilan]);

    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $kode) {
        $codes[] = $kode;
    }

    return $codes;
}

function getPengambilanPaidAmount(PDO $pdo, string $kodePengambilan): float
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return 0.0;
    }

    $codes = getPengambilanGroupCodes($pdo, $root['kode_pengambilan']);
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(nominal_dibayar), 0)
                           FROM pengambilan_setoran_pembayaran_closing_kasir
                           WHERE kode_pengambilan IN ($placeholders)");
    $stmt->execute($codes);

    return (float)$stmt->fetchColumn();
}

function getPengambilanRemainingAmount(PDO $pdo, string $kodePengambilan): float
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return 0.0;
    }

    $remaining = (float)$root['nominal_diambil'] - getPengambilanPaidAmount($pdo, $root['kode_pengambilan']);
    return max(0.0, round($remaining, 2));
}

function inferPengambilanClosingTimestamp(PDO $pdo, ?string $kodeCabangPenerima, ?string $referenceAt = null): ?string
{
    $kodeCabangPenerima = trim((string)$kodeCabangPenerima);
    if ($kodeCabangPenerima === '') {
        return null;
    }

    $referenceAt = trim((string)$referenceAt);
    $referenceDate = null;
    if ($referenceAt !== '') {
        try {
            $referenceDate = new DateTime($referenceAt);
        } catch (Throwable $e) {
            $referenceDate = null;
        }
    }

    if ($referenceDate) {
        $stmtSameDay = $pdo->prepare("SELECT tanggal_closing, jam_closing
                                      FROM kasir_transactions_closing_kasir
                                      WHERE kode_cabang = ?
                                        AND status = 'end proses'
                                        AND tanggal_closing = ?
                                      ORDER BY
                                        CASE
                                            WHEN TIMESTAMP(tanggal_closing, COALESCE(jam_closing, '00:00:00')) <= ? THEN 0
                                            ELSE 1
                                        END,
                                        ABS(TIMESTAMPDIFF(SECOND, TIMESTAMP(tanggal_closing, COALESCE(jam_closing, '00:00:00')), ?)) ASC
                                      LIMIT 1");
        $referenceTimestamp = $referenceDate->format('Y-m-d H:i:s');
        $stmtSameDay->execute([
            $kodeCabangPenerima,
            $referenceDate->format('Y-m-d'),
            $referenceTimestamp,
            $referenceTimestamp,
        ]);
        $sameDayRow = $stmtSameDay->fetch(PDO::FETCH_ASSOC);
        if ($sameDayRow && !empty($sameDayRow['tanggal_closing'])) {
            return $sameDayRow['tanggal_closing'] . ' ' . ($sameDayRow['jam_closing'] ?: '00:00:00');
        }

        $stmtNext = $pdo->prepare("SELECT tanggal_closing, jam_closing
                                   FROM kasir_transactions_closing_kasir
                                   WHERE kode_cabang = ?
                                     AND status = 'end proses'
                                     AND tanggal_closing IS NOT NULL
                                     AND TIMESTAMP(tanggal_closing, COALESCE(jam_closing, '00:00:00')) >= ?
                                   ORDER BY tanggal_closing ASC, jam_closing ASC
                                   LIMIT 1");
        $stmtNext->execute([$kodeCabangPenerima, $referenceTimestamp]);
        $nextRow = $stmtNext->fetch(PDO::FETCH_ASSOC);
        if ($nextRow && !empty($nextRow['tanggal_closing'])) {
            return $nextRow['tanggal_closing'] . ' ' . ($nextRow['jam_closing'] ?: '00:00:00');
        }
    }

    $stmtLatest = $pdo->prepare("SELECT tanggal_closing, jam_closing
                                 FROM kasir_transactions_closing_kasir
                                 WHERE kode_cabang = ?
                                   AND status = 'end proses'
                                   AND tanggal_closing IS NOT NULL
                                 ORDER BY tanggal_closing DESC, jam_closing DESC
                                 LIMIT 1");
    $stmtLatest->execute([$kodeCabangPenerima]);
    $latestRow = $stmtLatest->fetch(PDO::FETCH_ASSOC);

    if ($latestRow && !empty($latestRow['tanggal_closing'])) {
        return $latestRow['tanggal_closing'] . ' ' . ($latestRow['jam_closing'] ?: '00:00:00');
    }

    return null;
}

function getPengambilanExpectedTransferAccounts(PDO $pdo, string $kodePengambilan): array
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode pengambilan tidak ditemukan.');
    }

    $rekeningPemberi = normalizePengambilanNoRekening($root['no_rekening_peminjam'] ?? '');
    $rekeningPenerima = normalizePengambilanNoRekening($root['no_rekening_penerima'] ?? '');

    return [
        'kode_pengambilan' => $root['kode_pengambilan'],
        'klasifikasi' => resolvePengambilanClassification($root),
        'rekening_pemberi_pinjaman' => $rekeningPemberi,
        'rekening_penerima_pinjaman' => $rekeningPenerima,
        'rekening_pengirim_expected' => $rekeningPenerima,
        'rekening_penerima_expected' => $rekeningPemberi,
        'remaining_amount' => getPengambilanRemainingAmount($pdo, $root['kode_pengambilan']),
        'nama_cabang_keuangan' => $root['nama_cabang_keuangan'] ?? null,
        'kode_cabang_penerima' => $root['kode_cabang_penerima'] ?? null,
    ];
}

function isPengambilanNominalLocked(array $record): bool
{
    if (($record['status_setor_bank'] ?? '') === 'disetor') {
        return true;
    }

    if ((int)($record['verified_cabang_penerima_sudah_closing'] ?? 0) === 1) {
        return true;
    }

    if (!empty($record['verified_cabang_closing_at'])) {
        return true;
    }

    return false;
}

function validateDetectedPelunasanDocument(PDO $pdo, string $kodePengambilan, array $parsedDocument, ?float $submittedAmount = null): array
{
    $expected = getPengambilanExpectedTransferAccounts($pdo, $kodePengambilan);
    $detectedSender = normalizePengambilanNoRekening((string)($parsedDocument['pengirim'] ?? ''));
    $detectedReceiver = normalizePengambilanNoRekening((string)($parsedDocument['penerima'] ?? ''));
    $detectedNominal = (float)preg_replace('/[^\d]/', '', (string)($parsedDocument['nominal'] ?? '0'));

    $errors = [];
    $matches = [
        'sender' => $detectedSender !== '' && $detectedSender === $expected['rekening_pengirim_expected'],
        'receiver' => $detectedReceiver !== '' && $detectedReceiver === $expected['rekening_penerima_expected'],
        'nominal_limit' => $detectedNominal > 1000 && $detectedNominal <= (float)$expected['remaining_amount'],
    ];

    if (!$matches['sender']) {
        $errors[] = 'Nomor rekening pengirim pada dokumen tidak sesuai dengan no rek penerima pinjaman.';
    }

    if (!$matches['receiver']) {
        $errors[] = 'Nomor rekening penerima pada dokumen tidak sesuai dengan no rek pemberi pinjaman.';
    }

    if ($detectedNominal <= 1000) {
        $errors[] = 'Nominal transfer pada dokumen tidak valid.';
    } elseif ($detectedNominal > (float)$expected['remaining_amount']) {
        $errors[] = 'Nominal transfer melebihi sisa hutang Rp ' . number_format((float)$expected['remaining_amount'], 0, ',', '.') . '.';
    }

    if ($submittedAmount !== null && $submittedAmount > $detectedNominal) {
        $errors[] = 'Jumlah yang diproses tidak boleh lebih besar dari nominal yang terbaca pada dokumen.';
    }

    return [
        'ok' => empty($errors),
        'errors' => $errors,
        'expected' => $expected,
        'detected' => [
            'pengirim' => $detectedSender !== '' ? $detectedSender : null,
            'penerima' => $detectedReceiver !== '' ? $detectedReceiver : null,
            'nominal' => $detectedNominal > 0 ? $detectedNominal : null,
        ],
        'matches' => $matches,
    ];
}

function getPelunasanDocumentStorageRoot(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pelunasan_hutang';
}

function ensurePelunasanDocumentDirectory(string $directory): void
{
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Gagal membuat direktori penyimpanan dokumen.');
    }
}

function storePelunasanDocumentToken(array $payload): string
{
    $token = bin2hex(random_bytes(18));
    $tmpDir = getPelunasanDocumentStorageRoot() . DIRECTORY_SEPARATOR . 'tmp';
    ensurePelunasanDocumentDirectory($tmpDir);

    $payload['token'] = $token;
    $payload['created_at'] = date('c');
    $payload['expires_at'] = date('c', time() + 3600);

    $target = $tmpDir . DIRECTORY_SEPARATOR . $token . '.json';
    if (file_put_contents($target, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
        throw new RuntimeException('Gagal menyimpan token dokumen sementara.');
    }

    return $token;
}

function loadPelunasanDocumentToken(string $token): ?array
{
    $token = preg_replace('/[^a-f0-9]/i', '', $token) ?? '';
    if ($token === '') {
        return null;
    }

    $path = getPelunasanDocumentStorageRoot() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $token . '.json';
    if (!is_file($path)) {
        return null;
    }

    $payload = json_decode((string)file_get_contents($path), true);
    if (!is_array($payload)) {
        return null;
    }

    if (!empty($payload['expires_at']) && strtotime((string)$payload['expires_at']) < time()) {
        @unlink($path);
        if (!empty($payload['stored_file']) && is_file((string)$payload['stored_file'])) {
            @unlink((string)$payload['stored_file']);
        }
        return null;
    }

    return $payload;
}

function finalizePelunasanDocumentToken(string $token, string $kodePengambilan): array
{
    $payload = loadPelunasanDocumentToken($token);
    if (!$payload) {
        throw new RuntimeException('Dokumen pelunasan tidak ditemukan atau token sudah kedaluwarsa.');
    }

    $sourcePath = $payload['stored_file'] ?? '';
    if (!is_string($sourcePath) || !is_file($sourcePath)) {
        throw new RuntimeException('File dokumen sementara tidak ditemukan.');
    }

    $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION) ?: pathinfo((string)($payload['original_name'] ?? ''), PATHINFO_EXTENSION));
    $relativeDirectory = 'uploads/pelunasan_hutang/' . date('Y/m');
    $targetDirectory = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDirectory);
    ensurePelunasanDocumentDirectory($targetDirectory);

    $safeName = preg_replace('/[^A-Za-z0-9._-]/', '-', (string)($payload['original_name'] ?? ('mutasi.' . $extension))) ?: ('mutasi.' . $extension);
    $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $kodePengambilan . '-' . date('YmdHis') . '-' . $safeName;

    if (!@rename($sourcePath, $targetPath)) {
        if (!@copy($sourcePath, $targetPath)) {
            throw new RuntimeException('Gagal memindahkan dokumen pelunasan ke penyimpanan utama.');
        }
        @unlink($sourcePath);
    }

    $tokenPath = getPelunasanDocumentStorageRoot() . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . ($payload['token'] ?? $token) . '.json';
    if (is_file($tokenPath)) {
        @unlink($tokenPath);
    }

    return [
        'dokumen_path' => $relativeDirectory . '/' . basename($targetPath),
        'dokumen_nama_asli' => $payload['original_name'] ?? basename($targetPath),
        'dokumen_mime' => $payload['mime_type'] ?? 'application/octet-stream',
        'dokumen_tipe' => $payload['file_type'] ?? strtolower($extension),
        'rekening_pengirim_terdeteksi' => $payload['parsed']['pengirim'] ?? null,
        'rekening_penerima_terdeteksi' => $payload['parsed']['penerima'] ?? null,
        'nominal_terdeteksi' => isset($payload['parsed']['nominal']) ? (float)$payload['parsed']['nominal'] : null,
        'confidence' => $payload['parsed']['confidence'] ?? 'low',
        'hasil_ekstraksi_json' => json_encode([
            'parsed' => $payload['parsed'] ?? [],
            'validation' => $payload['validation'] ?? [],
            'expected' => $payload['expected'] ?? [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ];
}

function cleanupPelunasanDocumentPath(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    $absolutePath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    if (is_file($absolutePath)) {
        @unlink($absolutePath);
    }
}

function isPelunasanDocumentReferencedInPayments(PDO $pdo, ?string $relativePath): bool
{
    $relativePath = trim((string)$relativePath);
    if ($relativePath === '') {
        return false;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pengambilan_setoran_pembayaran_closing_kasir WHERE dokumen_path = ?");
    $stmt->execute([$relativePath]);
    return (int)$stmt->fetchColumn() > 0;
}

function getPengambilanStoredMutasiMetaFromRecord(array $record): ?array
{
    $path = trim((string)($record['mutasi_dokumen_path'] ?? ''));
    if ($path === '') {
        return null;
    }

    return [
        'dokumen_path' => $path,
        'dokumen_nama_asli' => $record['mutasi_dokumen_nama_asli'] ?? null,
        'dokumen_mime' => $record['mutasi_dokumen_mime'] ?? null,
        'dokumen_tipe' => $record['mutasi_dokumen_tipe'] ?? null,
        'rekening_pengirim_terdeteksi' => $record['mutasi_rekening_pengirim'] ?? null,
        'rekening_penerima_terdeteksi' => $record['mutasi_rekening_penerima'] ?? null,
        'nominal_terdeteksi' => isset($record['mutasi_nominal_terdeteksi']) ? (float)$record['mutasi_nominal_terdeteksi'] : null,
        'confidence' => $record['mutasi_confidence'] ?? null,
        'hasil_ekstraksi_json' => $record['mutasi_hasil_json'] ?? null,
        'verified_by' => $record['mutasi_verified_by'] ?? null,
        'verified_at' => $record['mutasi_verified_at'] ?? null,
    ];
}

function getPengambilanStoredMutasiVerification(PDO $pdo, string $kodePengambilan): array
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode pengambilan tidak ditemukan.');
    }

    $meta = getPengambilanStoredMutasiMetaFromRecord($root);
    $classification = resolvePengambilanClassification($root);
    $ready = $classification !== 'hutang'
        ? true
        : ($meta !== null && (int)($root['verified_mutasi_bank'] ?? 0) === 1);

    return [
        'root' => $root,
        'classification' => $classification,
        'ready' => $ready,
        'document_meta' => $meta,
        'remaining_amount' => getPengambilanRemainingAmount($pdo, $root['kode_pengambilan']),
    ];
}

function savePengambilanMutasiVerification(PDO $pdo, string $kodePengambilan, array $documentMeta, string $kodeKaryawanInput): array
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode pengambilan tidak ditemukan.');
    }

    if (resolvePengambilanClassification($root) !== 'hutang') {
        throw new RuntimeException('Upload mutasi hanya diperlukan untuk pengambilan dengan status hutang.');
    }

    $oldPath = $root['mutasi_dokumen_path'] ?? null;

    $stmt = $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                           SET mutasi_dokumen_path = ?,
                               mutasi_dokumen_nama_asli = ?,
                               mutasi_dokumen_mime = ?,
                               mutasi_dokumen_tipe = ?,
                               mutasi_rekening_pengirim = ?,
                               mutasi_rekening_penerima = ?,
                               mutasi_nominal_terdeteksi = ?,
                               mutasi_confidence = ?,
                               mutasi_hasil_json = ?,
                               mutasi_verified_by = ?,
                               mutasi_verified_at = NOW(),
                               verified_mutasi_bank = 1
                           WHERE kode_pengambilan = ?
                             AND parent_kode_pengambilan IS NULL");
    $stmt->execute([
        $documentMeta['dokumen_path'] ?? null,
        $documentMeta['dokumen_nama_asli'] ?? null,
        $documentMeta['dokumen_mime'] ?? null,
        $documentMeta['dokumen_tipe'] ?? null,
        $documentMeta['rekening_pengirim_terdeteksi'] ?? null,
        $documentMeta['rekening_penerima_terdeteksi'] ?? null,
        $documentMeta['nominal_terdeteksi'] ?? null,
        $documentMeta['confidence'] ?? null,
        $documentMeta['hasil_ekstraksi_json'] ?? null,
        $kodeKaryawanInput,
        $root['kode_pengambilan'],
    ]);

    if (!empty($oldPath) && $oldPath !== ($documentMeta['dokumen_path'] ?? null) && !isPelunasanDocumentReferencedInPayments($pdo, $oldPath)) {
        cleanupPelunasanDocumentPath($oldPath);
    }

    // Auto-create payment record if debt hasn't been paid, since Pemasukan Kasir no longer does it.
    $remainingBefore = getPengambilanRemainingAmount($pdo, $root['kode_pengambilan']);
    if ($remainingBefore > 0) {
        $nominal = (float)($documentMeta['nominal_terdeteksi'] ?? $remainingBefore);
        if ($nominal <= 0 || $nominal > $remainingBefore) {
            $nominal = $remainingBefore; // Default to full remaining amount if detection fails or overpays
        }
        
        $pdo->prepare("INSERT INTO pengambilan_setoran_pembayaran_closing_kasir 
            (kode_pengambilan, nominal_dibayar, kode_validasi_input, kode_karyawan_input, catatan) 
            VALUES (?, ?, ?, ?, 'Pelunasan via Upload Mutasi OCR')")
            ->execute([$root['kode_pengambilan'], $nominal, $root['kode_pengambilan'], $kodeKaryawanInput]);
            
        syncPengambilanNominalStatus($pdo, $root['kode_pengambilan']);
    }

    checkAndUpdatePengadaanSelesai($pdo, $root['kode_pengambilan']);

    return getPengambilanStoredMutasiVerification($pdo, $root['kode_pengambilan']);
}

function clearPengambilanMutasiVerification(PDO $pdo, string $kodePengambilan, bool $resetVerifiedFlag = true): void
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return;
    }

    $sql = "UPDATE pengambilan_setoran_closing_kasir
            SET mutasi_dokumen_path = NULL,
                mutasi_dokumen_nama_asli = NULL,
                mutasi_dokumen_mime = NULL,
                mutasi_dokumen_tipe = NULL,
                mutasi_rekening_pengirim = NULL,
                mutasi_rekening_penerima = NULL,
                mutasi_nominal_terdeteksi = NULL,
                mutasi_confidence = NULL,
                mutasi_hasil_json = NULL,
                mutasi_verified_by = NULL,
                mutasi_verified_at = NULL";
    if ($resetVerifiedFlag) {
        $sql .= ",
                verified_mutasi_bank = 0";
    }
    $sql .= "
            WHERE kode_pengambilan = ?
              AND parent_kode_pengambilan IS NULL";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$root['kode_pengambilan']]);
}

function processPengambilanMutasiUpload(PDO $pdo, string $kodePengambilan, array $uploadedFile, string $kodeKaryawanInput): array
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode validasi pengambilan tidak ditemukan.');
    }

    if (resolvePengambilanClassification($root) !== 'hutang') {
        throw new RuntimeException('Upload mutasi hanya digunakan untuk pengambilan hutang.');
    }

    if (($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload dokumen gagal. Kode error: ' . (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE));
    }

    $originalName = (string)($uploadedFile['name'] ?? 'dokumen');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, ['pdf', 'docx', 'jpg', 'jpeg', 'png'], true)) {
        throw new RuntimeException('File harus berupa PDF, DOCX, JPG, JPEG, atau PNG.');
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
        throw new RuntimeException('Gagal menyimpan file upload sementara.');
    }

    if (!$moved && is_file($tmpName)) {
        @unlink($tmpName);
    }

    $reader = new DocumentReader();
    $parsed = $reader->read($storedFile);
    $validation = validateDetectedPelunasanDocument($pdo, $root['kode_pengambilan'], $parsed, null);

    if (!$validation['ok']) {
        @unlink($storedFile);
        throw new RuntimeException(implode(' ', $validation['errors']));
    }

    $token = storePelunasanDocumentToken([
        'stored_file' => $storedFile,
        'original_name' => $originalName,
        'mime_type' => (string)($uploadedFile['type'] ?? 'application/octet-stream'),
        'file_type' => $parsed['file_type'] ?? $extension,
        'parsed' => $parsed,
        'validation' => $validation,
        'expected' => $validation['expected'] ?? [],
        'uploaded_by' => $kodeKaryawanInput,
    ]);

    $documentMeta = finalizePelunasanDocumentToken($token, $root['kode_pengambilan']);
    $stored = savePengambilanMutasiVerification($pdo, $root['kode_pengambilan'], $documentMeta, $kodeKaryawanInput);
    $stored['parsed'] = $parsed;
    $stored['validation'] = $validation;

    return $stored;
}

function canProcessSetoran(PDO $pdo, string $noRekeningPeminjam, ?string $kodeCabangPenerimaIntended = null, array $excludeKodePengambilan = []): array
{
    $noRekeningPeminjam = normalizePengambilanNoRekening($noRekeningPeminjam);
    if ($noRekeningPeminjam === '') {
        return ['ok' => false, 'reason' => 'Nomor rekening peminjam tidak ditemukan.'];
    }

    $excludeSql = '';
    $excludeParams = [];
    if (!empty($excludeKodePengambilan)) {
        $placeholders = implode(',', array_fill(0, count($excludeKodePengambilan), '?'));
        $excludeSql = " AND kode_pengambilan NOT IN ($placeholders)";
        $excludeParams = array_values($excludeKodePengambilan);
    }

    $stmt = $pdo->prepare("SELECT kode_pengambilan
                           FROM pengambilan_setoran_closing_kasir
                           WHERE no_rekening_peminjam = ?
                             AND klasifikasi = 'hutang'
                             AND status != 'selesai'"
                           . $excludeSql . "
                           ORDER BY created_at ASC");
    $stmt->execute(array_merge([$noRekeningPeminjam], $excludeParams));
    $rekeningBlocking = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (!empty($rekeningBlocking)) {
        return [
            'ok' => false,
            'reason' => 'Ada hutang belum selesai dari rekening ini: ' . implode(', ', $rekeningBlocking),
            'blocking_codes' => $rekeningBlocking,
        ];
    }

    if ($kodeCabangPenerimaIntended) {
        $stmtCabang = $pdo->prepare("SELECT kode_pengambilan
                                     FROM pengambilan_setoran_closing_kasir
                                     WHERE kode_cabang_penerima = ?
                                       AND status != 'selesai'"
                                     . $excludeSql . "
                                     ORDER BY created_at ASC");
        $stmtCabang->execute(array_merge([$kodeCabangPenerimaIntended], $excludeParams));
        $cabangBlocking = $stmtCabang->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($cabangBlocking)) {
            return [
                'ok' => false,
                'reason' => 'Cabang gudang ini masih punya pengambilan belum selesai: ' . implode(', ', $cabangBlocking),
                'blocking_codes' => $cabangBlocking,
            ];
        }
    }

    return ['ok' => true];
}

function syncPengambilanNominalStatus(PDO $pdo, string $kodePengambilan): void
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return;
    }

    $klasifikasi = resolvePengambilanClassification($root);

    if ($klasifikasi === 'hutang') {
        // Untuk hutang, checklist nominal harus mengikuti pemasukan kasir yang sudah terinput.
        // Upload mutasi tetap dipakai khusus untuk status pelunasan hutang, bukan untuk mengunci
        // tampilnya nominal kas masuk di histori pengambilan.
        $nominalVerified = (
            (int)($root['verified_notrx_kas_masuk'] ?? 0) === 1
            || !empty($root['id_pemasukan_gudang'])
        ) ? 1 : 0;
    } else {
        // Untuk internal: cek apakah total pembayaran sudah >= nominal diambil
        $paidAmount = getPengambilanPaidAmount($pdo, $root['kode_pengambilan']);
        $remaining = max(0.0, round((float)$root['nominal_diambil'] - $paidAmount, 2));
        $nominalVerified = $remaining <= 0.0 ? 1 : 0;
    }

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET verified_nominal_kas_masuk = ?
                   WHERE kode_pengambilan = ?")
        ->execute([$nominalVerified, $root['kode_pengambilan']]);
}

function syncPengambilanChildStatus(PDO $pdo, string $kodePengambilan, int $pemasukanId): void
{
    $stmt = $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                           SET verified_nominal_kas_masuk = 1,
                               verified_notrx_kas_masuk = 1,
                               verified_cabang_penerima_sudah_closing = 1,
                               verified_cabang_closing_at = COALESCE(verified_cabang_closing_at, NOW()),
                               verified_mutasi_bank = 1,
                               status = 'selesai',
                               id_pemasukan_gudang = COALESCE(id_pemasukan_gudang, ?)
                           WHERE kode_pengambilan = ?
                             AND parent_kode_pengambilan IS NOT NULL");
    $stmt->execute([$pemasukanId, $kodePengambilan]);
}

function updatePengadaanVerification(PDO $pdo, string $kodePengambilan, int $pemasukanId, float $nominalInput, string $kodeKaryawanInput = '', ?string $catatan = null, ?array $documentMeta = null): int
{
    $stmt = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1");
    $stmt->execute([$kodePengambilan]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        throw new RuntimeException('Kode validasi pengambilan tidak ditemukan.');
    }

    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root || $root['status'] === 'selesai') {
        throw new RuntimeException('Pengambilan ini sudah selesai diproses.');
    }

    if ($nominalInput <= 0) {
        throw new RuntimeException('Nominal input harus lebih besar dari nol.');
    }

    $remainingBefore = getPengambilanRemainingAmount($pdo, $root['kode_pengambilan']);
    if ($remainingBefore <= 0.0) {
        throw new RuntimeException('Pengambilan ini sudah tidak memiliki sisa hutang.');
    }

    if ($nominalInput > $remainingBefore) {
        throw new RuntimeException('Nominal melebihi sisa hutang Rp ' . number_format($remainingBefore, 0, ',', '.') . '. Silakan input ulang.');
    }

    // OCR tidak wajib lagi — P8: validasi keuangan dihapus dari alur pemasukan kasir

    $klasifikasi = resolvePengambilanClassification($root);
    $paymentId = 0;

    // Untuk 'hutang', pemasukan kasir hanya verifikasi penerimaan dana (tidak mengurangi sisa hutang)
    // Pelunasan hutang dilakukan terpisah oleh keuangan via setoran_keuangan_closing_kasir.php
    if ($klasifikasi !== 'hutang') {
        $stmtInsert = $pdo->prepare("INSERT INTO pengambilan_setoran_pembayaran_closing_kasir
            (kode_pengambilan, pemasukan_kasir_id, nominal_dibayar, kode_validasi_input, kode_karyawan_input, catatan,
             dokumen_path, dokumen_nama_asli, dokumen_mime, dokumen_tipe, rekening_pengirim_terdeteksi, rekening_penerima_terdeteksi,
             nominal_terdeteksi, confidence, hasil_ekstraksi_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtInsert->execute([
            $kodePengambilan,
            $pemasukanId,
            $nominalInput,
            $kodePengambilan,
            $kodeKaryawanInput !== '' ? $kodeKaryawanInput : ($record['kode_karyawan_input'] ?? ''),
            $catatan,
            $documentMeta['dokumen_path'] ?? null,
            $documentMeta['dokumen_nama_asli'] ?? null,
            $documentMeta['dokumen_mime'] ?? null,
            $documentMeta['dokumen_tipe'] ?? null,
            $documentMeta['rekening_pengirim_terdeteksi'] ?? null,
            $documentMeta['rekening_penerima_terdeteksi'] ?? null,
            $documentMeta['nominal_terdeteksi'] ?? null,
            $documentMeta['confidence'] ?? null,
            $documentMeta['hasil_ekstraksi_json'] ?? null,
        ]);
        $paymentId = (int)$pdo->lastInsertId();
    }

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET id_pemasukan_gudang = COALESCE(id_pemasukan_gudang, ?),
                       verified_notrx_kas_masuk = 1
                   WHERE kode_pengambilan = ?")
        ->execute([$pemasukanId, $kodePengambilan]);

    if (!empty($record['parent_kode_pengambilan'])) {
        syncPengambilanChildStatus($pdo, $kodePengambilan, $pemasukanId);
    }

    syncPengambilanNominalStatus($pdo, $root['kode_pengambilan']);

    $remainingAfter = getPengambilanRemainingAmount($pdo, $root['kode_pengambilan']);
    if (resolvePengambilanClassification($root) === 'hutang' && $remainingAfter > 0) {
        clearPengambilanMutasiVerification($pdo, $root['kode_pengambilan'], true);
    }

    checkAndUpdatePengadaanSelesai($pdo, $root['kode_pengambilan']);

    // Jaga-jaga: kalau cabang penerima sudah closing duluan sebelum kasir input, langsung sinkron di sini juga
    if (!empty($root['kode_cabang_penerima'])) {
        try {
            syncPengambilanClosingFromKasirStatus($pdo, $root['kode_cabang_penerima']);
        } catch (Throwable $syncEx) {
            error_log('syncPengambilanClosingFromKasirStatus (post-input): ' . $syncEx->getMessage());
        }
    }

    return $paymentId;
}

function markPengadaanNoTrxVerified(PDO $pdo, string $kodePengambilan): void
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return;
    }

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET verified_notrx_kas_masuk = 1
                   WHERE kode_pengambilan = ?
                     AND parent_kode_pengambilan IS NULL
                     AND status != 'selesai'")
        ->execute([$root['kode_pengambilan']]);

    checkAndUpdatePengadaanSelesai($pdo, $root['kode_pengambilan']);
}

function markPengadaanClosingVerified(PDO $pdo, string $kodePengambilan): void
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return;
    }

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET verified_cabang_penerima_sudah_closing = 1,
                       verified_cabang_closing_at = NOW()
                   WHERE kode_pengambilan = ?
                     AND parent_kode_pengambilan IS NULL
                     AND status != 'selesai'")
        ->execute([$root['kode_pengambilan']]);

    checkAndUpdatePengadaanSelesai($pdo, $root['kode_pengambilan']);
}

function syncPengambilanClosingFromKasirStatus(PDO $pdo, string $kodeCabangPenerima): void
{
    $stmt = $pdo->prepare("SELECT DISTINCT ps.kode_pengambilan,
                                   kt.tanggal_closing, kt.jam_closing
                            FROM pengambilan_setoran_closing_kasir ps
                            JOIN pemasukan_kasir_closing_kasir pk ON pk.kode_pengambilan_ref = ps.kode_pengambilan
                            JOIN kasir_transactions_closing_kasir kt ON kt.kode_transaksi = pk.kode_transaksi
                            WHERE ps.kode_cabang_penerima = ?
                              AND ps.parent_kode_pengambilan IS NULL
                              AND ps.status != 'selesai'
                              AND ps.verified_cabang_penerima_sudah_closing = 0
                              AND kt.status = 'end proses'");
    $stmt->execute([$kodeCabangPenerima]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $kodePengambilan = $row['kode_pengambilan'];
        $closingAt = (!empty($row['tanggal_closing']) && !empty($row['jam_closing']))
            ? $row['tanggal_closing'] . ' ' . $row['jam_closing']
            : null;

        $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                       SET verified_cabang_penerima_sudah_closing = 1,
                           verified_cabang_closing_at = COALESCE(?, NOW())
                       WHERE kode_pengambilan = ?
                         AND parent_kode_pengambilan IS NULL
                         AND status != 'selesai'")
            ->execute([$closingAt, $kodePengambilan]);

        checkAndUpdatePengadaanSelesai($pdo, $kodePengambilan);

        try {
            markPengambilanReadyForBank($pdo, $kodePengambilan);
        } catch (Throwable $readyEx) {
            error_log('markPengambilanReadyForBank (auto-sync): ' . $readyEx->getMessage());
        }
    }
}

function markPengadaanMutasiBankVerified(PDO $pdo, string $kodePengambilan): void
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return;
    }

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET verified_mutasi_bank = 1
                   WHERE kode_pengambilan = ?
                     AND parent_kode_pengambilan IS NULL
                     AND status != 'selesai'")
        ->execute([$root['kode_pengambilan']]);

    checkAndUpdatePengadaanSelesai($pdo, $root['kode_pengambilan']);
}

function checkAndUpdatePengadaanSelesai(PDO $pdo, string $kodePengambilan): void
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        return;
    }

    syncPengambilanNominalStatus($pdo, $root['kode_pengambilan']);

    $stmt = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1");
    $stmt->execute([$root['kode_pengambilan']]);
    $root = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$root) {
        return;
    }

    $klasifikasi = resolvePengambilanClassification($root);
    
    $allVerified = (int)($root['verified_nominal_kas_masuk'] ?? 0) === 1
        && (int)($root['verified_notrx_kas_masuk'] ?? 0) === 1
        && (int)($root['verified_cabang_penerima_sudah_closing'] ?? 0) === 1;

    if ($klasifikasi === 'hutang') {
        $allVerified = $allVerified && (int)($root['verified_mutasi_bank'] ?? 0) === 1;
    }

    $nextStatus = $allVerified ? 'selesai' : ($klasifikasi === 'hutang' ? 'hutang' : 'proses');

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET status = ?
                   WHERE kode_pengambilan = ?
                     AND parent_kode_pengambilan IS NULL")
        ->execute([$nextStatus, $root['kode_pengambilan']]);
}

function createPengambilanChild(PDO $pdo, string $parentKodePengambilan, float $nominalTambahan, string $tanggalPerencanaanSetor, string $kodeKaryawanInput, string $keterangan = ''): array
{
    $stmt = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1");
    $stmt->execute([$parentKodePengambilan]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$parent) {
        throw new RuntimeException('Pengambilan parent tidak ditemukan.');
    }

    if (!empty($parent['parent_kode_pengambilan'])) {
        throw new RuntimeException('Tambahan pembayaran hanya bisa dibuat dari record induk.');
    }

    $remaining = getPengambilanRemainingAmount($pdo, $parentKodePengambilan);
    if ($remaining <= 0.0) {
        throw new RuntimeException('Hutang ini sudah lunas.');
    }

    if ($nominalTambahan <= 0.0) {
        throw new RuntimeException('Nominal tambahan harus lebih besar dari nol.');
    }

    if ($nominalTambahan > $remaining) {
        throw new RuntimeException('Nominal tambahan melebihi sisa hutang Rp ' . number_format($remaining, 0, ',', '.') . '.');
    }

    $kodeBaru = generateKodePengambilan($pdo, $parentKodePengambilan);
    $statusBaru = resolvePengambilanClassification($parent) === 'hutang' ? 'hutang' : 'proses';

    $stmtInsert = $pdo->prepare("INSERT INTO pengambilan_setoran_closing_kasir
        (kode_pengambilan, nominal_diambil, nominal_sisa, jenis_rekening, klasifikasi,
         no_rekening_peminjam, no_rekening_penerima, kode_cabang_penerima, tanggal_perencanaan_setor,
         parent_kode_pengambilan, status, kode_cabang_keuangan, nama_cabang_keuangan, kode_karyawan_input, keterangan)
        VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtInsert->execute([
        $kodeBaru,
        $nominalTambahan,
        $parent['jenis_rekening'],
        resolvePengambilanClassification($parent),
        $parent['no_rekening_peminjam'],
        $parent['no_rekening_penerima'],
        $parent['kode_cabang_penerima'],
        $tanggalPerencanaanSetor,
        $parentKodePengambilan,
        $statusBaru,
        $parent['kode_cabang_keuangan'] ?? null,
        $parent['nama_cabang_keuangan'] ?? null,
        $kodeKaryawanInput,
        $keterangan,
    ]);

    $stmtNew = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1");
    $stmtNew->execute([$kodeBaru]);

    return $stmtNew->fetch(PDO::FETCH_ASSOC) ?: [];
}

function fetchPengambilanSummary(PDO $pdo, ?string $kodeCabangPenerima = null): array
{
    $summary = [
        'proses' => ['cnt' => 0, 'total' => 0.0],
        'hutang' => ['cnt' => 0, 'total' => 0.0],
        'selesai' => ['cnt' => 0, 'total' => 0.0],
    ];

    $sql = "SELECT status, COUNT(*) AS cnt, COALESCE(SUM(nominal_diambil), 0) AS total
            FROM pengambilan_setoran_closing_kasir
            WHERE 1=1";
    $params = [];

    if ($kodeCabangPenerima) {
        $sql .= " AND kode_cabang_penerima = ?";
        $params[] = $kodeCabangPenerima;
    }

    $sql .= " GROUP BY status";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $summary[$row['status']] = [
            'cnt' => (int)$row['cnt'],
            'total' => (float)$row['total'],
        ];
    }

    return $summary;
}

function fetchPengambilanRows(PDO $pdo, array $filters = []): array
{
    $sql = "SELECT ps.*,
                   u.nama_lengkap AS nama_input,
                   cp.nama_cabang AS nama_cabang_penerima,
                   pk_ref.jumlah         AS jumlah_pemasukan_aktual,
                   pk_ref.kode_transaksi AS no_trx_pemasukan_aktual,
                   psp_latest.no_transaksi       AS no_transaksi_pelunasan,
                   psp_latest.kode_validasi_input AS kode_validasi_pelunasan,
                   psp_latest.tanggal_transfer    AS tanggal_transfer_pelunasan,
                   psp_latest.nominal_dibayar     AS nominal_dibayar_pelunasan,
                   psp_latest.sumber              AS sumber_pelunasan
            FROM pengambilan_setoran_closing_kasir ps
            LEFT JOIN tbuser u ON ps.kode_karyawan_input = u.kode_karyawan
            LEFT JOIN tbcabang cp ON cp.cabang_ref_kode = ps.kode_cabang_penerima
            LEFT JOIN pemasukan_kasir_closing_kasir pk_ref
                ON pk_ref.kode_pengambilan_ref = ps.kode_pengambilan
                AND pk_ref.id = ps.id_pemasukan_gudang
            LEFT JOIN pengambilan_setoran_pembayaran_closing_kasir psp_latest
                ON psp_latest.kode_pengambilan = ps.kode_pengambilan
                AND psp_latest.id = (
                    SELECT MAX(id) FROM pengambilan_setoran_pembayaran_closing_kasir
                    WHERE kode_pengambilan = ps.kode_pengambilan
                )
            WHERE 1=1";
    $params = [];

    if (!empty($filters['klasifikasi']) && in_array($filters['klasifikasi'], ['internal', 'hutang'], true)) {
        $sql .= " AND ps.klasifikasi = ?";
        $params[] = $filters['klasifikasi'];
    }

    if (!empty($filters['status']) && $filters['status'] !== 'all') {
        $sql .= " AND ps.status = ?";
        $params[] = $filters['status'];
    }

    if (!empty($filters['tanggal_awal'])) {
        $sql .= " AND DATE(ps.created_at) >= ?";
        $params[] = $filters['tanggal_awal'];
    }

    if (!empty($filters['tanggal_akhir'])) {
        $sql .= " AND DATE(ps.created_at) <= ?";
        $params[] = $filters['tanggal_akhir'];
    }

    if (!empty($filters['kode_cabang_penerima'])) {
        $sql .= " AND ps.kode_cabang_penerima = ?";
        $params[] = $filters['kode_cabang_penerima'];
    }

    if (!empty($filters['parent_only'])) {
        $sql .= " AND ps.parent_kode_pengambilan IS NULL";
    }

    $sql .= " ORDER BY ps.created_at DESC, ps.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['resolved_klasifikasi'] = resolvePengambilanClassification($row);
        $row['is_child'] = !empty($row['parent_kode_pengambilan']);
        $row['nominal_terbayar'] = getPengambilanPaidAmount($pdo, $row['kode_pengambilan']);
        $row['nominal_sisa_hutang'] = getPengambilanRemainingAmount($pdo, $row['kode_pengambilan']);
        $row['resolved_verified_cabang_closing_at'] = $row['verified_cabang_closing_at'] ?? null;

        if ((int)($row['verified_cabang_penerima_sudah_closing'] ?? 0) === 1 && empty($row['resolved_verified_cabang_closing_at'])) {
            $row['resolved_verified_cabang_closing_at'] = inferPengambilanClosingTimestamp(
                $pdo,
                $row['kode_cabang_penerima'] ?? '',
                $row['created_at'] ?? null
            );
        }
    }
    unset($row);

    return $rows;
}

function fetchPengambilanPayments(PDO $pdo, string $rootKodePengambilan): array
{
    $codes = getPengambilanGroupCodes($pdo, $rootKodePengambilan);
    $placeholders = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare("SELECT psp.*,
                                  u.nama_lengkap AS nama_input
                           FROM pengambilan_setoran_pembayaran_closing_kasir psp
                           LEFT JOIN tbuser u ON psp.kode_karyawan_input = u.kode_karyawan
                           WHERE psp.kode_pengambilan IN ($placeholders)
                           ORDER BY psp.created_at ASC, psp.id ASC");
    $stmt->execute($codes);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchOtherHutangByRekening(PDO $pdo, string $noRekeningPeminjam, string $excludeKodePengambilan): array
{
    if (normalizePengambilanNoRekening($noRekeningPeminjam) === '') {
        return [];
    }

    $stmt = $pdo->prepare("SELECT kode_pengambilan, nominal_diambil, status
                           FROM pengambilan_setoran_closing_kasir
                           WHERE no_rekening_peminjam = ?
                             AND klasifikasi = 'hutang'
                             AND parent_kode_pengambilan IS NULL
                             AND kode_pengambilan != ?
                           ORDER BY created_at DESC");
    $stmt->execute([$noRekeningPeminjam, $excludeKodePengambilan]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function fetchHutangRootRows(PDO $pdo, array $filters = []): array
{
    $filters['klasifikasi'] = 'hutang';
    $filters['parent_only'] = true;
    $rows = fetchPengambilanRows($pdo, $filters);

    foreach ($rows as &$row) {
        $row['payments'] = fetchPengambilanPayments($pdo, $row['kode_pengambilan']);
        $row['other_hutang'] = fetchOtherHutangByRekening($pdo, $row['no_rekening_peminjam'] ?? '', $row['kode_pengambilan']);
        $row['mutasi_ready'] = !empty($row['mutasi_dokumen_path']) && (int)($row['verified_mutasi_bank'] ?? 0) === 1;
    }
    unset($row);

    return $rows;
}

function getPengambilanBlockedCabangMap(PDO $pdo): array
{
    $stmt = $pdo->query("SELECT kode_cabang_penerima, GROUP_CONCAT(kode_pengambilan ORDER BY created_at SEPARATOR ', ') AS blocking_codes
                         FROM pengambilan_setoran_closing_kasir
                         WHERE kode_cabang_penerima IS NOT NULL
                           AND status != 'selesai'
                         GROUP BY kode_cabang_penerima");

    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[$row['kode_cabang_penerima']] = $row['blocking_codes'];
    }

    return $map;
}

// canProcessSetoran() kept for backward compat but no longer enforced in UI flow (P1/P10)
// function canProcessSetoran is defined above — it still works but should not be called as a gate

function markPengambilanReadyForBank(PDO $pdo, string $kodePengambilan): void
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode pengambilan tidak ditemukan.');
    }

    if ((int)($root['verified_nominal_kas_masuk'] ?? 0) !== 1) {
        throw new RuntimeException('Pemasukan kasir dari keuangan belum diinput oleh cabang penerima (Nominal Kas Masuk belum terverifikasi).');
    }

    if ((int)($root['verified_notrx_kas_masuk'] ?? 0) !== 1) {
        throw new RuntimeException('Nomor transaksi pemasukan kasir belum terkonfirmasi (No Trx Kas Masuk belum terverifikasi).');
    }

    if ((int)($root['verified_cabang_penerima_sudah_closing'] ?? 0) !== 1) {
        throw new RuntimeException('Cabang penerima dana belum melakukan closing. Proses ini harus diselesaikan sebelum dana dapat disetor ke bank.');
    }

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET status_setor_bank = 'siap'
                   WHERE kode_pengambilan = ?
                     AND status_setor_bank = 'belum'")
        ->execute([$root['kode_pengambilan']]);
}


function executeSetorBank(PDO $pdo, string $kodePengambilan, string $tglPenyerahan, string $tglRiil, string $kodeSetoran, string $karyawan): int
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode pengambilan tidak ditemukan.');
    }

    $nominalSisa = (float)$root['nominal_sisa'];

    $idSetoranBank = 0;
    if ($nominalSisa > 0) {
        $stmt = $pdo->prepare("INSERT INTO setoran_ke_bank_closing_kasir (kode_setoran, tanggal_setoran, metode_setoran, rekening_tujuan, total_setoran, created_by)
                               VALUES (?, ?, 'Tunai', ?, ?, ?)");
        $stmt->execute([
            $kodeSetoran,
            $tglRiil,
            $root['no_rekening_peminjam'] ?? '',
            $nominalSisa,
            $karyawan,
        ]);
        $idSetoranBank = (int)$pdo->lastInsertId();
    }

    $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                   SET status_setor_bank    = 'disetor',
                       tanggal_penyerahan_setor = ?,
                       tanggal_riil_setor_bank  = ?,
                       setoran_ke_bank_id   = ?,
                       id_setoran_bank      = ?
                   WHERE kode_pengambilan = ?")
        ->execute([$tglPenyerahan, $tglRiil, $idSetoranBank ?: null, $idSetoranBank ?: null, $root['kode_pengambilan']]);

    return $idSetoranBank;
}

function parsePengambilanRefTrxIds(?string $keterangan): array
{
    $keterangan = trim((string)$keterangan);
    if ($keterangan === '') {
        return [];
    }

    if (!preg_match('/\[Ref\s*TRX\s*:\s*([^\]]+)\]/i', $keterangan, $matches)) {
        return [];
    }

    $rawIds = preg_split('/\s*,\s*/', trim((string)$matches[1])) ?: [];
    $result = [];
    foreach ($rawIds as $rawId) {
        if ($rawId === '' || !ctype_digit($rawId)) {
            continue;
        }

        $id = (int)$rawId;
        if ($id > 0 && !in_array($id, $result, true)) {
            $result[] = $id;
        }
    }

    return $result;
}

function invalidatePengambilanPemasukanKasir(PDO $pdo, string $kodePengambilan): array
{
    $stmt = $pdo->prepare("SELECT id FROM pemasukan_kasir_closing_kasir WHERE kode_pengambilan_ref = ? FOR UPDATE");
    $stmt->execute([$kodePengambilan]);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    $ids = array_values(array_filter($ids, static function ($id) {
        return $id > 0;
    }));

    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmtDeletePayments = $pdo->prepare("DELETE FROM pengambilan_setoran_pembayaran_closing_kasir WHERE pemasukan_kasir_id IN ($placeholders)");
    $stmtDeletePayments->execute($ids);

    $stmtDeletePemasukan = $pdo->prepare("DELETE FROM pemasukan_kasir_closing_kasir WHERE id IN ($placeholders)");
    $stmtDeletePemasukan->execute($ids);

    return $ids;
}

function adjustPengambilanReferencedTransactions(PDO $pdo, array $refTrxIds, float $delta): void
{
    $delta = round($delta, 2);
    if (abs($delta) < 0.00001) {
        return;
    }

    $orderedIds = [];
    foreach ($refTrxIds as $rawId) {
        $id = (int)$rawId;
        if ($id > 0 && !in_array($id, $orderedIds, true)) {
            $orderedIds[] = $id;
        }
    }

    if (empty($orderedIds)) {
        throw new RuntimeException('Referensi transaksi closing tidak ditemukan pada keterangan pengambilan.');
    }

    $placeholders = implode(',', array_fill(0, count($orderedIds), '?'));
    $stmt = $pdo->prepare("SELECT id,
                                  kode_setoran,
                                  setoran_real,
                                  jumlah_diterima_fisik,
                                  COALESCE(jumlah_diterima_fisik, setoran_real) AS nominal_aktif
                           FROM kasir_transactions_closing_kasir
                           WHERE id IN ($placeholders)
                           FOR UPDATE");
    $stmt->execute($orderedIds);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $trxMap = [];
    foreach ($rows as $row) {
        $trxMap[(int)$row['id']] = $row;
    }

    $missingIds = [];
    foreach ($orderedIds as $id) {
        if (!isset($trxMap[$id])) {
            $missingIds[] = $id;
        }
    }
    if (!empty($missingIds)) {
        throw new RuntimeException('Sebagian referensi transaksi closing tidak ditemukan: ' . implode(', ', $missingIds));
    }

    $stmtUpdateTrx = $pdo->prepare("UPDATE kasir_transactions_closing_kasir SET jumlah_diterima_fisik = ? WHERE id = ?");
    $stmtDeductSk = $pdo->prepare("UPDATE setoran_keuangan_closing_kasir
                                   SET jumlah_diterima = COALESCE(jumlah_diterima, jumlah_setoran) - ?,
                                       jumlah_setoran = jumlah_setoran - ?
                                   WHERE kode_setoran = ?");
    $stmtRefundSk = $pdo->prepare("UPDATE setoran_keuangan_closing_kasir
                                   SET jumlah_diterima = COALESCE(jumlah_diterima, jumlah_setoran) + ?,
                                       jumlah_setoran = jumlah_setoran + ?
                                   WHERE kode_setoran = ?");

    if ($delta > 0) {
        $remaining = $delta;
        foreach ($orderedIds as $id) {
            if ($remaining <= 0) {
                break;
            }

            $currentAmount = round((float)$trxMap[$id]['nominal_aktif'], 2);
            if ($currentAmount <= 0) {
                continue;
            }

            $potong = min($currentAmount, $remaining);
            $amountAfter = round($currentAmount - $potong, 2);

            $stmtUpdateTrx->execute([$amountAfter, $id]);
            $stmtDeductSk->execute([$potong, $potong, $trxMap[$id]['kode_setoran']]);

            $trxMap[$id]['nominal_aktif'] = $amountAfter;
            $remaining = round($remaining - $potong, 2);
        }

        if ($remaining > 0.00001) {
            throw new RuntimeException('Saldo closing referensi tidak mencukupi untuk menambah pengambilan sebesar Rp ' . number_format($delta, 0, ',', '.'));
        }

        return;
    }

    $refund = abs($delta);
    foreach ($orderedIds as $id) {
        if ($refund <= 0) {
            break;
        }

        $currentAmount = round((float)$trxMap[$id]['nominal_aktif'], 2);
        $baseAmount = round((float)$trxMap[$id]['setoran_real'], 2);
        $room = max(0, round($baseAmount - $currentAmount, 2));
        if ($room <= 0) {
            continue;
        }

        $tambah = min($room, $refund);
        $amountAfter = round($currentAmount + $tambah, 2);

        $stmtUpdateTrx->execute([$amountAfter, $id]);
        $stmtRefundSk->execute([$tambah, $tambah, $trxMap[$id]['kode_setoran']]);

        $trxMap[$id]['nominal_aktif'] = $amountAfter;
        $refund = round($refund - $tambah, 2);
    }

    if ($refund > 0.00001) {
        throw new RuntimeException('Refund pengambilan melebihi saldo sumber closing yang bisa dikembalikan.');
    }
}

function editPengambilanNominal(PDO $pdo, string $kodePengambilan, float $nominalBaru, string $alasan, string $karyawan, string $role): void
{
    if ($nominalBaru < 0) {
        throw new InvalidArgumentException('Nominal baru tidak boleh negatif.');
    }
    if (trim($alasan) === '') {
        throw new InvalidArgumentException('Alasan perubahan wajib diisi.');
    }

    $pdo->beginTransaction();
    try {
        ensurePengambilanEditLogTable($pdo);

        $stmt = $pdo->prepare("SELECT * FROM pengambilan_setoran_closing_kasir WHERE kode_pengambilan = ? LIMIT 1 FOR UPDATE");
        $stmt->execute([$kodePengambilan]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            throw new RuntimeException('Kode pengambilan tidak ditemukan.');
        }
        if (!empty($record['parent_kode_pengambilan'])) {
            throw new RuntimeException('Edit nominal hanya diizinkan untuk record pengambilan induk.');
        }

        if (isPengambilanNominalLocked($record)) {
            if ((int)($record['verified_cabang_penerima_sudah_closing'] ?? 0) === 1 || !empty($record['verified_cabang_closing_at'])) {
                throw new RuntimeException('Tidak bisa edit nominal — cabang penerima sudah closing.');
            }
            throw new RuntimeException('Tidak bisa edit nominal — pengambilan ini sudah disetor ke bank.');
        }

        $nominalBaru = round($nominalBaru, 2);
        $nominalLama = round((float)$record['nominal_diambil'], 2);
        $nominalSisaLama = round((float)$record['nominal_sisa'], 2);
        $totalAwal = round($nominalLama + $nominalSisaLama, 2);
        $delta = round($nominalBaru - $nominalLama, 2);

        if (abs($delta) < 0.00001) {
            throw new RuntimeException('Nominal baru sama dengan nominal sebelumnya.');
        }
        if ($nominalBaru > $totalAwal + 0.00001) {
            throw new RuntimeException('Nominal baru melebihi total sumber closing Rp ' . number_format($totalAwal, 0, ',', '.') . '.');
        }

        $refTrxIds = parsePengambilanRefTrxIds($record['keterangan'] ?? '');
        if (empty($refTrxIds)) {
            throw new RuntimeException('Referensi transaksi closing tidak ditemukan pada keterangan pengambilan.');
        }

        // Cari dan hapus pemasukan kasir yang terkait
        $invalidatedIds = invalidatePengambilanPemasukanKasir($pdo, $kodePengambilan);
        $invalidatedId = !empty($invalidatedIds) ? (int)$invalidatedIds[0] : null;

        $paidAmountExisting = round(getPengambilanPaidAmount($pdo, $kodePengambilan), 2);
        if ($nominalBaru + 0.00001 < $paidAmountExisting) {
            throw new RuntimeException('Nominal baru tidak boleh lebih kecil dari total pembayaran/pelunasan yang sudah tercatat Rp ' . number_format($paidAmountExisting, 0, ',', '.') . '.');
        }

        adjustPengambilanReferencedTransactions($pdo, $refTrxIds, $delta);

        $nominalSisaBaru = max(0.0, round($totalAwal - $nominalBaru, 2));
        $statusBaru = $nominalBaru <= 0
            ? 'selesai'
            : (resolvePengambilanClassification($record) === 'hutang' ? 'hutang' : 'proses');

        $pdo->prepare("INSERT INTO pengambilan_setoran_edit_log_closing_kasir
                       (kode_pengambilan, edited_by_role, edited_by_kode_karyawan, field_changed, old_value, new_value, pemasukan_kasir_id_invalidated, alasan, dibaca)
                       VALUES (?, ?, ?, 'nominal_diambil', ?, ?, ?, ?, 0)")
            ->execute([$kodePengambilan, $role, $karyawan, (string)$nominalLama, (string)$nominalBaru, $invalidatedId, trim($alasan)]);

        // Reset verified_mutasi_bank & clear dokumen mutasi jika nominal berubah (P1 fix)
        clearPengambilanMutasiVerification($pdo, $kodePengambilan, true);

        $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                       SET nominal_diambil           = ?,
                           nominal_sisa              = ?,
                           verified_nominal_kas_masuk = 0,
                           verified_notrx_kas_masuk  = 0,
                           verified_cabang_penerima_sudah_closing = 0,
                           verified_cabang_closing_at = NULL,
                           verified_mutasi_bank      = 0,
                           status_setor_bank         = 'belum',
                           id_setoran_bank           = NULL,
                           setoran_ke_bank_id        = NULL,
                           tanggal_penyerahan_setor  = NULL,
                           tanggal_riil_setor_bank   = NULL,
                           id_pemasukan_gudang       = NULL,
                           status                    = ?
                       WHERE kode_pengambilan = ?")
            ->execute([$nominalBaru, $nominalSisaBaru, $statusBaru, $kodePengambilan]);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function recordManualPelunasan(PDO $pdo, string $kodePengambilan, float $nominal, string $noTrx, string $tglTransfer, string $catatan, string $karyawan, ?array $documentMeta = null): int
{
    $root = getPengambilanRootRecord($pdo, $kodePengambilan);
    if (!$root) {
        throw new RuntimeException('Kode pengambilan tidak ditemukan.');
    }

    if (resolvePengambilanClassification($root) !== 'hutang') {
        throw new RuntimeException('Pelunasan manual hanya untuk pengambilan hutang.');
    }

    $remaining = getPengambilanRemainingAmount($pdo, $root['kode_pengambilan']);
    
    if ($remaining <= 0.0) {
        $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir SET verified_mutasi_bank = 1 WHERE kode_pengambilan = ?")
            ->execute([$root['kode_pengambilan']]);
        checkAndUpdatePengadaanSelesai($pdo, $root['kode_pengambilan']);
        return 0; // Kembalikan ID dummy karena tidak ada pembayaran baru yang diinsert
    }

    if ($nominal <= 0) {
        throw new RuntimeException('Nominal pelunasan harus lebih besar dari nol.');
    }
    if ($nominal > $remaining) {
        throw new RuntimeException('Nominal melebihi sisa hutang Rp ' . number_format($remaining, 0, ',', '.') . '.');
    }

    $kodePelunasan = generateKodePelunasan($pdo);

    $stmt = $pdo->prepare("INSERT INTO pengambilan_setoran_pembayaran_closing_kasir
        (kode_pengambilan, nominal_dibayar, sumber, no_transaksi, tanggal_transfer, catatan, kode_karyawan_input, kode_validasi_input, dokumen_path, dokumen_nama_asli, dokumen_mime, dokumen_tipe)
        VALUES (?, ?, 'manual', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $root['kode_pengambilan'],
        $nominal,
        $noTrx ?: null,
        $tglTransfer,
        $catatan,
        $karyawan,
        $kodePelunasan,
        $documentMeta['dokumen_path'] ?? null,
        $documentMeta['dokumen_nama_asli'] ?? null,
        $documentMeta['dokumen_mime'] ?? null,
        $documentMeta['dokumen_tipe'] ?? null
    ]);
    $paymentId = (int)$pdo->lastInsertId();

    syncPengambilanNominalStatus($pdo, $root['kode_pengambilan']);

    $remainingAfter = getPengambilanRemainingAmount($pdo, $root['kode_pengambilan']);
    if ($remainingAfter <= 0.0) {
        $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir SET verified_mutasi_bank = 1 WHERE kode_pengambilan = ?")
            ->execute([$root['kode_pengambilan']]);
        $pdo->prepare("UPDATE pengambilan_setoran_closing_kasir
                       SET verified_notrx_kas_masuk = 1,
                           verified_cabang_penerima_sudah_closing = 1,
                           verified_cabang_closing_at = COALESCE(verified_cabang_closing_at, NOW())
                       WHERE kode_pengambilan = ?
                         AND (verified_notrx_kas_masuk = 0 OR verified_cabang_penerima_sudah_closing = 0)")
            ->execute([$root['kode_pengambilan']]);
    }

    checkAndUpdatePengadaanSelesai($pdo, $root['kode_pengambilan']);

    return $paymentId;
}

function getEditLogUnreadForCabang(PDO $pdo, string $kodeCabangPenerima): array
{
    $stmt = $pdo->prepare("SELECT el.*, ps.nominal_diambil AS nominal_baru, ps.kode_cabang_penerima
                           FROM pengambilan_setoran_edit_log_closing_kasir el
                           JOIN pengambilan_setoran_closing_kasir ps ON ps.kode_pengambilan = el.kode_pengambilan
                           WHERE ps.kode_cabang_penerima = ?
                             AND el.dibaca = 0
                           ORDER BY el.created_at DESC");
    $stmt->execute([$kodeCabangPenerima]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markEditLogDibaca(PDO $pdo, string $kodeCabangPenerima): void
{
    $pdo->prepare("UPDATE pengambilan_setoran_edit_log_closing_kasir el
                   JOIN pengambilan_setoran_closing_kasir ps ON ps.kode_pengambilan = el.kode_pengambilan
                   SET el.dibaca = 1
                   WHERE ps.kode_cabang_penerima = ?
                     AND el.dibaca = 0")
        ->execute([$kodeCabangPenerima]);
}

function getPengambilanBelumDiinputForCabang(PDO $pdo, string $kodeCabangPenerima): array
{
    $stmt = $pdo->prepare("SELECT ps.kode_pengambilan, ps.nominal_diambil, ps.nama_cabang_keuangan,
                                  ps.keterangan, ps.created_at
                           FROM pengambilan_setoran_closing_kasir ps
                           WHERE ps.kode_cabang_penerima = ?
                             AND ps.verified_nominal_kas_masuk = 0
                             AND ps.status != 'selesai'
                             AND ps.parent_kode_pengambilan IS NULL
                           ORDER BY ps.created_at DESC");
    $stmt->execute([$kodeCabangPenerima]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        ensurePengambilanEditLogTable($pdo);
    } catch (Throwable $e) {
        error_log('ensurePengambilanEditLogTable: ' . $e->getMessage());
    }
}
