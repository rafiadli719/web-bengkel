<?php
session_start();
include "../../config/koneksi.php";

header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit;
}

try {
    $kode_barang = $_POST['kode_barang'] ?? '';
    
    if(empty($kode_barang)) {
        echo json_encode(array('success' => false, 'message' => 'Kode barang tidak boleh kosong'));
        exit;
    }
    
    // Search in tblitem table
    $query = "SELECT noitem, namaitem, hargapokok FROM tblitem 
              WHERE noitem LIKE '%$kode_barang%' OR namaitem LIKE '%$kode_barang%' 
              ORDER BY namaitem ASC LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $item = mysqli_fetch_array($result);
        echo json_encode(array(
            'success' => true,
            'kode_barang' => $item['noitem'],
            'nama_barang' => $item['namaitem'],
            'harga' => $item['hargapokok']
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Barang tidak ditemukan'));
    }
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
}
?>
