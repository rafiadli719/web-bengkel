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
    // Get form data
    $nama = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $gender = mysqli_real_escape_string($koneksi, $_POST['cbogender']);
    $tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['id-date-picker-1']);
    $valid_tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['cbovalid']);
    $provinsi = mysqli_real_escape_string($koneksi, $_POST['cboprovinsi']);
    $kota = mysqli_real_escape_string($koneksi, $_POST['cbokota']);
    $kecamatan = mysqli_real_escape_string($koneksi, $_POST['cbokecamatan']);
    $alamat = mysqli_real_escape_string($koneksi, $_POST['txtalamat']);
    $patokan = mysqli_real_escape_string($koneksi, $_POST['txtpatokan']);
    $google_maps = mysqli_real_escape_string($koneksi, $_POST['txtgooglemaps']);
    $nowa = mysqli_real_escape_string($koneksi, $_POST['txtnowa']);
    $info_sumber = mysqli_real_escape_string($koneksi, $_POST['cboinformasisumber']);

    if (trim($nowa) === '') {
        echo json_encode(['success' => false, 'message' => 'Nomor WA wajib diisi supaya sistem bisa mengenali pelanggan yang sama di kunjungan berikutnya']);
        exit;
    }
    
    // Vehicle data
    $nopol = strtoupper(mysqli_real_escape_string($koneksi, $_POST['txtnopol']));
    $bulan_pajak = mysqli_real_escape_string($koneksi, $_POST['cbobulanpajak']);
    $tahun_pajak = mysqli_real_escape_string($koneksi, $_POST['txtthnpajak']);
    $merek_id = mysqli_real_escape_string($koneksi, $_POST['cbomerek']);
    $tipe_id = mysqli_real_escape_string($koneksi, $_POST['cbotipe']);
    $jenis_id = mysqli_real_escape_string($koneksi, $_POST['cbojenis']);
    $warna_id = mysqli_real_escape_string($koneksi, $_POST['cbowarna']);
    
    // Convert date format from dd/mm/yyyy to yyyy-mm-dd
    $tgl_lahir_parts = explode('/', $tgl_lahir);
    if (count($tgl_lahir_parts) == 3) {
        $tgl_lahir_formatted = $tgl_lahir_parts[2] . '-' . $tgl_lahir_parts[1] . '-' . $tgl_lahir_parts[0];
    } else {
        $tgl_lahir_formatted = date('Y-m-d');
    }
    
    // Handle file upload
    $foto_rumah = '';
    if (isset($_FILES['fotorumah']) && $_FILES['fotorumah']['error'] == 0) {
        $upload_dir = '../file_upload/customer_photos/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['fotorumah']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $foto_rumah = 'customer_' . preg_replace('/[^0-9]/', '', $nowa) . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $foto_rumah;
            
            if (move_uploaded_file($_FILES['fotorumah']['tmp_name'], $upload_path)) {
                $foto_rumah = 'file_upload/customer_photos/' . $foto_rumah;
            } else {
                $foto_rumah = '';
            }
        }
    }
    
    // Start transaction
    mysqli_autocommit($koneksi, false);
    
    $customer_resolution = fitmotorResolveCustomerCodeByPhone($koneksi, $nowa);
    if ($customer_resolution['status'] === 'ambiguous') {
        echo json_encode([
            'success' => false,
            'message' => 'Nomor WA sudah terhubung ke lebih dari satu pelanggan. Rapikan merge pelanggan dulu sebelum tambah motor baru.'
        ]);
        exit;
    }

    $customer_exists = ($customer_resolution['status'] === 'existing');
    $customer_code = $customer_exists ? $customer_resolution['code'] : fitmotorGenerateCustomerCode($koneksi);
    
    if ($customer_exists) {
        // Update existing customer
        $update_sql = "UPDATE tblpelanggan SET 
                       namapelanggan = ?, 
                       alamat = ?, 
                       kota = ?, 
                       propinsi = ?, 
                       telephone = ?, 
                       patokan = ?, 
                       tgllahir = ?, 
                       gender = ?, 
                       valid_tgl_lahir = ?, 
                       informasi_sumber = ?, 
                       bl_pajak = ?, 
                       th_pajak = ?, 
                       merek_id = ?, 
                       tipe_id = ?, 
                       jenis_id = ?, 
                       warna_id = ?";
        
        $params = [$nama, $alamat, $kota, $provinsi, $nowa, $patokan, $tgl_lahir_formatted, 
                  $gender, $valid_tgl_lahir, $info_sumber, $bulan_pajak, $tahun_pajak, 
                  $merek_id, $tipe_id, $jenis_id, $warna_id];
        $types = "ssssssssssssssss";
        
        if (!empty($google_maps)) {
            $update_sql .= ", google_maps_link = ?";
            $params[] = $google_maps;
            $types .= "s";
        }
        
        if (!empty($foto_rumah)) {
            $update_sql .= ", foto_rumah = ?";
            $params[] = $foto_rumah;
            $types .= "s";
        }
        
        $update_sql .= " WHERE nopelanggan = ?";
        $params[] = $customer_code;
        $types .= "s";
        
        $stmt = mysqli_prepare($koneksi, $update_sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
    } else {
        // Insert new customer
        $insert_sql = "INSERT INTO tblpelanggan 
                      (nopelanggan, namapelanggan, alamat, kota, propinsi, kodepost, negara, 
                       telephone, fax, kontakperson, note, potongan, tipepot, lavelharga, kgrup, 
                       patokan, klat, klong, panggilan, saldoawal, pertanggal, tgllahir, 
                       id_panggilan, bl_pajak, th_pajak, merek_id, tipe_id, jenis_id, warna_id, 
                       gender, valid_tgl_lahir, informasi_sumber, google_maps_link, foto_rumah) 
                      VALUES 
                      (?, ?, ?, ?, ?, '', '', ?, '', 'WA', '', 0, 'C', '3', '001', ?, 
                       '', '', '', 0, '0000-00-00', ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($koneksi, $insert_sql);
        mysqli_stmt_bind_param($stmt, "sssssssssssiiiissss", 
                              $customer_code, $nama, $alamat, $kota, $provinsi, $nowa, $patokan, 
                              $tgl_lahir_formatted, $bulan_pajak, $tahun_pajak, $merek_id, 
                              $tipe_id, $jenis_id, $warna_id, $gender, $valid_tgl_lahir, 
                              $info_sumber, $google_maps, $foto_rumah);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    if (!$success) {
        throw new Exception("Gagal menyimpan data pelanggan: " . mysqli_error($koneksi));
    }
    
    // Check if vehicle exists
    $check_vehicle = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ?");
    mysqli_stmt_bind_param($check_vehicle, "s", $nopol);
    mysqli_stmt_execute($check_vehicle);
    $vehicle_result = mysqli_stmt_get_result($check_vehicle);
    $vehicle_exists = mysqli_num_rows($vehicle_result) > 0;
    mysqli_stmt_close($check_vehicle);
    
    if (!$vehicle_exists) {
        // Insert vehicle data
        $tahun_buat = $bulan_pajak . '-' . $tahun_pajak;
        $insert_vehicle = mysqli_prepare($koneksi, "INSERT INTO tblkendaraan 
                                        (nopolisi, pemilik, alamat, kode_merek, kode_tipe, kode_jenis, kode_warna, tahun_buat) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($insert_vehicle, "sssiiiis", $nopol, $customer_code, $alamat, $merek_id, $tipe_id, $jenis_id, $warna_id, $tahun_buat);
        $vehicle_success = mysqli_stmt_execute($insert_vehicle);
        mysqli_stmt_close($insert_vehicle);
        
        if (!$vehicle_success) {
            throw new Exception("Gagal menyimpan data kendaraan: " . mysqli_error($koneksi));
        }
    } else {
        $tahun_buat = $bulan_pajak . '-' . $tahun_pajak;
        $update_vehicle = mysqli_prepare($koneksi, "UPDATE tblkendaraan SET pemilik = ?, alamat = ?, kode_merek = ?, kode_tipe = ?, kode_jenis = ?, kode_warna = ?, tahun_buat = ? WHERE nopolisi = ?");
        mysqli_stmt_bind_param($update_vehicle, "ssiiiiss", $customer_code, $alamat, $merek_id, $tipe_id, $jenis_id, $warna_id, $tahun_buat, $nopol);
        mysqli_stmt_execute($update_vehicle);
        mysqli_stmt_close($update_vehicle);
    }
    
    // Commit transaction
    mysqli_commit($koneksi);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Data berhasil disimpan',
        'nopol' => $nopol
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
