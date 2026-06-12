<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include "../config/koneksi.php";

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kategori'])) {
    $kategori_rak = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    
    try {
        // Check if category exists
        $cat_check = mysqli_query($koneksi, "SELECT kode FROM tbkategori_rak WHERE kode = '$kategori_rak'");
        if (!$cat_check || mysqli_num_rows($cat_check) == 0) {
            echo json_encode(['success' => false, 'message' => 'Kategori tidak valid']);
            exit;
        }
        
        // Get last number for this category
        $prefix = "IM";
        $last_query = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING(noitem, 6) AS UNSIGNED)) as last_num 
                                           FROM tblitem 
                                           WHERE noitem LIKE '$prefix-$kategori_rak%' 
                                           AND tipe_item = 'NON_ORI'");
        
        if (!$last_query) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
            exit;
        }
        
        $last_result = mysqli_fetch_array($last_query);
        $next_num = ($last_result['last_num'] ?? 0) + 1;
        
        if ($next_num > 9999) {
            echo json_encode(['success' => false, 'message' => 'Maksimum items untuk kategori ini sudah tercapai']);
            exit;
        }
        
        $kode_auto = $prefix . "-" . $kategori_rak . str_pad($next_num, 4, '0', STR_PAD_LEFT);
        
        echo json_encode([
            'success' => true, 
            'code' => $kode_auto,
            'next_number' => $next_num
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>