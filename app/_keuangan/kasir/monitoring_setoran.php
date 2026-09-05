<?php
// Sumber: web_kasir/monitoring_setoran.php — monitoring 3-stage status
// setoran (kasir->keuangan->bank). Gerbang asli role==='super_admin'
// -> kasir_approve (Task 10: ADM+KEU).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

date_default_timezone_set('Asia/Jakarta');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$kode_karyawan = $kode_karyawan_aktif;
$username = $nama_karyawan_aktif;

// Fetch filter parameters
$tanggal_awal = $_POST['tanggal_awal'] ?? $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_POST['tanggal_akhir'] ?? $_GET['tanggal_akhir'] ?? '';
$cabang = $_POST['cabang'] ?? $_GET['cabang'] ?? 'all';
$status_filter = $_POST['status_filter'] ?? $_GET['status_filter'] ?? 'all';

// COMPREHENSIVE monitoring query - tracking ALL transactions across 3 main tables
$sql_setoran = "
    SELECT 
        kt.id,
        kt.kode_transaksi,
        kt.tanggal_transaksi,
        kt.tanggal_closing,
        kt.jam_closing,
        kt.setoran_real,
        kt.data_setoran,
        kt.deposit_status,
        kt.nama_cabang,
        kt.kode_karyawan,
        kt.kode_setoran,
        kt.jumlah_diterima_fisik,
        kt.selisih_fisik,
        kt.deposit_difference_status,
        kt.catatan_validasi,
        kt.validasi_at,
        kt.validasi_by,
        kt.status as transaction_status,
        COALESCE(u.nama_lengkap, 'N/A') as nama_karyawan,
        
        -- SETORAN KEUANGAN tracking
        sk.id as setoran_id,
        sk.nama_pengantar,
        sk.status as status_setoran_keuangan,
        sk.tanggal_setoran as tanggal_setoran_keuangan,
        sk.created_at as waktu_buat_setoran,
        sk.jumlah_diterima as jumlah_diterima_keuangan,
        sk.selisih_setoran as selisih_setoran_keuangan,
        sk.updated_by as diupdate_oleh,
        sk.updated_at as waktu_update_setoran,
        
        -- SETORAN KE BANK tracking
        sb.id as bank_setoran_id,
        sb.kode_setoran as kode_setoran_bank,
        sb.tanggal_setoran as tanggal_setor_bank,
        sb.rekening_tujuan,
        sb.total_setoran as total_setor_bank,
        sb.metode_setoran,
        sb.created_at as waktu_setor_bank,
        sb.created_by as disetor_oleh,
        u_bank.nama_lengkap as nama_disetor_bank,
        
        -- Enhanced transaction type detection
        CASE 
            WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'CLOSING'
            WHEN EXISTS (
                SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
            ) THEN 'DARI_CLOSING'
            WHEN kt.jenis_closing IS NOT NULL THEN 'CLOSING_GRUP'
            WHEN kt.is_part_of_closing = 1 THEN 'BAGIAN_CLOSING'
            ELSE 'TRANSAKSI_REGULER'
        END as jenis_transaksi,
        
        -- COMPREHENSIVE status workflow tracking (3-stage pipeline)
        CASE 
            -- Stage 3: Bank Level
            WHEN sb.id IS NOT NULL THEN 'STAGE_3_DISETOR_BANK'
            -- Stage 2: Setoran Keuangan Level  
            WHEN sk.status = 'Sudah Disetor ke Bank' THEN 'STAGE_2_SIAP_BANK' 
            WHEN sk.status = 'Validasi Keuangan OK' THEN 'STAGE_2_VALIDASI_OK'
            WHEN sk.status = 'Validasi Keuangan SELISIH' THEN 'STAGE_2_SELISIH'
            WHEN sk.status = 'Diterima Staff Keuangan' THEN 'STAGE_2_DITERIMA'
            WHEN sk.status = 'Sedang Dibawa Kurir' THEN 'STAGE_2_KURIR'
            WHEN sk.status = 'Ada yang Dikembalikan ke CS' THEN 'STAGE_2_DIKEMBALIKAN'
            -- Stage 1: Transaction Level
            WHEN kt.deposit_status = 'Validasi Keuangan OK' THEN 'STAGE_1_OK'
            WHEN kt.deposit_status = 'Validasi Keuangan SELISIH' THEN 'STAGE_1_SELISIH'
            WHEN kt.deposit_status = 'Diterima Staff Keuangan' THEN 'STAGE_1_DITERIMA'
            WHEN kt.deposit_status = 'Sedang Dibawa Kurir' THEN 'STAGE_1_KURIR'
            WHEN kt.deposit_status = 'Dikembalikan ke CS' THEN 'STAGE_1_DIKEMBALIKAN'
            WHEN kt.deposit_status = 'Sudah Disetor ke Bank' THEN 'STAGE_1_DISETOR'
            WHEN kt.status = 'end proses' AND kt.deposit_status = 'Belum Disetor' THEN 'STAGE_1_BELUM_DISETOR'
            WHEN kt.status = 'on proses' THEN 'STAGE_0_BERLANGSUNG'
            ELSE 'STAGE_UNKNOWN'
        END as status_workflow_comprehensive,
        
        -- Status priority for ordering
        CASE 
            WHEN kt.status = 'on proses' THEN 1
            WHEN kt.deposit_status = 'Sedang Dibawa Kurir' THEN 2
            WHEN kt.deposit_status = 'Diterima Staff Keuangan' THEN 3
            WHEN kt.deposit_status = 'Validasi Keuangan SELISIH' THEN 4
            WHEN kt.deposit_status = 'Validasi Keuangan OK' THEN 5
            WHEN sk.status = 'Validasi Keuangan OK' THEN 6
            WHEN sb.id IS NOT NULL THEN 7
            ELSE 8
        END as priority_order
        
    FROM kasir_transactions_closing_kasir kt
    LEFT JOIN tbuser u ON kt.kode_karyawan = u.kode_karyawan
    LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
    LEFT JOIN setoran_ke_bank_detail_closing_kasir sbd ON sk.id = sbd.setoran_keuangan_id
    LEFT JOIN setoran_ke_bank_closing_kasir sb ON sbd.setoran_ke_bank_id = sb.id
    LEFT JOIN tbuser u_bank ON sb.created_by = u_bank.kode_karyawan
    WHERE 1=1";

