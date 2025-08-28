<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";

if(isset($_POST['provinsi']) && isset($_POST['kota'])) {
    $provinsi = mysqli_real_escape_string($koneksi, $_POST['provinsi']);
    $kota = mysqli_real_escape_string($koneksi, $_POST['kota']);
    
    $query = "SELECT DISTINCT kecamatan FROM tbwilayah 
              WHERE provinsi = '$provinsi' AND kota_kabupaten = '$kota' 
              ORDER BY kecamatan ASC";
    $result = mysqli_query($koneksi, $query);
    
    $districts = [];
    while($row = mysqli_fetch_array($result)) {
        $districts[] = $row['kecamatan'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($districts);
} else {
    echo json_encode([]);
}
?>
