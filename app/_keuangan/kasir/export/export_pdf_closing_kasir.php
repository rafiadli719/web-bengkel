<?php
// Sumber: web_kasir/export_pdf_closing_kasir.php — cetak PDF closing kasir.
// Gerbang asli role IN (kasir,admin,super_admin) -> kasir_operate (Task 10,
// paling longgar - sama pola view_transaksi.php).
require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_operate');

// Include TCPDF library
require_once __DIR__ . '/../../../../vendor/tecnickcom/tcpdf/tcpdf.php';

// Koneksi database
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ambil kode transaksi dari parameter
$kode_transaksi = $_GET['kode_transaksi'] ?? '';
if (empty($kode_transaksi)) {
    die("Kode transaksi tidak ditemukan.");
}

// Ambil data dasar transaksi (hanya untuk info cabang dan kasir)
$sql_transaksi = "
    SELECT 
        kt.kode_transaksi,
        kt.nama_cabang,
        kt.kode_cabang,
        kt.tanggal_transaksi,
        kt.tanggal_closing,
        kt.jam_closing,
        kt.status,
        u.nama_lengkap AS nama_karyawan
    FROM kasir_transactions_closing_kasir kt
    LEFT JOIN tbuser u ON kt.kode_karyawan = u.kode_karyawan
    WHERE kt.kode_transaksi = :kode_transaksi
";

$stmt = $pdo->prepare($sql_transaksi);
$stmt->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt->execute();
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    die("Data transaksi tidak ditemukan.");
}

// --- PERBAIKAN: Hitung ulang semua data secara REAL-TIME (Live) ---
// Agar data di file PDF selalu sama dengan di layar closing

// 1. Ambil Kas Akhir (Terbaru)
$sql_kas_akhir = "SELECT total_nilai FROM kas_akhir WHERE kode_transaksi = :kode_transaksi ORDER BY tanggal DESC, waktu DESC LIMIT 1";
$stmt_kas_akhir = $pdo->prepare($sql_kas_akhir);
$stmt_kas_akhir->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_kas_akhir->execute();
$kas_akhir = $stmt_kas_akhir->fetchColumn() ?? 0;

// 2. Ambil Kas Awal (Terbaru)
$sql_kas_awal = "SELECT total_nilai FROM kas_awal WHERE kode_transaksi = :kode_transaksi ORDER BY tanggal DESC, waktu DESC LIMIT 1";
$stmt_kas_awal = $pdo->prepare($sql_kas_awal);
$stmt_kas_awal->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_kas_awal->execute();
$kas_awal = $stmt_kas_awal->fetchColumn() ?? 0;

// 3. Hitung Setoran Real
$setoran_real = $kas_akhir - $kas_awal;

// 4. Ambil Total Penjualan
$sql_penjualan = "SELECT SUM(jumlah_penjualan) as total_penjualan FROM data_penjualan_closing_kasir WHERE kode_transaksi = :kode_transaksi";
$stmt_penjualan = $pdo->prepare($sql_penjualan);
$stmt_penjualan->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_penjualan->execute();
$data_penjualan_closing_kasir = $stmt_penjualan->fetchColumn() ?? 0;

// 5. Ambil Total Servis
$sql_servis = "SELECT SUM(jumlah_servis) as total_servis FROM data_servis_closing_kasir WHERE kode_transaksi = :kode_transaksi";
$stmt_servis = $pdo->prepare($sql_servis);
$stmt_servis->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_servis->execute();
$data_servis_closing_kasir = $stmt_servis->fetchColumn() ?? 0;

// 6. Hitung Omset
$omset = $data_penjualan_closing_kasir + $data_servis_closing_kasir;

// 7. Ambil Pengeluaran
$sql_pengeluaran = "SELECT SUM(jumlah) as total_pengeluaran FROM pengeluaran_kasir_closing_kasir WHERE kode_transaksi = :kode_transaksi";
$stmt_pengeluaran = $pdo->prepare($sql_pengeluaran);
$stmt_pengeluaran->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_pengeluaran->execute();
$pengeluaran_dari_kasir = $stmt_pengeluaran->fetchColumn() ?? 0;

// 8. Ambil Pemasukan
$sql_pemasukan = "SELECT SUM(jumlah) as total_pemasukan FROM pemasukan_kasir_closing_kasir WHERE kode_transaksi = :kode_transaksi";
$stmt_pemasukan = $pdo->prepare($sql_pemasukan);
$stmt_pemasukan->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_pemasukan->execute();
$uang_masuk_ke_kasir = $stmt_pemasukan->fetchColumn() ?? 0;

// 9. Hitung Data Setoran (Teoritis)
$setoran_data = ($omset - $pengeluaran_dari_kasir) + $uang_masuk_ke_kasir;

// 10. Hitung Selisih
$selisih_setoran = $setoran_real - $setoran_data;

