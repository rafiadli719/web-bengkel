<?php
/**
 * Approve Penawaran Jasa
 * Menyetujui penawaran jasa dan menambahkan ke tblservis_jasa
 */
session_start();
if(empty($_SESSION['_iduser'])) {
    header('Location: ../index.php');
    exit;
}

include "../config/koneksi.php";
include "_include_kategori_member.php"; // Member kategori & discount helper

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$snoserv = isset($_GET['snoserv']) ? mysqli_real_escape_string($koneksi, $_GET['snoserv']) : '';

if($id <= 0 || empty($snoserv)) {
    echo "<script>alert('Parameter tidak valid'); window.history.back();</script>";
    exit;
}

// Get penawaran data
$query = mysqli_query($koneksi, "SELECT * FROM tbservis_penawaran_jasa WHERE id = '$id'");
$penawaran = mysqli_fetch_array($query);

// Tentukan halaman redirect berdasarkan tipe_service
$redirect_base = 'servis-input-reguler.php';
$qts = mysqli_query($koneksi, "SELECT tipe_service FROM tblservice WHERE no_service='$snoserv' LIMIT 1");
if($qts && ($rs = mysqli_fetch_assoc($qts))) {
    if(strtolower($rs['tipe_service'] ?? '') === 'jemput') {
        $redirect_base = 'servis-input-reguler-jemput.php';
    }
}

if(!$penawaran) {
    echo "<script>alert('Penawaran tidak ditemukan'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
    exit;
}

$no_service = $penawaran['no_service'];
$kode_jasa = $penawaran['kode_jasa'];
$harga = floatval($penawaran['harga']);
$waktu = $penawaran['waktu_estimasi'];
$user_respon = $_SESSION['_nama'] ?? 'System';

// Hitung diskon promo/member aktif untuk item ini
$no_polisi_svc = '';
$q_cust = mysqli_query($koneksi, "SELECT no_polisi FROM tblservice WHERE no_service='$no_service'");
if($q_cust && ($cust = mysqli_fetch_assoc($q_cust))) {
    $no_polisi_svc = $cust['no_polisi'] ?? '';
}

$diskon_source = '';
$diskon_persen = 0;
$diskon_nominal = 0;
$id_promo = 'NULL';

if(function_exists('calculateItemDiscount') && !empty($no_polisi_svc)) {
    $disc = calculateItemDiscount($koneksi, $no_polisi_svc, $kode_jasa, 'jasa', $harga);
    if(($disc['diskon_nominal'] ?? 0) > 0) {
        $diskon_persen = floatval($disc['diskon_persen']);
        $diskon_nominal = floatval($disc['diskon_nominal']);
        $diskon_source = (stripos($disc['discount_source'], 'promo') !== false) ? 'promo' : ((stripos($disc['discount_source'], 'member') !== false) ? 'member' : '');
        if($diskon_source === 'promo' && function_exists('getActiveDiscountForService')) {
            $ad = getActiveDiscountForService($koneksi, $no_polisi_svc);
            if(!empty($ad['promo_id'])) { $id_promo = intval($ad['promo_id']); }
        }
    }
}

$total_jasa = $harga - $diskon_nominal;
if($total_jasa < 0) { $total_jasa = 0; }

// Update status penawaran
$update = mysqli_query($koneksi, "UPDATE tbservis_penawaran_jasa
                                  SET status_penawaran = 'disetujui',
                                      tanggal_respon = NOW(),
                                      user_respon = '".mysqli_real_escape_string($koneksi, $user_respon)."'
                                  WHERE id = '$id'");

if($update) {
    // Get next nobaris
    $q_nobaris = mysqli_query($koneksi, "SELECT COALESCE(MAX(nobaris), 0) + 1 as next_nobaris FROM tblservis_jasa WHERE no_service='$no_service'");
    $nobaris_data = mysqli_fetch_array($q_nobaris);
    $nobaris = $nobaris_data['next_nobaris'] ?? 1;

    // Check waktu column exists
    $check_waktu = mysqli_query($koneksi, "SHOW COLUMNS FROM tblservis_jasa LIKE 'waktu'");
    $has_waktu = ($check_waktu && mysqli_num_rows($check_waktu) > 0);

    // Insert ke tblservis_jasa
    if($has_waktu) {
        $insert = mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                           (no_service, nobaris, no_item, harga, waktu, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                           VALUES
                                           ('$no_service', '$nobaris', '$kode_jasa', '$harga', '$waktu', '$diskon_persen', '$total_jasa', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
    } else {
        $insert = mysqli_query($koneksi, "INSERT INTO tblservis_jasa
                                           (no_service, nobaris, no_item, harga, potongan, total, diskon_source, diskon_persen, diskon_nominal, id_promo)
                                           VALUES
                                           ('$no_service', '$nobaris', '$kode_jasa', '$harga', '$diskon_persen', '$total_jasa', '$diskon_source', '$diskon_persen', '$diskon_nominal', $id_promo)");
    }

    if($insert) {
        if($diskon_source === 'promo' && isset($disc) && function_exists('wireLogPromoUsage')) { wireLogPromoUsage($koneksi, $disc, $no_service, 'jasa', $kode_jasa); }
        echo "<script>alert('Penawaran jasa disetujui dan ditambahkan ke servis!'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
    } else {
        echo "<script>alert('Penawaran disetujui, tapi gagal menambahkan ke servis: ".mysqli_error($koneksi)."'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
    }
} else {
    echo "<script>alert('Gagal menyetujui penawaran: ".mysqli_error($koneksi)."'); window.location.href='".$redirect_base."?snoserv=$snoserv&tab=temuan-penawaran';</script>";
}
?>
