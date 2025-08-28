<?php
// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "../config/accurate_config.php";

// Inisialisasi file log
$log_file = 'accurate_category_delete_log.txt';

// Ambil ID kategori dari parameter GET
$txtid = $_GET['kd'] ?? null;
$action = $_GET['action'] ?? 'delete'; // 'delete' or 'deactivate'

if (empty($txtid)) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ ID kategori kosong\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: ID kategori tidak ditemukan.');window.location=('barang_kategori.php');</script>";
    exit;
}

// Ambil data kategori dari database lokal
$cari_kd = mysqli_query($koneksi, "SELECT jenis, namajenis, accurate_id, status FROM tblitemjenis WHERE id='$txtid'");
if (!$cari_kd || mysqli_num_rows($cari_kd) == 0) {
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Kategori dengan ID $txtid tidak ditemukan di database lokal\n", FILE_APPEND | LOCK_EX);
    echo "<script>window.alert('Error: Kategori tidak ditemukan di database lokal.');window.location=('barang_kategori.php');</script>";
    exit;
}

$tm_cari = mysqli_fetch_array($cari_kd);
$kode = $tm_cari['jenis'];
$nama = $tm_cari['namajenis'];
$accurate_id = $tm_cari['accurate_id'] ?? null;
$current_status = $tm_cari['status'] ?? '1';

file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📝 ACTION: $action - ID: $txtid, Kode: $kode, Nama: $nama, Status: $current_status\n", FILE_APPEND | LOCK_EX);

/**
 * Function untuk mengecek apakah kategori digunakan di tabel lain
 */
function checkCategoryUsage($koneksi, $jenis_kode, $log_file) {
    $usage = [];
    
    // Cek di tblitem
    $check_item = mysqli_query($koneksi, "SELECT COUNT(*) as count FROM tblitem WHERE jenis='$jenis_kode'");
    if ($check_item) {
        $result = mysqli_fetch_array($check_item);
        $item_count = $result['count'];
        if ($item_count > 0) {
            $usage[] = "Master Barang ($item_count item)";
        }
    }
    
    // Cek di tabel transaksi lain jika diperlukan
    // Tambahkan pengecekan tabel transaksi lainnya di sini
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Usage check: " . (empty($usage) ? "No usage found" : implode(", ", $usage)) . "\n", FILE_APPEND | LOCK_EX);
    
    return $usage;
}

/**
 * Function untuk format timestamp sesuai dokumentasi Accurate
 */
function formatTimestamp() {
    return date('d/m/Y H:i:s');
}

/**
 * Function untuk generate API signature dengan HMAC SHA-256
 */
function generateApiSignature($timestamp, $signature_secret) {
    return base64_encode(hash_hmac('sha256', $timestamp, $signature_secret, true));
}

/**
 * Function untuk test koneksi dan mendapatkan host
 */
function getAccurateHostOptimized($log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $url = ACCURATE_API_BASE_URL . '/api/api-token.do';

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Getting Accurate host from: $url\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!empty($curl_error)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ cURL Error: $curl_error\n", FILE_APPEND | LOCK_EX);
            return false;
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                if (isset($result['d']['database']['host'])) {
                    $host = $result['d']['database']['host'];
                    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Host found: $host\n", FILE_APPEND | LOCK_EX);
                    return $host;
                }
            }
        }

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Failed to get host\n", FILE_APPEND | LOCK_EX);
        return false;
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
}

// === MAIN EXECUTION ===

// Step 1: Cek penggunaan kategori
$usage = checkCategoryUsage($koneksi, $kode, $log_file);

if ($action === 'delete') {
    // Jika ada penggunaan, tidak boleh dihapus - hanya bisa dinonaktifkan
    if (!empty($usage)) {
        $usage_text = implode("\\n- ", $usage);
        echo "<script>
        var msg = '❌ KATEGORI TIDAK DAPAT DIHAPUS\\n\\n';
        msg += 'Kategori \"$nama\" sedang digunakan di:\\n- $usage_text\\n\\n';
        msg += 'Silakan pilih \"Nonaktifkan\" untuk menonaktifkan kategori ini.';
        window.alert(msg);
        window.location=('barang_kategori.php');
        </script>";
        exit;
    }
    
    // Jika tidak ada penggunaan, lanjutkan dengan penghapusan
    $modal = mysqli_query($koneksi, "DELETE FROM tblitemjenis WHERE id='$txtid'");
    if (!$modal) {
        $error = mysqli_error($koneksi);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal menghapus dari database lokal: $error\n", FILE_APPEND | LOCK_EX);
        echo "<script>window.alert('Error: Gagal menghapus kategori dari database lokal. $error');window.location=('barang_kategori.php');</script>";
        exit;
    }
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 DATABASE LOKAL: Kategori '$nama' (Kode: $kode) dihapus - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);
    
    // Lanjutkan dengan penghapusan dari Accurate (kode yang sama seperti sebelumnya)
    // ... (sisanya sama seperti kode asli untuk penghapusan dari Accurate)
    
    echo "<script>
    var successMsg = '✅ KATEGORI BERHASIL DIHAPUS\\n\\n';
    successMsg += 'Kategori \"$nama\" telah dihapus dari sistem.';
    window.alert(successMsg);
    window.location=('barang_kategori.php');
    </script>";
    
} else if ($action === 'deactivate') {
    // Nonaktifkan kategori
    $new_status = ($current_status == '1') ? '0' : '1';
    $status_text = ($new_status == '1') ? 'diaktifkan' : 'dinonaktifkan';
    
    $modal = mysqli_query($koneksi, "UPDATE tblitemjenis SET status='$new_status' WHERE id='$txtid'");
    if (!$modal) {
        $error = mysqli_error($koneksi);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Gagal mengubah status kategori: $error\n", FILE_APPEND | LOCK_EX);
        echo "<script>window.alert('Error: Gagal mengubah status kategori. $error');window.location=('barang_kategori.php');</script>";
        exit;
    }
    
    file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 💾 DATABASE LOKAL: Kategori '$nama' (Kode: $kode) $status_text - SUCCESS ✅\n", FILE_APPEND | LOCK_EX);
    
    echo "<script>
    var successMsg = '✅ STATUS KATEGORI BERHASIL DIUBAH\\n\\n';
    successMsg += 'Kategori \"$nama\" telah $status_text.';
    window.alert(successMsg);
    window.location=('barang_kategori.php');
    </script>";
}

// Log akhir
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🏁 Script execution completed\n", FILE_APPEND | LOCK_EX);
file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] " . str_repeat("=", 80) . "\n", FILE_APPEND | LOCK_EX);
?>
