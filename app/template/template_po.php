<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../../index.php");
    exit;
}

require_once __DIR__ . '/../PHPExcel/PHPExcel.php';
require_once __DIR__ . '/../PHPExcel/PHPExcel/IOFactory.php';

ini_set('display_errors', '0');
session_write_close();

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()
    ->setCreator('Fitmotor Bengkel')
    ->setTitle('Template Upload PO')
    ->setSubject('Template Upload PO');

$sheet = $objPHPExcel->setActiveSheetIndex(0);
$sheet->setTitle('PO');

$headers = ['KODE', 'QTY', 'HARGA'];
$col = 'A';
foreach ($headers as $h) {
    $sheet->setCellValue($col . '1', $h);
    $sheet->getStyle($col . '1')->getFont()->setBold(true);
    $sheet->getStyle($col . '1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $col++;
}

$sheet->getColumnDimension('A')->setWidth(18);
$sheet->getColumnDimension('B')->setWidth(10);
$sheet->getColumnDimension('C')->setWidth(15);

$sheet->setCellValue('A2', 'BRG001');
$sheet->setCellValue('B2', 10);
$sheet->setCellValue('C2', 15000);

$sheet->setCellValue('A3', 'BRG002');
$sheet->setCellValue('B3', 5);
$sheet->setCellValue('C3', '');

$sheet->getStyle('B2:B1000')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
$sheet->getStyle('C2:C1000')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);

$sheet->freezePane('A2');
$sheet->setAutoFilter('A1:C1');

$sheet->getStyle('A1:C3')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);

$filename = 'template_po.xlsx';

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$writer->save('php://output');
exit;