$params = [];

// Apply filters
if ($tanggal_awal && $tanggal_akhir) {
    $sql_setoran .= " AND kt.tanggal_transaksi BETWEEN ? AND ?";
    $params[] = $tanggal_awal;
    $params[] = $tanggal_akhir;
}

if ($cabang !== 'all') {
    $sql_setoran .= " AND kt.nama_cabang = ?";
    $params[] = $cabang;
}

// Add status filter - support comprehensive workflow status
if ($status_filter !== 'all') {
    if (in_array($status_filter, ['STAGE_0_BERLANGSUNG', 'STAGE_1_BELUM_DISETOR', 'STAGE_1_KURIR', 'STAGE_1_DITERIMA', 'STAGE_1_SELISIH', 'STAGE_1_OK', 'STAGE_2_KURIR', 'STAGE_2_DITERIMA', 'STAGE_2_SELISIH', 'STAGE_2_VALIDASI_OK', 'STAGE_2_SIAP_BANK', 'STAGE_3_DISETOR_BANK'])) {
        // New comprehensive workflow status filter
        $sql_setoran .= " HAVING status_workflow_comprehensive = ?";
        $params[] = $status_filter;
    } else {
        // Legacy deposit status filter
        $sql_setoran .= " AND kt.deposit_status = ?";
        $params[] = $status_filter;
    }
}

// ORDER BY priority with comprehensive workflow tracking
$sql_setoran .= " ORDER BY 
    priority_order ASC,
    kt.tanggal_transaksi DESC, 
    kt.jam_closing DESC, 
    kt.kode_transaksi ASC";

// Execute query
$stmt_setoran = $pdo->prepare($sql_setoran);
$stmt_setoran->execute($params);
$setoran_list = $stmt_setoran->fetchAll(PDO::FETCH_ASSOC);

