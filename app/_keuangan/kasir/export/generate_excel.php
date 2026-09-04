<?php
// Sumber: web_kasir/generate_excel.php — export Excel detail closing transaksi.
// Source asli gak ada role-gate eksplisit (cuma cek session kode_karyawan) -
// tambah kasir_operate (Task 10, sama pola view_transaksi.php pasangannya).
require_once __DIR__ . '/../../../../vendor/autoload.php'; // Autoload PhpSpreadsheet via Composer

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../koneksi_kasir.php';
requirePermission($koneksi, $id_user_aktif, 'kasir_operate');
require_once __DIR__ . '/../closing_revision_helpers.php';

function formatRevisionExcelValue($value, string $field): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    if ($field === 'status') {
        return (string) $value;
    }

    if (is_numeric($value)) {
        return 'Rp' . number_format((float) $value, 0, ',', '.');
    }

    return (string) $value;
}

function formatRevisionExcelDetailValue($value, string $valueLabel): string
{
    if ($value === null || $value === '') {
        return '-';
    }

    if ($valueLabel === 'Jumlah Keping') {
        return (string) $value;
    }

    return formatRevisionExcelValue($value, '');
}

function applyRevisionExcelHeader($sheet, string $range): void
{
    $sheet->getStyle($range)->applyFromArray([
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4472C4'],
        ],
    ]);
}

function applyRevisionExcelStamp($sheet, array $transaction): void
{
    $stampText = '';
    $fontColor = 'DC3545';

    if (($transaction['status'] ?? '') === 'dibatalkan') {
        $stampText = 'DIBATALKAN';
    } elseif (!empty($transaction['revision_parent_kode'])) {
        $stampText = 'HASIL REVISI';
        $fontColor = '28A745';
    }

    if ($stampText === '') {
        return;
    }

    $sheet->mergeCells('F2:H4');
    $sheet->setCellValue('F2', $stampText);
    $sheet->getStyle('F2:H4')->applyFromArray([
        'font' => [
            'bold' => true,
            'size' => 20,
            'color' => ['rgb' => $fontColor],
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFFFFF'],
        ],
        'borders' => [
            'outline' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'D9D9D9'],
            ],
        ],
    ]);
}

// Initialize the PDO connection
$pdo = new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('DB_NAME') ?: 'fitmotor_dbbengkel'), getenv('DB_USER') ?: 'fitmotor_LOGIN', getenv('DB_PASS') ?: 'Sayalupa12');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pastikan kode_transaksi tersedia
$kode_transaksi = $_GET['kode_transaksi'] ?? null;
if (!$kode_transaksi) {
    die('Kode transaksi tidak ditemukan.');
}

// Mengambil user dan cabang dari RBAC fitmotor (Task 10/11)
$kode_karyawan = $kode_karyawan_aktif;
$username = $nama_karyawan_aktif;
$cabang = $nama_cabang_aktif;

// Ambil data transaksi dari database
$sql = "
    SELECT 
        kt.status,
        kt.revision_parent_kode,
        (SELECT SUM(jumlah_penjualan) FROM data_penjualan_closing_kasir WHERE kode_transaksi = :kode_transaksi) AS total_penjualan,
        (SELECT SUM(jumlah_servis) FROM data_servis_closing_kasir WHERE kode_transaksi = :kode_transaksi) AS total_servis,
        ka.total_nilai AS kas_awal,
        kcl.total_nilai AS kas_akhir,
        ka.tanggal AS kas_awal_date,
        ka.waktu AS kas_awal_time,
        kcl.tanggal AS kas_akhir_date,
        kcl.waktu AS kas_akhir_time
    FROM kasir_transactions_closing_kasir kt
    LEFT JOIN kas_awal ka ON ka.kode_transaksi = kt.kode_transaksi
    LEFT JOIN kas_akhir kcl ON kcl.kode_transaksi = kt.kode_transaksi
    WHERE kt.kode_transaksi = :kode_transaksi
";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt->execute();
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction || !userCanAccessTransaction($pdo, $legacy_session_kasir, $transaction)) {
    die('Transaksi tidak ditemukan atau Anda tidak memiliki akses.');
}

