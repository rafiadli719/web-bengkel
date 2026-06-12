<?php
/**
 * Get User Permissions AJAX Handler
 * Mengambil permission user dari role-nya
 */

session_start();
include "../../config/koneksi.php";
include "../../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('users', 'view')) {
    ajaxError("Unauthorized access", 403);
}

// Get user_id from request
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    ajaxError("User ID is required");
}

// Get user's role and permissions
$query = "SELECT u.id, u.nama_user, u.user_akses, r.permissions
          FROM tbuser u
          LEFT JOIN tb_user_roles r ON u.user_akses = r.role_code
          WHERE u.id = ? AND u.status_row='0' AND u.is_active='active'";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    ajaxError("User not found");
}

$user = $result->fetch_assoc();
$permissions = json_decode($user['permissions'], true) ?? [];

header('Content-Type: application/json');
echo json_encode($permissions);

$stmt->close();
?>
