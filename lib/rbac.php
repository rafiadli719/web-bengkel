<?php
if (!function_exists('rbac_permissions')) {
    function rbac_permissions() {
        static $perms = null;
        if ($perms !== null) return $perms;
        if (!isset($_SESSION['_iduser'])) { $perms = []; return $perms; }
        $userId = $_SESSION['_iduser'];
        global $koneksi;
        $perms = [];
        $roleCode = null;
        $q = mysqli_query($koneksi, "SELECT user_akses FROM tbuser WHERE id='".mysqli_real_escape_string($koneksi, $userId)."' LIMIT 1");
        if ($q && ($r = mysqli_fetch_assoc($q))) { $roleCode = (int)$r['user_akses']; }
        if ($roleCode === null) { return $perms; }

        // Modified to use tb_master_posisi
        $res = @mysqli_query($koneksi, "SELECT permissions FROM tb_master_posisi WHERE user_akses_level=".(int)$roleCode." LIMIT 1");
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $json = $row['permissions'];
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $perms = $decoded;
            }
        }
        if (empty($perms)) {
            $fallback = [
                1 => ['all'],
                2 => ['service_read','service_create','customer_read','customer_create','payment_read','payment_create','issue_read'],
                3 => ['payment_read','payment_create','issue_read'],
                4 => ['service_read','service_update_progress','task_read','task_update'],
                5 => ['purchase_read','purchase_create','inventory_read'],
                6 => ['customer_read','customer_update','marketing_read','issue_read'],
                7 => ['report_read','dashboard_read','issue_read','issue_approve_supervisor','issue_merge_customer_approve'],
                8 => ['finance_read','finance_create','report_read','issue_read','issue_approve_finansial'],
                9 => ['employee_read','employee_create','payroll_read'],
                10 => ['service_read','service_update','team_assign','quality_check'],
            ];
            if (isset($fallback[$roleCode])) { $perms = $fallback[$roleCode]; }
        }
        return $perms;
    }
}
if (!function_exists('rbac_has')) {
    function rbac_has($permission) {
        $perms = rbac_permissions();
        if (in_array('all', $perms, true)) return true;
        return in_array($permission, $perms, true);
    }
}
if (!function_exists('rbac_any')) {
    function rbac_any($permissions) {
        $perms = rbac_permissions();
        if (in_array('all', $perms, true)) return true;
        foreach ($permissions as $p) { if (in_array($p, $perms, true)) return true; }
        return false;
    }
}
if (!function_exists('rbac_require')) {
    function rbac_require($permission) {
        if (!rbac_has($permission)) {
            if (!headers_sent()) {
                header("Location: 403.php");
            } else {
                echo "<script>location.href='403.php';</script>";
            }
            exit;
        }
    }
}
if (!function_exists('rbac_require_any')) {
    function rbac_require_any($permissions) {
        if (!rbac_any($permissions)) {
            if (!headers_sent()) {
                header("Location: 403.php");
            } else {
                echo "<script>location.href='403.php';</script>";
            }
            exit;
        }
    }
}
