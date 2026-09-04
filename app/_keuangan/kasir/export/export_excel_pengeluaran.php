<?php
// Sumber: web_kasir/export_excel_pengeluaran.php — export Excel pengeluaran.
// Gerbang asli role IN (admin,super_admin) -> kasir_approve (Task 10).
require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Shared\Date;

$is_super_admin = ($legacy_session_kasir['role'] ?? '') === 'super_admin';

// Koneksi ke database
$dsn = 'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel');
try {
    $pdo = new PDO($dsn, getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get filter parameters
$filter_tanggal_awal = $_GET['tanggal_awal'] ?? null;
$filter_tanggal_akhir = $_GET['tanggal_akhir'] ?? null;
$filter_kategori = $_GET['kategori'] ?? null;
$filter_cabang = $_GET['cabang'] ?? null;
$jenis_data = $_GET['jenis_data'] ?? 'semua';
$sort_by = $_GET['sort_by'] ?? 'tanggal';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Build query based on jenis_data
$transactions = [];

if ($jenis_data === 'semua') {
    // Langsung gunakan query asli tanpa view untuk memastikan data yang benar
    $query_kasir = "SELECT 
                    p.kode_transaksi,
                    k.nama_cabang,
                    p.kategori AS kategori_akun,
                    p.tanggal,
                    p.waktu,
                    p.kode_akun,
                    m.arti AS nama_akun,
                    p.jumlah,
                    p.umur_pakai,
                    p.keterangan_transaksi AS keterangan_akun,
                    'kasir' as jenis_sumber,
                    COALESCE(k.tanggal_transaksi, k.tanggal_closing) as tanggal_transaksi,
                    CONCAT(p.tanggal, ' ', p.waktu) as datetime_input,
                    k.status as status_transaksi
                  FROM pengeluaran_kasir_closing_kasir p
                  JOIN kasir_transactions_closing_kasir k ON p.kode_transaksi = k.kode_transaksi
                  LEFT JOIN master_akun_closing_kasir m ON p.kode_akun = m.kode_akun
                  WHERE 1 = 1
                    AND k.status <> 'dibatalkan'";
    
    $query_pusat = "SELECT 
                    pp.kode_transaksi,
                    pp.cabang as nama_cabang,
                    pp.kategori AS kategori_akun,
                    pp.tanggal,
                    pp.waktu,
                    pp.kode_akun,
                    ma.arti AS nama_akun,
                    pp.jumlah,
                    pp.umur_pakai,
                    pp.keterangan AS keterangan_akun,
                    'pusat' as jenis_sumber,
                    pp.tanggal_transaksi,
                    CONCAT(pp.tanggal, ' ', pp.waktu) as datetime_input,
                    '-' as status_transaksi
                  FROM pengeluaran_pusat_closing_kasir pp
                  LEFT JOIN master_akun_closing_kasir ma ON pp.kode_akun = ma.kode_akun
                  WHERE 1 = 1";
    
    // Add filters
    $filter_conditions_kasir = [];
    $filter_conditions_pusat = [];
    
    if ($filter_tanggal_awal && $filter_tanggal_akhir) {
        // PERBAIKAN: Filter berdasarkan tanggal_transaksi, bukan tanggal input (konsisten dengan web)
        $filter_conditions_kasir[] = " AND k.tanggal_transaksi BETWEEN :tanggal_awal AND :tanggal_akhir";
        $filter_conditions_pusat[] = " AND pp.tanggal_transaksi BETWEEN :tanggal_awal AND :tanggal_akhir";
    }
    if ($filter_kategori) {
        $filter_conditions_kasir[] = " AND p.kategori = :kategori";
        $filter_conditions_pusat[] = " AND pp.kategori = :kategori";
    }
    if ($filter_cabang) {
        $filter_conditions_kasir[] = " AND k.nama_cabang = :cabang";
        $filter_conditions_pusat[] = " AND pp.cabang = :cabang";
    }
    
    $query_kasir .= implode('', $filter_conditions_kasir);
    $query_pusat .= implode('', $filter_conditions_pusat);
    
    // Adjust sort column for fallback
    $fallback_sort_by = $sort_by;
    if ($sort_by === 'nama_cabang') {
        $fallback_sort_by = 'nama_cabang';
    } elseif ($sort_by === 'kategori_akun') {
        $fallback_sort_by = 'kategori_akun';
    }
    
    $query = "({$query_kasir}) UNION ALL ({$query_pusat}) ORDER BY {$fallback_sort_by} " . strtoupper($sort_order);
} else {
    // Single data source query - langsung gunakan tabel asli
    if ($jenis_data === 'pusat') {
        $query = "SELECT 
                    pp.kode_transaksi,
                    pp.cabang as nama_cabang,
                    pp.kategori AS kategori_akun,
                    pp.tanggal,
                    pp.waktu,
                    pp.kode_akun,
                    ma.arti AS nama_akun,
                    pp.jumlah,
                    pp.umur_pakai,
                    pp.keterangan AS keterangan_akun,
                    'pusat' as jenis_sumber,
                    pp.tanggal_transaksi,
                    CONCAT(pp.tanggal, ' ', pp.waktu) as datetime_input,
                    '-' as status_transaksi
                  FROM pengeluaran_pusat_closing_kasir pp
                  LEFT JOIN master_akun_closing_kasir ma ON pp.kode_akun = ma.kode_akun
                  WHERE 1 = 1";
        
        if ($filter_tanggal_awal && $filter_tanggal_akhir) {
            // PERBAIKAN: Filter berdasarkan tanggal_transaksi, bukan tanggal input
            $query .= " AND pp.tanggal_transaksi BETWEEN :tanggal_awal AND :tanggal_akhir";
        }
        if ($filter_kategori) {
            $query .= " AND pp.kategori = :kategori";
        }
        if ($filter_cabang) {
            $query .= " AND pp.cabang = :cabang";
        }
        
        $fallback_sort_by = $sort_by;
        if ($sort_by === 'nama_cabang') $fallback_sort_by = 'pp.cabang';
        elseif ($sort_by === 'kategori_akun') $fallback_sort_by = 'pp.kategori';
        elseif (in_array($sort_by, ['tanggal', 'waktu', 'kode_akun', 'jumlah', 'umur_pakai'])) $fallback_sort_by = 'pp.' . $sort_by;
        
        $query .= " ORDER BY {$fallback_sort_by} " . strtoupper($sort_order);
        
    } else {
        $query = "SELECT 
                    p.kode_transaksi,
                    k.nama_cabang,
                    p.kategori AS kategori_akun,
                    p.tanggal,
                    p.waktu,
                    p.kode_akun,
                    m.arti AS nama_akun,
                    p.jumlah,
                    p.umur_pakai,
                    p.keterangan_transaksi AS keterangan_akun,
                    'kasir' as jenis_sumber,
                    COALESCE(k.tanggal_transaksi, k.tanggal_closing) as tanggal_transaksi,
                    CONCAT(p.tanggal, ' ', p.waktu) as datetime_input,
                    k.status as status_transaksi
                  FROM pengeluaran_kasir_closing_kasir p
                  JOIN kasir_transactions_closing_kasir k ON p.kode_transaksi = k.kode_transaksi
                  LEFT JOIN master_akun_closing_kasir m ON p.kode_akun = m.kode_akun
                  WHERE 1 = 1
                    AND k.status <> 'dibatalkan'";
        
        if ($filter_tanggal_awal && $filter_tanggal_akhir) {
            // PERBAIKAN: Filter berdasarkan tanggal_transaksi, bukan tanggal input
            $query .= " AND k.tanggal_transaksi BETWEEN :tanggal_awal AND :tanggal_akhir";
        }
        if ($filter_kategori) {
            $query .= " AND p.kategori = :kategori";
        }
        if ($filter_cabang) {
            $query .= " AND k.nama_cabang = :cabang";
        }
        
        $fallback_sort_by = $sort_by;
        if ($sort_by === 'nama_cabang') $fallback_sort_by = 'k.nama_cabang';
        elseif ($sort_by === 'kategori_akun') $fallback_sort_by = 'p.kategori';
        elseif (in_array($sort_by, ['tanggal', 'waktu', 'kode_akun', 'jumlah', 'umur_pakai'])) $fallback_sort_by = 'p.' . $sort_by;
        
        $query .= " ORDER BY {$fallback_sort_by} " . strtoupper($sort_order);
    }
}

// Execute query
$stmt = $pdo->prepare($query);
if ($filter_tanggal_awal && $filter_tanggal_akhir) {
    $stmt->bindParam(':tanggal_awal', $filter_tanggal_awal);
    $stmt->bindParam(':tanggal_akhir', $filter_tanggal_akhir);
}
if ($filter_kategori) {
    $stmt->bindParam(':kategori', $filter_kategori);
}
if ($filter_cabang) {
    $stmt->bindParam(':cabang', $filter_cabang);
}
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create Excel file
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Data Pengeluaran');

// Set headers - Updated to match database structure
$headers = [
    'No', 'Tanggal Input', 'Waktu Input', 'Status', 'Kode Transaksi', 'Nama Cabang', 'Sumber',
    'Kategori Akun', 'Nama Akun', 'Tanggal Transaksi', 'Kode Akun', 
    'Umur Pakai (Bulan)', 'Keterangan Akun', 'Jumlah (Rp)'
];

$columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N'];
foreach ($headers as $index => $header) {
    $sheet->setCellValue($columnLetters[$index] . '1', $header);
}

// Style header row
$sheet->getStyle('A1:N1')->getFont()->setBold(true);
$sheet->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

// Add data
$rowIndex = 2;
foreach ($transactions as $index => $row) {
    // Column A: No
    $sheet->setCellValue("A{$rowIndex}", $index + 1);
    
    // Column B: Tanggal Input (from datetime_input which is CONCAT of tanggal + waktu)
    // Extract just the date part from datetime_input
    $tanggal_input = '';
    if (isset($row['datetime_input']) && $row['datetime_input']) {
        $tanggal_input = explode(' ', $row['datetime_input'])[0];
    } elseif (isset($row['tanggal'])) {
        $tanggal_input = $row['tanggal'];
    }
    $tanggal_input_excel = null;
    if (!empty($tanggal_input) && $tanggal_input !== '0000-00-00') {
        try {
            $tanggal_input_dt = new \DateTimeImmutable($tanggal_input . ' 00:00:00', new \DateTimeZone('UTC'));
            $tanggal_input_excel = Date::PHPToExcel($tanggal_input_dt);
        } catch (\Exception $e) {
            $tanggal_input_excel = null;
        }
    }
    $sheet->setCellValue("B{$rowIndex}", $tanggal_input_excel);
    
    // Column C: Waktu Input
    $sheet->setCellValue("C{$rowIndex}", $row['waktu'] ?? '-');
    
    // Column D: Status
    $sheet->setCellValue("D{$rowIndex}", $row['status_transaksi'] ?? '-');
    
    // Column E: Kode Transaksi
    $sheet->setCellValue("E{$rowIndex}", $row['kode_transaksi'] ?? '-');
    
    // Column F: Nama Cabang
    $sheet->setCellValue("F{$rowIndex}", $row['nama_cabang'] ?? '-');
    
    // Column G: Sumber
    $sheet->setCellValue("G{$rowIndex}", ucfirst($row['jenis_sumber'] ?? 'Unknown'));
    
    // Column H: Kategori Akun
    $sheet->setCellValue("H{$rowIndex}", ucfirst(str_replace('_', ' ', $row['kategori_akun'] ?? '-')));
    
    // Column I: Nama Akun
    $sheet->setCellValue("I{$rowIndex}", $row['nama_akun'] ?? '-');
    
    // Column J: Tanggal Transaksi (from kasir_transactions_closing_kasir table - actual transaction date)
    $tanggal_transaksi = '';
    if (isset($row['tanggal_transaksi']) && $row['tanggal_transaksi'] && $row['tanggal_transaksi'] !== '0000-00-00' && $row['tanggal_transaksi'] !== null) {
        $tanggal_transaksi = $row['tanggal_transaksi'];
    } else {
        // If tanggal_transaksi is not available, show as empty/dash to differentiate from input date
        $tanggal_transaksi = '-';
    }
    $tanggal_transaksi_excel = null;
    if (!empty($tanggal_transaksi) && $tanggal_transaksi !== '-' && $tanggal_transaksi !== '0000-00-00') {
        try {
            $tanggal_transaksi_dt = new \DateTimeImmutable($tanggal_transaksi . ' 00:00:00', new \DateTimeZone('UTC'));
            $tanggal_transaksi_excel = Date::PHPToExcel($tanggal_transaksi_dt);
        } catch (\Exception $e) {
            $tanggal_transaksi_excel = null;
        }
    }
    $sheet->setCellValue("J{$rowIndex}", $tanggal_transaksi_excel);
    
    // Column K: Kode Akun
    $sheet->setCellValue("K{$rowIndex}", $row['kode_akun'] ?? '-');
    
    // Column L: Umur Pakai
    $sheet->setCellValue("L{$rowIndex}", $row['umur_pakai'] ?? '-');
    
    // Column M: Keterangan Akun
    $sheet->setCellValue("M{$rowIndex}", $row['keterangan_akun'] ?? '-');
    
    // Column N: Jumlah
    $sheet->setCellValue("N{$rowIndex}", $row['jumlah'] ?? 0);
    
    $rowIndex++;
}

// Apply formatting
if ($rowIndex > 2) {
    // Format Tanggal Input (Column B)
    $sheet->getStyle("B2:B" . ($rowIndex - 1))
          ->getNumberFormat()
          ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
    
    // Format Tanggal Transaksi (Column J)
    $sheet->getStyle("J2:J" . ($rowIndex - 1))
          ->getNumberFormat()
          ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
    
    // Format Jumlah (Column N)
    $sheet->getStyle("N2:N" . ($rowIndex - 1))
          ->getNumberFormat()
          ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}

// Auto-adjust column widths
foreach (range('A', 'N') as $columnID) {
    $sheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Apply border style
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
if ($rowIndex > 1) {
    $sheet->getStyle("A1:N" . ($rowIndex - 1))->applyFromArray($styleArray);
}

// Add summary row
if (count($transactions) > 0) {
    $total_amount = array_sum(array_column($transactions, 'jumlah'));

    $rowIndex++;
    $sheet->setCellValue("A{$rowIndex}", "TOTAL PENGELUARAN");
    $sheet->mergeCells("A{$rowIndex}:M{$rowIndex}");
    $sheet->setCellValue("N{$rowIndex}", $total_amount);
    $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getFont()->setBold(true);
    $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCFFCC');
    $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->applyFromArray($styleArray);
    $sheet->getStyle("N{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
}

// Add information sheet
$infoSheet = $spreadsheet->createSheet();
$infoSheet->setTitle('Info Laporan');

$filter_period_display = 'Semua Data';
if ($filter_tanggal_awal && $filter_tanggal_akhir) {
    if ($filter_tanggal_awal === $filter_tanggal_akhir) {
        $filter_period_display = date('d/m/Y', strtotime($filter_tanggal_awal));
    } else {
        $filter_period_display = date('d/m/Y', strtotime($filter_tanggal_awal)) . ' - ' . date('d/m/Y', strtotime($filter_tanggal_akhir));
    }
}

$total_amount = array_sum(array_column($transactions, 'jumlah'));

$infoData = [
    ['LAPORAN PENGELUARAN - FITMOTOR MAINTENANCE'],
    [''],
    ['INFORMASI FILTER:'],
    ['Periode:', $filter_period_display],
    ['Kategori:', $filter_kategori ? ucfirst($filter_kategori) : 'Semua Kategori'],
    ['Cabang:', $filter_cabang ? ucfirst($filter_cabang) : 'Semua Cabang'],
    ['Jenis Data:', ucfirst($jenis_data)],
    ['Sorting:', ucfirst($sort_by) . ' - ' . ($sort_order === 'ASC' ? 'Ascending' : 'Descending')],
    [''],
    ['STATISTIK:'],
    ['Total Transaksi:', number_format(count($transactions)) . ' transaksi'],
    ['Total Pengeluaran:', 'Rp ' . number_format($total_amount, 0, ',', '.')],
    [''],
    ['KETERANGAN URUTAN KOLOM:'],
    ['1. No - Nomor urut transaksi'],
    ['2. Tanggal Input - Tanggal data diinput ke sistem'],
    ['3. Waktu Input - Waktu data diinput ke sistem'],
    ['4. Status - Status transaksi kasir'],
    ['5. Kode Transaksi - Kode unik transaksi'],
    ['6. Nama Cabang - Nama cabang tempat transaksi'],
    ['7. Sumber - Sumber data (Kasir/Pusat)'],
    ['8. Kategori Akun - Kategori/jenis akun (Biaya/Non Biaya)'],
    ['9. Nama Akun - Nama akun pengeluaran'],
    ['10. Tanggal Transaksi - Tanggal aktual transaksi terjadi'],
    ['11. Kode Akun - Kode akun pengeluaran'],
    ['12. Umur Pakai (Bulan) - Umur pakai aset dalam bulan'],
    ['13. Keterangan Akun - Keterangan detail transaksi'],
    ['14. Jumlah (Rp) - Nominal pengeluaran'],
    [''],
    ['CATATAN PENTING:'],
    ['- Laporan ini dibuat secara otomatis oleh sistem Fitmotor Maintenance'],
    ['- Data transaksi kasir yang statusnya dibatalkan tidak ditampilkan agar tidak double'],
    ['- Format kolom telah disesuaikan dengan kebutuhan analisis'],
    ['- Data yang ditampilkan sesuai dengan filter dan sorting yang dipilih'],
    ['- File ini dapat dibuka dengan Microsoft Excel atau aplikasi spreadsheet lainnya']
];

$infoSheet->fromArray($infoData, NULL, 'A1');
$infoSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$infoSheet->getStyle('A3')->getFont()->setBold(true);
$infoSheet->getStyle('A10')->getFont()->setBold(true);
$infoSheet->getStyle('A14')->getFont()->setBold(true);
$infoSheet->getStyle('A27')->getFont()->setBold(true);

foreach (range('A', 'B') as $columnID) {
    $infoSheet->getColumnDimension($columnID)->setAutoSize(true);
}

// Set active sheet back to main data
$spreadsheet->setActiveSheetIndex(0);

// Generate filename
$filename = 'Laporan_Pengeluaran_' . date('Y-m-d_H-i-s') . '.xlsx';

// Set headers for download
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>
