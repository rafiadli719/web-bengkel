<?php
include 'database_connection.php'; // Koneksi ke database

$kode_karyawan = $_GET['kode_karyawan']; // Mendapatkan kode karyawan dari request

$query = "SELECT permission_name AS name, granted FROM permissions WHERE kode_karyawan = ?"; // Query untuk mengambil hak akses karyawan
$stmt = $pdo->prepare($query);
$stmt->execute([$kode_karyawan]); // Eksekusi query dengan parameter kode karyawan
$permissions = $stmt->fetchAll(PDO::FETCH_ASSOC); // Mengambil semua data hak akses

if (!$permissions) {
    $permissions = []; // Jika tidak ada hak akses, set ke array kosong
}

echo json_encode($permissions); // Mengembalikan data dalam format JSON
?>
