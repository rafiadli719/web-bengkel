<?php
session_start();

echo "<h2>🔍 Debug Accurate Session</h2>";
echo "<style>body{font-family:Arial;} .success{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;}</style>";

// Tampilkan semua session variables
echo "<h3>📋 Session Variables:</h3>";
echo "<pre>";
foreach ($_SESSION as $key => $value) {
    if (strpos($key, 'accurate') !== false || 
        in_array($key, ['access_token', 'session', 'host', 'refresh_token'])) {
        echo "$key = " . (is_string($value) ? $value : print_r($value, true)) . "\n";
    }
}
echo "</pre>";

// Ambil session variables
$access_token = $_SESSION['access_token'] ?? '';
$session = $_SESSION['session'] ?? '';
$host = $_SESSION['host'] ?? '';

echo "<h3>🔧 Configuration Check:</h3>";
echo "<p><strong>Host:</strong> " . ($host ?: '<span class="error">MISSING</span>') . "</p>";
echo "<p><strong>Access Token:</strong> " . ($access_token ? '<span class="success">SET</span> (' . substr($access_token, 0, 20) . '...)' : '<span class="error">MISSING</span>') . "</p>";
echo "<p><strong>Session:</strong> " . ($session ? '<span class="success">SET</span> (' . substr($session, 0, 20) . '...)' : '<span class="error">MISSING</span>') . "</p>";

if (empty($host) || empty($access_token) || empty($session)) {
    echo "<p class='error'><strong>❌ ERROR:</strong> Konfigurasi Accurate tidak lengkap!</p>";
    echo "<p><strong>💡 Solusi:</strong></p>";
    echo "<ul>";
    echo "<li>Login ulang ke dashboard Accurate</li>";
    echo "<li>Pastikan session variables tersimpan dengan benar</li>";
    echo "<li>Cek kode yang menyimpan session setelah login</li>";
    echo "</ul>";
    exit;
}

// Test berbagai endpoint
$test_endpoints = [
    'Company List' => '/api/open-api/company/list.do',
    'Item List' => '/api/item/list.do', 
    'Item Adjustment' => '/api/item-adjustment/save.do'
];

foreach ($test_endpoints as $name => $endpoint) {
    echo "<h3>🧪 Test $name</h3>";
    
    $test_url = $host . $endpoint;
    echo "<p><strong>URL:</strong> $test_url</p>";
    
    $ch = curl_init($test_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($endpoint == '/api/item-adjustment/save.do') {
        // POST request for item adjustment
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'test=1');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
            "X-Session-ID: $session",
            "Content-Type: application/x-www-form-urlencoded"
        ]);
    } else {
        // GET request for others
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $access_token",
            "X-Session-ID: $session",
            "Content-Type: application/json"
        ]);
    }
    
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $redirect_count = curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    echo "<p><strong>HTTP Code:</strong> $http_code</p>";
    echo "<p><strong>Response Time:</strong> {$response_time}ms</p>";
    echo "<p><strong>Effective URL:</strong> $effective_url</p>";
    echo "<p><strong>Redirect Count:</strong> $redirect_count</p>";
    
    if (curl_errno($ch)) {
        echo "<p class='error'><strong>❌ cURL Error:</strong> " . curl_error($ch) . "</p>";
    } else {
        // Analyze response
        if ($http_code == 200) {
            echo "<p class='success'><strong>✅ HTTP 200 OK</strong></p>";
            
            // Check if JSON
            $json_data = json_decode($response, true);
            if ($json_data !== null) {
                echo "<p><strong>📄 Response Type:</strong> JSON</p>";
                if (isset($json_data['s'])) {
                    echo "<p><strong>Success Flag:</strong> " . ($json_data['s'] ? '<span class="success">true</span>' : '<span class="error">false</span>') . "</p>";
                }
                if (isset($json_data['d'])) {
                    echo "<p><strong>Data/Error:</strong> " . (is_array($json_data['d']) ? implode(', ', $json_data['d']) : $json_data['d']) . "</p>";
                }
                echo "<pre>" . htmlspecialchars(json_encode($json_data, JSON_PRETTY_PRINT)) . "</pre>";
            } 
            // Check if XML
            elseif (stripos($response, '<?xml') !== false) {
                echo "<p><strong>📄 Response Type:</strong> XML</p>";
                $xml = simplexml_load_string($response);
                if ($xml !== false) {
                    echo "<pre>" . htmlspecialchars($response) . "</pre>";
                } else {
                    echo "<p class='error'>❌ Invalid XML</p>";
                    echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
                }
            }
            // Check if HTML
            elseif (stripos($response, '<html>') !== false || stripos($response, '<!DOCTYPE html>') !== false) {
                echo "<p class='warning'><strong>⚠️ Response Type:</strong> HTML (Possible login page)</p>";
                if (stripos($response, 'login') !== false || stripos($response, 'masuk') !== false) {
                    echo "<p class='error'><strong>❌ This is a login page - Session expired!</strong></p>";
                }
                echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "...</pre>";
            }
            // Plain text or other
            else {
                echo "<p><strong>📄 Response Type:</strong> Plain text/Other</p>";
                echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
            }
            
        } elseif ($http_code == 302) {
            echo "<p class='warning'><strong>⚠️ HTTP 302 Redirect</strong></p>";
            echo "<p>Kemungkinan diarahkan ke halaman login</p>";
        } elseif ($http_code == 401) {
            echo "<p class='error'><strong>❌ HTTP 401 Unauthorized</strong></p>";
            echo "<p>Access token tidak valid atau expired</p>";
        } elseif ($http_code == 403) {
            echo "<p class='error'><strong>❌ HTTP 403 Forbidden</strong></p>";
            echo "<p>Tidak memiliki akses ke endpoint ini</p>";
        } else {
            echo "<p class='error'><strong>❌ HTTP $http_code</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
    }
    
    curl_close($ch);
    echo "<hr>";
}

