<?php
session_start();
include "../config/koneksi.php";

if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
$id_user = $_SESSION['_iduser'];
$kd_cabang = $_SESSION['_cabang'] ?? '';
$cari_kd = mysqli_query($koneksi, "SELECT user_akses FROM tbuser WHERE id='$id_user'");
$tm_cari = mysqli_fetch_array($cari_kd);
$lvl_akses = $tm_cari ? $tm_cari['user_akses'] : '';
$is_admin_check = ($lvl_akses == '1');
$where_cabang_svc = $is_admin_check ? '' : " AND kd_cabang = '$kd_cabang'";
$exists_cabang_a = $is_admin_check ? '' : " AND EXISTS (SELECT 1 FROM tblservice sx WHERE sx.no_service = a.no_service AND sx.kd_cabang = '$kd_cabang')";
$exists_cabang_s = $is_admin_check ? '' : " AND s.kd_cabang = '$kd_cabang'";

echo "<h2>Debug Sistem Antrian</h2>";
echo "<hr>";

// 1. Cek data servis hari ini
$tgl_hari_ini = date('Y-m-d');
echo "<h3>1. Data Service Hari Ini ($tgl_hari_ini)</h3>";
$query_service = mysqli_query($koneksi, "
    SELECT no_service, tanggal, jam, no_pelanggan, no_polisi, status_servis, created_at
    FROM tblservice
    WHERE (DATE(tanggal) = '$tgl_hari_ini' OR DATE(created_at) = '$tgl_hari_ini')$where_cabang_svc
    ORDER BY created_at DESC
");

if(mysqli_num_rows($query_service) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>No Service</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>No Pelanggan</th>
            <th>No Polisi</th>
            <th>Status Servis</th>
            <th>Created At</th>
          </tr>";
    while($row = mysqli_fetch_array($query_service)) {
        echo "<tr>";
        echo "<td>{$row['no_service']}</td>";
        echo "<td>{$row['tanggal']}</td>";
        echo "<td>{$row['jam']}</td>";
        echo "<td>{$row['no_pelanggan']}</td>";
        echo "<td>{$row['no_polisi']}</td>";
        echo "<td><strong>{$row['status_servis']}</strong></td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Tidak ada data service hari ini!</p>";
}

echo "<hr>";

// 2. Cek data antrian hari ini
echo "<h3>2. Data Antrian Servis Hari Ini ($tgl_hari_ini)</h3>";
$query_antrian = mysqli_query($koneksi, "
    SELECT a.*, s.status_servis
    FROM tb_antrian_servis a
    LEFT JOIN tblservice s ON a.no_service = s.no_service$exists_cabang_s
    WHERE a.tanggal = '$tgl_hari_ini'$exists_cabang_a
    ORDER BY a.created_at DESC
");

if(mysqli_num_rows($query_antrian) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>ID</th>
            <th>No Antrian</th>
            <th>No Service</th>
            <th>Tanggal</th>
            <th>Jam Ambil</th>
            <th>Status Antrian</th>
            <th>Status Servis</th>
            <th>Prioritas</th>
            <th>Estimasi (menit)</th>
          </tr>";
    while($row = mysqli_fetch_array($query_antrian)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td><strong>{$row['no_antrian']}</strong></td>";
        echo "<td>{$row['no_service']}</td>";
        echo "<td>{$row['tanggal']}</td>";
        echo "<td>{$row['jam_ambil']}</td>";
        echo "<td><strong>{$row['status_antrian']}</strong></td>";
        echo "<td><strong>{$row['status_servis']}</strong></td>";
        echo "<td>{$row['prioritas']}</td>";
        echo "<td>{$row['estimasi_waktu']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>Tidak ada data antrian hari ini!</p>";
}

echo "<hr>";

// 3. Cek service yang TIDAK punya antrian
echo "<h3>3. Service Hari Ini yang BELUM Punya Antrian</h3>";
$where_cabang_s = $is_admin_check ? '' : " AND s.kd_cabang = '$kd_cabang'";
$query_no_antrian = mysqli_query($koneksi, "
    SELECT s.no_service, s.tanggal, s.jam, s.no_pelanggan, s.no_polisi, s.status_servis
    FROM tblservice s
    LEFT JOIN tb_antrian_servis a ON s.no_service = a.no_service
    WHERE (DATE(s.tanggal) = '$tgl_hari_ini' OR DATE(s.created_at) = '$tgl_hari_ini')
    AND a.id IS NULL$where_cabang_s
    ORDER BY s.created_at DESC
");

if(mysqli_num_rows($query_no_antrian) > 0) {
    echo "<p style='color:orange;'><strong>MASALAH DITEMUKAN!</strong> Ada service yang belum dibuatkan antrian:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr>
            <th>No Service</th>
            <th>Tanggal</th>
            <th>Jam</th>
            <th>No Pelanggan</th>
            <th>No Polisi</th>
            <th>Status Servis</th>
            <th>Aksi</th>
          </tr>";
    while($row = mysqli_fetch_array($query_no_antrian)) {
        echo "<tr>";
        echo "<td>{$row['no_service']}</td>";
        echo "<td>{$row['tanggal']}</td>";
        echo "<td>{$row['jam']}</td>";
        echo "<td>{$row['no_pelanggan']}</td>";
        echo "<td>{$row['no_polisi']}</td>";
        echo "<td><strong>{$row['status_servis']}</strong></td>";
        echo "<td><a href='fix_antrian.php?no_service={$row['no_service']}' style='color:blue;'>Buatkan Antrian</a></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:green;'>Semua service sudah punya antrian! ✓</p>";
}

echo "<hr>";

// 4. Total statistik
echo "<h3>4. Statistik Hari Ini</h3>";
$exists_cabang_tas = $is_admin_check ? '' : " AND EXISTS (SELECT 1 FROM tblservice sx WHERE sx.no_service = tb_antrian_servis.no_service AND sx.kd_cabang = '$kd_cabang')";
$total_service = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM tblservice WHERE (DATE(tanggal) = '$tgl_hari_ini' OR DATE(created_at) = '$tgl_hari_ini')$where_cabang_svc"));
$total_antrian = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini'$exists_cabang_tas"));
$total_menunggu = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'menunggu'$exists_cabang_tas"));
$total_diproses = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'diproses'$exists_cabang_tas"));
$total_selesai = mysqli_num_rows(mysqli_query($koneksi, "SELECT * FROM tb_antrian_servis WHERE tanggal = '$tgl_hari_ini' AND status_antrian = 'selesai'$exists_cabang_tas"));

echo "<ul>";
echo "<li>Total Service: <strong>$total_service</strong></li>";
echo "<li>Total Antrian: <strong>$total_antrian</strong></li>";
echo "<li>Antrian Menunggu: <strong>$total_menunggu</strong></li>";
echo "<li>Antrian Diproses: <strong>$total_diproses</strong></li>";
echo "<li>Antrian Selesai: <strong>$total_selesai</strong></li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php'>← Kembali ke Dashboard</a></p>";
?>
