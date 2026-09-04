<?php
// Sumber: web_kasir/export_excel_setoran.php — export Excel setoran (multi-tab:
// terima/setor_bank/validasi/bank_history/dkk). Gerbang asli role==='super_admin'
// -> kasir_approve (Task 10: ADM+KEU).
require_once __DIR__ . '/../../../../vendor/autoload.php'; // PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$type = $_GET['type'] ?? '';
$tab = $_GET['tab'] ?? '';
$rekening_filter = $_GET['rekening_filter'] ?? 'all';
// PERBAIKAN: Support parameter dari setoran_bank_rekap.php
$tanggal_awal = $_GET['tanggal_setor_awal'] ?? $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_setor_akhir'] ?? $_GET['tanggal_akhir'] ?? '';
$cabang = $_GET['cabang'] ?? 'all';

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Create new Spreadsheet object
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set document properties
$spreadsheet->getProperties()
    ->setCreator("FIT MOTOR - Keuangan Pusat")
    ->setLastModifiedBy("System")
    ->setTitle("Export Data Setoran")
    ->setSubject("Data Setoran Keuangan")
    ->setDescription("Export data setoran keuangan dari sistem FIT MOTOR")
    ->setKeywords("setoran keuangan export excel")
    ->setCategory("Financial Report");

$currentRow = 1;

// Header styling function
function setHeaderStyle($sheet, $range) {
    $sheet->getStyle($range)->applyFromArray([
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => '007bff']
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ]
    ]);
}

// Data styling function
function setDataStyle($sheet, $range) {
    $sheet->getStyle($range)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN
            ]
        ],
        'alignment' => [
            'vertical' => Alignment::VERTICAL_CENTER
        ]
    ]);
}

