<?php
/**
 * SHOW ERROR - Tampilkan error PHP secara detail
 */

// Enable semua error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>🔍 SHOW PHP ERROR</h2>";
echo "<hr>";

// Cek versi PHP
echo "<h3>PHP VERSION:</h3>";
echo "PHP Version: <strong>" . phpversion() . "</strong><br>";
echo "PHP SAPI: " . php_sapi_name() . "<br>";
echo "<br>";

// Cek error log Apache
echo "<h3>APACHE ERROR LOG (20 baris terakhir):</h3>";
$apache_log = 'C:\xampp\apache\logs\error.log';
if(file_exists($apache_log)) {
    $lines = file($apache_log);
    $last_lines = array_slice($lines, -20);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
    foreach($last_lines as $line) {
        if(stripos($line, 'error') !== false || stripos($line, 'fatal') !== false || stripos($line, 'parse') !== false) {
            echo "<span style='color: red; font-weight: bold;'>" . htmlspecialchars($line) . "</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "❌ File tidak ditemukan: $apache_log<br>";
}

echo "<hr>";

// Cek error log PHP
echo "<h3>PHP ERROR LOG (20 baris terakhir):</h3>";
$php_log = 'C:\xampp\php\logs\php_error_log';
if(file_exists($php_log)) {
    $lines = file($php_log);
    $last_lines = array_slice($lines, -20);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 400px; overflow-y: auto;'>";
    foreach($last_lines as $line) {
        if(stripos($line, 'error') !== false || stripos($line, 'fatal') !== false || stripos($line, 'parse') !== false) {
            echo "<span style='color: red; font-weight: bold;'>" . htmlspecialchars($line) . "</span>";
        } else {
            echo htmlspecialchars($line);
        }
    }
    echo "</pre>";
} else {
    echo "⚠️ File tidak ditemukan: $php_log<br>";
}

echo "<hr>";

// Test load file yang bermasalah
echo "<h3>TEST LOAD FILE:</h3>";

echo "<strong>1. Test config_whatsapp.php:</strong><br>";
try {
    ob_start();
    include 'config_whatsapp.php';
    $output = ob_get_clean();
    echo "✅ Berhasil di-load<br>";
    if(!empty($output)) {
        echo "⚠️ Ada output: <pre>" . htmlspecialchars($output) . "</pre>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
} catch(Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>2. Test class_whatsapp_automation.php:</strong><br>";
try {
    ob_start();
    include 'class_whatsapp_automation.php';
    $output = ob_get_clean();
    echo "✅ Berhasil di-load<br>";
    if(!empty($output)) {
        echo "⚠️ Ada output: <pre>" . htmlspecialchars($output) . "</pre>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
} catch(Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='servis-reguler.php'>← Kembali</a></p>";
?>
