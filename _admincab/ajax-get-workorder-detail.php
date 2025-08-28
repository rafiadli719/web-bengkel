<?php
// File: ajax-get-workorder-detail.php
// AJAX endpoint untuk mendapatkan detail workorder berdasarkan kode

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get input parameter
$kode_wo = isset($_GET['kode_wo']) ? trim($_GET['kode_wo']) : '';

if(empty($kode_wo)) {
    echo json_encode(['success' => false, 'message' => 'Kode WorkOrder parameter required']);
    exit;
}

try {
    // Escape input untuk mencegah SQL injection
    $kode_wo = mysqli_real_escape_string($koneksi, $kode_wo);
    
    // Query detail workorder
    $query = "SELECT 
                  kode_wo,
                  nama_wo,
                  harga,
                  waktu,
                  keterangan,
                  status
              FROM tbworkorderheader 
              WHERE kode_wo = '$kode_wo'
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $workorder = mysqli_fetch_assoc($result);
        
        // Get detail items dari workorder
        $query_detail = "SELECT 
                             wd.kode_barang,
                             wd.tipe,
                             wd.harga,
                             wd.jumlah,
                             wd.total,
                             CASE 
                                 WHEN wd.tipe = '1' THEN wh.nama_wo
                                 ELSE vi.namaitem
                             END as nama_item
                         FROM tbworkorderdetail wd
                         LEFT JOIN tbworkorderheader wh ON wd.kode_barang = wh.kode_wo AND wd.tipe = '1'
                         LEFT JOIN view_cari_item vi ON wd.kode_barang = vi.noitem AND wd.tipe = '2'
                         WHERE wd.kode_wo = '$kode_wo'
                         ORDER BY wd.tipe ASC, wd.kode_barang ASC";
        
        $result_detail = mysqli_query($koneksi, $query_detail);
        $detail_items = [];
        
        if($result_detail) {
            while($row_detail = mysqli_fetch_assoc($result_detail)) {
                $detail_items[] = $row_detail;
            }
        }
        
        $workorder['detail_items'] = $detail_items;
        
        echo json_encode([
            'success' => true,
            'workorder' => $workorder
        ]);
        
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'WorkOrder tidak ditemukan'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>