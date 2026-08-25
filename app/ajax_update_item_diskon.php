<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}

include "../config/koneksi.php";

// Handle individual item update via AJAX
if(isset($_POST['ajax_update_item'])) {
    $noitem = mysqli_real_escape_string($koneksi, $_POST['noitem']);
    $exclude_raw = mysqli_real_escape_string($koneksi, $_POST['exclude_value']); // Expecting '0', '1', or 'NULL'
    
    // Handle NULL value for reset
    if($exclude_raw === 'NULL' || $exclude_raw === 'null') {
        $exclude_sql = "NULL";
        $status_desc = "mengikuti pengaturan jenis";
    } else {
        $exclude_sql = intval($exclude_raw);
        $status_desc = ($exclude_sql == 1) ? "tidak dapat diskon" : "dapat diskon";
    }
    
    $sql = "UPDATE tblitem SET exclude_diskon_member = $exclude_sql WHERE noitem = '$noitem'";
    $success = mysqli_query($koneksi, $sql);
    
    if($success) {
        $response = [
            'success' => true, 
            'message' => 'Berhasil update status item: ' . $status_desc
        ];
    } else {
        $response = [
            'success' => false, 
            'message' => 'Database error: ' . mysqli_error($koneksi)
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// If no valid POST data
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>
