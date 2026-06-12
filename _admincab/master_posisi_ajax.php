<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

include "../config/koneksi.php";

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

try {
    if($action == 'getList') {
        getPosisiList();
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function getPosisiList() {
    global $koneksi;
    
    try {
        $search = isset($_POST['search']) ? trim($_POST['search']) : '';
        
        $query = "SELECT 
                    kode_posisi,
                    nama_posisi,
                    deskripsi
                FROM tb_master_posisi
                WHERE 1=1";
        
        if(!empty($search)) {
            $search = mysqli_real_escape_string($koneksi, $search);
            $query .= " AND (kode_posisi LIKE '%$search%' OR nama_posisi LIKE '%$search%')";
        }
        
        $query .= " ORDER BY kode_posisi ASC";
        
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
        
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $data, 'count' => count($data)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}
?>