$total_penjualan = $transaction['total_penjualan'] ?? 0;
$total_servis = $transaction['total_servis'] ?? 0;
$kas_awal = $transaction['kas_awal'] ?? 0;
$kas_akhir = $transaction['kas_akhir'] ?? 0;
$total_omset = $total_penjualan + $total_servis;
$setoran_real = $kas_akhir - $kas_awal;

// Fetch pemasukan dan pengeluaran
$sql_pemasukan = "SELECT * FROM pemasukan_kasir_closing_kasir WHERE kode_transaksi = :kode_transaksi";
$stmt_pemasukan = $pdo->prepare($sql_pemasukan);
$stmt_pemasukan->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_pemasukan->execute();
$pemasukan_kasir_closing_kasir = $stmt_pemasukan->fetchAll(PDO::FETCH_ASSOC);

// Fetch pengeluaran data (with umur_pakai and kategori)
$sql_pengeluaran = "
    SELECT pk.*, pk.umur_pakai, ma.kategori 
    FROM pengeluaran_kasir_closing_kasir pk
    LEFT JOIN master_akun_closing_kasir ma ON pk.kode_akun = ma.kode_akun
    WHERE pk.kode_transaksi = :kode_transaksi";
$stmt_pengeluaran = $pdo->prepare($sql_pengeluaran);
$stmt_pengeluaran->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_pengeluaran->execute();
$pengeluaran_kasir_closing_kasir = $stmt_pengeluaran->fetchAll(PDO::FETCH_ASSOC);

// Total Pemasukan & Pengeluaran
$total_pemasukan = 0;
$total_pengeluaran = 0;

foreach ($pemasukan_kasir_closing_kasir as $pemasukan) {
    $total_pemasukan += $pemasukan['jumlah'];
}

foreach ($pengeluaran_kasir_closing_kasir as $pengeluaran) {
    $total_pengeluaran += $pengeluaran['jumlah'];
}

$data_setoran = $total_omset - $total_pengeluaran + $total_pemasukan;
$selisih_setoran = $setoran_real - $data_setoran;

// Buat objek Spreadsheet baru
$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set header laporan
$sheet->setCellValue('A1', 'Laporan Closing Kasir');
$sheet->setCellValue('A2', 'Nama User: ' . $username);
$sheet->setCellValue('A3', 'Cabang: ' . $cabang);
$sheet->setCellValue('A4', 'TANGGAL DAN JAM TRANSAKSI');
$sheet->setCellValue('A5', 'Tanggal Kas Awal: ' . date('d M Y', strtotime($transaction['kas_awal_date'])));
$sheet->setCellValue('A6', 'Jam Kas Awal: ' . date('H:i:s', strtotime($transaction['kas_awal_time'])));
$sheet->setCellValue('A7', 'Tanggal Kas Akhir: ' . date('d M Y', strtotime($transaction['kas_akhir_date'])));
$sheet->setCellValue('A8', 'Jam Kas Akhir: ' . date('H:i:s', strtotime($transaction['kas_akhir_time'])));
applyRevisionExcelStamp($sheet, $transaction);

// Tambahkan data Kas Awal
$sheet->setCellValue('A10', 'Data Kas Awal');
$sheet->setCellValue('A11', 'Nominal');
$sheet->setCellValue('B11', 'Keping');
$sheet->setCellValue('C11', 'Total Nilai');

// Ambil data detail kas_awal
$sql_kas_awal_detail = "
    SELECT nominal, jumlah_keping 
    FROM detail_kas_awal 
    WHERE kode_transaksi = :kode_transaksi
";
$stmt_kas_awal_detail = $pdo->prepare($sql_kas_awal_detail);
$stmt_kas_awal_detail->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_kas_awal_detail->execute();
$kas_awal_detail = $stmt_kas_awal_detail->fetchAll(PDO::FETCH_ASSOC);

$row = 12;
foreach ($kas_awal_detail as $kas) {
    $sheet->setCellValue('A' . $row, 'Rp' . number_format($kas['nominal'], 0, ',', '.'));
    $sheet->setCellValue('B' . $row, $kas['jumlah_keping']);
    $sheet->setCellValue('C' . $row, 'Rp' . number_format($kas['nominal'] * $kas['jumlah_keping'], 0, ',', '.'));
    $row++;
}

$sheet->setCellValue('A' . $row, 'Total Kas Awal');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($kas_awal, 0, ',', '.'));

