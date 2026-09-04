<?php
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_close');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/closing_revision_helpers.php';

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$kodeTransaksi = $_GET['kode_transaksi'] ?? $_POST['kode_transaksi'] ?? '';
if ($kodeTransaksi === '') {
    die('Kode transaksi tidak ditemukan.');
}

$stmtTransaction = $pdo->prepare("SELECT * FROM kasir_transactions_closing_kasir WHERE kode_transaksi = ? LIMIT 1");
$stmtTransaction->execute([$kodeTransaksi]);
$transaction = $stmtTransaction->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    die('Transaksi tidak ditemukan.');
}

if (!userCanRequestRevisionForTransaction($pdo, $legacy_session_kasir, $transaction)) {
    die('Anda tidak memiliki akses untuk mengajukan revisi transaksi ini.');
}

$requestMessage = '';
$requestError = '';
$revisionBlockReason = '';
$canSubmitRevision = canTransactionBeRevised($transaction, $revisionBlockReason);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alasan = trim($_POST['alasan'] ?? '');

    if (!$canSubmitRevision) {
        $requestError = $revisionBlockReason;
    } elseif (strlen($alasan) < 15) {
        $requestError = 'Alasan revisi minimal 15 karakter.';
    } else {
        $stmtPending = $pdo->prepare(
            "SELECT id FROM closing_revision_requests_closing_kasir WHERE kode_transaksi_lama = ? AND status = 'pending' LIMIT 1"
        );
        $stmtPending->execute([$kodeTransaksi]);

        if ($stmtPending->fetch()) {
            $requestError = 'Sudah ada permintaan revisi yang masih menunggu approval.';
        } else {
            $stmtInsert = $pdo->prepare(
                "INSERT INTO closing_revision_requests_closing_kasir (kode_transaksi_lama, kode_cabang, kode_pemohon, alasan)
                 VALUES (:kode_transaksi_lama, :kode_cabang, :kode_pemohon, :alasan)"
            );
            $stmtInsert->execute([
                ':kode_transaksi_lama' => $kodeTransaksi,
                ':kode_cabang' => $transaction['kode_cabang'],
                ':kode_pemohon' => $kode_karyawan_aktif,
                ':alasan' => $alasan,
            ]);

            $requestMessage = 'Permintaan revisi berhasil dikirim. Transaksi lama tetap aman sampai approval diproses.';
        }
    }
}

$stmtLatestRequest = $pdo->prepare(
    "SELECT r.*, u.nama_lengkap AS nama_pemohon, a.nama_lengkap AS nama_approver
     FROM closing_revision_requests_closing_kasir r
     LEFT JOIN tbuser u ON u.kode_karyawan = r.kode_pemohon
     LEFT JOIN tbuser a ON a.kode_karyawan = r.approver_kode
     WHERE r.kode_transaksi_lama = ?
     ORDER BY r.created_at DESC
     LIMIT 5"
);
$stmtLatestRequest->execute([$kodeTransaksi]);
$requestHistory = $stmtLatestRequest->fetchAll(PDO::FETCH_ASSOC);

