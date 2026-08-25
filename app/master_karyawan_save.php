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

$action = isset($_POST['action']) ? mysqli_real_escape_string($koneksi, $_POST['action']) : 'add';

if ($action === 'add' || !isset($_POST['id'])) {
    if (!hasPermission('users', 'add') && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
} else {
    if (!hasPermission('users', 'edit') && !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
}

if ($action == 'add' || !isset($_POST['id'])) {
    saveKaryawanBaru();
} else {
    updateKaryawan();
}

function saveKaryawanBaru() {
    global $koneksi;

    try {
        $required = ['nik', 'nama_lengkap', 'kode_posisi', 'kode_cabang', 'tanggal_masuk'];
        foreach ($required as $f) {
            if (empty($_POST[$f])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "Field $f harus diisi"]);
                return;
            }
        }

        $nik            = mysqli_real_escape_string($koneksi, $_POST['nik']);
        $nama_lengkap   = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap']);
        $nama_panggilan = mysqli_real_escape_string($koneksi, $_POST['nama_panggilan'] ?? '');
        $kode_posisi    = mysqli_real_escape_string($koneksi, $_POST['kode_posisi']);
        $kode_jabatan   = mysqli_real_escape_string($koneksi, $_POST['kode_jabatan'] ?? '');
        $kode_cabang    = mysqli_real_escape_string($koneksi, $_POST['kode_cabang']);
        $email          = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $telp           = mysqli_real_escape_string($koneksi, $_POST['telp'] ?? '');
        $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $spesialisasi   = mysqli_real_escape_string($koneksi, $_POST['spesialisasi'] ?? '');
        $sertifikat     = mysqli_real_escape_string($koneksi, $_POST['sertifikat'] ?? '');
        $tanggal_masuk  = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk']);
        $tanggal_keluar = !empty($_POST['tanggal_keluar'])
            ? "'" . mysqli_real_escape_string($koneksi, $_POST['tanggal_keluar']) . "'"
            : 'NULL';

        // Generate kode_karyawan (format YYYYMMnnnn)
        $ts = strtotime($tanggal_masuk);
        if ($ts === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'tanggal_masuk tidak valid']);
            return;
        }
        $prefix = date('Ym', $ts);

        $seqRes = mysqli_query($koneksi,
            "SELECT MAX(CAST(RIGHT(kode_karyawan, 4) AS UNSIGNED)) AS seq
             FROM tbuser_karyawan WHERE kode_karyawan LIKE '$prefix%'");
        $seq = 1;
        if ($seqRes) {
            $sr = mysqli_fetch_assoc($seqRes);
            if (!empty($sr['seq'])) { $seq = (int)$sr['seq'] + 1; }
        }
        $kode_karyawan = sprintf('%s%04d', $prefix, $seq);

        $check = mysqli_query($koneksi,
            "SELECT COUNT(*) as cnt FROM tbuser_karyawan WHERE kode_karyawan = '$kode_karyawan'");
        if ($check && (int)mysqli_fetch_assoc($check)['cnt'] > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Kode karyawan sudah terdaftar, coba lagi']);
            return;
        }

        $is_active = ($tanggal_keluar === 'NULL') ? 'aktif' : 'nonaktif';
        $kj_val    = $kode_jabatan !== '' ? "'$kode_jabatan'" : 'NULL';

        $query = "INSERT INTO tbuser_karyawan
                    (kode_karyawan, nik, nama_lengkap, nama_panggilan,
                     kode_posisi, kode_jabatan, kode_cabang,
                     email, telp, alamat, spesialisasi, sertifikat,
                     tanggal_masuk, tanggal_keluar, is_active, created_at, updated_at)
                  VALUES
                    ('$kode_karyawan','$nik','$nama_lengkap','$nama_panggilan',
                     '$kode_posisi',$kj_val,'$kode_cabang',
                     '$email','$telp','$alamat','$spesialisasi','$sertifikat',
                     '$tanggal_masuk',$tanggal_keluar,'$is_active',NOW(),NOW())";

        if (mysqli_query($koneksi, $query)) {
            logActivity('SUCCESS', 'users', "Add karyawan $kode_karyawan - $nama_lengkap");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Karyawan berhasil ditambahkan']);
        } else {
            logActivity('FAILED', 'users', "Insert karyawan gagal: " . mysqli_error($koneksi));
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
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            return;
        }

        $nama_lengkap   = mysqli_real_escape_string($koneksi, $_POST['nama_lengkap'] ?? '');
        $nama_panggilan = mysqli_real_escape_string($koneksi, $_POST['nama_panggilan'] ?? '');
        $kode_posisi    = mysqli_real_escape_string($koneksi, $_POST['kode_posisi'] ?? '');
        $kode_jabatan   = mysqli_real_escape_string($koneksi, $_POST['kode_jabatan'] ?? '');
        $kode_cabang    = mysqli_real_escape_string($koneksi, $_POST['kode_cabang'] ?? '');
        $email          = mysqli_real_escape_string($koneksi, $_POST['email'] ?? '');
        $telp           = mysqli_real_escape_string($koneksi, $_POST['telp'] ?? '');
        $alamat         = mysqli_real_escape_string($koneksi, $_POST['alamat'] ?? '');
        $spesialisasi   = mysqli_real_escape_string($koneksi, $_POST['spesialisasi'] ?? '');
        $sertifikat     = mysqli_real_escape_string($koneksi, $_POST['sertifikat'] ?? '');
        $tanggal_masuk  = mysqli_real_escape_string($koneksi, $_POST['tanggal_masuk'] ?? '');
        $tanggal_keluar = !empty($_POST['tanggal_keluar'])
            ? "'" . mysqli_real_escape_string($koneksi, $_POST['tanggal_keluar']) . "'"
            : 'NULL';

        $is_active = ($tanggal_keluar === 'NULL') ? 'aktif' : 'nonaktif';
        $kj_val    = $kode_jabatan !== '' ? "'$kode_jabatan'" : 'NULL';

        $query = "UPDATE tbuser_karyawan SET
                    nama_lengkap   = '$nama_lengkap',
                    nama_panggilan = '$nama_panggilan',
                    kode_posisi    = '$kode_posisi',
                    kode_jabatan   = $kj_val,
                    kode_cabang    = '$kode_cabang',
                    email          = '$email',
                    telp           = '$telp',
                    alamat         = '$alamat',
                    spesialisasi   = '$spesialisasi',
                    sertifikat     = '$sertifikat',
                    tanggal_masuk  = '$tanggal_masuk',
                    tanggal_keluar = $tanggal_keluar,
                    is_active      = '$is_active',
                    updated_at     = NOW()
                  WHERE id = $id";

        if (mysqli_query($koneksi, $query)) {
            logActivity('SUCCESS', 'users', "Update karyawan ID $id");
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => 'Karyawan berhasil diperbarui']);
        } else {
            logActivity('FAILED', 'users', "Update karyawan gagal: " . mysqli_error($koneksi));
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Update error: ' . mysqli_error($koneksi)]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Exception: ' . $e->getMessage()]);
    }
}
?>
