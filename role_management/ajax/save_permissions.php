<?php
/**
 * Save Permissions AJAX Handler
 * Menyimpan permission untuk role
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
$permissions = $_POST['permissions'] ?? [];

// Validate input
if (!$role_id) {
    logActivity('FAILED', 'users', "Role ID is required");
    ajaxError("Role ID harus diisi");
}

// Convert permissions to JSON
$permissions_json = json_encode($permissions);

// Update role permissions
$update_query = "UPDATE tb_user_roles 
                 SET permissions = ?, updated_at = NOW()
                 WHERE role_id = ?";

$stmt = $koneksi->prepare($update_query);
$stmt->bind_param("si", $permissions_json, $role_id);

if ($stmt->execute()) {
    logActivity('SUCCESS', 'users', "Updated permissions for role ID $role_id");
    ajaxSuccess("Permission berhasil disimpan");
} else {
    logActivity('FAILED', 'users', "Failed to update permissions for role ID $role_id");
    ajaxError("Gagal menyimpan permission: " . $stmt->error);
}

$stmt->close();
?>
