<?php
session_start();

// Security check
if(empty($_SESSION['_iduser'])){
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

include "../config/koneksi.php";

// Get POST data
$jasa_id = isset($_POST['jasa_id']) ? mysqli_real_escape_string($koneksi, $_POST['jasa_id']) : '';
$harga = isset($_POST['harga']) ? floatval($_POST['harga']) : 0;
$no_service = isset($_POST['no_service']) ? mysqli_real_escape_string($koneksi, $_POST['no_service']) : '';

// Validate input
if(empty($jasa_id) || $harga < 0 || empty($no_service)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

// Get current jasa data
$query_jasa = mysqli_query($koneksi, "SELECT potongan FROM tblservis_jasa WHERE id='$jasa_id' AND no_service='$no_service'");
if(!$data_jasa = mysqli_fetch_array($query_jasa)) {
    echo json_encode(['success' => false, 'message' => 'Jasa item not found']);
    exit;
}

$potongan = $data_jasa['potongan'];

// Calculate new total (harga - (harga * potongan / 100))
$new_total = $harga - ($harga * $potongan / 100);

// Update database
$update_query = "UPDATE tblservis_jasa
                 SET harga = '$harga',
                     total = '$new_total'
                 WHERE id = '$jasa_id'
                 AND no_service = '$no_service'";

if(mysqli_query($koneksi, $update_query)) {
    // Log activity
    error_log("Harga jasa updated: ID=$jasa_id, Harga=$harga, Total=$new_total, User={$_SESSION['_iduser']}");

    echo json_encode([
        'success' => true,
        'message' => 'Harga berhasil diupdate',
        'new_total' => $new_total,
        'new_harga' => $harga
    ]);
} else {
    error_log("Failed to update harga jasa: " . mysqli_error($koneksi));
    echo json_encode(['success' => false, 'message' => 'Database update failed: ' . mysqli_error($koneksi)]);
}

mysqli_close($koneksi);
?>
