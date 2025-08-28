<?php
session_start();
if (!isset($_SESSION['_iduser']) || empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

include "../config/koneksi.php";
include "../config/accurate_config.php";

// Aktifkan error reporting untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$txtnama = $_POST['txtnama'] ?? null;
$txtdeskripsi = $_POST['txtdeskripsi'] ?? '';

$log_file = 'accurate_brand_save_log.txt';

// Validasi input
if (empty($txtnama)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Error: Nama pabrik barang harus diisi\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('❌ Error: Nama Pabrik Barang harus diisi!');window.location='barang_pabrik_add.php';</script>";
    exit;
}

// Validasi panjang nama
if (strlen($txtnama) < 2) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Error: Nama pabrik terlalu pendek\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('❌ Error: Nama Pabrik Barang minimal 2 karakter!');window.location='barang_pabrik_add.php';</script>";
    exit;
}

// Clean input
$txtnama = strtoupper(trim($txtnama));
$txtdeskripsi = trim($txtdeskripsi);

// Cek duplikasi di database lokal
$check_query = "SELECT pabrik_barang FROM tbpabrik_barang WHERE UPPER(pabrik_barang) = '" . mysqli_real_escape_string($koneksi, $txtnama) . "'";
$check_result = mysqli_query($koneksi, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Error: Pabrik '$txtnama' sudah ada\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('❌ Error: Pabrik Barang \"$txtnama\" sudah ada!');window.location='barang_pabrik_add.php';</script>";
    exit;
}

// Simpan ke Database Lokal
$insert_query = "INSERT INTO tbpabrik_barang (pabrik_barang, deskripsi, created_at) VALUES (?, ?, NOW())";
$stmt = mysqli_prepare($koneksi, $insert_query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $txtnama, $txtdeskripsi);
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        $error = mysqli_error($koneksi);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal menyimpan ke database lokal: $error\n", FILE_APPEND | LOCK_EX);
        echo "<script>alert('❌ Error: Gagal menyimpan ke database lokal. $error');window.location='barang_pabrik_add.php';</script>";
        exit;
    }
    
    mysqli_stmt_close($stmt);
} else {
    $error = mysqli_error($koneksi);
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal prepare statement: $error\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('❌ Error: Gagal menyimpan ke database lokal. $error');window.location='barang_pabrik_add.php';</script>";
    exit;
}

// Logging
file_put_contents($log_file, "\n======= NEW BRAND: $txtnama =======\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 💾 LOCAL DATABASE: Brand $txtnama - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);

// Accurate API Functions
function formatTimestamp() {
    return date('d/m/Y H:i:s');
}

function generateApiSignature($timestamp, $secret) {
    return base64_encode(hash_hmac('sha256', $timestamp, $secret, true));
}

function getAccurateHost($log_file) {
    try {
        if (isset($GLOBALS['ACCURATE_HOST']) && !empty($GLOBALS['ACCURATE_HOST'])) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📋 Using cached host: " . $GLOBALS['ACCURATE_HOST'] . "\n", FILE_APPEND);
            return $GLOBALS['ACCURATE_HOST'];
        }

        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $base_url = ACCURATE_API_BASE_URL;

        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);

        $url = $base_url . '/api/api-token.do';

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 Calling api-token.do: $url\n", FILE_APPEND);

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
                    $GLOBALS['ACCURATE_HOST'] = $host;
                    return $host;
                } else {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Host not found in any expected path\n", FILE_APPEND);
                    $host = findUrlInResponse($result, $log_file);
                    if ($host) {
                        $GLOBALS['ACCURATE_HOST'] = $host;
                        return $host;
                    }
                }
            }
        }

        return false;

    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ getAccurateHost Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}

function findUrlInResponse($data, $log_file, $path = '') {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $current_path = $path ? "$path.$key" : $key;

            if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔍 Found URL at [$current_path]: $value\n", FILE_APPEND);
                return $value;
            } else if (is_array($value)) {
                $result = findUrlInResponse($value, $log_file, $current_path);
                if ($result) {
                    return $result;
                }
            }
        }
    }
    return null;
}

