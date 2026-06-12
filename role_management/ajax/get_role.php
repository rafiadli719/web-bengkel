<?php
/**
 * Get Single Role AJAX Handler
 * Mengambil detail single role dari database
 */

session_start();
include "../../config/koneksi.php";
include "../../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('users', 'manage_roles')) {
    ajaxError("Unauthorized access", 403);
}

// Get role_id from request
$role_id = $_GET['role_id'] ?? null;

if (!$role_id) {
    ajaxError("Role ID is required");
}

// Get role details
$query = "SELECT role_id, role_code, role_name, role_description, department, permissions, is_active
          FROM tb_user_roles
          WHERE role_id = ?";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $role_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    ajaxError("Role not found");
}

$role = $result->fetch_assoc();
$role['permissions'] = json_decode($role['permissions'], true);

header('Content-Type: application/json');
echo json_encode($role);

$stmt->close();
?>
