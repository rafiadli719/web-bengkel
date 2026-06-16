<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";

if(isset($_POST['nopol']) && isset($_POST['phone'])) {
    $nopol = mysqli_real_escape_string($koneksi, $_POST['nopol']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    
    // Check if vehicle exists for this customer
    $query = "SELECT k.nopolisi, p.namapelanggan, p.telephone 
              FROM tblkendaraan k 
              JOIN tblpelanggan p ON k.nopolisi = p.nopelanggan 
              WHERE k.nopolisi = '$nopol' AND p.telephone = '$phone'
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_array($result);
        echo json_encode([
            'exists' => true,
            'data' => [
                'nopolisi' => $data['nopolisi'],
                'nama' => $data['namapelanggan'],
                'phone' => $data['telephone']
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
} else {
    echo json_encode(['exists' => false]);
}
?>