function sendBrandToAccurate($brandName, $description, $log_file) {
    try {
        // Coba beberapa endpoint yang mungkin untuk kategori/merek
        $endpoints = [
            '/accurate/api/item-category/save.do',
            '/accurate/api/item/category/save.do',
            '/accurate/api/itemcategory/save.do'
        ];

        $host = getAccurateHost($log_file);
        if (!$host) {
            return [
                'success' => false,
                'error' => 'Cannot get Accurate host',
                'endpoint' => 'host_detection_failed'
            ];
        }

        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;

        foreach ($endpoints as $endpoint) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🚀 TRYING ENDPOINT: " . $endpoint . "\n", FILE_APPEND);

            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, $signature_secret);
            $url = $host . $endpoint;

            // Data untuk kategori item (merek barang)
            $data = [
                'name' => $brandName,
                'notes' => $description,
                'suspended' => 'false'
            ];

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 URL: $url\n", FILE_APPEND);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📤 Data: " . json_encode($data) . "\n", FILE_APPEND);

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
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 HTTP Code: $http_code\n", FILE_APPEND);
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📖 Response: " . substr($response, 0, 500) . "\n", FILE_APPEND);

            if (!empty($curl_error)) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ cURL Error: " . $curl_error . "\n", FILE_APPEND);
                continue; // Coba endpoint berikutnya
            }

            if ($http_code == 200) {
                if (stripos($response, 'html') !== false && strpos($response, 'login') !== false) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Received login page\n", FILE_APPEND);
                    continue; // Coba endpoint berikutnya
                }

                $json_result = json_decode($response, true);
                if ($json_result !== null) {
                    if (isset($json_result['s']) && $json_result['s'] === true) {
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ SUCCESS with endpoint: $endpoint ✅\n", FILE_APPEND | LOCK_EX);
                        return ['success' => true, 'data' => $json_result, 'endpoint' => $url];
                    } else if (isset($json_result['s']) && $json_result['s'] === false) {
                        $error_msg = '';
                        if (isset($json_result['d']) && is_array($json_result['d'])) {
                            foreach ($json_result['d'] as $error_item) {
                                if (is_array($error_item)) {
                                    $error_msg .= implode('; ', $error_item) . '. ';
                                } else {
                                    $error_msg .= $error_item . '. ';
                                }
                            }
                        } else {
                            $error_msg = is_array($json_result['d']) ? implode(', ', $json_result['d']) : ($json_result['d'] ?? 'Unknown error');
                        }
                        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ API Error with $endpoint: $error_msg\n", FILE_APPEND);
                        
                        // Jika error karena endpoint tidak ada, coba yang lain
                        if (strpos(strtolower($error_msg), 'not found') !== false || 
                            strpos(strtolower($error_msg), '404') !== false) {
                            continue; // Coba endpoint berikutnya
                        } else {
                            // Error lain, return error
                            return [
                                'success' => false,
                                'error' => "API Error: $error_msg",
                                'endpoint' => $url
                            ];
                        }
                    }
                }
            } else if ($http_code == 404) {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ Endpoint $endpoint not found, trying next...\n", FILE_APPEND);
                continue; // Coba endpoint berikutnya
            } else {
                file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ HTTP $http_code with $endpoint\n", FILE_APPEND);
                continue; // Coba endpoint berikutnya
            }
        }

        // Jika semua endpoint gagal, coba buat dummy item dengan kategori ini
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔄 All category endpoints failed, trying to create dummy item with brand category...\n", FILE_APPEND);
        return createDummyItemWithBrand($brandName, $description, $log_file);

    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ EXCEPTION: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage(),
            'endpoint' => 'exception'
        ];
    }
}

