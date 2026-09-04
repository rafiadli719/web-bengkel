<?php
// Sumber: web_kasir/export_excel_omset.php — export Excel rekap omset.
// Gerbang asli role IN (admin,super_admin) -> kasir_approve (Task 10).
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

// Load PHPSpreadsheet
require_once __DIR__ . '/../../../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;

$is_super_admin = ($_SESSION['_kode_posisi'] ?? '') === 'ADM';
$is_admin = ($_SESSION['_kode_posisi'] ?? '') === 'KEU';
$username = $nama_karyawan_aktif;
$role = $legacy_session_kasir['role'] ?? 'User';

// Koneksi ke database
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Set variabel untuk filter
$tanggal_awal = $_GET['tanggal_awal'] ?? null;
$tanggal_akhir = $_GET['tanggal_akhir'] ?? null;
$cabang = $_GET['cabang'] ?? null;
$sort_by = $_GET['sort_by'] ?? 'tanggal_transaksi';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Validasi sort_by untuk keamanan
$allowed_sort_columns = [
    'tanggal_transaksi', 'nama_cabang', 'kode_transaksi', 
    'omset_penjualan', 'omset_servis_final', 'total_omset'
];

if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'tanggal_transaksi';
}

if (!in_array(strtoupper($sort_order), ['ASC', 'DESC'])) {
    $sort_order = 'DESC';
}

// Query yang diperbaiki untuk menghindari duplikasi
$query = "
    SELECT DISTINCT
        kt.id,
        kt.kode_transaksi,
        kt.tanggal_transaksi,
        kt.tanggal_closing,
        kt.jam_closing,
        kt.nama_cabang,
        kt.kode_karyawan,
        kt.status,
        COALESCE(
            (SELECT SUM(dp.jumlah_penjualan) 
             FROM data_penjualan_closing_kasir dp 
             WHERE dp.kode_transaksi = kt.kode_transaksi), 0
        ) as omset_penjualan,
        COALESCE(
            (SELECT SUM(ds.jumlah_servis) 
             FROM data_servis_closing_kasir ds 
             WHERE ds.kode_transaksi = kt.kode_transaksi), 0
        ) as omset_servis_cs,
        COALESCE(kt.selisih_setoran, 0) as selisih_closing,
        (COALESCE(
            (SELECT SUM(ds.jumlah_servis) 
             FROM data_servis_closing_kasir ds 
             WHERE ds.kode_transaksi = kt.kode_transaksi), 0
        ) + COALESCE(kt.selisih_setoran, 0)) as omset_servis_final,
        (COALESCE(
            (SELECT SUM(dp.jumlah_penjualan) 
             FROM data_penjualan_closing_kasir dp 
             WHERE dp.kode_transaksi = kt.kode_transaksi), 0
        ) + COALESCE(
            (SELECT SUM(ds.jumlah_servis) 
             FROM data_servis_closing_kasir ds 
             WHERE ds.kode_transaksi = kt.kode_transaksi), 0
        ) + COALESCE(kt.selisih_setoran, 0)) as total_omset
    FROM kasir_transactions_closing_kasir kt
    WHERE 1 = 1
";

// Tambahkan filter
$params = [];
if ($tanggal_awal && $tanggal_akhir) {
    $query .= " AND kt.tanggal_transaksi BETWEEN :tanggal_awal AND :tanggal_akhir";
    $params[':tanggal_awal'] = $tanggal_awal;
    $params[':tanggal_akhir'] = $tanggal_akhir;
}
if ($cabang) {
    $query .= " AND kt.nama_cabang = :cabang";
    $params[':cabang'] = $cabang;
}

// Tambahkan sorting dengan mapping yang benar
$sort_mapping = [
    'tanggal_transaksi' => 'kt.tanggal_transaksi',
    'nama_cabang' => 'kt.nama_cabang',
    'kode_transaksi' => 'kt.kode_transaksi',
    'omset_penjualan' => 'omset_penjualan',
    'omset_servis_final' => 'omset_servis_final', 
    'total_omset' => 'total_omset'
];

$actual_sort_column = $sort_mapping[$sort_by] ?? 'kt.tanggal_transaksi';
$query .= " ORDER BY {$actual_sort_column} " . strtoupper($sort_order);

