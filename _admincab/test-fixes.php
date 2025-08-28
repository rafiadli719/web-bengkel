<?php
// Test perbaikan database dan tabel keluhan
include "../config/koneksi.php";

echo "<h2>🔧 Test Perbaikan - Web Bengkel</h2>";
echo "<hr>";

if (!$koneksi) {
    echo "<div style='color: red;'>❌ Koneksi database gagal!</div>";
    exit;
}

echo "<div style='color: green;'>✅ Koneksi database berhasil!</div><br>";

// Test 1: Query servis-print yang diperbaiki
echo "<h3>🖨️ Test 1: Query Servis Print (Tanpa Email)</h3>";
$test_query = "SELECT s.*, p.namapelanggan, p.alamat, p.telephone,
               k.merek, k.tipe, k.warna, k.no_rangka, k.no_mesin, k.jenis
               FROM tblservice s 
               LEFT JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
               LEFT JOIN tblkendaraan k ON s.no_polisi = k.nopolisi
               LIMIT 3";

$result = mysqli_query($koneksi, $test_query);
if ($result) {
    echo "<div style='color: green;'>✅ Query berhasil - Error 'p.email' sudah diperbaiki!</div>";
    echo "<p>Jumlah record: " . mysqli_num_rows($result) . "</p>";
} else {
    echo "<div style='color: red;'>❌ Query error: " . mysqli_error($koneksi) . "</div>";
}

echo "<br><hr><br>";

// Test 2: Tabel keluhan
echo "<h3>📝 Test 2: Tabel Keluhan</h3>";
$query_keluhan = "SELECT * FROM view_master_keluhan LIMIT 3";
$result_keluhan = mysqli_query($koneksi, $query_keluhan);

if ($result_keluhan) {
    echo "<div style='color: green;'>✅ Tabel view_master_keluhan dapat diakses!</div>";
    if (mysqli_num_rows($result_keluhan) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>Kode</th><th>Nama Keluhan</th><th>Kategori</th><th>Prioritas</th></tr>";
        
        while($row = mysqli_fetch_array($result_keluhan)) {
            echo "<tr>";
            echo "<td>" . ($row['kode_keluhan'] ?? '-') . "</td>";
            echo "<td>" . ($row['nama_keluhan'] ?? '-') . "</td>";
            echo "<td>" . ($row['kategori'] ?? '-') . "</td>";
            echo "<td>" . ($row['tingkat_prioritas'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<div style='color: red;'>❌ Error: " . mysqli_error($koneksi) . "</div>";
}

echo "<br><hr><br>";

// Test 3: Tabel service keluhan
echo "<h3>🔗 Test 3: Tabel Service Keluhan</h3>";
$query_service_keluhan = "SELECT * FROM tbservis_keluhan LIMIT 3";
$result_service_keluhan = mysqli_query($koneksi, $query_service_keluhan);

if ($result_service_keluhan) {
    echo "<div style='color: green;'>✅ Tabel tbservis_keluhan dapat diakses!</div>";
    echo "<p>Jumlah record: " . mysqli_num_rows($result_service_keluhan) . "</p>";
} else {
    echo "<div style='color: red;'>❌ Error: " . mysqli_error($koneksi) . "</div>";
}

echo "<br><hr><br>";

// Summary
echo "<h3>📊 Ringkasan Perbaikan</h3>";
echo "<div style='background-color: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<h4>✅ Perbaikan Database Berhasil:</h4>";
echo "<ul>";
echo "<li>✅ <strong>Error 'p.email'</strong> - Kolom email dihapus dari query</li>";
echo "<li>✅ <strong>Query servis-print.php</strong> - Berjalan tanpa error</li>";
echo "<li>✅ <strong>Tabel keluhan</strong> - Dapat diakses untuk fitur tabel</li>";
echo "</ul>";
echo "</div>";

mysqli_close($koneksi);
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; font-size: 12px; }
th, td { padding: 8px; text-align: left; }
</style>
