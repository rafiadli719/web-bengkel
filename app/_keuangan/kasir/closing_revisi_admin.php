<?php
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/closing_revision_helpers.php';

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

if (!canApproveClosingRevision($pdo, $legacy_session_kasir)) {
    die('Anda tidak memiliki akses approval revisi closing.');
}

$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $approvalNote = trim($_POST['approval_note'] ?? '');

    try {
        if ($action === 'approve_request') {
            $result = approveClosingRevisionRequest($pdo, $requestId, (string) $kode_karyawan_aktif, $approvalNote);
            $flash = 'Permintaan disetujui. Transaksi baru dibuat: ' . $result['kode_transaksi_baru'];
        } elseif ($action === 'reject_request') {
            $stmtReject = $pdo->prepare(
                "UPDATE closing_revision_requests_closing_kasir
                 SET status = 'rejected',
                     approver_kode = :approver_kode,
                     approval_note = :approval_note,
                     rejected_at = NOW()
                 WHERE id = :id AND status = 'pending'"
            );
            $stmtReject->execute([
                ':approver_kode' => $kode_karyawan_aktif,
                ':approval_note' => $approvalNote,
                ':id' => $requestId,
            ]);

            if ($stmtReject->rowCount() === 0) {
                throw new RuntimeException('Permintaan tidak ditemukan atau sudah diproses.');
            }

            $flash = 'Permintaan revisi ditolak.';
        }
    } catch (Throwable $exception) {
        $flash = $exception->getMessage();
        $flashType = 'error';
    }
}

$params = [];
$cabangFilterSql = '';

$pendingSql = "
    SELECT r.*, t.nama_cabang, t.status AS status_transaksi,
           u.nama_lengkap AS nama_pemohon
    FROM closing_revision_requests_closing_kasir r
    LEFT JOIN kasir_transactions_closing_kasir t ON t.kode_transaksi = r.kode_transaksi_lama
    LEFT JOIN tbuser u ON u.kode_karyawan = r.kode_pemohon
";
$pendingSql .= $cabangFilterSql;
$pendingSql .= $cabangFilterSql ? " AND r.status = 'pending' " : " WHERE r.status = 'pending' ";
$pendingSql .= " ORDER BY r.created_at ASC";

$stmtPending = $pdo->prepare($pendingSql);
$stmtPending->execute($params);
$pendingRequests = $stmtPending->fetchAll(PDO::FETCH_ASSOC);

$historySql = "
    SELECT r.*, t.nama_cabang,
           u.nama_lengkap AS nama_pemohon,
           a.nama_lengkap AS nama_approver
    FROM closing_revision_requests_closing_kasir r
    LEFT JOIN kasir_transactions_closing_kasir t ON t.kode_transaksi = r.kode_transaksi_lama
    LEFT JOIN tbuser u ON u.kode_karyawan = r.kode_pemohon
    LEFT JOIN tbuser a ON a.kode_karyawan = r.approver_kode
";
$historySql .= $cabangFilterSql;
$historySql .= $cabangFilterSql ? " AND r.status <> 'pending' " : " WHERE r.status <> 'pending' ";
$historySql .= " ORDER BY COALESCE(r.approved_at, r.rejected_at, r.created_at) DESC LIMIT 30";

$stmtHistory = $pdo->prepare($historySql);
$stmtHistory->execute($params);
$historyRequests = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

