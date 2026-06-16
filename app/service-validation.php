<?php
// File: service-validation.php
// Handler untuk validasi dan save service dengan persentase mekanik

session_start();
include "../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'];

// Function untuk validasi persentase mekanik
function validateMekanikPercentage($mekanik_data) {
    $errors = [];
    
    // Cek kepala mekanik
    if(empty($mekanik_data['kepala1'])) {
        $errors[] = "Kepala Mekanik 1 harus diisi";
    }
    
    // Cek minimal 1 admin/kasir
    $mekanik_filled = 0;
    for($i = 1; $i <= 4; $i++) {
        if(!empty($mekanik_data["mekanik$i"])) {
            $mekanik_filled++;
        }
    }
    
    if($mekanik_filled == 0) {
        $errors[] = "Minimal 1 admin/kasir harus diisi";
    }
    
    // Cek total persentase mekanik pengerjaan
    $total_persen = 0;
    for($i = 1; $i <= 4; $i++) {
        if(!empty($mekanik_data["mekanik$i"])) {
            $persen = intval($mekanik_data["persen$i"] ?? 0);
            $total_persen += $persen;
        }
    }
    
    if($total_persen != 100) {
        $errors[] = "Total persentase mekanik pengerjaan harus 100%. Saat ini: $total_persen%";
    }
    
    // Cek persentase kepala mekanik (boleh kurang dari 100%)
    $total_persen_kepala = intval($mekanik_data['persen_kepala1'] ?? 0) + intval($mekanik_data['persen_kepala2'] ?? 0);
    if($total_persen_kepala > 100) {
        $errors[] = "Total persentase kepala mekanik tidak boleh melebihi 100%. Saat ini: $total_persen_kepala%";
    }
    
    // Cek duplikasi mekanik
    $selected_mekanik = [];
    
    // Cek kepala mekanik
    if(!empty($mekanik_data['kepala1'])) {
        $selected_mekanik[] = $mekanik_data['kepala1'];
    }
    if(!empty($mekanik_data['kepala2'])) {
        if(in_array($mekanik_data['kepala2'], $selected_mekanik)) {
            $errors[] = "Kepala Mekanik 2 tidak boleh sama dengan Kepala Mekanik 1";
        }
        $selected_mekanik[] = $mekanik_data['kepala2'];
    }
    
    // Cek mekanik pengerjaan
    $mekanik_pengerjaan = [];
    for($i = 1; $i <= 4; $i++) {
        if(!empty($mekanik_data["mekanik$i"])) {
            if(in_array($mekanik_data["mekanik$i"], $mekanik_pengerjaan)) {
                $errors[] = "Mekanik pengerjaan tidak boleh duplikasi";
                break;
            }
            $mekanik_pengerjaan[] = $mekanik_data["mekanik$i"];
        }
    }
    
    return $errors;
}

// Function untuk save service data
function saveServiceData($koneksi, $service_data, $mekanik_data) {
    $no_service = $service_data['no_service'];
    
    // Prepare mekanik update query
    $mekanik_fields = [
        "kepala_mekanik1 = '" . ($mekanik_data['kepala1'] ?? '') . "'",
        "kepala_mekanik2 = '" . ($mekanik_data['kepala2'] ?? '') . "'",
        "persen_kepala_mekanik1 = " . intval($mekanik_data['persen_kepala1'] ?? 0),
        "persen_kepala_mekanik2 = " . intval($mekanik_data['persen_kepala2'] ?? 0),
        "mekanik1 = '" . ($mekanik_data['mekanik1'] ?? '') . "'",
        "mekanik2 = '" . ($mekanik_data['mekanik2'] ?? '') . "'",
        "mekanik3 = '" . ($mekanik_data['mekanik3'] ?? '') . "'",
        "mekanik4 = '" . ($mekanik_data['mekanik4'] ?? '') . "'",
        "persen_mekanik1 = " . intval($mekanik_data['persen1'] ?? 0),
        "persen_mekanik2 = " . intval($mekanik_data['persen2'] ?? 0),
        "persen_mekanik3 = " . intval($mekanik_data['persen3'] ?? 0),
        "persen_mekanik4 = " . intval($mekanik_data['persen4'] ?? 0)
    ];
    
    // Add other service fields
    if(isset($service_data['km_skr'])) {
        $mekanik_fields[] = "km_skr = " . intval($service_data['km_skr']);
    }
    if(isset($service_data['km_berikut'])) {
        $mekanik_fields[] = "km_berikut = " . intval($service_data['km_berikut']);
    }
    if(isset($service_data['status_servis'])) {
        $mekanik_fields[] = "status_servis = '" . $service_data['status_servis'] . "'";
    }
    if(isset($service_data['keterangan_jemput'])) {
        $mekanik_fields[] = "keterangan_jemput = '" . mysqli_real_escape_string($koneksi, $service_data['keterangan_jemput']) . "'";
    }
    
    $mekanik_fields[] = "updated_at = NOW()";
    
    $update_query = "UPDATE tblservice SET " . implode(', ', $mekanik_fields) . " WHERE no_service = '$no_service'";
    
    return mysqli_query($koneksi, $update_query);
}

