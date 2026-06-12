<?php
session_start();
include 'config.php'; // Sambungkan ke database

// Pastikan pengguna sudah login
if (!isset($_SESSION['nama_karyawan']) || !isset($_SESSION['kode_karyawan'])) {
    header("Location: ../login.php");
    exit();
}

// Ambil nama karyawan dan kode karyawan dari session
$nama_karyawan = $_SESSION['nama_karyawan'];
$kode_karyawan = $_SESSION['kode_karyawan'];

// Proses untuk mengubah password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Periksa apakah password baru dan konfirmasi cocok
    if ($new_password !== $confirm_password) {
        echo "Kata sandi baru dan konfirmasi tidak cocok.";
        exit();
    }

    // Update password di database tanpa hashing
    $update_query = "UPDATE users SET password = ? WHERE nama_karyawan = ? AND kode_karyawan = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sss", $new_password, $nama_karyawan, $kode_karyawan);
    
    if ($stmt->execute()) {
        echo "Password berhasil diubah!";
        header("Location:../dashboard/dashboard_user.php");
        exit();
    } else {
        echo "Terjadi kesalahan. Coba lagi.";
    }

    $stmt->close();
}

$conn->close();
?>
