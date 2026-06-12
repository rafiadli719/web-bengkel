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

// Query untuk mengecek apakah kolom password kosong berdasarkan nama karyawan dan kode karyawan
$query = "SELECT password FROM users WHERE nama_karyawan = ? AND kode_karyawan = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $nama_karyawan, $kode_karyawan);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    
    // Jika password kosong, redirect ke halaman buat password
    if (empty($row['password'])) {
        header("Location: buat_password.php");
        exit();
    } else {
        // Jika password sudah ada, redirect ke halaman ubah password
        header("Location: ubah_password.php");
        exit();
    }
} else {
    echo "Pengguna tidak ditemukan. Cek apakah nama dan kode karyawan sesuai.";
}

// Tutup statement dan koneksi
$stmt->close();
$conn->close();
?>
