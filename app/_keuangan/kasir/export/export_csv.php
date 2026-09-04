<?php
// Sumber: web_kasir/export_csv.php — export CSV setoran (receipt/terima/
// setor_bank/validasi/validasi_selisih/bank_history). Gerbang asli
// role==='super_admin' -> kasir_approve (Task 10: ADM+KEU).
require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$type = $_GET['type'] ?? '';
$tab = $_GET['tab'] ?? '';
$rekening_filter = $_GET['rekening_filter'] ?? 'all';
$tanggal_awal = $_GET['tanggal_setor_awal'] ?? $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_setor_akhir'] ?? $_GET['tanggal_akhir'] ?? '';
$cabang = $_GET['cabang'] ?? 'all';

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Set CSV headers
$filename = '';
$data = [];
$headers = [];

if ($type == 'receipt') {
    // Receipt export
    $receipt_data = json_decode(base64_decode($_GET['data']), true);
    
    $filename = 'Bukti_Penerimaan_Setoran_' . date('Y-m-d_H-i-s') . '.csv';
    $headers = ['Kode Setoran', 'Cabang', 'Tanggal Setoran', 'Pengantar', 'Status'];
    
    foreach ($receipt_data as $item) {
        $data[] = [
            $item['kode_setoran'],
            $item['nama_cabang'],
            date('d/m/Y', strtotime($item['tanggal_setoran'])),
            $item['nama_pengantar'],
            'Diterima'
        ];
    }

} elseif ($type == 'terima') {
    // Terima setoran export
    $sql = "SELECT sk.*, COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
            FROM setoran_keuangan_closing_kasir sk
            LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
            WHERE sk.status = 'Sedang Dibawa Kurir'
            ORDER BY sk.tanggal_setoran DESC";
    
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'Setoran_Menunggu_Penerimaan_' . date('Y-m-d_H-i-s') . '.csv';
    $headers = ['Tanggal', 'Kode Setoran', 'Cabang', 'Kasir', 'Pengantar', 'Status', 'Dibuat'];
    
    foreach ($result as $row) {
        $data[] = [
            date('d/m/Y', strtotime($row['tanggal_setoran'])),
            $row['kode_setoran'],
            ucfirst($row['nama_cabang']),
            $row['nama_karyawan'],
            $row['nama_pengantar'],
            'Sedang Dibawa Kurir',
            date('d/m/Y H:i', strtotime($row['created_at']))
        ];
    }

} elseif ($type == 'setor_bank') {
    // Setor bank export - Menggunakan query yang sama dengan halaman utama
    $sql = "SELECT
                kt.id,
                kt.kode_transaksi,
                kt.tanggal_transaksi,
                kt.tanggal_closing,
                kt.jam_closing,
                COALESCE(kt.jumlah_diterima_fisik, kt.setoran_real) as setoran_real,
                kt.omset,
                kt.data_setoran,
                kt.deposit_status,
                kt.kode_setoran,
                kt.nama_cabang,
                sk.tanggal_setoran,
                sk.nama_pengantar,
                sk.status as setoran_status,
                COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
            FROM kasir_transactions_closing_kasir kt
            LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
            LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
            WHERE sk.status = 'Validasi Keuangan OK'
            AND kt.status = 'end proses'
            AND kt.deposit_status IN ('Validasi Keuangan OK')";

    $params = [];

    // Add rekening filter untuk setor_bank
    if ($rekening_filter !== 'all' && !empty($rekening_filter)) {
        // Handle multiple rekening IDs (comma separated)
        $rekening_ids = explode(',', $rekening_filter);
        $placeholders = array_fill(0, count($rekening_ids), '?');
        $sql .= " AND sk.kode_cabang IN (
            SELECT kode_cabang FROM master_rekening_cabang_closing_kasir
            WHERE id IN (" . implode(',', $placeholders) . ") AND status = 'active'
        )";
        $params = array_merge($params, $rekening_ids);
    }

    // Apply filters seperti di halaman utama
    if ($tanggal_awal && $tanggal_akhir) {
        $sql .= " AND sk.tanggal_setoran BETWEEN ? AND ?";
        $params[] = $tanggal_awal;
        $params[] = $tanggal_akhir;
    }

    if ($cabang !== 'all') {
        $sql .= " AND sk.nama_cabang = ?";
        $params[] = $cabang;
    }

    $sql .= " ORDER BY kt.tanggal_closing DESC, kt.jam_closing DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'Setoran_Siap_Setor_Bank_' . date('Y-m-d_H-i-s') . '.csv';
    $headers = ['Tanggal Closing', 'Kode Transaksi', 'Kode Setoran', 'Cabang', 'Nominal Setor', 'Data Setoran', 'Status', 'Kasir'];

    foreach ($result as $row) {
        $data[] = [
            date('d/m/Y', strtotime($row['tanggal_closing'])),
            $row['kode_transaksi'],
            $row['kode_setoran'],
            ucfirst($row['nama_cabang']),
            formatRupiah($row['setoran_real']),
            formatRupiah($row['data_setoran']),
            $row['deposit_status'],
            $row['nama_karyawan']
        ];
    }

} elseif ($type == 'validasi') {
    // Validasi fisik export
    $sql = "SELECT kt.*, sk.nama_cabang, sk.tanggal_setoran, sk.nama_pengantar, 
                   COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
            FROM kasir_transactions_closing_kasir kt
            LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
            LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
            WHERE kt.deposit_status = 'Diterima Staff Keuangan'
            ORDER BY sk.tanggal_setoran DESC, kt.tanggal_transaksi DESC";
    
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'Transaksi_Perlu_Validasi_' . date('Y-m-d_H-i-s') . '.csv';
    $headers = ['Tanggal', 'Kode Transaksi', 'Kode Setoran', 'Cabang', 'Kasir', 'Nominal Transaksi', 'Status', 'Pengantar'];
    
    foreach ($result as $row) {
        $data[] = [
            date('d/m/Y', strtotime($row['tanggal_transaksi'])),
            $row['kode_transaksi'],
            $row['kode_setoran'],
            ucfirst($row['nama_cabang']),
            $row['nama_karyawan'],
            formatRupiah($row['setoran_real']),
            'Diterima Staff Keuangan',
            $row['nama_pengantar']
        ];
    }

} elseif ($type == 'validasi_selisih') {
    // Validasi selisih export
    $sql = "SELECT kt.*, sk.nama_cabang, sk.tanggal_setoran, sk.nama_pengantar, 
                   COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
            FROM kasir_transactions_closing_kasir kt
            LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
            LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
            WHERE kt.deposit_status = 'Validasi Keuangan SELISIH'
            ORDER BY sk.tanggal_setoran DESC, kt.tanggal_transaksi DESC";
    
    $stmt = $pdo->query($sql);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if validation columns exist
    $validation_columns_exist = false;
    try {
        $stmt_check = $pdo->query("SHOW COLUMNS FROM kasir_transactions_closing_kasir LIKE 'jumlah_diterima_fisik'");
        $validation_columns_exist = $stmt_check->rowCount() > 0;
    } catch (Exception $e) {
        // Column doesn't exist
    }
    
    $filename = 'Transaksi_Selisih_Validasi_' . date('Y-m-d_H-i-s') . '.csv';
    $headers = ['Tanggal', 'Kode Transaksi', 'Kode Setoran', 'Cabang', 'Kasir', 'Nominal Sistem', 'Diterima Fisik', 'Selisih', 'Catatan'];
    
    foreach ($result as $row) {
        $diterima_fisik = ($validation_columns_exist && isset($row['jumlah_diterima_fisik'])) 
            ? $row['jumlah_diterima_fisik'] 
            : $row['setoran_real'];
        $selisih = ($validation_columns_exist && isset($row['selisih_fisik'])) 
            ? $row['selisih_fisik'] 
            : 0;
        
        $data[] = [
            date('d/m/Y', strtotime($row['tanggal_transaksi'])),
            $row['kode_transaksi'],
            $row['kode_setoran'],
            ucfirst($row['nama_cabang']),
            $row['nama_karyawan'],
            formatRupiah($row['setoran_real']),
            formatRupiah($diterima_fisik),
            formatRupiah($selisih),
            $row['catatan_validasi'] ?? ''
        ];
    }

} elseif ($type == 'bank_history') {
    // Bank history export
    $sql = "SELECT sb.*, 
                   GROUP_CONCAT(DISTINCT c.nama_cabang) as cabang_names,
                   COUNT(sbd.setoran_keuangan_id) as total_setoran_count,
                   u.nama_lengkap as created_by_name
            FROM setoran_ke_bank_closing_kasir sb
            JOIN setoran_ke_bank_detail_closing_kasir sbd ON sb.id = sbd.setoran_ke_bank_id
            JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
            JOIN tbcabang c ON sk.kode_cabang = c.cabang_ref_kode
            LEFT JOIN tbuser u ON sb.created_by = u.kode_karyawan
            WHERE 1=1";
    
    $params = [];
    
    if ($tanggal_awal && $tanggal_akhir) {
        $sql .= " AND sb.tanggal_setoran BETWEEN ? AND ?";
        $params[] = $tanggal_awal;
        $params[] = $tanggal_akhir;
    }
    
    if ($cabang !== 'all') {
        $sql .= " AND c.nama_cabang = ?";
        $params[] = $cabang;
    }

    if ($rekening_filter !== 'all' && !empty($rekening_filter)) {
        $rekening_ids = explode(',', $rekening_filter);
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
            $sql .= " AND (" . implode(' OR ', $rekening_conditions) . ")";
        }
    }
    
    $sql .= " GROUP BY sb.id ORDER BY sb.tanggal_setoran DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filename = 'Riwayat_Setoran_Bank_' . date('Y-m-d_H-i-s') . '.csv';
    $headers = ['Tanggal ke Penyetor', 'Kode Setoran Bank', 'Cabang Terkait', 'Rekening Tujuan', 'Tanggal Setor ke Bank', 'Total Setoran', 'Jumlah Paket', 'Disetor Oleh'];
    
    foreach ($result as $row) {
        $data[] = [
            date('d/m/Y', strtotime($row['tanggal_setoran'])),
            $row['kode_setoran'],
            $row['cabang_names'],
            $row['rekening_tujuan'],
            !empty($row['tanggal_setor']) ? date('d/m/Y', strtotime($row['tanggal_setor'])) : '-',
            formatRupiah($row['total_setoran']),
            $row['total_setoran_count'] . ' paket',
            $row['created_by_name']
        ];
    }

} else {
    die('Invalid export type');
}

// Generate CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV with BOM for Excel compatibility
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Write headers
fputcsv($output, $headers, ';');

// Write data
foreach ($data as $row) {
    fputcsv($output, $row, ';');
}

// Add footer
fputcsv($output, [], ';');
fputcsv($output, ['Generated on: ' . date('d/m/Y H:i:s')], ';');
fputcsv($output, ['By: ' . ($nama_karyawan_aktif ?? 'System')], ';');

fclose($output);
exit;
