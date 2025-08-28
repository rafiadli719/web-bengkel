<?php
/**
 * Debug Script untuk Accurate Configuration
 * File: debug_accurate_config.php
 */

echo "=== ACCURATE API DEBUG SCRIPT ===\n\n";

// Step 1: Check if config file exists
echo "1. Checking Config File...\n";
$config_file = 'config/accurate_config.php';
if (file_exists($config_file)) {
    echo "   ✅ Config file exists: $config_file\n";
    
    // Include and check
    include_once $config_file;
    
    // Check if constants are defined
    if (defined('ACCURATE_API_TOKEN')) {
        echo "   ✅ ACCURATE_API_TOKEN defined\n";
    } else {
        echo "   ❌ ACCURATE_API_TOKEN NOT defined\n";
    }
    
    if (defined('ACCURATE_SIGNATURE_SECRET')) {
        echo "   ✅ ACCURATE_SIGNATURE_SECRET defined\n";
    } else {
        echo "   ❌ ACCURATE_SIGNATURE_SECRET NOT defined\n";
    }
    
} else {
    echo "   ❌ Config file NOT exists: $config_file\n";
    echo "   Creating basic config file...\n";
    
    // Create basic config
    $config_content = '<?php
// Basic Accurate API Configuration
define("ACCURATE_API_TOKEN", "aat.MTAw.eyJ2IjoxLCJ1Ijo4NjEwMzcsImQiOjE4NjczMjcsImFpIjo1NTUxOCwiYWsiOiIzYjNjNzk3OS02M2ExLTQ5M2EtYWZkNi01Y2NiNGIyZjNkNzIiLCJhbiI6IlBST0dSQU0gQkVOR0tFTCBGSVQgTU9UT1IiLCJhcCI6IjM2OWFlOTg1LWIwMWYtNDc0ZC05ZGFkLTgwZGQ5Yzg1MzIxMiIsInQiOjE3NDg0OTA4MTA5MDR9.MIbI/euMn/DNS3LSxWznCBN2Wmi9YG2Ik1kynk2ghJ3/yI8Q+Pppzskk7yoAd/7yNZxGwfYYrGwT9dLlF/u5pbkFc0W8pi0gqiCd8bSeYBAdOoFKxo5ZWD/Mj3Tb16ui9RAoiekfLWCXUkzd2xnbbq+l78kXnbHg5OqxoYkpUIddMI7XJlZbs6kKGRD3bDnrGt4FP+e55Fc=.RYKlzTDl2EcY70g6FXV06PhRqKfB4j0YtIuCKNj1N0I");
define("ACCURATE_SIGNATURE_SECRET", "6mJgVhxLeA0rwWht8cRZd3NbHDONE51oyrQ9WAXu12nmCRMGObpoi3xzNEfYFZa1");
define("ACCURATE_API_BASE_URL", "https://account.accurate.id");

function formatTimestamp() {
    date_default_timezone_set("Asia/Jakarta");
    return date("d/m/Y H:i:s");
}

function generateApiSignature($timestamp, $signature_secret) {
    return base64_encode(hash_hmac("sha256", $timestamp, $signature_secret, true));
}

function getAccurateHost() {
    try {
        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $base_url = ACCURATE_API_BASE_URL;
        
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);
        
        $url = $base_url . "/api/api-token.do";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $api_token",
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result["s"]) && $result["s"] == true) {
                if (isset($result["d"]["database"]["host"])) {
                    return $result["d"]["database"]["host"];
                } elseif (isset($result["d"]["data usaha"]["host"])) {
                    return $result["d"]["data usaha"]["host"];
                }
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}
?>';
    
    if (!is_dir('config')) {
        mkdir('config', 0755, true);
    }
    file_put_contents($config_file, $config_content);
    echo "   ✅ Basic config file created\n";
    include_once $config_file;
}
echo "\n";