if ($sort_by !== 'tanggal_transaksi') {
    $query .= ", kt.tanggal_transaksi " . strtoupper($sort_order);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$omset_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Log jumlah data untuk memastikan tidak duplikat
error_log("Total records fetched: " . count($omset_data));

// Calculate statistics
$total_records = count($omset_data);
$total_omset_penjualan = array_sum(array_column($omset_data, 'omset_penjualan'));
$total_omset_servis = array_sum(array_column($omset_data, 'omset_servis_final'));
$total_omset_keseluruhan = array_sum(array_column($omset_data, 'total_omset'));
$total_selisih_closing = array_sum(array_column($omset_data, 'selisih_closing'));

// Generate filter description for filename and header
$filter_desc = [];
if ($tanggal_awal && $tanggal_akhir) {
    $filter_desc[] = "Periode " . date('d-m-Y', strtotime($tanggal_awal)) . " s/d " . date('d-m-Y', strtotime($tanggal_akhir));
}
if ($cabang) {
    $filter_desc[] = "Cabang " . ucfirst($cabang);
}

$filter_text = !empty($filter_desc) ? implode(", ", $filter_desc) : "Semua Data";

// Generate filename
$filename = "Detail_Omset_" . date('Y-m-d_H-i-s');
if ($tanggal_awal && $tanggal_akhir) {
    $filename .= "_" . date('dmY', strtotime($tanggal_awal)) . "-" . date('dmY', strtotime($tanggal_akhir));
}
if ($cabang) {
    $filename .= "_" . str_replace(' ', '_', ucfirst($cabang));
}

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Detail Omset');

// Mulai dari baris 1
$currentRow = 1;

// Header Informasi
$sheet->setCellValue('A' . $currentRow, 'LAPORAN DETAIL OMSET PENJUALAN & SERVIS');
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4CAF50');
$sheet->getStyle('A' . $currentRow)->getFont()->getColor()->setRGB('FFFFFF');
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'PT. FITMOTOR INDONESIA - SISTEM MANAJEMEN KEUANGAN');
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8F5E8');
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Filter: ' . $filter_text);
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Diunduh oleh: ' . $username . ' (' . ucfirst($role) . ') pada ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Sorting: ' . ucfirst($sort_by) . ' ' . $sort_order . ' | Total Data: ' . number_format($total_records) . ' transaksi');
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$currentRow++;

// Baris kosong
$currentRow++;

// Summary Statistics
$sheet->setCellValue('A' . $currentRow, 'RINGKASAN DATA OMSET');
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getFont()->setBold(true);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D4EDDA');
$currentRow++;

// Summary row 1
$sheet->setCellValue('A' . $currentRow, 'Total Transaksi');
$sheet->mergeCells('A' . $currentRow . ':C' . $currentRow);
$sheet->setCellValue('D' . $currentRow, number_format($total_records));
$sheet->setCellValue('E' . $currentRow, 'Total Omset Penjualan');
$sheet->mergeCells('E' . $currentRow . ':G' . $currentRow);
$sheet->setCellValue('H' . $currentRow, 'Rp ' . number_format($total_omset_penjualan, 0, ',', '.'));
$sheet->setCellValue('I' . $currentRow, 'Total Omset Servis');
$sheet->mergeCells('I' . $currentRow . ':K' . $currentRow);
$sheet->setCellValue('L' . $currentRow, 'Rp ' . number_format($total_omset_servis, 0, ',', '.'));
$currentRow++;

// Summary row 2
$sheet->setCellValue('A' . $currentRow, 'Total Selisih Closing');
$sheet->mergeCells('A' . $currentRow . ':C' . $currentRow);
$sheet->setCellValue('D' . $currentRow, 'Rp ' . number_format($total_selisih_closing, 0, ',', '.'));
$sheet->setCellValue('E' . $currentRow, 'Total Omset Keseluruhan');
$sheet->mergeCells('E' . $currentRow . ':G' . $currentRow);
$sheet->setCellValue('H' . $currentRow, 'Rp ' . number_format($total_omset_keseluruhan, 0, ',', '.'));
$sheet->getStyle('H' . $currentRow)->getFont()->setBold(true);
$currentRow++;

// Baris kosong
$currentRow++;

// Header Tabel - Diperbaiki dengan kolom tanggal closing terpisah
$headers = [
    'A' => 'No',
    'B' => 'Tanggal Transaksi',
    'C' => 'Tanggal Closing', 
    'D' => 'Jam Closing',
    'E' => 'Kode Transaksi',
    'F' => 'Nama Cabang',
    'G' => 'Kode Karyawan',
    'H' => 'Omset Penjualan (Rp)',
    'I' => 'Omset Servis CS (Rp)',
    'J' => 'Selisih Closing (Rp)',
    'K' => 'Omset Servis Final (Rp)',
    'L' => 'Total Omset (Rp)',
    'M' => 'Status'
];

