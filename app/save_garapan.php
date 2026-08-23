<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "function_servis.php";

// Ambil data dari form dan session
$nopelanggan_param = $_GET['nopelanggan'] ?? '';
$daftar_pengerjaan = trim($_POST['txtdaftar'] ?? '');
$jam_jemput = $_POST['txtjamjemput'] ?? '';
$keterangan_jemput = trim($_POST['txtketerangan'] ?? '');
$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

// Validasi input
if (empty($nopelanggan_param)) {
    header("location:pelanggan.php?error=" . urlencode("Pelanggan tidak dipilih"));
    exit;
}

if (empty($daftar_pengerjaan)) {
    header("location:input_garapan.php?nopelanggan=" . urlencode($nopelanggan_param) . "&error=" . urlencode("Daftar pengerjaan wajib diisi"));
    exit;
}

// Ambil data pelanggan berdasarkan nopelanggan
$stmt = mysqli_prepare($koneksi, "SELECT nopelanggan, namapelanggan FROM tblpelanggan WHERE nopelanggan = ?");
mysqli_stmt_bind_param($stmt, "s", $nopelanggan_param);

if (!$stmt) {
    header("location:input_garapan.php?nopelanggan=" . urlencode($nopelanggan_param) . "&error=" . urlencode("Error prepare statement: " . mysqli_error($koneksi)));
    exit;
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pelanggan = mysqli_fetch_assoc($result);

if (!$pelanggan) {
    mysqli_stmt_close($stmt);
    header("location:input_garapan.php?nopelanggan=" . urlencode($nopelanggan_param) . "&error=" . urlencode("Data pelanggan tidak ditemukan"));
    exit;
}

$nopelanggan = $pelanggan['nopelanggan'];
$namapelanggan = $pelanggan['namapelanggan'];
mysqli_stmt_close($stmt);

// Generate nomor service
// FIX 2026-08-23: SELECT ... ORDER BY DESC LIMIT 1 lalu +1 rawan race
// condition kalau dua request nabrak barengan. Ganti ke atomic counter
// per prefix (function_servis.php::NextServiceSeqByPrefix). Format
// no_service TIDAK berubah.
$tahun = date('Y');
$bulan = date('m');
$prefix = "SRV" . $tahun . $bulan;

$next_seq = NextServiceSeqByPrefix($koneksi, $prefix, $prefix);
$no_service = $prefix . str_pad($next_seq, 4, '0', STR_PAD_LEFT);

// Tentukan jenis servis berdasarkan ada tidaknya jam jemput
$jenis_servis = (!empty($jam_jemput)) ? 'jemput' : 'reguler';
$status_jemput = (!empty($jam_jemput)) ? '1' : '0';

// Mulai transaksi
mysqli_begin_transaction($koneksi);

try {
    // Insert ke tblservice
    $tanggal = date('Y-m-d');
    $jam = date('H:i:s');
    $status = '1'; // Status awal (dalam proses)
    $total = 0;
    $diskon_persen = 0;
    $diskon_nom = 0;
    $ppn_persen = 0;
    $ppn_nom = 0;
    $total_grand = 0;
    $total_waktu = 0;
    
    $query_service = "INSERT INTO tblservice (
        no_service, tanggal, jam, no_pelanggan, no_polisi, 
        status, total, diskon_persen, diskon_nom, ppn_persen, ppn_nom, 
        total_grand, total_waktu, kd_cabang
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_service = mysqli_prepare($koneksi, $query_service);
    if (!$stmt_service) {
        throw new Exception("Error prepare tblservice: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmt_service, "ssssssddddddds", 
        $no_service, $tanggal, $jam, $nopelanggan, $nopelanggan, 
        $status, $total, $diskon_persen, $diskon_nom, $ppn_persen, $ppn_nom, 
        $total_grand, $total_waktu, $kd_cabang);
    
    if (!mysqli_stmt_execute($stmt_service)) {
        throw new Exception("Error execute tblservice: " . mysqli_stmt_error($stmt_service));
    }
    mysqli_stmt_close($stmt_service);
    
    // Insert ke tbservis_keluhan (untuk menyimpan daftar pengerjaan)
    $query_keluhan = "INSERT INTO tbservis_keluhan (no_service, keluhan) VALUES (?, ?)";
    $stmt_keluhan = mysqli_prepare($koneksi, $query_keluhan);
    if (!$stmt_keluhan) {
        throw new Exception("Error prepare tbservis_keluhan: " . mysqli_error($koneksi));
    }
    
    mysqli_stmt_bind_param($stmt_keluhan, "ss", $no_service, $daftar_pengerjaan);
    if (!mysqli_stmt_execute($stmt_keluhan)) {
        throw new Exception("Error execute tbservis_keluhan: " . mysqli_stmt_error($stmt_keluhan));
    }
    mysqli_stmt_close($stmt_keluhan);
    
    // Jika ada jam jemput, simpan informasi ke session atau tabel khusus
    if (!empty($jam_jemput)) {
        // Bisa disimpan ke session untuk digunakan di halaman servis
        $_SESSION['jemput_antar'] = array(
            'jam_jemput' => $jam_jemput,
            'keterangan' => $keterangan_jemput,
            'no_service' => $no_service
        );
    }
    
    // Commit transaksi
    mysqli_commit($koneksi);
    
    // Redirect berdasarkan jenis servis
    if (!empty($jam_jemput)) {
        header("location:servis-input-reguler-jemput-rst.php?snoserv=" . urlencode($no_service));
    } else {
        header("location:servis-input-reguler-rst.php?snoserv=" . urlencode($no_service));
    }
    exit;
    
} catch (Exception $e) {
    // Rollback transaksi jika terjadi error
    mysqli_rollback($koneksi);
    
    // Log error untuk debugging
    error_log("Error in save_garapan.php: " . $e->getMessage());
    
    header("location:input_garapan.php?pelanggan_id=" . urlencode($pelanggan_id) . "&error=" . urlencode("Gagal menyimpan data: " . $e->getMessage()));
    exit;
}

mysqli_close($koneksi);
?>