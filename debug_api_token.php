<?php
session_start();
$config_paths = [
    '../config/config_accurate.php',
    './config/config_accurate.php', 
    '../../config/config_accurate.php',
    'config_accurate.php'
];

echo "<h2>🔑 Debug API Token Integration</h2>";
echo "<style>body{font-family:Arial;} .success{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border:1px solid #ddd;overflow-x:auto;}</style>";

// Check API Token configuration
echo "<h3>📋 API Token Configuration:</h3>";
if (defined('API_TOKEN') && !empty(API_TOKEN)) {
    echo "<p class='success'>✅ API_TOKEN is configured</p>";
    echo "<p><strong>Token (first 50 chars):</strong> " . substr(API_TOKEN, 0, 50) . "...</p>";
    echo "<p><strong>Token Length:</strong> " . strlen(API_TOKEN) . " characters</p>";
} else {
    echo "<p class='error'>❌ API_TOKEN is not configured or empty</p>";
    exit;
}

// Display other config values
echo "<p><strong>CLIENT_ID:</strong> " . (defined('CLIENT_ID') ? CLIENT_ID : 'Not set') . "</p>";
echo "<p><strong>CLIENT_SECRET:</strong> " . (defined('CLIENT_SECRET') ? substr(CLIENT_SECRET, 0, 10) . '...' : 'Not set') . "</p>";
echo "<p><strong>REDIRECT_URI:</strong> " . (defined('REDIRECT_URI') ? REDIRECT_URI : 'Not set') . "</p>";

// Test various API endpoints with the token
$api_token = API_TOKEN;

$test_endpoints = [
    'Company List (api.accurate.id)' => 'https://api.accurate.id/accurate/api/open-api/company/list.do',
    'Company List (secure.accurate.id)' => 'https://secure.accurate.id/accurate/api/open-api/company/list.do',
    'Item Adjustment (api.accurate.id)' => 'https://api.accurate.id/accurate/api/item-adjustment/save.do',
    'Item Adjustment (secure.accurate.id)' => 'https://secure.accurate.id/accurate/api/item-adjustment/save.do',
    'Item List (api.accurate.id)' => 'https://api.accurate.id/accurate/api/item/list.do',
];

