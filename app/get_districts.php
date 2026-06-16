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

    // Menggunakan prepared statement untuk keamanan
    $query = "SELECT DISTINCT kecamatan FROM tbwilayah
              WHERE provinsi = ? AND kota_kabupaten = ?
              ORDER BY kecamatan ASC";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, "ss", $provinsi, $kota);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $districts = [];
    while($row = mysqli_fetch_array($result)) {
        $districts[] = $row['kecamatan'];
    }

    mysqli_stmt_close($stmt);
    header('Content-Type: application/json');
    echo json_encode($districts);
} else {
    header('Content-Type: application/json');
    echo json_encode([]);
}
?>
