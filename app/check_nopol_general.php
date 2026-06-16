<?php
// Prevent any output before JSON
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

session_start();

// Set JSON header
header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    ob_clean();
    echo json_encode(['error' => 'Session tidak valid']);
    exit;
}

include "../config/koneksi.php";
include "_include_customer_vehicle_sync.php";

// Check database connection
if (mysqli_connect_errno()){
    ob_clean();
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

if(isset($_POST['nopol'])) {
    $nopol = strtoupper(trim($_POST['nopol']));

    if(empty($nopol)) {
        ob_clean();
        echo json_encode(['exists' => false]);
        exit;
    }

    $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $nopol);
    $pelanggan_data = $bundle['customer'] ?? null;
    $kendaraan_data = $bundle['vehicle'] ?? null;

    if($pelanggan_data || $kendaraan_data) {
        // Nopol ditemukan
        $response_data = [
            'nopolisi' => $nopol,
            'pemilik' => $pelanggan_data['namapelanggan'] ?? $kendaraan_data['pemilik'] ?? '',
            'telephone' => $pelanggan_data['telephone'] ?? $kendaraan_data['telephone'] ?? '',
            'tipe' => $kendaraan_data['tipe'] ?? '',
            'jenis' => $kendaraan_data['jenis'] ?? '',
            'warna' => $kendaraan_data['warna'] ?? '',
            'merek' => $kendaraan_data['merek'] ?? ($kendaraan_data['tipe'] ?? ''),
            'nopelanggan' => $pelanggan_data['nopelanggan'] ?? ($bundle['mapped_customer_code'] ?? ''),
            'status_member' => $pelanggan_data['status_member'] ?? '',
            'kategori_pelanggan' => $pelanggan_data['grup'] ?? ($pelanggan_data['kgrup'] ?? '')
        ];

        ob_clean();
        echo json_encode([
            'exists' => true,
            'data' => $response_data,
            'found_in' => ($pelanggan_data && $kendaraan_data) ? 'both' :
                         ($pelanggan_data ? 'pelanggan' : 'kendaraan')
        ]);
    } else {
        // Nopol tidak ditemukan
        ob_clean();
        echo json_encode(['exists' => false]);
    }
} else {
    ob_clean();
    echo json_encode(['exists' => false]);
}

mysqli_close($koneksi);
?>
