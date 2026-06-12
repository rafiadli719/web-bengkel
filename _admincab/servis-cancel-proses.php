<?php
session_start();

if(empty($_SESSION['_iduser'])){
    if(isset($_POST['no_service'])) {
        // Redirect back with error message
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=unauthorized");
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
        exit;
    }
}

include "../config/koneksi.php";

// Get user info
$id_user = $_SESSION['_iduser'];
$user_cancel = $_SESSION['username'] ?? $_SESSION['_nama'] ?? 'Unknown';

// Get data from POST - SIMPLIFIED (hanya kategori dan keterangan)
$no_service = mysqli_real_escape_string($koneksi, $_POST['no_service'] ?? '');
$kategori_alasan = mysqli_real_escape_string($koneksi, $_POST['kategori_alasan'] ?? 'lainnya');
$alasan_cancel = mysqli_real_escape_string($koneksi, $_POST['alasan_cancel'] ?? '');

// Determine if this is AJAX or form submit
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Validasi input
if(empty($no_service)) {
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Nomor service tidak valid'
        ]);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=invalid_service");
    }
    exit;
}

if(empty($kategori_alasan) || $kategori_alasan == '') {
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Kategori alasan harus dipilih'
        ]);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=missing_category");
    }
    exit;
}

if(empty($alasan_cancel) || strlen(trim($alasan_cancel)) < 10) {
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Keterangan alasan minimal 10 karakter'
        ]);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=missing_reason");
    }
    exit;
}

// Cek apakah service exists
$check_service = mysqli_query($koneksi, "SELECT * FROM tblservice WHERE no_service='$no_service'");
if(mysqli_num_rows($check_service) == 0) {
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Service tidak ditemukan'
        ]);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=service_not_found");
    }
    exit;
}

$service_data = mysqli_fetch_array($check_service);

// Cek apakah sudah di-cancel sebelumnya
if($service_data['status_servis'] == 'cancel') {
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Service ini sudah dibatalkan sebelumnya'
        ]);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=already_cancelled");
    }
    exit;
}

// Cek apakah sudah bayar/selesai
if($service_data['status_servis'] == 'bayar') {
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Service sudah dibayar, tidak bisa dibatalkan. Gunakan fitur RETUR/KOMPLAIN.'
        ]);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=already_paid");
    }
    exit;
}

// Start transaction
mysqli_begin_transaction($koneksi);

try {
    // 1. UPDATE tblservice - set status cancel
    $query_update_service = "UPDATE tblservice SET
        status_servis = 'cancel',
        tanggal_cancel = NOW(),
        alasan_cancel = '$alasan_cancel',
        user_cancel = '$user_cancel',
        updated_at = NOW()
        WHERE no_service = '$no_service'";

    if(!mysqli_query($koneksi, $query_update_service)) {
        throw new Exception('Gagal update tblservice: ' . mysqli_error($koneksi));
    }

    // 2. UPDATE tb_antrian_servis (jika ada)
    $check_antrian = mysqli_query($koneksi, "SELECT * FROM tb_antrian_servis WHERE no_service='$no_service'");
    if(mysqli_num_rows($check_antrian) > 0) {
        $query_update_antrian = "UPDATE tb_antrian_servis SET
            status_antrian = 'batal',
            jam_selesai = NOW(),
            catatan = CONCAT(IFNULL(catatan, ''), ' | Dibatalkan: $alasan_cancel'),
            alasan_batal = '$alasan_cancel',
            user_batal = '$user_cancel',
            waktu_batal = NOW(),
            updated_at = NOW()
            WHERE no_service = '$no_service'";

        if(!mysqli_query($koneksi, $query_update_antrian)) {
            throw new Exception('Gagal update tb_antrian_servis: ' . mysqli_error($koneksi));
        }
    }

    // 3. INSERT tb_log_cancel_servis - SIMPLIFIED (hanya kategori dan keterangan)
    $query_insert_log = "INSERT INTO tb_log_cancel_servis (
        no_service,
        tanggal_cancel,
        alasan_cancel,
        kategori_alasan,
        user_cancel,
        biaya_cancel,
        status_barang,
        status_pembayaran,
        nominal_dp,
        nominal_refund,
        catatan,
        created_at
    ) VALUES (
        '$no_service',
        NOW(),
        '$alasan_cancel',
        '$kategori_alasan',
        '$user_cancel',
        0,
        'belum_diambil',
        'belum_bayar',
        0,
        0,
        '',
        NOW()
    )";

    if(!mysqli_query($koneksi, $query_insert_log)) {
        throw new Exception('Gagal insert log cancel: ' . mysqli_error($koneksi));
    }

    $log_id = mysqli_insert_id($koneksi);

    // 4. UPDATE tb_progress_mekanik (jika ada)
    $check_progress = mysqli_query($koneksi, "SELECT * FROM tb_progress_mekanik WHERE no_service='$no_service'");
    if(mysqli_num_rows($check_progress) > 0) {
        $query_update_mekanik = "UPDATE tb_progress_mekanik SET
            status_kerja = 'selesai',
            jam_selesai = NOW(),
            catatan_kerja = CONCAT(IFNULL(catatan_kerja, ''), ' | Service dibatalkan: $alasan_cancel'),
            updated_at = NOW()
            WHERE no_service = '$no_service'";

        if(!mysqli_query($koneksi, $query_update_mekanik)) {
            throw new Exception('Gagal update progress mekanik: ' . mysqli_error($koneksi));
        }
    }

    // 5. INSERT tb_log_antrian (jika tabel ada)
    $check_log_table = mysqli_query($koneksi, "SHOW TABLES LIKE 'tb_log_antrian'");
    if(mysqli_num_rows($check_log_table) > 0 && mysqli_num_rows($check_antrian) > 0) {
        $antrian_data = mysqli_fetch_array($check_antrian);
        $no_antrian = $antrian_data['no_antrian'];
        $status_sebelum = $antrian_data['status_antrian'];

        $query_insert_log_antrian = "INSERT INTO tb_log_antrian (
            no_antrian,
            no_service,
            aksi,
            status_sebelum,
            status_sesudah,
            user_id,
            keterangan,
            created_at
        ) VALUES (
            '$no_antrian',
            '$no_service',
            'cancel',
            '$status_sebelum',
            'batal',
            $id_user,
            'Service dibatalkan: $alasan_cancel',
            NOW()
        )";

        mysqli_query($koneksi, $query_insert_log_antrian); // Tidak throw error jika gagal
    }

    // Commit transaction
    mysqli_commit($koneksi);

    // =========================================================
    // AUTO-UPDATE STATISTIK PELANGGAN
    // =========================================================
    // Update cancel statistics for customer
    $no_pelanggan = $service_data['no_pelanggan'];
    
    if (!empty($no_pelanggan)) {
        if (file_exists('_include_statistik_pelanggan.php')) {
            include_once '_include_statistik_pelanggan.php';
            updateCancelStatistikPelanggan($koneksi, $no_pelanggan);
        }
    }

    // Return success
    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Service berhasil dibatalkan',
            'data' => [
                'no_service' => $no_service,
                'log_id' => $log_id,
                'kategori_alasan' => $kategori_alasan
            ]
        ]);
    } else {
        // Redirect back with success message
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?success=cancel&no_service=" . urlencode($no_service));
    }

} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($koneksi);

    if($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    } else {
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=database_error&msg=" . urlencode($e->getMessage()));
    }
}
?>
