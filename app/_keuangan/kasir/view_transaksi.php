<?php
// Sumber: web_kasir/view_transaksi.php — detail 1 transaksi kasir (versi
// kasir/self-service, beda dari view_transaksi_admin.php yang modal admin).
// Gerbang asli role IN (kasir,admin,super_admin) -> kasir_operate (Task 10:
// paling longgar, KSR+KEU+ADM semua boleh, cuma butuh transaksi miliknya).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_operate');
require_once __DIR__ . '/closing_revision_helpers.php';

// Database connection
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get user details dari RBAC fitmotor (Task 10/11 pola koneksi_kasir.php)
$kode_karyawan = $kode_karyawan_aktif;
$username = $nama_karyawan_aktif;
$cabang_user = $nama_cabang_aktif;

$kode_transaksi = $_GET['kode_transaksi'] ?? null; // Get transaction code from URL

// PORTING GAP (Task 23, belum dikerjakan): data_penjualan_closing_kasir/
// data_servis_closing_kasir belum ada DDL+migrasinya di fitmotor. Degrade
// graceful (0 AS ..., bukan fatal error) sampai Task 23 kelar - pola sama
// view_transaksi_admin.php.
function vtTableExists(PDO $pdo, string $table): bool {
    static $cache = [];
    if (!isset($cache[$table])) {
        $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table));
        $cache[$table] = (bool) $stmt->fetch();
    }
    return $cache[$table];
}
$sqlDataPenjualan = vtTableExists($pdo, 'data_penjualan_closing_kasir')
    ? "(SELECT SUM(jumlah_penjualan) FROM data_penjualan_closing_kasir WHERE kode_transaksi = :kode_transaksi) AS data_penjualan_closing_kasir,"
    : "0 AS data_penjualan_closing_kasir,";
$sqlDataServis = vtTableExists($pdo, 'data_servis_closing_kasir')
    ? "(SELECT SUM(jumlah_servis) FROM data_servis_closing_kasir WHERE kode_transaksi = :kode_transaksi) AS data_servis_closing_kasir,"
    : "0 AS data_servis_closing_kasir,";

// Retrieve transaction data, including the branch name directly from `kasir_transactions_closing_kasir`
$sql = "
    SELECT
        kt.*,
        kt.nama_cabang AS cabang,   -- Get the branch name directly from kasir_transactions_closing_kasir
        $sqlDataPenjualan
        $sqlDataServis
        (SELECT SUM(jumlah) FROM pengeluaran_kasir_closing_kasir WHERE kode_transaksi = :kode_transaksi) AS total_pengeluaran,
        (SELECT SUM(jumlah) FROM pemasukan_kasir_closing_kasir WHERE kode_transaksi = :kode_transaksi) AS total_pemasukan,
        ka.total_nilai AS kas_awal,
        kcl.total_nilai AS kas_akhir,
        ka.tanggal AS kas_awal_date,
        kcl.tanggal AS kas_akhir_date,
        ka.waktu AS kas_awal_time,
        kcl.waktu AS kas_akhir_time,
        kt.tanggal_closing,      -- Get the closing date
        kt.jam_closing           -- Get the closing time
    FROM kasir_transactions_closing_kasir kt
    LEFT JOIN kas_awal ka ON ka.kode_transaksi = kt.kode_transaksi
    LEFT JOIN kas_akhir kcl ON kcl.kode_transaksi = kt.kode_transaksi
    WHERE kt.kode_transaksi = :kode_transaksi";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction || !userCanAccessTransaction($pdo, $legacy_session_kasir, $transaction)) {
    die("Transaksi tidak ditemukan atau Anda tidak memiliki akses.");
}

// Fetch nominal and keping_closing_kasir data for Kas Awal and Kas Akhir
$sql_kas_awal_detail = "
    SELECT nominal, SUM(jumlah_keping) as jumlah_keping
    FROM detail_kas_awal 
    WHERE kode_transaksi = :kode_transaksi
    GROUP BY nominal";
$stmt_kas_awal_detail = $pdo->prepare($sql_kas_awal_detail);
$stmt_kas_awal_detail->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_kas_awal_detail->execute();
$kas_awal_detail = $stmt_kas_awal_detail->fetchAll(PDO::FETCH_ASSOC);

