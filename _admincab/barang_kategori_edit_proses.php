<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include file konfigurasi database
include "../config/koneksi.php";

// Ambil data dari form
$id = $_POST['txtid'] ?? null;
$txtkd = $_POST['txtkd'] ?? null;
$txtnama = $_POST['txtnama'] ?? null;

// Inisialisasi file log
$log_file = 'accurate_category_edit_log.txt';

// Validasi field wajib
if (empty($id) || empty($txtkd) || empty($txtnama)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Field wajib kosong: id, txtkd, atau txtnama\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: ID, Kode, dan Nama Kategori harus diisi.');window.location=('barang_kategori.php');</script>";
    exit;
}

// Get data kategori lama untuk log dan accurate_id
$old_data_query = mysqli_query($koneksi, "SELECT jenis, namajenis, accurate_id FROM tblitemjenis WHERE id='$id'");
if (!$old_data_query || mysqli_num_rows($old_data_query) == 0) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Kategori dengan ID $id tidak ditemukan di database lokal\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Kategori tidak ditemukan di database lokal.');window.location=('barang_kategori.php');</script>";
    exit;
}
$old_data = mysqli_fetch_array($old_data_query);
$old_kode = $old_data['jenis'] ?? '';
$old_nama = $old_data['namajenis'] ?? '';
$accurate_id = $old_data['accurate_id'] ?? null;

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 EDIT CATEGORY - ID: $id, Accurate ID: " . ($accurate_id ?? 'NULL') . "\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 OLD: Kode='$old_kode', Nama='$old_nama'\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 NEW: Kode='$txtkd', Nama='$txtnama'\n", FILE_APPEND | LOCK_EX);

// Update database lokal
$result = mysqli_query($koneksi, "UPDATE tblitemjenis SET jenis='$txtkd', namajenis='$txtnama' WHERE id='$id'");
if (!$result) {
    $error = mysqli_error($koneksi);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal update database lokal: $error\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Gagal update database lokal. $error');window.location=('barang_kategori.php');</script>";
    exit;
}

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 DATABASE LOKAL: Update kategori '$txtnama' (Kode: $txtkd) - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);

// Include file konfigurasi Accurate
$config_path = '../config/accurate_config.php';
if (!file_exists($config_path)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ File konfigurasi tidak ditemukan di: $config_path\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ DATA BERHASIL DIUPDATE DI DATABASE LOKAL\\n⚠️ File konfigurasi Accurate tidak ditemukan.');window.location=('barang_kategori.php');</script>";
    exit;
}
include_once $config_path;

// Periksa konstanta API
if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Konfigurasi API tidak lengkap\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ DATA BERHASIL DIUPDATE DI DATABASE LOKAL\\n⚠️ Konfigurasi API Accurate tidak lengkap.');window.location=('barang_kategori.php');</script>";
    exit;
}

/**
 * Function untuk format timestamp sesuai dokumentasi Accurate
 */
function formatTimestamp($format = 'accurate') {
    switch ($format) {
        case 'iso8601':
            return date('Y-m-d\TH:i:sP', time() + (7 * 3600)); // WIB +0700
        case 'accurate':
        default:
            return date('d/m/Y H:i:s');
    }
}

/**
 * Function untuk generate API signature dengan HMAC SHA-256
 */
function generateApiSignature($timestamp, $signature_secret) {
    $signature = hash_hmac('sha256', $timestamp, $signature_secret, true);
    return base64_encode($signature);
}

/**
 * Function untuk mendapatkan host dari api-token.do
 */
function getAccurateHost($log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $url = ACCURATE_API_BASE_URL . '/api/api-token.do';

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Getting host from: $url\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code != 200) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Failed to get host: HTTP $http_code\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        $result = json_decode($response, true);
        if (!$result || !isset($result['s']) || $result['s'] != true) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Invalid api-token response\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        $host = $result['d']['database']['host'] ?? null;
        if (!$host) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Host not found in response\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Host extracted: $host\n", FILE_APPEND | LOCK_EX);
        return $host;

    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
}

/**
 * PERBAIKAN: Function untuk mencari kategori berdasarkan nama atau accurate_id
 */
