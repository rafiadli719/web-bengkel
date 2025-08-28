<?php
// File: ajax-validate-keluhan.php
// AJAX endpoint untuk validasi keluhan (duplicate check)

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get input parameters
$kode_keluhan = isset($_POST['kode_keluhan']) ? trim($_POST['kode_keluhan']) : '';
$nama_keluhan = isset($_POST['nama_keluhan']) ? trim($_POST['nama_keluhan']) : '';
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if(empty($kode_keluhan) || empty($nama_keluhan)) {
    echo json_encode(['success' => false, 'message' => 'Parameter required']);
    exit;
}

try {
    $errors = [];
    
    // Check duplicate kode keluhan
    $kode_query = "SELECT COUNT(*) as count FROM tbmaster_keluhan 
                   WHERE kode_keluhan = '$kode_keluhan'";
    if($id > 0) {
        $kode_query .= " AND id != $id";
    }
    
    $kode_result = mysqli_query($koneksi, $kode_query);
    $kode_data = mysqli_fetch_assoc($kode_result);
    
    if($kode_data['count'] > 0) {
        $errors[] = 'Kode keluhan sudah digunakan';
    }
    
    // Check duplicate nama keluhan
    $nama_query = "SELECT COUNT(*) as count FROM tbmaster_keluhan 
                   WHERE nama_keluhan = '$nama_keluhan'";
    if($id > 0) {
        $nama_query .= " AND id != $id";
    }
    
    $nama_result = mysqli_query($koneksi, $nama_query);
    $nama_data = mysqli_fetch_assoc($nama_result);
    
    if($nama_data['count'] > 0) {
        $errors[] = 'Nama keluhan sudah digunakan';
    }
    
    // Suggest workorder based on keluhan name
    $suggested_workorder = null;
    $keluhan_lower = strtolower($nama_keluhan);
    
    if(strpos($keluhan_lower, 'mesin') !== false || 
       strpos($keluhan_lower, 'oli') !== false || 
       strpos($keluhan_lower, 'mogok') !== false ||
       strpos($keluhan_lower, 'starter') !== false) {
        $suggested_workorder = 'WO0005'; // Servis Lengkap
    } elseif(strpos($keluhan_lower, 'rem') !== false) {
        $suggested_workorder = 'WO0002'; // Ganti Kampas
    } elseif(strpos($keluhan_lower, 'boros') !== false) {
        $suggested_workorder = 'WO0003'; // Tune Up
    } else {
        $suggested_workorder = 'WO0001'; // Servis Standar
    }
    
    echo json_encode([
        'success' => true,
        'valid' => empty($errors),
        'errors' => $errors,
        'suggested_workorder' => $suggested_workorder
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>