$sql_kas_akhir_detail = "
    SELECT nominal, SUM(jumlah_keping) as jumlah_keping
    FROM detail_kas_akhir 
    WHERE kode_transaksi = :kode_transaksi
    GROUP BY nominal";
$stmt_kas_akhir_detail = $pdo->prepare($sql_kas_akhir_detail);
$stmt_kas_akhir_detail->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_kas_akhir_detail->execute();
$kas_akhir_detail = $stmt_kas_akhir_detail->fetchAll(PDO::FETCH_ASSOC);

// Fetch Pemasukan Kasir details
$sql_pemasukan = "
    SELECT kode_transaksi, kode_akun, jumlah, keterangan_transaksi, tanggal, waktu 
    FROM pemasukan_kasir_closing_kasir 
    WHERE kode_transaksi = :kode_transaksi";
$stmt_pemasukan = $pdo->prepare($sql_pemasukan);
$stmt_pemasukan->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_pemasukan->execute();
$pemasukan_kasir_closing_kasir = $stmt_pemasukan->fetchAll(PDO::FETCH_ASSOC);

// Fetch Pengeluaran Kasir details
$sql_pengeluaran = "
    SELECT kode_transaksi, kode_akun, jumlah, keterangan_transaksi, tanggal, waktu, umur_pakai, kategori 
    FROM pengeluaran_kasir_closing_kasir 
    WHERE kode_transaksi = :kode_transaksi";
$stmt_pengeluaran = $pdo->prepare($sql_pengeluaran);
$stmt_pengeluaran->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_pengeluaran->execute();
$pengeluaran_kasir_closing_kasir = $stmt_pengeluaran->fetchAll(PDO::FETCH_ASSOC);

// Calculate additional variables for display
$omset = $transaction['data_penjualan_closing_kasir'] + $transaction['data_servis_closing_kasir'];
$setoran_real = $transaction['kas_akhir'] - $transaction['kas_awal'];
$data_setoran = $omset + $transaction['total_pemasukan'] - $transaction['total_pengeluaran'];
$selisih_setoran = $setoran_real - $data_setoran;

$bulan = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

$tanggal = date('d', strtotime($transaction['kas_awal_date']));
$bulanNama = $bulan[(int)date('m', strtotime($transaction['kas_awal_date']))];
$tahun = date('Y', strtotime($transaction['kas_awal_date']));
$formattedDate = "$tanggal $bulanNama $tahun";

$revisionBlockReason = '';
$canRequestRevision = userCanRequestRevisionForTransaction($pdo, $legacy_session_kasir, $transaction)
    && canTransactionBeRevised($transaction, $revisionBlockReason);

