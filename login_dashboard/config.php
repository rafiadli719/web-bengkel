<?php
// Konfigurasi koneksi database menggunakan PDO
$host = 'localhost';
$dbname = 'fitmotor_maintance-beta';
$username = 'fitmotor_LOGIN';
$password = 'Sayalupa12';

try {
    // Membuat koneksi PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    // Set PDO error mode ke Exception untuk menangani error lebih baik
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error
    die("Koneksi gagal: " . $e->getMessage());
}
?>