<?php
// Sumber: web_kasir/export_excel_pemasukan.php — export Excel pemasukan.
// Gerbang asli role IN (admin,super_admin) -> kasir_approve (Task 10).
ob_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;



// Fungsi untuk mengekstrak tanggal dari kode_transaksi
function extractTransactionDate($kode_transaksi, $format = 'full') {
    if (preg_match('/(PMK|PST)-(\d{4})(\d{2})(\d{2})-/', $kode_transaksi, $matches)) {
        $year = $matches[2];
        $month = $matches[3];
        $day = $matches[4];
        switch ($format) {
            case 'year': return $year;
            case 'month': return $year . '-' . $month;
            case 'full':
            default: return $year . '-' . $month . '-' . $day;
        }
    }
    return '-';
}

// Fungsi untuk menentukan format tanggal berdasarkan filter
function determineDateFormat($tanggal_awal, $tanggal_akhir) {
    if ($tanggal_awal === $tanggal_akhir && preg_match('/^\d{4}$/', $tanggal_awal)) {
        return 'year';
    }
    if ($tanggal_awal === $tanggal_akhir && preg_match('/^\d{4}-\d{2}$/', $tanggal_awal)) {
        return 'month';
    }
    return 'full';
}

// Fungsi untuk memparse filter tanggal fleksibel
function parseFlexibleDate($date_string, $is_start = true) {
    if (empty($date_string)) return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_string)) return $date_string;
    if (preg_match('/^\d{4}-\d{2}$/', $date_string)) {
        return $is_start ? $date_string . '-01' : date('Y-m-t', strtotime($date_string . '-01'));
    }
    if (preg_match('/^\d{4}$/', $date_string)) {
        return $is_start ? $date_string . '-01-01' : $date_string . '-12-31';
    }
    return $date_string;
}

// Koneksi database
try {
    $dsn = 'mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel') . ';charset=utf8mb4';
    $pdo = new PDO($dsn, getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");
} catch (PDOException $e) {
    ob_end_clean();
    die("Koneksi database gagal: " . htmlspecialchars($e->getMessage()));
}

// PERBAIKAN: Set variabel untuk filter dengan nama parameter yang sama seperti di web
$jenis_data = $_GET['jenis_data'] ?? 'semua';
$filter_tanggal_awal = $_GET['tanggal_awal'] ?? $_GET['filter_tanggal_awal'] ?? null; // Support both parameter names
$filter_tanggal_akhir = $_GET['tanggal_akhir'] ?? $_GET['filter_tanggal_akhir'] ?? null; // Support both parameter names
$filter_cabang = $_GET['cabang'] ?? $_GET['filter_cabang'] ?? null; // Support both parameter names

$tanggal_awal_parsed = parseFlexibleDate($filter_tanggal_awal, true);
$tanggal_akhir_parsed = parseFlexibleDate($filter_tanggal_akhir, false);
$date_format = determineDateFormat($filter_tanggal_awal, $filter_tanggal_akhir);

$sort_by = $_GET['sort_by'] ?? 'tanggal';
$sort_order = $_GET['sort_order'] ?? 'DESC';

$allowed_sort_columns = ['tanggal', 'waktu', 'kode_transaksi', 'nama_cabang', 'kategori_akun', 'nama_akun', 'tanggal_transaksi', 'kode_akun', 'keterangan_akun', 'jumlah', 'jenis_sumber', 'status_transaksi'];
if (!in_array($sort_by, $allowed_sort_columns)) $sort_by = 'tanggal';
if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) $sort_order = 'DESC';

