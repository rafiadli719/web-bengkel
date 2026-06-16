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

$action = isset($_POST['action']) ? $_POST['action'] : 'add';

// Helper: check table exists
function tableExists($table) {
    global $koneksi;
    $res = mysqli_query($koneksi, "SHOW TABLES LIKE '" . mysqli_real_escape_string($koneksi, $table) . "'");
    return ($res && mysqli_num_rows($res) > 0);
}

// RBAC gate
if ($action === 'add' || !isset($_POST['id'])) {
    if (!hasPermission('users','add') && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Access denied']);
        exit;
    }
} else {
    if (!hasPermission('users','edit') && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Access denied']);
        exit;
    }
}

if($action == 'add' || !isset($_POST['id'])) {
    saveKaryawanBaru();
} else {
    updateKaryawan();
}

function saveKaryawanBaru() {
    global $koneksi;
    
    try {
        // Validate required fields
        $required_fields = ['nik', 'nama_lengkap', 'kode_posisi', 'kode_cabang', 'tanggal_masuk'];
        foreach($required_fields as $field) {
            if(empty($_POST[$field])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Field ' . $field . ' harus diisi']);
                return;
            }
        }
        
        // Prepare data
        $nik = mysqli_real_escape_string($koneksi, $_POST['nik']);
        $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
        $nama_panggilan = mysqli_real_escape_string($koneksi, $_POST['nama_panggilan'] ?? '');
        $kode_posisi = mysqli_real_escape_string($koneksi, $_POST['kode_posisi']);
        $kode_level = mysqli_real_escape_string($koneksi, $_POST['kode_level'] ?? '');
        $kode_cabang = mysqli_real_escape_string($koneksi, $_POST['kode_cabang']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $telp = mysqli_real_escape_string($koneksi, $_POST['telp'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $tanggal_masuk = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk']);
        $tanggal_keluar = !empty($_POST['tanggal_keluar']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['tanggal_keluar']) . "'" : 'NULL';
        $spesialisasi = mysqli_real_escape_string($koneksi, $_POST['spesialisasi'] ?? '');
        $sertifikat = mysqli_real_escape_string($koneksi, $_POST['sertifikat'] ?? '');

        // Generate kode_karyawan using format yyyymm0001 (no hyphens) based on tanggal_masuk
        $periodKey = '';
        $prefix = '';
        $ts = strtotime($tanggal_masuk);
        if ($ts !== false) {
            // periodKey: for masterkeys (YYYY-MM), prefix: for kode (YYYYMM)
            $periodKey = date('Y-m', $ts);
            $prefix = date('Ym', $ts);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'tanggal_masuk tidak valid']);
            return;
        }

        $seq = 0;
        $kode_karyawan = '';
        if (tableExists('masterkeys')) {
            // Use atomic upsert with LAST_INSERT_ID trick
            mysqli_begin_transaction($koneksi);
            $keyName = 'EMP_CODE';
            $sql_inc = "INSERT INTO masterkeys (key_name, period, curr_value) VALUES ('$keyName', '$periodKey', 1)
                        ON DUPLICATE KEY UPDATE curr_value = LAST_INSERT_ID(curr_value + 1)";
            $ok_inc = mysqli_query($koneksi, $sql_inc);
            if ($ok_inc) {
                $res_seq = mysqli_query($koneksi, "SELECT LAST_INSERT_ID() AS seq");
                if ($res_seq) {
                    $row_seq = mysqli_fetch_assoc($res_seq);
                    $seq = isset($row_seq['seq']) ? (int)$row_seq['seq'] : 1;
                } else {
                    $seq = 1;
                }
                mysqli_commit($koneksi);
            } else {
                mysqli_rollback($koneksi);
                $seq = 1;
            }
        } else {
            // Fallback: derive from existing kode in tbuser for the same month
            // Support both new (YYYYMMNNNN) and legacy (YYYY-MM-NNNN) patterns
            $seqNew = 0; $seqLegacy = 0;
            $qNew = mysqli_query($koneksi, "SELECT MAX(CAST(RIGHT(kode_karyawan, 4) AS UNSIGNED)) AS seq FROM tbuser WHERE status_row='0' AND kode_karyawan LIKE '".$prefix."%'");
            if ($qNew) { $rNew = mysqli_fetch_assoc($qNew); if (!empty($rNew['seq'])) { $seqNew = (int)$rNew['seq']; } }
            $qLegacy = mysqli_query($koneksi, "SELECT MAX(CAST(SUBSTRING_INDEX(kode_karyawan, '-', -1) AS UNSIGNED)) AS seq FROM tbuser WHERE status_row='0' AND kode_karyawan LIKE '".$periodKey."-%'");
            if ($qLegacy) { $rLegacy = mysqli_fetch_assoc($qLegacy); if (!empty($rLegacy['seq'])) { $seqLegacy = (int)$rLegacy['seq']; } }
            $seq = max($seqNew, $seqLegacy) + 1;
        }
        if ($seq < 1) { $seq = 1; }
        $kode_karyawan = sprintf('%s%04d', $prefix, $seq);
        
        // Check if kode_karyawan already exists (unified to tbuser)
        $check_query = "SELECT COUNT(*) as cnt FROM tbuser WHERE kode_karyawan = '$kode_karyawan' AND status_row='0'";
        $check_result = mysqli_query($koneksi, $check_query);
        
        if(!$check_result) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Query error: ' . mysqli_error($koneksi)]);
            return;
        }
        
        $check_row = mysqli_fetch_assoc($check_result);
        
        if($check_row['cnt'] > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kode karyawan sudah terdaftar']);
            return;
        }
        
        // Insert into tbuser only (unified)
        $is_active = ($tanggal_keluar === 'NULL') ? "'active'" : "'inactive'";
        $query = "INSERT INTO tbuser (
                    kode_karyawan,
                    nik,
                    nama_lengkap,
                    nama_panggilan,
                    kode_posisi,
                    kode_level,
                    kode_cabang,
                    email,
                    telp,
                    alamat,
                    tanggal_masuk,
                    tanggal_keluar,
                    spesialisasi,
                    sertifikat,
                    is_active,
                    status_row,
                    created_at,
                    updated_at
                ) VALUES (
                    '$kode_karyawan',
                    '$nik',
                    '$nama_lengkap',
                    '$nama_panggilan',
                    '$kode_posisi',
                    '$kode_level',
                    '$kode_cabang',
                    '$email',
                    '$telp',
                    '$alamat',
                    '$tanggal_masuk',
                    $tanggal_keluar,
                    '$spesialisasi',
                    '$sertifikat',
                    $is_active,
                    '0',
                    NOW(),
                    NOW()
                )";
        
        if(mysqli_query($koneksi, $query)) {
            logActivity('SUCCESS','users',"Add karyawan $kode_karyawan - $nama_lengkap");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Karyawan berhasil ditambahkan']);
        } else {
            logActivity('FAILED','users',"Insert karyawan gagal: ".mysqli_error($koneksi));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Insert error: ' . mysqli_error($koneksi)]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}

function updateKaryawan() {
    global $koneksi;
    
    try {
        $id = intval($_POST['id']);
        
        if($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            return;
        }
        
        // Prepare data
        $nama_lengkap = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap'] ?? '');
        $nama_panggilan = mysqli_real_escape_string($koneksi, $_POST['nama_panggilan'] ?? '');
        $kode_posisi = mysqli_real_escape_string($koneksi, $_POST['kode_posisi'] ?? '');
        $kode_level = mysqli_real_escape_string($koneksi, $_POST['kode_level'] ?? '');
        $kode_cabang = mysqli_real_escape_string($koneksi, $_POST['kode_cabang'] ?? '');
        $email = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $telp = mysqli_real_escape_string($koneksi, $_POST['telp'] ?? '');
        $alamat = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $tanggal_masuk = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk'] ?? '');
        $tanggal_keluar = !empty($_POST['tanggal_keluar']) ? "'" . mysqli_real_escape_string($koneksi, $_POST['tanggal_keluar']) . "'" : 'NULL';
        $spesialisasi = mysqli_real_escape_string($koneksi, $_POST['spesialisasi'] ?? '');
        $sertifikat = mysqli_real_escape_string($koneksi, $_POST['sertifikat'] ?? '');
        
        // Update data in tbuser only (unified)
        $is_active = ($tanggal_keluar === 'NULL') ? "'active'" : "'inactive'";
        $query = "UPDATE tbuser SET
                    nik = '$nik',
                    nama_lengkap = '$nama_lengkap',
                    nama_panggilan = '$nama_panggilan',
                    kode_posisi = '$kode_posisi',
                    kode_level = '$kode_level',
                    kode_cabang = '$kode_cabang',
                    email = '$email',
                    telp = '$telp',
                    alamat = '$alamat',
                    tanggal_masuk = '$tanggal_masuk',
                    tanggal_keluar = $tanggal_keluar,
                    spesialisasi = '$spesialisasi',
                    sertifikat = '$sertifikat',
                    is_active = $is_active,
                    updated_at = NOW()
                WHERE id = $id";
        
        if(mysqli_query($koneksi, $query)) {
            logActivity('SUCCESS','users',"Update karyawan ID $id");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Karyawan berhasil diperbarui']);
        } else {
            logActivity('FAILED','users',"Update karyawan gagal: ".mysqli_error($koneksi));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Update error: ' . mysqli_error($koneksi)]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}
?>
