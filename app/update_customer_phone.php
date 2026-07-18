<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['_iduser'])) {
    echo json_encode(['success' => false, 'message' => 'Session tidak valid']);
    exit;
}

include "../config/koneksi.php";
require_once __DIR__ . '/_customer_identity.php';

try {
    // Get parameters
    $nopol = mysqli_real_escape_string($koneksi, $_POST['nopol']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    
    if (empty($nopol) || empty($phone)) {
        throw new Exception('Parameter tidak lengkap');
    }
    
    // Validate phone format
    if (!preg_match('/^(\+62|62|0)[0-9]{9,13}$/', $phone)) {
        throw new Exception('Format nomor WA tidak valid');
    }
    
    // Start transaction
    mysqli_autocommit($koneksi, false);
    
    // Update phone in tblpelanggan where nopelanggan matches
    $update_pelanggan = mysqli_prepare($koneksi, "UPDATE tblpelanggan SET telephone = ? WHERE nopelanggan = ?");
    mysqli_stmt_bind_param($update_pelanggan, "ss", $phone, $nopol);
    $success1 = mysqli_stmt_execute($update_pelanggan);
    $affected1 = mysqli_stmt_affected_rows($update_pelanggan);
    mysqli_stmt_close($update_pelanggan);
    
    // Also try to update based on vehicle owner name match
    $get_owner = mysqli_prepare($koneksi, "SELECT pemilik FROM tblkendaraan WHERE nopolisi = ?");
    mysqli_stmt_bind_param($get_owner, "s", $nopol);
    mysqli_stmt_execute($get_owner);
    $result = mysqli_stmt_get_result($get_owner);
    $owner_data = mysqli_fetch_array($result);
    mysqli_stmt_close($get_owner);
    
    $affected2 = 0;
    if ($owner_data && !empty($owner_data['pemilik'])) {
        $owner_name = $owner_data['pemilik'];
        $update_by_name = mysqli_prepare($koneksi, "UPDATE tblpelanggan SET telephone = ? WHERE namapelanggan = ?");
        mysqli_stmt_bind_param($update_by_name, "ss", $phone, $owner_name);
        $success2 = mysqli_stmt_execute($update_by_name);
        $affected2 = mysqli_stmt_affected_rows($update_by_name);
        mysqli_stmt_close($update_by_name);
    }
    
    // If no records updated in tblpelanggan, try to insert new record
    if ($affected1 == 0 && $affected2 == 0 && $owner_data) {
        $owner_name = $owner_data['pemilik'];
        $customer_resolution = fitmotorResolveCustomerCodeByPhone($koneksi, $phone);
        if ($customer_resolution['status'] === 'ambiguous') {
            throw new Exception('Nomor WA terhubung ke lebih dari satu pelanggan. Rapikan merge pelanggan dulu sebelum update.');
        }

        $customer_code = $customer_resolution['status'] === 'existing'
            ? $customer_resolution['code']
            : fitmotorGenerateCustomerCode($koneksi);

        if ($customer_resolution['status'] === 'existing') {
            $link_vehicle = mysqli_prepare($koneksi, "UPDATE tblkendaraan SET pemilik = ? WHERE nopolisi = ?");
            mysqli_stmt_bind_param($link_vehicle, "ss", $customer_code, $nopol);
            mysqli_stmt_execute($link_vehicle);
            mysqli_stmt_close($link_vehicle);
        } else {
        $insert_pelanggan = mysqli_prepare($koneksi, 
            "INSERT INTO tblpelanggan (nopelanggan, namapelanggan, telephone, alamat, kota, propinsi, 
             kodepost, negara, fax, kontakperson, note, potongan, tipepot, lavelharga, kgrup, 
             patokan, klat, klong, panggilan, saldoawal, pertanggal, tgllahir, id_panggilan) 
             VALUES (?, ?, ?, '', '', '', '', '', '', 'WA', '', 0, 'C', '3', '001', '', '', '', '', 0, '0000-00-00', '0000-00-00', 0)");
        mysqli_stmt_bind_param($insert_pelanggan, "sss", $customer_code, $owner_name, $phone);
        $success3 = mysqli_stmt_execute($insert_pelanggan);
        mysqli_stmt_close($insert_pelanggan);
        
        if (!$success3) {
            throw new Exception("Gagal membuat record pelanggan baru: " . mysqli_error($koneksi));
        }

            $link_vehicle = mysqli_prepare($koneksi, "UPDATE tblkendaraan SET pemilik = ? WHERE nopolisi = ?");
            mysqli_stmt_bind_param($link_vehicle, "ss", $customer_code, $nopol);
            mysqli_stmt_execute($link_vehicle);
            mysqli_stmt_close($link_vehicle);
        }
    }
    
    // Commit transaction
    mysqli_commit($koneksi);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Nomor WA berhasil diupdate',
        'updated_records' => $affected1 + $affected2
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($koneksi);
    
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}

mysqli_close($koneksi);
?>