// Tambahkan data Kas Akhir
$row += 2;
$sheet->setCellValue('A' . $row, 'Data Kas Akhir');
$row++;
$sheet->setCellValue('A' . $row, 'Nominal');
$sheet->setCellValue('B' . $row, 'Keping');
$sheet->setCellValue('C' . $row, 'Total Nilai');

$sql_kas_akhir_detail = "
    SELECT nominal, jumlah_keping 
    FROM detail_kas_akhir 
    WHERE kode_transaksi = :kode_transaksi
";
$stmt_kas_akhir_detail = $pdo->prepare($sql_kas_akhir_detail);
$stmt_kas_akhir_detail->bindParam(':kode_transaksi', $kode_transaksi, PDO::PARAM_STR);
$stmt_kas_akhir_detail->execute();
$kas_akhir_detail = $stmt_kas_akhir_detail->fetchAll(PDO::FETCH_ASSOC);

$row++;
foreach ($kas_akhir_detail as $kas) {
    $sheet->setCellValue('A' . $row, 'Rp' . number_format($kas['nominal'], 0, ',', '.'));
    $sheet->setCellValue('B' . $row, $kas['jumlah_keping']);
    $sheet->setCellValue('C' . $row, 'Rp' . number_format($kas['nominal'] * $kas['jumlah_keping'], 0, ',', '.'));
    $row++;
}

$sheet->setCellValue('A' . $row, 'Total Kas Akhir');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($kas_akhir, 0, ',', '.'));

// Tambahkan Data Sistem Aplikasi
$row += 2;
$sheet->setCellValue('A' . $row, 'Data Sistem Aplikasi');
$row++;
$sheet->setCellValue('A' . $row, 'Data Penjualan');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($total_penjualan, 0, ',', '.'));
$row++;
$sheet->setCellValue('A' . $row, 'Data Servis');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($total_servis, 0, ',', '.'));
$row++;
$sheet->setCellValue('A' . $row, 'Omset');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($total_omset, 0, ',', '.'));
$row++;
$sheet->setCellValue('A' . $row, 'Pengeluaran dari Kasir');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($total_pengeluaran, 0, ',', '.'));
$row++;
$sheet->setCellValue('A' . $row, 'Uang Masuk ke Kasir');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($total_pemasukan, 0, ',', '.'));
$row++;
$sheet->setCellValue('A' . $row, 'Data Setoran');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($data_setoran, 0, ',', '.'));
$row++;
$sheet->setCellValue('A' . $row, 'Selisih Setoran (REAL-DATA)');
$sheet->setCellValue('C' . $row, 'Rp' . number_format($selisih_setoran, 0, ',', '.'));

// Pemasukan Kasir
$row += 2;
$sheet->setCellValue('A' . $row, 'View Pemasukan Kasir');
$row++;
$sheet->setCellValue('A' . $row, 'Kode Transaksi');
$sheet->setCellValue('B' . $row, 'Kode Akun');
$sheet->setCellValue('C' . $row, 'Jumlah (Rp)');
$sheet->setCellValue('D' . $row, 'Keterangan Transaksi');
$sheet->setCellValue('E' . $row, 'Tanggal');
$sheet->setCellValue('F' . $row, 'Waktu');
$row++;

foreach ($pemasukan_kasir_closing_kasir as $pemasukan) {
    $sheet->setCellValue('A' . $row, $pemasukan['kode_transaksi']);
    $sheet->setCellValue('B' . $row, $pemasukan['kode_akun']);
    $sheet->setCellValue('C' . $row, 'Rp ' . number_format($pemasukan['jumlah'], 0, ',', '.'));
    $sheet->setCellValue('D' . $row, $pemasukan['keterangan_transaksi']);
    $sheet->setCellValue('E' . $row, $pemasukan['tanggal']);
    $sheet->setCellValue('F' . $row, $pemasukan['waktu']);
    $row++;
}
$sheet->setCellValue('A' . $row, 'Total Pemasukan');
$sheet->setCellValue('C' . $row, 'Rp ' . number_format($total_pemasukan, 0, ',', '.'));

