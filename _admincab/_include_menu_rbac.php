<?php
/**
 * Role-Based Access Control (RBAC) Menu System
 * File: _include_menu_rbac.php
 * 
 * Purpose: Centralized permission checking and menu rendering
 * Replaces: 67 static menu files (menu_adm01-04, menu_kasir01-03, etc.)
 */

// ============================================================================
// PERMISSION FUNCTIONS
// ============================================================================

/**
 * Get user's permissions from database
 * @param mysqli $koneksi Database connection
 * @param int $id_user User ID from session
 * @return array Array of permission codes
 */
function getUserPermissions($koneksi, $id_user) {
    // First, try to get permissions from kode_posisi
    $query = "SELECT u.kode_posisi, p.permissions, p.is_active
              FROM tbuser u
              LEFT JOIN tb_master_posisi p ON u.kode_posisi = p.kode_posisi
              WHERE u.id = '" . mysqli_real_escape_string($koneksi, $id_user) . "'";
    
    $result = mysqli_query($koneksi, $query);
    
    if (!$result) {
        error_log("[RBAC] Query error: " . mysqli_error($koneksi));
        return [];
    }
    
    $row = mysqli_fetch_assoc($result);
    
    if (!$row) {
        error_log("[RBAC] User not found: $id_user");
        return [];
    }
    
    // Check if position is active and has permissions
    if ($row['kode_posisi'] && $row['is_active'] === 'active' && $row['permissions']) {
        $perms = json_decode($row['permissions'], true);
        if (is_array($perms) && !empty($perms)) {
            return $perms;
        }
    }
    
    // Fallback: Grant basic dashboard access for all logged-in users
    // (since user_ak column doesn't exist in this database)
    return ['dashboard_read'];
}

/**
 * Map legacy user_ak levels to permission arrays (backward compatibility)
 * @param int $level Access level (1-99)
 * @return array Permission codes
 */
function mapLegacyAccessLevel($level) {
    // Super admin - full access
    if ($level === 1) {
        return ['*']; // Wildcard = all permissions
    }
    
    // Admin - most permissions
    if ($level <= 5) {
        return [
            'dashboard_read',
            'master_*', // All master data
            'pembelian_*',
            'penjualan_*',
            'servis_*',
            'laporan_*',
            'stok_*'
        ];
    }
    
    // Manager - limited admin access
    if ($level <= 10) {
        return [
            'dashboard_read',
            'master_read',
            'servis_read',
            'laporan_menu_read',
            'lap_servis_read',
            'lap_penjualan_read'
        ];
    }
    
    // Kasir - transaction focused
    if ($level <= 20) {
        return [
            'dashboard_read',
            'servis_menu_read',
            'servis_reguler_read',
            'input_servis_read',
            'penjualan_menu_read',
            'penjualan_read'
        ];
    }
    
    // Mekanik - service only
    if ($level <= 30) {
        return [
            'dashboard_read',
            'servis_menu_read',
            'servis_reguler_read',
            'input_servis_read',
            'lihat_servis_read'
        ];
    }
    
    // Default - minimal access
    return ['dashboard_read'];
}

/* 
// DUPLICATE FUNCTION: Already defined in permission_check.php
function hasPermission($user_permissions, $required_permission) {
    if (empty($user_permissions)) {
        return false;
    }
    
    // Check for wildcard (admin has all)
    if (in_array('*', $user_permissions, true)) {
        return true;
    }
    
    // Check exact match
    if (in_array($required_permission, $user_permissions, true)) {
        return true;
    }
    
    // Check wildcard patterns (e.g., "master_*" matches "master_read")
    foreach ($user_permissions as $perm) {
        if (str_ends_with($perm, '_*')) {
            $prefix = substr($perm, 0, -1); // Remove "*"
            if (str_starts_with($required_permission, $prefix)) {
                return true;
            }
        }
    }
    
    return false;
}
*/

