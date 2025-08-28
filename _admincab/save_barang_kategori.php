<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include file konfigurasi database
include "../config/koneksi.php";

// Ambil data dari form
$txtkd = $_POST['txtkd'] ?? null;
$txtnama = $_POST['txtnama'] ?? null;

// Inisialisasi file log
$log_file = 'accurate_category_update_log.txt';

// Log field hapus jika ada
$hapus_fields = [
    'hapus1' => $_POST['hapus1'] ?? 'Not set',
    'hapus2' => $_POST['hapus2'] ?? 'Not set',
    'hapus3' => $_POST['hapus3'] ?? 'Not set',
    'hapus4' => $_POST['hapus4'] ?? 'Not set'
];
file_put_contents(
    $log_file,
    "[" . date('Y-m-d H:i:s') . " WIB] 📋 Pengiriman Form - " .
    "hapus1: " . json_encode($hapus_fields['hapus1']) . ", " .
    "hapus2: " . json_encode($hapus_fields['hapus2']) . ", " .
    "hapus3: " . json_encode($hapus_fields['hapus3']) . ", " .
    "hapus4: " . json_encode($hapus_fields['hapus4']) . "\n",
    FILE_APPEND | LOCK_EX
);

// Validasi field wajib
if (empty($txtkd) || empty($txtnama)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Field wajib kosong: txtkd atau txtnama\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Kode dan Nama Kategori harus diisi.');window.location=('barang_kategori.php');</script>";
    exit;
}

// Simpan ke database lokal
$result = mysqli_query($koneksi, "INSERT INTO tblitemjenis (jenis, namajenis) VALUES ('$txtkd', '$txtnama')");
if (!$result) {
    $error = mysqli_error($koneksi);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal menyimpan ke database lokal: $error\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Gagal menyimpan ke database lokal. $error');window.location=('barang_kategori.php');</script>";
    exit;
}

// Log keberhasilan penyimpanan lokal
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 DATABASE LOKAL: Kategori '$txtnama' (Kode: $txtkd) - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);

// Include file konfigurasi Accurate
$config_path = '../config/accurate_config.php';
if (!file_exists($config_path)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ File konfigurasi tidak ditemukan di: $config_path\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: File konfigurasi accurate_config.php tidak ditemukan.');window.location=('barang_kategori.php');</script>";
    exit;
}
include_once $config_path;

// Periksa konstanta API
if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Konfigurasi API tidak lengkap\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Konfigurasi API tidak lengkap.');window.location=('barang_kategori.php');</script>";
    exit;
}

/**
 * Helper function untuk format timestamp sesuai dokumentasi Accurate
 * Format: dd/mm/yyyy hh:nn:ss
 */
function formatTimestamp() {
    return date('d/m/Y H:i:s');
}

/**
 * Helper function untuk generate API signature
 * HMAC-SHA256 dari timestamp + signature secret
 */
function generateApiSignature($timestamp, $signature_secret) {
    return base64_encode(hash_hmac('sha256', $timestamp, $signature_secret, true));
}

/**
 * FIXED: Function untuk mendapatkan host dari /api-token.do
 * Berdasarkan dokumentasi resmi: tidak perlu session untuk API Token
 */
