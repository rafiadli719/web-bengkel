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

// Check database connection
if (mysqli_connect_errno()){
    ob_clean();
    echo json_encode(['error' => 'Database connection failed: ' . mysqli_connect_error()]);
    exit;
}

if(isset($_POST['phone'])) {
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    
    // Clean phone number (remove non-numeric characters except +)
    $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
    
    // Check in tblpelanggan table
    $query = "SELECT namapelanggan, telephone, nopelanggan FROM tblpelanggan 
              WHERE telephone = '$clean_phone' 
              OR telephone = '$phone'
              OR REPLACE(REPLACE(REPLACE(telephone, '-', ''), ' ', ''), '+', '') = REPLACE(REPLACE(REPLACE('$clean_phone', '-', ''), ' ', ''), '+', '')
              LIMIT 1";
    
    $result = mysqli_query($koneksi, $query);

    if(!$result) {
        ob_clean();
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($koneksi)]);
        exit;
    }

    if(mysqli_num_rows($result) > 0) {
        $customer_data = mysqli_fetch_array($result);
        
        // Get all vehicles owned by this customer
        $vehicles = [];
        
        // Method 1: Get vehicle from tblpelanggan.nopelanggan (primary vehicle)
        if(!empty($customer_data['nopelanggan'])) {
            $vehicle_query = "SELECT k.nopolisi, k.pemilik, k.tipe, k.jenis, k.warna, k.tahun_buat,
                                     COALESCE(pm.merek, 'Unknown') as merek, k.no_rangka, k.no_mesin
                              FROM tblkendaraan k
                              LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                              WHERE k.nopolisi = '" . mysqli_real_escape_string($koneksi, $customer_data['nopelanggan']) . "'";
            
            $vehicle_result = mysqli_query($koneksi, $vehicle_query);
            if($vehicle_result && mysqli_num_rows($vehicle_result) > 0) {
                $vehicle = mysqli_fetch_array($vehicle_result);
                $vehicles[] = [
                    'nopolisi' => $vehicle['nopolisi'],
                    'pemilik' => $vehicle['pemilik'],
                    'tipe' => $vehicle['tipe'],
                    'jenis' => $vehicle['jenis'],
                    'warna' => $vehicle['warna'],
                    'merek' => $vehicle['merek'],
                    'tahun_buat' => $vehicle['tahun_buat'],
                    'no_rangka' => $vehicle['no_rangka'],
                    'no_mesin' => $vehicle['no_mesin'],
                    'source' => 'primary'
                ];
            }
        }
        
        // Method 2: Get additional vehicles where customer name matches in tblkendaraan.pemilik
        $additional_vehicles_query = "SELECT k.nopolisi, k.pemilik, k.tipe, k.jenis, k.warna, k.tahun_buat,
                                             COALESCE(pm.merek, 'Unknown') as merek, k.no_rangka, k.no_mesin
                                      FROM tblkendaraan k
                                      LEFT JOIN tbpabrik_motor pm ON k.kode_merek = pm.id
                                      WHERE k.pemilik LIKE '%" . mysqli_real_escape_string($koneksi, $customer_data['namapelanggan']) . "%'
                                      AND k.nopolisi != '" . mysqli_real_escape_string($koneksi, $customer_data['nopelanggan']) . "'";
        
        $additional_result = mysqli_query($koneksi, $additional_vehicles_query);
        if($additional_result && mysqli_num_rows($additional_result) > 0) {
            while($vehicle = mysqli_fetch_array($additional_result)) {
                // Check if this vehicle is not already in the list
                $exists = false;
                foreach($vehicles as $existing_vehicle) {
                    if($existing_vehicle['nopolisi'] == $vehicle['nopolisi']) {
                        $exists = true;
                        break;
                    }
                }
                
                if(!$exists) {
                    $vehicles[] = [
                        'nopolisi' => $vehicle['nopolisi'],
                        'pemilik' => $vehicle['pemilik'],
                        'tipe' => $vehicle['tipe'],
                        'jenis' => $vehicle['jenis'],
                        'warna' => $vehicle['warna'],
                        'merek' => $vehicle['merek'],
                        'tahun_buat' => $vehicle['tahun_buat'],
                        'no_rangka' => $vehicle['no_rangka'],
                        'no_mesin' => $vehicle['no_mesin'],
                        'source' => 'additional'
                    ];
                }
            }
        }
        
        ob_clean();
        echo json_encode([
            'exists' => true,
            'data' => [
                'nama' => $customer_data['namapelanggan'],
                'phone' => $customer_data['telephone'],
                'nopelanggan' => $customer_data['nopelanggan']
            ],
            'vehicles' => $vehicles,
            'vehicle_count' => count($vehicles)
        ]);
    } else {
        ob_clean();
        echo json_encode(['exists' => false]);
    }
} else {
    ob_clean();
    echo json_encode(['exists' => false]);
}
?>