// PERBAIKAN: Query utama dengan filter jenis_data yang sesuai dengan web
try {
    $query_kasir = "";
    $query_pusat = "";
    
    // Apply jenis_data filter like in web interface
    if ($jenis_data === 'semua' || $jenis_data === 'kasir') {
        $query_kasir = "SELECT 
                        CAST(p.kode_transaksi AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kode_transaksi,
                        CAST(k.nama_cabang AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_cabang,
                        p.tanggal,
                        p.waktu,
                        CAST(p.kode_akun AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS kode_akun,
                        CAST(m.arti AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_akun,
                        CAST(m.jenis_akun AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kategori_akun,
                        p.jumlah,
                        CAST(p.keterangan_transaksi AS CHAR(255)) COLLATE utf8mb4_unicode_ci AS keterangan_akun,
                        CAST('kasir' AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS jenis_sumber,
                        k.tanggal_transaksi,
                        CAST(CONCAT(p.tanggal, ' ', p.waktu) AS CHAR(21)) COLLATE utf8mb4_unicode_ci AS datetime_input,
                        CAST(NULL AS CHAR(50)) COLLATE utf8mb4_unicode_ci AS nama_karyawan,
                        CAST(NULL AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS umur_pakai,
                        CAST(k.status AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS status_transaksi
                    FROM pemasukan_kasir_closing_kasir p
                    JOIN kasir_transactions_closing_kasir k ON p.kode_transaksi = k.kode_transaksi
                    LEFT JOIN master_akun_closing_kasir m ON p.kode_akun = m.kode_akun
                    WHERE 1=1
                      AND k.status <> 'dibatalkan'";
    }

    if (($jenis_data === 'semua' || $jenis_data === 'pusat') && ($legacy_session_kasir['role'] ?? '') === 'super_admin') {
        $query_pusat = "SELECT 
                        CAST(pp.kode_transaksi AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kode_transaksi,
                        CAST(pp.cabang AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_cabang,
                        pp.tanggal,
                        pp.waktu,
                        CAST(pp.kode_akun AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS kode_akun,
                        CAST(ma.arti AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_akun,
                        CAST(ma.jenis_akun AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kategori_akun,
                        pp.jumlah,
                        CAST(pp.keterangan AS CHAR(255)) COLLATE utf8mb4_unicode_ci AS keterangan_akun,
                        CAST('pusat' AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS jenis_sumber,
                        pp.tanggal_transaksi,
                        CAST(CONCAT(pp.tanggal, ' ', pp.waktu) AS CHAR(21)) COLLATE utf8mb4_unicode_ci AS datetime_input,
                        CAST(u.nama_lengkap AS CHAR(50)) COLLATE utf8mb4_unicode_ci AS nama_karyawan,
                        CAST(NULL AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS umur_pakai,
                        CAST('-' AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS status_transaksi
                    FROM pemasukan_pusat_closing_kasir pp
                    LEFT JOIN master_akun_closing_kasir ma ON pp.kode_akun = ma.kode_akun
                    LEFT JOIN tbuser u ON pp.kode_karyawan = u.kode_karyawan
                    WHERE 1=1";
    }

    $params = [];
    $filters = [];
    if ($tanggal_awal_parsed && $tanggal_akhir_parsed) {
        // PERBAIKAN: Filter berdasarkan tanggal_transaksi, bukan tanggal input (konsisten dengan web)
        $filters[] = " AND tanggal_transaksi BETWEEN :tanggal_awal AND :tanggal_akhir";
        $params[':tanggal_awal'] = $tanggal_awal_parsed;
        $params[':tanggal_akhir'] = $tanggal_akhir_parsed;
    }
    if ($filter_cabang) {
        $filters[] = " AND nama_cabang = :cabang";
        $params[':cabang'] = $filter_cabang;
    }

    $filter_string = implode('', $filters);
    
    // PERBAIKAN: Build final query based on jenis_data filter
    $queries = [];
    
    if (!empty($query_kasir)) {
        $query_kasir .= $filter_string;
        $queries[] = $query_kasir;
    }
    
    if (!empty($query_pusat)) {
        // For pusat query, adjust column names in filter
        $filter_string_pusat = str_replace('nama_cabang', 'cabang', $filter_string);
        $query_pusat .= $filter_string_pusat;
        $queries[] = $query_pusat;
    }
    
    // Handle case where no queries are built (shouldn't happen but safety check)
    if (empty($queries)) {
        throw new Exception("No valid data source selected for jenis_data: " . $jenis_data);
    }
    
    $sort_column = $sort_by;
    if ($sort_by === 'nama_cabang') $sort_column = 'nama_cabang';
    elseif ($sort_by === 'kategori_akun') $sort_column = 'kategori_akun';
    elseif ($sort_by === 'kode_akun') $sort_column = 'kode_akun';
    elseif ($sort_by === 'nama_akun') $sort_column = 'nama_akun';
    elseif ($sort_by === 'keterangan_akun') $sort_column = 'keterangan_akun';
    elseif ($sort_by === 'jenis_sumber') $sort_column = 'jenis_sumber';
    elseif ($sort_by === 'tanggal_transaksi') $sort_column = 'tanggal_transaksi';
    elseif ($sort_by === 'kode_transaksi') $sort_column = 'kode_transaksi';
    elseif ($sort_by === 'waktu') $sort_column = 'waktu';
    elseif ($sort_by === 'jumlah') $sort_column = 'jumlah';

    // Build final query with proper UNION
    if (count($queries) > 1) {
        $query = "(" . implode(") UNION ALL (", $queries) . ") ORDER BY $sort_column " . strtoupper($sort_order);
    } else {
        $query = $queries[0] . " ORDER BY $sort_column " . strtoupper($sort_order);
    }
    
    if ($sort_by !== 'tanggal') $query .= ", tanggal " . strtoupper($sort_order);

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // PERBAIKAN: Fallback ke query original jika VIEW belum dibuat (seperti di web)
    error_log("VIEW fallback triggered in export: " . $e->getMessage());
    
    $query_kasir = "";
    $query_pusat = "";
    
    if ($jenis_data === 'semua' || $jenis_data === 'kasir') {
        $query_kasir = "SELECT 
                        CAST(p.kode_transaksi AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kode_transaksi,
                        CAST(k.nama_cabang AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_cabang,
                        p.tanggal,
                        p.waktu,
                        CAST(p.kode_akun AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS kode_akun,
                        CAST(m.arti AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_akun,
                        CAST(m.jenis_akun AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kategori_akun,
                        p.jumlah,
                        CAST(p.keterangan_transaksi AS CHAR(255)) COLLATE utf8mb4_unicode_ci AS keterangan_akun,
                        CAST('kasir' AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS jenis_sumber,
                        k.tanggal_transaksi,
                        CAST(CONCAT(p.tanggal, ' ', p.waktu) AS CHAR(21)) COLLATE utf8mb4_unicode_ci AS datetime_input,
                        CAST(NULL AS CHAR(50)) COLLATE utf8mb4_unicode_ci AS nama_karyawan,
                        CAST(NULL AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS umur_pakai,
                        CAST(k.status AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS status_transaksi
                      FROM pemasukan_kasir_closing_kasir p
                      JOIN kasir_transactions_closing_kasir k ON p.kode_transaksi = k.kode_transaksi
                      LEFT JOIN master_akun_closing_kasir m ON p.kode_akun = m.kode_akun
                      WHERE 1=1
                        AND k.status <> 'dibatalkan'";
    }
    
    if (($jenis_data === 'semua' || $jenis_data === 'pusat') && ($legacy_session_kasir['role'] ?? '') === 'super_admin') {
        $query_pusat = "SELECT 
                        CAST(pp.kode_transaksi AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kode_transaksi,
                        CAST(pp.cabang AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_cabang,
                        pp.tanggal,
                        pp.waktu,
                        CAST(pp.kode_akun AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS kode_akun,
                        CAST(ma.arti AS CHAR(100)) COLLATE utf8mb4_unicode_ci AS nama_akun,
                        CAST(ma.jenis_akun AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS kategori_akun,
                        pp.jumlah,
                        CAST(pp.keterangan AS CHAR(255)) COLLATE utf8mb4_unicode_ci AS keterangan_akun,
                        CAST('pusat' AS CHAR(10)) COLLATE utf8mb4_unicode_ci AS jenis_sumber,
                        COALESCE(pp.tanggal_transaksi, pp.tanggal) as tanggal_transaksi,
                        CAST(CONCAT(pp.tanggal, ' ', pp.waktu) AS CHAR(21)) COLLATE utf8mb4_unicode_ci AS datetime_input,
                        CAST(u.nama_lengkap AS CHAR(50)) COLLATE utf8mb4_unicode_ci AS nama_karyawan,
                        CAST(NULL AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS umur_pakai,
                        CAST('-' AS CHAR(20)) COLLATE utf8mb4_unicode_ci AS status_transaksi
                      FROM pemasukan_pusat_closing_kasir pp
                      LEFT JOIN master_akun_closing_kasir ma ON pp.kode_akun = ma.kode_akun
                      LEFT JOIN tbuser u ON pp.kode_karyawan = u.kode_karyawan
                      WHERE 1=1";
    }
    
    // Apply same filter logic
    $queries = [];
    if (!empty($query_kasir)) {
        $query_kasir .= $filter_string;
        $queries[] = $query_kasir;
    }
    if (!empty($query_pusat)) {
        $filter_string_pusat = str_replace('nama_cabang', 'cabang', $filter_string);
        $query_pusat .= $filter_string_pusat;
        $queries[] = $query_pusat;
    }
    
    if (empty($queries)) {
        throw new Exception("No valid data source selected for jenis_data: " . $jenis_data);
    }
    
    // Apply same sort column mapping for fallback
    $sort_column = $sort_by;
    if ($sort_by === 'nama_cabang') $sort_column = 'nama_cabang';
    elseif ($sort_by === 'kategori_akun') $sort_column = 'kategori_akun';
    elseif ($sort_by === 'kode_akun') $sort_column = 'kode_akun';
    elseif ($sort_by === 'nama_akun') $sort_column = 'nama_akun';
    elseif ($sort_by === 'keterangan_akun') $sort_column = 'keterangan_akun';
    elseif ($sort_by === 'jenis_sumber') $sort_column = 'jenis_sumber';
    elseif ($sort_by === 'tanggal_transaksi') $sort_column = 'tanggal_transaksi';
    elseif ($sort_by === 'kode_transaksi') $sort_column = 'kode_transaksi';
    elseif ($sort_by === 'waktu') $sort_column = 'waktu';
    elseif ($sort_by === 'jumlah') $sort_column = 'jumlah';
    
    if (count($queries) > 1) {
        $query = "(" . implode(") UNION ALL (", $queries) . ") ORDER BY $sort_column " . strtoupper($sort_order);
    } else {
        $query = $queries[0] . " ORDER BY $sort_column " . strtoupper($sort_order);
    }
    
    if ($sort_by !== 'tanggal') $query .= ", tanggal " . strtoupper($sort_order);
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// PERBAIKAN: Bungkus semua kode spreadsheet dengan try-catch yang benar
try {
    // Buat Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set properti dokumen
    $spreadsheet->getProperties()
        ->setCreator("Fitmotor Maintenance System")
        ->setLastModifiedBy($nama_karyawan_aktif ?? 'System')
        ->setTitle("Laporan Pemasukan")
        ->setSubject("Detail Pemasukan")
        ->setDescription("Laporan detail pemasukan dari sistem Fitmotor Maintenance")
        ->setKeywords("pemasukan fitmotor maintenance")
        ->setCategory("Laporan Keuangan");

    // PERBAIKAN: Set header row sesuai dengan tampilan web detail pemasukan
    $headers = ['No', 'Tanggal Input', 'Waktu Input', 'Status', 'Kode Transaksi', 'Nama Cabang', 'Sumber', 'Kategori Akun', 'Nama Akun', 'Tanggal Transaksi', 'Kode Akun', 'Umur Pakai (Bulan)', 'Keterangan Akun', 'Jumlah (Rp)'];
    $sheet->fromArray($headers, NULL, 'A1');

    // Terapkan bold font dan warna fill untuk header row
    $sheet->getStyle('A1:N1')->getFont()->setBold(true);
    $sheet->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCCCCC');

    // Masukkan data rows (dengan tanggal transaksi)
    $rowIndex = 2;
    foreach ($transactions as $index => $row) {
        $tanggal_input_excel = null;
        if (!empty($row['tanggal']) && $row['tanggal'] !== '0000-00-00') {
            try {
                $tanggal_input_dt = new \DateTimeImmutable($row['tanggal'] . ' 00:00:00', new \DateTimeZone('UTC'));
                $tanggal_input_excel = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($tanggal_input_dt);
            } catch (\Exception $e) {
                $tanggal_input_excel = null;
            }
        }

        $tanggal_transaksi_excel = null;
        if (!empty($row['tanggal_transaksi']) && $row['tanggal_transaksi'] !== '0000-00-00') {
            try {
                $tanggal_transaksi_dt = new \DateTimeImmutable($row['tanggal_transaksi'] . ' 00:00:00', new \DateTimeZone('UTC'));
                $tanggal_transaksi_excel = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($tanggal_transaksi_dt);
            } catch (\Exception $e) {
                $tanggal_transaksi_excel = null;
            }
        }
        $jumlah = is_numeric($row['jumlah']) ? (float)$row['jumlah'] : 0;

        $sheet->setCellValue("A{$rowIndex}", $index + 1);
        $sheet->setCellValue("B{$rowIndex}", $tanggal_input_excel);
        $sheet->setCellValue("C{$rowIndex}", $row['waktu'] ?? '');
        $sheet->setCellValue("D{$rowIndex}", $row['status_transaksi'] ?? '-');
        $sheet->setCellValue("E{$rowIndex}", $row['kode_transaksi'] ? $row['kode_transaksi'] : 'Auto Generated');
        $sheet->setCellValue("F{$rowIndex}", ucfirst($row['nama_cabang'] ?? ''));
        $sheet->setCellValue("G{$rowIndex}", ucfirst($row['jenis_sumber'] ?? 'Unknown'));
        $sheet->setCellValue("H{$rowIndex}", $row['kategori_akun'] ?? '-');
        $sheet->setCellValue("I{$rowIndex}", $row['nama_akun'] ?? '-');
        $sheet->setCellValue("J{$rowIndex}", $tanggal_transaksi_excel);
        $sheet->setCellValue("K{$rowIndex}", $row['kode_akun'] ?? '');
        $sheet->setCellValue("L{$rowIndex}", $row['umur_pakai'] ?? '-');
        $sheet->setCellValue("M{$rowIndex}", $row['keterangan_akun'] ?? '-');
        $sheet->setCellValue("N{$rowIndex}", $jumlah);
        $rowIndex++;
    }

    // PERBAIKAN: Terapkan formatting sesuai urutan kolom yang baru
    if ($rowIndex > 2) {
        // Format tanggal input (kolom B)
        $sheet->getStyle("B2:B" . ($rowIndex - 1))
              ->getNumberFormat()
              ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        // Format tanggal transaksi (kolom I)
        $sheet->getStyle("J2:J" . ($rowIndex - 1))
              ->getNumberFormat()
              ->setFormatCode(NumberFormat::FORMAT_DATE_DDMMYYYY);
        // Format jumlah (kolom N)
        $sheet->getStyle("N2:N" . ($rowIndex - 1))
              ->getNumberFormat()
              ->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    // Auto-adjust column widths
    foreach (range('A', 'N') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Terapkan border style ke semua sel
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

    // Tambahkan summary row
    if (count($transactions) > 0) {
        $total_amount = array_sum(array_column($transactions, 'jumlah'));

        $rowIndex++;
        $sheet->setCellValue("A{$rowIndex}", "TOTAL PEMASUKAN");
        $sheet->mergeCells("A{$rowIndex}:M{$rowIndex}");
        $sheet->setCellValue("N{$rowIndex}", $total_amount);
        $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getFont()->setBold(true);
        $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFCCFFCC');
        $sheet->getStyle("A{$rowIndex}:N{$rowIndex}")->applyFromArray($styleArray);
        $sheet->getStyle("N{$rowIndex}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
    }

    // Tambahkan information sheet
    $infoSheet = $spreadsheet->createSheet();
    $infoSheet->setTitle('Info Laporan');

    $filter_period_display = 'Semua Data';
    if ($filter_tanggal_awal && $filter_tanggal_akhir) {
        if ($filter_tanggal_awal === $filter_tanggal_akhir) {
            if (preg_match('/^\d{4}-\d{2}$/', $filter_tanggal_awal)) {
                $filter_period_display = 'Bulan ' . date('F Y', strtotime($filter_tanggal_awal . '-01'));
            } elseif (preg_match('/^\d{4}$/', $filter_tanggal_awal)) {
                $filter_period_display = 'Tahun ' . $filter_tanggal_awal;
            } else {
                $filter_period_display = date('d F Y', strtotime($tanggal_awal_parsed)) . ' s/d ' . date('d F Y', strtotime($tanggal_akhir_parsed));
            }
        } else {
            $filter_period_display = date('d F Y', strtotime($tanggal_awal_parsed)) . ' s/d ' . date('d F Y', strtotime($tanggal_akhir_parsed));
        }
    }

    $infoData = [
        ['INFORMASI LAPORAN PEMASUKAN'],
        [''],
        ['Tanggal Export:', date('d F Y - H:i:s') . ' WIB'],
        ['User Export:', $nama_karyawan_aktif ?? 'Unknown User'],
        ['Role:', ucfirst($legacy_session_kasir['role'] ?? 'User')],
        [''],
        ['FILTER YANG DIGUNAKAN:'],
        ['Periode:', $filter_period_display],
        ['Cabang:', $filter_cabang ? ucfirst($filter_cabang) : 'Semua Cabang'],
        ['Jenis Data:', ucfirst($jenis_data)],
        ['Sorting:', ucfirst($sort_by) . ' - ' . ($sort_order === 'ASC' ? 'Ascending' : 'Descending')],
        [''],
        ['STATISTIK:'],
        ['Total Transaksi:', number_format(count($transactions)) . ' transaksi'],
        ['Total Pemasukan:', 'Rp ' . number_format($total_amount, 0, ',', '.')],
        [''],
        ['KETERANGAN URUTAN KOLOM (TELAH DIPERBAIKI):'],
        ['1. No - Nomor urut transaksi'],
        ['2. Tanggal Input - Tanggal data diinput ke sistem'],
        ['3. Waktu Input - Waktu data diinput ke sistem'],
        ['4. Status - Status transaksi kasir'],
        ['5. Kode Transaksi - Kode unik transaksi'],
        ['6. Cabang - Nama cabang tempat transaksi'],
        ['7. Sumber - Sumber data (Kasir/Pusat)'],
        ['8. Kategori Akun - Kategori/jenis akun'],
        ['9. Nama Akun - Nama akun'],
        ['10. Tanggal Transaksi - Tanggal aktual transaksi terjadi'],
        ['11. Kode Akun - Kode akun'],
        ['12. Umur Pakai - Umur pakai (untuk pemasukan biasanya kosong)'],
        ['13. Keterangan - Keterangan transaksi'],
        ['14. Jumlah (Rp) - Nominal transaksi'],
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
    $infoSheet->getStyle('A7')->getFont()->setBold(true);
    $infoSheet->getStyle('A13')->getFont()->setBold(true);
    $infoSheet->getStyle('A17')->getFont()->setBold(true);
    foreach (range('A', 'B') as $columnID) {
        $infoSheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Kembali ke sheet data
    $spreadsheet->setActiveSheetIndex(0);

    // Generate nama file
    $filename = "laporan_pemasukan";
    if ($filter_tanggal_awal && $filter_tanggal_akhir) {
        if ($filter_tanggal_awal === $filter_tanggal_akhir) {
            if (preg_match('/^\d{4}-\d{2}$/', $filter_tanggal_awal)) {
                $filename .= "_" . str_replace('-', '', $filter_tanggal_awal);
            } elseif (preg_match('/^\d{4}$/', $filter_tanggal_awal)) {
                $filename .= "_" . $filter_tanggal_awal;
            } else {
                $filename .= "_" . str_replace('-', '', $tanggal_awal_parsed);
            }
        } else {
            $filename .= "_" . str_replace('-', '', $tanggal_awal_parsed) . "_" . str_replace('-', '', $tanggal_akhir_parsed);
        }
    }
    if ($filter_cabang) {
        $filename .= "_" . preg_replace('/[^a-zA-Z0-9]/', '', $filter_cabang);
    }
    $filename .= "_sort_" . $sort_by . "_" . strtolower($sort_order);
    $filename .= "_" . date('Ymd_His') . ".xlsx";

    // Bersihkan output buffer dan set header
    ob_end_clean();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('Content-Transfer-Encoding: binary');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');

    // Tulis ke output
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo "Error generating Excel file: " . htmlspecialchars($e->getMessage());
    error_log("Excel Export Error (Pemasukan): " . $e->getMessage() . " - " . (isset($query) ? $query : 'No query'));
    exit;
} catch (PDOException $e) {
    ob_end_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo "Database Error: " . htmlspecialchars($e->getMessage()) . "<br>Query: " . htmlspecialchars($query);
    error_log("Database Error in Excel Export (Pemasukan): " . $e->getMessage() . " - Query: " . $query);
    exit;
}

exit;
?>
