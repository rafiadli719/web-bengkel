<?php
// File: ajax-get-keluhan-detail.php
// AJAX endpoint untuk mendapatkan detail keluhan

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get input parameter
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ID parameter']);
    exit;
}

try {
    // Query detail keluhan
    $query = "SELECT * FROM tbmaster_keluhan WHERE id = $id";
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Keluhan tidak ditemukan'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>