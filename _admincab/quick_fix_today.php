<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

echo "<h2>Quick Fix - Buat Antrian untuk Service Hari Ini</h2>";
echo "<hr>";

$tgl_hari_ini = date('Y-m-d');

// Get service hari ini yang belum punya antrian
$query_no_antrian = mysqli_query($koneksi, "
    SELECT s.no_service, s.tanggal, s.jam, s.no_pelanggan, s.no_polisi, s.status_servis, s.status_jemput
    FROM tblservice s
    LEFT JOIN tb_antrian_servis a ON s.no_service = a.no_service
    WHERE DATE(s.tanggal) = '$tgl_hari_ini'
    AND a.id IS NULL
    AND s.no_service != ''
    ORDER BY s.created_at ASC
");

$total_service = mysqli_num_rows($query_no_antrian);

if($total_service == 0) {
    echo "<p style='color:green; font-size:16px;'><strong>✓ SEMPURNA!</strong> Semua service hari ini sudah memiliki antrian!</p>";
    echo "<hr>";
    echo "<p><a href='index.php' style='padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>→ Lihat Dashboard</a></p>";
    exit;
}

echo "<p style='font-size:16px;'>Ditemukan <strong style='color:orange;'>$total_service service</strong> hari ini yang belum punya antrian.</p>";
echo "<p>Sedang membuat antrian otomatis...</p>";
echo "<hr>";

$success_count = 0;
$error_count = 0;

while($service = mysqli_fetch_array($query_no_antrian)) {
    $no_service = $service['no_service'];
    $tanggal_service = $service['tanggal'];
    $jam_service = $service['jam'];
    $status_jemput = $service['status_jemput'];

    echo "<div style='padding:10px; margin:10px 0; background:#f8f9fa; border-left:4px solid #007bff;'>";
    echo "<strong>Processing:</strong> $no_service<br>";

    // Generate nomor antrian untuk hari ini
    $query_count = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini'");
    $count_data = mysqli_fetch_array($query_count);
    $no_antrian = $count_data['total'] + 1;

    // Get total waktu dari tblservis_jasa
    $query_waktu = mysqli_query($koneksi, "SELECT COALESCE(SUM(waktu), 0) as total_waktu FROM tblservis_jasa WHERE no_service='$no_service'");
    $waktu_data = mysqli_fetch_array($query_waktu);
    $estimasi_waktu = $waktu_data['total_waktu'];

    // Tentukan prioritas
    $prioritas = ($status_jemput == '1') ? 'urgent' : 'normal';

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
        '$tgl_hari_ini',
        '$jam_service',
        'menunggu',
        '$prioritas',
        " . ($estimasi_waktu > 0 ? "'$estimasi_waktu'" : "NULL") . ",
        NOW(),
        NOW()
    )";

    if(mysqli_query($koneksi, $query_insert)) {
        $success_count++;
        echo "<span style='color:green;'>✓ <strong>BERHASIL!</strong></span><br>";
        echo "→ Nomor Antrian: <strong>$no_antrian</strong><br>";
        echo "→ Prioritas: <strong>$prioritas</strong><br>";
        echo "→ Estimasi: <strong>" . ($estimasi_waktu > 0 ? "$estimasi_waktu menit" : "Belum ada") . "</strong>";
    } else {
        $error_count++;
        $error_msg = mysqli_error($koneksi);
        echo "<span style='color:red;'>✗ <strong>GAGAL!</strong></span><br>";
        echo "→ Error: $error_msg";
    }

    echo "</div>";
}

echo "<hr>";
echo "<div style='padding:20px; background:#e7f3ff; border-radius:8px; margin:20px 0;'>";
echo "<h3 style='margin-top:0;'>📊 Hasil Perbaikan:</h3>";
echo "<ul style='font-size:16px;'>";
echo "<li>✅ Berhasil: <strong style='color:green; font-size:20px;'>$success_count</strong></li>";
echo "<li>❌ Gagal: <strong style='color:red; font-size:20px;'>$error_count</strong></li>";
echo "</ul>";
echo "</div>";

if($success_count > 0) {
    echo "<div style='padding:20px; background:#d4edda; border-radius:8px; border-left:4px solid #28a745;'>";
    echo "<h3 style='color:#155724; margin-top:0;'>🎉 SELESAI!</h3>";
    echo "<p style='font-size:16px;'>Antrian berhasil dibuat. Sekarang service Anda akan muncul di dashboard!</p>";
    echo "</div>";
}

echo "<hr>";
echo "<div style='margin:20px 0;'>";
echo "<a href='check_antrian.php' style='padding:10px 20px; background:#6c757d; color:white; text-decoration:none; border-radius:5px; margin-right:10px;'>← Cek Ulang</a>";
echo "<a href='index.php' style='padding:10px 20px; background:#28a745; color:white; text-decoration:none; border-radius:5px;'>→ Lihat Dashboard</a>";
echo "</div>";
?>
