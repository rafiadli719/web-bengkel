<?php
// Temporary file to check service data
include '../config/koneksi.php';

echo "<h3>Daftar 10 Service Terakhir:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>No</th><th>No Service</th><th>Tanggal</th><th>Status</th><th>Link Test</th></tr>";

$sql = mysqli_query($koneksi, "SELECT no_service, tgl_service, status FROM tblservice ORDER BY id DESC LIMIT 10");

if($sql) {
    $no = 1;
    while($r = mysqli_fetch_assoc($sql)) {
        echo "<tr>";
        echo "<td>".$no."</td>";
        echo "<td>".$r['no_service']."</td>";
        echo "<td>".$r['tgl_service']."</td>";
        echo "<td>".$r['status']."</td>";
        echo "<td><a href='servis-input-reguler-coba.php?snoserv=".urlencode($r['no_service'])."' target='_blank'>Test Redesign</a></td>";
        echo "</tr>";
        $no++;
    }
} else {
    echo "<tr><td colspan='5'>Error: ".mysqli_error($koneksi)."</td></tr>";
}

echo "</table>";

echo "<br><br>";
echo "<p>Klik link 'Test Redesign' untuk membuka halaman redesign dengan nomor service tersebut.</p>";
?>
