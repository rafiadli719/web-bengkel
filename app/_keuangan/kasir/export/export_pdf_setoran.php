<?php
// Sumber: web_kasir/export_pdf_setoran.php — cetak PDF setoran bank/keuangan.
// Gerbang asli role==='super_admin' -> kasir_approve (Task 10: ADM+KEU).
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Jakarta');

// Include required files
require_once __DIR__ . '/../../../../vendor/autoload.php';

require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_approve');

$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$type = $_GET['type'] ?? '';
$rekening_filter = $_GET['rekening_filter'] ?? 'all';
$tanggal_awal = $_GET['tanggal_setor_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_setor_akhir'] ?? '';
$cabang = $_GET['cabang'] ?? 'all';

function formatRupiah($angka) {
    if ($angka === null || $angka === '') {
        return 'Rp 0';
    }
    return 'Rp ' . number_format(floatval($angka), 0, ',', '.');
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('FIT MOTOR - Keuangan Pusat');
$pdf->SetAuthor($nama_karyawan_aktif ?? 'System');
$pdf->SetTitle('Laporan Setoran Bank');
$pdf->SetSubject('Export Data Setoran Bank');

// Set margins
$pdf->SetMargins(15, 27, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Set font
$pdf->SetFont('helvetica', '', 10);

$data = [];
$filename = '';
$title = '';
$bank_detail = null;

if ($type == 'bank_history') {
    // Bank history query - Same as in setoran_bank_rekap.php
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
                        ) THEN 1 ELSE 0 END) as total_closing_transactions
        FROM setoran_ke_bank_closing_kasir sb
        JOIN setoran_ke_bank_detail_closing_kasir sbd ON sb.id = sbd.setoran_ke_bank_id
        JOIN setoran_keuangan_closing_kasir sk ON sbd.setoran_keuangan_id = sk.id
        JOIN tbcabang c ON sk.kode_cabang = c.cabang_ref_kode
        LEFT JOIN tbuser u ON sb.created_by = u.kode_karyawan
        LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
        WHERE 1=1";

    $params = [];

    // Apply filters
    if ($tanggal_awal && $tanggal_akhir) {
        $sql_setoran .= " AND sb.tanggal_setoran BETWEEN ? AND ?";
        $params[] = $tanggal_awal;
        $params[] = $tanggal_akhir;
    }

    if ($cabang !== 'all') {
        $sql_setoran .= " AND sk.nama_cabang = ?";
        $params[] = $cabang;
    }

    // Add rekening filter
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
            $sql_setoran .= " AND (" . implode(' OR ', $rekening_conditions) . ")";
        }
    }

    $sql_setoran .= " GROUP BY sb.id ORDER BY sb.tanggal_setoran DESC";

    $stmt_setoran = $pdo->prepare($sql_setoran);
    $stmt_setoran->execute($params);
    $data = $stmt_setoran->fetchAll(PDO::FETCH_ASSOC);
    
    $title = 'RIWAYAT SETORAN KE BANK';
    $filename = 'Riwayat_Setoran_Bank_' . date('Y-m-d_H-i-s') . '.pdf';

} elseif ($type == 'bank_detail') {
    // Bank detail export
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
    
    // Get detail transactions
    $sql_all_detail = "SELECT 
                            c.nama_cabang,
                            sk.kode_setoran,
                            sk.tanggal_setoran,
                            kt.kode_transaksi,
                            kt.tanggal_transaksi,
                            kt.tanggal_closing,
                            kt.setoran_real,
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
                       JOIN tbcabang c ON sk.kode_cabang = c.cabang_ref_kode
                       LEFT JOIN kasir_transactions_closing_kasir kt ON sk.kode_setoran = kt.kode_setoran
                       WHERE sbd.setoran_ke_bank_id = ?
                       ORDER BY sk.tanggal_setoran, kt.tanggal_closing DESC, kt.tanggal_transaksi";
    $stmt_all = $pdo->prepare($sql_all_detail);
    $stmt_all->execute([$bank_id]);
    $data = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    
    $title = 'DETAIL SETORAN BANK - ' . $bank_detail['kode_setoran'];
    $filename = 'Detail_Setoran_Bank_' . $bank_detail['kode_setoran'] . '_' . date('Y-m-d_H-i-s') . '.pdf';
    
} else {
    die('Invalid export type');
}

