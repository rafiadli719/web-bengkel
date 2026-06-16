<?php
/**
 * Database Update Script - Add Keluhan Link Columns
 * Adds kode_keluhan and kategori columns to tbservis_keluhan_status
 */

// Robust include for koneksi.php
$koneksi_paths = [
    __DIR__ . '/../config/koneksi.php',
    __DIR__ . '/../../config/koneksi.php',
    '../config/koneksi.php'
];

$koneksi_loaded = false;
foreach ($koneksi_paths as $path) {
    if (file_exists($path)) {
        include $path;
        $koneksi_loaded = true;
        break;
    }
}

if (!$koneksi_loaded) {
    die("ERROR: Could not find koneksi.php in any expected location!");
}

echo "<h2>Database Update - Add Keluhan Link Columns</h2>";
echo "<p>Adding kode_keluhan and kategori columns to tbservis_keluhan_status...</p>";

// Add kode_keluhan column
$sql1 = "ALTER TABLE tbservis_keluhan_status 
         ADD COLUMN kode_keluhan VARCHAR(10) NULL AFTER keluhan,
         ADD INDEX idx_kode_keluhan (kode_keluhan)";

if (mysqli_query($koneksi, $sql1)) {
    echo "<p style='color: green;'>✓ Successfully added kode_keluhan column</p>";
} else {
    $error = mysqli_error($koneksi);
    if (strpos($error, 'Duplicate column') !== false) {
        echo "<p style='color: orange;'>⚠ kode_keluhan column already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding kode_keluhan: " . $error . "</p>";
    }
}

// Add kategori column
$sql2 = "ALTER TABLE tbservis_keluhan_status 
         ADD COLUMN kategori VARCHAR(50) NULL AFTER kode_keluhan";

if (mysqli_query($koneksi, $sql2)) {
    echo "<p style='color: green;'>✓ Successfully added kategori column</p>";
} else {
    $error = mysqli_error($koneksi);
    if (strpos($error, 'Duplicate column') !== false) {
        echo "<p style='color: orange;'>⚠ kategori column already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding kategori: " . $error . "</p>";
    }
}

// Verify the changes
$verify = mysqli_query($koneksi, "DESCRIBE tbservis_keluhan_status");
echo "<h3>Current Table Structure:</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($verify)) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<p><strong>Database update completed!</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
