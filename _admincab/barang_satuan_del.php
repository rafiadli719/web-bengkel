<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "../config/accurate_config.php";

// Inisialisasi file log
$log_file = 'accurate_unit_delete_log.txt';

// Ambil ID satuan dari parameter GET
$txtid = $_GET['kd'] ?? null;

if (empty($txtid)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ ID satuan kosong\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: ID satuan tidak ditemukan.');window.location=('barang_satuan.php');</script>";
    exit;
}

// PERBAIKAN: Cek apakah kolom accurate_id ada, jika tidak gunakan tanpa accurate_id
$columns_check = mysqli_query($koneksi, "SHOW COLUMNS FROM tblitemsatuan LIKE 'accurate_id'");
$has_accurate_id = mysqli_num_rows($columns_check) > 0;

if ($has_accurate_id) {
    // Jika ada kolom accurate_id
    $cari_kd = mysqli_query($koneksi, "SELECT satuan, accurate_id FROM tblitemsatuan WHERE id='$txtid'");
} else {
    // Jika tidak ada kolom accurate_id
    $cari_kd = mysqli_query($koneksi, "SELECT satuan FROM tblitemsatuan WHERE id='$txtid'");
}

if (!$cari_kd || mysqli_num_rows($cari_kd) == 0) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Satuan dengan ID $txtid tidak ditemukan di database lokal\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Satuan tidak ditemukan di database lokal.');window.location=('barang_satuan.php');</script>";
    exit;
}

$tm_cari = mysqli_fetch_array($cari_kd);
$nama_satuan = $tm_cari['satuan'];
$accurate_id = $has_accurate_id ? ($tm_cari['accurate_id'] ?? null) : null;

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 DELETE UNIT - ID: $txtid, Nama: $nama_satuan\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 Table has accurate_id column: " . ($has_accurate_id ? 'YES' : 'NO') . "\n", FILE_APPEND | LOCK_EX);
if ($has_accurate_id) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 Accurate ID: " . ($accurate_id ?? 'NULL') . "\n", FILE_APPEND | LOCK_EX);
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
 * PERBAIKAN: Function untuk mencari satuan berdasarkan nama atau accurate_id (tanpa session)
 */
function findUnitInAccurate($host, $unit_name, $accurate_id, $log_file) {
    try {
        // Strategi 1: Jika ada accurate_id, verifikasi dengan detail endpoint
        if (!empty($accurate_id) && $accurate_id != 'NULL' && is_numeric($accurate_id)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🎯 Verifying stored accurate_id: $accurate_id\n", FILE_APPEND | LOCK_EX);
            
            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
            $detail_url = $host . '/accurate/api/unit/detail.do?id=' . $accurate_id;
            
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
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Searching by name: '$unit_name'\n", FILE_APPEND | LOCK_EX);
        
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $search_params = [
            'sp.pageSize' => '100',
            'fields' => 'id,name,code,description'
        ];
        
        $search_url = $host . '/accurate/api/unit/list.do?' . http_build_query($search_params);
        
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
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📥 Search response: " . substr($response, 0, 500) . "\n", FILE_APPEND | LOCK_EX);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true && !empty($result['d'])) {
                $unit_name_lower = strtolower(trim($unit_name));
                
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📋 Found " . count($result['d']) . " units, searching for '$unit_name_lower'\n", FILE_APPEND | LOCK_EX);
                
                // Log semua hasil untuk debugging
                foreach ($result['d'] as $index => $item) {
                    $item_id = $item['id'] ?? 'no-id';
                    $item_name = $item['name'] ?? 'no-name';
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 [$index] ID: $item_id, Name: '$item_name'\n", FILE_APPEND | LOCK_EX);
                }
                
                // Jika hanya ada sedikit hasil, ambil yang pertama
                if (count($result['d']) <= 5) {
                    foreach ($result['d'] as $item) {
                        $item_id = $item['id'] ?? null;
                        if ($item_id) {
                            $actual_name = $item['name'] ?? 'Unknown';
                            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔄 Few results found, using: '$actual_name' (ID: $item_id)\n", FILE_APPEND | LOCK_EX);
                            return $item_id;
                        }
                    }
                }
                
                // Exact match first
                foreach ($result['d'] as $item) {
                    $item_name = isset($item['name']) ? strtolower(trim($item['name'])) : '';
                    $item_id = $item['id'] ?? null;
                    
                    if ($item_name === $unit_name_lower && $item_id) {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Exact match found: ID $item_id\n", FILE_APPEND | LOCK_EX);
                        return $item_id;
                    }
                }
                
                // Partial match fallback
                foreach ($result['d'] as $item) {
                    $item_name = isset($item['name']) ? strtolower(trim($item['name'])) : '';
                    $item_id = $item['id'] ?? null;
                    
                    if (strpos($item_name, $unit_name_lower) !== false && $item_id) {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Partial match found: ID $item_id\n", FILE_APPEND | LOCK_EX);
                        return $item_id;
                    }
                }
            } else {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ No units found in response or API error\n", FILE_APPEND | LOCK_EX);
            }
        } else {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Search HTTP Error: $http_code\n", FILE_APPEND | LOCK_EX);
        }

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Unit not found with any strategy\n", FILE_APPEND | LOCK_EX);
        return null;
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ findUnitInAccurate Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return null;
    }
}

