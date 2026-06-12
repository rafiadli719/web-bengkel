<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Koneksi ke database
$host = "localhost";
$username = "fitmotor_LOGIN";
$password = "Sayalupa12";
$database = "fitmotor_maintance-beta";

// Koneksi ke database
$con = new mysqli($host, $username, $password, $database);

// Memeriksa koneksi
if ($con->connect_error) {
    die("Koneksi gagal: " . $con->connect_error);
}

if (isset($_POST['cabang'])) {
    // Mengamankan input dan mengubahnya menjadi huruf kecil
    $cabang = strtolower($con->real_escape_string($_POST['cabang']));
    
    // Query untuk mengambil karyawan dari tabel users berdasarkan cabang yang dipilih
    $sql = "
        SELECT kode_karyawan, nama_karyawan 
        FROM users
        WHERE kode_karyawan IN (
            SELECT kode_karyawan FROM masterkeys WHERE nama_cabang = ? AND status_aktif = 1
        )
    ";
    
    if ($stmt = $con->prepare($sql)) {
        $stmt->bind_param("s", $cabang);
        $stmt->execute();
        $result = $stmt->get_result();

        // Menghasilkan opsi untuk dropdown
        if ($result->num_rows > 0) {
            echo '<option value="">Pilih Karyawan</option>';
            while ($row = $result->fetch_assoc()) {
                echo '<option value="' . htmlspecialchars($row['kode_karyawan']) . '">' 
                     . htmlspecialchars($row['kode_karyawan'] . ' - ' . $row['nama_karyawan']) 
                     . '</option>';
            }
        } else {
            echo '<option value="">Karyawan tidak ditemukan</option>';
        }

        // Menutup statement
        $stmt->close();
    } else {
        echo '<option value="">Query gagal: ' . $con->error . '</option>';
    }
}

// Menutup koneksi
$con->close();
?>
