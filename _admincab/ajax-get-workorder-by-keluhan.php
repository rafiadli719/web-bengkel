<?php
// File: ajax-get-workorder-by-keluhan.php
// AJAX endpoint untuk mendapatkan workorder berdasarkan keluhan

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get input parameter
$keluhan = isset($_GET['keluhan']) ? trim($_GET['keluhan']) : '';

if(empty($keluhan)) {
    echo json_encode(['success' => false, 'message' => 'Keluhan parameter required']);
    exit;
}

try {
    // Escape input untuk mencegah SQL injection
    $keluhan = mysqli_real_escape_string($koneksi, $keluhan);
    
    // Strategy 1: Cari exact match berdasarkan kode keluhan atau nama keluhan
    $query_exact = "SELECT 
                        mk.kode_keluhan,
                        mk.nama_keluhan,
                        mk.workorder_default,
                        mk.tingkat_prioritas,
                        wo.kode_wo,
                        wo.nama_wo,
                        wo.harga,
                        wo.waktu,
                        wo.keterangan
                    FROM tbmaster_keluhan mk
                    INNER JOIN tbworkorderheader wo ON mk.workorder_default = wo.kode_wo
                    WHERE mk.status_aktif = '1'
                      AND mk.workorder_default IS NOT NULL
                      AND (mk.kode_keluhan = '$keluhan' OR mk.nama_keluhan = '$keluhan')
                    LIMIT 1";
    
    $result_exact = mysqli_query($koneksi, $query_exact);
    
    if($result_exact && mysqli_num_rows($result_exact) > 0) {
        $workorder = mysqli_fetch_assoc($result_exact);
        echo json_encode([
            'success' => true,
            'workorder' => $workorder,
            'match_type' => 'exact'
        ]);
        exit;
    }
    
    // Strategy 2: Cari partial match berdasarkan nama keluhan
    $query_partial = "SELECT 
                          mk.kode_keluhan,
                          mk.nama_keluhan,
                          mk.workorder_default,
                          mk.tingkat_prioritas,
                          wo.kode_wo,
                          wo.nama_wo,
                          wo.harga,
                          wo.waktu,
                          wo.keterangan
                      FROM tbmaster_keluhan mk
                      INNER JOIN tbworkorderheader wo ON mk.workorder_default = wo.kode_wo
                      WHERE mk.status_aktif = '1'
                        AND mk.workorder_default IS NOT NULL
                        AND mk.nama_keluhan LIKE '%$keluhan%'
                      ORDER BY mk.tingkat_prioritas DESC, 
                               CHAR_LENGTH(mk.nama_keluhan) ASC
                      LIMIT 1";
    
    $result_partial = mysqli_query($koneksi, $query_partial);
    
    if($result_partial && mysqli_num_rows($result_partial) > 0) {
        $workorder = mysqli_fetch_assoc($result_partial);
        echo json_encode([
            'success' => true,
            'workorder' => $workorder,
            'match_type' => 'partial'
        ]);
        exit;
    }
    
    // Strategy 3: Cari berdasarkan keyword dalam keluhan
    $keywords = ['mesin', 'oli', 'rem', 'starter', 'mogok', 'suara', 'bunyi', 'getaran', 'overheating', 'boros'];
    $workorder_mapping = [
        'mesin' => 'WO0005',     // Servis Lengkap
        'oli' => 'WO0005',       // Servis Lengkap
        'rem' => 'WO0002',       // Ganti Kampas
        'starter' => 'WO0005',   // Servis Lengkap
        'mogok' => 'WO0005',     // Servis Lengkap
        'suara' => 'WO0001',     // Servis Standar
        'bunyi' => 'WO0001',     // Servis Standar
        'getaran' => 'WO0001',   // Servis Standar
        'overheating' => 'WO0005', // Servis Lengkap
        'boros' => 'WO0003'      // Tune Up
    ];
    
    $keluhan_lower = strtolower($keluhan);
    $suggested_wo = null;
    
    foreach($keywords as $keyword) {
        if(strpos($keluhan_lower, $keyword) !== false) {
            $suggested_wo = $workorder_mapping[$keyword];
            break;
        }
    }
    
    if($suggested_wo) {
        $query_suggested = "SELECT 
                                kode_wo,
                                nama_wo,
                                harga,
                                waktu,
                                keterangan
                            FROM tbworkorderheader 
                            WHERE kode_wo = '$suggested_wo'
                            LIMIT 1";
        
        $result_suggested = mysqli_query($koneksi, $query_suggested);
        
        if($result_suggested && mysqli_num_rows($result_suggested) > 0) {
            $workorder = mysqli_fetch_assoc($result_suggested);
            echo json_encode([
                'success' => true,
                'workorder' => $workorder,
                'match_type' => 'keyword_based'
            ]);
            exit;
        }
    }
    
    // Strategy 4: Default fallback - gunakan Servis Standar untuk keluhan umum
    $query_default = "SELECT 
                          kode_wo,
                          nama_wo,
                          harga,
                          waktu,
                          keterangan
                      FROM tbworkorderheader 
                      WHERE kode_wo = 'WO0001'
                      LIMIT 1";
    
    $result_default = mysqli_query($koneksi, $query_default);
    
    if($result_default && mysqli_num_rows($result_default) > 0) {
        $workorder = mysqli_fetch_assoc($result_default);
        echo json_encode([
            'success' => true,
            'workorder' => $workorder,
            'match_type' => 'default'
        ]);
        exit;
    }
    
    // Jika semua strategy gagal
    echo json_encode([
        'success' => false, 
        'message' => 'No suitable workorder found for this complaint'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>