<?php
/**
 * FIX TRIGGER ERROR
 * Error: FUNCTION fn_get_status_member_nominal does not exist
 */

require_once '../config/koneksi.php';

echo "<h2>🔧 FIX TRIGGER ERROR</h2>";
echo "<hr>";

// 1. Cek trigger yang ada di tblservice
echo "<h3>1. CEK TRIGGER DI TBLSERVICE:</h3>";
$query = "SHOW TRIGGERS FROM fitmotor_dbbengkel WHERE `Table` = 'tblservice'";
$result = mysqli_query($koneksi, $query);

if($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Trigger</th><th>Event</th><th>Table</th><th>Timing</th><th>Statement</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Trigger'] . "</td>";
        echo "<td>" . $row['Event'] . "</td>";
        echo "<td>" . $row['Table'] . "</td>";
        echo "<td>" . $row['Timing'] . "</td>";
        echo "<td><pre style='max-width: 600px; overflow-x: auto;'>" . htmlspecialchars($row['Statement']) . "</pre></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "✅ Tidak ada trigger di tblservice<br>";
}

echo "<hr>";

// 2. Cek apakah function fn_get_status_member_nominal ada
echo "<h3>2. CEK FUNCTION fn_get_status_member_nominal:</h3>";
$query = "SHOW FUNCTION STATUS WHERE Db = 'fitmotor_dbbengkel' AND Name = 'fn_get_status_member_nominal'";
$result = mysqli_query($koneksi, $query);

if($result && mysqli_num_rows($result) > 0) {
    echo "✅ Function fn_get_status_member_nominal ADA<br>";
    
    // Show function definition
    $query2 = "SHOW CREATE FUNCTION fn_get_status_member_nominal";
    $result2 = mysqli_query($koneksi, $query2);
    if($result2) {
        $row = mysqli_fetch_assoc($result2);
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
        echo htmlspecialchars($row['Create Function']);
        echo "</pre>";
    }
} else {
    echo "❌ Function fn_get_status_member_nominal TIDAK ADA<br>";
    echo "<br>";
    echo "<strong>SOLUSI:</strong><br>";
    echo "Function ini diperlukan oleh trigger. Ada 2 opsi:<br>";
    echo "1. <strong>DROP TRIGGER</strong> yang menggunakan function ini (RECOMMENDED)<br>";
    echo "2. <strong>CREATE FUNCTION</strong> fn_get_status_member_nominal<br>";
}

echo "<hr>";

// 3. Cari trigger yang memanggil function ini
echo "<h3>3. CARI TRIGGER YANG MEMANGGIL FUNCTION:</h3>";
$query = "SELECT 
            TRIGGER_NAME,
            EVENT_MANIPULATION,
            EVENT_OBJECT_TABLE,
            ACTION_TIMING,
            ACTION_STATEMENT
          FROM information_schema.TRIGGERS
          WHERE TRIGGER_SCHEMA = 'fitmotor_dbbengkel'
          AND ACTION_STATEMENT LIKE '%fn_get_status_member_nominal%'";
$result = mysqli_query($koneksi, $query);

if($result && mysqli_num_rows($result) > 0) {
    echo "❌ <strong>DITEMUKAN TRIGGER YANG BERMASALAH:</strong><br><br>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb; margin-bottom: 10px;'>";
        echo "<strong>Trigger Name:</strong> " . $row['TRIGGER_NAME'] . "<br>";
        echo "<strong>Event:</strong> " . $row['ACTION_TIMING'] . " " . $row['EVENT_MANIPULATION'] . "<br>";
        echo "<strong>Table:</strong> " . $row['EVENT_OBJECT_TABLE'] . "<br>";
        echo "<strong>Statement:</strong><br>";
        echo "<pre style='background: white; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($row['ACTION_STATEMENT']);
        echo "</pre>";
        echo "</div>";
        
        // Tombol untuk drop trigger
        echo "<form method='post' style='display: inline;'>";
        echo "<input type='hidden' name='drop_trigger' value='" . $row['TRIGGER_NAME'] . "'>";
        echo "<button type='submit' onclick=\"return confirm('Yakin ingin DROP trigger " . $row['TRIGGER_NAME'] . "?')\" style='background: #dc3545; color: white; padding: 10px 20px; border: none; cursor: pointer;'>DROP TRIGGER " . $row['TRIGGER_NAME'] . "</button>";
        echo "</form>";
        echo "<br><br>";
    }
} else {
    echo "✅ Tidak ada trigger yang memanggil function ini<br>";
}

// Handle drop trigger
if(isset($_POST['drop_trigger'])) {
    $trigger_name = mysqli_real_escape_string($koneksi, $_POST['drop_trigger']);
    echo "<hr>";
    echo "<h3>4. DROP TRIGGER:</h3>";
    
    $query = "DROP TRIGGER IF EXISTS `$trigger_name`";
    if(mysqli_query($koneksi, $query)) {
        echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
        echo "✅ <strong>BERHASIL DROP TRIGGER: $trigger_name</strong><br>";
        echo "Silakan refresh halaman dan test lagi.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb;'>";
        echo "❌ <strong>GAGAL DROP TRIGGER:</strong><br>";
        echo "Error: " . mysqli_error($koneksi);
        echo "</div>";
    }
}

echo "<hr>";
echo "<p><a href='servis-reguler.php'>← Kembali ke Servis Reguler</a> | ";
echo "<a href='fix_trigger_error.php'>Refresh</a></p>";
?>