if ($type == 'receipt') {
    // Receipt export
    $data = json_decode(base64_decode($_GET['data']), true);
    
    $sheet->setTitle('Bukti Penerimaan');
    
    // Title
    $sheet->setCellValue('A1', 'BUKTI PENERIMAAN SETORAN');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A2', 'FIT MOTOR - KEUANGAN PUSAT');
    $sheet->mergeCells('A2:E2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $currentRow = 4;
    
    // Receipt info
    $sheet->setCellValue('A' . $currentRow, 'Tanggal Penerimaan:');
    $sheet->setCellValue('B' . $currentRow, date('d/m/Y H:i'));
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Diterima Oleh:');
    $sheet->setCellValue('B' . $currentRow, $nama_karyawan_aktif ?? 'Unknown');
    $currentRow++;
    
    $sheet->setCellValue('A' . $currentRow, 'Jumlah Setoran:');
    $sheet->setCellValue('B' . $currentRow, count($data) . ' paket');
    $currentRow += 2;
    
    // Headers
    $headers = ['Kode Setoran', 'Cabang', 'Tanggal Setoran', 'Pengantar', 'Status'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $col++;
    }
    setHeaderStyle($sheet, 'A' . $currentRow . ':E' . $currentRow);
    $currentRow++;
    
    // Data
    foreach ($data as $item) {
        $sheet->setCellValue('A' . $currentRow, $item['kode_setoran']);
        $sheet->setCellValue('B' . $currentRow, $item['nama_cabang']);
        $sheet->setCellValue('C' . $currentRow, date('d/m/Y', strtotime($item['tanggal_setoran'])));
        $sheet->setCellValue('D' . $currentRow, $item['nama_pengantar']);
        $sheet->setCellValue('E' . $currentRow, 'Diterima');
        $currentRow++;
    }
    
    setDataStyle($sheet, 'A' . ($currentRow - count($data)) . ':E' . ($currentRow - 1));
    
    $filename = 'Bukti_Penerimaan_Setoran_' . date('Y-m-d_H-i-s') . '.xlsx';

} elseif ($type == 'terima') {
    // Terima setoran export
    $sql = "SELECT sk.*, COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
            FROM setoran_keuangan_closing_kasir sk
            LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
            WHERE sk.status = 'Sedang Dibawa Kurir'
            ORDER BY sk.tanggal_setoran DESC";
    
    $stmt = $pdo->query($sql);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sheet->setTitle('Terima Setoran');
    
    // Title
    $sheet->setCellValue('A1', 'DATA SETORAN MENUNGGU PENERIMAAN');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $currentRow = 3;
    
    // Headers
    $headers = ['Tanggal', 'Kode Setoran', 'Cabang', 'Kasir', 'Pengantar', 'Status', 'Dibuat'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $col++;
    }
    setHeaderStyle($sheet, 'A' . $currentRow . ':G' . $currentRow);
    $currentRow++;
    
    // Data
    foreach ($data as $row) {
        // PERBAIKAN: Gunakan tanggal individual transaksi, bukan tanggal setoran yang sama
        $tanggal_display = isset($row['tanggal_transaksi']) && $row['tanggal_transaksi'] ? 
                          $row['tanggal_transaksi'] : $row['tanggal_setoran'];
        $sheet->setCellValue('A' . $currentRow, date('d/m/Y', strtotime($tanggal_display)));
        $sheet->setCellValue('B' . $currentRow, $row['kode_setoran']);
        $sheet->setCellValue('C' . $currentRow, ucfirst($row['nama_cabang']));
        $sheet->setCellValue('D' . $currentRow, $row['nama_karyawan']);
        $sheet->setCellValue('E' . $currentRow, $row['nama_pengantar']);
        $sheet->setCellValue('F' . $currentRow, 'Sedang Dibawa Kurir');
        $sheet->setCellValue('G' . $currentRow, date('d/m/Y H:i', strtotime($row['created_at'])));
        $currentRow++;
    }
    
    if (count($data) > 0) {
        setDataStyle($sheet, 'A4:G' . ($currentRow - 1));
    }
    
    $filename = 'Setoran_Menunggu_Penerimaan_' . date('Y-m-d_H-i-s') . '.xlsx';

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
                kt.kode_karyawan as kt_kode_karyawan,
                sk.kode_setoran as setoran_kode,
                sk.tanggal_setoran,
                sk.jumlah_setoran,
                sk.nama_pengantar,
                sk.status as setoran_status,
                sk.kode_karyawan,
                sk.kode_cabang,
                sk.nama_cabang as sk_nama_cabang,
                COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan
            FROM kasir_transactions_closing_kasir kt
            LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
            LEFT JOIN tbuser u ON sk.kode_karyawan = u.kode_karyawan
            WHERE sk.status = 'Validasi Keuangan OK'
            AND kt.status = 'end proses'
            AND kt.deposit_status IN ('Validasi Keuangan OK')";
    
    $params = [];
    
    // Add rekening filter for setor_bank - filter by cabang matching rekening with same no_rekening
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

    // Order by tanggal closing untuk setor_bank tab (per closing transaction)
    $sql .= " ORDER BY kt.tanggal_closing DESC, kt.jam_closing DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sheet->setTitle('Siap Setor Bank');
    
    // Title
    $sheet->setCellValue('A1', 'DATA SETORAN SIAP DISETOR KE BANK');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $currentRow = 3;
    
    // Headers
    $headers = ['Tanggal Closing', 'Kode Transaksi', 'Kode Setoran', 'Cabang', 'Nominal Setor', 'Data Setoran', 'Status', 'Kasir'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $col++;
    }
    setHeaderStyle($sheet, 'A' . $currentRow . ':H' . $currentRow);
    $currentRow++;

    // Data
    $total_setoran = 0;
    foreach ($data as $row) {
        $total_setoran += $row['setoran_real'];

        $sheet->setCellValue('A' . $currentRow, date('d/m/Y', strtotime($row['tanggal_closing'])));
        $sheet->setCellValue('B' . $currentRow, $row['kode_transaksi']);
        $sheet->setCellValue('C' . $currentRow, $row['kode_setoran']);
        $sheet->setCellValue('D' . $currentRow, ucfirst($row['nama_cabang']));
        $sheet->setCellValue('E' . $currentRow, $row['setoran_real']);
        $sheet->setCellValue('F' . $currentRow, $row['data_setoran']);
        $sheet->setCellValue('G' . $currentRow, $row['deposit_status']);
        $sheet->setCellValue('H' . $currentRow, $row['nama_karyawan']);

        // Format currency
        $sheet->getStyle('E' . $currentRow . ':F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');

        $currentRow++;
    }
    
    // Total row
    if (count($data) > 0) {
        $sheet->setCellValue('A' . $currentRow, 'TOTAL');
        $sheet->mergeCells('A' . $currentRow . ':D' . $currentRow);
        $sheet->setCellValue('E' . $currentRow, $total_setoran);
        $sheet->getStyle('E' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->getFont()->setBold(true);

        setDataStyle($sheet, 'A4:H' . $currentRow);
    }
    
    $filename = 'Setoran_Siap_Setor_Bank_' . date('Y-m-d_H-i-s') . '.xlsx';

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
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sheet->setTitle('Validasi Fisik');
    
    // Title
    $sheet->setCellValue('A1', 'DATA TRANSAKSI PERLU VALIDASI FISIK');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $currentRow = 3;
    
    // Headers
    $headers = ['Tanggal', 'Kode Transaksi', 'Kode Setoran', 'Cabang', 'Kasir', 'Nominal Transaksi', 'Status', 'Pengantar'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $col++;
    }
    setHeaderStyle($sheet, 'A' . $currentRow . ':H' . $currentRow);
    $currentRow++;
    
    // Data
    foreach ($data as $row) {
        $sheet->setCellValue('A' . $currentRow, date('d/m/Y', strtotime($row['tanggal_transaksi'])));
        $sheet->setCellValue('B' . $currentRow, $row['kode_transaksi']);
        $sheet->setCellValue('C' . $currentRow, $row['kode_setoran']);
        $sheet->setCellValue('D' . $currentRow, ucfirst($row['nama_cabang']));
        $sheet->setCellValue('E' . $currentRow, $row['nama_karyawan']);
        $sheet->setCellValue('F' . $currentRow, $row['setoran_real']);
        $sheet->setCellValue('G' . $currentRow, 'Diterima Staff Keuangan');
        $sheet->setCellValue('H' . $currentRow, $row['nama_pengantar']);
        
        // Format currency
        $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        
        $currentRow++;
    }
    
    if (count($data) > 0) {
        setDataStyle($sheet, 'A4:H' . ($currentRow - 1));
    }
    
    $filename = 'Transaksi_Perlu_Validasi_' . date('Y-m-d_H-i-s') . '.xlsx';

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
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if validation columns exist
    $validation_columns_exist = false;
    try {
        $stmt_check = $pdo->query("SHOW COLUMNS FROM kasir_transactions_closing_kasir LIKE 'jumlah_diterima_fisik'");
        $validation_columns_exist = $stmt_check->rowCount() > 0;
    } catch (Exception $e) {
        // Column doesn't exist
    }
    
    $sheet->setTitle('Validasi Selisih');
    
    // Title
    $sheet->setCellValue('A1', 'DATA TRANSAKSI DENGAN SELISIH VALIDASI');
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $currentRow = 3;
    
    // Headers
    $headers = ['Tanggal', 'Kode Transaksi', 'Kode Setoran', 'Cabang', 'Kasir', 'Nominal Sistem', 'Diterima Fisik', 'Selisih', 'Catatan'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $col++;
    }
    setHeaderStyle($sheet, 'A' . $currentRow . ':I' . $currentRow);
    $currentRow++;
    
    // Data
    foreach ($data as $row) {
        $diterima_fisik = ($validation_columns_exist && isset($row['jumlah_diterima_fisik'])) 
            ? $row['jumlah_diterima_fisik'] 
            : $row['setoran_real'];
        $selisih = ($validation_columns_exist && isset($row['selisih_fisik'])) 
            ? $row['selisih_fisik'] 
            : 0;
        
        $sheet->setCellValue('A' . $currentRow, date('d/m/Y', strtotime($row['tanggal_transaksi'])));
        $sheet->setCellValue('B' . $currentRow, $row['kode_transaksi']);
        $sheet->setCellValue('C' . $currentRow, $row['kode_setoran']);
        $sheet->setCellValue('D' . $currentRow, ucfirst($row['nama_cabang']));
        $sheet->setCellValue('E' . $currentRow, $row['nama_karyawan']);
        $sheet->setCellValue('F' . $currentRow, $row['setoran_real']);
        $sheet->setCellValue('G' . $currentRow, $diterima_fisik);
        $sheet->setCellValue('H' . $currentRow, $selisih);
        $sheet->setCellValue('I' . $currentRow, $row['catatan_validasi'] ?? '');
        
        // Format currency
        $sheet->getStyle('F' . $currentRow . ':H' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        
        // Color negative selisih red
        if ($selisih < 0) {
            $sheet->getStyle('H' . $currentRow)->getFont()->getColor()->setRGB('FF0000');
        } elseif ($selisih > 0) {
            $sheet->getStyle('H' . $currentRow)->getFont()->getColor()->setRGB('008000');
        }
        
        $currentRow++;
    }
    
    if (count($data) > 0) {
        setDataStyle($sheet, 'A4:I' . ($currentRow - 1));
    }
    
    $filename = 'Transaksi_Selisih_Validasi_' . date('Y-m-d_H-i-s') . '.xlsx';

} elseif ($type == 'bank_history') {
    // Bank history export - Include closing date aggregation and closing txn count
    $sql = "SELECT sb.*, 
                   GROUP_CONCAT(DISTINCT c.nama_cabang) as cabang_names,
                   COUNT(sbd.setoran_keuangan_id) as total_setoran_count,
                   u.nama_lengkap as created_by_name,
                   MIN(kt.tanggal_closing) as tanggal_closing_transaksi,
                   SUM(CASE WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%'
                            OR EXISTS (
                                SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                                WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                            ) THEN 1 ELSE 0 END) as total_closing_transactions
            FROM setoran_ke_bank_closing_kasir sb
            JOIN setoran_ke_bank_detail_closing_kasir sbd ON sb.id = sbd.setoran_ke_bank_id
            JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
            JOIN tbcabang c ON sk.kode_cabang = c.cabang_ref_kode
            LEFT JOIN tbuser u ON sb.created_by = u.kode_karyawan
            LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
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
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sheet->setTitle('Riwayat Setoran Bank');
    
    // Title
    $sheet->setCellValue('A1', 'RIWAYAT SETORAN KE BANK');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $currentRow = 3;
    
    $headers = ['Tanggal ke Penyetor', 'Kode Setoran Bank', 'Cabang Terkait', 'Rekening Tujuan', 'Tanggal Setor ke Bank', 'Total Setoran', 'Jumlah Paket', 'Disetor Oleh'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $col++;
    }
    setHeaderStyle($sheet, 'A' . $currentRow . ':H' . $currentRow);
    $currentRow++;
    
    // Data
    $grand_total = 0;
    foreach ($data as $row) {
        $grand_total += $row['total_setoran'];
        
        // PERBAIKAN: Gunakan tanggal setoran bank, sembunyikan waktu jika 00:00
        $tanggal_display = '';
        if (isset($row['tanggal_setoran']) && $row['tanggal_setoran'] && $row['tanggal_setoran'] != '0000-00-00') {
            $tanggal_setor = strtotime($row['tanggal_setoran']);
            $waktu = date('H:i', $tanggal_setor);
            
            // Jika waktu 00:00, tampilkan hanya tanggal
            if ($waktu === '00:00') {
                $tanggal_display = date('d/m/Y', $tanggal_setor);
            } else {
                $tanggal_display = date('d/m/Y H:i', $tanggal_setor);
            }
        } else {
            $tanggal_display = 'N/A';
        }
        
        $sheet->setCellValue('A' . $currentRow, $tanggal_display);
        $sheet->setCellValue('B' . $currentRow, $row['kode_setoran']);
        $sheet->setCellValue('C' . $currentRow, $row['cabang_names']);
        $sheet->setCellValue('D' . $currentRow, $row['rekening_tujuan']);
        $sheet->setCellValue('E' . $currentRow, !empty($row['tanggal_setor']) ? date('d/m/Y', strtotime($row['tanggal_setor'])) : '-');
        $sheet->setCellValue('F' . $currentRow, $row['total_setoran']);
        $sheet->setCellValue('G' . $currentRow, $row['total_setoran_count'] . ' paket');
        $sheet->setCellValue('H' . $currentRow, $row['created_by_name']);
        
        // Format currency
        $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        
        // Enable text wrapping for long content
        $sheet->getStyle('C' . $currentRow . ':H' . $currentRow)->getAlignment()->setWrapText(true);
        
        $currentRow++;
    }
    
    $sheet->getColumnDimension('A')->setWidth(18);
    $sheet->getColumnDimension('B')->setWidth(25); // Kode Setoran Bank
    $sheet->getColumnDimension('C')->setWidth(30); // Cabang Terkait
    $sheet->getColumnDimension('D')->setWidth(35); // Rekening Tujuan
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(18);
    $sheet->getColumnDimension('G')->setWidth(15);
    $sheet->getColumnDimension('H')->setWidth(22);
    
    // Grand total
    if (count($data) > 0) {
        $sheet->setCellValue('A' . $currentRow, 'GRAND TOTAL');
        $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
        $sheet->setCellValue('F' . $currentRow, $grand_total);
        $sheet->getStyle('F' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $currentRow . ':H' . $currentRow)->getFont()->setBold(true);
        
        setDataStyle($sheet, 'A4:H' . $currentRow);
    }
    
    $filename = 'Riwayat_Setoran_Bank_' . date('Y-m-d_H-i-s') . '.xlsx';

} elseif ($type == 'bank_detail') {
    // Bank detail export dengan filter tanggal yang disesuaikan dengan halaman setoran_keuangan_closing_kasir.php?tab=bank_history&bank_detail_id
    $bank_id = $_GET['bank_id'] ?? '';
    
    if (!$bank_id) {
        die('Bank ID required');
    }
    
    // Get bank detail info
    $sql_bank_detail = "SELECT sb.*, u.nama_lengkap as created_by_name 
                       FROM setoran_ke_bank_closing_kasir sb 
                       LEFT JOIN tbuser u ON sb.created_by = u.kode_karyawan 
                       WHERE sb.id = ?";
    $stmt_bank_detail = $pdo->prepare($sql_bank_detail);
    $stmt_bank_detail->execute([$bank_id]);
    $bank_detail = $stmt_bank_detail->fetch(PDO::FETCH_ASSOC);
    
    if (!$bank_detail) {
        die('Bank detail not found');
    }
    
    // Query yang sama dengan web interface - menampilkan detail individual transaksi
    $sql_closing = "SELECT 
                       c.nama_cabang,
                       sk.kode_setoran,
                       sk.tanggal_setoran,
                       kt.kode_transaksi,
                       COALESCE(kt.tanggal_transaksi, sk.tanggal_setoran) as tanggal_transaksi,
                       COALESCE(kt.setoran_real, sk.jumlah_setoran, sk.jumlah_diterima, 0) as setoran_real,
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
                           WHEN kt.kode_transaksi IS NULL THEN 'BELUM DIPROSES'
                           ELSE 'TRANSAKSI BIASA'
                       END as jenis_transaksi,
                       kt.tanggal_closing
                   FROM setoran_ke_bank_detail_closing_kasir sbd
                   JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
                   JOIN tbcabang c ON sk.kode_cabang = c.cabang_ref_kode
                   LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                   WHERE sbd.setoran_ke_bank_id = ?";
    
    $params_closing = [$bank_id];
    
    // Add date filter if provided
    if ($tanggal_awal && $tanggal_akhir) {
        $sql_closing .= " AND (
            (kt.tanggal_transaksi IS NOT NULL AND kt.tanggal_transaksi BETWEEN ? AND ?) OR
            (kt.tanggal_transaksi IS NULL AND sk.tanggal_setoran BETWEEN ? AND ?)
        )";
        $params_closing[] = $tanggal_awal;
        $params_closing[] = $tanggal_akhir;
        $params_closing[] = $tanggal_awal;
        $params_closing[] = $tanggal_akhir;
    }
    
    // Add branch filter if provided
    if ($cabang !== 'all') {
        $sql_closing .= " AND c.nama_cabang = ?";
        $params_closing[] = $cabang;
    }
    
    $sql_closing .= " ORDER BY c.nama_cabang, kt.tanggal_closing, sk.tanggal_setoran";
    
    $stmt_closing = $pdo->prepare($sql_closing);
    $stmt_closing->execute($params_closing);
    $closing_data = $stmt_closing->fetchAll(PDO::FETCH_ASSOC);
    
    $sheet->setTitle('Detail Setoran Bank');
    
    // Title dengan info filter
    $title = 'DETAIL SETORAN KE BANK';
    if ($tanggal_awal && $tanggal_akhir) {
        $title .= ' - PERIODE: ' . date('d/m/Y', strtotime($tanggal_awal)) . ' s/d ' . date('d/m/Y', strtotime($tanggal_akhir));
    }
    if ($cabang !== 'all') {
        $title .= ' - CABANG: ' . strtoupper($cabang);
    }
    
    $sheet->setCellValue('A1', $title);
    $sheet->mergeCells('A1:L1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Enhanced Bank info - matching the modal "Informasi Setoran Bank"
    $currentRow = 3;
    
    // Header for Bank Information Section
    $sheet->setCellValue('A' . $currentRow, 'INFORMASI SETORAN BANK');
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('007BFF');
    $sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3F2FD');
    $sheet->getStyle('A' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->mergeCells('A' . $currentRow . ':B' . $currentRow);
    $currentRow++;
    
    // Bank info details
    $bank_info = [
        ['Tanggal ke Penyetor:', date('d/m/Y', strtotime($bank_detail['tanggal_setoran']))],
        ['Tanggal Setor ke Bank:', !empty($bank_detail['tanggal_setor']) ? date('d/m/Y', strtotime($bank_detail['tanggal_setor'])) : '-'],
        ['Rekening Tujuan:', $bank_detail['rekening_tujuan']],
        ['Total Setoran:', 'Rp ' . number_format($bank_detail['total_setoran'], 0, ',', '.')],
        ['Disetor Oleh:', $bank_detail['created_by_name']]
    ];
    
    foreach ($bank_info as $info) {
        $sheet->setCellValue('A' . $currentRow, $info[0]);
        $sheet->setCellValue('B' . $currentRow, $info[1]);
        $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
        $currentRow++;
    }
    
    $currentRow += 2; // Add spacing
    
    // Header for transaction details
    $sheet->setCellValue('A' . $currentRow, 'DETAIL SELURUH TRANSAKSI SETORAN');
    $sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(12);
    $sheet->mergeCells('A' . $currentRow . ':I' . $currentRow);
    $currentRow++;
    
    // Table headers - matching web interface
    $headers = ['Cabang', 'Kode Setoran', 'Tgl Setoran', 'Kode Transaksi', 'Tgl Closing', 'Setoran Awal', 'Setoran Kekas Masuk', 'Setoran Diterima', 'Jenis'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $sheet->getStyle($col . $currentRow)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($col . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('007BFF');
        $sheet->getStyle($col . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $col++;
    }
    $currentRow++;
    
    // Data rows
    $total_awal = 0;
    $total_kekas = 0;
    $total_diterima = 0;
    
    foreach ($closing_data as $detail) {
        $sheet->setCellValue('A' . $currentRow, $detail['nama_cabang']);
        $sheet->setCellValue('B' . $currentRow, $detail['kode_setoran']);
        $sheet->setCellValue('C' . $currentRow, date('d/m/Y', strtotime($detail['tanggal_setoran'])));
        $sheet->setCellValue('D' . $currentRow, $detail['kode_transaksi'] ?? '-');
        // Prioritas: tanggal_closing > tanggal_transaksi > '-'
        $tgl_closing_display = '-';
        if (!empty($detail['tanggal_closing']) && $detail['tanggal_closing'] !== '0000-00-00') {
            $tgl_closing_display = date('d/m/Y', strtotime($detail['tanggal_closing']));
        } elseif (!empty($detail['tanggal_transaksi']) && $detail['tanggal_transaksi'] !== '0000-00-00') {
            $tgl_closing_display = date('d/m/Y', strtotime($detail['tanggal_transaksi']));
        }
        $sheet->setCellValue('E' . $currentRow, $tgl_closing_display);
        $sheet->setCellValue('F' . $currentRow, $detail['setoran_awal'] ?? 0);
        $sheet->setCellValue('G' . $currentRow, $detail['setoran_kekas_masuk'] ?? 0);
        $sheet->setCellValue('H' . $currentRow, $detail['setoran_diterima'] ?? 0);
        $sheet->setCellValue('I' . $currentRow, $detail['jenis_transaksi']);
        
        // Format currency
        $sheet->getStyle('F' . $currentRow . ':H' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A' . $currentRow . ':I' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        // Accumulate totals
        $total_awal += ($detail['setoran_awal'] ?? 0);
        $total_kekas += ($detail['setoran_kekas_masuk'] ?? 0);
        $total_diterima += ($detail['setoran_diterima'] ?? 0);
        
        $currentRow++;
    }
    
    // Grand total row
    $sheet->setCellValue('A' . $currentRow, 'TOTAL KESELURUHAN');
    $sheet->mergeCells('A' . $currentRow . ':E' . $currentRow);
    $sheet->setCellValue('F' . $currentRow, $total_awal);
    $sheet->setCellValue('G' . $currentRow, $total_kekas);
    $sheet->setCellValue('H' . $currentRow, $total_diterima);
    $sheet->getStyle('A' . $currentRow . ':I' . $currentRow)->getFont()->setBold(true);
    $sheet->getStyle('A' . $currentRow . ':I' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('28a745');
    $sheet->getStyle('A' . $currentRow . ':I' . $currentRow)->getFont()->getColor()->setRGB('FFFFFF');
    $sheet->getStyle('F' . $currentRow . ':H' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('A' . $currentRow . ':I' . $currentRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    $filename = 'Detail_Setoran_Bank_' . $bank_detail['id'] . '_' . date('Y-m-d_H-i-s') . '.xlsx';

} elseif ($type == 'monitoring') {
    // Monitoring export - Updated to match setoran_keuangan_closing_kasir.php monitoring tab
    $sql = "
        SELECT 
            kt.id,
            kt.kode_transaksi,
            kt.tanggal_transaksi,
            kt.tanggal_closing,
            kt.jam_closing,
            kt.setoran_real,
            kt.deposit_status,
            kt.kode_setoran,
            kt.nama_cabang,
            kt.validasi_at,
            kt.catatan_validasi,
            kt.jumlah_diterima_fisik,
            COALESCE(u.nama_lengkap, 'Unknown User') AS nama_karyawan,
            sk.tanggal_setoran,
            sk.status as setoran_status,
            -- Bank deposit information
            sb.id as setor_bank_id,
            sb.tanggal_setoran as tanggal_setor_bank,
            sb.total_setoran as total_setor_bank,
            sb.rekening_tujuan as bank_account,
            sb.metode_setoran,
            sb.bukti_transfer,
            sb.created_at as bank_created_at,
            sb.created_by as bank_created_by,
            -- Check if it's a closing transaction
            CASE 
                WHEN kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' THEN 'CLOSING'
                WHEN EXISTS (
                    SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                    WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
                ) THEN 'DARI_CLOSING'
                ELSE 'REGULER'
            END as jenis_transaksi,
            -- Get selisih if available
            CASE 
                WHEN kt.jumlah_diterima_fisik IS NOT NULL 
                THEN (kt.setoran_real - kt.jumlah_diterima_fisik) 
                ELSE 0 
            END as selisih_fisik
        FROM kasir_transactions_closing_kasir kt
        LEFT JOIN setoran_keuangan_closing_kasir sk ON kt.kode_setoran = sk.kode_setoran
        LEFT JOIN tbuser u ON kt.kode_karyawan = u.kode_karyawan
        LEFT JOIN setoran_ke_bank_detail_closing_kasir sbd ON sk.id = sbd.setoran_keuangan_id
        LEFT JOIN setoran_ke_bank_closing_kasir sb ON sbd.setoran_ke_bank_id = sb.id
        WHERE (kt.kode_transaksi LIKE '%CLOSING%' OR kt.kode_transaksi LIKE '%CLO%' 
               OR EXISTS (
                   SELECT 1 FROM pemasukan_kasir_closing_kasir pk 
                   WHERE pk.nomor_transaksi_closing = kt.kode_transaksi
               ))
        AND (kt.status = 'end proses' 
             OR kt.deposit_status IN ('Validasi Keuangan OK', 'Validasi Keuangan SELISIH', 'Sedang Dibawa Kurir', 'Diterima Staff Keuangan', 'Dikembalikan ke CS', 'Sudah Disetor ke Bank'))";

    $params = [];
    
    if ($tanggal_awal && $tanggal_akhir) {
        $sql .= " AND kt.tanggal_transaksi BETWEEN ? AND ?";
        $params[] = $tanggal_awal;
        $params[] = $tanggal_akhir;
    }
    
    if ($cabang !== 'all') {
        $sql .= " AND kt.nama_cabang = ?";
        $params[] = $cabang;
    }
    
    $sql .= " ORDER BY 
        CASE kt.deposit_status 
            WHEN 'Sedang Dibawa Kurir' THEN 1
            WHEN 'Diterima Staff Keuangan' THEN 2
            WHEN 'Validasi Keuangan SELISIH' THEN 3
            WHEN 'Validasi Keuangan OK' THEN 4
            WHEN 'Dikembalikan ke CS' THEN 5
            WHEN 'Sudah Disetor ke Bank' THEN 6
            ELSE 7
        END,
        kt.tanggal_transaksi DESC, kt.jam_closing DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sheet->setTitle('Monitoring Closing');
    
    // Title
    $sheet->setCellValue('A1', 'MONITORING TRANSAKSI CLOSING');
    $sheet->mergeCells('A1:M1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $currentRow = 3;
    
    // Headers
    $headers = ['Tanggal', 'Kode Transaksi', 'Cabang', 'Kasir', 'Jenis', 'Setoran Real', 'Diterima Fisik', 'Selisih', 'Status Deposit', 'Bank Account', 'Metode Setor', 'Tanggal Setor Bank', 'Catatan'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $currentRow, $header);
        $col++;
    }
    setHeaderStyle($sheet, 'A' . $currentRow . ':M' . $currentRow);
    $currentRow++;
    
    // Data
    foreach ($data as $row) {
        $diterima_fisik = $row['jumlah_diterima_fisik'] ?? $row['setoran_real'];
        $selisih = $row['selisih_fisik'] ?? 0;
        
        $sheet->setCellValue('A' . $currentRow, date('d/m/Y', strtotime($row['tanggal_transaksi'])));
        $sheet->setCellValue('B' . $currentRow, $row['kode_transaksi']);
        $sheet->setCellValue('C' . $currentRow, $row['nama_cabang']);
        $sheet->setCellValue('D' . $currentRow, $row['nama_karyawan']);
        $sheet->setCellValue('E' . $currentRow, $row['jenis_transaksi']);
        $sheet->setCellValue('F' . $currentRow, $row['setoran_real']);
        $sheet->setCellValue('G' . $currentRow, $diterima_fisik);
        $sheet->setCellValue('H' . $currentRow, $selisih);
        $sheet->setCellValue('I' . $currentRow, $row['deposit_status']);
        $sheet->setCellValue('J' . $currentRow, $row['bank_account'] ?? '-');
        $sheet->setCellValue('K' . $currentRow, $row['metode_setoran'] ?? '-');
        $sheet->setCellValue('L' . $currentRow, $row['tanggal_setor_bank'] ? date('d/m/Y', strtotime($row['tanggal_setor_bank'])) : '-');
        $sheet->setCellValue('M' . $currentRow, $row['catatan_validasi'] ?? '');
        
        // Format currency
        $sheet->getStyle('F' . $currentRow . ':H' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        
        // Color status
        switch ($row['deposit_status']) {
            case 'Validasi Keuangan SELISIH':
                $sheet->getStyle('I' . $currentRow)->getFont()->getColor()->setRGB('FF6B35');
                break;
            case 'Validasi Keuangan OK':
                $sheet->getStyle('I' . $currentRow)->getFont()->getColor()->setRGB('28a745');
                break;
            case 'Sudah Disetor ke Bank':
                $sheet->getStyle('I' . $currentRow)->getFont()->getColor()->setRGB('007bff');
                break;
        }
        
        // Color selisih
        if ($selisih < 0) {
            $sheet->getStyle('H' . $currentRow)->getFont()->getColor()->setRGB('FF0000');
        } elseif ($selisih > 0) {
            $sheet->getStyle('H' . $currentRow)->getFont()->getColor()->setRGB('FF8C00');
        }
        
        $currentRow++;
    }
    
    if (count($data) > 0) {
        setDataStyle($sheet, 'A4:M' . ($currentRow - 1));
    }
    
    $filename = 'Monitoring_Closing_Transactions_' . date('Y-m-d_H-i-s') . '.xlsx';

} else {
    die('Invalid export type');
}

// Auto-size columns
foreach (range('A', $sheet->getHighestColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Add footer with timestamp
$lastRow = $sheet->getHighestRow() + 2;
$sheet->setCellValue('A' . $lastRow, 'Generated on: ' . date('d/m/Y H:i:s'));
$sheet->setCellValue('A' . ($lastRow + 1), 'By: ' . ($nama_karyawan_aktif ?? 'System'));
$sheet->getStyle('A' . $lastRow . ':A' . ($lastRow + 1))->getFont()->setItalic(true)->setSize(10);

// Set page setup for printing
$sheet->getPageSetup()
    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
    ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
    ->setFitToPage(true)
    ->setFitToWidth(1)
    ->setFitToHeight(0);

// Set margins
$sheet->getPageMargins()
    ->setTop(0.75)
    ->setRight(0.25)
    ->setLeft(0.25)
    ->setBottom(0.75);

// Create writer and output
$writer = new Xlsx($spreadsheet);

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer->save('php://output');
exit;
?>
