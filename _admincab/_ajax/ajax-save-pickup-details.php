<?php
session_start();
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

if (!isset($_POST['no_service']) || empty($_POST['no_service'])) {
    echo json_encode(['success' => false, 'message' => 'Nomor service tidak valid']);
    exit;
}

$no_service = $_POST['no_service'];

// Get pickup detail data from POST
$alamat_jemput = $_POST['alamat_jemput'] ?? '';
$kelurahan_jemput = $_POST['kelurahan_jemput'] ?? '';
$kecamatan_jemput = $_POST['kecamatan_jemput'] ?? '';
$patokan_jemput = $_POST['patokan_jemput'] ?? '';
$tanggal_jemput = $_POST['tanggal_jemput'] ?? '';
$jam_jemput = $_POST['jam_jemput'] ?? '';
$prioritas_jemput = $_POST['prioritas_jemput'] ?? 'normal';
$status_jemput = $_POST['status_jemput'] ?? 'dijadwalkan';
$nama_kontak_jemput = $_POST['nama_kontak_jemput'] ?? '';
$telp_kontak_jemput = $_POST['telp_kontak_jemput'] ?? '';
$hubungan_kontak = $_POST['hubungan_kontak'] ?? '';
$driver_jemput = $_POST['driver_jemput'] ?? '';
$kendaraan_derek = $_POST['kendaraan_derek'] ?? '';
$biaya_jemput = floatval($_POST['biaya_jemput'] ?? 0);
$catatan_jemput = $_POST['catatan_jemput'] ?? '';

// Convert date format if needed (from dd/mm/yyyy to yyyy-mm-dd)
if (!empty($tanggal_jemput)) {
    $date_parts = explode('/', $tanggal_jemput);
    if (count($date_parts) == 3) {
        $tanggal_jemput_db = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
    } else {
        $tanggal_jemput_db = $tanggal_jemput;
    }
} else {
    $tanggal_jemput_db = null;
}

try {
    // Check if service exists
    $query_check = "SELECT COUNT(*) as count FROM tblservice WHERE no_service = ?";
    $stmt_check = mysqli_prepare($koneksi, $query_check);
    mysqli_stmt_bind_param($stmt_check, "s", $no_service);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);
    $check_data = mysqli_fetch_assoc($result_check);
    
    if ($check_data['count'] == 0) {
        echo json_encode(['success' => false, 'message' => 'Service tidak ditemukan']);
        exit;
    }
    
    // Check if pickup details already exist
    $query_check_pickup = "SELECT COUNT(*) as count FROM tb_pickup_details WHERE no_service = ?";
    $stmt_check_pickup = mysqli_prepare($koneksi, $query_check_pickup);
    mysqli_stmt_bind_param($stmt_check_pickup, "s", $no_service);
    mysqli_stmt_execute($stmt_check_pickup);
    $result_check_pickup = mysqli_stmt_get_result($stmt_check_pickup);
    $pickup_exists = mysqli_fetch_assoc($result_check_pickup)['count'] > 0;
    
    if ($pickup_exists) {
        // Update existing pickup details
        $query_update = "UPDATE tb_pickup_details SET 
                         alamat_jemput = ?, kelurahan_jemput = ?, kecamatan_jemput = ?, patokan_jemput = ?,
                         tanggal_jemput = ?, jam_jemput = ?, prioritas_jemput = ?, status_jemput = ?,
                         nama_kontak_jemput = ?, telp_kontak_jemput = ?, hubungan_kontak = ?,
                         driver_jemput = ?, kendaraan_derek = ?, biaya_jemput = ?, catatan_jemput = ?,
                         updated_at = NOW()
                         WHERE no_service = ?";
        
        $stmt_update = mysqli_prepare($koneksi, $query_update);
        mysqli_stmt_bind_param($stmt_update, "sssssssssssssdss", 
            $alamat_jemput, $kelurahan_jemput, $kecamatan_jemput, $patokan_jemput,
            $tanggal_jemput_db, $jam_jemput, $prioritas_jemput, $status_jemput,
            $nama_kontak_jemput, $telp_kontak_jemput, $hubungan_kontak,
            $driver_jemput, $kendaraan_derek, $biaya_jemput, $catatan_jemput,
            $no_service
        );
        
        if (mysqli_stmt_execute($stmt_update)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Detail penjemputan berhasil diperbarui',
                'action' => 'updated'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal memperbarui detail penjemputan']);
        }
        
    } else {
        // Insert new pickup details
        $query_insert = "INSERT INTO tb_pickup_details (
                         no_service, alamat_jemput, kelurahan_jemput, kecamatan_jemput, patokan_jemput,
                         tanggal_jemput, jam_jemput, prioritas_jemput, status_jemput,
                         nama_kontak_jemput, telp_kontak_jemput, hubungan_kontak,
                         driver_jemput, kendaraan_derek, biaya_jemput, catatan_jemput, created_at
                         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt_insert = mysqli_prepare($koneksi, $query_insert);
        mysqli_stmt_bind_param($stmt_insert, "sssssssssssssdss", 
            $no_service, $alamat_jemput, $kelurahan_jemput, $kecamatan_jemput, $patokan_jemput,
            $tanggal_jemput_db, $jam_jemput, $prioritas_jemput, $status_jemput,
            $nama_kontak_jemput, $telp_kontak_jemput, $hubungan_kontak,
            $driver_jemput, $kendaraan_derek, $biaya_jemput, $catatan_jemput
        );
        
        if (mysqli_stmt_execute($stmt_insert)) {
            echo json_encode([
                'success' => true, 
                'message' => 'Detail penjemputan berhasil disimpan',
                'action' => 'inserted'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menyimpan detail penjemputan']);
        }
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($stmt_check)) mysqli_stmt_close($stmt_check);
    if (isset($stmt_check_pickup)) mysqli_stmt_close($stmt_check_pickup);
    if (isset($stmt_update)) mysqli_stmt_close($stmt_update);
    if (isset($stmt_insert)) mysqli_stmt_close($stmt_insert);
}

// Function to create pickup details table if it doesn't exist
function createPickupDetailsTable($koneksi) {
    $query_create = "CREATE TABLE IF NOT EXISTS tb_pickup_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        no_service VARCHAR(50) NOT NULL,
        alamat_jemput TEXT,
        kelurahan_jemput VARCHAR(100),
        kecamatan_jemput VARCHAR(100),
        patokan_jemput VARCHAR(200),
        tanggal_jemput DATE,
        jam_jemput TIME,
        prioritas_jemput ENUM('normal', 'urgent', 'emergency') DEFAULT 'normal',
        status_jemput ENUM('dijadwalkan', 'dalam_perjalanan', 'dijemput') DEFAULT 'dijadwalkan',
        nama_kontak_jemput VARCHAR(100),
        telp_kontak_jemput VARCHAR(20),
        hubungan_kontak ENUM('pemilik', 'keluarga', 'teman', 'karyawan', 'lainnya'),
        driver_jemput VARCHAR(50),
        kendaraan_derek VARCHAR(20),
        biaya_jemput DECIMAL(10,2) DEFAULT 0,
        catatan_jemput TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_service (no_service),
        FOREIGN KEY (no_service) REFERENCES tblservice(no_service) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($koneksi, $query_create);
}

// Try to create table if it doesn't exist
createPickupDetailsTable($koneksi);
?>