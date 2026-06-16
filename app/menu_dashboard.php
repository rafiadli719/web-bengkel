<?php
/**
 * Dynamic Role-Based Access Control Menu
 * File: menu_dashboard.php
 * 
 * Replaces: 67 static menu files (menu_adm01-04, menu_kasir01-03, etc.)
 * Uses: Permission-based filtering from tb_master_posisi
 */

// Load RBAC functions
require_once '_include_menu_rbac.php';

// Get user ID from session (should be set by parent page)
$id_user = $_SESSION['_iduser'] ?? 0;

if ($id_user > 0 && isset($koneksi)) {
    // Get and render RBAC menu
    echo getRBACMenu($koneksi, $id_user);
} else {
    // Fallback if session or connection not available
    echo '<ul class="nav nav-list">
            <li class=""><a href="index.php">
                <i class="menu-icon fa fa-home"></i>
                <span class="menu-text">Dashboard</span>
            </a></li>
            <li class="divider"></li>
            <li class=""><span class="menu-text" style="color: #999; padding: 10px;">
                <i class="fa fa-warning"></i> Session error. Please login again.
            </span></li>
          </ul>';
}
?>
