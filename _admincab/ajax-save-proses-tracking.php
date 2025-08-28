<?php
// File: ajax-save-proses-tracking.php
session_start();
include "../config/koneksi.php";
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

if(!isset($_POST['keluhan_id']) || !isset($_POST['proses_id'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

$keluhan_id = $_POST['keluhan_id'];
$proses_id = $_POST['proses_id'];
$status_proses = $_POST['status_proses'];
$mekanik_id = isset($_POST['mekanik_id']) ? $_POST['mekanik_id'] : null;
$catatan = isset($_POST['catatan']) ? $_POST['catatan'] : '';

try {
    // Check if tracking record exists
    $check_sql = mysqli_query($koneksi,"SELECT id FROM tbservis_keluhan_tracking 
                                       WHERE keluhan_id='$keluhan_id' AND proses_id='$proses_id'");
    
    $current_time = date('Y-m-d H:i:s');
    
    if(mysqli_num_rows($check_sql) > 0) {
        // Update existing record
        $tracking_data = mysqli_fetch_array($check_sql);
        $tracking_id = $tracking_data['id'];
        
        // Prepare update fields
        $update_fields = [];
        $update_fields[] = "status_proses='$status_proses'";
        $update_fields[] = "catatan='" . mysqli_real_escape_string($koneksi, $catatan) . "'";
        $update_fields[] = "updated_at='$current_time'";
        
        if($mekanik_id) {
            $update_fields[] = "mekanik_id='$mekanik_id'";
        }
        
        // Set waktu based on status
        if($status_proses == 'dikerjakan' && !$tracking_data['waktu_mulai']) {
            $update_fields[] = "waktu_mulai='$current_time'";
        } elseif($status_proses == 'selesai') {
            $update_fields[] = "waktu_selesai='$current_time'";
            if(!$tracking_data['waktu_mulai']) {
                $update_fields[] = "waktu_mulai='$current_time'";
            }
        }
        
        $update_query = "UPDATE tbservis_keluhan_tracking SET " . implode(', ', $update_fields) . " WHERE id='$tracking_id'";
        mysqli_query($koneksi, $update_query);
        
    } else {
        // Insert new record
        $waktu_mulai = 'NULL';
        $waktu_selesai = 'NULL';
        
        if($status_proses == 'dikerjakan' || $status_proses == 'selesai') {
            $waktu_mulai = "'$current_time'";
        }
        
        if($status_proses == 'selesai') {
            $waktu_selesai = "'$current_time'";
        }
        
        $mekanik_value = $mekanik_id ? "'$mekanik_id'" : 'NULL';
        
        $insert_query = "INSERT INTO tbservis_keluhan_tracking 
                        (keluhan_id, proses_id, status_proses, mekanik_id, waktu_mulai, waktu_selesai, catatan, created_at, updated_at) 
                        VALUES 
                        ('$keluhan_id', '$proses_id', '$status_proses', $mekanik_value, $waktu_mulai, $waktu_selesai, '" . mysqli_real_escape_string($koneksi, $catatan) . "', '$current_time', '$current_time')";
        
        mysqli_query($koneksi, $insert_query);
    }
    
    // Update overall keluhan status based on proses completion
    updateKeluhanStatus($koneksi, $keluhan_id);
    
    echo json_encode(['success' => true, 'message' => 'Proses berhasil disimpan']);
    
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function updateKeluhanStatus($koneksi, $keluhan_id) {
    // Get keluhan info and related master
    $sql_keluhan = mysqli_query($koneksi,"SELECT k.*, mk.kode_keluhan 
                                         FROM tbservis_keluhan_status k 
                                         LEFT JOIN tbmaster_keluhan mk ON k.keluhan LIKE CONCAT('%', mk.nama_keluhan, '%')
                                         WHERE k.id='$keluhan_id'");
    
    $keluhan_data = mysqli_fetch_array($sql_keluhan);
    
    if($keluhan_data && $keluhan_data['kode_keluhan']) {
        // Count total proses and completed proses
        $total_proses_sql = mysqli_query($koneksi,"SELECT COUNT(*) as total 
                                                  FROM tbkeluhan_proses 
                                                  WHERE kode_keluhan='" . $keluhan_data['kode_keluhan'] . "' AND status_aktif='1'");
        $total_proses = mysqli_fetch_array($total_proses_sql)['total'];
        
        $completed_proses_sql = mysqli_query($koneksi,"SELECT COUNT(*) as completed 
                                                      FROM tbservis_keluhan_tracking 
                                                      WHERE keluhan_id='$keluhan_id' AND status_proses='selesai'");
        $completed_proses = mysqli_fetch_array($completed_proses_sql)['completed'];
        
        // Determine status
        $new_status = 'datang';
        if($completed_proses > 0) {
            if($completed_proses >= $total_proses) {
                $new_status = 'selesai';
            } else {
                $new_status = 'diproses';
            }
        }
        
        // Update keluhan status
        mysqli_query($koneksi,"UPDATE tbservis_keluhan_status 
                              SET status_pengerjaan='$new_status', updated_at=NOW() 
                              WHERE id='$keluhan_id'");
    }
}
?>