// Pengeluaran Kasir
$row += 2;
$sheet->setCellValue('A' . $row, 'View Pengeluaran Kasir');
$row++;
$sheet->setCellValue('A' . $row, 'Kode Transaksi');
$sheet->setCellValue('B' . $row, 'Kode Akun');
$sheet->setCellValue('C' . $row, 'Kategori');
$sheet->setCellValue('D' . $row, 'Jumlah (Rp)');
$sheet->setCellValue('E' . $row, 'Keterangan Transaksi');
$sheet->setCellValue('F' . $row, 'Umur Pakai (Bulan)');
$sheet->setCellValue('G' . $row, 'Tanggal');
$sheet->setCellValue('H' . $row, 'Waktu');
$row++;

foreach ($pengeluaran_kasir_closing_kasir as $pengeluaran) {
    $sheet->setCellValue('A' . $row, $pengeluaran['kode_transaksi']);
    $sheet->setCellValue('B' . $row, $pengeluaran['kode_akun']);
    $sheet->setCellValue('C' . $row, $pengeluaran['kategori']);
    $sheet->setCellValue('D' . $row, 'Rp ' . number_format($pengeluaran['jumlah'], 0, ',', '.'));
    $sheet->setCellValue('E' . $row, $pengeluaran['keterangan_transaksi']);
    $sheet->setCellValue('F' . $row, $pengeluaran['umur_pakai'] . ' Bulan');
    $sheet->setCellValue('G' . $row, $pengeluaran['tanggal']);
    $sheet->setCellValue('H' . $row, $pengeluaran['waktu']);
    $row++;
}
$sheet->setCellValue('A' . $row, 'Total Pengeluaran');
$sheet->setCellValue('D' . $row, 'Rp ' . number_format($total_pengeluaran, 0, ',', '.'));

