<?php
session_start();
include "../../config/koneksi.php";

header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit;
}

try {
    $kode_jasa = $_POST['kode_jasa'] ?? '';
    
    if(empty($kode_jasa)) {
        echo json_encode(array('success' => false, 'message' => 'Kode jasa tidak boleh kosong'));
        exit;
    }
    
    // Search in tbljasa table (assuming it exists)
    // If tbljasa doesn't exist, you can create a simple service list
    $query = "SELECT kode_jasa, nama_jasa, harga FROM tbljasa 
              WHERE kode_jasa LIKE '%$kode_jasa%' OR nama_jasa LIKE '%$kode_jasa%' 
              ORDER BY nama_jasa ASC LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $jasa = mysqli_fetch_array($result);
        echo json_encode(array(
            'success' => true,
            'kode_jasa' => $jasa['kode_jasa'],
            'nama_jasa' => $jasa['nama_jasa'],
            'harga' => $jasa['harga']
        ));
    } else {
        // If no service found, return default service based on code
        $default_services = array(
            'SERV' => array('nama' => 'Service Berkala', 'harga' => 50000),
            'TUNE' => array('nama' => 'Tune Up', 'harga' => 75000),
            'REM' => array('nama' => 'Service Rem', 'harga' => 30000),
            'KARB' => array('nama' => 'Service Karburator', 'harga' => 45000),
            'GANTI' => array('nama' => 'Ganti Oli', 'harga' => 25000),
            'CUCI' => array('nama' => 'Cuci Motor', 'harga' => 15000),
            'DETIL' => array('nama' => 'Detailing', 'harga' => 100000),
            'LAIN' => array('nama' => 'Lain-lain', 'harga' => 0)
        );
        
        if(isset($default_services[$kode_jasa])) {
            echo json_encode(array(
                'success' => true,
                'kode_jasa' => $kode_jasa,
                'nama_jasa' => $default_services[$kode_jasa]['nama'],
                'harga' => $default_services[$kode_jasa]['harga']
            ));
        } else {
            echo json_encode(array('success' => false, 'message' => 'Jasa tidak ditemukan'));
        }
    }
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
}
?>