function getAccurateHostFixed($log_file) {
    try {
        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $base_url = ACCURATE_API_BASE_URL;
        
        // Generate timestamp dan signature
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);
        
        $url = $base_url . '/api/api-token.do';
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 Calling api-token.do: $url\n", FILE_APPEND);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔒 X-Api-Timestamp: $timestamp\n", FILE_APPEND);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔒 X-Api-Signature: $signature\n", FILE_APPEND);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_token",
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 api-token.do HTTP Code: $http_code\n", FILE_APPEND);
        
        if (!empty($curl_error)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ api-token.do cURL Error: $curl_error\n", FILE_APPEND);
            return false;
        }
        
        if ($http_code == 200) {
            $result = json_decode($response, true);
            
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📄 Full api-token.do response: " . $response . "\n", FILE_APPEND);
            
            if ($result && isset($result['s']) && $result['s'] == true) {
                // Check different possible paths for host
                $host = null;
                $possible_paths = [
                    ['d', 'database', 'host'],
                    ['d', 'data usaha', 'host'],
                    ['d', 'host'],
                    ['host'],
                    ['d', 'dataUsaha', 'host'],
                    ['d', 'company', 'host']
                ];
                
                foreach ($possible_paths as $path) {
                    $temp = $result;
                    $path_str = implode('.', $path);
                    
                    foreach ($path as $key) {
                        if (isset($temp[$key])) {
                            $temp = $temp[$key];
                        } else {
                            $temp = null;
                            break;
                        }
                    }
                    
                    if ($temp && is_string($temp) && filter_var($temp, FILTER_VALIDATE_URL)) {
                        $host = $temp;
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ Host found at path [$path_str]: $host\n", FILE_APPEND);
                        break;
                    }
                }
                
                if ($host) {
                    return $host;
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Host not found in any expected path\n", FILE_APPEND);
                }
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ api-token.do returned success=false or invalid structure\n", FILE_APPEND);
                if (isset($result['d'])) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📄 Error details: " . json_encode($result['d']) . "\n", FILE_APPEND);
                }
            }
        } else {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ api-token.do HTTP Error: $http_code\n", FILE_APPEND);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📄 Response: " . substr($response, 0, 500) . "\n", FILE_APPEND);
        }
        
        return false;
        
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ getAccurateHost Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

/**
 * FIXED: Function untuk kirim kategori ke Accurate
 * Berdasarkan dokumentasi resmi - langsung ke endpoint tanpa session
 */
function sendCategoryToAccurateFixed($data, $log_file) {
    try {
        $endpoint = '/accurate/api/item-category/save.do'; // Sesuai dokumentasi
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🚀 STARTING CATEGORY API CALL: " . $endpoint . "\n", FILE_APPEND);
        
        // STEP 1: Get host
        $host = getAccurateHostFixed($log_file);
        if (!$host) {
            return [
                'success' => false,
                'error' => 'Cannot get Accurate host',
                'endpoint' => 'host_detection_failed'
            ];
        }
        
        // STEP 2: Prepare data sesuai dokumentasi resmi
        $accurate_data = [
            'name' => $data['name'], // Required: Nama Kategori Barang
            'defaultCategory' => $data['defaultCategory'] ? 'true' : 'false', // Optional: Boolean
            'parentName' => $data['parentName'] ?? '' // Optional: Nama kategori parent
        ];
        
        // Tambahkan ID jika ada (untuk update)
        if (isset($data['id']) && !empty($data['id'])) {
            $accurate_data['id'] = $data['id'];
        }
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📤 Category Data: " . http_build_query($accurate_data) . "\n", FILE_APPEND);
        
        // STEP 3: Generate timestamp dan signature
        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);
        
        // STEP 4: Build URL dengan path yang benar
        $url = $host . $endpoint;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 Category URL: $url\n", FILE_APPEND);
        
        // STEP 5: Setup cURL dengan header yang sesuai dokumentasi
        // PENTING: Untuk API Token TIDAK PERLU X-Session-ID
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_token",
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($accurate_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');
        
        // STEP 6: Execute
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📡 Executing category API request...\n", FILE_APPEND);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 Category HTTP Code: $http_code\n", FILE_APPEND);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📥 Category Response: " . substr($response, 0, 500) . "\n", FILE_APPEND);
        
        if (!empty($curl_error)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Category cURL Error: " . $curl_error . "\n", FILE_APPEND);
            return [
                'success' => false,
                'error' => "cURL Error: " . $curl_error,
                'endpoint' => $url
            ];
        }
        
        // STEP 7: Process response
        if ($http_code == 200) {
            // Cek apakah response adalah halaman login
            if (stripos($response, '<html>') !== false && stripos($response, 'login') !== false) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Received login page\n", FILE_APPEND);
                return [
                    'success' => false,
                    'error' => 'Authentication failed. API Token invalid or expired.',
                    'endpoint' => $url
                ];
            }
            
            $json_result = json_decode($response, true);
            if ($json_result !== null) {
                if (isset($json_result['s']) && $json_result['s'] == true) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ CATEGORY SUCCESS!\n", FILE_APPEND);
                    if (isset($json_result['d']) && isset($json_result['d']['id'])) {
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📋 Category ID: " . $json_result['d']['id'] . "\n", FILE_APPEND);
                    }
                    return ['success' => true, 'data' => $json_result, 'endpoint' => $url];
                } elseif (isset($json_result['s']) && $json_result['s'] == false) {
                    $error_msg = isset($json_result['d']) ? (is_array($json_result['d']) ? implode(', ', $json_result['d']) : $json_result['d']) : 'Unknown error';
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Category API Error: $error_msg\n", FILE_APPEND);
                    return [
                        'success' => false,
                        'error' => "API Error: $error_msg",
                        'endpoint' => $url
                    ];
                }
            } else {
                // Coba cek response text untuk indikasi sukses
                if (stripos($response, 'success') !== false || 
                    stripos($response, 'berhasil') !== false ||
                    preg_match('/^[0-9]+$/', trim($response))) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ Success (text response)\n", FILE_APPEND);
                    return ['success' => true, 'data' => trim($response), 'endpoint' => $url];
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ Unexpected response format\n", FILE_APPEND);
                }
            }
        } elseif ($http_code == 302) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Getting 302 redirect\n", FILE_APPEND);
            return [
                'success' => false,
                'error' => 'Getting redirect. Check API token permissions for item category.',
                'endpoint' => $url
            ];
        } else {
            // Handle other HTTP codes
            $error_messages = [
                401 => 'API Token tidak valid atau sudah expired',
                403 => 'Akses ditolak. Periksa permission API token untuk item category',
                404 => 'Endpoint tidak ditemukan',
                500 => 'Internal server error'
            ];
            
            $error_msg = $error_messages[$http_code] ?? "HTTP Error Code: $http_code";
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ HTTP $http_code: $error_msg\n", FILE_APPEND);
            
            return [
                'success' => false,
                'error' => $error_msg,
                'endpoint' => $url
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Unexpected response format',
            'endpoint' => $url
        ];
        
    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ CATEGORY EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND);
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage(),
            'endpoint' => 'exception'
        ];
    }
}

