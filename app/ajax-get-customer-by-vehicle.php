<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Database connection
require_once "../config/koneksi.php";
require_once "_include_customer_vehicle_sync.php";

// Set content type to JSON
header('Content-Type: application/json');

// Check if POST request with nopol parameter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nopol'])) {
    $nopol = trim((string) $_POST['nopol']);
    
    try {
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $nopol);
        $data = $bundle['vehicle'] ?? null;
        $customer = $bundle['customer'] ?? null;

        if ($data) {
            // Get link Google Maps from database or build from coordinates
            $link_gmaps = $customer['link_gmaps'] ?? '';
            if (empty($link_gmaps) && !empty($customer['klat']) && !empty($customer['klong'])) {
                $link_gmaps = "https://www.google.com/maps?q=" . $customer['klat'] . "," . $customer['klong'];
            }

            // Get foto_rumah from database
            $foto_rumah = $customer['foto_tampak_rumah'] ?? ($customer['patokan'] ?? '');

            // Prepare response data
            $response_data = [
                'no_polisi' => $data['nopolisi'],
                'nama_pelanggan' => $customer['namapelanggan'] ?? $data['pemilik'],
                'no_pelanggan' => $customer['nopelanggan'] ?? '',
                'telepon' => $customer['telephone'] ?? '',
                'alamat' => $customer['alamat'] ?? '',
                'patokan' => $customer['patokan'] ?? '',
                'foto_rumah' => $foto_rumah,
                'google_maps_link' => $link_gmaps,
                'klat' => $customer['klat'] ?? '',
                'klong' => $customer['klong'] ?? '',
                'tipe' => $data['tipe'],
                'jenis' => $data['jenis'],
                'warna' => $data['warna'],
                'merek' => $data['merek'] ?: ''
            ];

            echo json_encode([
                'success' => true,
                'data' => $response_data,
                'message' => 'Data pelanggan berhasil ditemukan'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Data kendaraan tidak ditemukan',
                'data' => null
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'data' => null
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method or missing parameters',
        'data' => null
    ]);
}

mysqli_close($koneksi);
?>
