<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Koneksi ke database
$host = "localhost";
$username = "fitmotor_LOGIN";
$password = "Sayalupa12";
$database = "fitmotor_maintance-beta";

$con = new mysqli($host, $username, $password, $database);
if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}

// Validasi parameter karyawan jika dikirim
$karyawan = isset($_POST['karyawan']) ? mysqli_real_escape_string($con, $_POST['karyawan']) : '';

// Jika ada parameter karyawan, validasi dulu
if (!empty($karyawan)) {
    $sql_check = "SELECT kode_karyawan FROM users WHERE kode_karyawan = ?";
    $stmt_check = $con->prepare($sql_check);
    
    if ($stmt_check) {
        $stmt_check->bind_param("s", $karyawan);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows === 0) {
            echo '<option value="">Error: Karyawan tidak valid</option>';
            $stmt_check->close();
            $con->close();
            exit();
        }
        $stmt_check->close();
    }
}

// Ambil data cabang dari tabel cabang
$sql = "SELECT kode_cabang, nama_cabang FROM cabang ORDER BY nama_cabang";
$result = $con->query($sql);

// Menampilkan opsi cabang untuk dropdown
if ($result && $result->num_rows > 0) {
    echo '<option value="">Pilih Cabang</option>';
    while ($row = $result->fetch_assoc()) {
        echo '<option value="' . htmlspecialchars($row['kode_cabang'] . '|' . $row['nama_cabang']) . '">' 
             . htmlspecialchars($row['kode_cabang'] . ' - ' . $row['nama_cabang']) . '</option>';
    }
} else {
    echo '<option value="">Cabang tidak ditemukan</option>';
}

$con->close();
?>