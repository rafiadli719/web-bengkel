<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

$no_service = $_GET['no_service'] ?? '';

if(empty($no_service)) {
    echo "<script>alert('No service tidak valid!'); window.location='check_antrian.php';</script>";
    exit;
}

// Get service data
$query_service = mysqli_query($koneksi, "SELECT * FROM tblservice WHERE no_service='$no_service'");
if(mysqli_num_rows($query_service) == 0) {
    echo "<script>alert('Service tidak ditemukan!'); window.location='check_antrian.php';</script>";
    exit;
}

$service_data = mysqli_fetch_array($query_service);
$tanggal_service = $service_data['tanggal'];
$jam_service = $service_data['jam'];

// Check if antrian already exists
$check_antrian = mysqli_query($koneksi, "SELECT * FROM tb_antrian_servis WHERE no_service='$no_service'");
if(mysqli_num_rows($check_antrian) > 0) {
    echo "<script>alert('Antrian sudah ada untuk service ini!'); window.location='check_antrian.php';</script>";
    exit;
}

// Generate nomor antrian untuk tanggal service
$query_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal_service'");
$count_data = mysqli_fetch_array($query_count);
$no_antrian = $count_data['total'] + 1;

// Get total waktu dari tblservis_jasa
$query_waktu = mysqli_query($koneksi, "SELECT COALESCE(SUM(waktu), 0) as total_waktu FROM tblservis_jasa WHERE no_service='$no_service'");
$waktu_data = mysqli_fetch_array($query_waktu);
$estimasi_waktu = $waktu_data['total_waktu'];

// Insert antrian
$query_insert = "INSERT INTO tb_antrian_servis (
    no_antrian,
    no_service,
    tanggal,
    jam_ambil,
    status_antrian,
    prioritas,
    estimasi_waktu,
    created_at,
    updated_at
) VALUES (
    '$no_antrian',
    '$no_service',
    '$tanggal_service',
    '$jam_service',
    'menunggu',
    'normal',
    " . ($estimasi_waktu > 0 ? "'$estimasi_waktu'" : "NULL") . ",
    NOW(),
    NOW()
)";

if(mysqli_query($koneksi, $query_insert)) {
    echo "<script>
        alert('Antrian berhasil dibuat!\\nNo Antrian: $no_antrian\\nNo Service: $no_service');
        window.location='check_antrian.php';
    </script>";
} else {
    echo "<script>
        alert('Error: " . mysqli_error($koneksi) . "');
        window.location='check_antrian.php';
    </script>";
}
?>