foreach ($headers as $col => $header) {
    $sheet->setCellValue($col . $currentRow, $header);
    $sheet->getStyle($col . $currentRow)->getFont()->setBold(true);
    $sheet->getStyle($col . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($col . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4CAF50');
    $sheet->getStyle($col . $currentRow)->getFont()->getColor()->setRGB('FFFFFF');
}
$currentRow++;

// Data Rows
if (count($omset_data) > 0) {
    foreach ($omset_data as $index => $data) {
        $sheet->setCellValue('A' . $currentRow, $index + 1);
        $sheet->setCellValue('B' . $currentRow, date('d/m/Y', strtotime($data['tanggal_transaksi'])));
        $sheet->setCellValue('C' . $currentRow, $data['tanggal_closing'] ? date('d/m/Y', strtotime($data['tanggal_closing'])) : 'Belum Closing');
        $sheet->setCellValue('D' . $currentRow, $data['jam_closing'] ?? '-');
        $sheet->setCellValue('E' . $currentRow, $data['kode_transaksi']);
        $sheet->setCellValue('F' . $currentRow, ucfirst($data['nama_cabang']));
        $sheet->setCellValue('G' . $currentRow, $data['kode_karyawan'] ?? '-');
        $sheet->setCellValue('H' . $currentRow, $data['omset_penjualan']);
        $sheet->setCellValue('I' . $currentRow, $data['omset_servis_cs']);
        $sheet->setCellValue('J' . $currentRow, $data['selisih_closing']);
        $sheet->setCellValue('K' . $currentRow, $data['omset_servis_final']);
        $sheet->setCellValue('L' . $currentRow, $data['total_omset']);
        $sheet->setCellValue('M' . $currentRow, ucfirst(str_replace('_', ' ', $data['status'])));
        
        // Format currency columns
        $sheet->getStyle('H' . $currentRow . ':L' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
        
        // Alignment
        $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B' . $currentRow . ':D' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('M' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('H' . $currentRow . ':L' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        $currentRow++;
    }
    
    // Total Row
    $sheet->setCellValue('A' . $currentRow, 'TOTAL KESELURUHAN');
    $sheet->mergeCells('A' . $currentRow . ':G' . $currentRow);
    $sheet->setCellValue('H' . $currentRow, $total_omset_penjualan);
    $sheet->setCellValue('I' . $currentRow, array_sum(array_column($omset_data, 'omset_servis_cs')));
    $sheet->setCellValue('J' . $currentRow, $total_selisih_closing);
    $sheet->setCellValue('K' . $currentRow, $total_omset_servis);
    $sheet->setCellValue('L' . $currentRow, $total_omset_keseluruhan);
    $sheet->setCellValue('M' . $currentRow, number_format($total_records) . ' Transaksi');
    
    // Style total row
    $sheet->getStyle('A' . $currentRow . ':M' . $currentRow)->getFont()->setBold(true);
    $sheet->getStyle('A' . $currentRow . ':M' . $currentRow)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFF2CC');
    $sheet->getStyle('H' . $currentRow . ':L' . $currentRow)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('M' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('H' . $currentRow . ':L' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    
    $currentRow++;
} else {
    $sheet->setCellValue('A' . $currentRow, 'Tidak ada data untuk filter yang dipilih');
    $sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
    $sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $currentRow++;
}

// Footer Informasi
$currentRow++;
$keterangan = "KETERANGAN:\n" .
    "• Omset Penjualan: Data yang diisikan oleh CS dari transaksi penjualan\n" .
    "• Omset Servis CS: Data yang diisikan oleh CS dari transaksi servis\n" .
    "• Selisih Closing: Selisih yang terjadi saat proses closing kasir\n" .
    "• Omset Servis Final: Omset Servis CS + Selisih Closing\n" .
    "• Total Omset: Penjualan + Servis Final untuk perhitungan rugi laba\n" .
    "• Tanggal Closing: Tanggal transaksi ditutup/closing (berbeda dari tanggal transaksi)";

$sheet->setCellValue('A' . $currentRow, $keterangan);
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setWrapText(true);
$currentRow++;

$sheet->setCellValue('A' . $currentRow, 'Laporan ini dibuat secara otomatis oleh Sistem Manajemen Keuangan PT. Fitmotor Indonesia');
$sheet->mergeCells('A' . $currentRow . ':M' . $currentRow);
$sheet->getStyle('A' . $currentRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A' . $currentRow)->getFont()->setItalic(true);

// Auto resize columns
foreach (range('A', 'M') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

// Add borders to all data
$dataRange = 'A1:M' . $currentRow;
$sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Set headers untuk download Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

// Output file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>