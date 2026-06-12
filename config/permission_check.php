<?php
/**
 * Permission Check Functions
 * Sistem kontrol akses berbasis permission
 * 
 * Usage:
 * <?php
 * session_start();
 * include 'config/permission_check.php';
 * 
 * // Check permission
 * if (!hasPermission('barang', 'add')) {
 *     die("Access Denied");
 * }
 * 
 * // Or die if no permission
 * checkPermissionOrDie('barang', 'add');
 * 
 * // Log activity
 * logActivity('SUCCESS', 'barang', 'Added new barang');
 * ?>
 */

if (file_exists(__DIR__ . '/auth_session.php')) {
    include_once __DIR__ . '/auth_session.php';
}

if (!function_exists('permission_code_candidates')) {
    function permission_code_candidates($module, $action = 'view') {
        $candidates = [
            $module . '_' . $action,
            $module . '.' . $action,
        ];

        $aliasMap = function_exists('auth_permission_aliases') ? auth_permission_aliases() : [];
        $aliasKey = $module . '.' . $action;
        if (isset($aliasMap[$aliasKey])) {
            foreach ($aliasMap[$aliasKey] as $alias) {
                $candidates[] = $alias;
            }
        }

        return array_values(array_unique($candidates));
    }
}

/**
 * Check if user has specific permission
 * 
 * @param string $module - Module name (barang, keluhan, workorder, etc)
 * @param string $action - Action (view, add, edit, delete, approve, reject, etc)
 * @return bool - True if user has permission, false otherwise
 */
function hasPermission($module, $action = 'view') {
    // Check if session exists
    if (empty($_SESSION['_iduser'])) {
        return false;
    }
    
    // Administrator (level 1) has access to everything
    if ($_SESSION['user_akses'] == 1) {
        return true;
    }

    $permissions = $_SESSION['_permissions'] ?? [];
    $permissionCodes = $_SESSION['_permission_codes'] ?? [];

    if (isset($permissions['*']['*']) && $permissions['*']['*'] === true) {
        return true;
    }

    if (isset($permissions[$module][$action]) && $permissions[$module][$action] === true) {
        return true;
    }

    foreach (permission_code_candidates($module, $action) as $candidate) {
        if (in_array($candidate, $permissionCodes, true)) {
            return true;
        }
    }

    if ((!isset($_SESSION['_permissions']) || !isset($_SESSION['_permission_codes'])) && !empty($_SESSION['_iduser'])) {
        global $koneksi;
        if ((!isset($koneksi) || !$koneksi) && file_exists(__DIR__ . '/koneksi.php')) {
            include __DIR__ . '/koneksi.php';
        }
        if (isset($koneksi) && $koneksi && function_exists('auth_get_user_context')) {
            $ctx = auth_get_user_context($koneksi, (int) $_SESSION['_iduser']);
            if ($ctx) {
                if (!empty($_SESSION['_cabang'])) {
                    $ctx['kode_cabang'] = $_SESSION['_cabang'];
                }
                auth_store_user_context($ctx);
                return hasPermission($module, $action);
            }
        }
    }

    return false;
}

/**
 * Check permission and die if not allowed
 * 
 * @param string $module - Module name
 * @param string $action - Action
 * @param string $custom_message - Custom error message (optional)
 */