// Sinkronisasi dengan Accurate menggunakan metode yang diperbaiki
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔄 Memulai sinkronisasi kategori dengan Accurate (API Token)...\n", FILE_APPEND | LOCK_EX);

$data_to_sync = [
    'name' => $txtnama,  // Required field
    'defaultCategory' => false,  // Optional - set ke false untuk kategori biasa
    'parentName' => ''  // Optional - kosong untuk kategori root
];

try {
    $sync_result = sendCategoryToAccurateFixed($data_to_sync, $log_file);
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📌 Hasil sinkronisasi: " . ($sync_result['success'] ? "Berhasil" : "Gagal") . "\n", FILE_APPEND | LOCK_EX);
    
    if ($sync_result['success']) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🎉 Sinkronisasi kategori ke Accurate berhasil\n", FILE_APPEND | LOCK_EX);
        echo "<script>window.alert('🎉 SUKSES TOTAL! 🎉\\n\\n✅ Data kategori tersimpan di database lokal\\n✅ Data berhasil disinkronisasi ke Accurate Online\\n\\nKode: $txtkd\\nNama: $txtnama\\nEndpoint: {$sync_result['endpoint']}');window.location=('barang_kategori.php');</script>";
    } else {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ Sinkronisasi kategori ke Accurate gagal: {$sync_result['error']}\n", FILE_APPEND | LOCK_EX);
        echo "<script>window.alert('✅ DATA TERSIMPAN DI DATABASE LOKAL\\n❌ GAGAL SINKRONISASI KE ACCURATE\\n\\nError: {$sync_result['error']}\\nEndpoint: {$sync_result['endpoint']}\\n\\nData tetap aman tersimpan di sistem lokal.\\nKode: $txtkd\\nNama: $txtnama');window.location=('barang_kategori.php');</script>";
    }
    
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Exception saat sinkronisasi: $error_msg\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('✅ DATA TERSIMPAN DI DATABASE LOKAL\\n❌ EXCEPTION SAAT SINKRONISASI\\n\\nError: $error_msg\\n\\nData tetap aman tersimpan di sistem lokal.\\nKode: $txtkd\\nNama: $txtnama');window.location=('barang_kategori.php');</script>";
}

// Log akhir eksekusi
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🏁 Eksekusi script kategori selesai\n", FILE_APPEND | LOCK_EX);
?>