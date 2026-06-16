<?php
/**
 * CREATE MISSING FUNCTION
 * Membuat function fn_get_status_member_nominal yang diperlukan trigger
 */

require_once '../config/koneksi.php';

echo "<h2>🔧 CREATE MISSING FUNCTION</h2>";
echo "<hr>";

// SQL untuk membuat function
$sql_function = "
DROP FUNCTION IF EXISTS fn_get_status_member_nominal;

DELIMITER $$

CREATE FUNCTION fn_get_status_member_nominal(p_no_pelanggan VARCHAR(50))
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_total_nominal DECIMAL(15,2);
    
    -- Ambil total nominal dari statistik pelanggan
    SELECT total_nominal INTO v_total_nominal
    FROM statistik_pelanggan
    WHERE no_pelanggan = p_no_pelanggan
    LIMIT 1;
    
    -- Jika tidak ada data, return Bronze
    IF v_total_nominal IS NULL THEN
        RETURN 'Bronze';
    END IF;
    
    -- Tentukan status berdasarkan total nominal
    -- Sesuaikan dengan kategori di master_kategori_member
    IF v_total_nominal >= 10000000 THEN
        SET v_status = 'Platinum';
    ELSEIF v_total_nominal >= 5000000 THEN
        SET v_status = 'Gold';
    ELSEIF v_total_nominal >= 2000000 THEN
        SET v_status = 'Silver';
    ELSE
        SET v_status = 'Bronze';
    END IF;
    
    RETURN v_status;
END$$

DELIMITER ;
";

echo "<h3>1. SQL FUNCTION:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
echo htmlspecialchars($sql_function);
echo "</pre>";

echo "<hr>";
echo "<h3>2. EXECUTE SQL:</h3>";

// Split SQL by delimiter
$queries = [
    "DROP FUNCTION IF EXISTS fn_get_status_member_nominal",
    "CREATE FUNCTION fn_get_status_member_nominal(p_no_pelanggan VARCHAR(50))
RETURNS VARCHAR(20)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE v_status VARCHAR(20);
    DECLARE v_total_nominal DECIMAL(15,2);
    
    SELECT total_nominal INTO v_total_nominal
    FROM statistik_pelanggan
    WHERE no_pelanggan = p_no_pelanggan
    LIMIT 1;
    
    IF v_total_nominal IS NULL THEN
        RETURN 'Bronze';
    END IF;
    
    IF v_total_nominal >= 10000000 THEN
        SET v_status = 'Platinum';
    ELSEIF v_total_nominal >= 5000000 THEN
        SET v_status = 'Gold';
    ELSEIF v_total_nominal >= 2000000 THEN
        SET v_status = 'Silver';
    ELSE
        SET v_status = 'Bronze';
    END IF;
    
    RETURN v_status;
END"
];

$success = true;
foreach($queries as $index => $query) {
    $query = trim($query);
    if(empty($query)) continue;
    
    echo "<strong>Query " . ($index + 1) . ":</strong> ";
    
    if(mysqli_query($koneksi, $query)) {
        echo "<span style='color: green;'>✅ Berhasil</span><br>";
    } else {
        echo "<span style='color: red;'>❌ Gagal</span><br>";
        echo "Error: " . mysqli_error($koneksi) . "<br>";
        $success = false;
    }
}

echo "<hr>";

if($success) {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb;'>";
    echo "✅ <strong>FUNCTION BERHASIL DIBUAT!</strong><br><br>";
    echo "Function <code>fn_get_status_member_nominal</code> sudah tersedia.<br>";
    echo "Trigger sekarang bisa berjalan dengan normal.<br><br>";
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
$query = "SHOW FUNCTION STATUS WHERE Db = 'fitmotor_dbbengkel' AND Name = 'fn_get_status_member_nominal'";
$result = mysqli_query($koneksi, $query);

if($result && mysqli_num_rows($result) > 0) {
    echo "✅ Function <code>fn_get_status_member_nominal</code> <strong>ADA</strong><br><br>";
    
    // Test function
    echo "<strong>Test Function:</strong><br>";
    $test_query = "SELECT fn_get_status_member_nominal('TEST') as status";
    $test_result = mysqli_query($koneksi, $test_query);
    if($test_result) {
        $test_row = mysqli_fetch_assoc($test_result);
        echo "Result: <code>" . $test_row['status'] . "</code> ✅<br>";
    } else {
        echo "Error: " . mysqli_error($koneksi) . "<br>";
    }
} else {
    echo "❌ Function <code>fn_get_status_member_nominal</code> <strong>TIDAK ADA</strong><br>";
}

echo "<hr>";

// Show function definition
echo "<h3>4. FUNCTION DEFINITION:</h3>";
$query = "SHOW CREATE FUNCTION fn_get_status_member_nominal";
$result = mysqli_query($koneksi, $query);
if($result) {
    $row = mysqli_fetch_assoc($result);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
    echo htmlspecialchars($row['Create Function']);
    echo "</pre>";
} else {
    echo "⚠️ Tidak bisa menampilkan definition<br>";
    echo "Error: " . mysqli_error($koneksi) . "<br>";
}

echo "<hr>";
echo "<p><a href='servis-reguler.php'>← Kembali ke Servis Reguler</a> | ";
echo "<a href='fix_trigger_error.php'>Cek Trigger</a></p>";
?>