/**
 * PERBAIKAN: Function untuk menghapus satuan dari Accurate (tanpa session)
 */
function deleteUnitFromAccurate($host, $unit_id, $unit_name, $log_file) {
    try {
        $delete_url = $host . '/accurate/api/unit/delete.do';
        
        // Prioritas menggunakan ID, fallback ke unitName
        if (!empty($unit_id)) {
            $delete_data = ['id' => $unit_id];
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🗑️ Deleting unit by ID: $unit_id\n", FILE_APPEND | LOCK_EX);
        } else {
            $delete_data = ['unitName' => $unit_name];
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🗑️ Deleting unit by name: $unit_name\n", FILE_APPEND | LOCK_EX);
        }

        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🌐 Delete URL: $delete_url\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📤 Delete data: " . json_encode($delete_data) . "\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($delete_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);  // PERBAIKAN: Gunakan POST bukan DELETE
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($delete_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📊 Delete HTTP Code: $http_code\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📥 Delete response: " . substr($response, 0, 300) . "\n", FILE_APPEND | LOCK_EX);

        if (!empty($curl_error)) {
            return [
                'success' => false,
                'error' => "cURL Error: $curl_error"
            ];
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Unit delete SUCCESS\n", FILE_APPEND | LOCK_EX);
                return [
                    'success' => true,
                    'deleted_id' => $unit_id,
                    'deleted_name' => $unit_name
                ];
            } else {
                $error_msg = isset($result['d']) ? (is_array($result['d']) ? implode(', ', $result['d']) : $result['d']) : 'Unknown error';
                return [
                    'success' => false,
                    'error' => "API Error: $error_msg"
                ];
            }
        } else {
            $error_messages = [
                400 => 'Bad Request - Data tidak valid',
                401 => 'API Token tidak valid atau expired',
                403 => 'Akses ditolak - periksa permission',
                404 => 'Satuan tidak ditemukan',
                422 => 'Satuan sedang digunakan dan tidak dapat dihapus',
                500 => 'Server error'
            ];
            $error_msg = $error_messages[$http_code] ?? "HTTP Error: $http_code";
            return [
                'success' => false,
                'error' => $error_msg
            ];
        }
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ deleteUnitFromAccurate Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// === MAIN EXECUTION ===

// Step 1: Hapus dari database lokal
$modal = mysqli_query($koneksi, "DELETE FROM tblitemsatuan WHERE id='$txtid'");
if (!$modal) {
    $error = mysqli_error($koneksi);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal menghapus dari database lokal: $error\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Gagal menghapus satuan dari database lokal. $error');window.location=('barang_satuan.php');</script>";
    exit;
}

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 DATABASE LOKAL: Satuan '$nama_satuan' dihapus - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);

// Step 2: Periksa apakah konfigurasi Accurate tersedia
if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Konfigurasi Accurate tidak lengkap\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ SATUAN BERHASIL DIHAPUS DARI DATABASE LOKAL\\n⚠️ Konfigurasi Accurate tidak lengkap\\n\\nData Satuan: $nama_satuan');window.location=('barang_satuan.php');</script>";
    exit;
}

