<?php
require_once("../config/koneksi.php");

$no_polisi = "G 3495 AF";

echo "<h3>Checking Missing Vehicle Data</h3>";
echo "<p>Testing for nopolisi: <strong>$no_polisi</strong></p>";

// Check if vehicle exists
$check_vehicle = mysqli_query($koneksi, "SELECT * FROM view_cari_kendaraan WHERE nopolisi='$no_polisi'");
$vehicle_exists = mysqli_num_rows($check_vehicle) > 0;

if($vehicle_exists) {
    echo "<p style='color:green'>✅ Vehicle data EXISTS</p>";
} else {
    echo "<p style='color:red'>❌ Vehicle data DOES NOT EXIST</p>";
    
    // Check if there's service history for this nopolisi
    echo "<h4>Checking service history for data migration:</h4>";
    $check_service = mysqli_query($koneksi, "SELECT no_service, no_polisi, tanggal, km_skr FROM tblservice WHERE no_polisi='$no_polisi' ORDER BY tanggal DESC LIMIT 5");
    
    if(mysqli_num_rows($check_service) > 0) {
        echo "<p style='color:orange'>⚠️ Found service history for this vehicle!</p>";
        echo "<table border='1' style='border-collapse:collapse'>";
        echo "<tr><th>No Service</th><th>No Polisi</th><th>Tanggal</th><th>KM</th></tr>";
        while($row = mysqli_fetch_assoc($check_service)) {
            echo "<tr>";
            echo "<td>" . $row['no_service'] . "</td>";
            echo "<td>" . $row['no_polisi'] . "</td>";
            echo "<td>" . $row['tanggal'] . "</td>";
            echo "<td>" . $row['km_skr'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p><strong>Recommendation:</strong> Vehicle needs to be registered in master kendaraan table.</p>";
    } else {
        echo "<p>No service history found for this vehicle.</p>";
    }
    
    // Check tblkendaraan directly
    echo "<h4>Checking tblkendaraan table:</h4>";
    $check_direct = mysqli_query($koneksi, "SELECT * FROM tblkendaraan WHERE nopolisi='$no_polisi'");
    if($check_direct && mysqli_num_rows($check_direct) > 0) {
        $data = mysqli_fetch_assoc($check_direct);
        echo "<p style='color:green'>✅ Data found in tblkendaraan!</p>";
        echo "<pre>";
        print_r($data);
        echo "</pre>";
    } else {
        echo "<p style='color:red'>❌ No data in tblkendaraan</p>";
    }
}

// Show where to input vehicle data
echo "<h4>Solution:</h4>";
echo "<ol>";
echo "<li>Go to <strong>Master → Data Kendaraan</strong> menu</li>";
echo "<li>Input vehicle data for nopolisi: <strong>$no_polisi</strong></li>";
echo "<li>Fill in: Pemilik, Merek, Jenis, Warna, No. Rangka, No. Mesin</li>";
echo "<li>After saving, vehicle data will appear in Detail Service</li>";
echo "</ol>";
?>