$revisionSummary = getClosingRevisionSummary($pdo, (string) $kode_transaksi);
if ($revisionSummary) {
    $request = $revisionSummary['request'] ?? [];
    $differences = $revisionSummary['differences'] ?? [];
    $lineItemChanges = $revisionSummary['line_item_changes'] ?? [];

    $row += 3;
    $sheet->setCellValue('A' . $row, 'Ringkasan Revisi Closing');
    $sheet->mergeCells("A{$row}:F{$row}");
    applyRevisionExcelHeader($sheet, "A{$row}:F{$row}");

    $row++;
    $sheet->setCellValue('A' . $row, 'Transaksi Lama Dibatalkan');
    $sheet->setCellValue('B' . $row, (string) ($request['kode_transaksi_lama'] ?? '-'));
    $sheet->setCellValue('D' . $row, 'Transaksi Baru Pengganti');
    $sheet->setCellValue('E' . $row, (string) ($request['kode_transaksi_baru'] ?? '-'));

    $row++;
    $sheet->setCellValue('A' . $row, 'Status Approval');
    $sheet->setCellValue('B' . $row, (string) ($request['status'] ?? '-'));
    $sheet->setCellValue('D' . $row, 'Pemohon');
    $sheet->setCellValue('E' . $row, (string) ($request['nama_pemohon'] ?? '-'));

    $row++;
    $sheet->setCellValue('D' . $row, 'Approver');
    $sheet->setCellValue('E' . $row, (string) ($request['nama_approver'] ?? '-'));

    if (!empty($request['alasan'])) {
        $row++;
        $sheet->setCellValue('A' . $row, 'Alasan Revisi');
        $sheet->mergeCells("B{$row}:F{$row}");
        $sheet->setCellValue('B' . $row, (string) $request['alasan']);
    }

    if (!empty($request['approval_note'])) {
        $row++;
        $sheet->setCellValue('A' . $row, 'Catatan Approval');
        $sheet->mergeCells("B{$row}:F{$row}");
        $sheet->setCellValue('B' . $row, (string) $request['approval_note']);
    }

    $row += 2;
    $sheet->setCellValue('A' . $row, 'Perbedaan Angka Utama');
    $sheet->mergeCells("A{$row}:F{$row}");
    applyRevisionExcelHeader($sheet, "A{$row}:F{$row}");
    $row++;
    $sheet->setCellValue('A' . $row, 'Bagian');
    $sheet->setCellValue('B' . $row, 'Transaksi Lama');
    $sheet->setCellValue('D' . $row, 'Transaksi Baru');
    applyRevisionExcelHeader($sheet, "A{$row}:F{$row}");

    if (!empty($differences)) {
        foreach ($differences as $difference) {
            $row++;
            $sheet->setCellValue('A' . $row, (string) ($difference['label'] ?? '-'));
            $sheet->setCellValue('B' . $row, formatRevisionExcelValue($difference['old'] ?? null, (string) ($difference['field'] ?? '')));
            $sheet->mergeCells("B{$row}:C{$row}");
            $sheet->setCellValue('D' . $row, formatRevisionExcelValue($difference['new'] ?? null, (string) ($difference['field'] ?? '')));
            $sheet->mergeCells("D{$row}:F{$row}");
        }
    } else {
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue('A' . $row, 'Tidak ada perubahan angka utama.');
    }

    $row += 2;
    $sheet->setCellValue('A' . $row, 'Detail Item Revisi');
    $sheet->mergeCells("A{$row}:F{$row}");
    applyRevisionExcelHeader($sheet, "A{$row}:F{$row}");
    $row++;
    $sheet->setCellValue('A' . $row, 'Bagian');
    $sheet->setCellValue('B' . $row, 'Jenis');
    $sheet->setCellValue('C' . $row, 'Item');
    $sheet->setCellValue('F' . $row, 'Keterangan');
    applyRevisionExcelHeader($sheet, "A{$row}:F{$row}");

    $hasLineItemChanges = false;
    foreach ($lineItemChanges as $section) {
        foreach ($section['added'] as $item) {
            $row++;
            $hasLineItemChanges = true;
            $sheet->setCellValue('A' . $row, (string) ($section['label'] ?? '-'));
            $sheet->setCellValue('B' . $row, 'Ditambahkan');
            $sheet->mergeCells("C{$row}:E{$row}");
            $sheet->setCellValue('C' . $row, (string) ($item['label'] ?? '-'));
            $sheet->setCellValue('F' . $row, (string) (($item['value_label'] ?? 'Nilai') . ' baru: ' . formatRevisionExcelDetailValue($item['new_value'] ?? null, (string) ($item['value_label'] ?? ''))));
        }

        foreach ($section['removed'] as $item) {
            $row++;
            $hasLineItemChanges = true;
            $sheet->setCellValue('A' . $row, (string) ($section['label'] ?? '-'));
            $sheet->setCellValue('B' . $row, 'Dihapus');
            $sheet->mergeCells("C{$row}:E{$row}");
            $sheet->setCellValue('C' . $row, (string) ($item['label'] ?? '-'));
            $sheet->setCellValue('F' . $row, (string) (($item['value_label'] ?? 'Nilai') . ' lama: ' . formatRevisionExcelDetailValue($item['old_value'] ?? null, (string) ($item['value_label'] ?? ''))));
        }

        foreach ($section['changed'] as $item) {
            $row++;
            $hasLineItemChanges = true;
            $sheet->setCellValue('A' . $row, (string) ($section['label'] ?? '-'));
            $sheet->setCellValue('B' . $row, 'Diubah');
            $sheet->mergeCells("C{$row}:E{$row}");
            $sheet->setCellValue('C' . $row, (string) ($item['label'] ?? '-'));
            $sheet->setCellValue('F' . $row, (string) (($item['value_label'] ?? 'Nilai') . ': ' . formatRevisionExcelDetailValue($item['old_value'] ?? null, (string) ($item['value_label'] ?? '')) . ' -> ' . formatRevisionExcelDetailValue($item['new_value'] ?? null, (string) ($item['value_label'] ?? ''))));
        }
    }

    if (!$hasLineItemChanges) {
        $row++;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue('A' . $row, 'Tidak ada perubahan detail item.');
    }
}

// Set borders to make the table more visible
$styleArray = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            'color' => ['argb' => 'FF000000'],
        ],
    ],
];
$sheet->getStyle('A1:H' . $row)->applyFromArray($styleArray);
$sheet->getStyle('A1:H' . $row)->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
$sheet->getStyle('A1:H' . $row)->getAlignment()->setWrapText(true);

// Simpan file Excel
$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
$filename = 'Laporan_Closing_Kasir_' . $kode_transaksi . '.xlsx';

// Set header untuk download file Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: max-age=0');

// Simpan file ke output langsung untuk di-download
$writer->save('php://output');
exit;