function findCategoryInAccurate($host, $category_name, $accurate_id, $log_file) {
    try {
        // Strategi 1: Jika ada accurate_id, verifikasi dengan detail endpoint
        if (!empty($accurate_id) && $accurate_id != 'NULL' && is_numeric($accurate_id)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🎯 Verifying stored accurate_id: $accurate_id\n", FILE_APPEND | LOCK_EX);
            
            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
            $detail_url = $host . '/accurate/api/item-category/detail.do?id=' . $accurate_id;
            
            $ch = curl_init($detail_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . ACCURATE_API_TOKEN,
                "X-Api-Timestamp: $timestamp",
                "X-Api-Signature: $signature",
                "Accept: application/json"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📊 Detail check HTTP: $http_code\n", FILE_APPEND | LOCK_EX);

            if ($http_code == 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['s']) && $result['s'] == true && isset($result['d']['id'])) {
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Accurate ID $accurate_id is valid and exists\n", FILE_APPEND | LOCK_EX);
                    return $accurate_id;
                }
            }
            
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Stored accurate_id is not valid, searching by name\n", FILE_APPEND | LOCK_EX);
        }

        // Strategi 2: Cari berdasarkan nama menggunakan list endpoint
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Searching by name: '$category_name'\n", FILE_APPEND | LOCK_EX);
        
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $search_params = [
            'sp.pageSize' => '100',
            'fields' => 'id,name,code,description'
        ];
        
        $search_url = $host . '/accurate/api/item-category/list.do?' . http_build_query($search_params);
        
        $ch = curl_init($search_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📊 Search HTTP: $http_code\n", FILE_APPEND | LOCK_EX);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true && !empty($result['d'])) {
                $category_name_lower = strtolower(trim($category_name));
                
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📋 Found " . count($result['d']) . " categories, searching for '$category_name_lower'\n", FILE_APPEND | LOCK_EX);
                
                // Exact match first
                foreach ($result['d'] as $item) {
                    $item_name = isset($item['name']) ? strtolower(trim($item['name'])) : '';
                    $item_id = $item['id'] ?? null;
                    
                    if ($item_name === $category_name_lower && $item_id) {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Exact match found: ID $item_id\n", FILE_APPEND | LOCK_EX);
                        return $item_id;
                    }
                }
                
                // Partial match fallback
                foreach ($result['d'] as $item) {
                    $item_name = isset($item['name']) ? strtolower(trim($item['name'])) : '';
                    $item_id = $item['id'] ?? null;
                    
                    if (strpos($item_name, $category_name_lower) !== false && $item_id) {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Partial match found: ID $item_id\n", FILE_APPEND | LOCK_EX);
                        return $item_id;
                    }
                }
            }
        }

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Category not found\n", FILE_APPEND | LOCK_EX);
        return null;
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ findCategoryInAccurate Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return null;
    }
}

/**
 * PERBAIKAN: Function untuk update kategori ke Accurate
 */
