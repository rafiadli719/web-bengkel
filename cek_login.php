<?php
session_start();
include 'config/koneksi.php';
include_once 'config/auth_session.php';

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
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $txtnama = isset($_POST['txtnama']) ? trim($_POST['txtnama']) : '';
    $txtpass = isset($_POST['txtpass']) ? trim($_POST['txtpass']) : '';
    $cbocabang = isset($_POST['cbocabang']) ? trim($_POST['cbocabang']) : '';

    if ($txtnama === '' || $txtpass === '') {
        $_SESSION['login_error'] = "Username dan password wajib diisi.";
        header("Location: index.php");
        exit;
    }

    $stmt = mysqli_prepare($koneksi, "SELECT id, user_akses FROM tbuser WHERE nama_user = ? AND password = ? AND status_row = '0' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ss", $txtnama, $txtpass);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $tm_cari = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if ($tm_cari) {
        $id_user = (int) $tm_cari['id'];
        $lvl_akses = $tm_cari['user_akses'];

        $userContext = auth_get_user_context($koneksi, $id_user);
        if (!$userContext) {
            $_SESSION['login_error'] = "Gagal memuat konteks user.";
            header("Location: index.php");
            exit;
        }

        session_regenerate_id(true);
        if ($cbocabang !== '') {
            $userContext['kode_cabang'] = $cbocabang;
        }

        auth_store_user_context($userContext);
        
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

        // RBAC MENU SYSTEM: Redirect ALL users to _admincab
        // Menu sidebar will auto-filter based on permissions from tb_master_posisi
        $location = 'panel/';

        error_log("Successful login for user: $txtnama (ID: $id_user, Access Level: $lvl_akses) - Redirecting to: $location");
        header("Location: $location");
        exit;
    } else {
        $_SESSION['login_error'] = "Username atau Password salah!";
        error_log("Failed login attempt for username: $txtnama");
        header("Location: index.php");
        exit;
    }
}
?>
