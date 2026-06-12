<?php
/**
 * Role-Based Access Control (RBAC) System
 * Sistem kontrol akses berbasis role/peran
 * 
 * Usage:
 * <?php
 * session_start();
 * include 'config/rbac.php';
 * 
 * // Check if user has permission
 * if (!hasPermission('view_barang')) {
 *     die("Access Denied");
 * }
 * ?>
 */

// Define role permissions
$role_permissions = [
    1 => [ // Administrator
        'view_barang',
        'add_barang',
        'edit_barang',
        'delete_barang',
        'view_keluhan',
        'add_keluhan',
        'edit_keluhan',
        'delete_keluhan',
        'approve_keluhan',
        'reject_keluhan',
        'view_workorder',
        'add_workorder',
        'edit_workorder',
        'delete_workorder',
        'view_users',
        'add_users',
        'edit_users',
        'delete_users',
        'view_reports',
        'export_reports',
        'system_settings',
        'view_logs',
        'manage_roles'
    ],
    2 => [ // Customer Service
        'view_barang',
        'view_keluhan',
        'add_keluhan',
        'edit_keluhan',
        'view_workorder',
        'add_workorder',
        'edit_workorder',
        'view_reports'
    ],
    3 => [ // Cashier
        'view_barang',
        'view_keluhan',
        'view_workorder',
        'view_reports',
        'process_payment',
        'print_invoice'
    ],
    4 => [ // Mechanic
        'view_barang',
        'view_keluhan',
        'view_workorder',
        'update_workorder_status',
        'add_workorder_notes',
        'view_reports'
    ],
    5 => [ // Procurement
        'view_barang',
        'add_barang',
        'edit_barang',
        'view_reports',
        'manage_suppliers',
        'create_purchase_order'
    ],
    6 => [ // CRM
        'view_barang',
        'view_keluhan',
        'view_workorder',
        'view_customers',
        'add_customers',
        'edit_customers',
        'view_reports',
        'export_reports'
    ],
    7 => [ // Management
        'view_barang',
        'view_keluhan',
        'view_workorder',
        'view_reports',
        'export_reports',
        'view_analytics',
        'view_logs'
    ],
    8 => [ // Finance
        'view_barang',
        'view_reports',
        'export_reports',
        'view_financial_reports',
        'manage_invoices',
        'view_payments'
    ],
    9 => [ // HRD
        'view_users',
        'add_users',
        'edit_users',
        'delete_users',
        'view_employees',
        'add_employees',
        'edit_employees',
        'view_reports',
        'manage_roles'
    ]
];

// Define role names
$role_names = [
    1 => 'Administrator',
    2 => 'Customer Service',
    3 => 'Cashier',
    4 => 'Mechanic',
    5 => 'Procurement',
    6 => 'CRM',
    7 => 'Management',
    8 => 'Finance',
    9 => 'HRD'
];

// Define role descriptions
$role_descriptions = [
    1 => 'Full system access with administrative privileges',
    2 => 'Customer service representative access',
    3 => 'Cashier and payment processing access',
    4 => 'Mechanic work order and service access',
    5 => 'Procurement and inventory management',
    6 => 'Customer relationship management',
    7 => 'Management and reporting access',
    8 => 'Financial reporting and accounting',
    9 => 'Human resources and employee management'
];

/**
 * Check if user has specific permission
 * 
 * @param string $permission - Permission name to check
 * @return bool - True if user has permission, False otherwise
 */
function hasPermission($permission) {
    global $role_permissions, $user_akses;
    
    if (!isset($role_permissions[$user_akses])) {
        return false;
    }
    
    return in_array($permission, $role_permissions[$user_akses]);
}

/**
 * Check if user has any of the specified permissions
 * 
 * @param array $permissions - Array of permission names
 * @return bool - True if user has any of the permissions
 */
function hasAnyPermission($permissions) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all of the specified permissions
 * 
 * @param array $permissions - Array of permission names
 * @return bool - True if user has all permissions
 */
function hasAllPermissions($permissions) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission)) {
            return false;
        }
    }
    return true;
}

/**
 * Require specific permission, deny access if not granted
 * 
 * @param string $permission - Permission name to require
 * @param string $message - Custom error message (optional)
 */
function requirePermission($permission, $message = null) {
    if (!hasPermission($permission)) {
        $message = $message ?? "Anda tidak memiliki izin untuk mengakses fitur ini.";
        http_response_code(403);
        die("
            <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
                <h2 style='color: #d32f2f;'>Access Denied</h2>
                <p>$message</p>
                <p><a href='javascript:history.back()'>Kembali</a></p>
            </div>
        ");
    }
}

/**
 * Require any of the specified permissions
 * 
 * @param array $permissions - Array of permission names
 * @param string $message - Custom error message (optional)
 */
function requireAnyPermission($permissions, $message = null) {
    if (!hasAnyPermission($permissions)) {
        $message = $message ?? "Anda tidak memiliki izin untuk mengakses fitur ini.";
        http_response_code(403);
        die("
            <div style='text-align: center; margin-top: 50px; font-family: Arial, sans-serif;'>
                <h2 style='color: #d32f2f;'>Access Denied</h2>
                <p>$message</p>
                <p><a href='javascript:history.back()'>Kembali</a></p>
            </div>
        ");
    }
}

/**
 * Get all permissions for current user role
 * 
 * @return array - Array of permissions
 */
function getUserPermissions() {
    global $role_permissions, $user_akses;
    
    return $role_permissions[$user_akses] ?? [];
}

/**
 * Get role name
 * 
 * @param int $role_id - Role ID (optional, uses current user if not specified)
 * @return string - Role name
 */
function getRoleName($role_id = null) {
    global $role_names, $user_akses;
    
    $role_id = $role_id ?? $user_akses;
    return $role_names[$role_id] ?? 'Unknown';
}

/**
 * Get role description
 * 
 * @param int $role_id - Role ID (optional, uses current user if not specified)
 * @return string - Role description
 */
function getRoleDescription($role_id = null) {
    global $role_descriptions, $user_akses;
    
    $role_id = $role_id ?? $user_akses;
    return $role_descriptions[$role_id] ?? 'No description available';
}

/**
 * Check if user has admin role
 * 
 * @return bool - True if user is admin
 */
function isAdmin() {
    global $user_akses;
    return $user_akses == 1;
}

/**
 * Check if user has specific role
 * 
 * @param int $role_id - Role ID to check
 * @return bool - True if user has the role
 */
function hasRole($role_id) {
    global $user_akses;
    return $user_akses == $role_id;
}

/**
 * Get all available roles
 * 
 * @return array - Array of roles with id, name, and description
 */
function getAllRoles() {
    global $role_names, $role_descriptions;
    
    $roles = [];
    foreach ($role_names as $id => $name) {
        $roles[] = [
            'id' => $id,
            'name' => $name,
            'description' => $role_descriptions[$id] ?? ''
        ];
    }
    return $roles;
}

/**
 * Display permission-based content
 * 
 * @param string $permission - Permission to check
 * @param string $content - Content to display if permission granted
 * @param string $fallback - Fallback content if permission denied (optional)
 */
function showIfPermitted($permission, $content, $fallback = '') {
    if (hasPermission($permission)) {
        echo $content;
    } else {
        echo $fallback;
    }
}

?>
