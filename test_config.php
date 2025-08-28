<?php
// test_config.php - Simpan ini di folder yang sama dengan file stok_masuk Anda

echo "<h2>🔍 Test Config File Access</h2>";
echo "<style>body{font-family:Arial;} .success{color:green;} .error{color:red;} pre{background:#f5f5f5;padding:10px;}</style>";

// Test 1: Cek path file config
$config_paths = [
    '../config/config_accurate.php',
    './config/config_accurate.php', 
    '../../config/config_accurate.php',
    'config_accurate.php'
];

echo "<h3>📂 Testing Config File Paths:</h3>";
foreach ($config_paths as $path) {
    echo "<p><strong>Path:</strong> $path</p>";
    if (file_exists($path)) {
        echo "<p class='success'>✅ File exists</p>";
        echo "<p><strong>Full Path:</strong> " . realpath($path) . "</p>";
        
        // Try to include it
        try {
            include_once $path;
            
            // Check if constants are defined
            if (defined('API_TOKEN')) {
                echo "<p class='success'>✅ API_TOKEN is defined</p>";
                echo "<p><strong>Token (first 30 chars):</strong> " . substr(API_TOKEN, 0, 30) . "...</p>";
                echo "<p><strong>Token Length:</strong> " . strlen(API_TOKEN) . " characters</p>";
            } else {
                echo "<p class='error'>❌ API_TOKEN is not defined</p>";
            }
            
            if (defined('CLIENT_ID')) {
                echo "<p class='success'>✅ CLIENT_ID: " . CLIENT_ID . "</p>";
            } else {
                echo "<p class='error'>❌ CLIENT_ID not defined</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error including file: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='error'>❌ File not found</p>";
    }
    echo "<hr>";
}

// Test 2: Show current directory
echo "<h3>📍 Current Directory Info:</h3>";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";

// Test 3: Manual config definition
echo "<h3>🔧 Manual Config Test:</h3>";
echo "<p>If config file not found, you can define constants manually:</p>";
echo "<pre>";
echo "define('API_TOKEN', 'aat.MTAw.eyJ2IjoxLCJ1Ijo4NjEwMzcsImQiOjE4NjczMjcsImFpIjo1NTUxOCwiYWsiOiIzYjNjNzk3OS02M2ExLTQ5M2EtYWZkNi01Y2NiNGIyZjNkNzIiLCJhbiI6IlBST0dSQU0gQkVOR0tFTCBGSVQgTU9UT1IiLCJhcCI6IjM2OWFlOTg1LWIwMWYtNDc0ZC05ZGFkLTgwZGQ5Yzg1MzIxMiIsInQiOjE3NDg0OTA4MTA5MDR9.MIbI/euMn/DNS3LSxWznCBN2Wmi9YG2Ik1kynk2ghJ3/yI8Q+Pppzskk7yoAd/7yNZxGwfYYrGwT9dLlF/u5pbkFc0W8pi0gqiCd8bSeYBAdOoFKxo5ZWD/Mj3Tb16ui9RAoiekfLWCXUkzd2xnbbq+l78kXnbHg5OqxoYkpUIddMI7XJlZbs6kKGRD3bDnrGt4FP+e55Fc=.RYKlzTDl2EcY70g6FXV06PhRqKfB4j0YtIuCKNj1N0I');";
echo "</pre>";

// Test manual definition
define('API_TOKEN_TEST', 'aat.MTAw.eyJ2IjoxLCJ1Ijo4NjEwMzcsImQiOjE4NjczMjcsImFpIjo1NTUxOCwiYWsiOiIzYjNjNzk3OS02M2ExLTQ5M2EtYWZkNi01Y2NiNGIyZjNkNzIiLCJhbiI6IlBST0dSQU0gQkVOR0tFTCBGSVQgTU9UT1IiLCJhcCI6IjM2OWFlOTg1LWIwMWYtNDc0ZC05ZGFkLTgwZGQ5Yzg1MzIxMiIsInQiOjE3NDg0OTA4MTA5MDR9.MIbI/euMn/DNS3LSxWznCBN2Wmi9YG2Ik1kynk2ghJ3/yI8Q+Pppzskk7yoAd/7yNZxGwfYYrGwT9dLlF/u5pbkFc0W8pi0gqiCd8bSeYBAdOoFKxo5ZWD/Mj3Tb16ui9RAoiekfLWCXUkzd2xnbbq+l78kXnbHg5OqxoYkpUIddMI7XJlZbs6kKGRD3bDnrGt4FP+e55Fc=.RYKlzTDl2EcY70g6FXV06PhRqKfB4j0YtIuCKNj1N0I');

if (defined('API_TOKEN_TEST')) {
    echo "<p class='success'>✅ Manual definition works!</p>";
}

// Test 4: Show what's in config directory
echo "<h3>📁 Config Directory Contents:</h3>";
$possible_dirs = ['../config/', './config/', '../../config/'];
foreach ($possible_dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p><strong>Directory:</strong> $dir</p>";
        $files = scandir($dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                echo "<li>$file</li>";
            }
        }
        echo "</ul>";
    }
}
?>