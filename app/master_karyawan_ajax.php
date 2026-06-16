<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

include "../config/koneksi.php";
include "../config/permission_check.php";

// Check database connection
if (!$koneksi) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Check if user is logged in
if(empty($_SESSION['_iduser'])){
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Data source unified to tbuser

try {
    if($action == 'getList') {
        if (!hasPermission('users','view')) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Access denied']);
            return;
        }
        getKaryawanList();
    } elseif($action == 'delete') {
        if (!hasPermission('users','delete')) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Access denied']);
            return;
        }
        deleteKaryawan();
    } elseif($action == 'getDetail') {
        if (!hasPermission('users','view')) {
            http_response_code(403);
            echo json_encode(['success'=>false,'message'=>'Access denied']);
            return;
        }
        getKaryawanDetail();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function getKaryawanList() {
    global $koneksi;
    
    try {
        $search = isset($_POST['search']) ? trim($_POST['search']) : '';
        $posisi = isset($_POST['posisi']) ? trim($_POST['posisi']) : '';
        $level = isset($_POST['level']) ? trim($_POST['level']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        $query = "SELECT 
                    id,
                    kode_karyawan,
                    COALESCE(nama_lengkap, nama_user) AS nama_lengkap,
                    kode_posisi,
                    kode_level,
                    kode_cabang,
                    email,
                    telp,
                    is_active
                FROM tbuser
                WHERE status_row = '0'";
        
        if(!empty($search)) {
            $search = mysqli_real_escape_string($koneksi, $search);
            $query .= " AND (kode_karyawan LIKE '%$search%' OR COALESCE(nama_lengkap, nama_user) LIKE '%$search%')";
        }
        
        if(!empty($posisi)) {
            $posisi = mysqli_real_escape_string($koneksi, $posisi);
            $query .= " AND kode_posisi = '$posisi'";
        }
        
        if(!empty($level)) {
            $level = mysqli_real_escape_string($koneksi, $level);
            $query .= " AND kode_level = '$level'";
        }
        
        if(!empty($status)) {
            if($status == 'aktif') {
                $query .= " AND (is_active = 'active' OR is_active IS NULL)";
            } elseif($status == 'nonaktif') {
                $query .= " AND is_active = 'inactive'";
            }
        }
        
        $query .= " ORDER BY kode_karyawan ASC";
        
        $result = mysqli_query($koneksi, $query);
        
        if(!$result) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Query error: ' . mysqli_error($koneksi),
                'query' => $query
            ]);
            return;
        }
        
        $data = [];
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        
        logActivity('SUCCESS','users','Load karyawan list');
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}

function deleteKaryawan() {
    global $koneksi;
    
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            return;
        }
        
        // Get karyawan data first from tbuser
        $query = "SELECT kode_karyawan FROM tbuser WHERE id = $id";
        $result = mysqli_query($koneksi, $query);
        
        if(!$result || mysqli_num_rows($result) == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan']);
            return;
        }
        
        $row = mysqli_fetch_assoc($result);
        $kode_karyawan = mysqli_real_escape_string($koneksi, $row['kode_karyawan']);
        
        // Check if karyawan is used in other tables
        $checkQuery = "SELECT COUNT(*) as cnt FROM tblservice WHERE mekanik1 = '$kode_karyawan' OR mekanik2 = '$kode_karyawan' OR mekanik3 = '$kode_karyawan' OR mekanik4 = '$kode_karyawan' OR mekanik5 = '$kode_karyawan'";
        $checkResult = mysqli_query($koneksi, $checkQuery);
        
        if(!$checkResult) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($koneksi)]);
            return;
        }
        
        $checkRow = mysqli_fetch_assoc($checkResult);
        
        if($checkRow['cnt'] > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Karyawan tidak dapat dihapus karena sudah digunakan di transaksi service']);
            return;
        }
        
        // Soft-delete on tbuser
        $deleteQuery = "UPDATE tbuser SET status_row='1', is_active='inactive' WHERE id = $id";
        $ok = mysqli_query($koneksi, $deleteQuery);
        
        if($ok) {
            logActivity('SUCCESS','users',"Delete karyawan ID $id");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Karyawan berhasil dihapus']);
        } else {
            logActivity('FAILED','users',"Delete karyawan gagal: " . mysqli_error($koneksi));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Delete error: ' . mysqli_error($koneksi)]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}

function getKaryawanDetail() {
    global $koneksi;
    
    try {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            return;
        }
        
        $query = "SELECT id, kode_karyawan, COALESCE(nama_lengkap,nama_user) AS nama_lengkap, kode_posisi, kode_level, kode_cabang, email, telp, is_active FROM tbuser WHERE id = $id";
        $result = mysqli_query($koneksi, $query);
        
        if(!$result) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($koneksi)]);
            return;
        }
        
        if(mysqli_num_rows($result) == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan']);
            return;
        }
        
        $row = mysqli_fetch_assoc($result);
        logActivity('SUCCESS','users',"Get detail karyawan ID $id");
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $row]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}
?>
