<?php
// File: ajax-hapus-keluhan-workorder.php
// AJAX endpoint untuk menghapus keluhan dan workorder terkait

session_start();
header('Content-Type: application/json');

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get input parameter
$keluhan_id = isset($_POST['keluhan_id']) ? intval($_POST['keluhan_id']) : 0;

if($keluhan_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid keluhan ID']);
    exit;
}

try {
    // Begin transaction
    mysqli_autocommit($koneksi, false);
    
    // Get keluhan data first
    $query_keluhan = "SELECT no_service, auto_workorder FROM tbservis_keluhan_status WHERE id = $keluhan_id";
    $result_keluhan = mysqli_query($koneksi, $query_keluhan);
    
    if(!$result_keluhan || mysqli_num_rows($result_keluhan) == 0) {
        mysqli_rollback($koneksi);
        echo json_encode(['success' => false, 'message' => 'Keluhan tidak ditemukan']);
        exit;
    }
    
    $keluhan_data = mysqli_fetch_assoc($result_keluhan);
    $no_service = $keluhan_data['no_service'];
    $auto_workorder = $keluhan_data['auto_workorder'];
    
    // Delete keluhan
    $delete_keluhan = "DELETE FROM tbservis_keluhan_status WHERE id = $keluhan_id";
    if(!mysqli_query($koneksi, $delete_keluhan)) {
        mysqli_rollback($koneksi);
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus keluhan: ' . mysqli_error($koneksi)]);
        exit;
    }
    
    // Check jika masih ada keluhan lain yang menggunakan workorder yang sama
    if($auto_workorder) {
        $check_other_keluhan = "SELECT COUNT(*) as count 
                               FROM tbservis_keluhan_status 
                               WHERE no_service = '$no_service' 
                                 AND auto_workorder = '$auto_workorder'
                                 AND id != $keluhan_id";
        $result_check = mysqli_query($koneksi, $check_other_keluhan);
        $count_data = mysqli_fetch_assoc($result_check);
        
        // Jika tidak ada keluhan lain yang menggunakan workorder ini, hapus workorder
        if($count_data['count'] == 0) {
            // Hapus workorder detail items terlebih dahulu
            $delete_jasa = "DELETE FROM tblservis_jasa 
                           WHERE no_service = '$no_service' 
                             AND no_item IN (
                                 SELECT kode_barang FROM tbworkorderdetail 
                                 WHERE kode_wo = '$auto_workorder' AND tipe = '1'
                             )";
            mysqli_query($koneksi, $delete_jasa);
            
            $delete_barang = "DELETE FROM tblservis_barang 
                             WHERE no_service = '$no_service' 
                               AND no_item IN (
                                   SELECT kode_barang FROM tbworkorderdetail 
                                   WHERE kode_wo = '$auto_workorder' AND tipe = '2'
                               )";
            mysqli_query($koneksi, $delete_barang);
            
            // Hapus workorder
            $delete_workorder = "DELETE FROM tbservis_workorder 
                               WHERE no_service = '$no_service' 
                                 AND kode_wo = '$auto_workorder'";
            if(!mysqli_query($koneksi, $delete_workorder)) {
                mysqli_rollback($koneksi);
                echo json_encode(['success' => false, 'message' => 'Gagal menghapus workorder: ' . mysqli_error($koneksi)]);
                exit;
            }
        }
    }
    
    // Commit transaction
    mysqli_commit($koneksi);
    mysqli_autocommit($koneksi, true);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Keluhan dan workorder berhasil dihapus'
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    mysqli_autocommit($koneksi, true);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>