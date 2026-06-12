<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";

if(isset($_POST['provinsi'])) {
    $provinsi = mysqli_real_escape_string($koneksi, $_POST['provinsi']);

    // Menggunakan prepared statement untuk keamanan
    $query = "SELECT DISTINCT kota_kabupaten FROM tbwilayah WHERE provinsi = ? ORDER BY kota_kabupaten ASC";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "s", $provinsi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $cities = [];
    while($row = mysqli_fetch_array($result)) {
        $cities[] = $row['kota_kabupaten'];
    }

    mysqli_stmt_close($stmt);
    header('Content-Type: application/json');
    echo json_encode($cities);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
