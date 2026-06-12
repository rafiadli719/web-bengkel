<?php
/**
 * Save Role AJAX Handler
 * Menyimpan role baru ke database
 */

session_start();
include "../../config/koneksi.php";
include "../../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('users', 'manage_roles')) {
    ajaxError("Unauthorized access", 403);
}

// Get POST data
$role_code = $_POST['role_code'] ?? null;
$role_name = $_POST['role_name'] ?? null;
$department = $_POST['department'] ?? null;
$role_description = $_POST['role_description'] ?? null;
$is_active = $_POST['is_active'] ?? 'active';

// Validate input
if (!$role_code || !$role_name) {
    logActivity('FAILED', 'users', "Invalid role data");
    ajaxError("Role code dan name harus diisi");
}

// Check if role code already exists
$check_query = "SELECT role_id FROM tb_user_roles WHERE role_code = ?";
$check_stmt = $koneksi->prepare($check_query);
$check_stmt->bind_param("i", $role_code);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    logActivity('FAILED', 'users', "Role code $role_code already exists");
    ajaxError("Role code sudah ada");
}

// Insert role
$insert_query = "INSERT INTO tb_user_roles 
                 (role_code, role_name, department, role_description, permissions, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())";

$stmt = $koneksi->prepare($insert_query);
$default_permissions = json_encode([]);
$stmt->bind_param("isssss", $role_code, $role_name, $department, $role_description, $default_permissions, $is_active);

if ($stmt->execute()) {
    $role_id = $koneksi->insert_id;
    logActivity('SUCCESS', 'users', "Created new role: $role_name (Code: $role_code)");
    ajaxSuccess("Role berhasil dibuat", ['role_id' => $role_id]);
} else {
    logActivity('FAILED', 'users', "Failed to create role: $role_name");
    ajaxError("Gagal membuat role: " . $stmt->error);
}

$stmt->close();
$check_stmt->close();
?>
