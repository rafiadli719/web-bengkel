<?php
session_start();
include "../../config/koneksi.php";
require_once "../_include_customer_vehicle_sync.php";
require_once "../_include_kategori_member.php";

header('Content-Type: application/json');

if(empty($_SESSION['_iduser'])){
    echo json_encode(array('success' => false, 'message' => 'Unauthorized access'));
    exit;
}

try {
    $no_polisi = trim((string) ($_POST['no_polisi'] ?? ''));
    
    if(empty($no_polisi)) {
        echo json_encode(array('success' => false, 'message' => 'Nomor polisi tidak boleh kosong'));
        exit;
    }
    
    $like = '%' . fitmotorEscapeLike($koneksi, $no_polisi) . '%';
    $query = "SELECT nopolisi FROM tblkendaraan
              WHERE nopolisi LIKE ?
              ORDER BY CASE WHEN nopolisi = ? THEN 0 ELSE 1 END, nopolisi ASC
              LIMIT 1";
    $stmt = mysqli_prepare($koneksi, $query);
    mysqli_stmt_bind_param($stmt, 'ss', $like, $no_polisi);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if($result && mysqli_num_rows($result) > 0) {
        $found = mysqli_fetch_array($result);
        $bundle = fitmotorGetCustomerVehicleBundle($koneksi, $found['nopolisi']);
        $kendaraan = $bundle['vehicle'] ?? [];
        $pelanggan = $bundle['customer'] ?? [];
        echo json_encode(array(
            'success' => true,
            'no_polisi' => $kendaraan['nopolisi'] ?? $found['nopolisi'],
            'pemilik' => $pelanggan['namapelanggan'] ?? ($kendaraan['pemilik'] ?? ''),
            'jenis' => $kendaraan['jenis'] ?? '',
            'merek' => $kendaraan['merek'] ?? '',
            'warna' => $kendaraan['warna'] ?? '',
            'no_rangka' => $kendaraan['no_rangka'] ?? '',
            'no_mesin' => $kendaraan['no_mesin'] ?? '',
            'kode_pelanggan' => $pelanggan['nopelanggan'] ?? '',
            'telepon' => $pelanggan['telephone'] ?? '',
            'status_member' => $pelanggan['status_member'] ?? fitmotorMapGrupNameToTier($pelanggan['grup'] ?? '')
        ));
    } else {
        echo json_encode(array('success' => false, 'message' => 'Kendaraan tidak ditemukan'));
    }
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    echo json_encode(array('success' => false, 'message' => 'Error: ' . $e->getMessage()));
}
?>
