<?php
// File: process-keluhan.php
// Handler untuk menambah keluhan service dengan integrasi master keluhan

session_start();
include "../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

// Function untuk mencari master keluhan yang cocok
function findMatchingKeluhan($koneksi, $keluhan_text) {
    $keluhan_text = strtolower(trim($keluhan_text));
    
    // Cari exact match dulu
    $sql = mysqli_query($koneksi,"SELECT kode_keluhan, nama_keluhan 
                                 FROM tbmaster_keluhan 
                                 WHERE status_aktif='1' 
                                 AND LOWER(nama_keluhan) = '$keluhan_text'
                                 LIMIT 1");
    
    if(mysqli_num_rows($sql) > 0) {
        return mysqli_fetch_array($sql);
    }
    
    // Cari partial match
    $sql = mysqli_query($koneksi,"SELECT kode_keluhan, nama_keluhan 
                                 FROM tbmaster_keluhan 
                                 WHERE status_aktif='1' 
                                 AND LOWER(nama_keluhan) LIKE '%$keluhan_text%'
                                 ORDER BY 
                                 CASE 
                                    WHEN LOWER(nama_keluhan) LIKE '$keluhan_text%' THEN 1
                                    WHEN LOWER(nama_keluhan) LIKE '%$keluhan_text%' THEN 2
                                    ELSE 3
                                 END
                                 LIMIT 1");
    
    if(mysqli_num_rows($sql) > 0) {
        return mysqli_fetch_array($sql);
    }
    
    // Cari berdasarkan kata kunci
    $keywords = explode(' ', $keluhan_text);
    foreach($keywords as $keyword) {
        if(strlen($keyword) > 3) {
            $sql = mysqli_query($koneksi,"SELECT kode_keluhan, nama_keluhan 
                                         FROM tbmaster_keluhan 
                                         WHERE status_aktif='1' 
                                         AND (LOWER(nama_keluhan) LIKE '%$keyword%' 
                                              OR LOWER(deskripsi) LIKE '%$keyword%')
                                         ORDER BY tingkat_prioritas DESC
                                         LIMIT 1");
            
            if(mysqli_num_rows($sql) > 0) {
                return mysqli_fetch_array($sql);
            }
        }
    }
    
    return null;
}

// Function untuk create tracking proses
function createKeluhanTracking($koneksi, $no_service, $keluhan_id, $kode_keluhan) {
    if(!$kode_keluhan) return;
    
    // Get all proses for this keluhan
    $sql_proses = mysqli_query($koneksi,"SELECT id FROM tbkeluhan_proses 
                                        WHERE kode_keluhan='$kode_keluhan' 
                                        AND status_aktif='1' 
                                        ORDER BY urutan ASC");
    
    while($proses = mysqli_fetch_array($sql_proses)) {
        mysqli_query($koneksi,"INSERT INTO tbservis_keluhan_tracking 
                              (no_service, keluhan_id, proses_id, status_proses, created_at, updated_at) 
                              VALUES 
                              ('$no_service', '$keluhan_id', '{$proses['id']}', 'pending', NOW(), NOW())");
    }
}

// Process form submission
if(isset($_POST['btnaddkeluhan'])) {
    $no_service = $_POST['txtnosrv'] ?? '';
    $keluhan = trim($_POST['txtkeluhan'] ?? '');
    
    if(empty($no_service) || empty($keluhan)) {
        echo "<script>alert('No service dan keluhan harus diisi!'); history.back();</script>";
        exit;
    }
    
    // Sanitize input
    $keluhan = mysqli_real_escape_string($koneksi, $keluhan);
    
    try {
        // Find matching master keluhan
        $master_keluhan = findMatchingKeluhan($koneksi, $keluhan);
        
        // Insert ke tbservis_keluhan_status
        $insert_sql = "INSERT INTO tbservis_keluhan_status 
                      (no_service, keluhan, status_pengerjaan, created_at, updated_at) 
                      VALUES 
                      ('$no_service', '$keluhan', 'datang', NOW(), NOW())";
        
        if(mysqli_query($koneksi, $insert_sql)) {
            $keluhan_id = mysqli_insert_id($koneksi);
            
            // Create tracking if master keluhan found
            if($master_keluhan) {
                createKeluhanTracking($koneksi, $no_service, $keluhan_id, $master_keluhan['kode_keluhan']);
                
                // Update keluhan text to include master info
                $updated_keluhan = $keluhan;
                if(stripos($keluhan, $master_keluhan['nama_keluhan']) === false) {
                    $updated_keluhan = $master_keluhan['nama_keluhan'] . ' - ' . $keluhan;
                }
                
                mysqli_query($koneksi,"UPDATE tbservis_keluhan_status 
                                      SET keluhan='$updated_keluhan' 
                                      WHERE id='$keluhan_id'");
            }
            
            // Redirect back with success
            $redirect_page = $_POST['redirect'] ?? 'service-add.php';
            header("Location: $redirect_page?noserv=$no_service&success=keluhan_added");
            exit;
            
        } else {
            throw new Exception("Error inserting keluhan: " . mysqli_error($koneksi));
        }
        
    } catch(Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
}

// Process update status keluhan
if(isset($_POST['btnupdatestatuskeluhan'])) {
    $no_service = $_POST['txtnosrv'] ?? '';
    $keluhan_id = $_POST['keluhan_id'] ?? '';
    $status_keluhan = $_POST['status_keluhan'] ?? '';
    $keterangan_keluhan = $_POST['keterangan_keluhan'] ?? '';
    
    if(empty($keluhan_id) || empty($status_keluhan)) {
        echo "<script>alert('Parameter tidak lengkap!'); history.back();</script>";
        exit;
    }
    
    try {
        $keterangan_keluhan = mysqli_real_escape_string($koneksi, $keterangan_keluhan);
        
        $update_sql = "UPDATE tbservis_keluhan_status SET 
                      status_pengerjaan='$status_keluhan',
                      keterangan_tidak_selesai='$keterangan_keluhan',
                      updated_at=NOW() 
                      WHERE id='$keluhan_id'";
        
        if(mysqli_query($koneksi, $update_sql)) {
            // Update tracking if needed
            if($status_keluhan == 'selesai') {
                // Mark all pending proses as selesai for manual keluhan
                mysqli_query($koneksi,"UPDATE tbservis_keluhan_tracking 
                                      SET status_proses='selesai', waktu_selesai=NOW(), updated_at=NOW()
                                      WHERE keluhan_id='$keluhan_id' AND status_proses='pending'");
            }
            
            $redirect_page = $_POST['redirect'] ?? 'service-bayar.php';
            header("Location: $redirect_page?noserv=$no_service&success=status_updated");
            exit;
            
        } else {
            throw new Exception("Error updating status: " . mysqli_error($koneksi));
        }
        
    } catch(Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
}

// Process batch update keluhan
if(isset($_POST['btnbatchupdate'])) {
    $no_service = $_POST['txtnosrv'] ?? '';
    $keluhan_updates = $_POST['keluhan_updates'] ?? [];
    
    if(empty($no_service) || empty($keluhan_updates)) {
        echo "<script>alert('No service dan data update harus diisi!'); history.back();</script>";
        exit;
    }
    
    try {
        foreach($keluhan_updates as $keluhan_id => $update_data) {
            $status = $update_data['status'] ?? '';
            $keterangan = mysqli_real_escape_string($koneksi, $update_data['keterangan'] ?? '');
            
            if(!empty($status)) {
                mysqli_query($koneksi,"UPDATE tbservis_keluhan_status SET 
                                      status_pengerjaan='$status',
                                      keterangan_tidak_selesai='$keterangan',
                                      updated_at=NOW() 
                                      WHERE id='$keluhan_id'");
            }
        }
        
        $redirect_page = $_POST['redirect'] ?? 'service-bayar.php';
        header("Location: $redirect_page?noserv=$no_service&success=batch_updated");
        exit;
        
    } catch(Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
}

// If no valid action, redirect to service page
header("Location: service-list.php");
exit;
?>