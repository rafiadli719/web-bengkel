<?php
/**
 * AJAX: GET INFO MEMBER PELANGGAN
 * ============================================================================
 * File ini digunakan untuk mendapatkan info member pelanggan via AJAX
 * Return HTML yang akan ditampilkan di halaman input servis
 * 
 * @version 1.0
 * @date 5 November 2025
 * ============================================================================
 */

// Include koneksi database
include "_koneksi.php";

// Include helper kategori member
include "_include_kategori_member.php";

// Get parameter
$no_pelanggan = isset($_GET['no_pelanggan']) ? mysqli_real_escape_string($koneksi, $_GET['no_pelanggan']) : '';
$mode = isset($_GET['mode']) ? mysqli_real_escape_string($koneksi, $_GET['mode']) : 'compact'; // compact atau full

// Validasi
if (empty($no_pelanggan)) {
    echo '<div class="alert alert-warning">Silakan pilih pelanggan terlebih dahulu</div>';
    exit;
}

// Display info berdasarkan mode
if ($mode == 'full') {
    echo displayInfoKategoriMember($koneksi, $no_pelanggan);
} else {
    echo displayInfoKategoriMemberCompact($koneksi, $no_pelanggan);
}
?>
