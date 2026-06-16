<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header('Location: ../index.php');
    exit;
}

include "../config/koneksi.php";

$no_service = isset($_GET['snoserv']) ? $_GET['snoserv'] : (isset($_GET['no_service']) ? $_GET['no_service'] : '');
$tab = isset($_GET['tab']) ? $_GET['tab'] : '';

if (empty($no_service)) {
    header('Location: index.php');
    exit;
}

$ns = mysqli_real_escape_string($koneksi, $no_service);
$q = mysqli_query($koneksi, "SELECT tipe_service, status_servis FROM tblservice WHERE no_service='" . $ns . "' LIMIT 1");
$tipe = 'reguler';
$status = '';
if ($q && ($r = mysqli_fetch_assoc($q))) {
    $tipe = strtolower(trim(isset($r['tipe_service']) ? $r['tipe_service'] : 'reguler'));
    $status = strtolower(isset($r['status_servis']) ? $r['status_servis'] : '');
}
$finished = ($status === 'selesai' || $status === 'bayar');

switch ($tipe) {
    case 'jemput':
        $page = $finished ? 'servis-input-reguler-jemput-rst.php' : 'servis-input-reguler-jemput.php';
        break;
    case 'garansi':
        $page = $finished ? 'servis-garansi-rst.php' : 'servis-garansi.php';
        break;
    default:
        $page = $finished ? 'servis-input-reguler-rst.php' : 'servis-input-reguler.php';
}

$redir = $page . '?snoserv=' . urlencode($no_service);
if ($tab !== '') {
    $redir .= '&tab=' . urlencode($tab);
}
header('Location: ' . $redir);
exit;
?>
