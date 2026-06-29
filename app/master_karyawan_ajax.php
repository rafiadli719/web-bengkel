<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

include "../config/koneksi.php";
include "../config/permission_check.php";

if (!$koneksi) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if (empty($_SESSION['_iduser'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Data source: tabel karyawan (non-mekanik)

try {
    if ($action == 'getList') {
        if (!hasPermission('users', 'view')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        getKaryawanList();
    } elseif ($action == 'delete') {
        if (!hasPermission('users', 'delete')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        deleteKaryawan();
    } elseif ($action == 'getDetail') {
        if (!hasPermission('users', 'view')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
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
        $jabatan = isset($_POST['jabatan']) ? trim($_POST['jabatan']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        $query = "SELECT id, kode_karyawan, nama_lengkap, kode_posisi, kode_jabatan,
                         kode_cabang, email, telp, is_active
                  FROM karyawan
                  WHERE 1=1";

        if (!empty($search)) {
            $search = mysqli_real_escape_string($koneksi, $search);
            $query .= " AND (kode_karyawan LIKE '%$search%' OR nama_lengkap LIKE '%$search%')";
        }
        if (!empty($posisi)) {
            $posisi = mysqli_real_escape_string($koneksi, $posisi);
            $query .= " AND kode_posisi = '$posisi'";
        }
        if (!empty($jabatan)) {
            $jabatan = mysqli_real_escape_string($koneksi, $jabatan);
            $query .= " AND kode_jabatan = '$jabatan'";
        }
        if ($status === 'aktif') {
            $query .= " AND is_active = 'aktif'";
        } elseif ($status === 'nonaktif') {
            $query .= " AND is_active = 'nonaktif'";
        }

        $query .= " ORDER BY kode_karyawan ASC";

        $result = mysqli_query($koneksi, $query);

        if (!$result) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($koneksi)]);
            return;
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        logActivity('SUCCESS', 'users', 'Load karyawan list');
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

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            return;
        }

        $query = "SELECT kode_karyawan FROM karyawan WHERE id = $id";
        $result = mysqli_query($koneksi, $query);

        if (!$result || mysqli_num_rows($result) == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan']);
            return;
        }

        $row = mysqli_fetch_assoc($result);

        // Soft-delete
        $ok = mysqli_query($koneksi, "UPDATE karyawan SET is_active='nonaktif' WHERE id = $id");

        if ($ok) {
            logActivity('SUCCESS', 'users', "Delete karyawan ID $id");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Karyawan berhasil dinonaktifkan']);
        } else {
            logActivity('FAILED', 'users', "Delete karyawan gagal: " . mysqli_error($koneksi));
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

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            return;
        }

        $query = "SELECT id, kode_karyawan, nama_lengkap, nama_panggilan,
                         kode_posisi, kode_jabatan, kode_cabang, email, telp, is_active
                  FROM karyawan WHERE id = $id";
        $result = mysqli_query($koneksi, $query);

        if (!$result) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($koneksi)]);
            return;
        }

        if (mysqli_num_rows($result) == 0) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Karyawan tidak ditemukan']);
            return;
        }

        $row = mysqli_fetch_assoc($result);
        logActivity('SUCCESS', 'users', "Get detail karyawan ID $id");
        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $row]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}
?>