// Test actual item adjustment request
echo "<h3>🎯 Test Actual Item Adjustment Request</h3>";

$test_data = [
    'transDate' => date('d/m/Y'),
    'description' => 'Test from debug script',
    'branchName' => 'Kantor Pusat',
    'adjustmentAccountNo' => '110401',
    'detailItem[0].itemNo' => '10009', // Item dari log Anda
    'detailItem[0].itemAdjustmentType' => 'ADJUSTMENT_IN',
    'detailItem[0].unitCost' => '500000.000000',
    'detailItem[0].quantity' => '1.000000',
    'detailItem[0].warehouseName' => 'Utama',
];

echo "<p><strong>📤 Request Data:</strong></p>";
echo "<pre>" . http_build_query($test_data) . "</pre>";

$url = $host . "/api/item-adjustment/save.do";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "X-Session-ID: $session",
    "Content-Type: application/x-www-form-urlencoded"
]);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

echo "<p><strong>Response HTTP Code:</strong> $http_code</p>";
echo "<p><strong>Effective URL:</strong> $effective_url</p>";

if (curl_errno($ch)) {
    echo "<p class='error'><strong>❌ cURL Error:</strong> " . curl_error($ch) . "</p>";
} else {
    echo "<p><strong>📥 Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Analyze this specific response
    if (strpos($effective_url, 'account.accurate.id') !== false) {
        echo "<p class='error'><strong>❌ REDIRECTED TO LOGIN!</strong> Session definitely expired.</p>";
    } elseif (stripos($response, '<html>') !== false && stripos($response, 'login') !== false) {
        echo "<p class='error'><strong>❌ LOGIN PAGE DETECTED!</strong> Session expired.</p>";
    } else {
        $json = json_decode($response, true);
        if ($json !== null) {
            if (isset($json['s']) && $json['s']) {
                echo "<p class='success'><strong>✅ SUCCESS!</strong> Session is working!</p>";
            } elseif (isset($json['s']) && !$json['s']) {
                echo "<p class='error'><strong>❌ API ERROR:</strong> " . (isset($json['d']) ? implode(', ', (array)$json['d']) : 'Unknown error') . "</p>";
            }
        }
    }
}

curl_close($ch);

echo "<h3>💡 Recommendation</h3>";
echo "<p>Berdasarkan hasil test di atas:</p>";
echo "<ul>";
echo "<li>Jika semua test menunjukkan <strong>redirect ke login</strong> atau <strong>HTML login page</strong>, maka session memang sudah expired</li>";
echo "<li>Jika ada test yang berhasil (HTTP 200 dengan JSON response), maka session masih valid</li>";
echo "<li>Jika Item Adjustment test berhasil, maka kode PHP Anda seharusnya bisa bekerja</li>";
echo "</ul>";

?>

<script>
// Auto refresh setiap 30 detik untuk monitoring
setTimeout(function(){
    window.location.reload();
}, 30000);
</script>