<?php
session_start();

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include "../../config/koneksi.php";

// Set header untuk JSON response
header('Content-Type: application/json');

try {
    // Validate required parameters
    if(!isset($_POST['no_service']) || empty($_POST['no_service'])) {
        throw new Exception('No service tidak valid');
    }
    
    $no_service = mysqli_real_escape_string($koneksi, $_POST['no_service']);
    
    // Get KM values
    $km_skr = $_POST['km_skr'] ?? 0;
    $km_berikut = $_POST['km_berikut'] ?? 0;
    
    // Validate and sanitize
    $km_skr = (int)$km_skr;
    $km_berikut = (int)$km_berikut;
    
    // Update query
    $sql = "UPDATE tblservice SET 
            km_skr = $km_skr,
            km_berikut = $km_berikut
            WHERE no_service = '$no_service'";
    
    // Execute update
    $result = mysqli_query($koneksi, $sql);
    
    if(!$result) {
        throw new Exception('Database error: ' . mysqli_error($koneksi));
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Data KM berhasil disimpan',
        'km_skr' => $km_skr,
        'km_berikut' => $km_berikut
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

// Close connection
mysqli_close($koneksi);
?>