function hasRbacPermission($user_permissions, $required_permission) {
    if (empty($user_permissions)) {
        return false;
    }

    // Check for wildcard (admin has all)
    if (in_array('*', $user_permissions, true)) {
        return true;
    }

    // Check exact match
    if (in_array($required_permission, $user_permissions, true)) {
        return true;
    }

    // Check wildcard patterns (e.g., "master_*" matches "master_read")
    foreach ($user_permissions as $perm) {
        if (str_ends_with($perm, '_*')) {
            $prefix = substr($perm, 0, -1); // Remove "*"
            if (str_starts_with($required_permission, $prefix)) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Recursively filter menu items by user permissions
 * @param array $menu_items Menu configuration array
 * @param array $user_permissions User's permission codes
 * @return array Filtered menu array
 */
function filterMenuByPermissions($menu_items, $user_permissions) {
    if (empty($menu_items)) {
        return [];
    }
    
    $filtered = [];
    
    foreach ($menu_items as $item) {
        // Check if user has permission for this item
        if (isset($item['permission'])) {
            if (!hasRbacPermission($user_permissions, $item['permission'])) {
                continue; // Skip this item - no permission
            }
        }
        
        // If item has submenu, filter it recursively
        if (isset($item['submenu']) && is_array($item['submenu'])) {
            $filtered_submenu = filterMenuByPermissions($item['submenu'], $user_permissions);
            
            // Only include parent if it has accessible children OR if parent itself has a URL
            if (!empty($filtered_submenu)) {
                $item['submenu'] = $filtered_submenu;
            } else {
                // No accessible submenu
                if (!isset($item['url'])) {
                    // Parent is just a container with no children - skip it
                    continue;
                }
                // Parent has URL but no submenu - keep it
                unset($item['submenu']);
            }
        }
        
        $filtered[] = $item;
    }
    
    return $filtered;
}

// ============================================================================
// MENU RENDERING FUNCTIONS
// ============================================================================

/**
 * Render menu HTML recursively
 * @param array $menu_items Filtered menu items
 * @param string $current_page Current page filename for active highlighting
 * @param int $level Nesting level (for CSS classes)
 * @return string HTML output
 */
function renderMenu($menu_items, $current_page = '', $level = 0) {
    if (empty($menu_items)) {
        if ($level === 0) {
            // No menu items at all - show message
            return '<ul class="nav nav-list">
                        <li class=""><a href="index.php">
                            <i class="menu-icon fa fa-home"></i>
                            <span class="menu-text">Dashboard</span>
                        </a></li>
                        <li class="divider"></li>
                        <li class=""><span class="menu-text" style="color: #999; padding: 10px;">
                            <i class="fa fa-warning"></i> Tidak ada menu yang tersedia untuk role Anda.
                        </span></li>
                    </ul>';
        }
        return '';
    }
    
    $ul_class = ($level === 0) ? 'nav nav-list' : 'submenu';
    $html = "<ul class=\"$ul_class\">\n";
    
    foreach ($menu_items as $item) {
        $has_submenu = isset($item['submenu']) && !empty($item['submenu']);
        $item_url = $item['url'] ?? '#';
        $is_active = ($item_url !== '#' && $item_url === $current_page);
        
        // Build CSS classes
        $li_classes = [];
        if ($is_active) $li_classes[] = 'active';
        if ($has_submenu) $li_classes[] = 'hover';
        
        $li_class_str = !empty($li_classes) ? ' class="' . implode(' ', $li_classes) . '"' : ' class=""';
        
        $html .= "\t<li$li_class_str>\n";
        
        if ($has_submenu) {
            // Parent menu with submenu
            $html .= "\t\t<a href=\"#\" class=\"dropdown-toggle\">\n";
            $html .= "\t\t\t<i class=\"menu-icon fa " . ($item['icon'] ?? 'fa-caret-right') . "\"></i>\n";
            $html .= "\t\t\t<span class=\"menu-text\">\n\t\t\t\t" . htmlspecialchars($item['title']) . "\n\t\t\t</span>\n";
            $html .= "\t\t\t<b class=\"arrow fa fa-angle-down\"></b>\n";
            $html .= "\t\t</a>\n";
            $html .= "\t\t<b class=\"arrow\"></b>\n\n";
            
            // Render submenu
            $html .= renderMenu($item['submenu'], $current_page, $level + 1);
        } else {
            // Simple menu item
            $html .= "\t\t<a href=\"" . htmlspecialchars($item_url) . "\">\n";
            $html .= "\t\t\t<i class=\"menu-icon fa " . ($item['icon'] ?? 'fa-caret-right') . "\"></i>\n";
            $html .= "\t\t\t<span class=\"menu-text\">" . htmlspecialchars($item['title']) . "</span>\n";
            $html .= "\t\t</a>\n";
            $html .= "\t\t<b class=\"arrow\"></b>\n";
        }
        
        $html .= "\t</li>\n";
    }
    
    $html .= "</ul>\n";
    
    return $html;
}

/**
 * Main function: Get and render filtered menu for current user
 * @param mysqli $koneksi Database connection
 * @param int $id_user User ID from session
 * @return string Rendered HTML menu
 */
function getRBACMenu($koneksi, $id_user) {
    // Get user permissions
    $user_permissions = getUserPermissions($koneksi, $id_user);
    
    // Load menu configuration
    $menu_config = include 'menu_config.php';
    if (!is_array($menu_config)) {
        error_log("[RBAC] Invalid menu_config.php - must return array");
        return '<ul class="nav nav-list"><li>Error loading menu</li></ul>';
    }
    
    // Filter menu by permissions
    $filtered_menu = filterMenuByPermissions($menu_config, $user_permissions);
    
    // Get current page for active highlighting
    $current_page = basename($_SERVER['PHP_SELF']);
    
    // Render menu HTML
    return renderMenu($filtered_menu, $current_page);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Check if user can access a specific page (for page-level protection)
 * @param mysqli $koneksi Database connection
 * @param int $id_user User ID
 * @param string $required_permission Permission code
 * @return bool True if allowed
 */
function canAccessPage($koneksi, $id_user, $required_permission) {
    $user_permissions = getUserPermissions($koneksi, $id_user);
    return hasRbacPermission($user_permissions, $required_permission);
}

/**
 * Redirect to 403 page if user lacks permission
 * @param mysqli $koneksi Database connection
 * @param int $id_user User ID
 * @param string $required_permission Permission code
 */
function requirePermission($koneksi, $id_user, $required_permission) {
    if (!canAccessPage($koneksi, $id_user, $required_permission)) {
        header("Location: 403.php?permission=" . urlencode($required_permission));
        exit;
    }
}

?>
