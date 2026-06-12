<?php
/**
 * Get Roles AJAX Handler
 * Mengambil daftar semua role dari database
 */

session_start();
include "../../config/koneksi.php";
include "../../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('users', 'manage_roles')) {
    ajaxError("Unauthorized access", 403);
}

// Get all roles
$query = "SELECT role_id, role_code, role_name, role_description, department, is_active
          FROM tb_user_roles
          ORDER BY role_code ASC";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    ajaxError("Database error: " . mysqli_error($koneksi));
}

$roles = [];
while ($row = mysqli_fetch_assoc($result)) {
    $roles[] = $row;
}

header('Content-Type: application/json');
echo json_encode($roles);
?>
