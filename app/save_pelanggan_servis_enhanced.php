<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
require_once __DIR__ . '/_customer_identity.php';

// Get form data
$edit_mode = $_POST['edit_mode'] ?? '0';
$service_type = $_POST['service_type'] ?? 'reguler';

// Customer data
$nama = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
$gender = mysqli_real_escape_string($koneksi, $_POST['cbogender']);
$tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['id-date-picker-1']);
$valid_tgl_lahir = mysqli_real_escape_string($koneksi, $_POST['cbovalid']);
$provinsi = mysqli_real_escape_string($koneksi, $_POST['cboprovinsi']);
$kota = mysqli_real_escape_string($koneksi, $_POST['cbokota']);
$kecamatan = mysqli_real_escape_string($koneksi, $_POST['cbokecamatan']);
$alamat = mysqli_real_escape_string($koneksi, $_POST['txtalamat']);
$patokan = mysqli_real_escape_string($koneksi, $_POST['txtpatokan']);
$nowa = mysqli_real_escape_string($koneksi, $_POST['txtnowa']);
$google_maps = mysqli_real_escape_string($koneksi, $_POST['txtgooglemaps']);
$info_sumber = mysqli_real_escape_string($koneksi, $_POST['cboinformasisumber']);

// Vehicle data
$nopol = strtoupper(mysqli_real_escape_string($koneksi, $_POST['txtnopol']));
$bulan_pajak = mysqli_real_escape_string($koneksi, $_POST['cbobulanpajak']);
$tahun_pajak = mysqli_real_escape_string($koneksi, $_POST['txtthnpajak']);
$merek_id = mysqli_real_escape_string($koneksi, $_POST['cbomerek']);
$tipe_id = mysqli_real_escape_string($koneksi, $_POST['cbotipe']);
$jenis_id = mysqli_real_escape_string($koneksi, $_POST['cbojenis']);
$warna_id = mysqli_real_escape_string($koneksi, $_POST['cbowarna']);

// Convert date format
$tgl_lahir_formatted = date('Y-m-d', strtotime(str_replace('/', '-', $tgl_lahir)));

// Handle file upload
$foto_rumah = '';
if (isset($_FILES['txtfotorumah']) && $_FILES['txtfotorumah']['error'] == 0) {
    $upload_dir = '../file_upload/customer_photos/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['txtfotorumah']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_extension, $allowed_extensions)) {
        $foto_rumah = 'customer_' . $nowa . '_' . time() . '.' . $file_extension;
        $upload_path = $upload_dir . $foto_rumah;
        
        if (move_uploaded_file($_FILES['txtfotorumah']['tmp_name'], $upload_path)) {
            $foto_rumah = 'file_upload/customer_photos/' . $foto_rumah;
        } else {
            $foto_rumah = '';
        }
    }
}

