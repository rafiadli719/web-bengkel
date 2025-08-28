<?php
// File: ajax-search-workorder-dynamic.php
// AJAX endpoint untuk pencarian workorder dinamis

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get input parameter
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;

if(strlen($query) < 2) {
    echo json_encode(['success' => false, 'message' => 'Query minimal 2 karakter']);
    exit;
}

try {
    // Escape input untuk mencegah SQL injection
    $query = mysqli_real_escape_string($koneksi, $query);
    
    // Search query dengan relevance scoring
    $search_query = "SELECT 
                        wo.kode_wo,
                        wo.nama_wo,
                        wo.harga,
                        wo.waktu,
                        wo.keterangan,
                        wo.status,
                        (
                            CASE 
                                WHEN wo.kode_wo LIKE '$query%' THEN 100
                                WHEN wo.nama_wo LIKE '$query%' THEN 90
                                WHEN wo.kode_wo LIKE '%$query%' THEN 80
                                WHEN wo.nama_wo LIKE '%$query%' THEN 70
                                WHEN wo.keterangan LIKE '%$query%' THEN 60
                                WHEN CAST(wo.harga AS CHAR) LIKE '%$query%' THEN 50
                                ELSE 0
                            END
                        ) as relevance_score
                     FROM tbworkorderheader wo
                     WHERE wo.status = '0'
                       AND (
                           wo.kode_wo LIKE '%$query%' OR
                           wo.nama_wo LIKE '%$query%' OR
                           wo.keterangan LIKE '%$query%' OR
                           CAST(wo.harga AS CHAR) LIKE '%$query%'
                       )
                     ORDER BY relevance_score DESC, wo.harga ASC, wo.nama_wo ASC
                     LIMIT $limit";
    
    $result = mysqli_query($koneksi, $search_query);
    $workorder_list = [];
    
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $harga_formatted = 'Rp ' . number_format($row['harga'], 0, ',', '.');
            $waktu_formatted = $row['waktu'] . ' min';
            
            // Category based on price range
            $price_category = '';
            if($row['harga'] < 50000) {
                $price_category = 'Ekonomis';
            } elseif($row['harga'] < 150000) {
                $price_category = 'Standar';
            } elseif($row['harga'] < 300000) {
                $price_category = 'Premium';
            } else {
                $price_category = 'Lengkap';
            }
            
            $workorder_list[] = [
                'kode_wo' => $row['kode_wo'],
                'nama_wo' => $row['nama_wo'],
                'harga' => $row['harga'],
                'harga_formatted' => $harga_formatted,
                'waktu' => $row['waktu'],
                'waktu_formatted' => $waktu_formatted,
                'keterangan' => $row['keterangan'],
                'price_category' => $price_category,
                'relevance_score' => $row['relevance_score'],
                'display_text' => $row['kode_wo'] . ' - ' . $row['nama_wo'] . ' | ' . $harga_formatted . ' | ' . $waktu_formatted
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'total_found' => count($workorder_list),
        'workorder_list' => $workorder_list
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>