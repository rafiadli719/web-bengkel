<?php
/**
 * Get Activity Log AJAX Handler
 * Mengambil activity log untuk audit trail
 */

session_start();
include "../../config/koneksi.php";
include "../../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('users', 'manage_roles')) {
    ajaxError("Unauthorized access", 403);
}

// Get filter parameters
$module = $_GET['module'] ?? 'users';
$limit = $_GET['limit'] ?? 50;
$offset = $_GET['offset'] ?? 0;

// Get activity logs
$query = "SELECT log_id, user_id, username, action, module, description, 
                 ip_address, status, created_at
          FROM tb_user_activity_log
          WHERE module = ?
          ORDER BY created_at DESC
          LIMIT ? OFFSET ?";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("sii", $module, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

header('Content-Type: application/json');
echo json_encode($logs);

$stmt->close();
?>
