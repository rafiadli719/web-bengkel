<?php
session_start();
include "../../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    die("Unauthorized access");
}

$response = array('success' => false, 'message' => '', 'antrian_id' => '');

try {
    $no_antrian = $_POST['no_antrian'] ?? '';
    $no_service = $_POST['no_service'] ?? '';
    $prioritas = $_POST['prioritas'] ?? 'normal';
    $estimasi_waktu = $_POST['estimasi_waktu'] ?? 0;
    $catatan = $_POST['catatan'] ?? '';
    
    if(empty($no_antrian) || empty($no_service)) {
        throw new Exception('Nomor antrian dan nomor service harus diisi');
    }
    
    $tanggal = date('Y-m-d');
    $jam_ambil = date('H:i:s');
    
    // Cek apakah sudah ada antrian untuk service ini
    $check_query = "SELECT id FROM tb_antrian_servis WHERE no_service = '$no_service'";
    $check_result = mysqli_query($koneksi, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        // Update antrian yang sudah ada
        $update_query = "UPDATE tb_antrian_servis SET 
                        no_antrian = '$no_antrian',
                        prioritas = '$prioritas',
                        estimasi_waktu = '$estimasi_waktu',
                        catatan = '$catatan',
                        updated_at = CURRENT_TIMESTAMP
                        WHERE no_service = '$no_service'";
        
        if(mysqli_query($koneksi, $update_query)) {
            $response['success'] = true;
            $response['message'] = 'Antrian berhasil diupdate';
            
            // Ambil ID antrian
            $get_id = mysqli_query($koneksi, "SELECT id FROM tb_antrian_servis WHERE no_service = '$no_service'");
            $antrian_data = mysqli_fetch_array($get_id);
            $response['antrian_id'] = $antrian_data['id'];
        } else {
            throw new Exception('Gagal update antrian: ' . mysqli_error($koneksi));
        }
    } else {
        // Insert antrian baru
        $insert_query = "INSERT INTO tb_antrian_servis 
                        (no_antrian, no_service, tanggal, jam_ambil, status_antrian, prioritas, estimasi_waktu, catatan) 
                        VALUES 
                        ('$no_antrian', '$no_service', '$tanggal', '$jam_ambil', 'menunggu', '$prioritas', '$estimasi_waktu', '$catatan')";
        
        if(mysqli_query($koneksi, $insert_query)) {
            $response['success'] = true;
            $response['message'] = 'Antrian berhasil disimpan';
            $response['antrian_id'] = mysqli_insert_id($koneksi);
        } else {
            throw new Exception('Gagal simpan antrian: ' . mysqli_error($koneksi));
        }
    }
    
    // Log aktivitas
    $user_id = $_SESSION['_iduser'];
    $user_nama = $_SESSION['username'] ?? 'Unknown';
    $log_query = "INSERT INTO tb_log_antrian 
                  (no_antrian, no_service, aktivitas, keterangan, user_id, user_nama) 
                  VALUES 
                  ('$no_antrian', '$no_service', 'Simpan Antrian', 'Antrian berhasil disimpan', '$user_id', '$user_nama')";
    mysqli_query($koneksi, $log_query);
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