$username = $nama_karyawan_aktif ?? 'Unknown User';
$role = $legacy_session_kasir['role'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Revisi Closing</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --primary: #1d4ed8;
            --success: #15803d;
            --danger: #b91c1c;
            --warning: #b45309;
            --border: #e2e8f0;
            --bg: #f8fafc;
            --text: #1e293b;
            --muted: #64748b;
        }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text);
            padding: 24px;
        }
        .page {
            max-width: 980px;
            margin: 0 auto;
        }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .header p {
            color: var(--muted);
            line-height: 1.6;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-top: 20px;
        }
        .info-item {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
        }
        .info-item .label {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 4px;
        }
        .info-item .value {
            font-weight: 700;
        }
        .note-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1e3a8a;
            border-radius: 12px;
            padding: 16px 18px;
            line-height: 1.6;
        }
        .alert {
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-size: 14px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        textarea {
            width: 100%;
            min-height: 140px;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px;
            font: inherit;
            margin-top: 10px;
            resize: vertical;
        }
        .actions {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-secondary { background: #e2e8f0; color: var(--text); }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .history-table th,
        .history-table td {
            border-bottom: 1px solid var(--border);
            padding: 12px 10px;
            text-align: left;
            font-size: 14px;
            vertical-align: top;
        }
        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 700;
        }
        .badge.pending { background: #fef3c7; color: #92400e; }
        .badge.approved { background: #dcfce7; color: #166534; }
        .badge.rejected { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="page">
        <div class="card header">
            <h1><i class="fas fa-code-branch"></i> Ajukan Revisi Closing</h1>
            <p>Transaksi closing tidak dibuka kembali secara langsung. Sistem akan membuat transaksi revisi dengan nomor baru setelah approval disetujui.</p>
            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Kode Transaksi Lama</span>
                    <span class="value"><?= htmlspecialchars($transaction['kode_transaksi']) ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Cabang</span>
                    <span class="value"><?= htmlspecialchars($transaction['nama_cabang'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Status Saat Ini</span>
                    <span class="value"><?= htmlspecialchars($transaction['status'] ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Pemohon</span>
                    <span class="value"><?= htmlspecialchars($username) ?> (<?= htmlspecialchars($role) ?>)</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="note-box">
                <strong>Alur revisi:</strong> transaksi lama akan ditandai dibatalkan, bukan diedit atau dihapus. Setelah approval, sistem membuat transaksi baru untuk proses koreksi sehingga riwayat lama dan baru tetap bisa dilacak.
            </div>
        </div>

        <div class="card">
            <?php if ($requestMessage !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($requestMessage) ?></div>
            <?php endif; ?>
            <?php if ($requestError !== ''): ?>
                <div class="alert alert-error"><?= htmlspecialchars($requestError) ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="kode_transaksi" value="<?= htmlspecialchars($kodeTransaksi) ?>">
                <label for="alasan"><strong>Alasan Revisi</strong></label>
                <textarea id="alasan" name="alasan" placeholder="Contoh: salah kode akun pada pemasukan, transaksi sudah closing tetapi perlu dikoreksi agar balance dan histori tetap aman." <?= $canSubmitRevision ? 'required' : 'disabled' ?>></textarea>
                <div class="actions">
                    <button type="submit" class="btn btn-primary" <?= $canSubmitRevision ? '' : 'disabled' ?>><i class="fas fa-paper-plane"></i> Kirim Permintaan Revisi</button>
                    <a href="view_transaksi.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Detail</a>
                    <?php if (($legacy_session_kasir['role'] ?? '') === 'super_admin'): ?>
                        <a href="closing_revisi_admin.php?kode_transaksi=<?= urlencode($kodeTransaksi) ?>" class="btn btn-secondary"><i class="fas fa-user-check"></i> Halaman Approval</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h2 style="font-size: 20px; margin-bottom: 6px;">Riwayat Permintaan</h2>
            <p style="color: var(--muted); margin-bottom: 10px;">5 permintaan terakhir untuk transaksi ini.</p>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Alasan</th>
                        <th>Pemohon</th>
                        <th>Approver</th>
                        <th>Transaksi Baru</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($requestHistory): ?>
                        <?php foreach ($requestHistory as $request): ?>
                            <tr>
                                <td><span class="badge <?= htmlspecialchars($request['status']) ?>"><?= htmlspecialchars($request['status']) ?></span></td>
                                <td><?= nl2br(htmlspecialchars($request['alasan'])) ?></td>
                                <td><?= htmlspecialchars($request['nama_pemohon'] ?? $request['kode_pemohon']) ?></td>
                                <td><?= htmlspecialchars($request['nama_approver'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($request['kode_transaksi_baru'])): ?>
                                        <a href="view_transaksi.php?kode_transaksi=<?= urlencode($request['kode_transaksi_baru']) ?>"><?= htmlspecialchars($request['kode_transaksi_baru']) ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($request['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Belum ada riwayat permintaan revisi untuk transaksi ini.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