// Step 3: Get host
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔄 Starting Accurate synchronization\n", FILE_APPEND | LOCK_EX);

$host = getAccurateHost($log_file);
if (!$host) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Cannot establish Accurate connection\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ DATA BERHASIL DIHAPUS DARI DATABASE LOKAL\\n⚠️ Tidak dapat terhubung ke Accurate Online\\n\\nData tetap aman tersimpan di sistem lokal.\\nSatuan: $nama_satuan');window.location=('barang_satuan.php');</script>";
    exit;
}

// Step 4: Find unit in Accurate
$found_unit_id = findUnitInAccurate($host, $nama_satuan, $accurate_id, $log_file);

if (!$found_unit_id) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Satuan tidak ditemukan di Accurate, akan coba delete by name\n", FILE_APPEND | LOCK_EX);
    
    // Fallback: coba delete berdasarkan nama saja
    $delete_result = deleteUnitFromAccurate($host, null, $nama_satuan, $log_file);
    
    if ($delete_result['success']) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🎉 SUKSES delete by name\n", FILE_APPEND | LOCK_EX);
        echo "<script>
        var successMsg = '🎉 SUKSES TOTAL! 🎉\\n\\n';
        successMsg += '✅ Satuan berhasil dihapus dari database lokal\\n';
        successMsg += '✅ Satuan berhasil dihapus dari Accurate Online (by name)\\n\\n';
        successMsg += 'Data Satuan: $nama_satuan';
        window.alert(successMsg);
        window.location=('barang_satuan.php');
        </script>";
        exit;
    } else {
        echo "<script>
        var warningMsg = '✅ SATUAN BERHASIL DIHAPUS DARI DATABASE LOKAL\\n';
        warningMsg += '⚠️ SATUAN TIDAK DITEMUKAN DI ACCURATE\\n\\n';
        warningMsg += 'Kemungkinan satuan sudah tidak ada di Accurate.\\n\\n';
        warningMsg += 'Data Satuan: $nama_satuan\\n';
        warningMsg += 'Error: {$delete_result['error']}';
        window.alert(warningMsg);
        window.location=('barang_satuan.php');
        </script>";
        exit;
    }
}

// Step 5: Delete unit from Accurate using ID
$delete_result = deleteUnitFromAccurate($host, $found_unit_id, $nama_satuan, $log_file);

if ($delete_result['success']) {
    $deleted_id = $delete_result['deleted_id'];
    $deleted_name = $delete_result['deleted_name'];
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🎉 Synchronization successful: DELETED\n", FILE_APPEND | LOCK_EX);
    
    echo "<script>
    var successMsg = '🎉 SUKSES TOTAL! 🎉\\n\\n';
    successMsg += '✅ Satuan berhasil dihapus dari database lokal\\n';
    successMsg += '✅ Satuan berhasil dihapus dari Accurate Online\\n\\n';
    successMsg += 'Data Satuan:\\n';
    successMsg += 'Nama: $deleted_name\\n';
    successMsg += 'Accurate ID: $deleted_id';
    window.alert(successMsg);
    window.location=('barang_satuan.php');
    </script>";
} else {
    $error_detail = $delete_result['error'];
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Synchronization failed: $error_detail\n", FILE_APPEND | LOCK_EX);
    
    echo "<script>
    var errorMsg = '✅ SATUAN BERHASIL DIHAPUS DARI DATABASE LOKAL\\n';
    errorMsg += '❌ GAGAL SINKRONISASI KE ACCURATE\\n\\n';
    errorMsg += 'Error: $error_detail\\n\\n';
    errorMsg += 'Data Satuan:\\n';
    errorMsg += 'Nama: $nama_satuan\\n';
    errorMsg += 'Target ID: $found_unit_id\\n\\n';
    errorMsg += 'Data tetap aman tersimpan di sistem lokal.';
    window.alert(errorMsg);
    window.location=('barang_satuan.php');
    </script>";
}

// Log akhir eksekusi
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🏁 Script execution completed\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] " . str_repeat("=", 80) . "\n", FILE_APPEND | LOCK_EX);
?>