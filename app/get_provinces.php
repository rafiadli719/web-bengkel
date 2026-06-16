<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";

// Ambil data provinsi
$query = "SELECT DISTINCT provinsi FROM tbwilayah ORDER BY provinsi ASC";
$result = mysqli_query($koneksi, $query);

$provinces = [];
while($row = mysqli_fetch_array($result)) {
    $provinces[] = $row['provinsi'];
}

header('Content-Type: application/json');
echo json_encode($provinces);
?>
