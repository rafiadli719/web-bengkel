<?php
// File: ajax-search-keluhan-dynamic.php
// AJAX endpoint untuk pencarian keluhan dinamis

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
                        mk.kode_keluhan,
                        mk.nama_keluhan,
                        mk.kategori,
                        mk.tingkat_prioritas,
                        mk.estimasi_waktu,
                        mk.workorder_default,
                        wo.nama_wo,
                        wo.harga as wo_harga,
                        wo.waktu as wo_waktu,
                        (
                            CASE 
                                WHEN mk.kode_keluhan LIKE '$query%' THEN 100
                                WHEN mk.nama_keluhan LIKE '$query%' THEN 90
                                WHEN mk.kode_keluhan LIKE '%$query%' THEN 80
                                WHEN mk.nama_keluhan LIKE '%$query%' THEN 70
                                WHEN mk.kategori LIKE '%$query%' THEN 60
                                WHEN mk.deskripsi LIKE '%$query%' THEN 50
                                ELSE 0
                            END
                        ) as relevance_score
                     FROM tbmaster_keluhan mk
                     LEFT JOIN tbworkorderheader wo ON mk.workorder_default = wo.kode_wo
                     WHERE mk.status_aktif = '1'
                       AND (
                           mk.kode_keluhan LIKE '%$query%' OR
                           mk.nama_keluhan LIKE '%$query%' OR
                           mk.kategori LIKE '%$query%' OR
                           mk.deskripsi LIKE '%$query%'
                       )
                     ORDER BY relevance_score DESC, mk.tingkat_prioritas DESC, mk.nama_keluhan ASC
                     LIMIT $limit";
    
    $result = mysqli_query($koneksi, $search_query);
    $keluhan_list = [];
    
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $prioritas_badge = '';
            $prioritas_class = '';
            
            switch($row['tingkat_prioritas']) {
                case 'darurat': 
                    $prioritas_badge = '[DARURAT]'; 
                    $prioritas_class = 'danger';
                    break;
                case 'tinggi': 
                    $prioritas_badge = '[TINGGI]'; 
                    $prioritas_class = 'warning';
                    break;
                case 'sedang': 
                    $prioritas_badge = '[SEDANG]'; 
                    $prioritas_class = 'info';
                    break;
                case 'rendah': 
                    $prioritas_badge = '[RENDAH]'; 
                    $prioritas_class = 'success';
                    break;
            }
            
            $keluhan_list[] = [
                'kode_keluhan' => $row['kode_keluhan'],
                'nama_keluhan' => $row['nama_keluhan'],
                'kategori' => $row['kategori'],
                'tingkat_prioritas' => $row['tingkat_prioritas'],
                'prioritas_badge' => $prioritas_badge,
                'prioritas_class' => $prioritas_class,
                'estimasi_waktu' => $row['estimasi_waktu'],
                'workorder_default' => $row['workorder_default'],
                'nama_wo' => $row['nama_wo'],
                'wo_harga' => $row['wo_harga'],
                'wo_waktu' => $row['wo_waktu'],
                'relevance_score' => $row['relevance_score'],
                'display_text' => $row['kode_keluhan'] . ' - ' . $row['nama_keluhan'] . ' ' . $prioritas_badge . ' (' . $row['kategori'] . ')'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'query' => $query,
        'total_found' => count($keluhan_list),
        'keluhan_list' => $keluhan_list
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>