$username = $nama_karyawan_aktif ?? 'Unknown User';
$role = $legacy_session_kasir['role'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approval Revisi Closing</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="includes/sidebar.css" rel="stylesheet">
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
            font-family: 'Inter', 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            min-height: 100vh;
        }
        .page {
            max-width: 1280px;
            margin: 0 auto;
        }
        .main-content {
            padding: 30px;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
        }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            margin-bottom: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        .header p {
            color: var(--muted);
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
        .request-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(360px, 1fr));
            gap: 18px;
        }
        .request-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 18px;
            background: #fff;
        }
        .request-card h3 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }
        .meta-item {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 12px;
        }
        .meta-item span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 4px;
        }
        .reason-box {
            border-left: 4px solid #f59e0b;
            background: #fffbeb;
            padding: 12px 14px;
            border-radius: 10px;
            margin-bottom: 12px;
            line-height: 1.6;
        }
        textarea {
            width: 100%;
            min-height: 92px;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            font: inherit;
            resize: vertical;
            margin-bottom: 12px;
        }
        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: none;
            border-radius: 10px;
            padding: 11px 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-success { background: var(--success); color: #fff; }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-secondary { background: #e2e8f0; color: var(--text); }
        .history-table {
            width: 100%;
            border-collapse: collapse;
        }
        .history-table th,
        .history-table td {
            border-bottom: 1px solid var(--border);
            padding: 12px 10px;
            font-size: 14px;
            text-align: left;
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
        @media (max-width: 768px) {
            .main-content {
                padding: 20px;
            }
            .meta {
                grid-template-columns: 1fr;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php /* TODO(Task 15): wire ke sidebar/menu fitmotor, sidebar.php sumber web_kasir tidak diport */ ?>
    <div class="main-content">
    <div class="page">
        <div class="user-profile">
            <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($username, 0, 1))); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($username); ?></strong>
                <p style="color: var(--muted); font-size: 12px;"><?php echo htmlspecialchars(ucfirst($role)); ?></p>
            </div>
        </div>

        <div class="card header">
            <div>
                <h1><i class="fas fa-user-check"></i> Approval Revisi Closing</h1>
                <p>Setujui atau tolak permintaan revisi closing. Jika disetujui, transaksi lama akan dibatalkan dan sistem membuat transaksi baru dengan nomor urut baru.</p>
            </div>
            <a href="admin_dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard Admin</a>
        </div>

        <?php if ($flash !== ''): ?>
            <div class="alert <?= $flashType === 'error' ? 'alert-error' : 'alert-success' ?>"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2 style="font-size: 22px; margin-bottom: 14px;">Permintaan Pending</h2>
            <?php if ($pendingRequests): ?>
                <div class="request-grid">
                    <?php foreach ($pendingRequests as $request): ?>
                        <div class="request-card">
                            <h3><?= htmlspecialchars($request['kode_transaksi_lama']) ?></h3>
                            <div class="meta">
                                <div class="meta-item">
                                    <span>Cabang</span>
                                    <strong><?= htmlspecialchars($request['nama_cabang'] ?? '-') ?></strong>
                                </div>
                                <div class="meta-item">
                                    <span>Pemohon</span>
                                    <strong><?= htmlspecialchars($request['nama_pemohon'] ?? $request['kode_pemohon']) ?></strong>
                                </div>
                                <div class="meta-item">
                                    <span>Status Transaksi</span>
                                    <strong><?= htmlspecialchars($request['status_transaksi'] ?? '-') ?></strong>
                                </div>
                                <div class="meta-item">
                                    <span>Diajukan</span>
                                    <strong><?= htmlspecialchars($request['created_at']) ?></strong>
                                </div>
                            </div>
                            <div class="reason-box"><?= nl2br(htmlspecialchars($request['alasan'])) ?></div>
                            <form method="POST">
                                <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                <textarea name="approval_note" placeholder="Catatan approval atau alasan penolakan (opsional untuk approve, disarankan untuk reject)."></textarea>
                                <div class="actions">
                                    <button type="submit" name="action" value="approve_request" class="btn btn-success"><i class="fas fa-check"></i> Setujui & Buat Revisi</button>
                                    <button type="submit" name="action" value="reject_request" class="btn btn-danger"><i class="fas fa-times"></i> Tolak</button>
                                    <a href="view_transaksi.php?kode_transaksi=<?= urlencode($request['kode_transaksi_lama']) ?>" class="btn btn-secondary"><i class="fas fa-eye"></i> Lihat Detail</a>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: var(--muted);">Tidak ada permintaan revisi yang menunggu approval.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2 style="font-size: 22px; margin-bottom: 14px;">Riwayat Approval</h2>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Transaksi Lama</th>
                        <th>Transaksi Baru</th>
                        <th>Pemohon</th>
                        <th>Approver</th>
                        <th>Catatan</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($historyRequests): ?>
                        <?php foreach ($historyRequests as $history): ?>
                            <tr>
                                <td><span class="badge <?= htmlspecialchars($history['status']) ?>"><?= htmlspecialchars($history['status']) ?></span></td>
                                <td><?= htmlspecialchars($history['kode_transaksi_lama']) ?></td>
                                <td>
                                    <?php if (!empty($history['kode_transaksi_baru'])): ?>
                                        <a href="view_transaksi.php?kode_transaksi=<?= urlencode($history['kode_transaksi_baru']) ?>"><?= htmlspecialchars($history['kode_transaksi_baru']) ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($history['nama_pemohon'] ?? $history['kode_pemohon']) ?></td>
                                <td><?= htmlspecialchars($history['nama_approver'] ?? '-') ?></td>
                                <td><?= nl2br(htmlspecialchars($history['approval_note'] ?? '-')) ?></td>
                                <td><?= htmlspecialchars($history['approved_at'] ?? $history['rejected_at'] ?? $history['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">Belum ada riwayat approval revisi closing.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</body>
</html>
