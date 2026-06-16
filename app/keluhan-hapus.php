<?php
session_start();

// Handle AJAX delete request
if(isset($_POST['id'])) {
    header('Content-Type: application/json');
    
    if(empty($_SESSION['_iduser'])){
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }
    
    include "../config/koneksi.php";
    
    $keluhan_id = $_POST['id'];
    
    if(empty($keluhan_id)) {
        echo json_encode(['success' => false, 'message' => 'ID keluhan tidak valid']);
        exit;
    }
    
    try {
        // Delete keluhan
        $result = mysqli_query($koneksi,"DELETE FROM tbservis_keluhan_status WHERE id='$keluhan_id'");
        
        if($result) {
            echo json_encode(['success' => true, 'message' => 'Keluhan berhasil dihapus']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Gagal menghapus keluhan: ' . mysqli_error($koneksi)]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle regular GET request (fallback)
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    include "../config/koneksi.php";
    
    // Get parameters
    $keluhan_id = $_GET['kid'] ?? $_GET['sid'] ?? $_GET['id']; // Support multiple parameter names
    $no_service = $_GET['snoserv'];
    
    if(!empty($keluhan_id)) {
        // Delete keluhan
        mysqli_query($koneksi,"DELETE FROM tbservis_keluhan_status WHERE id='$keluhan_id'");
    }
    
    // Redirect back to appropriate service input page
    // Check if this is from jemput service or regular service
    $cari_service = mysqli_query($koneksi,"SELECT tipe_service FROM tblservice WHERE no_service='$no_service'");
    $service_data = mysqli_fetch_array($cari_service);
    
    if($service_data && $service_data['tipe_service'] == 'jemput') {
        echo"<script>window.location=('servis-input-reguler-jemput.php?snoserv=$no_service');</script>";
    } else {
        // Check if it's the RST version by looking at the referer
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if(strpos($referer, 'servis-input-reguler-rst.php') !== false) {
            echo"<script>window.location=('servis-input-reguler-rst.php?snoserv=$no_service');</script>";
        } else {
            echo"<script>window.location=('servis-input-reguler.php?snoserv=$no_service');</script>";
        }
    }
}
?>