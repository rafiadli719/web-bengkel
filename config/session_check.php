<?php
/**
 * Session Check & Security Middleware
 * Include this file at the beginning of every protected page
 * 
 * Usage:
 * <?php
 * session_start();
 * include 'config/session_check.php';
 * ?>
 */

// SECURITY: Check if session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (file_exists(__DIR__ . '/auth_session.php')) {
    include_once __DIR__ . '/auth_session.php';
}

// SECURITY: Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php?session_expired=1");
    exit;
}

// SECURITY: Session timeout (30 minutes)
$session_timeout = 30 * 60; // 30 minutes
$current_time = time();

if (isset($_SESSION['last_activity'])) {
    $time_elapsed = $current_time - $_SESSION['last_activity'];
    
    if ($time_elapsed > $session_timeout) {
        // Session expired
        $_SESSION = array();
        session_destroy();
        
        header("Location: ../index.php?session_expired=1");
        exit;
    }
}

// Update last activity time
$_SESSION['last_activity'] = $current_time;

// SECURITY: Get user data from session
$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'] ?? '';
$user_akses = $_SESSION['user_akses'] ?? 0;
$login_time = $_SESSION['login_time'] ?? 0;

// SECURITY: Validate user access level
if (!is_numeric($user_akses) || $user_akses < 1 || $user_akses > 10) {
    $_SESSION = array();
    session_destroy();
    header("Location: ../index.php?invalid_access=1");
    exit;
}

// SECURITY: Define access level names
$access_levels = [
    1 => 'Administrator',
    2 => 'Customer Service',
    3 => 'Cashier',
    4 => 'Mechanic',
    5 => 'Procurement',
    6 => 'CRM',
    7 => 'Management',
    8 => 'Finance',
    9 => 'HRD',
    10 => 'Kepala Mekanik'
];

$current_role = $access_levels[$user_akses] ?? 'Unknown';

// Sync richer session context if missing.
if (
    function_exists('auth_get_user_context') &&
    (!isset($_SESSION['_permissions']) || !isset($_SESSION['_role_name']) || !isset($_SESSION['_username']))
) {
    if (!isset($koneksi) || !$koneksi) {
        $koneksiFile = __DIR__ . '/koneksi.php';
        if (file_exists($koneksiFile)) {
            include_once $koneksiFile;
        }
    }

    if (isset($koneksi) && $koneksi) {
        $userContext = auth_get_user_context($koneksi, $id_user);
        if ($userContext) {
            if ($kd_cabang !== '') {
                $userContext['kode_cabang'] = $kd_cabang;
            }
            auth_store_user_context($userContext);
            $kd_cabang = $_SESSION['_cabang'] ?? $kd_cabang;
            $user_akses = $_SESSION['user_akses'] ?? $user_akses;
        }
    }
}

// SECURITY: Log session activity
// error_log("Session active for user: $id_user (Role: $current_role) at " . date('Y-m-d H:i:s'));

/**
 * Function untuk check user access level
 * 
 * @param int|array $required_levels - Level akses yang diizinkan
 * @return bool - True jika user memiliki akses, False jika tidak
 */
function checkAccess($required_levels) {
    global $user_akses;
    
    if (!is_array($required_levels)) {
        $required_levels = [$required_levels];
    }
    
    return in_array($user_akses, $required_levels);
}

/**
 * Function untuk deny access jika user tidak memiliki permission
 * 
 * @param int|array $required_levels - Level akses yang diizinkan
 * @param string $message - Pesan error (optional)
 */
function requireAccess($required_levels, $message = null) {
    if (!checkAccess($required_levels)) {
        $message = $message ?? "Anda tidak memiliki akses ke halaman ini.";
        die("
            <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
                <h2 style='color: #d32f2f;'>Access Denied</h2>
                <p>$message</p>
                <p><a href='../index.php'>Kembali ke Login</a></p>
            </div>
        ");
    }
}

/**
 * Function untuk get user info dari database
 * 
 * @param mysqli $koneksi - Database connection
 * @return array - User data
 */
function getUserInfo($koneksi) {
    global $id_user;
    
    $stmt = $koneksi->prepare("SELECT id, nama_user, password, foto_user, user_akses, role_name, department FROM tbuser WHERE id = ?");
    
    if (!$stmt) {
        error_log("Prepared statement error: " . $koneksi->error);
        return null;
    }
    
    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $stmt->close();
        return $user_data;
    }
    
    $stmt->close();
    return null;
}

/**
 * Function untuk get branch info dari database
 * 
 * @param mysqli $koneksi - Database connection
 * @return array - Branch data
 */
function getBranchInfo($koneksi) {
    global $kd_cabang;
    
    if (empty($kd_cabang)) {
        return null;
    }
    
    $stmt = $koneksi->prepare("SELECT kode_cabang, nama_cabang, tipe_cabang, alamat FROM tbcabang WHERE kode_cabang = ?");
    
    if (!$stmt) {
        error_log("Prepared statement error: " . $koneksi->error);
        return null;
    }
    
    $stmt->bind_param("s", $kd_cabang);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $branch_data = $result->fetch_assoc();
        $stmt->close();
        return $branch_data;
    }
    
    $stmt->close();
    return null;
}

/**
 * Function untuk log user activity
 * 
 * @param mysqli $koneksi - Database connection
 * @param string $action - Action description
 * @param string $details - Additional details (optional)
 */
function logActivity($koneksi, $action, $details = null) {
    global $id_user, $kd_cabang;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $timestamp = date('Y-m-d H:i:s');
    
    $stmt = $koneksi->prepare("
        INSERT INTO tb_user_activity_log 
        (user_id, action, details, ip_address, user_agent, timestamp) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt) {
        $stmt->bind_param("isssss", $id_user, $action, $details, $ip_address, $user_agent, $timestamp);
        $stmt->execute();
        $stmt->close();
    }
}

?>
