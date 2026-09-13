<?php
require __DIR__ . '/koneksi_komplain.php';
require __DIR__ . '/../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if (!cekPermissionKomplain('komplain_export')) {
    http_response_code(403);
    die('Tidak punya akses export.');
}

$filterCabang = $_GET['cabang'] ?? null;
$sql = "SELECT no_komplain, nama_pelanggan, nopol, kode_cabang, kode_kategori, status, tanggal_lapor FROM tblkomplain";
$params = [];
if ($filterCabang) {
    $sql .= " WHERE kode_cabang = :cabang";
    $params[':cabang'] = $filterCabang;
}
$stmt = $koneksi_komplain->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$header = ['No Komplain', 'Nama Pelanggan', 'Nopol', 'Cabang', 'Kategori', 'Status', 'Tanggal Lapor'];
$sheet->fromArray($header, null, 'A1');
$sheet->fromArray(array_map('array_values', $rows), null, 'A2');

$stmtLog = $koneksi_komplain->prepare(
    "INSERT INTO tblkomplain_export_log (kode_karyawan, cakupan_filter, jumlah_baris) VALUES (:pelaku, :filter, :jumlah)"
);
$stmtLog->execute([
    ':pelaku' => $kode_karyawan_aktif,
    ':filter' => $filterCabang ?: 'semua_cabang',
    ':jumlah' => count($rows),
]);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="komplain_export_' . date('Ymd_His') . '.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
