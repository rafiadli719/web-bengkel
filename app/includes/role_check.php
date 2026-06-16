<?php
/**
 * Role-Based Access Control Helper
 * Digunakan untuk check role dan permission di setiap file
 * 
 * Usage:
 * - checkRole(['ADM', 'MNG']); // Hanya admin dan manager yang boleh
 * - if(hasRole('ADM')) { ... } // Check role tertentu
 * - if(hasAnyRole(['ADM', 'MNG'])) { ... } // Check multiple roles
 */

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user has required role(s)
 * Redirect to login if not authenticated
 * Redirect to unauthorized page if role not allowed
 * 
 * @param array $required_roles Array of allowed roles
 * @return bool
 */
function checkRole($required_roles = []) {
    // Check if session exists
    if (empty($_SESSION['kode_karyawan']) || empty($_SESSION['role'])) {
        header("Location: ../login_dashboard/login.php?error=session_expired");
        exit();
    }
    
    $user_role = $_SESSION['role'];
    
    // If no specific roles required, just check if logged in
    if (empty($required_roles)) {
        return true;
    }
    
    // Check if user role is in allowed roles
    if (!in_array($user_role, $required_roles)) {
        // Log unauthorized access attempt
        error_log("Unauthorized access attempt - User: " . $_SESSION['kode_karyawan'] . 
                  ", Role: " . $user_role . ", Required: " . implode(',', $required_roles) . 
                  ", File: " . $_SERVER['PHP_SELF']);
        
        header("Location: index.php?error=unauthorized");
        exit();
    }
    
    return true;
}

/**
 * Check if user has specific role
 * 
 * @param string $role Role to check
 * @return bool
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user has any of the specified roles
 * 
 * @param array $roles Array of roles to check
 * @return bool
 */
function hasAnyRole($roles = []) {
    if (empty($roles) || !isset($_SESSION['role'])) {
        return false;
    }
    return in_array($_SESSION['role'], $roles);
}

/**
 * Check if user is super admin
 * 
 * @return bool
 */
function isSuperAdmin() {
    return hasRole('ADM') || hasRole('super_admin');
}

/**
 * Check if user is admin/manager
 * 
 * @return bool
 */
function isAdmin() {
    return hasAnyRole(['ADM', 'MNG', 'admin', 'super_admin']);
}

/**
 * Check if user is mekanik
 * 
 * @return bool
 */
function isMekanik() {
    return hasAnyRole(['MK', 'mekanik']);
}

/**
 * Check if user is kepala mekanik
 * 
 * @return bool
 */
function isKepalaMetanik() {
    return hasAnyRole(['KM', 'kepala_mekanik']);
}

/**
 * Check if user is kasir
 * 
 * @return bool
 */
function isKasir() {
    return hasAnyRole(['KSR', 'kasir']);
}

/**
 * Get current user information from session
 * 
 * @return array User information
 */
function getUserInfo() {
    return [
        'kode_karyawan' => $_SESSION['kode_karyawan'] ?? '',
        'nama_karyawan' => $_SESSION['nama_karyawan'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'kode_cabang' => $_SESSION['kode_cabang'] ?? '',
        'nama_cabang' => $_SESSION['nama_cabang'] ?? '',
        'login_time' => $_SESSION['login_time'] ?? '',
        'last_activity' => $_SESSION['last_activity'] ?? ''
    ];
}

/**
 * Get current user kode_karyawan
 * 
 * @return string
 */
function getCurrentUserCode() {
    return $_SESSION['kode_karyawan'] ?? '';
}

/**
 * Get current user name
 * 
 * @return string
 */
function getCurrentUserName() {
    return $_SESSION['nama_karyawan'] ?? '';
}

/**
 * Get current user role
 * 
 * @return string
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? '';
}

/**
 * Get current branch code
 * 
 * @return string
 */
function getCurrentBranchCode() {
    return $_SESSION['kode_cabang'] ?? '';
}

/**
 * Get current branch name
 * 
 * @return string
 */
function getCurrentBranchName() {
    return $_SESSION['nama_cabang'] ?? '';
}

/**
 * Check if user session is still valid
 * 
 * @param int $timeout Session timeout in seconds (default: 3600 = 1 hour)
 * @return bool
 */
function isSessionValid($timeout = 3600) {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    $elapsed = time() - $_SESSION['last_activity'];
    
    if ($elapsed > $timeout) {
        // Session expired
        session_destroy();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Logout user
 */
function logoutUser() {
    // Clear session
    $_SESSION = [];
    
    // Destroy session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    // Redirect to login
    header("Location: ../login_dashboard/login.php?logout=success");
    exit();
}

/**
 * Map old role values to new role codes
 * 
 * @param int $old_user_akses Old user_akses value (1-10)
 * @return string New role code (ADM, MNG, CS, KSR, MK, KM, PGD, CRM, KEU, HRD)
 */
function mapOldRoleToNew($old_user_akses) {
    $mapping = [
        1 => 'ADM',    // Administrator
        2 => 'CS',     // Customer Service
        3 => 'KSR',    // Kasir
        4 => 'MK',     // Mekanik
        5 => 'PGD',    // Pengadaan
        6 => 'CRM',    // CRM Staff
        7 => 'MNG',    // Manager
        8 => 'KEU',    // Keuangan
        9 => 'HRD',    // HRD Staff
        10 => 'KM'     // Kepala Mekanik
    ];
    
    return $mapping[$old_user_akses] ?? 'CS'; // Default to CS
}

/**
 * Map new role code to old user_akses value
 * 
 * @param string $role_code New role code (ADM, MNG, CS, KSR, MK, KM, PGD, CRM, KEU, HRD)
 * @return int Old user_akses value (1-10)
 */
function mapNewRoleToOld($role_code) {
    $mapping = [
        'ADM' => 1,    // Administrator
        'CS' => 2,     // Customer Service
        'KSR' => 3,    // Kasir
        'MK' => 4,     // Mekanik
        'PGD' => 5,    // Pengadaan
        'CRM' => 6,    // CRM Staff
        'MNG' => 7,    // Manager
        'KEU' => 8,    // Keuangan
        'HRD' => 9,    // HRD Staff
        'KM' => 10     // Kepala Mekanik
    ];
    
    return $mapping[$role_code] ?? 2; // Default to CS (2)
}

?>
