<?php
session_start();
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";

echo "<h3>🔧 Creating Validation Log Table</h3>";

// Create the validation log table
$create_log_table = "CREATE TABLE IF NOT EXISTS `tbitem_validation_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `noitem` varchar(20) NOT NULL,
    `action` varchar(50) NOT NULL,
    `notes` text,
    `user_id` int(11) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_noitem` (`noitem`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";

if (mysqli_query($koneksi, $create_log_table)) {
    echo "<p style='color:green'>✅ Success: tbitem_validation_log table created successfully!</p>";
} else {
    echo "<p style='color:red'>❌ Error: " . mysqli_error($koneksi) . "</p>";
}

// Test the table
$test_query = "SELECT COUNT(*) as count FROM tbitem_validation_log";
$result = mysqli_query($koneksi, $test_query);
if ($result) {
    $count = mysqli_fetch_assoc($result)['count'];
    echo "<p style='color:blue'>ℹ️ Table has $count records</p>";
} else {
    echo "<p style='color:red'>❌ Cannot query the table</p>";
}

echo "<br><a href='barang_validate.php?kd=TEST001'>🧪 Test Validation System</a>";
echo " | <a href='barang.php'>📋 Back to Items</a>";
?>