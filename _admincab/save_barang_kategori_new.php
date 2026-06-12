<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "../config/accurate_config.php";

// Ambil data dari form
$kategori = strtoupper(trim($_POST['txtkategori']));
$keterangan = strtoupper(trim($_POST['txtketerangan']));
$margin_sesuai_jenis = $_POST['margin_sesuai_jenis'];
$margin_kategori = isset($_POST['txtmargin']) ? floatval($_POST['txtmargin']) : null;

// Validasi input
if (empty($kategori) || empty($keterangan) || empty($margin_sesuai_jenis)) {
    $_SESSION['error'] = 'Semua field wajib harus diisi!';
    header("location:barang_kategori_add_new.php");
    exit;
}

// Validasi kategori tidak boleh ada spasi
if (strpos($kategori, ' ') !== false) {
    $_SESSION['error'] = 'Kategori Item tidak boleh mengandung spasi!';
    header("location:barang_kategori_add_new.php");
    exit;
}

// Validasi margin jika diperlukan
if ($margin_sesuai_jenis === 'TIDAK' && ($margin_kategori === null || $margin_kategori < 0)) {
    $_SESSION['error'] = 'Margin Kategori harus diisi dengan nilai yang valid!';
    header("location:barang_kategori_add_new.php");
    exit;
}

// Cek apakah kategori sudah ada
$check_query = "SELECT COUNT(*) as count FROM tblitemjenis WHERE jenis = '$kategori'";
$check_result = mysqli_query($koneksi, $check_query);
$check_data = mysqli_fetch_array($check_result);

if ($check_data['count'] > 0) {
    $_SESSION['error'] = 'Kategori Item "' . $kategori . '" sudah ada dalam database!';
    header("location:barang_kategori_add_new.php");
    exit;
}

// Set nilai untuk database
$ikut_margin_jenis = ($margin_sesuai_jenis === 'YA') ? '1' : '0';
$margin_khusus = ($margin_sesuai_jenis === 'TIDAK') ? $margin_kategori : null;

// Insert ke database
$insert_query = "INSERT INTO tblitemjenis (jenis, namajenis, keterangan, ikut_margin_jenis, margin_khusus, status, _default) 
                 VALUES ('$kategori', '$kategori', '$keterangan', '$ikut_margin_jenis', " . 
                 ($margin_khusus !== null ? $margin_khusus : 'NULL') . ", '1', '0')";

if (mysqli_query($koneksi, $insert_query)) {
    $new_id = mysqli_insert_id($koneksi);
    
    // Coba sinkronisasi ke Accurate jika terhubung
    $accurate_sync_result = '';
    if (isset($_SESSION['accurate_status']) && $_SESSION['accurate_status'] == 'connected') {
        try {
            // Function untuk sinkronisasi ke Accurate
            $sync_result = syncToAccurate($kategori, $keterangan);
            if ($sync_result['success']) {
                $accurate_sync_result = ' ✅ Data berhasil disinkronisasi ke Accurate Online.';
                
                // Update accurate_id jika berhasil
                if (isset($sync_result['accurate_id'])) {
                    $update_accurate_id = "UPDATE tblitemjenis SET accurate_id = " . $sync_result['accurate_id'] . " WHERE id = $new_id";
                    mysqli_query($koneksi, $update_accurate_id);
                }
            } else {
                $accurate_sync_result = ' ⚠️ Data tersimpan lokal, namun gagal sinkronisasi ke Accurate: ' . $sync_result['message'];
            }
        } catch (Exception $e) {
            $accurate_sync_result = ' ⚠️ Data tersimpan lokal, namun terjadi error saat sinkronisasi: ' . $e->getMessage();
        }
    } else {
        $accurate_sync_result = ' ℹ️ Data tersimpan lokal. Sinkronisasi ke Accurate tidak tersedia.';
    }
    
    $_SESSION['success'] = 'Kategori Item "' . $kategori . '" berhasil ditambahkan!' . $accurate_sync_result;
    header("location:barang_kategori_new.php");
} else {
    $_SESSION['error'] = 'Gagal menyimpan data: ' . mysqli_error($koneksi);
    header("location:barang_kategori_add_new.php");
}

/**
 * Function untuk sinkronisasi ke Accurate Online
 */
function syncToAccurate($kategori, $keterangan) {
    try {
        if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
            return [
                'success' => false,
                'message' => 'Konfigurasi API tidak lengkap'
            ];
        }

        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $url = ACCURATE_API_BASE_URL . '/api/item-category/save.do';

        // Data untuk Accurate
        $postData = [
            'name' => $kategori,
            'description' => $keterangan,
            'no' => $kategori // Menggunakan nama kategori sebagai kode
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!empty($curl_error)) {
            return [
                'success' => false,
                'message' => 'Connection error: ' . $curl_error
            ];
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                return [
                    'success' => true,
                    'message' => 'Berhasil sinkronisasi ke Accurate',
                    'accurate_id' => isset($result['r']['id']) ? $result['r']['id'] : null
                ];
            } else {
                $error_msg = isset($result['m']) ? $result['m'] : 'Unknown error';
                return [
                    'success' => false,
                    'message' => $error_msg
                ];
            }
        } else {
            return [
                'success' => false,
                'message' => "HTTP Error: $http_code"
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}
?>
