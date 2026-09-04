<?php
// Sumber: web_kasir/setoran_bank_rekap.php — rekap riwayat setoran ke
// bank per rekening cabang. Gerbang asli super_admin-only murni (single
// check, gak ada vestigial kedua) -> kasir_admin (Task 10: ADM only).
require_once __DIR__ . '/koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_admin');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

$pdo = new PDO("mysql:host=localhost;dbname=fitmotor_dbbengkel", "fitmotor_LOGIN", "Sayalupa12");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$is_super_admin = true;
$is_admin = false;
$kode_karyawan = $kode_karyawan_aktif;
$username = $nama_karyawan_aktif;

// Fetch filter parameters
$tanggal_setor_awal = $_POST['tanggal_setor_awal'] ?? $_GET['tanggal_setor_awal'] ?? '';
$tanggal_setor_akhir = $_POST['tanggal_setor_akhir'] ?? $_GET['tanggal_setor_akhir'] ?? '';
$cabang = $_POST['cabang'] ?? $_GET['cabang'] ?? 'all';
$rekening_filter = $_POST['rekening_filter'] ?? $_GET['rekening_filter'] ?? 'all';
$bank_status = $_GET['bank_status'] ?? '';
$bank_message = $_GET['bank_message'] ?? '';

$history_filter_params = [];
if ($tanggal_setor_awal !== '') {
    $history_filter_params['tanggal_setor_awal'] = $tanggal_setor_awal;
}
if ($tanggal_setor_akhir !== '') {
    $history_filter_params['tanggal_setor_akhir'] = $tanggal_setor_akhir;
}
if ($cabang !== 'all' && $cabang !== '') {
    $history_filter_params['cabang'] = $cabang;
}
if ($rekening_filter !== 'all' && $rekening_filter !== '') {
    $history_filter_params['rekening_filter'] = $rekening_filter;
}
$history_filter_query = http_build_query($history_filter_params);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_tanggal_setor_bank'])) {
    $setoran_bank_id = (int)($_POST['setoran_bank_id'] ?? 0);
    $tanggal_setor_riil = trim($_POST['tanggal_setor_riil'] ?? '');

    $redirect_params = $history_filter_params;

    if ($setoran_bank_id <= 0) {
        $redirect_params['bank_status'] = 'error';
        $redirect_params['bank_message'] = 'Data setoran bank tidak valid.';
        header('Location: setoran_bank_rekap.php?' . http_build_query($redirect_params));
        exit;
    }

    $tanggal_valid = false;
    if ($tanggal_setor_riil !== '') {
        $tanggal_obj = DateTime::createFromFormat('Y-m-d', $tanggal_setor_riil);
        $tanggal_valid = $tanggal_obj instanceof DateTime && $tanggal_obj->format('Y-m-d') === $tanggal_setor_riil;
    }

    if (!$tanggal_valid) {
        $redirect_params['bank_status'] = 'error';
        $redirect_params['bank_message'] = 'Tanggal setor ke bank wajib diisi dengan format yang valid.';
        header('Location: setoran_bank_rekap.php?' . http_build_query($redirect_params));
        exit;
    }

    $stmt_update_tanggal_setor = $pdo->prepare("UPDATE setoran_ke_bank_closing_kasir SET tanggal_setor = ? WHERE id = ?");
    $stmt_update_tanggal_setor->execute([$tanggal_setor_riil, $setoran_bank_id]);

    $redirect_params['bank_status'] = 'success';
    $redirect_params['bank_message'] = 'Tanggal setor ke bank berhasil disimpan.';
    header('Location: setoran_bank_rekap.php?' . http_build_query($redirect_params));
    exit;
}

// Get cabang list for filter dropdown
$sql_cabang = "SELECT DISTINCT nama_cabang FROM setoran_keuangan_closing_kasir WHERE nama_cabang IS NOT NULL AND nama_cabang != '' ORDER BY nama_cabang";
$stmt_cabang = $pdo->query($sql_cabang);
$cabang_list = $stmt_cabang->fetchAll(PDO::FETCH_COLUMN);

// Get rekening list for dropdown grouped by no_rekening with all cabang names
$sql_rekening = "
    SELECT 
        mr.no_rekening,
        mr.nama_bank,
        MAX(mr.nama_rekening) as nama_rekening,
        MAX(mr.jenis_rekening) as jenis_rekening,
        GROUP_CONCAT(DISTINCT CONCAT(c.nama_cabang, '|', mr.id) ORDER BY c.nama_cabang SEPARATOR ';;') as cabang_info,
        GROUP_CONCAT(DISTINCT mr.id ORDER BY c.nama_cabang) as rekening_ids
    FROM master_rekening_cabang_closing_kasir mr
    JOIN tbcabang c ON c.cabang_ref_kode = mr.kode_cabang
    WHERE mr.status = 'active' 
    GROUP BY mr.no_rekening, mr.nama_bank
    ORDER BY mr.nama_bank, mr.no_rekening
";

$stmt_rekening = $pdo->query($sql_rekening);
$rekening_list = $stmt_rekening->fetchAll(PDO::FETCH_ASSOC);