foreach ($test_endpoints as $name => $url) {
    echo "<h3>🧪 Test: $name</h3>";
    echo "<p><strong>URL:</strong> $url</p>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_token",
        "Content-Type: application/json",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Accurate API Client/1.0');
    
    $start_time = microtime(true);
    $response = curl_exec($ch);
    $end_time = microtime(true);
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $response_time = round(($end_time - $start_time) * 1000, 2);
    
    echo "<p><strong>HTTP Code:</strong> $http_code</p>";
    echo "<p><strong>Response Time:</strong> {$response_time}ms</p>";
    echo "<p><strong>Effective URL:</strong> $effective_url</p>";
    
    if (curl_errno($ch)) {
        echo "<p class='error'><strong>❌ cURL Error:</strong> " . curl_error($ch) . "</p>";
    } else {
        if ($http_code == 200) {
            echo "<p class='success'><strong>✅ HTTP 200 OK</strong></p>";
            
            // Try to parse JSON
            $json_data = json_decode($response, true);
            if ($json_data !== null) {
                echo "<p><strong>📄 Response Type:</strong> JSON</p>";
                if (isset($json_data['s'])) {
                    echo "<p><strong>Success Flag:</strong> " . ($json_data['s'] ? '<span class="success">true</span>' : '<span class="error">false</span>') . "</p>";
                }
                if (isset($json_data['d'])) {
                    echo "<p><strong>Data/Message:</strong> " . (is_array($json_data['d']) ? json_encode($json_data['d']) : $json_data['d']) . "</p>";
                }
                echo "<pre>" . json_encode($json_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            } else {
                echo "<p><strong>📄 Response Type:</strong> Plain Text</p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        } elseif ($http_code == 401) {
            echo "<p class='error'><strong>❌ HTTP 401 Unauthorized</strong></p>";
            echo "<p>API token tidak valid atau tidak memiliki akses</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        } elseif ($http_code == 403) {
            echo "<p class='error'><strong>❌ HTTP 403 Forbidden</strong></p>";
            echo "<p>API token valid tapi tidak memiliki permission untuk endpoint ini</p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        } elseif ($http_code == 404) {
            echo "<p class='warning'><strong>⚠️ HTTP 404 Not Found</strong></p>";
            echo "<p>Endpoint tidak ditemukan</p>";
        } else {
            echo "<p class='error'><strong>❌ HTTP $http_code</strong></p>";
            echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";
        }
    }
    
    curl_close($ch);
    echo "<hr>";
}

// Test actual item adjustment with API token
echo "<h3>🎯 Test Item Adjustment with API Token</h3>";

$test_data = [
    'transDate' => date('d/m/Y'),
    'description' => 'Test from API debug script',
    'branchName' => 'Kantor Pusat',
    'adjustmentAccountNo' => '110401',
    'detailItem[0].itemNo' => '10009',
    'detailItem[0].itemAdjustmentType' => 'ADJUSTMENT_IN',
    'detailItem[0].unitCost' => '1000.000000',
    'detailItem[0].quantity' => '1.000000',
    'detailItem[0].warehouseName' => 'Utama',
];

echo "<p><strong>📤 Request Data:</strong></p>";
echo "<pre>" . http_build_query($test_data) . "</pre>";

// Try multiple URLs for item adjustment
$adjustment_urls = [
    'https://api.accurate.id/accurate/api/item-adjustment/save.do',
    'https://secure.accurate.id/accurate/api/item-adjustment/save.do'
];

foreach ($adjustment_urls as $url) {
    echo "<h4>Testing: $url</h4>";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($test_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_token",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p><strong>HTTP Code:</strong> $http_code</p>";
    
    if (curl_errno($ch)) {
        echo "<p class='error'><strong>❌ cURL Error:</strong> " . curl_error($ch) . "</p>";
    } else {
        echo "<p><strong>📥 Response:</strong></p>";
        
        if ($http_code == 200) {
            $json = json_decode($response, true);
            if ($json !== null) {
                echo "<pre>" . json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                if (isset($json['s'])) {
                    if ($json['s']) {
                        echo "<p class='success'><strong>🎉 SUCCESS!</strong> API Token bekerja dengan baik!</p>";
                    } else {
                        echo "<p class='error'><strong>❌ API Error:</strong> " . (isset($json['d']) ? json_encode($json['d']) : 'Unknown error') . "</p>";
                    }
                }
            } else {
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                if (stripos($response, 'success') !== false || strlen(trim($response)) < 100) {
                    echo "<p class='success'><strong>🎉 Possible SUCCESS!</strong> Response looks positive</p>";
                }
            }
        } else {
            echo "<pre>" . htmlspecialchars(substr($response, 0, 1000)) . "</pre>";
        }
    }
    
    curl_close($ch);
    echo "<br>";
}

echo "<h3>💡 Summary & Recommendations</h3>";
echo "<div style='background: #f0f8ff; padding: 15px; border-left: 5px solid #007cba;'>";
echo "<h4>Berdasarkan hasil test di atas:</h4>";
echo "<ul>";
echo "<li><strong>Jika ada endpoint yang return HTTP 200 dengan JSON success</strong> → API Token bekerja!</li>";
echo "<li><strong>Jika semua return HTTP 401/403</strong> → API Token expired atau tidak valid</li>";
echo "<li><strong>Jika ada yang return HTTP 404</strong> → Endpoint URL salah</li>";
echo "<li><strong>Jika Item Adjustment test berhasil</strong> → Kode PHP baru akan bekerja</li>";
echo "</ul>";

echo "<h4>Next Steps:</h4>";
echo "<ol>";
echo "<li>Jika API Token bekerja → Gunakan kode PHP yang sudah diperbaiki</li>";
echo "<li>Jika API Token tidak bekerja → Perlu refresh/regenerate token dari dashboard Accurate</li>";
echo "<li>Jika endpoint tidak ditemukan → Coba dokumentasi API Accurate terbaru</li>";
echo "</ol>";
echo "</div>";

// Tambahan: Cek token expiry dari JWT if possible
echo "<h3>🔍 Token Analysis</h3>";
$token_parts = explode('.', API_TOKEN);
if (count($token_parts) >= 3) {
    echo "<p>Token terlihat seperti JWT format (3 parts)</p>";
    try {
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1])), true);
        if ($payload && isset($payload['exp'])) {
            $expiry = date('Y-m-d H:i:s', $payload['exp']);
            echo "<p><strong>Token Expiry:</strong> $expiry</p>";
            if ($payload['exp'] < time()) {
                echo "<p class='error'>⚠️ Token sudah expired!</p>";
            } else {
                echo "<p class='success'>✅ Token masih valid</p>";
            }
        }
        if ($payload) {
            echo "<p><strong>Token Payload:</strong></p>";
            echo "<pre>" . json_encode($payload, JSON_PRETTY_PRINT) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>Cannot decode token payload</p>";
    }
} else {
    echo "<p>Token bukan format JWT standard</p>";
}
?>

<script>
// Auto refresh setiap 60 detik untuk monitoring
setTimeout(function(){
    if (confirm('Refresh halaman untuk test ulang?')) {
        window.location.reload();
    }
}, 60000);
</script>