// Step 2: Test API Call dengan detailed response
echo "2. Raw API Test...\n";
if (defined('ACCURATE_API_TOKEN') && defined('ACCURATE_SIGNATURE_SECRET')) {
    $api_token = ACCURATE_API_TOKEN;
    $signature_secret = ACCURATE_SIGNATURE_SECRET;
    $base_url = ACCURATE_API_BASE_URL;
    
    echo "   🔑 Using API Token: " . substr($api_token, 0, 20) . "...\n";
    echo "   🔐 Using Signature Secret: " . substr($signature_secret, 0, 20) . "...\n";
    
    // Generate timestamp and signature
    date_default_timezone_set('Asia/Jakarta');
    $timestamp = date('d/m/Y H:i:s');
    $signature = base64_encode(hash_hmac('sha256', $timestamp, $signature_secret, true));
    
    echo "   📅 Timestamp: $timestamp\n";
    echo "   🔏 Signature: " . substr($signature, 0, 20) . "...\n";
    
    $url = $base_url . '/api/api-token.do';
    echo "   🌐 URL: $url\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_token",
        "X-Api-Timestamp: $timestamp",
        "X-Api-Signature: $signature",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_VERBOSE, false);
    
    echo "   📡 Making API call...\n";
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    echo "   📊 HTTP Code: $http_code\n";
    if ($curl_error) {
        echo "   ❌ cURL Error: $curl_error\n";
    }
    
    echo "   📥 Response Length: " . strlen($response) . " bytes\n";
    echo "   📄 Raw Response (first 500 chars):\n";
    echo "   " . substr($response, 0, 500) . "\n";
    
    if ($http_code == 200) {
        $result = json_decode($response, true);
        if ($result !== null) {
            echo "   ✅ JSON is valid\n";
            echo "   📋 Response Structure:\n";
            echo "   " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
            
            // Analyze structure for host
            echo "\n   🔍 Looking for host in response...\n";
            if (isset($result['s'])) {
                echo "   - Success flag: " . ($result['s'] ? 'true' : 'false') . "\n";
            }
            
            if (isset($result['d'])) {
                echo "   - Data section exists\n";
                $data = $result['d'];
                
                if (is_array($data)) {
                    echo "   - Data keys: " . implode(', ', array_keys($data)) . "\n";
                    
                    // Check for host in various paths
                    $host_paths = [
                        ['database', 'host'],
                        ['data usaha', 'host'], 
                        ['host'],
                        ['company', 'host']
                    ];
                    
                    foreach ($host_paths as $path) {
                        $temp = $data;
                        $path_str = implode('.', $path);
                        $found = true;
                        
                        foreach ($path as $key) {
                            if (isset($temp[$key])) {
                                $temp = $temp[$key];
                            } else {
                                $found = false;
                                break;
                            }
                        }
                        
                        if ($found && is_string($temp)) {
                            echo "   ✅ Found host at path '$path_str': $temp\n";
                        } else {
                            echo "   ❌ No host found at path '$path_str'\n";
                        }
                    }
                } else {
                    echo "   - Data is not an array, it's: " . gettype($data) . "\n";
                    echo "   - Data value: " . json_encode($data) . "\n";
                }
            } else {
                echo "   ❌ No data section in response\n";
            }
        } else {
            echo "   ❌ Response is not valid JSON\n";
            echo "   📄 Raw response:\n$response\n";
        }
    } else {
        echo "   ❌ HTTP Error: $http_code\n";
        echo "   📄 Response: $response\n";
    }
} else {
    echo "   ❌ API constants not defined\n";
}
echo "\n";

// Step 3: Test functions
echo "3. Testing Functions...\n";
$functions_to_test = ['formatTimestamp', 'generateApiSignature', 'getAccurateHost'];
foreach ($functions_to_test as $func) {
    if (function_exists($func)) {
        echo "   ✅ $func exists\n";
        
        if ($func == 'formatTimestamp') {
            $ts = formatTimestamp();
            echo "      Sample: $ts\n";
        } elseif ($func == 'getAccurateHost') {
            echo "      Testing getAccurateHost()...\n";
            $host = getAccurateHost();
            if ($host) {
                echo "      ✅ Got host: $host\n";
            } else {
                echo "      ❌ Failed to get host\n";
            }
        }
    } else {
        echo "   ❌ $func NOT exists\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "Next steps:\n";
echo "1. Check the response structure above\n";  
echo "2. Update config file if needed\n";
echo "3. Run: php test_accurate_config.php\n";
?>