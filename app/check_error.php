<?php
/**
 * CHECK PHP ERROR
 * File ini untuk menampilkan error PHP yang terjadi
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔍 CHECK PHP ERROR</h2>";
echo "<hr>";

// Cek PHP error log
$error_log_locations = [
    'C:\xampp\php\logs\php_error_log',
    'C:\xampp\apache\logs\error.log',
    'C:\xampp\logs\php_error_log',
    __DIR__ . '/error.log'
];

echo "<h3>LOKASI ERROR LOG:</h3>";
foreach($error_log_locations as $log_file) {
    if(file_exists($log_file)) {
        echo "✅ <strong>$log_file</strong> (ADA)<br>";
        
        // Baca 20 baris terakhir
        $lines = file($log_file);
        $last_lines = array_slice($lines, -20);
        
        echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow-y: auto;'>";
        foreach($last_lines as $line) {
            // Highlight error
            if(stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                echo "<span style='color: red; font-weight: bold;'>" . htmlspecialchars($line) . "</span>";
            } else {
                echo htmlspecialchars($line);
            }
        }
        echo "</pre>";
        echo "<br>";
    } else {
        echo "❌ $log_file (TIDAK ADA)<br>";
    }
}

echo "<hr>";
echo "<h3>TEST SYNTAX FILE:</h3>";

// Test syntax file yang baru diubah
$files_to_check = [
    'config_whatsapp.php',
    'class_whatsapp_automation.php',
    'servis-input-reguler.php'
];

foreach($files_to_check as $file) {
    if(file_exists($file)) {
        echo "<strong>$file:</strong> ";
        
        // Check syntax
        $output = [];
        $return_var = 0;
        exec("php -l $file 2>&1", $output, $return_var);
        
        if($return_var === 0) {
            echo "<span style='color: green;'>✅ Syntax OK</span><br>";
        } else {
            echo "<span style='color: red;'>❌ Syntax Error:</span><br>";
            echo "<pre style='background: #f8d7da; padding: 10px; border: 1px solid #f5c6cb;'>";
            echo implode("\n", $output);
            echo "</pre>";
        }
    } else {
        echo "<strong>$file:</strong> <span style='color: orange;'>⚠️ File tidak ada</span><br>";
    }
}

echo "<hr>";
echo "<h3>TEST REQUIRE FILE:</h3>";

// Test require config
echo "<strong>1. Test require config_whatsapp.php:</strong><br>";
try {
    if(file_exists('config_whatsapp.php')) {
        require_once 'config_whatsapp.php';
        echo "✅ Config berhasil di-load<br>";
        
        // Cek konstanta
        echo "   - WA_API_ENABLED: " . (defined('WA_API_ENABLED') ? 'OK' : 'NOT DEFINED') . "<br>";
        echo "   - WA_API_KEY: " . (defined('WA_API_KEY') ? 'OK' : 'NOT DEFINED') . "<br>";
    } else {
        echo "❌ File tidak ada<br>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>2. Test require class_whatsapp_automation.php:</strong><br>";
try {
    if(file_exists('class_whatsapp_automation.php')) {
        require_once 'class_whatsapp_automation.php';
        echo "✅ Class berhasil di-load<br>";
        
        // Cek class
        echo "   - WhatsAppAutomation: " . (class_exists('WhatsAppAutomation') ? 'OK' : 'NOT FOUND') . "<br>";
    } else {
        echo "❌ File tidak ada<br>";
    }
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><a href='servis-reguler.php'>← Kembali ke Servis Reguler</a></p>";
?>