// Get cabang list for filter dropdown
$sql_cabang = "SELECT DISTINCT nama_cabang FROM kasir_transactions_closing_kasir WHERE nama_cabang IS NOT NULL AND nama_cabang != '' ORDER BY nama_cabang";
$stmt_cabang = $pdo->query($sql_cabang);
$cabang_list = $stmt_cabang->fetchAll(PDO::FETCH_COLUMN);

function formatRupiah($angka) {
    if ($angka === null || $angka === '') {
        return 'Rp 0';
    }
    return 'Rp ' . number_format(floatval($angka), 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Setoran</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="includes/sidebar.css" rel="stylesheet">
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
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --secondary-color: #6c757d;
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

        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .welcome-card h1 {
            font-size: 24px;
            margin-bottom: 15px;
            color: var(--text-dark);
        }

        .info-tags {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }

        .info-tag {
            background: var(--background-light);
            padding: 8px 12px;
            border-radius: 12px;
            font-size: 14px;
            color: var(--text-dark);
        }

        .filter-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .form-inline {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-label {
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--text-dark);
            font-size: 14px;
        }

        .form-control {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: white;
            min-width: 120px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-success {
            background-color: var(--success-color);
            color: white;
        }

        .btn-success:hover {
            background-color: #1e7e34;
        }

        .btn-info {
            background-color: var(--info-color);
            color: white;
        }

        .btn-info:hover {
            background-color: #117a8b;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 24px;
        }

        .content-header {
            background: var(--background-light);
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .content-header h3 {
            margin: 0;
            color: var(--text-dark);
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .content-body {
            padding: 24px;
        }

        .workflow-info {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .workflow-info h6 {
            margin-bottom: 10px;
            color: var(--text-dark);
            font-weight: 600;
        }

        .workflow-info p {
            margin: 0;
            color: var(--text-muted);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid transparent;
        }

        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }

        .table-enhanced {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            font-weight: 600;
            padding: 12px 8px;
            text-align: left;
            font-size: 13px;
            border: none;
        }

        .table td {
            padding: 12px 8px;
            border-bottom: 1px solid var(--border-color);
            font-size: 13px;
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background: rgba(0,123,255,0.05);
        }

        .kode-transaksi {
            font-family: monospace;
            font-weight: 600;
            color: var(--primary-color);
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .bg-kurir { background: linear-gradient(135deg, #ffc107, #e0a800); color: #212529; }
        .bg-diterima { background: linear-gradient(135deg, #17a2b8, #138496); color: white; }
        .bg-selisih { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
        .bg-ok { background: linear-gradient(135deg, #28a745, #1e7e34); color: white; }
        .bg-kembali { background: linear-gradient(135deg, #6c757d, #5a6268); color: white; }
        .bg-disetor { background: linear-gradient(135deg, #007bff, #0056b3); color: white; }
        .bg-closing { background: linear-gradient(135deg, #fd7e14, #e55a00); color: white; }

        .table-wrapper {
            overflow-x: auto;
            scrollbar-width: thin;
            scrollbar-color: #888 #f1f1f1;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 12px;
        }

        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 6px;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 6px;
        }

        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            font-style: italic;
        }

        @media (max-width: 768px) {
            .form-inline {
                flex-direction: column;
                align-items: stretch;
            }

            .form-group {
                width: 100%;
            }

            .table th,
            .table td {
                padding: 8px 4px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($username); ?></strong>
                <p style="color: var(--text-muted); font-size: 12px;">Super Admin</p>
            </div>
        </div>

        <div class="welcome-card">
            <h1><i class="fas fa-chart-line"></i> Monitoring Setoran</h1>
            <p style="color: var(--text-muted); margin-bottom: 0;">Monitoring detail setiap transaksi closing dengan status tracking real-time. Data diurutkan berdasarkan prioritas status untuk memudahkan workflow keuangan pusat.</p>
            <div class="info-tags">
                <div class="info-tag">User: <?php echo htmlspecialchars($username); ?></div>
                <div class="info-tag">Role: Super Admin</div>
                <div class="info-tag">Tanggal: <?php echo date('d M Y'); ?></div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-card">
            <form action="" method="POST" class="form-inline">
                <div class="form-group">
                    <label class="form-label">Tanggal Awal:</label>
                    <input type="date" name="tanggal_awal" class="form-control" value="<?php echo htmlspecialchars($tanggal_awal); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Akhir:</label>
                    <input type="date" name="tanggal_akhir" class="form-control" value="<?php echo htmlspecialchars($tanggal_akhir); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Cabang:</label>
                    <select name="cabang" class="form-control">
                        <option value="all">Semua Cabang</option>
                        <?php foreach ($cabang_list as $nama_cabang): ?>
                            <option value="<?php echo htmlspecialchars($nama_cabang); ?>" <?php echo $cabang == $nama_cabang ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($nama_cabang)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Status Tracking (3 Stage):</label>
                    <select name="status_filter" class="form-control">
                        <option value="all">🔍 Semua Status</option>
                        <optgroup label="📊 Stage 0: Transaksi">
                            <option value="STAGE_0_BERLANGSUNG" <?php echo ($status_filter == 'STAGE_0_BERLANGSUNG') ? 'selected' : ''; ?>>⏳ Sedang Berlangsung</option>
                        </optgroup>
                        <optgroup label="💰 Stage 1: Kasir Transaksi">
                            <option value="STAGE_1_BELUM_DISETOR" <?php echo ($status_filter == 'STAGE_1_BELUM_DISETOR') ? 'selected' : ''; ?>>⏸️ Belum Disetor</option>
                            <option value="STAGE_1_KURIR" <?php echo ($status_filter == 'STAGE_1_KURIR') ? 'selected' : ''; ?>>🚚 Dibawa Kurir</option>
                            <option value="STAGE_1_DITERIMA" <?php echo ($status_filter == 'STAGE_1_DITERIMA') ? 'selected' : ''; ?>>📨 Diterima Staff</option>
                            <option value="STAGE_1_SELISIH" <?php echo ($status_filter == 'STAGE_1_SELISIH') ? 'selected' : ''; ?>>⚠️ Ada Selisih</option>
                            <option value="STAGE_1_OK" <?php echo ($status_filter == 'STAGE_1_OK') ? 'selected' : ''; ?>>✅ Validasi OK</option>
                        </optgroup>
                        <optgroup label="🏢 Stage 2: Setoran Keuangan">
                            <option value="STAGE_2_KURIR" <?php echo ($status_filter == 'STAGE_2_KURIR') ? 'selected' : ''; ?>>🚚 Kurir Keuangan</option>
                            <option value="STAGE_2_DITERIMA" <?php echo ($status_filter == 'STAGE_2_DITERIMA') ? 'selected' : ''; ?>>📨 Diterima Keuangan</option>
                            <option value="STAGE_2_SELISIH" <?php echo ($status_filter == 'STAGE_2_SELISIH') ? 'selected' : ''; ?>>⚠️ Selisih Keuangan</option>
                            <option value="STAGE_2_VALIDASI_OK" <?php echo ($status_filter == 'STAGE_2_VALIDASI_OK') ? 'selected' : ''; ?>>✅ Validasi Keuangan OK</option>
                            <option value="STAGE_2_SIAP_BANK" <?php echo ($status_filter == 'STAGE_2_SIAP_BANK') ? 'selected' : ''; ?>>🏦 Siap ke Bank</option>
                        </optgroup>
                        <optgroup label="🏦 Stage 3: Setoran Bank">
                            <option value="STAGE_3_DISETOR_BANK" <?php echo ($status_filter == 'STAGE_3_DISETOR_BANK') ? 'selected' : ''; ?>>💎 Sudah Disetor Bank</option>
                        </optgroup>
                        <optgroup label="🔧 Status Legacy">
                            <option value="Sedang Dibawa Kurir" <?php echo ($status_filter == 'Sedang Dibawa Kurir') ? 'selected' : ''; ?>>Sedang Dibawa Kurir</option>
                            <option value="Diterima Staff Keuangan" <?php echo ($status_filter == 'Diterima Staff Keuangan') ? 'selected' : ''; ?>>Diterima Staff Keuangan</option>
                            <option value="Validasi Keuangan OK" <?php echo ($status_filter == 'Validasi Keuangan OK') ? 'selected' : ''; ?>>Validasi Keuangan OK</option>
                            <option value="Validasi Keuangan SELISIH" <?php echo ($status_filter == 'Validasi Keuangan SELISIH') ? 'selected' : ''; ?>>Validasi Keuangan SELISIH</option>
                        </optgroup>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>

        <!-- Content Card -->
        <div class="content-card">
            <div class="content-header">
                <h3><i class="fas fa-chart-line"></i> Monitoring Transaksi Closing</h3>
                <div class="export-buttons">
                    <a href="export/export_excel_setoran.php?type=monitoring" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="export/export_csv.php?type=monitoring" class="btn btn-info btn-sm">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                </div>
            </div>
            <div class="content-body">
                <div class="workflow-info">
                    <h6><i class="fas fa-info-circle"></i> Informasi Monitoring</h6>
                    <p>Monitoring detail setiap transaksi closing dengan status tracking real-time. Data diurutkan berdasarkan prioritas status untuk memudahkan workflow keuangan pusat.</p>
                </div>
                
                <?php if ($setoran_list): ?>
                    <!-- 3-Stage Comprehensive Dashboard -->
                    <?php
                    $summary_comprehensive = [];
                    $stage_summary = ['Stage_0' => 0, 'Stage_1' => 0, 'Stage_2' => 0, 'Stage_3' => 0];
                    $total_nominal = 0;
                    foreach ($setoran_list as $t) {
                        $workflow = $t['status_workflow_comprehensive'];
                        if (!isset($summary_comprehensive[$workflow])) {
                            $summary_comprehensive[$workflow] = ['count' => 0, 'nominal' => 0];
                        }
                        $summary_comprehensive[$workflow]['count']++;
                        $summary_comprehensive[$workflow]['nominal'] += $t['setoran_real'];
                        $total_nominal += $t['setoran_real'];
                        
                        // Count by stage
                        if (strpos($workflow, 'STAGE_0') === 0) $stage_summary['Stage_0']++;
                        elseif (strpos($workflow, 'STAGE_1') === 0) $stage_summary['Stage_1']++;
                        elseif (strpos($workflow, 'STAGE_2') === 0) $stage_summary['Stage_2']++;
                        elseif (strpos($workflow, 'STAGE_3') === 0) $stage_summary['Stage_3']++;
                    }
                    ?>
                    
                    <!-- Stage Overview -->
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
                        <div style="background: linear-gradient(135deg, #17a2b8, #138496); border-radius: 12px; padding: 15px; color: white; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold;"><?php echo $stage_summary['Stage_0']; ?></div>
                            <div style="font-size: 12px; opacity: 0.9;">📊 Stage 0: Transaksi</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #ffc107, #e0a800); border-radius: 12px; padding: 15px; color: white; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold;"><?php echo $stage_summary['Stage_1']; ?></div>
                            <div style="font-size: 12px; opacity: 0.9;">💰 Stage 1: Kasir</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #fd7e14, #e55a00); border-radius: 12px; padding: 15px; color: white; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold;"><?php echo $stage_summary['Stage_2']; ?></div>
                            <div style="font-size: 12px; opacity: 0.9;">🏢 Stage 2: Keuangan</div>
                        </div>
                        <div style="background: linear-gradient(135deg, #28a745, #1e7e34); border-radius: 12px; padding: 15px; color: white; text-align: center;">
                            <div style="font-size: 24px; font-weight: bold;"><?php echo $stage_summary['Stage_3']; ?></div>
                            <div style="font-size: 12px; opacity: 0.9;">🏦 Stage 3: Bank</div>
                        </div>
                    </div>
                    
                    <!-- Detailed Status Grid -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px;">
                        <?php 
                        $status_mapping = [
                            'STAGE_0_BERLANGSUNG' => ['⏳ Berlangsung', '#17a2b8'],
                            'STAGE_1_BELUM_DISETOR' => ['⏸️ Belum Disetor', '#6c757d'],
                            'STAGE_1_KURIR' => ['🚚 Kurir', '#ffc107'],
                            'STAGE_1_DITERIMA' => ['📨 Diterima', '#17a2b8'],
                            'STAGE_1_SELISIH' => ['⚠️ Selisih', '#dc3545'],
                            'STAGE_1_OK' => ['✅ OK', '#28a745'],
                            'STAGE_2_KURIR' => ['🚚 Kurir Keuangan', '#fd7e14'],
                            'STAGE_2_DITERIMA' => ['📨 Diterima Keuangan', '#fd7e14'],
                            'STAGE_2_SELISIH' => ['⚠️ Selisih Keuangan', '#dc3545'],
                            'STAGE_2_VALIDASI_OK' => ['✅ Validasi OK', '#28a745'],
                            'STAGE_2_SIAP_BANK' => ['🏦 Siap Bank', '#007bff'],
                            'STAGE_3_DISETOR_BANK' => ['💎 Disetor Bank', '#28a745']
                        ];
                        
                        foreach ($status_mapping as $status => $info): 
                            if (($summary_comprehensive[$status]['count'] ?? 0) > 0):
                        ?>
                            <div style="background: white; border-radius: 8px; padding: 12px; border-left: 4px solid <?php echo $info[1]; ?>; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                                    <span style="font-size: 11px; font-weight: 600; color: var(--text-dark);"><?php echo $info[0]; ?></span>
                                    <span style="background: <?php echo $info[1]; ?>; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: 600;"><?php echo $summary_comprehensive[$status]['count']; ?></span>
                                </div>
                                <div style="font-size: 12px; font-weight: 600; color: var(--success-color);">
                                    <?php echo formatRupiah($summary_comprehensive[$status]['nominal']); ?>
                                </div>
                            </div>
                        <?php endif; endforeach; ?>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Total: <strong><?php echo count($setoran_list); ?> transaksi</strong> dengan nilai <strong><?php echo formatRupiah($total_nominal); ?></strong>
                    </div>

                    <div class="table-wrapper">
                        <div class="table-enhanced">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th width="100">Kode Transaksi</th>
                                        <th width="80">Tanggal</th>
                                        <th width="60">Jam</th>
                                        <th width="100">Cabang</th>
                                        <th width="80">Kasir</th>
                                        <th width="60">Jenis</th>
                                        <th width="90">Nominal</th>
                                        <th width="120">Status 3-Stage</th>
                                        <th width="100">Timeline Workflow</th>
                                        <th width="100">Info Setoran</th>
                                        <th width="100">Info Bank</th>
                                        <th width="80">Selisih</th>
                                        <th width="150">Catatan/Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($setoran_list as $transaksi): ?>
                                        <tr>
                                            <td class="kode-transaksi" style="font-size: 10px;"><?php echo htmlspecialchars(substr($transaksi['kode_transaksi'], -12)); ?></td>
                                            <td style="font-size: 11px;"><?php echo date('d/m/Y', strtotime($transaksi['tanggal_transaksi'])); ?></td>
                                            <td style="font-size: 10px;"><?php echo $transaksi['jam_closing'] ? date('H:i', strtotime($transaksi['jam_closing'])) : '-'; ?></td>
                                            <td style="font-size: 11px;"><?php echo htmlspecialchars(substr($transaksi['nama_cabang'], -15)); ?></td>
                                            <td style="font-size: 10px;"><?php echo htmlspecialchars(substr($transaksi['nama_karyawan'], 0, 12)); ?></td>
                                            <td>
                                                <?php 
                                                $jenis_badge = '';
                                                $jenis_text = '';
                                                switch($transaksi['jenis_transaksi']) {
                                                    case 'CLOSING': $jenis_badge = 'bg-closing'; $jenis_text = 'CLO'; break;
                                                    case 'DARI_CLOSING': $jenis_badge = 'bg-closing'; $jenis_text = 'D-CLO'; break;
                                                    case 'CLOSING_GRUP': $jenis_badge = 'bg-closing'; $jenis_text = 'GRP'; break;
                                                    case 'BAGIAN_CLOSING': $jenis_badge = 'bg-closing'; $jenis_text = 'B-CLO'; break;
                                                    default: $jenis_badge = 'bg-ok'; $jenis_text = 'REG'; break;
                                                }
                                                ?>
                                                <span class="status-badge <?php echo $jenis_badge; ?>" style="font-size: 8px;"><?php echo $jenis_text; ?></span>
                                            </td>
                                            <td style="text-align: right; font-weight: 600; color: var(--success-color); font-size: 10px;"><?php echo str_replace('Rp ', '', formatRupiah($transaksi['setoran_real'])); ?></td>
                                            <!-- 3-Stage Status Column -->
                                            <td style="font-size: 9px;">
                                                <?php
                                                $workflow_comp = $transaksi['status_workflow_comprehensive'];
                                                $stage_info = '';
                                                $stage_color = '';
                                                $stage_icon = '';
                                                
                                                if (strpos($workflow_comp, 'STAGE_0') === 0) {
                                                    $stage_color = '#17a2b8'; $stage_icon = '📊'; $stage_info = 'S0';
                                                } elseif (strpos($workflow_comp, 'STAGE_1') === 0) {
                                                    $stage_color = '#ffc107'; $stage_icon = '💰'; $stage_info = 'S1';
                                                } elseif (strpos($workflow_comp, 'STAGE_2') === 0) {
                                                    $stage_color = '#fd7e14'; $stage_icon = '🏢'; $stage_info = 'S2';
                                                } elseif (strpos($workflow_comp, 'STAGE_3') === 0) {
                                                    $stage_color = '#28a745'; $stage_icon = '🏦'; $stage_info = 'S3';
                                                } else {
                                                    $stage_color = '#6c757d'; $stage_icon = '❓'; $stage_info = '??';
                                                }
                                                
                                                $status_detail = '';
                                                switch($workflow_comp) {
                                                    case 'STAGE_0_BERLANGSUNG': $status_detail = 'Berlangsung'; break;
                                                    case 'STAGE_1_BELUM_DISETOR': $status_detail = 'Belum Disetor'; break;
                                                    case 'STAGE_1_KURIR': $status_detail = 'Kurir'; break;
                                                    case 'STAGE_1_DITERIMA': $status_detail = 'Diterima'; break;
                                                    case 'STAGE_1_SELISIH': $status_detail = 'Selisih'; break;
                                                    case 'STAGE_1_OK': $status_detail = 'OK'; break;
                                                    case 'STAGE_2_KURIR': $status_detail = 'Kurir'; break;
                                                    case 'STAGE_2_DITERIMA': $status_detail = 'Diterima'; break;
                                                    case 'STAGE_2_SELISIH': $status_detail = 'Selisih'; break;
                                                    case 'STAGE_2_VALIDASI_OK': $status_detail = 'Valid'; break;
                                                    case 'STAGE_2_SIAP_BANK': $status_detail = 'Siap Bank'; break;
                                                    case 'STAGE_3_DISETOR_BANK': $status_detail = 'Disetor'; break;
                                                    default: $status_detail = 'Unknown'; break;
                                                }
                                                ?>
                                                <div style="text-align: center;">
                                                    <div style="background: <?php echo $stage_color; ?>; color: white; padding: 2px 6px; border-radius: 8px; font-weight: 600; margin-bottom: 2px;"><?php echo $stage_icon . ' ' . $stage_info; ?></div>
                                                    <div style="font-size: 8px; color: var(--text-muted);"><?php echo $status_detail; ?></div>
                                                </div>
                                            </td>
                                            <!-- Visual Timeline Column -->
                                            <td style="font-size: 8px; text-align: center;">
                                                <?php
                                                $current_stage = 0;
                                                if (strpos($workflow_comp, 'STAGE_1') === 0) $current_stage = 1;
                                                elseif (strpos($workflow_comp, 'STAGE_2') === 0) $current_stage = 2;
                                                elseif (strpos($workflow_comp, 'STAGE_3') === 0) $current_stage = 3;
                                                ?>
                                                <div style="display: flex; align-items: center; gap: 2px;">
                                                    <div style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $current_stage >= 1 ? '#28a745' : '#e9ecef'; ?>; font-size: 8px; display: flex; align-items: center; justify-content: center; color: white;">1</div>
                                                    <div style="width: 15px; height: 2px; background: <?php echo $current_stage >= 2 ? '#28a745' : '#e9ecef'; ?>;"></div>
                                                    <div style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $current_stage >= 2 ? '#fd7e14' : '#e9ecef'; ?>; font-size: 8px; display: flex; align-items: center; justify-content: center; color: white;">2</div>
                                                    <div style="width: 15px; height: 2px; background: <?php echo $current_stage >= 3 ? '#28a745' : '#e9ecef'; ?>;"></div>
                                                    <div style="width: 12px; height: 12px; border-radius: 50%; background: <?php echo $current_stage >= 3 ? '#007bff' : '#e9ecef'; ?>; font-size: 8px; display: flex; align-items: center; justify-content: center; color: white;">3</div>
                                                </div>
                                            </td>
                                            <!-- Info Setoran Column -->
                                            <td style="font-size: 9px;">
                                                <?php if ($transaksi['setoran_id']): ?>
                                                    <div style="line-height: 1.1;">
                                                        <strong>ID: <?php echo $transaksi['setoran_id']; ?></strong><br>
                                                        <span style="color: var(--text-muted);"><?php echo $transaksi['status_setoran_keuangan'] ?? 'N/A'; ?></span><br>
                                                        <span style="font-size: 8px; color: var(--text-muted);"><?php echo $transaksi['tanggal_setoran_keuangan'] ? date('d/m', strtotime($transaksi['tanggal_setoran_keuangan'])) : '-'; ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-style: italic;">Belum setoran</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Info Bank Column -->
                                            <td style="font-size: 9px;">
                                                <?php if ($transaksi['bank_setoran_id']): ?>
                                                    <div style="line-height: 1.1;">
                                                        <strong><?php echo substr($transaksi['kode_setoran_bank'], -8); ?></strong><br>
                                                        <span style="color: var(--text-muted);"><?php echo $transaksi['metode_setoran']; ?></span><br>
                                                        <span style="font-size: 8px; color: var(--text-muted);"><?php echo $transaksi['tanggal_setor_bank'] ? date('d/m', strtotime($transaksi['tanggal_setor_bank'])) : '-'; ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-style: italic;">Belum bank</span>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Selisih Column -->
                                            <td style="text-align: right; font-size: 10px; <?php echo ($transaksi['selisih_fisik'] ?? 0) != 0 ? 'color: var(--danger-color); font-weight: 600;' : 'color: var(--text-muted);'; ?>">
                                                <?php echo ($transaksi['selisih_fisik'] ?? 0) == 0 ? '0' : str_replace('Rp ', '', formatRupiah($transaksi['selisih_fisik'])); ?>
                                            </td>
                                            <!-- Catatan/Progress Column -->
                                            <td style="font-size: 9px; max-width: 150px; word-wrap: break-word;">
                                                <?php if ($transaksi['catatan_validasi']): ?>
                                                    <div style="max-height: 35px; overflow-y: auto; background: #f8f9fa; padding: 3px; border-radius: 3px;">
                                                        <?php echo htmlspecialchars(substr($transaksi['catatan_validasi'], 0, 80)); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="color: var(--text-muted); font-size: 8px;">
                                                        <?php if ($transaksi['validasi_at']): ?>
                                                            Validasi: <?php echo date('d/m H:i', strtotime($transaksi['validasi_at'])); ?>
                                                        <?php elseif ($transaksi['waktu_buat_setoran']): ?>
                                                            Dibuat: <?php echo date('d/m H:i', strtotime($transaksi['waktu_buat_setoran'])); ?>
                                                        <?php else: ?>
                                                            No progress
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-file-alt"></i><br>
                        Tidak ada transaksi monitoring ditemukan
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Currency formatter utility
        function formatCurrency(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID');
        }

        // Table search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const table = document.querySelector('.table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                if (rows.length > 10) {
                    const searchBox = document.createElement('input');
                    searchBox.type = 'text';
                    searchBox.placeholder = 'Cari dalam tabel...';
                    searchBox.className = 'form-control';
                    searchBox.style.marginBottom = '15px';
                    searchBox.style.maxWidth = '300px';
                    searchBox.addEventListener('input', function() {
                        const searchTerm = this.value.toLowerCase();
                        rows.forEach(row => {
                            const text = row.textContent.toLowerCase();
                            row.style.display = text.includes(searchTerm) ? '' : 'none';
                        });
                    });
                    table.parentNode.insertBefore(searchBox, table);
                }
            }
        });
    </script>
</body>
</html>