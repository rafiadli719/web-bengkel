<?php
session_start();
include "../../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    die("Unauthorized access");
}

$response = array('success' => false, 'message' => '');

try {
    $no_service = $_POST['no_service'] ?? '';
    $status_baru = $_POST['status_baru'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $catatan = $_POST['catatan'] ?? '';
    
    if(empty($no_service) || empty($status_baru)) {
        throw new Exception('Nomor service dan status harus diisi');
    }
    
    // Update status antrian
    $update_query = "UPDATE tb_antrian_servis SET 
                    status_antrian = '$status_baru'";
    
    if($status_baru == 'diproses' && !empty($jam_mulai)) {
        $update_query .= ", jam_mulai = '$jam_mulai'";
    }
    
    if($status_baru == 'selesai' && !empty($jam_selesai)) {
        $update_query .= ", jam_selesai = '$jam_selesai'";
    }
    
    if(!empty($catatan)) {
        $update_query .= ", catatan = '$catatan'";
    }
    
    $update_query .= ", updated_at = CURRENT_TIMESTAMP WHERE no_service = '$no_service'";
    
    if(mysqli_query($koneksi, $update_query)) {
        $response['success'] = true;
        $response['message'] = 'Status antrian berhasil diupdate';
        
        // Log aktivitas
        $user_id = $_SESSION['_iduser'];
        $user_nama = $_SESSION['username'] ?? 'Unknown';
        
        // Ambil nomor antrian untuk log
        $get_antrian = mysqli_query($koneksi, "SELECT no_antrian FROM tb_antrian_servis WHERE no_service = '$no_service'");
        $antrian_data = mysqli_fetch_array($get_antrian);
        $no_antrian = $antrian_data['no_antrian'] ?? '';
        
        $log_query = "INSERT INTO tb_log_antrian 
                      (no_antrian, no_service, aktivitas, keterangan, user_id, user_nama) 
                      VALUES 
                      ('$no_antrian', '$no_service', 'Update Status', 'Status diubah ke: $status_baru', '$user_id', '$user_nama')";
        mysqli_query($koneksi, $log_query);
        
        // Update status di tabel service juga
        mysqli_query($koneksi, "UPDATE tblservice SET status_servis = '$status_baru' WHERE no_service = '$no_service'");
        
    } else {
        throw new Exception('Gagal update status antrian: ' . mysqli_error($koneksi));
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
