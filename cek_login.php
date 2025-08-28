<?php
session_start();
include 'config/koneksi.php';

// FIXED: Load accurate config dengan path yang benar dan proper error checking
$config_loaded = false;
$config_error = null;

// Coba load config file accurate
if (file_exists('config/accurate_config.php')) {
    try {
        include_once 'config/accurate_config.php';
        $config_loaded = true;
    } catch (Exception $e) {
        $config_error = "Error loading accurate_config.php: " . $e->getMessage();
        error_log($config_error);
    }
} else {
    $config_error = "File config/accurate_config.php tidak ditemukan";
    error_log($config_error);
}

// REMOVED: Semua function definitions yang sudah ada di accurate_config.php
// Tidak perlu define ulang karena sudah ada di config file

// Jika config tidak ter-load, define fallback functions (tanpa redeclaration)
if (!$config_loaded) {
    // FALLBACK: Define minimal functions jika config gagal di-load
    if (!function_exists('getAccurateHost')) {
        function getAccurateHost() {
            error_log("getAccurateHost called but accurate_config.php not loaded properly");
            return false; // Return false jika config tidak ter-load
        }
    }
    
    if (!function_exists('testAccurateConnection')) {
        function testAccurateConnection() {
            return [
                'success' => false,
                'error' => 'Accurate config not loaded'
            ];
        }
    }
}

/**
 * FIXED: Function untuk get accurate host dengan proper error handling
 * Menggunakan functions dari accurate_config.php
 */
function getAccurateHostForLogin() {
    try {
        // Pastikan function dan konstanta tersedia
        if (!function_exists('formatTimestamp') || 
            !function_exists('generateApiSignature') || 
            !defined('ACCURATE_API_TOKEN') || 
            !defined('ACCURATE_SIGNATURE_SECRET') || 
            !defined('ACCURATE_API_BASE_URL')) {
            
            error_log("Accurate config functions/constants not available");
            return false;
        }
        
        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $base_url = ACCURATE_API_BASE_URL;
        
        // Generate timestamp dan signature
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);
        
        $url = $base_url . '/api/api-token.do';
        
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor-Login/1.0');
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Log untuk debugging
        $log_msg = date('Y-m-d H:i:s') . " - Login Accurate API Test: HTTP $http_code";
        if ($curl_error) {
            $log_msg .= " - cURL Error: $curl_error";
        }
        error_log($log_msg);
        
        if ($http_code == 200 && empty($curl_error)) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                // Check multiple possible paths for host
                $possible_paths = [
                    ['d', 'database', 'host'],
                    ['d', 'data usaha', 'host'],
                    ['d', 'host'],
                    ['host']
                ];
                
                foreach ($possible_paths as $path) {
                    $temp = $result;
                    foreach ($path as $key) {
                        if (isset($temp[$key])) {
                            $temp = $temp[$key];
                        } else {
                            $temp = null;
                            break;
                        }
                    }
                    
                    if ($temp && is_string($temp) && filter_var($temp, FILTER_VALIDATE_URL)) {
                        error_log("Accurate host found: $temp");
                        return $temp;
                    }
                }
                
                error_log("Accurate API response successful but host not found in expected paths");
                error_log("Response: " . json_encode($result));
            } else {
                error_log("Accurate API response invalid: " . json_encode($result));
            }
        } else {
            error_log("Accurate API call failed: HTTP $http_code" . ($curl_error ? ", cURL: $curl_error" : ""));
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Exception in getAccurateHostForLogin: " . $e->getMessage());
        return false;
    }
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txtnama = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $txtpass = mysqli_real_escape_string($koneksi, $_POST['txtpass']);
    $cbocabang = mysqli_real_escape_string($koneksi, $_POST['cbocabang']);

    // Query untuk cek login
    $data = mysqli_query($koneksi, "SELECT * FROM tbuser 
                                    WHERE nama_user='$txtnama' AND password='$txtpass' AND status_row='0'");
    $cek = mysqli_num_rows($data);

    if ($cek > 0) {
        $cari_kd = mysqli_query($koneksi, "SELECT id, user_akses FROM tbuser WHERE nama_user='$txtnama'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        $id_user = $tm_cari['id'];
        $lvl_akses = $tm_cari['user_akses'];
        
        // Set session variables
        $_SESSION['_iduser'] = $id_user;
        $_SESSION['_cabang'] = $cbocabang;
        $_SESSION['user_akses'] = $lvl_akses;
        
        // Cek apakah cabang dipilih jika diperlukan
        if (($lvl_akses == '2' || $lvl_akses == '3' || $lvl_akses == '4' || $lvl_akses == '5') && $cbocabang == '') {
            $_SESSION['login_error'] = "Anda Harus Memilih Cabang Terlebih Dahulu!";
            header("Location: index.php");
            exit;
        }

        // FIXED: Test koneksi ke Accurate API dengan proper error handling
        if ($config_loaded) {
            try {
                $test_host = getAccurateHostForLogin();
                if ($test_host) {
                    $_SESSION['accurate_host'] = $test_host;
                    $_SESSION['accurate_status'] = 'connected';
                    $_SESSION['login_success'] = "Login berhasil! Accurate API terhubung ke: " . parse_url($test_host, PHP_URL_HOST);
                    error_log("Login successful with Accurate API connected to: $test_host");
                } else {
                    $_SESSION['accurate_status'] = 'disconnected';
                    $_SESSION['login_success'] = "Login berhasil! (Accurate API tidak terhubung - periksa konfigurasi)";
                    error_log("Login successful but Accurate API not connected");
                }
            } catch (Exception $e) {
                $_SESSION['accurate_status'] = 'error';
                $_SESSION['login_success'] = "Login berhasil! (Accurate API error: " . $e->getMessage() . ")";
                error_log("Accurate API Error during login: " . $e->getMessage());
            }
        } else {
            // Config tidak ter-load
            $_SESSION['accurate_status'] = 'config_error';
            $_SESSION['login_success'] = "Login berhasil! (Accurate config error: " . ($config_error ?? 'Unknown error') . ")";
            error_log("Login successful but Accurate config not loaded: " . ($config_error ?? 'Unknown error'));
        }

        // Arahkan ke halaman sesuai user_akses
        $base_url = "https://fitmotor.web.id/beta/aplikasi/";
        switch ($lvl_akses) {
            case '1':
                $location = $cbocabang == '' ? '_admin/index.php' : '_admincab/index.php';
                break;
            case '2':
                $location = '_cs/index.php';
                break;
            case '3':
                $location = '_kasir/index.php';
                break;
            case '4':
                $location = '_mekanik/index.php';
                break;
            case '5':
                $location = '_pengadaan/index.php';
                break;
            case '6':
                $location = '_crm/index.php';
                break;
            case '7':
                $location = '_managemen/index.php';
                break;
            case '8':
                $location = '_keuangan/index.php';
                break;
            case '9':
                $location = '_hrd/index.php';
                break;
            default:
                $location = 'index.php';
        }
        
        error_log("Redirecting user $txtnama (access level $lvl_akses) to: $base_url$location");
        header("Location: $base_url$location");
        exit;
    } else {
        $_SESSION['login_error'] = "Username atau Password salah!";
        error_log("Failed login attempt for username: $txtnama");
        header("Location: index.php");
        exit;
    }
}
?>