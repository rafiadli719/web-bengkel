<?php
/**
 * AJAX Search Barang
 * Untuk Select2 di halaman master-fastmoves.php
 */

session_start();
if(empty($_SESSION['_iduser'])){
    echo json_encode([]);
    exit;
}

include "../config/koneksi.php";

$search = isset($_GET['q']) ? mysqli_real_escape_string($koneksi, $_GET['q']) : '';

if(strlen($search) < 2) {
    echo json_encode([]);
    exit;
}

$query = "SELECT 
            kode_barang,
            nama_barang,
            harga_jual,
            satuan
          FROM tbmaster_nama_barang
          WHERE (kode_barang LIKE '%$search%' OR nama_barang LIKE '%$search%')
          AND status = '0'
          ORDER BY nama_barang
          LIMIT 50";

$result = mysqli_query($koneksi, $query);
$data = [];

while($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'kode_barang' => $row['kode_barang'],
        'nama_barang' => $row['nama_barang'],
        'harga_jual' => $row['harga_jual'],
        'satuan' => $row['satuan']
    ];
}

header('Content-Type: application/json');
echo json_encode($data);
?>