function createDummyItemWithBrand($brandName, $description, $log_file) {
    try {
        $endpoint = '/accurate/api/item/save.do';
        
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🚀 CREATING DUMMY ITEM: " . $endpoint . "\n", FILE_APPEND);

        $host = getAccurateHost($log_file);
        if (!$host) {
            return [
                'success' => false,
                'error' => 'Cannot get Accurate host',
                'endpoint' => 'host_detection_failed'
            ];
        }

        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);

        $url = $host . $endpoint;

        // Buat dummy item dengan kategori brand ini
        $dummy_code = 'BRAND_' . strtoupper(str_replace(' ', '_', $brandName)) . '_DUMMY';
        $data = [
            'itemType' => 'NON_INVENTORY',
            'name' => "DUMMY - Brand $brandName",
            'no' => $dummy_code,
            'itemCategoryName' => $brandName,
            'notes' => "Dummy item untuk membuat kategori brand: $brandName. $description",
            'controlQuantity' => 'false',
            'suspended' => 'true' // Suspend dummy item
        ];

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 URL: $url\n", FILE_APPEND);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📤 Dummy Item Data: " . json_encode($data) . "\n", FILE_APPEND);

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
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📊 Dummy Item HTTP Code: $http_code\n", FILE_APPEND);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 📖 Dummy Item Response: " . substr($response, 0, 500) . "\n", FILE_APPEND);

        if (!empty($curl_error)) {
            file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Dummy Item cURL Error: " . $curl_error . "\n", FILE_APPEND);
            return [
                'success' => false,
                'error' => "cURL Error: " . $curl_error,
                'endpoint' => $url
            ];
        }

        if ($http_code == 200) {
            $json_result = json_decode($response, true);
            if ($json_result !== null) {
                if (isset($json_result['s']) && $json_result['s'] === true) {
                    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ DUMMY ITEM SUCCESS - Brand category created! ✅\n", FILE_APPEND | LOCK_EX);
                    return [
                        'success' => true, 
                        'data' => $json_result, 
                        'endpoint' => $url,
                        'method' => 'dummy_item'
                    ];
                } else {
                    $error_msg = isset($json_result['d']) ? 
                        (is_array($json_result['d']) ? implode(', ', $json_result['d']) : $json_result['d']) : 
                        'Unknown error';
                    return [
                        'success' => false,
                        'error' => "Dummy Item API Error: $error_msg",
                        'endpoint' => $url
                    ];
                }
            }
        }

        return [
            'success' => false,
            'error' => "HTTP Error: $http_code",
            'endpoint' => $url
        ];

    } catch (Exception $e) {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ Dummy Item Exception: " . $e->getMessage() . "\n", FILE_APPEND);
        return [
            'success' => false,
            'error' => 'Exception: ' . $e->getMessage(),
            'endpoint' => 'exception'
        ];
    }
}

// Sinkronisasi ke Accurate jika terhubung
if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected') {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🌐 ACCURATE API SYNC: Starting brand sync for '$txtnama'\n", FILE_APPEND | LOCK_EX);
    
    $result = sendBrandToAccurate($txtnama, $txtdeskripsi, $log_file);

    if ($result['success']) {
        $method_info = isset($result['method']) ? " (via " . $result['method'] . ")" : "";
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🎉 ACCURATE BRAND SYNC SUCCESS!$method_info Brand synchronized via " . $result['endpoint'] . "\n", FILE_APPEND | LOCK_EX);
        
        if (isset($result['method']) && $result['method'] == 'dummy_item') {
            echo "<script>alert('🎉 SUKSES TOTAL! 🎉\\n\\n✅ Data Pabrik Barang tersimpan di database lokal\\n✅ Merek berhasil dibuat di Accurate Online sebagai kategori item\\n\\n📝 Catatan: Kategori dibuat melalui dummy item yang telah disuspend\\n\\nNama: $txtnama\\nDeskripsi: $txtdeskripsi\\nEndpoint: " . $result['endpoint'] . "');window.location='barang_pabrik.php';</script>";
        } else {
            echo "<script>alert('🎉 SUKSES TOTAL! 🎉\\n\\n✅ Data Pabrik Barang tersimpan di database lokal\\n✅ Merek berhasil disinkronisasi ke Accurate Online\\n\\nNama: $txtnama\\nDeskripsi: $txtdeskripsi\\nEndpoint: " . $result['endpoint'] . "');window.location='barang_pabrik.php';</script>";
        }
    } else {
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ ACCURATE BRAND SYNC FAILED: " . $result['error'] . "\n", FILE_APPEND | LOCK_EX);
        echo "<script>alert('✅ DATA TERSIMPAN DI DATABASE LOKAL\\n❌ GAGAL SINKRONISASI MEREK KE ACCURATE\\n\\nError: " . addslashes($result['error']) . "\\nEndpoint: " . $result['endpoint'] . "\\n\\n💡 Tip: Anda dapat membuat kategori ini secara manual di:\\nAccurate Online > Master Data > Item > Kategori\\n\\nData tetap aman tersimpan di sistem lokal.\\nNama: $txtnama');window.location='barang_pabrik.php';</script>";
    }
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ ACCURATE BRAND SYNC SKIPPED: Not connected\n", FILE_APPEND | LOCK_EX);
    echo "<script>alert('✅ DATA TERSIMPAN DI DATABASE LOKAL\\n⚠️ Sinkronisasi Merek ke Accurate tidak dilakukan karena koneksi tidak tersedia\\n\\n💡 Tip: Setelah koneksi Accurate tersedia, Anda dapat membuat kategori secara manual di:\\nAccurate Online > Master Data > Item > Kategori\\n\\nNama: $txtnama');window.location='barang_pabrik.php';</script>";
}

// Cleanup
if (ob_get_level()) {
    ob_end_flush();
}
flush();
exit;
?>