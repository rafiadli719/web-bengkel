<?php
session_start();

// Security check - use app's session convention
if (empty($_SESSION['_iduser'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$kd_cabang = $_SESSION['_cabang'];

// Include database connection (path relative to _admincab/_ajax)
require_once('../../config/koneksi.php');

// Check if request is POST
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$no_service = isset($_POST['no_service']) ? mysqli_real_escape_string($koneksi, $_POST['no_service']) : '';

if(empty($no_service)) {
    echo json_encode(['status' => 'error', 'message' => 'Nomor service tidak boleh kosong']);
    exit;
}

// Prepare mechanic data
$kepala_mekanik1 = isset($_POST['kepala_mekanik1']) ? mysqli_real_escape_string($koneksi, $_POST['kepala_mekanik1']) : '';
$persen_kepala1 = isset($_POST['persen_kepala1']) ? floatval($_POST['persen_kepala1']) : 0;
$kepala_mekanik2 = isset($_POST['kepala_mekanik2']) ? mysqli_real_escape_string($koneksi, $_POST['kepala_mekanik2']) : '';
$persen_kepala2 = isset($_POST['persen_kepala2']) ? floatval($_POST['persen_kepala2']) : 0;

$admin1 = isset($_POST['admin1']) ? mysqli_real_escape_string($koneksi, $_POST['admin1']) : '';
$persen_admin1 = isset($_POST['persen_admin1']) ? floatval($_POST['persen_admin1']) : 0;
$admin2 = isset($_POST['admin2']) ? mysqli_real_escape_string($koneksi, $_POST['admin2']) : '';
$persen_admin2 = isset($_POST['persen_admin2']) ? floatval($_POST['persen_admin2']) : 0;

$mekanik1 = isset($_POST['mekanik1']) ? mysqli_real_escape_string($koneksi, $_POST['mekanik1']) : '';
$persen_mekanik1 = isset($_POST['persen_mekanik1']) ? floatval($_POST['persen_mekanik1']) : 0;
$mekanik2 = isset($_POST['mekanik2']) ? mysqli_real_escape_string($koneksi, $_POST['mekanik2']) : '';
$persen_mekanik2 = isset($_POST['persen_mekanik2']) ? floatval($_POST['persen_mekanik2']) : 0;
$mekanik3 = isset($_POST['mekanik3']) ? mysqli_real_escape_string($koneksi, $_POST['mekanik3']) : '';
$persen_mekanik3 = isset($_POST['persen_mekanik3']) ? floatval($_POST['persen_mekanik3']) : 0;
$mekanik4 = isset($_POST['mekanik4']) ? mysqli_real_escape_string($koneksi, $_POST['mekanik4']) : '';
$persen_mekanik4 = isset($_POST['persen_mekanik4']) ? floatval($_POST['persen_mekanik4']) : 0;

$km_skr = isset($_POST['km_skr']) ? intval($_POST['km_skr']) : 0;
$km_berikut = isset($_POST['km_berikut']) ? intval($_POST['km_berikut']) : 0;

// Helper to check column existence safely
function column_exists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $res = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && mysqli_num_rows($res) > 0;
}

// Build dynamic UPDATE SET parts to avoid SQL errors for optional columns
$setParts = [];

// Kepala mekanik fields (align to schema)
$setParts[] = "kepala_mekanik1 = '$kepala_mekanik1'";
$setParts[] = "kepala_mekanik2 = '$kepala_mekanik2'";
$setParts[] = "persen_kepala_mekanik1 = $persen_kepala1";
$setParts[] = "persen_kepala_mekanik2 = $persen_kepala2";

// Admin fields (optional)
if (column_exists($koneksi, 'tblservice', 'admin1')) { $setParts[] = "admin1 = '$admin1'"; }
if (column_exists($koneksi, 'tblservice', 'persen_admin1')) { $setParts[] = "persen_admin1 = $persen_admin1"; }
if (column_exists($koneksi, 'tblservice', 'admin2')) { $setParts[] = "admin2 = '$admin2'"; }
if (column_exists($koneksi, 'tblservice', 'persen_admin2')) { $setParts[] = "persen_admin2 = $persen_admin2"; }

// Mekanik fields (present)
$setParts[] = "mekanik1 = '$mekanik1'";
$setParts[] = "persen_mekanik1 = $persen_mekanik1";
$setParts[] = "mekanik2 = '$mekanik2'";
$setParts[] = "persen_mekanik2 = $persen_mekanik2";
$setParts[] = "mekanik3 = '$mekanik3'";
$setParts[] = "persen_mekanik3 = $persen_mekanik3";
$setParts[] = "mekanik4 = '$mekanik4'";
$setParts[] = "persen_mekanik4 = $persen_mekanik4";

// KM fields
$setParts[] = "km_skr = $km_skr";
$setParts[] = "km_berikut = $km_berikut";

$setSql = implode(",\n    ", $setParts);

$kd_cabang_esc = mysqli_real_escape_string($koneksi, $kd_cabang);
$query = "UPDATE tblservice SET\n    $setSql\nWHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang_esc'";

// Execute query
$result = mysqli_query($koneksi, $query);

if($result) {
    if(mysqli_affected_rows($koneksi) > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Data mekanik dan persentase berhasil disimpan!',
            'affected_rows' => mysqli_affected_rows($koneksi)
        ]);
    } else {
        // Check if record exists
        $check = mysqli_query($koneksi, "SELECT no_service FROM tblservice WHERE no_service = '$no_service' AND kd_cabang = '$kd_cabang_esc'");
        if(mysqli_num_rows($check) > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Data mekanik dan persentase berhasil disimpan (tidak ada perubahan)!'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Nomor service tidak ditemukan'
            ]);
        }
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Gagal menyimpan data: ' . mysqli_error($koneksi)
    ]);
}

mysqli_close($koneksi);
?>
