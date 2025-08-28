<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

if(isset($_POST['nopol'])) {
    $nopol = mysqli_real_escape_string($koneksi, $_POST['nopol']);
    
    $query = "SELECT nopolisi, pemilik, telephone FROM view_pelanggan_kendaraan WHERE nopolisi = '$nopol' LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_array($result);
        echo json_encode([
            'exists' => true,
            'data' => [
                'nopolisi' => $data['nopolisi'],
                'pemilik' => $data['pemilik'],
                'telephone' => $data['telephone']
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} else {
    echo json_encode(['exists' => false]);
}
?>
