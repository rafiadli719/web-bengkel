<?php
// Load menu configuration
$menu_items = include 'menu_config.php';

// Function to check if a menu item or any of its children is active
function is_menu_active($item) {
    $current_page = basename($_SERVER['PHP_SELF']);
    if (isset($item['url']) && $item['url'] === $current_page) {
        return true;
    }
    if (isset($item['submenu'])) {
        foreach ($item['submenu'] as $sub) {
            if (is_menu_active($sub)) {
                return true;
            }
        }
    }
    return false;
}

// Function to check if user has permission for a menu item
function has_menu_permission($item) {
    // If no permission defined, assume public/allowed
    if (!isset($item['permission'])) {
        return true;
    }
    
    // Check specific permission
    if (function_exists('rbac_has')) {
        return rbac_has($item['permission']);
    }
    
    return true; // Fallback if rbac not loaded
}

// Recursive function to render menu
function render_menu($items, $level = 0) {
    $html = '';
    if ($level === 0) {
        $html .= '<ul class="nav nav-list">';
    } else {
        $html .= '<ul class="submenu">';
    }

    foreach ($items as $item) {
        // Skip if no permission
        if (!has_menu_permission($item)) {
            continue;
        }

        $active_class = is_menu_active($item) ? 'active' : '';
        $open_class = (is_menu_active($item) && isset($item['submenu'])) ? 'open' : '';
        $classes = trim("$active_class $open_class");
        
        $html .= '<li class="' . $classes . '">';
        
        $url = isset($item['url']) ? $item['url'] : '#';
        $toggle_class = isset($item['submenu']) ? 'dropdown-toggle' : '';
        
        $html .= '<a href="' . $url . '" class="' . $toggle_class . '">';
        
        if (isset($item['icon'])) {
            $html .= '<i class="menu-icon fa ' . $item['icon'] . '"></i>';
        } elseif ($level > 0) {
             $html .= '<i class="menu-icon fa fa-caret-right"></i>';
        }
        
        if ($level === 0) {
            $html .= '<span class="menu-text"> ' . $item['title'] . ' </span>';
        } else {
            $html .= $item['title'];
        }

        if (isset($item['submenu'])) {
            $html .= '<b class="arrow fa fa-angle-down"></b>';
        }
        
        $html .= '</a>';
        $html .= '<b class="arrow"></b>';

        if (isset($item['submenu'])) {
            $html .= render_menu($item['submenu'], $level + 1);
        }

        $html .= '</li>';
    }

    $html .= '</ul>';
    return $html;
}

echo render_menu($menu_items);
?>
