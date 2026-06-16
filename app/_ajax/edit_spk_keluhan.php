<?php
session_start();

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include "../config/koneksi.php";

// Set header untuk JSON response
header('Content-Type: application/json');

try {
    // Validate required parameters
    if(!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('ID keluhan tidak valid');
    }
    
    if(!isset($_POST['no_service']) || empty($_POST['no_service'])) {
        throw new Exception('No service tidak valid');
    }
    
    if(!isset($_POST['keluhan_baru']) || empty($_POST['keluhan_baru'])) {
        throw new Exception('Keluhan baru tidak boleh kosong');
    }
    
    $id = (int)$_POST['id'];
    $no_service = mysqli_real_escape_string($koneksi, $_POST['no_service']);
    $keluhan_baru = mysqli_real_escape_string($koneksi, $_POST['keluhan_baru']);
    
    // Update keluhan in SPK
    $sql = "UPDATE tbservis_keluhan_status 
            SET keluhan = '$keluhan_baru' 
            WHERE id = $id AND no_service = '$no_service'";
    
    // Execute update
    $result = mysqli_query($koneksi, $sql);
    
    if(!$result) {
        throw new Exception('Database error: ' . mysqli_error($koneksi));
    }
    
    // Check if any rows were affected
    if(mysqli_affected_rows($koneksi) > 0) {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Keluhan berhasil diupdate',
            'keluhan_baru' => $keluhan_baru
        ]);
    } else {
        throw new Exception('Keluhan tidak ditemukan atau tidak ada perubahan');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

// Close connection
mysqli_close($koneksi);
?>