// Bank history query with enhanced closing information
$sql_setoran = "
    SELECT sb.*, 
           GROUP_CONCAT(DISTINCT c.nama_cabang ORDER BY c.nama_cabang) as cabang_names,
           COUNT(DISTINCT sbd.setoran_keuangan_id) as total_setoran_count,
           u.nama_lengkap as created_by_name,
           MIN(kt.tanggal_closing) as tanggal_closing_transaksi,
           MAX(kt.tanggal_closing) as tanggal_closing_terakhir,
           MIN(sk.tanggal_setoran) as tanggal_setoran_awal,
           MAX(sk.tanggal_setoran) as tanggal_setoran_akhir,
           SUM(CASE WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' 
                    OR kt.jenis_closing IS NOT NULL
                    OR EXISTS (
                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                        WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                    ) THEN 1 ELSE 0 END) as total_closing_transactions,
           GROUP_CONCAT(DISTINCT 
               CASE WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' 
                    OR kt.jenis_closing IS NOT NULL
                    OR EXISTS (
                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                        WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                    ) 
               THEN CONCAT(c.nama_cabang, ' (', DATE_FORMAT(kt.tanggal_closing, '%d/%m/%Y'), ')')
               END 
               ORDER BY kt.tanggal_closing 
               SEPARATOR ', '
           ) as detail_closing_info
    FROM setoran_ke_bank_closing_kasir sb
    JOIN setoran_ke_bank_detail_closing_kasir sbd ON sb.id = sbd.setoran_ke_bank_id
    JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
    JOIN tbcabang c ON c.cabang_ref_kode = sk.kode_cabang
    LEFT JOIN tbuser u ON sb.created_by = u.kode_karyawan
    LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
    WHERE 1=1";

$params = [];

// Apply filters
if ($tanggal_setor_awal && $tanggal_setor_akhir) {
    $sql_setoran .= " AND sb.tanggal_setoran BETWEEN ? AND ?";
    $params[] = $tanggal_setor_awal;
    $params[] = $tanggal_setor_akhir;
}

if ($cabang !== 'all') {
    $sql_setoran .= " AND sk.nama_cabang = ?";
    $params[] = $cabang;
}

// Add rekening filter
if ($rekening_filter !== 'all' && !empty($rekening_filter)) {
    // Handle multiple rekening IDs (comma separated)
    $rekening_ids = explode(',', $rekening_filter);
    
    // Get the account info for filtering
    $placeholders = array_fill(0, count($rekening_ids), '?');
    $sql_get_rekening_info = "SELECT DISTINCT CONCAT(nama_bank, ' - ', no_rekening) as rekening_pattern 
                              FROM master_rekening_cabang_closing_kasir 
                              WHERE id IN (" . implode(',', $placeholders) . ") AND status = 'active'";
    $stmt_get_rekening = $pdo->prepare($sql_get_rekening_info);
    $stmt_get_rekening->execute($rekening_ids);
    $rekening_patterns = $stmt_get_rekening->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($rekening_patterns)) {
        $rekening_conditions = array();
        foreach ($rekening_patterns as $pattern) {
            $rekening_conditions[] = "sb.rekening_tujuan LIKE ?";
            $params[] = $pattern . '%';
        }
        $sql_setoran .= " AND (" . implode(' OR ', $rekening_conditions) . ")";
    }
}

$sql_setoran .= " GROUP BY sb.id ORDER BY sb.tanggal_setoran DESC";

// Execute query
$stmt_setoran = $pdo->prepare($sql_setoran);
$stmt_setoran->execute($params);
$setoran_list = $stmt_setoran->fetchAll(PDO::FETCH_ASSOC);