try {
    mysqli_autocommit($koneksi, false);

    $customer_resolution = fitmotorResolveCustomerCodeByPhone($koneksi, $nowa);
    if ($customer_resolution['status'] === 'ambiguous') {
        throw new Exception('Nomor WA sudah terhubung ke lebih dari satu pelanggan. Rapikan merge pelanggan dulu sebelum tambah motor baru.');
    }

    $customer_code = $customer_resolution['status'] === 'existing'
        ? $customer_resolution['code']
        : fitmotorGenerateCustomerCode($koneksi);
    
    // Check if customer exists
    $customer_exists = false;
    if ($edit_mode == '1') {
        $original_phone = $_POST['original_phone'];
        $check_customer = mysqli_prepare($koneksi, "SELECT nopelanggan FROM tblpelanggan WHERE nopelanggan = ?");
        mysqli_stmt_bind_param($check_customer, "s", $customer_code);
        mysqli_stmt_execute($check_customer);
        $result = mysqli_stmt_get_result($check_customer);
        $customer_exists = mysqli_num_rows($result) > 0;
        mysqli_stmt_close($check_customer);
    }
    
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
        
        // Check if vehicle exists
        $check_vehicle = mysqli_prepare($koneksi, "SELECT nopolisi FROM tblkendaraan WHERE nopolisi = ?");
        mysqli_stmt_bind_param($check_vehicle, "s", $nopol);
        mysqli_stmt_execute($check_vehicle);
        $vehicle_result = mysqli_stmt_get_result($check_vehicle);
        $vehicle_exists = mysqli_num_rows($vehicle_result) > 0;
        mysqli_stmt_close($check_vehicle);
        
        if (!$vehicle_exists) {
            // Insert new vehicle
            $insert_vehicle = mysqli_prepare($koneksi, "INSERT INTO tblkendaraan 
                                            (nopolisi, pemilik, alamat, kode_merek, kode_tipe, kode_jenis, kode_warna, tahun_buat) 
                                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $tahun_buat = $bulan_pajak . '-' . $tahun_pajak;
            mysqli_stmt_bind_param($insert_vehicle, "sssiiiis", $nopol, $customer_code, $alamat, $merek_id, $tipe_id, $jenis_id, $warna_id, $tahun_buat);
            mysqli_stmt_execute($insert_vehicle);
            mysqli_stmt_close($insert_vehicle);
        } else {
            $update_vehicle = mysqli_prepare($koneksi, "UPDATE tblkendaraan SET pemilik = ?, alamat = ?, kode_merek = ?, kode_tipe = ?, kode_jenis = ?, kode_warna = ?, tahun_buat = ? WHERE nopolisi = ?");
            $tahun_buat = $bulan_pajak . '-' . $tahun_pajak;
            mysqli_stmt_bind_param($update_vehicle, "ssiiiiss", $customer_code, $alamat, $merek_id, $tipe_id, $jenis_id, $warna_id, $tahun_buat, $nopol);
            mysqli_stmt_execute($update_vehicle);
            mysqli_stmt_close($update_vehicle);
        }
        
    } else {
        // Insert new customer
        $insert_customer = mysqli_prepare($koneksi, "INSERT INTO tblpelanggan 
                                         (nopelanggan, namapelanggan, alamat, kota, propinsi, kodepost, negara, telephone, 
                                          fax, kontakperson, note, potongan, tipepot, lavelharga, kgrup, patokan, 
                                          klat, klong, panggilan, saldoawal, pertanggal, tgllahir, id_panggilan, 
                                          bl_pajak, th_pajak, merek_id, tipe_id, jenis_id, warna_id, gender, 
                                          valid_tgl_lahir, informasi_sumber, google_maps_link, foto_rumah) 
                                         VALUES (?, ?, ?, '', '', '', '', ?, '', 'WA', '', 0, 'C', '3', '001', ?, 
                                                '', '', '', 0, '0000-00-00', ?, 0, ?, ?, ?, ?, ?, ?, ?, 
                                                'Valid', ?, ?, ?)");
        
        mysqli_stmt_bind_param($insert_customer, "ssssssssiiiiissss", 
                              $customer_code, $nama, $alamat, $nowa, $patokan, $tgl_lahir_formatted, 
                              $bulan_pajak, $tahun_pajak, $merek_id, $tipe_id, $jenis_id, $warna_id, 
                              $gender, $info_sumber, $google_maps, $foto_rumah);
        mysqli_stmt_execute($insert_customer);
        mysqli_stmt_close($insert_customer);
        
        // Insert vehicle
        $insert_vehicle = mysqli_prepare($koneksi, "INSERT INTO tblkendaraan 
                                        (nopolisi, pemilik, alamat, kode_merek, kode_tipe, kode_jenis, kode_warna, tahun_buat) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $tahun_buat = $bulan_pajak . '-' . $tahun_pajak;
        mysqli_stmt_bind_param($insert_vehicle, "sssiiiis", $nopol, $customer_code, $alamat, $merek_id, $tipe_id, $jenis_id, $warna_id, $tahun_buat);
        mysqli_stmt_execute($insert_vehicle);
        mysqli_stmt_close($insert_vehicle);
    }
    
    mysqli_commit($koneksi);
    
    // Redirect based on service type
    if ($service_type == 'jemput') {
        header("Location: save-no-servis-jemput.php?snopol=" . urlencode($nopol) . "&success=1");
    } else {
        header("Location: save-no-servis-reguler.php?snopol=" . urlencode($nopol) . "&success=1");
    }
    exit;
    
} catch (Exception $e) {
    mysqli_rollback($koneksi);
    $error_msg = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: pelanggan_add_enhanced.php?error=" . urlencode($error_msg));
    exit;
}

mysqli_close($koneksi);
?>
