<?php
session_start();

if (empty($_SESSION['_iduser']) || empty($_SESSION['_cabang'])) {
    header("Location: ../index.php");
    exit;
}

$id_user = (int) $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];
$nopol = isset($_GET['snopol']) ? trim($_GET['snopol']) : '';

if ($nopol === '') {
    echo "<script>window.alert('Nomor polisi tidak valid.');window.location=('servis-carinopol.php');</script>";
    exit;
}

include "../config/koneksi.php";
include "function_servis.php";
require_once __DIR__ . '/_include_customer_vehicle_sync.php';

date_default_timezone_set('Asia/Jakarta');
$tgl_skr = date('Y/m/d');
$waktu_skr = date('H:i');
$tanggal_today = date('Y-m-d');

$stmtVehicle = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ? LIMIT 1");
mysqli_stmt_bind_param($stmtVehicle, "s", $nopol);
mysqli_stmt_execute($stmtVehicle);
$vehicleResult = mysqli_stmt_get_result($stmtVehicle);
if (!$vehicleResult || mysqli_num_rows($vehicleResult) === 0) {
    mysqli_stmt_close($stmtVehicle);
    echo "<script>window.alert('Data kendaraan tidak ditemukan.');window.location=('servis-carinopol.php');</script>";
    exit;
}
mysqli_stmt_close($stmtVehicle);

$stmtDuplicate = mysqli_prepare(
    $koneksi,
    "SELECT no_service FROM tblservice WHERE no_polisi = ? AND COALESCE(status_servis, '') NOT IN ('selesai', 'bayar', 'cancel') ORDER BY tanggal DESC, jam DESC LIMIT 1"
);
mysqli_stmt_bind_param($stmtDuplicate, "s", $nopol);
mysqli_stmt_execute($stmtDuplicate);
$duplicateResult = mysqli_stmt_get_result($stmtDuplicate);
if ($duplicateResult && ($duplicateRow = mysqli_fetch_assoc($duplicateResult))) {
    mysqli_stmt_close($stmtDuplicate);
    $existingService = urlencode($duplicateRow['no_service']);
    echo "<script>window.alert('Masih ada service aktif untuk nomor polisi ini.');window.location=('servis-input-reguler-jemput.php?snoserv={$existingService}');</script>";
    exit;
}
mysqli_stmt_close($stmtDuplicate);

$lastID = FormatNoTrans(OtomatisID());
$bundle = fitmotorGetCustomerVehicleBundle($koneksi, $nopol);
$customerCode = trim((string)($bundle['mapped_customer_code'] ?? ''));
if ($customerCode === '') {
    echo "<script>window.alert('Data pelanggan untuk nomor polisi ini belum valid. Rapikan relasi pelanggan-kendaraan dulu.');window.location=('servis-carinopol.php');</script>";
    exit;
}

$stmtInsert = mysqli_prepare(
    $koneksi,
    "INSERT INTO tblservice (no_service, tanggal, jam, no_pelanggan, no_polisi, status_jemput, status_servis, kd_cabang, id_user) VALUES (?, ?, ?, ?, ?, '1', 'datang', ?, ?)"
);
mysqli_stmt_bind_param($stmtInsert, "ssssssi", $lastID, $tgl_skr, $waktu_skr, $customerCode, $nopol, $kd_cabang, $id_user);
if (!mysqli_stmt_execute($stmtInsert)) {
    mysqli_stmt_close($stmtInsert);
    echo "<script>window.alert('Gagal membuat service jemput.');window.location=('servis-carinopol.php');</script>";
    exit;
}
mysqli_stmt_close($stmtInsert);

$resultAntrian = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '" . mysqli_real_escape_string($koneksi, $tanggal_today) . "'");
$antrian_count = 1;
if ($resultAntrian && ($antrianData = mysqli_fetch_assoc($resultAntrian))) {
    $antrian_count = ((int) $antrianData['total']) + 1;
}

$stmtQueue = mysqli_prepare(
    $koneksi,
    "INSERT INTO tb_antrian_servis (no_service, tanggal, no_antrian, status, tipe) VALUES (?, ?, ?, 'dijemput', 'jemput')"
);
mysqli_stmt_bind_param($stmtQueue, "ssi", $lastID, $tanggal_today, $antrian_count);
mysqli_stmt_execute($stmtQueue);
mysqli_stmt_close($stmtQueue);

echo "<script>window.location=('servis-input-reguler-jemput.php?snoserv=" . urlencode($lastID) . "&tab=pickup-details');</script>";
exit;
?>
