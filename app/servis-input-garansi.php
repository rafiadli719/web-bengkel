<?php
// Halaman ini sudah digantikan oleh servis-garansi.php (redesign 3-kolom, konsisten
// dengan servis-input-reguler.php & servis-input-reguler-jemput.php). Dipertahankan
// sebagai redirect supaya bookmark/link lama tetap jalan ke tampilan yang benar.
session_start();
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

$no_service = $_GET['snoserv'] ?? $_GET['no_service'] ?? '';
$target = 'servis-garansi.php';
if (!empty($no_service)) {
    $target .= '?snoserv=' . urlencode($no_service);
    if (!empty($_GET['tab'])) {
        $target .= '&tab=' . urlencode($_GET['tab']);
    }
}
header('Location: ' . $target);
exit;
