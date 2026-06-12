<?php
/**
 * Get Users AJAX Handler
 * Mengambil daftar semua user dari database
 */

session_start();
include "../../config/koneksi.php";
include "../../config/permission_check.php";

// Validate AJAX request
if (!validateAjaxRequest('users', 'view')) {
    ajaxError("Unauthorized access", 403);
}

// Get all users
$query = "SELECT id, kode_karyawan, nama_user, nama_lengkap, email, user_akses, is_active
          FROM tbuser
          WHERE status_row='0' AND is_active='active'
          ORDER BY nama_lengkap ASC";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    ajaxError("Database error: " . mysqli_error($koneksi));
}

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode($users);
?>
