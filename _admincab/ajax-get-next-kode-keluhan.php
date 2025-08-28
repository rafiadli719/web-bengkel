<?php
// File: ajax-get-next-kode-keluhan.php
// AJAX endpoint untuk mendapatkan kode keluhan berikutnya

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get input parameter
$prefix = isset($_GET['prefix']) ? trim($_GET['prefix']) : 'KEL';

try {
    // Query untuk mendapatkan kode terakhir dengan prefix yang sama
    $query = "SELECT kode_keluhan FROM tbmaster_keluhan 
              WHERE kode_keluhan LIKE '$prefix%' 
              ORDER BY kode_keluhan DESC 
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        $last_kode = $data['kode_keluhan'];
        
        // Extract number dari kode terakhir
        $last_number = intval(substr($last_kode, strlen($prefix)));
        $next_number = $last_number + 1;
        
        // Format dengan leading zeros (3 digit)
        $next_kode = $prefix . sprintf('%03d', $next_number);
    } else {
        // Jika belum ada kode dengan prefix ini, mulai dari 001
        $next_kode = $prefix . '001';
    }
    
    echo json_encode([
        'success' => true,
        'next_kode' => $next_kode
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>