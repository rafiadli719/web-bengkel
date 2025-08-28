<?php
session_start();
if(empty($_SESSION['_iduser'])){
    http_response_code(403);
    exit;
}

include "../config/koneksi.php";

if(isset($_POST['phone'])) {
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    
    // Clean phone number (remove non-numeric characters except +)
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check in tblpelanggan table
    $query = "SELECT namapelanggan, telephone FROM tblpelanggan 
              WHERE telephone = '$clean_phone' 
              OR telephone = '$phone'
              OR REPLACE(REPLACE(REPLACE(telephone, '-', ''), ' ', ''), '+', '') = REPLACE(REPLACE(REPLACE('$clean_phone', '-', ''), ' ', ''), '+', '')
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if(mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_array($result);
        echo json_encode([
            'exists' => true,
            'data' => [
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
