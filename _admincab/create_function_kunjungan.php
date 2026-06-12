<?php
/**
 * CREATE FUNCTION KUNJUNGAN
 * Membuat function fn_get_status_member_kunjungan yang diperlukan trigger
 */

require_once '../config/koneksi.php';

echo "<h2>🔧 CREATE FUNCTION KUNJUNGAN</h2>";
echo "<hr>";

echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin-bottom: 20px;'>";
echo "⚠️ <strong>DITEMUKAN FUNCTION KEDUA YANG BELUM ADA!</strong><br><br>";
echo "Trigger memanggil 2 function:<br>";
echo "1. ✅ <code>fn_get_status_member_nominal</code> - SUDAH ADA<br>";
echo "2. ❌ <code>fn_get_status_member_kunjungan</code> - <strong>BELUM ADA</strong><br><br>";
echo "Mari kita buat function yang kedua!";
echo "</div>";

// SQL untuk membuat function
$sql_create = "CREATE FUNCTION fn_get_status_member_kunjungan(p_jumlah_kunjungan INT)
RETURNS VARCHAR(20)
DETERMINISTIC
NO SQL
BEGIN
    DECLARE v_status VARCHAR(20);
    
    -- Tentukan status berdasarkan jumlah kunjungan
    -- Sesuaikan dengan kategori di master_kategori_member
    IF p_jumlah_kunjungan >= 20 THEN
        SET v_status = 'Platinum';
    ELSEIF p_jumlah_kunjungan >= 10 THEN
        SET v_status = 'Gold';
    ELSEIF p_jumlah_kunjungan >= 5 THEN
        SET v_status = 'Silver';
    ELSE
        SET v_status = 'Bronze';
    END IF;
    
    RETURN v_status;
END";

echo "<h3>1. SQL FUNCTION:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($sql_create);
echo "</pre>";

echo "<hr>";
echo "<h3>2. EXECUTE SQL:</h3>";

// Drop function lama jika ada
echo "<strong>Query 1 (Drop old):</strong> ";
$drop_query = "DROP FUNCTION IF EXISTS fn_get_status_member_kunjungan";
if(mysqli_query($koneksi, $drop_query)) {
    echo "<span style='color: green;'>✅ Berhasil</span><br>";
} else {
    echo "<span style='color: orange;'>⚠️ " . mysqli_error($koneksi) . "</span><br>";
}

// Create function baru
echo "<strong>Query 2 (Create new):</strong> ";
if(mysqli_query($koneksi, $sql_create)) {
    echo "<span style='color: green;'>✅ Berhasil</span><br>";
    $success = true;
} else {
    echo "<span style='color: red;'>❌ Gagal</span><br>";
    echo "Error: " . mysqli_error($koneksi) . "<br>";
    $success = false;
}

echo "<hr>";

if($success) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
    echo "✅ <strong>FUNCTION BERHASIL DIBUAT!</strong><br><br>";
    echo "Function <code>fn_get_status_member_kunjungan</code> sudah tersedia.<br><br>";
    echo "<strong>Sekarang KEDUA function sudah lengkap:</strong><br>";
    echo "1. ✅ <code>fn_get_status_member_nominal</code><br>";
    echo "2. ✅ <code>fn_get_status_member_kunjungan</code><br><br>";
    echo "Trigger sekarang bisa berjalan dengan normal!<br><br>";
    echo "<strong>Silakan test input servis & bayar lagi!</strong>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; padding: 15px; border: 1px solid #f5c6cb;'>";
    echo "❌ <strong>GAGAL MEMBUAT FUNCTION</strong><br><br>";
    echo "Coba cara manual:<br>";
    echo "1. Buka phpMyAdmin<br>";
    echo "2. Pilih database <code>fitmotor_dbbengkel</code><br>";
    echo "3. Klik tab 'SQL'<br>";
    echo "4. Copy-paste SQL di atas<br>";
    echo "5. Klik 'Go'<br>";
    echo "</div>";
}

echo "<hr>";

// Verify function created
echo "<h3>3. VERIFY FUNCTION:</h3>";
$query = "SHOW FUNCTION STATUS WHERE Db = 'fitmotor_dbbengkel' AND Name = 'fn_get_status_member_kunjungan'";
$result = mysqli_query($koneksi, $query);

if($result && mysqli_num_rows($result) > 0) {
    echo "✅ Function <code>fn_get_status_member_kunjungan</code> <strong>ADA</strong><br><br>";
    
    // Test function
    echo "<strong>Test Function:</strong><br>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Jumlah Kunjungan</th><th>Status</th></tr>";
    
    $test_cases = [0, 3, 5, 8, 10, 15, 20, 25];
    foreach($test_cases as $kunjungan) {
        $test_query = "SELECT fn_get_status_member_kunjungan($kunjungan) as status";
        $test_result = mysqli_query($koneksi, $test_query);
        if($test_result) {
            $test_row = mysqli_fetch_assoc($test_result);
            echo "<tr>";
            echo "<td>$kunjungan kali</td>";
            echo "<td><strong>" . $test_row['status'] . "</strong></td>";
            echo "</tr>";
        }
    }
    echo "</table>";
} else {
    echo "❌ Function <code>fn_get_status_member_kunjungan</code> <strong>TIDAK ADA</strong><br>";
}

echo "<hr>";

// Show function definition
echo "<h3>4. FUNCTION DEFINITION:</h3>";
$query = "SHOW CREATE FUNCTION fn_get_status_member_kunjungan";
$result = mysqli_query($koneksi, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
    echo htmlspecialchars($row['Create Function']);
    echo "</pre>";
} else {
    echo "⚠️ Tidak bisa menampilkan definition<br>";
}

echo "<hr>";

// Cek kedua function
echo "<h3>5. CEK SEMUA FUNCTION:</h3>";
$query = "SHOW FUNCTION STATUS WHERE Db = 'fitmotor_dbbengkel' AND Name LIKE 'fn_get_status_member%'";
$result = mysqli_query($koneksi, $query);

if($result && mysqli_num_rows($result) > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Function Name</th><th>Type</th><th>Status</th></tr>";
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td><code>" . $row['Name'] . "</code></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td><span style='color: green;'>✅ ADA</span></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br>";
    $count = mysqli_num_rows($result);
    if($count >= 2) {
        echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb;'>";
        echo "✅ <strong>SEMUA FUNCTION LENGKAP! ($count function)</strong>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb;'>";
        echo "⚠️ <strong>Masih kurang function! (Hanya $count dari 2)</strong>";
        echo "</div>";
    }
} else {
    echo "❌ Tidak ada function yang ditemukan<br>";
}

echo "<hr>";
echo "<p><a href='servis-reguler.php'>← Kembali ke Servis Reguler</a> | ";
echo "<a href='fix_trigger_error.php'>Cek Trigger</a></p>";
?>