// Handle bank detail view for closing report
$bank_detail_view = null;
$closing_detail = [];
$all_closing_detail = [];
$bank_pengambilan_rows = [];
if (isset($_GET['bank_detail_id'])) {
    $bank_detail_id = $_GET['bank_detail_id'];
    
    $sql_bank_detail = "SELECT sb.*, u.nama_lengkap as created_by_name 
                       FROM setoran_ke_bank_closing_kasir sb 
                       LEFT JOIN tbuser u ON sb.created_by = u.kode_karyawan 
                       WHERE sb.id = ?";
    $stmt_bank_detail = $pdo->prepare($sql_bank_detail);
    $stmt_bank_detail->execute([$bank_detail_id]);
    $bank_detail_view = $stmt_bank_detail->fetch(PDO::FETCH_ASSOC);

    if ($bank_detail_view) {
        // Get all setoran details grouped by cabang dengan closing info
        $sql_closing = "SELECT 
                           sk.kode_cabang,
                           c.nama_cabang,
                           COUNT(sk.id) as total_setoran,
                           SUM(sk.jumlah_diterima) as total_nominal,
                           GROUP_CONCAT(sk.kode_setoran ORDER BY sk.tanggal_setoran) as kode_setoran_list,
                           MIN(sk.tanggal_setoran) as tanggal_awal,
                           MAX(sk.tanggal_setoran) as tanggal_akhir,
                           SUM(CASE WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' 
                                    OR EXISTS (
                                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                                        WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                    ) THEN 1 ELSE 0 END) as total_closing_transactions
                       FROM setoran_ke_bank_detail_closing_kasir sbd
                       JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                       JOIN tbcabang c ON c.cabang_ref_kode = sk.kode_cabang
                       LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                       WHERE sbd.setoran_ke_bank_id = ?
                       GROUP BY sk.kode_cabang, c.nama_cabang
                       ORDER BY c.nama_cabang";
        $stmt_closing = $pdo->prepare($sql_closing);
        $stmt_closing->execute([$bank_detail_id]);
        $closing_detail = $stmt_closing->fetchAll(PDO::FETCH_ASSOC);

        // Get all detail transactions
        $sql_all_detail = "SELECT 
                                c.nama_cabang,
                                sk.kode_setoran,
                                sk.tanggal_setoran,
                                kt.kode_transaksi,
                                kt.tanggal_transaksi,
                                kt.tanggal_closing,
                                kt.setoran_real,
                                kt.data_setoran,
                                kt.jumlah_diterima_fisik,
                                kt.deposit_status,
                                kt.setoran_real as setoran_awal,
                                -- Total yang sudah masuk ke kas (sampai dengan tanggal setor bank)
                                COALESCE((
                                    SELECT SUM(pk.jumlah) 
                                    FROM pemasukan_kasir_closing_kasir pk 
                                    WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                    AND pk.tanggal <= DATE(sk.tanggal_setoran)
                                ), 0) as total_masuk_kas,
                                -- Setoran Kekas Masuk = Total yang SUDAH masuk ke kas (sampai dengan tanggal setor)
                                COALESCE((
                                    SELECT SUM(pk.jumlah) 
                                    FROM pemasukan_kasir_closing_kasir pk 
                                    WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                    AND pk.tanggal <= DATE(sk.tanggal_setoran)
                                ), 0) as setoran_kekas_masuk,
                                -- Setoran Diterima = Sisa yang BELUM masuk ke kas
                                CASE 
                                    -- Jika semua sudah masuk kas (total >= setoran_real), maka = 0
                                    WHEN COALESCE((
                                        SELECT SUM(pk.jumlah) 
                                        FROM pemasukan_kasir_closing_kasir pk 
                                        WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                        AND pk.tanggal <= DATE(sk.tanggal_setoran)
                                    ), 0) >= kt.setoran_real THEN 0
                                    -- Jika ada jumlah_diterima_fisik, gunakan itu
                                    WHEN kt.jumlah_diterima_fisik IS NOT NULL AND kt.jumlah_diterima_fisik > 0
                                    THEN kt.jumlah_diterima_fisik
                                    -- Jika tidak, hitung sisa: setoran_real - total_masuk_kas
                                    ELSE kt.setoran_real - COALESCE((
                                        SELECT SUM(pk.jumlah) 
                                        FROM pemasukan_kasir_closing_kasir pk 
                                        WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                        AND pk.tanggal <= DATE(sk.tanggal_setoran)
                                    ), 0)
                                END as setoran_diterima,
                                CASE 
                                    WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                                    WHEN kt.jenis_closing IS NOT NULL THEN 'DARI CLOSING'
                                    WHEN EXISTS (
                                        SELECT 1 FROM pemasukan_kasir_closing_kasir pk2 
                                        WHERE pk2.nomor_transaksi_closing = kt.kode_transaksi
                                    ) THEN 'DARI CLOSING'
                                    ELSE 'TRANSAKSI BIASA'
                                END as jenis_transaksi
                           FROM setoran_ke_bank_detail_closing_kasir sbd
                           JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                           JOIN tbcabang c ON c.cabang_ref_kode = sk.kode_cabang
                           LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                           WHERE sbd.setoran_ke_bank_id = ?
                           ORDER BY c.nama_cabang, kt.tanggal_closing, sk.tanggal_setoran";
        $stmt_all = $pdo->prepare($sql_all_detail);
        $stmt_all->execute([$bank_detail_id]);
        $all_closing_detail = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        $sql_pengambilan_bank = "SELECT ps.kode_pengambilan,
                                        ps.parent_kode_pengambilan,
                                        ps.klasifikasi,
                                        ps.nominal_diambil,
                                        ps.nominal_sisa,
                                        ps.no_rekening_peminjam,
                                        ps.no_rekening_penerima,
                                        ps.tanggal_perencanaan_setor,
                                        ps.status,
                                        c.nama_cabang AS nama_cabang_penerima
                                 FROM pengambilan_setoran_closing_kasir ps
                                 LEFT JOIN tbcabang c ON c.cabang_ref_kode = ps.kode_cabang_penerima
                                 WHERE ps.id_setoran_bank = ?
                                 ORDER BY ps.created_at DESC";
        $stmt_pengambilan_bank = $pdo->prepare($sql_pengambilan_bank);
        $stmt_pengambilan_bank->execute([$bank_detail_id]);
        $bank_pengambilan_rows = $stmt_pengambilan_bank->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Handle specific cabang closing detail
$cabang_closing_detail = [];
if (isset($_GET['cabang_closing']) && isset($_GET['bank_detail_id'])) {
    $cabang_name = $_GET['cabang_closing'];
    $bank_detail_id = $_GET['bank_detail_id'];
    
    $sql_cabang_detail = "SELECT 
                             sk.*,
                             kt.kode_transaksi,
                             kt.tanggal_transaksi,
                             kt.setoran_real,
                             kt.deposit_status,
                             CASE 
                                 WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'DARI CLOSING'
                                 WHEN EXISTS (
                                     SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                                     WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                                 ) THEN 'DARI CLOSING'
                                 ELSE 'TRANSAKSI BIASA'
                             END as jenis_transaksi
                         FROM setoran_ke_bank_detail_closing_kasir sbd
                         JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                         JOIN tbcabang c ON c.cabang_ref_kode = sk.kode_cabang
                         LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                         WHERE sbd.setoran_ke_bank_id = ? AND c.nama_cabang = ?
                         ORDER BY 
                             CASE 
                                 WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 0
                                 WHEN EXISTS (
                                     SELECT 1 FROM pemasukan_kasir_closing_kasir pk2 
                                     WHERE pk2.nomor_transaksi_closing = kt.kode_transaksi
                                 ) THEN 0
                                 ELSE 1
                             END,
                             sk.tanggal_setoran, kt.tanggal_transaksi";
    $stmt_cabang_detail = $pdo->prepare($sql_cabang_detail);
    $stmt_cabang_detail->execute([$bank_detail_id, $cabang_name]);
    $cabang_closing_detail = $stmt_cabang_detail->fetchAll(PDO::FETCH_ASSOC);
}
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
    <title>Riwayat Setoran Bank</title>
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

        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn-info {
            background-color: var(--info-color);
            color: white;
        }

        .btn-info:hover {
            background-color: #117a8b;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
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

        .table-container {
            position: relative;
            border: 2px solid #007bff;
            border-radius: 12px;
            background: #fff;
            overflow: visible;
        }

        .table-wrapper {
            overflow-x: scroll !important;
            overflow-y: visible !important;
            max-width: 100%;
            width: 100%;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            scrollbar-width: thick;
            scrollbar-color: #dc3545 #f8f9fa;
        }

        .table-wrapper::-webkit-scrollbar {
            height: 16px !important;
            background: #f1f1f1 !important;
            border: 2px solid #ccc !important;
            border-radius: 8px !important;
            -webkit-appearance: none !important;
            display: block !important;
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: #e0e0e0 !important;
            border-radius: 8px !important;
            border: 1px solid #bbb !important;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #dc3545 !important;
            border-radius: 8px !important;
            border: 2px solid #fff !important;
            min-width: 40px !important;
            -webkit-appearance: none !important;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #c82333 !important;
            cursor: grab !important;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:active {
            background: #a71e2a !important;
            cursor: grabbing !important;
        }

        .table-enhanced {
            background: white;
            border-radius: 12px;
            overflow: visible;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            min-width: 1560px;
            width: 1560px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            min-width: 1560px !important;
            width: 1560px !important;
            table-layout: auto;
            white-space: nowrap;
        }

        .table th {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: white;
            font-weight: 600;
            padding: 12px 8px;
            text-align: left;
            font-size: 13px;
            border: none;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
            vertical-align: top !important;
        }

        .table td {
            padding: 12px 8px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
            vertical-align: middle;
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
        }

        .table tbody tr:hover {
            background: rgba(0,123,255,0.05);
        }

        .table code {
            word-break: break-all !important;
            white-space: normal !important;
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

        .bg-closing {
            background: linear-gradient(135deg, #fd7e14, #e55a00);
            color: white;
        }

        .total-summary-card {
            margin-top: 20px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,123,255,0.3);
        }

        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.show {
            display: flex;
        }

        .modal-dialog {
            background: white;
            border-radius: 16px;
            width: 95%;
            height: 95%;
            max-width: 95%;
            max-height: 95%;
            margin: 2.5%;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }
        
        .modal-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--text-dark);
            margin: 0;
        }

        .btn-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0;
            margin-left: 10px;
            text-decoration: none;
        }


        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .closing-summary {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .closing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .closing-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .closing-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .closing-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .closing-value.amount {
            color: var(--success-color);
            font-size: 18px;
        }

        .related-pengambilan-panel {
            margin-bottom: 20px;
            padding: 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fff7ed, #f8fafc);
            border: 1px solid #fed7aa;
        }

        .related-pengambilan-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        .related-pengambilan-head h4 {
            margin: 0;
            color: #7c2d12;
            font-size: 17px;
        }

        .related-pengambilan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 14px;
        }

        .related-pengambilan-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 14px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.04);
        }

        .related-pengambilan-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 10px;
        }

        .related-pengambilan-top code {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 8px;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 700;
        }

        .mini-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .mini-badge.hutang {
            background: #fee2e2;
            color: #b91c1c;
        }

        .mini-badge.internal {
            background: #dcfce7;
            color: #166534;
        }

        .related-pengambilan-meta {
            display: grid;
            gap: 10px;
        }

        .related-meta-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .related-meta-item {
            padding: 10px 12px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .related-meta-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: .05em;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .related-meta-value {
            font-size: 14px;
            color: #0f172a;
            font-weight: 700;
            word-break: break-word;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            font-style: italic;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }

        .col-md-8 {
            flex: 0 0 66.666667%;
            max-width: 66.666667%;
        }

        .col-md-4 {
            flex: 0 0 33.333333%;
            max-width: 33.333333%;
        }

        .text-right {
            text-align: right;
        }

        /* Modal responsive styles */
        .modal-table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
        }
        
        .modal-table th,
        .modal-table td {
            word-wrap: break-word !important;
            overflow-wrap: break-word !important;
            white-space: normal !important;
            vertical-align: top !important;
        }
        
        .modal-table-container {
            width: 100% !important;
            overflow: hidden !important;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
        }
        
        .modal-table-wrapper {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            max-height: 60vh;
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

            .col-md-8,
            .col-md-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .text-right {
                text-align: left;
            }
            
            .modal-dialog {
                width: 98% !important;
                height: 98% !important;
                margin: 1% !important;
            }
            
            .modal-table th,
            .modal-table td {
                padding: 4px 3px !important;
                font-size: 12px !important;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
            <div>
                <strong><?php echo htmlspecialchars($username); ?></strong>
                <p style="color: var(--text-muted); font-size: 12px;">Super Admin</p>
            </div>
        </div>

        <div class="welcome-card">
            <h1><i class="fas fa-file-alt"></i> Riwayat Setoran ke Bank</h1>
            <p style="color: var(--text-muted); margin-bottom: 0;">Riwayat semua setoran yang telah disetor ke bank. Sistem menampilkan informasi dari transaksi closing jika ada. Klik "Detail" untuk melihat detail setoran closing per cabang.</p>
            <div class="info-tags">
                <div class="info-tag">User: <?php echo htmlspecialchars($username); ?></div>
                <div class="info-tag">Role: Super Admin</div>
                <div class="info-tag">Tanggal: <?php echo date('d M Y'); ?></div>
            </div>
        </div>

        <!-- Filter khusus untuk bank history -->
        <div class="filter-card">
            <form action="" method="POST" class="form-inline">
                <div class="form-group">
                    <label class="form-label">Tanggal ke Penyetor (Awal):</label>
                    <input type="date" name="tanggal_setor_awal" class="form-control" value="<?php echo htmlspecialchars($tanggal_setor_awal); ?>" title="Filter berdasarkan tanggal ke penyetor">
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal ke Penyetor (Akhir):</label>
                    <input type="date" name="tanggal_setor_akhir" class="form-control" value="<?php echo htmlspecialchars($tanggal_setor_akhir); ?>" title="Filter berdasarkan tanggal ke penyetor">
                </div>
                <div class="form-group">
                    <label class="form-label">Rekening:</label>
                    <select name="rekening_filter" class="form-control">
                        <option value="all">Semua Rekening</option>
                        <?php foreach ($rekening_list as $rekening): ?>
                            <?php
                            // Parse cabang info to get all cabang names and IDs
                            $cabang_items = explode(';;', $rekening['cabang_info']);
                            $rekening_ids = explode(',', $rekening['rekening_ids']);
                            $cabang_names = array();
                            foreach ($cabang_items as $item) {
                                $parts = explode('|', $item);
                                if (count($parts) == 2) {
                                    $cabang_names[] = $parts[0];
                                }
                            }
                            $cabang_display = '(' . implode('-', $cabang_names) . ')';
                            $jenis_badge = $rekening['jenis_rekening'] == 'Mitra' ? ' (MITRA)' : ' (MILIK SENDIRI)';
                            
                            // Use all rekening IDs as comma separated values for the option
                            $all_rekening_ids = $rekening['rekening_ids'];
                            
                            // Format: Nama Bank (No Rek) - (Cabang1-Cabang2) (Jenis)
                            $display_text = $rekening['nama_bank'] . ' (' . $rekening['no_rekening'] . ') - ' . $cabang_display . $jenis_badge;
                            ?>
                            <option value="<?php echo htmlspecialchars($all_rekening_ids); ?>" <?php echo ($rekening_filter !== 'all' && !empty($rekening_filter) && ($rekening_filter == $all_rekening_ids || in_array($rekening_filter, explode(',', $all_rekening_ids)))) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($display_text); ?>
                            </option>
                        <?php endforeach; ?>
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
                <h3><i class="fas fa-file-alt"></i> Riwayat Setoran ke Bank</h3>
                <div class="export-buttons">
                    <!-- PERBAIKAN: Kirim semua parameter filter ke export Excel -->
                    <a href="export_excel_setoran.php?type=bank_history&rekening_filter=<?php echo urlencode($rekening_filter); ?>&tanggal_setor_awal=<?php echo urlencode($tanggal_setor_awal); ?>&tanggal_setor_akhir=<?php echo urlencode($tanggal_setor_akhir); ?>&cabang=<?php echo urlencode($cabang); ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="export_csv.php?type=bank_history&rekening_filter=<?php echo urlencode($rekening_filter); ?>&tanggal_setor_awal=<?php echo urlencode($tanggal_setor_awal); ?>&tanggal_setor_akhir=<?php echo urlencode($tanggal_setor_akhir); ?>&cabang=<?php echo urlencode($cabang); ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-file-csv"></i> Export CSV
                    </a>
                    <a href="export_pdf_setoran.php?type=bank_history&rekening_filter=<?php echo urlencode($rekening_filter); ?>&tanggal_setor_awal=<?php echo urlencode($tanggal_setor_awal); ?>&tanggal_setor_akhir=<?php echo urlencode($tanggal_setor_akhir); ?>&cabang=<?php echo urlencode($cabang); ?>" class="btn btn-danger btn-sm" target="_blank">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="setoran_keuangan_closing_kasir.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Kembali ke Manajemen Setoran
                    </a>
                </div>
            </div>
            <div class="content-body">
                <div class="workflow-info">
                    <h6><i class="fas fa-info-circle"></i> Informasi</h6>
                    <p>Filter memakai tanggal ke penyetor. Tanggal setor ke bank diisi manual oleh keuangan setelah setoran benar-benar masuk ke rekening tujuan. Klik "Detail" untuk melihat detail setoran closing per cabang.</p>
                </div>
                <?php if ($bank_message !== ''): ?>
                    <div class="workflow-info" style="margin-top: 16px; border-left-color: <?php echo $bank_status === 'success' ? '#16a34a' : '#dc2626'; ?>; background: <?php echo $bank_status === 'success' ? 'rgba(22, 163, 74, 0.08)' : 'rgba(220, 38, 38, 0.08)'; ?>;">
                        <h6><i class="fas <?php echo $bank_status === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i> <?php echo $bank_status === 'success' ? 'Berhasil' : 'Perlu Dicek'; ?></h6>
                        <p><?php echo htmlspecialchars($bank_message); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="table-container">
                    <div class="table-wrapper">
                        <div class="table-enhanced">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 140px;">Tanggal ke Penyetor</th>
                                        <th style="width: 200px;">Kode Setoran Bank</th>
                                        <th style="width: 280px;">Cabang Terkait</th>
                                        <th style="width: 320px;">Rekening Tujuan</th>
                                        <th style="width: 300px;">Tanggal Setor ke Bank</th>
                                        <th style="width: 150px;">Total Setoran</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($setoran_list): ?>
                                        <?php foreach ($setoran_list as $row): ?>
                                            <tr>
                                                <!-- PERBAIKAN: Tampilkan tanggal setor bank, sembunyikan waktu jika 00:00 -->
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;"><?php 
                                                    $tanggal_setor = strtotime($row['tanggal_setoran']);
                                                    $waktu = date('H:i', $tanggal_setor);
                                                    
                                                    // Jika waktu 00:00, tampilkan hanya tanggal
                                                    if ($waktu === '00:00') {
                                                        echo date('d/m/Y', $tanggal_setor);
                                                    } else {
                                                        echo date('d/m/Y H:i', $tanggal_setor);
                                                    }
                                                ?></td>

                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;"><code style="word-break: break-all;"><?php echo htmlspecialchars($row['kode_setoran']); ?></code></td>
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;"><?php echo htmlspecialchars($row['cabang_names']); ?></td>
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;"><?php echo htmlspecialchars($row['rekening_tujuan']); ?></td>
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                                                    <form action="" method="POST" style="display:flex; gap:8px; align-items:center; flex-wrap:nowrap; margin:0;">
                                                        <input type="hidden" name="simpan_tanggal_setor_bank" value="1">
                                                        <input type="hidden" name="setoran_bank_id" value="<?php echo (int)$row['id']; ?>">
                                                        <input type="hidden" name="tanggal_setor_awal" value="<?php echo htmlspecialchars($tanggal_setor_awal); ?>">
                                                        <input type="hidden" name="tanggal_setor_akhir" value="<?php echo htmlspecialchars($tanggal_setor_akhir); ?>">
                                                        <input type="hidden" name="rekening_filter" value="<?php echo htmlspecialchars($rekening_filter); ?>">
                                                        <input type="hidden" name="cabang" value="<?php echo htmlspecialchars($cabang); ?>">
                                                        <input type="date" name="tanggal_setor_riil" class="form-control" value="<?php echo htmlspecialchars((string)($row['tanggal_setor'] ?? '')); ?>" style="min-width: 160px; max-width: 170px;" required>
                                                        <button type="submit" class="btn btn-info btn-sm">
                                                            <i class="fas fa-<?php echo !empty($row['tanggal_setor']) ? 'pen' : 'plus'; ?>"></i> <?php echo !empty($row['tanggal_setor']) ? 'Simpan' : 'Input'; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td style="text-align: right; font-weight: 600; color: var(--success-color); word-wrap: break-word; overflow-wrap: break-word; white-space: normal;">
                                                    <?php echo formatRupiah($row['total_setoran']); ?>
                                                </td>
                                                <td>
                                                    <a href="?bank_detail_id=<?php echo $row['id']; ?><?php echo $history_filter_query !== '' ? '&' . htmlspecialchars($history_filter_query, ENT_QUOTES) : ''; ?>" class="btn btn-info btn-sm">
                                                        <i class="fas fa-eye"></i> Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="no-data">
                                                <i class="fas fa-file-alt"></i><br>
                                                Tidak ada riwayat setoran ke bank ditemukan
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="workflow-info" style="margin-top: 16px;">
                        <h6><i class="fas fa-info-circle"></i> Keterangan</h6>
                        <p>Filter menggunakan tanggal ke penyetor untuk kebutuhan laporan keuangan. Tanggal setor ke bank diinput manual setelah setoran benar-benar dilakukan ke rekening bank agar pencocokan mutasi tetap akurat.</p>
                    </div>
                    
                    <!-- Total Keseluruhan Setoran -->
                    <?php if ($setoran_list && !empty($setoran_list)): ?>
                        <?php 
                        $total_keseluruhan = 0;
                        $total_paket = 0;
                        foreach ($setoran_list as $row) {
                            $total_keseluruhan += $row['total_setoran'];
                            $total_paket += $row['total_setoran_count'];
                        }
                        ?>
                        <div class="total-summary-card">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 style="margin: 0; font-weight: 600; display: flex; align-items: center;">
                                        <i class="fas fa-calculator" style="margin-right: 10px; font-size: 20px;"></i>
                                        Total Keseluruhan Setoran
                                        <?php if ($rekening_filter !== 'all' && !empty($rekening_filter)): ?>
                                            <span style="font-size: 14px; opacity: 0.8; margin-left: 10px;">(Filtered)</span>
                                        <?php endif; ?>
                                    </h4>
                                </div>
                                <div class="col-md-4 text-right">
                                    <div style="font-size: 24px; font-weight: 700; margin-bottom: 5px;">
                                        <?php echo formatRupiah($total_keseluruhan); ?>
                                    </div>
                                    <div style="font-size: 14px; opacity: 0.9;">
                                        <i class="fas fa-boxes" style="margin-right: 5px;"></i>
                                        <?php echo number_format($total_paket); ?> paket setoran
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Bank Detail Modal (Closing Summary) -->
    <?php if (isset($bank_detail_view) && !empty($bank_detail_view)): ?>
    <div class="modal show">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: white; border-radius: 16px;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-university"></i> Detail Setoran Bank - <?php echo htmlspecialchars($bank_detail_view['kode_setoran']); ?></h5>
                    <a href="<?php echo $history_filter_query !== '' ? '?' . htmlspecialchars($history_filter_query, ENT_QUOTES) : '?'; ?>" class="btn-close">&times;</a>
                </div>
                <div class="modal-body">
                    <div class="closing-summary">
                        <h4><i class="fas fa-info-circle"></i> Informasi Setoran Bank</h4>
                        <div class="closing-grid">
                            <div class="closing-item">
                                <div class="closing-label">Tanggal ke Penyetor</div>
                                <!-- PERBAIKAN: Tampilkan tanggal setor bank, sembunyikan waktu jika 00:00 -->
                                <div class="closing-value"><?php 
                                    $tanggal_setor = strtotime($bank_detail_view['tanggal_setoran']);
                                    $waktu = date('H:i', $tanggal_setor);
                                    
                                    // Jika waktu 00:00, tampilkan hanya tanggal
                                    if ($waktu === '00:00') {
                                        echo date('d/m/Y', $tanggal_setor);
                                    } else {
                                        echo date('d/m/Y H:i', $tanggal_setor);
                                    }
                                ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Tanggal Setor ke Bank</div>
                                <div class="closing-value"><?php echo !empty($bank_detail_view['tanggal_setor']) ? date('d/m/Y', strtotime($bank_detail_view['tanggal_setor'])) : '-'; ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Rekening Tujuan</div>
                                <div class="closing-value"><?php echo htmlspecialchars($bank_detail_view['rekening_tujuan']); ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Total Setoran</div>
                                <div class="closing-value amount"><?php echo formatRupiah($bank_detail_view['total_setoran']); ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Disetor Oleh</div>
                                <div class="closing-value"><?php echo htmlspecialchars($bank_detail_view['created_by_name']); ?></div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Jumlah Paket Setoran</div>
                                <div class="closing-value"><?php 
                                    $total_paket = 0;
                                    foreach ($all_closing_detail as $detail) {
                                        if (isset($detail['kode_setoran'])) $total_paket++;
                                    }
                                    echo $total_paket; ?> paket</div>
                            </div>
                            <div class="closing-item">
                                <div class="closing-label">Status Closing</div>
                                <div class="closing-value">
                                    <?php 
                                    $total_closing = 0;
                                    foreach ($all_closing_detail as $detail) {
                                        if ($detail['jenis_transaksi'] === 'DARI CLOSING') $total_closing++;
                                    }
                                    if ($total_closing > 0): ?>
                                        <span class="status-badge bg-closing"><?php echo $total_closing; ?> dari Closing</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-style: italic;">Tidak ada dari closing</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($bank_pengambilan_rows)): ?>
                    <div class="related-pengambilan-panel">
                        <div class="related-pengambilan-head">
                            <h4><i class="fas fa-hand-holding-usd"></i> Terkait Pengambilan Cabang/Gudang</h4>
                            <span class="mini-badge hutang"><?php echo count($bank_pengambilan_rows); ?> record</span>
                        </div>
                        <div class="related-pengambilan-grid">
                            <?php foreach ($bank_pengambilan_rows as $pengambilanBank): ?>
                            <?php $badgeClass = ($pengambilanBank['klasifikasi'] ?? 'internal') === 'hutang' ? 'hutang' : 'internal'; ?>
                            <div class="related-pengambilan-card">
                                <div class="related-pengambilan-top">
                                    <div>
                                        <code><?php echo htmlspecialchars($pengambilanBank['kode_pengambilan']); ?></code>
                                        <?php if (!empty($pengambilanBank['parent_kode_pengambilan'])): ?>
                                        <div style="margin-top:6px; font-size:12px; color:#64748b;">Child dari <?php echo htmlspecialchars($pengambilanBank['parent_kode_pengambilan']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="mini-badge <?php echo $badgeClass; ?>">
                                        <?php echo htmlspecialchars(strtoupper($pengambilanBank['klasifikasi'] ?? 'internal')); ?>
                                    </span>
                                </div>
                                <div class="related-pengambilan-meta">
                                    <div class="related-meta-row">
                                        <div class="related-meta-item">
                                            <div class="related-meta-label">Cabang Penerima</div>
                                            <div class="related-meta-value"><?php echo htmlspecialchars($pengambilanBank['nama_cabang_penerima'] ?? '-'); ?></div>
                                        </div>
                                        <div class="related-meta-item">
                                            <div class="related-meta-label">Tanggal Setor ke Penyetor</div>
                                            <div class="related-meta-value">
                                                <?php echo !empty($pengambilanBank['tanggal_perencanaan_setor']) ? date('d/m/Y', strtotime($pengambilanBank['tanggal_perencanaan_setor'])) : '-'; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="related-meta-row">
                                        <div class="related-meta-item">
                                            <div class="related-meta-label">Nominal Diambil</div>
                                            <div class="related-meta-value"><?php echo formatRupiah($pengambilanBank['nominal_diambil'] ?? 0); ?></div>
                                        </div>
                                        <div class="related-meta-item">
                                            <div class="related-meta-label">Sisa ke Bank</div>
                                            <div class="related-meta-value"><?php echo formatRupiah($pengambilanBank['nominal_sisa'] ?? 0); ?></div>
                                        </div>
                                    </div>
                                    <div class="related-meta-item">
                                        <div class="related-meta-label">No Rek Pemberi Pinjaman</div>
                                        <div class="related-meta-value"><?php echo htmlspecialchars($pengambilanBank['no_rekening_peminjam'] ?? '-'); ?></div>
                                    </div>
                                    <div class="related-meta-item">
                                        <div class="related-meta-label">No Rek Penerima Pinjaman</div>
                                        <div class="related-meta-value"><?php echo htmlspecialchars($pengambilanBank['no_rekening_penerima'] ?? '-'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <h6 style="margin-bottom: 15px; color: var(--text-dark);"><i class="fas fa-list"></i> Detail Seluruh Transaksi Setoran (Semua Cabang)</h6>
                    <div class="modal-table-container">
                        <div class="modal-table-wrapper">
                            <table class="modal-table">
                                <thead>
                                    <tr>
                                        <th style="width: 8%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Cabang</th>
                                        <th style="width: 15%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Kode Setoran</th>
                                        <th style="width: 8%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Tgl Setoran</th>
                                        <th style="width: 18%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Kode Transaksi</th>
                                        <th style="width: 8%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Tgl Closing</th>
                                        <th style="width: 10%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Setoran Awal</th>
                                        <th style="width: 10%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Setoran Kekas Masuk</th>
                                        <th style="width: 10%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Setoran Diterima</th>
                                        <th style="width: 6%; padding: 8px 4px; background: linear-gradient(135deg, var(--primary-color), #0056b3); color: white; font-weight: 600; word-wrap: break-word; white-space: normal; vertical-align: top; font-size: 14px;">Jenis</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($all_closing_detail): ?>
                                        <?php foreach ($all_closing_detail as $detail): ?>
                                            <tr style="border-bottom: 1px solid var(--border-color);">
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 14px; border-right: 1px solid #f0f0f0;"><?php echo htmlspecialchars($detail['nama_cabang']); ?></td>
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 13px; border-right: 1px solid #f0f0f0;"><code style="font-size: 12px; word-break: break-all; background: #f8f9fa; padding: 2px 4px; border-radius: 3px;"><?php echo htmlspecialchars($detail['kode_setoran']); ?></code></td>
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 13px; border-right: 1px solid #f0f0f0;"><?php echo date('d/m/Y', strtotime($detail['tanggal_setoran'])); ?></td>
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 12px; border-right: 1px solid #f0f0f0;"><code style="font-size: 11px; word-break: break-all; background: #f8f9fa; padding: 2px 4px; border-radius: 3px;"><?php echo htmlspecialchars($detail['kode_transaksi'] ?? 'N/A'); ?></code></td>
                                                <td style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 13px; border-right: 1px solid #f0f0f0;"><?php 
                                                    // Prioritas: tanggal_closing > tanggal_transaksi > '-'
                                                    if (!empty($detail['tanggal_closing']) && $detail['tanggal_closing'] !== '0000-00-00') {
                                                        echo date('d/m/Y', strtotime($detail['tanggal_closing']));
                                                    } elseif (!empty($detail['tanggal_transaksi']) && $detail['tanggal_transaksi'] !== '0000-00-00') {
                                                        echo date('d/m/Y', strtotime($detail['tanggal_transaksi']));
                                                    } else {
                                                        echo '-';
                                                    }
                                                ?></td>
                                                <td style="text-align: right; font-weight: 600; color: var(--primary-color); word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 13px; border-right: 1px solid #f0f0f0;"><?php echo formatRupiah($detail['setoran_awal'] ?? 0); ?></td>
                                                <td style="text-align: right; font-weight: 600; color: var(--info-color); word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 13px; border-right: 1px solid #f0f0f0;"><?php echo formatRupiah($detail['setoran_kekas_masuk'] ?? 0); ?></td>
                                                <td style="text-align: right; font-weight: 600; color: var(--success-color); word-wrap: break-word; overflow-wrap: break-word; white-space: normal; padding: 6px 4px; vertical-align: top; font-size: 13px; border-right: 1px solid #f0f0f0;"><?php echo formatRupiah($detail['setoran_diterima'] ?? 0); ?></td>
                                                <td style="text-align: center; padding: 6px 4px; vertical-align: top;">
                                                    <?php if ($detail['jenis_transaksi'] === 'DARI CLOSING'): ?>
                                                        <span style="font-size: 11px; padding: 4px 6px; background: linear-gradient(135deg, #fd7e14, #e55a00); color: white; border-radius: 12px; font-weight: 600; text-transform: uppercase; display: inline-block;">CLO</span>
                                                    <?php else: ?>
                                                        <span style="font-size: 11px; padding: 4px 6px; background: #6c757d; color: white; border-radius: 12px; font-weight: 600; text-transform: uppercase; display: inline-block;">BIA</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" style="text-align: center; padding: 40px; color: var(--text-muted); font-style: italic;">Tidak ada detail transaksi ditemukan</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="export_excel_setoran.php?type=bank_detail&bank_id=<?php echo $bank_detail_view['id']; ?>" class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="export_pdf_setoran.php?type=bank_detail&bank_id=<?php echo $bank_detail_view['id']; ?>" class="btn btn-danger btn-sm" target="_blank">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="<?php echo $history_filter_query !== '' ? '?' . htmlspecialchars($history_filter_query, ENT_QUOTES) : '?'; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Tutup
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Currency formatter utility
        function formatCurrency(amount) {
            return 'Rp ' + amount.toLocaleString('id-ID');
        }

        // Export functionality
        function exportToExcel(type, additionalParams = '') {
            let url = `export_excel_setoran.php?type=${type}`;
            if (additionalParams) {
                url += '&' + additionalParams;
            }
            const urlParams = new URLSearchParams(window.location.search);
            const relevantParams = ['tanggal_awal', 'tanggal_akhir', 'cabang', 'rekening_filter'];
            relevantParams.forEach(param => {
                if (urlParams.has(param)) {
                    url += `&${param}=${urlParams.get(param)}`;
                }
            });
            window.open(url, '_blank');
        }

        // Close modal on outside click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    window.location.href = '?';
                }
            });
        });

        // Table search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tables = document.querySelectorAll('.table');
            tables.forEach((table, index) => {
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
            });
        });

        // Ensure modal tables are responsive
        document.addEventListener('DOMContentLoaded', function() {
            const modalTables = document.querySelectorAll('.modal-table');
            modalTables.forEach(table => {
                // Ensure no horizontal scrolling
                table.style.width = '100%';
                table.style.tableLayout = 'fixed';
            });
        });
    </script>
</body>
</html>
