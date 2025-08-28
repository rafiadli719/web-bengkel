<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

// Database connection
include "../config/koneksi.php";

// Get service number
$no_service = $_GET['snoserv'] ?? '';

if (empty($no_service)) {
    echo "<script>
        alert('Error: No service number provided!');
        window.location='servis-reguler.php';
    </script>";
    exit;
}

// Validate service exists
$stmt = mysqli_prepare($koneksi, "SELECT COUNT(*) as count FROM tblservice WHERE no_service = ?");
mysqli_stmt_bind_param($stmt, "s", $no_service);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);
$check_data = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($stmt);

if ($check_data['count'] == 0) {
    echo "<script>
        alert('Error: Service data not found!');
        window.location='servis-reguler.php';
    </script>";
    exit;
}

try {
    // Start transaction
    mysqli_autocommit($koneksi, false);
    
    // Delete related records first
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tbservis_keluhan WHERE no_service = ?");
    mysqli_stmt_bind_param($stmt, "s", $no_service);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tblservis_barang WHERE no_service = ?");
    mysqli_stmt_bind_param($stmt, "s", $no_service);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tblservis_jasa WHERE no_service = ?");
    mysqli_stmt_bind_param($stmt, "s", $no_service);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Delete main service record
    $stmt = mysqli_prepare($koneksi, "DELETE FROM tblservice WHERE no_service = ?");
    mysqli_stmt_bind_param($stmt, "s", $no_service);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Commit transaction
    mysqli_commit($koneksi);
    
    echo "<script>
        alert('Data servis " . htmlspecialchars($no_service) . " berhasil dihapus!');
        window.location='servis-reguler.php';
    </script>";
    
} catch (Exception $e) {
    // Rollback transaction
    mysqli_rollback($koneksi);
    
    echo "<script>
        alert('Error: Gagal menghapus data servis!\\n" . addslashes($e->getMessage()) . "');
        window.location='servis-reguler.php';
    </script>";
}

// Restore autocommit
mysqli_autocommit($koneksi, true);
?>