// Custom header and footer
class MYPDF extends TCPDF {
    public function Header() {
        // Company logo and header
        $this->SetFont('helvetica', 'B', 20);
        $this->SetTextColor(0, 123, 255);
        $this->Cell(0, 15, 'FIT MOTOR', 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(10);
        
        $this->SetFont('helvetica', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, $GLOBALS['pdf_title'], 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(8);
        
        $this->SetFont('helvetica', '', 10);
        $this->SetTextColor(100, 100, 100);
        $info_text = 'Generated: ' . date('d/m/Y H:i:s') . ' | By: ' . ($nama_karyawan_aktif ?? 'System');
        $this->Cell(0, 5, $info_text, 0, false, 'C', 0, '', 0, false, 'M', 'M');
        $this->Ln(5);
        
        // Add border line
        $this->SetDrawColor(0, 123, 255);
        $this->SetLineWidth(0.8);
        $this->Line(15, $this->GetY(), 195, $this->GetY());
        $this->Ln(5);
    }
    
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'FIT MOTOR - Sistem Keuangan | Halaman ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// Create custom PDF instance
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$GLOBALS['pdf_title'] = $title;

// Set document information
$pdf->SetCreator('FIT MOTOR - Keuangan Pusat');
$pdf->SetAuthor($nama_karyawan_aktif ?? 'System');
$pdf->SetTitle($title);
$pdf->SetSubject('Export Data Setoran Bank');

// Set margins
$pdf->SetMargins(15, 40, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Add a page
$pdf->AddPage();

if ($type == 'bank_history') {
    // Filter information
    if ($tanggal_awal && $tanggal_akhir || $cabang !== 'all') {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(0, 123, 255);
        $pdf->Cell(0, 8, 'Filter yang Diterapkan:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        if ($tanggal_awal && $tanggal_akhir) {
            $pdf->Cell(0, 6, '• Periode: ' . date('d/m/Y', strtotime($tanggal_awal)) . ' s/d ' . date('d/m/Y', strtotime($tanggal_akhir)), 0, 1, 'L');
        }
        if ($cabang !== 'all') {
            $pdf->Cell(0, 6, '• Cabang: ' . $cabang, 0, 1, 'L');
        }
        $pdf->Ln(5);
    }
    
    // Bank History Table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(0, 123, 255);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(0, 123, 255);
    
    $pdf->Cell(25, 8, 'Tgl Penyetor', 1, 0, 'C', 1);
    $pdf->Cell(35, 8, 'Kode Setoran', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Cabang', 1, 0, 'C', 1);
    $pdf->Cell(40, 8, 'Rekening Tujuan', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Tgl Setor Bank', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'Total Setoran', 1, 1, 'C', 1);
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(248, 249, 250);
    
    $grand_total = 0;
    $fill = 0;
    
    if ($data) {
        foreach ($data as $row) {
            $grand_total += $row['total_setoran'];
            $tanggal_penyetor = !empty($row['tanggal_setoran']) ? date('d/m/Y', strtotime($row['tanggal_setoran'])) : '-';
            $tanggal_setor_bank = !empty($row['tanggal_setor']) ? date('d/m/Y', strtotime($row['tanggal_setor'])) : '-';
            
            $pdf->Cell(25, 7, $tanggal_penyetor, 1, 0, 'C', $fill);
            $pdf->Cell(35, 7, substr($row['kode_setoran'], 0, 16) . (strlen($row['kode_setoran']) > 16 ? '...' : ''), 1, 0, 'L', $fill);
            $pdf->Cell(30, 7, substr($row['cabang_names'], 0, 13) . (strlen($row['cabang_names']) > 13 ? '...' : ''), 1, 0, 'L', $fill);
            $pdf->Cell(40, 7, substr($row['rekening_tujuan'], 0, 18) . (strlen($row['rekening_tujuan']) > 18 ? '...' : ''), 1, 0, 'L', $fill);
            $pdf->Cell(25, 7, $tanggal_setor_bank, 1, 0, 'C', $fill);
            $pdf->Cell(25, 7, formatRupiah($row['total_setoran']), 1, 1, 'R', $fill);
            
            $fill = 1 - $fill;
        }
        
        // Total row
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(0, 123, 255);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(155, 8, 'TOTAL KESELURUHAN', 1, 0, 'C', 1);
        $pdf->Cell(25, 8, formatRupiah($grand_total), 1, 1, 'R', 1);
        
        // Summary
        $pdf->Ln(8);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(0, 123, 255);
        $pdf->Cell(0, 8, 'Ringkasan:', 0, 1, 'L');
        
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 6, '• Total Paket Setoran: ' . count($data) . ' paket', 0, 1, 'L');
        $pdf->Cell(0, 6, '• Total Nilai: ' . formatRupiah($grand_total), 0, 1, 'L');
    } else {
        $pdf->Cell(180, 15, 'Tidak ada data ditemukan', 1, 1, 'C', 0);
    }

} elseif ($type == 'bank_detail' && $bank_detail) {
    // Bank Detail Information
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(0, 123, 255);
    $pdf->Cell(0, 8, 'Informasi Setoran Bank', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(248, 249, 250);
    
    // Create info table
    $info_data = [
        ['Kode Setoran Bank:', $bank_detail['kode_setoran']],
        ['Tanggal ke Penyetor:', date('d/m/Y', strtotime($bank_detail['tanggal_setoran']))],
        ['Tanggal Setor ke Bank:', !empty($bank_detail['tanggal_setor']) ? date('d/m/Y', strtotime($bank_detail['tanggal_setor'])) : '-'],
        ['Rekening Tujuan:', $bank_detail['rekening_tujuan']],
        ['Total Setoran:', formatRupiah($bank_detail['total_setoran'])],
        ['Disetor Oleh:', $bank_detail['created_by_name']],
        ['Jumlah Transaksi:', count($data) . ' transaksi']
    ];
    
    foreach ($info_data as $info) {
        $pdf->Cell(60, 6, $info[0], 1, 0, 'L', 1);
        $pdf->Cell(120, 6, $info[1], 1, 1, 'L', 0);
    }
    
    $pdf->Ln(8);
    
    // Detail Transactions Table
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetTextColor(0, 123, 255);
    $pdf->Cell(0, 8, 'Detail Transaksi:', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(0, 123, 255);
    $pdf->SetTextColor(255, 255, 255);
    
    // Detail table headers - Optimized column widths
    $pdf->Cell(18, 8, 'Cabang', 1, 0, 'C', 1);
    $pdf->Cell(32, 8, 'Kode Setoran', 1, 0, 'C', 1);
    $pdf->Cell(20, 8, 'Tgl Setoran', 1, 0, 'C', 1);
    $pdf->Cell(38, 8, 'Kode Transaksi', 1, 0, 'C', 1);
    $pdf->Cell(20, 8, 'Tgl Closing', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Nominal', 1, 0, 'C', 1);
    $pdf->Cell(22, 8, 'Jenis', 1, 1, 'C', 1);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(248, 249, 250);
    
    $total_detail = 0;
    $fill = 0;
    
    if ($data) {
        foreach ($data as $detail) {
            $total_detail += (float)$detail['setoran_real'];
            
            $pdf->Cell(18, 6, substr($detail['nama_cabang'], 0, 7), 1, 0, 'L', $fill);
            $pdf->Cell(32, 6, substr($detail['kode_setoran'], 0, 14), 1, 0, 'L', $fill);
            // PERBAIKAN: Gunakan tanggal individual transaksi, bukan tanggal setoran bank yang sama
            $tanggal_individual = isset($detail['tanggal_transaksi']) && $detail['tanggal_transaksi'] ? 
                                 $detail['tanggal_transaksi'] : $detail['tanggal_setoran'];
            $pdf->Cell(20, 6, date('d/m/Y', strtotime($tanggal_individual)), 1, 0, 'C', $fill);
            $pdf->Cell(38, 6, substr($detail['kode_transaksi'] ?? 'N/A', 0, 17), 1, 0, 'L', $fill);
            
            $tgl_closing = isset($detail['tanggal_closing']) && $detail['tanggal_closing'] ? 
                          date('d/m/Y', strtotime($detail['tanggal_closing'])) : 
                          ($detail['tanggal_transaksi'] ? date('d/m/Y', strtotime($detail['tanggal_transaksi'])) : '-');
            $pdf->Cell(20, 6, $tgl_closing, 1, 0, 'C', $fill);
            
            $pdf->Cell(30, 6, formatRupiah($detail['setoran_real'] ?? 0), 1, 0, 'R', $fill);
            
            $jenis = $detail['jenis_transaksi'] === 'DARI CLOSING' ? 'CLO' : 'BIA';
            $pdf->Cell(22, 6, $jenis, 1, 1, 'C', $fill);
            
            $fill = 1 - $fill;
        }
        
        // Total row - Fixed column alignment
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetFillColor(0, 123, 255);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell(128, 7, 'TOTAL KESELURUHAN', 1, 0, 'C', 1);
        $pdf->Cell(30, 7, formatRupiah($total_detail), 1, 0, 'R', 1);
        $pdf->Cell(22, 7, '', 1, 1, 'C', 1);
    } else {
        $pdf->Cell(180, 15, 'Tidak ada detail transaksi ditemukan', 1, 1, 'C', 0);
    }
}

// Output PDF
$pdf->Output($filename, 'D'); // 'D' forces download
exit;
?>
