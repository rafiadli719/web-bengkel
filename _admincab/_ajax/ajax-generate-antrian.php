<?php
session_start();
include "../../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    die("Unauthorized access");
}

$response = array('success' => false, 'message' => '', 'no_antrian' => '');

try {
    $tanggal = date('Y-m-d');
    
    // Cek nomor antrian terakhir untuk hari ini
    $query = "SELECT no_antrian FROM tb_antrian_servis 
              WHERE tanggal = '$tanggal' 
              ORDER BY no_antrian DESC 
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_array($result);
        $last_number = intval(substr($row['no_antrian'], 1)); // Ambil angka dari A001
        $next_number = $last_number + 1;
    } else {
        $next_number = 1;
    }
    
    // Format nomor antrian: A001, A002, dst
    $no_antrian = 'A' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    
    $response['success'] = true;
    $response['no_antrian'] = $no_antrian;
    $response['message'] = 'Nomor antrian berhasil di-generate';
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