function checkPermissionOrDie($module, $action = 'view', $custom_message = null) {
    if (!hasPermission($module, $action)) {
        // Log denied access
        logActivity('DENIED', $module, "Unauthorized access attempt to $action $module");
        
        // Show error message
        $message = $custom_message ?? "Access Denied: Anda tidak memiliki izin untuk mengakses fitur ini.";
        
        // If AJAX request, return JSON
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
        
        // Otherwise show HTML error
        die("<div style='padding: 20px; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px;'>
            <h4>⛔ Akses Ditolak</h4>
            <p>$message</p>
            <a href='javascript:history.back()' class='btn btn-sm btn-secondary'>Kembali</a>
        </div>");
    }
}

/**
 * Log user activity
 * 
 * @param string $status - Status (SUCCESS, FAILED, DENIED)
 * @param string $module - Module name
 * @param string $description - Activity description
 * @param string $action - Action type (optional)
 */
function logActivity($status, $module, $description, $action = null) {
    global $koneksi;
    
    // Get user info from session
    $user_id = $_SESSION['_iduser'] ?? 0;
    $username = $_SESSION['_username'] ?? 'Unknown';
    
    // Get action from POST/GET if not provided
    if ($action === null) {
        $action = $_POST['action'] ?? $_GET['action'] ?? 'view';
    }
    
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Get user agent
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);
    
    // If DB connection is not available, skip logging gracefully
    if (!isset($koneksi) || !$koneksi) {
        return false;
    }

    // Prepare SQL
    // Prepend status to description since status column doesn't exist
    $full_description = "[$status] $description";

    // Prepare SQL - Removed username and status columns that don't exist in table
    $sql = "INSERT INTO tb_user_activity_log 
            (user_id, action, module, description, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    try {
        if (!method_exists($koneksi, 'prepare')) {
            return false;
        }
        $stmt = $koneksi->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $koneksi->error);
            return false;
        }
        
        // Removed username and status from binding. Types: i (user_id), s (action), s (module), s (desc), s (ip), s (agent)
        $stmt->bind_param("isssss", $user_id, $action, $module, $full_description, $ip_address, $user_agent);
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Activity log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's role name
 * 
 * @return string - Role name
 */
function getRoleName() {
    return $_SESSION['_role_name'] ?? 'Unknown';
}

/**
 * Get user's access level
 * 
 * @return int - Access level (1-10)
 */
function getAccessLevel() {
    return $_SESSION['user_akses'] ?? 0;
}

/**
 * Get user's department
 * 
 * @return string - Department name
 */
function getDepartment() {
    return $_SESSION['_department'] ?? 'Unknown';
}

/**
 * Get user's branch code
 * 
 * @return string - Branch code
 */
function getBranchCode() {
    return $_SESSION['_cabang'] ?? '';
}

/**
 * Check if user is administrator
 * 
 * @return bool - True if user is admin
 */
function isAdmin() {
    return ($_SESSION['user_akses'] ?? 0) == 1;
}

/**
 * Get all permissions for current user
 * 
 * @return array - Array of permissions
 */
function getAllPermissions() {
    return $_SESSION['_permissions'] ?? [];
}

/**
 * Get permissions for specific module
 * 
 * @param string $module - Module name
 * @return array - Array of module permissions
 */
function getModulePermissions($module) {
    $permissions = $_SESSION['_permissions'] ?? [];
    return $permissions[$module] ?? [];
}

/**
 * Check if user can perform multiple actions
 * 
 * @param string $module - Module name
 * @param array $actions - Array of actions to check
 * @return bool - True if user has ALL actions
 */
function hasAllPermissions($module, $actions = []) {
    foreach ($actions as $action) {
        if (!hasPermission($module, $action)) {
            return false;
        }
    }
    return true;
}

/**
 * Check if user can perform ANY of multiple actions
 * 
 * @param string $module - Module name
 * @param array $actions - Array of actions to check
 * @return bool - True if user has ANY action
 */
function hasAnyPermission($module, $actions = []) {
    foreach ($actions as $action) {
        if (hasPermission($module, $action)) {
            return true;
        }
    }
    return false;
}

/**
 * Get list of accessible modules for current user
 * 
 * @return array - Array of module names
 */
function getAccessibleModules() {
    $permissions = $_SESSION['_permissions'] ?? [];
    return array_keys($permissions);
}

/**
 * Validate AJAX request
 * Check if request is valid AJAX and user has permission
 * 
 * @param string $module - Module name
 * @param string $action - Action name
 * @return bool - True if valid
 */
function validateAjaxRequest($module, $action = 'view') {
    // Check if AJAX request
    if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        logActivity('DENIED', $module, "Invalid AJAX request");
        return false;
    }
    
    // Check permission
    if (!hasPermission($module, $action)) {
        logActivity('DENIED', $module, "Unauthorized AJAX access to $action $module");
        return false;
    }
    
    return true;
}

/**
 * Return JSON response for AJAX
 * 
 * @param bool $success - Success status
 * @param string $message - Response message
 * @param array $data - Additional data (optional)
 */
function ajaxResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Return JSON error response for AJAX
 * 
 * @param string $message - Error message
 * @param int $code - Error code (optional)
 */
function ajaxError($message, $code = 400) {
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'code' => $code
    ]);
    exit;
}

/**
 * Return JSON success response for AJAX
 * 
 * @param string $message - Success message
 * @param array $data - Additional data (optional)
 */
function ajaxSuccess($message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

?>
