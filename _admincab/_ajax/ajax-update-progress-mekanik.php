<?php
session_start();
include "../../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    die("Unauthorized access");
}

$response = array('success' => false, 'message' => '');

try {
    $no_service = $_POST['no_service'] ?? '';
    $no_antrian = $_POST['no_antrian'] ?? '';
    $mekanik_id = $_POST['mekanik_id'] ?? '';
    $nama_mekanik = $_POST['nama_mekanik'] ?? '';
    $jenis_mekanik = $_POST['jenis_mekanik'] ?? '';
    $persen_kerja = $_POST['persen_kerja'] ?? 0;
    $status_kerja = $_POST['status_kerja'] ?? '';
    $jam_mulai = $_POST['jam_mulai'] ?? '';
    $jam_selesai = $_POST['jam_selesai'] ?? '';
    $catatan_kerja = $_POST['catatan_kerja'] ?? '';
    
    if(empty($no_service) || empty($mekanik_id) || empty($nama_mekanik)) {
        throw new Exception('Data mekanik tidak lengkap');
    }
    
    // Cek apakah sudah ada progress untuk mekanik ini
    $check_query = "SELECT id FROM tb_progress_mekanik 
                    WHERE no_service = '$no_service' AND id_mekanik = '$mekanik_id'";
    $check_result = mysqli_query($koneksi, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        // Update progress yang sudah ada
        $update_query = "UPDATE tb_progress_mekanik SET 
                        persen_kerja = '$persen_kerja',
                        status_kerja = '$status_kerja'";
        
        if($status_kerja == 'sedang_bekerja' && !empty($jam_mulai)) {
            $update_query .= ", jam_mulai = '$jam_mulai'";
        }
        
        if($status_kerja == 'selesai' && !empty($jam_selesai)) {
            $update_query .= ", jam_selesai = '$jam_selesai'";
        }
        
        if(!empty($catatan_kerja)) {
            $update_query .= ", catatan_kerja = '$catatan_kerja'";
        }
        
        $update_query .= ", updated_at = CURRENT_TIMESTAMP 
                        WHERE no_service = '$no_service' AND id_mekanik = '$mekanik_id'";
        
        if(mysqli_query($koneksi, $update_query)) {
            $response['success'] = true;
            $response['message'] = 'Progress mekanik berhasil diupdate';
        } else {
            throw new Exception('Gagal update progress mekanik: ' . mysqli_error($koneksi));
        }
    } else {
        // Insert progress baru
        $insert_query = "INSERT INTO tb_progress_mekanik 
                        (no_service, no_antrian, id_mekanik, jenis_kerja, 
                         persen_kerja, status_kerja, jam_mulai, jam_selesai, catatan_kerja) 
                        VALUES 
                        ('$no_service', '$no_antrian', '$mekanik_id', '$jenis_mekanik',
                         '$persen_kerja', '$status_kerja', '$jam_mulai', '$jam_selesai', '$catatan_kerja')";
        
        if(mysqli_query($koneksi, $insert_query)) {
            $response['success'] = true;
            $response['message'] = 'Progress mekanik berhasil disimpan';
        } else {
            throw new Exception('Gagal simpan progress mekanik: ' . mysqli_error($koneksi));
        }
    }
    
    // Log aktivitas
    $user_id = $_SESSION['_iduser'];
    $log_query = "INSERT INTO tb_log_antrian 
                  (no_antrian, no_service, aksi, keterangan, user_id) 
                  VALUES 
                  ('$no_antrian', '$no_service', 'update_progress', 
                   'Mekanik: $nama_mekanik - Progress: $persen_kerja% - Status: $status_kerja', 
                   '$user_id')";
    mysqli_query($koneksi, $log_query);
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?>