$stmtRevisionHistory = $pdo->prepare(
    "SELECT r.*, u.nama_lengkap AS nama_pemohon, a.nama_lengkap AS nama_approver
     FROM closing_revision_requests_closing_kasir r
     LEFT JOIN tbuser u ON u.kode_karyawan = r.kode_pemohon
     LEFT JOIN tbuser a ON a.kode_karyawan = r.approver_kode
     WHERE r.kode_transaksi_lama = :kode_transaksi
        OR r.kode_transaksi_baru = :kode_transaksi
     ORDER BY r.created_at DESC
     LIMIT 10"
);
$stmtRevisionHistory->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmtRevisionHistory->execute();
$revisionHistory = $stmtRevisionHistory->fetchAll(PDO::FETCH_ASSOC);
$revisionSummary = getClosingRevisionSummary($pdo, (string) $kode_transaksi);
$formatRevisionValue = static function ($field, $value): string {
    if ($value === null || $value === '') {
        return '-';
    }
    if ($field === 'status') {
        return (string) $value;
    }
    return 'Rp' . number_format((float) $value, 0, ',', '.');
};
$formatRevisionDetailValue = static function (?string $valueLabel, $value): string {
    if ($value === null || $value === '') {
        return '-';
    }
    if ($valueLabel === 'Jumlah Keping') {
        return number_format((float) $value, 0, ',', '.');
    }
    return 'Rp' . number_format((float) $value, 0, ',', '.');
};
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Closing Kasir</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        :root {
            --primary-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --secondary-color: #6c757d;
            --warning-color: #ffc107;
            --background-light: #f8fafc;
            --text-dark: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--background-light);
            color: var(--text-dark);
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: #1e293b;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
            transition: width 0.3s ease;
        }
        .sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            font-size: 14px;
            white-space: nowrap;
        }
        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .logout-btn {
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 12px 20px;
            width: 100%;
            text-align: left;
            margin-top: 20px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background: #c82333;
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
            flex: 1;
            transition: margin-left 0.3s ease;
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
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            color: var(--text-muted);
        }
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
        }
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-dark);
            margin: 32px 0 16px 0;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--border-color);
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            justify-content: center;
        }
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            box-shadow: 0 2px 4px rgba(23, 162, 184, 0.2);
        }
        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(23, 162, 184, 0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, var(--success-color), #20c997);
            color: white;
            box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
        }
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
        }
        .btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color), #5a6268);
            color: white;
            box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        }
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3);
        }
        .status-banner {
            margin-bottom: 18px;
            border-radius: 14px;
            padding: 16px 18px;
            border: 1px solid var(--border-color);
            background: #fff7ed;
            color: #9a3412;
            line-height: 1.6;
        }
        .status-banner.success {
            background: #ecfdf5;
            color: #166534;
            border-color: #bbf7d0;
        }
        .status-banner-title {
            display: inline-block;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .status-banner-note {
            margin-top: 8px;
            font-size: 14px;
        }
        .revision-summary-card {
            margin-bottom: 24px;
            border-radius: 16px;
            padding: 20px;
            border: 1px solid var(--border-color);
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .revision-summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
            margin-top: 16px;
        }
        .revision-summary-box {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px 16px;
            background: var(--background-light);
        }
        .revision-summary-box.cancelled {
            background: #fff1f2;
            border-color: #fecdd3;
        }
        .revision-summary-box.replacement {
            background: #ecfdf5;
            border-color: #bbf7d0;
        }
        .revision-summary-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 6px;
        }
        .revision-summary-value {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-dark);
            word-break: break-word;
        }
        .revision-diff-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .revision-diff-table th,
        .revision-diff-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-color);
            text-align: left;
            font-size: 14px;
            vertical-align: top;
        }
        .revision-diff-table th {
            background: var(--background-light);
            font-weight: 700;
        }
        .revision-diff-old {
            color: var(--danger-color);
            font-weight: 600;
        }
        .revision-diff-new {
            color: var(--success-color);
            font-weight: 600;
        }
        .revision-summary-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .btn-outline-primary {
            background: #eff6ff;
            color: var(--primary-color);
            border: 1px solid #bfdbfe;
        }
        .btn-outline-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 123, 255, 0.15);
        }
        .revision-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
            z-index: 2000;
        }
        .revision-modal.show {
            display: flex;
        }
        .revision-modal-card {
            width: min(1100px, 100%);
            max-height: 88vh;
            overflow: auto;
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--border-color);
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.22);
            padding: 24px;
        }
        .revision-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 16px;
        }
        .revision-modal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
        }
        .revision-modal-reason {
            margin-top: 14px;
            padding: 14px 16px;
            background: linear-gradient(135deg, #fff7ed 0%, #fefce8 100%);
            border: 1px solid #fed7aa;
            border-radius: 12px;
            color: #7c2d12;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
        }
        .revision-modal-reason strong {
            display: block;
            margin-bottom: 6px;
            color: #9a3412;
        }
        .revision-modal-reason-text {
            line-height: 1.6;
            color: #7c2d12;
            word-break: break-word;
        }
        .revision-change-block {
            border: 1px solid var(--border-color);
            border-radius: 14px;
            padding: 16px;
            background: var(--background-light);
        }
        .revision-change-block h4 {
            margin: 0 0 12px 0;
            font-size: 16px;
        }
        .revision-change-list {
            display: grid;
            gap: 10px;
        }
        .revision-change-item {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 10px 12px;
        }
        .revision-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .revision-pill.added { background: #dcfce7; color: #166534; }
        .revision-pill.removed { background: #fee2e2; color: #991b1b; }
        .revision-pill.changed { background: #fef3c7; color: #92400e; }
        .revision-change-title {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
        }
        .revision-change-meta {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .revision-table {
            width: 100%;
            border-collapse: collapse;
        }
        .revision-table th,
        .revision-table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-color);
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
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .container {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <a href="index_kasir.php"><i class="fas fa-tachometer-alt"></i> Dashboard Kasir</a>
        <a href="serah_terima_kasir_closing_kasir.php"><i class="fas fa-handshake"></i> Serah Terima Kasir</a>
        <a href="setoran_keuangan_cs.php"><i class="fas fa-money-bill"></i> Setoran Keuangan CS</a>
        <button class="logout-btn" onclick="window.location.href='logout.php';"><i class="fas fa-sign-out-alt"></i> Logout</button>
    </div>

    <div class="main-content">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($username); ?></strong> (<?php echo htmlspecialchars($cabang_user); ?>)
                <p style="color: var(--text-muted); font-size: 12px;">Kasir</p>
            </div>
        </div>

        <div class="breadcrumb">
            <a href="index_kasir.php"><i class="fas fa-home"></i> Dashboard</a>
            <i class="fas fa-chevron-right"></i>
            <span>Closing Kasir</span>
        </div>

        <div class="container">
            <div class="header">
                <h1><i class="fas fa-file-invoice"></i> CLOSING <?php echo htmlspecialchars($transaction['cabang']) . ' ' . $formattedDate; ?></h1>
            </div>

            <?php if (($transaction['status'] ?? '') === 'dibatalkan'): ?>
                <div class="status-banner">
                    <span class="status-banner-title">DIBATALKAN</span>
                </div>
            <?php elseif (!empty($transaction['revision_parent_kode'])): ?>
                <div class="status-banner success">
                    <span class="status-banner-title">HASIL REVISI</span>
                </div>
            <?php endif; ?>

            <?php if ($revisionSummary): ?>
                <?php $request = $revisionSummary['request']; ?>
                <div class="revision-summary-card">
                    <h2 class="section-title" style="margin:0 0 8px 0;">Ringkasan Revisi Closing</h2>
                    <div class="revision-summary-grid">
                        <div class="revision-summary-box cancelled">
                            <div class="revision-summary-label">Transaksi Lama Dibatalkan</div>
                            <div class="revision-summary-value"><?php echo htmlspecialchars($request['kode_transaksi_lama']); ?></div>
                        </div>
                        <div class="revision-summary-box replacement">
                            <div class="revision-summary-label">Transaksi Baru Pengganti</div>
                            <div class="revision-summary-value">
                                <?php if (!empty($request['kode_transaksi_baru'])): ?>
                                    <?php echo htmlspecialchars($request['kode_transaksi_baru']); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="revision-summary-box">
                            <div class="revision-summary-label">Status Approval</div>
                            <div class="revision-summary-value"><?php echo htmlspecialchars($request['status']); ?></div>
                        </div>
                        <div class="revision-summary-box">
                            <div class="revision-summary-label">Pemohon / Approver</div>
                            <div class="revision-summary-value" style="font-size:14px;">
                                <?php echo htmlspecialchars($request['nama_pemohon'] ?? $request['kode_pemohon']); ?><br>
                                <span style="font-weight:500; color: var(--text-muted);">
                                    Approver: <?php echo htmlspecialchars($request['nama_approver'] ?? '-'); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($request['alasan']) || !empty($request['approval_note'])): ?>
                        <div style="margin-top: 16px; display:grid; gap:10px;">
                            <?php if (!empty($request['alasan'])): ?>
                                <div class="status-banner" style="margin-bottom:0;">
                                    <strong>Alasan Revisi:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($request['alasan'])); ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($request['approval_note'])): ?>
                                <div class="status-banner success" style="margin-bottom:0;">
                                    <strong>Catatan Approval:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($request['approval_note'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $hasLineItemChanges = false;
                    foreach (($revisionSummary['line_item_changes'] ?? []) as $sectionChange) {
                        if (!empty($sectionChange['added']) || !empty($sectionChange['removed']) || !empty($sectionChange['changed'])) {
                            $hasLineItemChanges = true;
                            break;
                        }
                    }
                    ?>
                    <?php if ($hasLineItemChanges): ?>
                        <div class="revision-summary-actions">
                            <button type="button" class="btn btn-outline-primary" onclick="openRevisionDetailModal()">
                                <i class="fas fa-list-ul"></i> Detail Revisi
                            </button>
                        </div>
                    <?php endif; ?>

                    <table class="revision-diff-table">
                        <thead>
                            <tr>
                                <th>Bagian</th>
                                <th>Transaksi Lama</th>
                                <th>Transaksi Baru</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($revisionSummary['differences'])): ?>
                                <?php foreach ($revisionSummary['differences'] as $difference): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($difference['label']); ?></td>
                                        <td class="revision-diff-old"><?php echo htmlspecialchars($formatRevisionValue($difference['field'], $difference['old'])); ?></td>
                                        <td class="revision-diff-new"><?php echo htmlspecialchars($formatRevisionValue($difference['field'], $difference['new'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td>Status</td>
                                    <td class="revision-diff-old"><?php echo htmlspecialchars($formatRevisionValue('status', $revisionSummary['old_snapshot']['status'] ?? null)); ?></td>
                                    <td class="revision-diff-new"><?php echo htmlspecialchars($formatRevisionValue('status', $revisionSummary['new_snapshot']['status'] ?? null)); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" style="color: var(--text-muted);">
                                        Tidak ada perubahan nominal. Revisi ini mempertahankan angka transaksi dan hanya mengganti nomor atau relasi transaksi.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Transaction Date and Time -->
            <h2 class="section-title">Tanggal dan Jam Transaksi</h2>
            <table class="table table-bordered">
                <tr>
                    <th>Kas Awal - Tanggal</th>
                    <td><?php echo !empty($transaction['kas_awal_date']) ? date('d M Y', strtotime($transaction['kas_awal_date'])) : 'Belum diisi'; ?></td>
                    <th>Kas Awal - Jam</th>
                    <td><?php echo !empty($transaction['kas_awal_time']) ? date('H:i:s', strtotime($transaction['kas_awal_time'])) : 'Belum diisi'; ?></td>
                </tr>
                <tr>
                    <th>Kas Akhir - Tanggal</th>
                    <td><?php echo !empty($transaction['kas_akhir_date']) ? date('d M Y', strtotime($transaction['kas_akhir_date'])) : 'Belum diisi'; ?></td>
                    <th>Kas Akhir - Jam</th>
                    <td><?php echo !empty($transaction['kas_akhir_time']) ? date('H:i:s', strtotime($transaction['kas_akhir_time'])) : 'Belum diisi'; ?></td>
                </tr>
                <tr>
                    <th>Tanggal Closing</th>
                    <td><?php echo !empty($transaction['tanggal_closing']) ? date('d M Y', strtotime($transaction['tanggal_closing'])) : 'Belum diisi'; ?></td>
                    <th>Jam Closing</th>
                    <td><?php echo !empty($transaction['jam_closing']) ? date('H:i:s', strtotime($transaction['jam_closing'])) : 'Belum diisi'; ?></td>
                </tr>
            </table>

            <!-- Data Sistem Aplikasi -->
            <h2 class="section-title">Data Sistem Aplikasi</h2>
            <table class="table table-striped table-bordered">
                <tr><th>Omset Penjualan</th><td>Rp<?php echo number_format($transaction['data_penjualan_closing_kasir'], 0, ',', '.'); ?></td></tr>
                <tr><th>Omset Servis</th><td>Rp<?php echo number_format($transaction['data_servis_closing_kasir'], 0, ',', '.'); ?></td></tr>
                <tr><th>Jumlah Omset (Penjualan + Servis)</th><td>Rp<?php echo number_format($omset, 0, ',', '.'); ?></td></tr>
                <tr><th>Pemasukan Kas</th><td>Rp<?php echo number_format($transaction['total_pemasukan'], 0, ',', '.'); ?></td></tr>
                <tr><th>Total Uang Masuk Kas</th><td>Rp<?php echo number_format($omset + $transaction['total_pemasukan'], 0, ',', '.'); ?></td></tr>
                <tr><th>Pengeluaran Kas</th><td>Rp<?php echo number_format($transaction['total_pengeluaran'], 0, ',', '.'); ?></td></tr>
                <tr><th>Data Setoran</th><td>Rp<?php echo number_format($data_setoran, 0, ',', '.'); ?></td></tr>
                <tr><th>Selisih Setoran (REAL - DATA)</th><td>Rp<?php echo number_format($selisih_setoran, 0, ',', '.'); ?></td></tr>
            </table>

            <!-- Riil Uang -->
            <h2 class="section-title">Riil Uang</h2>
            <table class="table table-bordered">
                <tr><th>Kas Awal</th><td>Rp<?php echo number_format($transaction['kas_awal'], 0, ',', '.'); ?></td></tr>
                <tr><th>Kas Akhir</th><td>Rp<?php echo number_format($transaction['kas_akhir'], 0, ',', '.'); ?></td></tr>
                <tr><th>Setoran Riil</th><td>Rp<?php echo number_format($setoran_real, 0, ',', '.'); ?></td></tr>
            </table>

            <!-- Data Kas Awal and Data Kas Akhir side by side -->
            <div class="row">
                <div class="col-md-6">
                    <h2 class="section-title">Data Kas Awal</h2>
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Nominal</th>
                                <th>Keping</th>
                                <th>Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kas_awal_detail as $row): ?>
                                <tr>
                                    <td>Rp<?php echo number_format($row['nominal'], 0, ',', '.'); ?></td>
                                    <td><?php echo $row['jumlah_keping']; ?></td>
                                    <td>Rp<?php echo number_format($row['nominal'] * $row['jumlah_keping'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-6">
                    <h2 class="section-title">Data Kas Akhir</h2>
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Nominal</th>
                                <th>Keping</th>
                                <th>Total Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($kas_akhir_detail as $row): ?>
                                <tr>
                                    <td>Rp<?php echo number_format($row['nominal'], 0, ',', '.'); ?></td>
                                    <td><?php echo $row['jumlah_keping']; ?></td>
                                    <td>Rp<?php echo number_format($row['nominal'] * $row['jumlah_keping'], 0, ',', '.'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- View Pemasukan Kasir -->
            <h2 class="section-title">Pemasukan Kasir</h2>
            <table class="table table-striped table-bordered">
                <tr><th>Kode Transaksi</th><th>Kode Akun</th><th>Jumlah (Rp)</th><th>Keterangan</th><th>Tanggal</th><th>Waktu</th></tr>
                <?php if ($pemasukan_kasir_closing_kasir): ?>
                    <?php foreach ($pemasukan_kasir_closing_kasir as $pemasukan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pemasukan['kode_transaksi']); ?></td>
                            <td><?php echo htmlspecialchars($pemasukan['kode_akun']); ?></td>
                            <td>Rp<?php echo number_format($pemasukan['jumlah'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($pemasukan['keterangan_transaksi']); ?></td>
                            <td><?php echo htmlspecialchars($pemasukan['tanggal']); ?></td>
                            <td><?php echo htmlspecialchars($pemasukan['waktu']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">Tidak ada data pemasukan tercatat.</td></tr>
                <?php endif; ?>
            </table>

            <!-- View Pengeluaran Kasir -->
            <h2 class="section-title">Pengeluaran Kasir</h2>
            <table class="table table-striped table-bordered">
                <tr><th>Kode Transaksi</th><th>Kode Akun</th><th>Kategori Akun</th><th>Jumlah (Rp)</th><th>Keterangan</th><th>Tanggal</th><th>Waktu</th><th>Umur Pakai (Bulan)</th></tr>
                <?php if ($pengeluaran_kasir_closing_kasir): ?>
                    <?php foreach ($pengeluaran_kasir_closing_kasir as $pengeluaran): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($pengeluaran['kode_transaksi']); ?></td>
                            <td><?php echo htmlspecialchars($pengeluaran['kode_akun']); ?></td>
                            <td><?php echo htmlspecialchars($pengeluaran['kategori']); ?></td>
                            <td>Rp<?php echo number_format($pengeluaran['jumlah'], 0, ',', '.'); ?></td>
                            <td><?php echo htmlspecialchars($pengeluaran['keterangan_transaksi']); ?></td>
                            <td><?php echo htmlspecialchars($pengeluaran['tanggal']); ?></td>
                            <td><?php echo htmlspecialchars($pengeluaran['waktu']); ?></td>
                            <td><?php echo htmlspecialchars($pengeluaran['umur_pakai']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">Tidak ada data pengeluaran tercatat.</td></tr>
                <?php endif; ?>
            </table>

            <div class="btn-group mb-3">
                <a href="generate_pdf.php?kode_transaksi=<?php echo $kode_transaksi; ?>" class="btn btn-info"><i class="fas fa-file-pdf"></i> Unduh PDF</a>
                <a href="generate_excel.php?kode_transaksi=<?php echo $kode_transaksi; ?>" class="btn btn-success" download="Laporan_Closing_Kasir_<?php echo htmlspecialchars($kode_transaksi); ?>.xlsx"><i class="fas fa-file-excel"></i> Unduh Excel</a>
                <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Cetak</button>
                <?php if ($canRequestRevision): ?>
                    <a href="closing_revision_request.php?kode_transaksi=<?php echo urlencode($kode_transaksi); ?>" class="btn btn-warning"><i class="fas fa-code-branch"></i> Ajukan Revisi</a>
                <?php endif; ?>
            </div>
            <div>
                <button class="btn btn-secondary" onclick="window.location.href='index_kasir.php'"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard Kasir</button>
            </div>
        </div>
    </div>

    <?php if (!empty($revisionSummary) && $hasLineItemChanges): ?>
    <div class="revision-modal" id="revisionDetailModal" onclick="closeRevisionDetailModal(event)">
        <div class="revision-modal-card">
            <div class="revision-modal-header">
                <div>
                    <h2 class="section-title" style="margin:0 0 6px 0;">Detail Revisi Item</h2>
                    <p style="color: var(--text-muted); margin: 0;">
                        Menampilkan item yang ditambahkan, dihapus, atau diubah dari transaksi lama ke transaksi revisi baru.
                    </p>
                    <?php if (!empty($revisionSummary['request']['alasan'])): ?>
                        <div class="revision-modal-reason">
                            <strong>Alasan Revisi</strong>
                            <div class="revision-modal-reason-text">
                                <?php echo nl2br(htmlspecialchars($revisionSummary['request']['alasan'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-secondary" onclick="closeRevisionDetailModal()">
                    <i class="fas fa-times"></i> Tutup
                </button>
            </div>
            <div class="revision-modal-grid">
                <?php foreach (($revisionSummary['line_item_changes'] ?? []) as $sectionChange): ?>
                    <?php if (empty($sectionChange['added']) && empty($sectionChange['removed']) && empty($sectionChange['changed'])) continue; ?>
                    <div class="revision-change-block">
                        <h4><?php echo htmlspecialchars($sectionChange['label']); ?></h4>
                        <div class="revision-change-list">
                            <?php foreach ($sectionChange['added'] as $item): ?>
                                <div class="revision-change-item">
                                    <div class="revision-pill added">Ditambahkan</div>
                                    <div class="revision-change-title"><?php echo htmlspecialchars($item['label']); ?></div>
                                    <div class="revision-change-meta">
                                        <?php echo htmlspecialchars($item['value_label']); ?> baru:
                                        <strong><?php echo htmlspecialchars($formatRevisionDetailValue($item['value_label'] ?? null, $item['new_value'] ?? null)); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($sectionChange['removed'] as $item): ?>
                                <div class="revision-change-item">
                                    <div class="revision-pill removed">Dihapus</div>
                                    <div class="revision-change-title"><?php echo htmlspecialchars($item['label']); ?></div>
                                    <div class="revision-change-meta">
                                        <?php echo htmlspecialchars($item['value_label']); ?> lama:
                                        <strong><?php echo htmlspecialchars($formatRevisionDetailValue($item['value_label'] ?? null, $item['old_value'] ?? null)); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($sectionChange['changed'] as $item): ?>
                                <div class="revision-change-item">
                                    <div class="revision-pill changed">Diubah</div>
                                    <div class="revision-change-title"><?php echo htmlspecialchars($item['label']); ?></div>
                                    <div class="revision-change-meta">
                                        <?php echo htmlspecialchars($item['value_label']); ?> lama:
                                        <strong><?php echo htmlspecialchars($formatRevisionDetailValue($item['value_label'] ?? null, $item['old_value'] ?? null)); ?></strong><br>
                                        <?php echo htmlspecialchars($item['value_label']); ?> baru:
                                        <strong><?php echo htmlspecialchars($formatRevisionDetailValue($item['value_label'] ?? null, $item['new_value'] ?? null)); ?></strong>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="assets/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script>
        function openRevisionDetailModal() {
            const modal = document.getElementById('revisionDetailModal');
            if (modal) modal.classList.add('show');
        }

        function closeRevisionDetailModal(event) {
            const modal = document.getElementById('revisionDetailModal');
            if (!modal) return;
            if (!event || event.target === modal) {
                modal.classList.remove('show');
            }
        }
    </script>
</body>
</html>
<?php
$pdo = null;
?>
