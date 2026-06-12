<?php
/**
 * Update Role AJAX Handler
 * Mengupdate role yang sudah ada
 */

session_start();
include "../../config/koneksi.php";
include "../../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('users', 'manage_roles')) {
    ajaxError("Unauthorized access", 403);
}

// Get POST data
$role_id = $_POST['role_id'] ?? null;
$role_code = $_POST['role_code'] ?? null;
$role_name = $_POST['role_name'] ?? null;
$department = $_POST['department'] ?? null;
$role_description = $_POST['role_description'] ?? null;
$is_active = $_POST['is_active'] ?? 'active';

// Validate input
if (!$role_id || !$role_code || !$role_name) {
    logActivity('FAILED', 'users', "Invalid role data");
    ajaxError("Role ID, code, dan name harus diisi");
}

// Update role
$update_query = "UPDATE tb_user_roles 
                 SET role_code = ?, role_name = ?, department = ?, 
                     role_description = ?, is_active = ?, updated_at = NOW()
                 WHERE role_id = ?";

$stmt = $koneksi->prepare($update_query);
$stmt->bind_param("issssi", $role_code, $role_name, $department, $role_description, $is_active, $role_id);

if ($stmt->execute()) {
    logActivity('SUCCESS', 'users', "Updated role ID $role_id: $role_name");
    ajaxSuccess("Role berhasil diupdate");
} else {
    logActivity('FAILED', 'users', "Failed to update role ID $role_id");
    ajaxError("Gagal mengupdate role: " . $stmt->error);
}

$stmt->close();
?>