// Update array $transaksi dengan data terbaru hasil perhitungan
$transaksi['kas_awal'] = $kas_awal;
$transaksi['kas_akhir'] = $kas_akhir;
$transaksi['setoran_real'] = $setoran_real;
$transaksi['total_penjualan'] = $data_penjualan_closing_kasir;
$transaksi['total_servis'] = $data_servis_closing_kasir;
$transaksi['omset'] = $omset;
$transaksi['total_pengeluaran'] = $pengeluaran_dari_kasir;
$transaksi['total_pemasukan'] = $uang_masuk_ke_kasir;
$transaksi['data_setoran'] = $setoran_data;
$transaksi['selisih_setoran'] = $selisih_setoran;

// Format functions
function formatNominalSederhana($angka) {
    return number_format($angka, 0, ',', '.');
}

function formatTanggalIndonesia($tanggal) {
    if (empty($tanggal)) return date('d F Y');
    
    $bulan = [
        1 => 'JANUARI', 2 => 'FEBRUARI', 3 => 'MARET', 4 => 'APRIL',
        5 => 'MEI', 6 => 'JUNI', 7 => 'JULI', 8 => 'AGUSTUS',
        9 => 'SEPTEMBER', 10 => 'OKTOBER', 11 => 'NOVEMBER', 12 => 'DESEMBER'
    ];
    
    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bulan_num = date('n', $timestamp);
    $tahun = date('Y', $timestamp);
    
    return $hari . ' ' . $bulan[$bulan_num] . ' ' . $tahun;
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('FIT MOTOR - Sistem Kasir');
$pdf->SetAuthor($transaksi['nama_karyawan'] ?? 'System');
$pdf->SetTitle('Closing Kasir - ' . $transaksi['kode_transaksi']);
$pdf->SetSubject('Data Closing Kasir');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(20, 20, 20);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 20);

// Set font
$pdf->SetFont('helvetica', '', 12);

// Add a page
$pdf->AddPage();

// Build HTML content
$html = '<style>
    body { font-family: helvetica; }
    .center { text-align: center; }
    .bold { font-weight: bold; }
    .large { font-size: 22px; }
    .medium { font-size: 18px; }
    .small { font-size: 11px; }
    .info-box { padding: 10px; margin: 20px 0; }
    .warning { background-color: #fff3cd; border: 2px solid #ffc107; padding: 15px; text-align: center; color: #856404; }
    .danger { background-color: #f8d7da; border: 2px solid #dc3545; padding: 15px; text-align: center; color: #721c24; }
</style>';

// Nama Cabang
$html .= '<h1 class="center bold large">' . strtoupper($transaksi['nama_cabang'] ?: 'UNKNOWN CABANG') . '</h1>';
$html .= '<br><br>';

// Setoran Real
$html .= '<h2 class="center bold medium">SETORAN REAL ' . formatNominalSederhana($transaksi['setoran_real']) . '</h2>';
$html .= '<br><br>';

// Tanggal
$html .= '<h2 class="center bold medium">TANGGAL ' . formatTanggalIndonesia($transaksi['tanggal_transaksi']) . '</h2>';
$html .= '<br><br><br>';

// Informasi Tambahan
$html .= '<div class="center small">';
$html .= '<p><strong>Kode Transaksi:</strong> ' . htmlspecialchars($transaksi['kode_transaksi']) . '</p>';
$html .= '<p><strong>Kasir:</strong> ' . htmlspecialchars($transaksi['nama_karyawan'] ?: 'Unknown') . '</p>';

if (!empty($transaksi['tanggal_closing'])) {
    $html .= '<p><strong>Tanggal Closing:</strong> ' . formatTanggalIndonesia($transaksi['tanggal_closing']) . '</p>';
}
if (!empty($transaksi['jam_closing'])) {
    $html .= '<p><strong>Jam Closing:</strong> ' . date('H:i:s', strtotime($transaksi['jam_closing'])) . '</p>';
}

$html .= '</div>';
$html .= '<br><br>';

// Peringatan Selisih (jika ada)
$selisih = $transaksi['selisih_setoran'];
if ($selisih != 0) {
    $class = ($selisih > 0) ? 'warning' : 'danger';
    $statusText = ($selisih > 0) 
        ? '<strong>⚠ PERHATIAN: LEBIH Rp ' . formatNominalSederhana($selisih) . '</strong>'
        : '<strong>⚠ PERHATIAN: KURANG Rp ' . formatNominalSederhana(abs($selisih)) . '</strong>';
    
    $html .= '<div class="' . $class . '">' . $statusText . '</div>';
    $html .= '<br>';
}

// Footer
$html .= '<br><br><br>';
$html .= '<div class="center small">';
$html .= '<p><em>Dicetak pada: ' . date('d/m/Y H:i:s') . '</em></p>';
$html .= '<p><em>FIT MOTOR - Sistem Kasir</em></p>';
$html .= '</div>';

// Output the HTML content
$pdf->writeHTML($html, true, false, true, false, '');

// Generate filename
$filename = 'Closing_Kasir_' . $transaksi['kode_transaksi'] . '_' . date('Ymd_His') . '.pdf';

// Close and output PDF document
$pdf->Output($filename, 'D'); // 'D' = force download
?>
