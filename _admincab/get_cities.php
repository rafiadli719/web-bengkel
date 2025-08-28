<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";

if(isset($_POST['provinsi'])) {
    $provinsi = mysqli_real_escape_string($koneksi, $_POST['provinsi']);
    
    $query = "SELECT DISTINCT kota_kabupaten FROM tbwilayah WHERE provinsi = '$provinsi' ORDER BY kota_kabupaten ASC";
    $result = mysqli_query($koneksi, $query);
    
    $cities = [];
    while($row = mysqli_fetch_array($result)) {
        $cities[] = $row['kota_kabupaten'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($cities);
} else {
    echo json_encode([]);
}
?>