// Process form submission
if(isset($_POST['btnsimpan']) || isset($_POST['btnupdatemekanik'])) {
    $no_service = $_POST['txtnosrv'] ?? '';
    
    if(empty($no_service)) {
        echo "<script>alert('No service tidak ditemukan!'); history.back();</script>";
        exit;
    }
    
    // Collect mekanik data
    $mekanik_data = [
        'kepala1' => $_POST['cbokepala1'] ?? '',
        'kepala2' => $_POST['cbokepala2'] ?? '',
        'persen_kepala1' => $_POST['txtpersen_kepala1'] ?? 0,
        'persen_kepala2' => $_POST['txtpersen_kepala2'] ?? 0,
        'mekanik1' => $_POST['cbomekanik1'] ?? '',
        'mekanik2' => $_POST['cbomekanik2'] ?? '',
        'mekanik3' => $_POST['cbomekanik3'] ?? '',
        'mekanik4' => $_POST['cbomekanik4'] ?? '',
        'persen1' => $_POST['txtpersen1'] ?? 0,
        'persen2' => $_POST['txtpersen2'] ?? 0,
        'persen3' => $_POST['txtpersen3'] ?? 0,
        'persen4' => $_POST['txtpersen4'] ?? 0
    ];
    
    // Collect service data
    $service_data = [
        'no_service' => $no_service,
        'km_skr' => $_POST['txtkm_skr'] ?? null,
        'km_berikut' => $_POST['txtkm_next'] ?? null,
        'status_servis' => $_POST['cbostatus'] ?? null,
        'keterangan_jemput' => $_POST['keterangan_jemput'] ?? null
    ];
    
    // Validate
    $validation_errors = validateMekanikPercentage($mekanik_data);
    
    if(!empty($validation_errors)) {
        $error_message = "Validasi gagal:\\n" . implode("\\n", $validation_errors);
        echo "<script>alert('$error_message'); history.back();</script>";
        exit;
    }
    
    try {
        // Save data
        if(saveServiceData($koneksi, $service_data, $mekanik_data)) {
            // Log activity
            $activity_log = "Updated service $no_service - Mekanik assignment";
            mysqli_query($koneksi,"INSERT INTO activity_log (user_id, activity, created_at) VALUES ('$id_user', '$activity_log', NOW())");
            
            // Determine redirect page
            $redirect_page = 'service-add.php';
            if(isset($_POST['redirect_to'])) {
                $redirect_page = $_POST['redirect_to'];
            } elseif(strpos($_SERVER['HTTP_REFERER'], 'service-bayar') !== false) {
                $redirect_page = 'service-bayar.php';
            } elseif(strpos($_SERVER['HTTP_REFERER'], 'service-garansi') !== false) {
                $redirect_page = 'service-garansi.php';
            } elseif(strpos($_SERVER['HTTP_REFERER'], 'jemput') !== false) {
                $redirect_page = 'service-add-jemput.php';
            }
            
            header("Location: $redirect_page?noserv=$no_service&success=mekanik_updated");
            exit;
            
        } else {
            throw new Exception("Error saving service data: " . mysqli_error($koneksi));
        }
        
    } catch(Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
}

// Process update status service
if(isset($_POST['btnupdatestatus'])) {
    $no_service = $_POST['txtnosrv'] ?? '';
    $status_servis = $_POST['cbostatus'] ?? '';
    
    if(empty($no_service) || empty($status_servis)) {
        echo "<script>alert('No service dan status harus diisi!'); history.back();</script>";
        exit;
    }
    
    try {
        $update_query = "UPDATE tblservice SET 
                        status_servis = '$status_servis',
                        updated_at = NOW() 
                        WHERE no_service = '$no_service'";
        
        if(mysqli_query($koneksi, $update_query)) {
            // Update related keluhan status if service completed
            if($status_servis == 'selesai' || $status_servis == 'bayar') {
                mysqli_query($koneksi,"UPDATE tbservis_keluhan_status 
                                      SET status_pengerjaan = 'selesai', updated_at = NOW()
                                      WHERE no_service = '$no_service' 
                                      AND status_pengerjaan IN ('datang', 'diproses')");
            }
            
            // Log activity
            $activity_log = "Updated service status $no_service to $status_servis";
            mysqli_query($koneksi,"INSERT INTO activity_log (user_id, activity, created_at) VALUES ('$id_user', '$activity_log', NOW())");
            
            header("Location: " . $_SERVER['HTTP_REFERER'] . "&success=status_updated");
            exit;
            
        } else {
            throw new Exception("Error updating status: " . mysqli_error($koneksi));
        }
        
    } catch(Exception $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
}

// Process calculate service estimates
if(isset($_POST['btncalculate'])) {
    $no_service = $_POST['txtnosrv'] ?? '';
    
    if(empty($no_service)) {
        echo json_encode(['success' => false, 'message' => 'No service tidak ditemukan']);
        exit;
    }
    
    try {
        // Calculate total estimates from keluhan tracking
        $sql_estimate = mysqli_query($koneksi,"SELECT 
                                              SUM(kp.estimasi_waktu) as total_waktu,
                                              SUM(kp.harga_estimasi) as total_harga,
                                              COUNT(kt.id) as total_proses,
                                              SUM(CASE WHEN kt.status_proses = 'selesai' THEN 1 ELSE 0 END) as proses_selesai
                                              FROM tbservis_keluhan_tracking kt
                                              JOIN tbkeluhan_proses kp ON kt.proses_id = kp.id
                                              WHERE kt.no_service = '$no_service'");
        
        $estimate_data = mysqli_fetch_array($sql_estimate);
        
        $response = [
            'success' => true,
            'total_waktu' => $estimate_data['total_waktu'] ?? 0,
            'total_harga' => $estimate_data['total_harga'] ?? 0,
            'total_proses' => $estimate_data['total_proses'] ?? 0,
            'proses_selesai' => $estimate_data['proses_selesai'] ?? 0,
            'progress' => $estimate_data['total_proses'] > 0 ? round(($estimate_data['proses_selesai'] / $estimate_data['total_proses']) * 100) : 0
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
        
    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// If no valid action, redirect back
if(isset($_SERVER['HTTP_REFERER'])) {
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    header("Location: service-list.php");
}
exit;
?>