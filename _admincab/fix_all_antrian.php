<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

echo "<h2>Perbaikan Otomatis - Buat Antrian untuk Semua Service</h2>";
echo "<hr>";

// Get semua service yang belum punya antrian (7 hari terakhir)
$tanggal_mulai = date('Y-m-d', strtotime('-7 days'));
$query_no_antrian = mysqli_query($koneksi, "
    SELECT s.no_service, s.tanggal, s.jam, s.no_pelanggan, s.no_polisi, s.status_servis
    FROM tblservice s
    LEFT JOIN tb_antrian_servis a ON s.no_service = a.no_service
    WHERE s.tanggal >= '$tanggal_mulai'
    AND a.id IS NULL
    AND s.no_service != ''
    ORDER BY s.tanggal DESC, s.jam DESC
");

$total_service = mysqli_num_rows($query_no_antrian);

if($total_service == 0) {
    echo "<p style='color:green;'>✓ Semua service sudah memiliki antrian!</p>";
    echo "<p><a href='check_antrian.php'>← Kembali ke Check Antrian</a></p>";
    exit;
}

echo "<p>Ditemukan <strong>$total_service service</strong> yang belum punya antrian.</p>";
echo "<p>Sedang memproses...</p>";
echo "<hr>";

$success_count = 0;
$error_count = 0;
$errors = [];

while($service = mysqli_fetch_array($query_no_antrian)) {
    $no_service = $service['no_service'];
    $tanggal_service = $service['tanggal'];
    $jam_service = $service['jam'];

    // Generate nomor antrian untuk tanggal service
    $query_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tanggal_service'");
    $count_data = mysqli_fetch_array($query_count);
    $no_antrian = $count_data['total'] + 1;

    // Get total waktu dari tblservis_jasa
    $query_waktu = mysqli_query($koneksi, "SELECT COALESCE(SUM(waktu), 0) as total_waktu FROM tblservis_jasa WHERE no_service='$no_service'");
    $waktu_data = mysqli_fetch_array($query_waktu);
    $estimasi_waktu = $waktu_data['total_waktu'];

    // Tentukan prioritas berdasarkan status jemput
    $query_jemput = mysqli_query($koneksi, "SELECT status_jemput FROM tblservice WHERE no_service='$no_service'");
    $jemput_data = mysqli_fetch_array($query_jemput);
    $prioritas = ($jemput_data['status_jemput'] == '1') ? 'urgent' : 'normal';

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
        '$prioritas',
        " . ($estimasi_waktu > 0 ? "'$estimasi_waktu'" : "NULL") . ",
        NOW(),
        NOW()
    )";

    if(mysqli_query($koneksi, $query_insert)) {
        $success_count++;
        echo "<p style='color:green;'>✓ Antrian #$no_antrian dibuat untuk service <strong>$no_service</strong> (Tanggal: $tanggal_service)</p>";
    } else {
        $error_count++;
        $error_msg = mysqli_error($koneksi);
        $errors[] = "Service $no_service: $error_msg";
        echo "<p style='color:red;'>✗ Gagal membuat antrian untuk service <strong>$no_service</strong>: $error_msg</p>";
    }
}

echo "<hr>";
echo "<h3>Hasil:</h3>";
echo "<ul>";
echo "<li>Berhasil: <strong style='color:green;'>$success_count</strong></li>";
echo "<li>Gagal: <strong style='color:red;'>$error_count</strong></li>";
echo "</ul>";

if($error_count > 0) {
    echo "<h4>Error Detail:</h4>";
    echo "<ul>";
    foreach($errors as $error) {
        echo "<li style='color:red;'>$error</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='check_antrian.php'>← Kembali ke Check Antrian</a> | <a href='index.php'>Dashboard</a></p>";
?>
