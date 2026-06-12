<?php
/**
 * AJAX: GET DISKON PELANGGAN
 * ============================================================================
 * File ini digunakan untuk mendapatkan diskon pelanggan via AJAX
 * Digunakan di halaman input servis untuk hitung total otomatis
 * 
 * @version 1.0
 * @date 5 November 2025
 * ============================================================================
 */

// Include koneksi database
include "_koneksi.php";

// Include helper kategori member
include "_include_kategori_member.php";

// Set header JSON
header('Content-Type: application/json');

// Get parameter
$no_pelanggan = isset($_GET['no_pelanggan']) ? mysqli_real_escape_string($koneksi, $_GET['no_pelanggan']) : '';

// Validasi
if (empty($no_pelanggan)) {
    echo json_encode([
        'success' => false,
        'message' => 'Nomor pelanggan tidak boleh kosong',
        'diskon_persen' => 0,
        'status_member' => 'Bronze'
    ]);
    exit;
}

// Get info kategori member
$info = getKategoriMemberPelanggan($koneksi, $no_pelanggan);

// Return JSON
echo json_encode([
    'success' => true,
    'no_pelanggan' => $no_pelanggan,
    'diskon_persen' => floatval($info['diskon_persen']),
    'status_member' => $info['status_member'],
    'total_transaksi' => intval($info['total_transaksi']),
    'total_nominal' => floatval($info['total_nominal']),
    'rata_rata_transaksi' => floatval($info['rata_rata_transaksi'])
]);
?>