function updateCategoryToAccurate($host, $category_data, $log_file, $koneksi, $local_id) {
    try {
        $save_url = $host . '/accurate/api/item-category/save.do';
        
        // Prepare data untuk update/create
        $accurate_data = [
            'name' => $category_data['name'],
            'defaultCategory' => 'false',
            'parentName' => ''
        ];
        
        // KUNCI: Jika ada category_id, tambahkan untuk UPDATE
        if (!empty($category_data['category_id'])) {
            $accurate_data['id'] = $category_data['category_id'];
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔄 UPDATE MODE: Using category ID " . $category_data['category_id'] . "\n", FILE_APPEND | LOCK_EX);
        } else {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🆕 CREATE MODE: No category ID, will create new\n", FILE_APPEND | LOCK_EX);
        }

        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🌐 Save URL: $save_url\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📤 Data: " . http_build_query($accurate_data) . "\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($save_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($accurate_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📊 Save HTTP Code: $http_code\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📥 Save Response: " . substr($response, 0, 300) . "\n", FILE_APPEND | LOCK_EX);

        if (!empty($curl_error)) {
            return [
                'success' => false,
                'error' => "cURL Error: $curl_error"
            ];
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                $operation = !empty($category_data['category_id']) ? 'UPDATED' : 'CREATED';
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Category $operation successfully\n", FILE_APPEND | LOCK_EX);
                
                // Get returned ID and update local database
                if (isset($result['d']['id'])) {
                    $returned_id = $result['d']['id'];
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📋 Returned category ID: $returned_id\n", FILE_APPEND | LOCK_EX);
                    
                    // Update accurate_id in local database
                    $update_query = "UPDATE tblitemjenis SET accurate_id='$returned_id' WHERE id='$local_id'";
                    if (mysqli_query($koneksi, $update_query)) {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 Updated local accurate_id to: $returned_id\n", FILE_APPEND | LOCK_EX);
                    }
                }
                
                return [
                    'success' => true,
                    'operation' => $operation,
                    'category_id' => $result['d']['id'] ?? null
                ];
            } else {
                $error_msg = isset($result['d']) ? (is_array($result['d']) ? implode(', ', $result['d']) : $result['d']) : 'Unknown error';
                return [
                    'success' => false,
                    'error' => "API Error: $error_msg"
                ];
            }
        } else {
            return [
                'success' => false,
                'error' => "HTTP Error: $http_code"
            ];
        }
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ updateCategoryToAccurate Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// === MAIN EXECUTION ===

// Step 1: Get host
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔄 Starting Accurate synchronization\n", FILE_APPEND | LOCK_EX);

$host = getAccurateHost($log_file);
if (!$host) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Cannot establish Accurate connection\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ DATA BERHASIL DIUPDATE DI DATABASE LOKAL\\n⚠️ Tidak dapat terhubung ke Accurate Online\\n\\nData tetap aman tersimpan di sistem lokal.\\nKode: $txtkd\\nNama: $txtnama');window.location=('barang_kategori.php');</script>";
    exit;
}

// Step 2: Find category in Accurate
$found_category_id = findCategoryInAccurate($host, $old_nama, $accurate_id, $log_file);

// Step 3: Update/Create category
$category_data = [
    'name' => $txtnama,
    'category_id' => $found_category_id // Will be null if not found (create mode)
];

$update_result = updateCategoryToAccurate($host, $category_data, $log_file, $koneksi, $id);

if ($update_result['success']) {
    $operation = $update_result['operation'];
    $category_id = $update_result['category_id'] ?? 'unknown';
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🎉 Synchronization successful: $operation\n", FILE_APPEND | LOCK_EX);
    
    echo "<script>
    var successMsg = '🎉 SUKSES TOTAL! 🎉\\n\\n';
    successMsg += '✅ Data kategori berhasil diupdate di database lokal\\n';
    successMsg += '✅ Data berhasil di$operation di Accurate Online\\n\\n';
    successMsg += 'Data Kategori:\\n';
    successMsg += 'Kode: $txtkd\\n';
    successMsg += 'Nama: $txtnama\\n';
    successMsg += 'Operation: $operation\\n';
    successMsg += 'Accurate ID: $category_id';
    window.alert(successMsg);
    window.location=('barang_kategori.php');
    </script>";
} else {
    $error_detail = $update_result['error'];
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Synchronization failed: $error_detail\n", FILE_APPEND | LOCK_EX);
    
    echo "<script>
    var errorMsg = '✅ DATA BERHASIL DIUPDATE DI DATABASE LOKAL\\n';
    errorMsg += '❌ GAGAL SINKRONISASI KE ACCURATE\\n\\n';
    errorMsg += 'Error: $error_detail\\n\\n';
    errorMsg += 'Data Kategori:\\n';
    errorMsg += 'Kode: $txtkd\\n';
    errorMsg += 'Nama: $txtnama\\n\\n';
    errorMsg += 'Data tetap aman tersimpan di sistem lokal.';
    window.alert(errorMsg);
    window.location=('barang_kategori.php');
    </script>";
}

// Log akhir eksekusi
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🏁 Script execution completed\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] " . str_repeat("=", 80) . "\n", FILE_APPEND | LOCK_EX);
?>