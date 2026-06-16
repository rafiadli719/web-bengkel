<?php
session_start();

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

include "../../config/koneksi.php";

// Set header untuk JSON response
header('Content-Type: application/json');

try {
    // Start transaction untuk konsistensi data
    if (function_exists('mysqli_begin_transaction')) {
        mysqli_begin_transaction($koneksi);
    } else {
        mysqli_autocommit($koneksi, false);
    }
    // Validate required parameters
    if(!isset($_POST['id']) || empty($_POST['id'])) {
        throw new Exception('ID work order tidak valid');
    }
    
    if(!isset($_POST['no_service']) || empty($_POST['no_service'])) {
        throw new Exception('No service tidak valid');
    }
    
    $id = (int)$_POST['id'];
    $no_service = mysqli_real_escape_string($koneksi, $_POST['no_service']);
    
    // Hapus pending items yang terkait WO ini (hanya yang masih pending)
    $sql_pending = "DELETE FROM tbservis_pending_items 
                    WHERE no_service = '$no_service' AND wo_id = $id AND status_approval = 'pending'";
    $del_pending = mysqli_query($koneksi, $sql_pending);
    if ($del_pending === false) {
        throw new Exception('Database error (pending): ' . mysqli_error($koneksi));
    }
    $pending_deleted = mysqli_affected_rows($koneksi);

    // Delete work order from SPK
    $sql = "DELETE FROM tbservis_workorder WHERE id = $id AND no_service = '$no_service'";
    $result = mysqli_query($koneksi, $sql);
    if(!$result) {
        throw new Exception('Database error: ' . mysqli_error($koneksi));
    }

    // Check if any rows were affected
    if(mysqli_affected_rows($koneksi) > 0) {
        // Commit transaksi
        if (function_exists('mysqli_commit')) {
            mysqli_commit($koneksi);
        } else {
            mysqli_autocommit($koneksi, true);
        }
        echo json_encode([
            'status' => 'success', 
            'message' => 'Work order berhasil dihapus dari SPK',
            'deleted_pending' => $pending_deleted
        ]);
    } else {
        // Rollback jika WO tidak ditemukan
        if (function_exists('mysqli_rollback')) {
            mysqli_rollback($koneksi);
        } else {
            mysqli_autocommit($koneksi, true);
        }
        throw new Exception('Work order tidak ditemukan atau sudah dihapus');
    }
    
} catch (Exception $e) {
    if (isset($koneksi)) {
        if (function_exists('mysqli_rollback')) {
            @mysqli_rollback($koneksi);
        } else {
            @mysqli_autocommit($koneksi, true);
        }
    }
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

// Close connection
mysqli_close($koneksi);
?>