<?php
session_start();
include "../config/koneksi.php";
include "../config/accurate_config.php";

// Script untuk test endpoint merek barang di Accurate Online
$log_file = 'accurate_endpoint_test.txt';

function formatTimestamp() {
    return date('d/m/Y H:i:s');
}

function generateApiSignature($timestamp, $secret) {
    return base64_encode(hash_hmac('sha256', $timestamp, $secret, true));
}

function getAccurateHost($log_file) {
    try {
        $api_token = ACCURATE_API_TOKEN;
        $signature_secret = ACCURATE_SIGNATURE_SECRET;
        $base_url = ACCURATE_API_BASE_URL;

        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, $signature_secret);

        $url = $base_url . '/api/api-token.do';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $api_token,
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
            if ($result && isset($result['s']) && $result['s'] == true) {
                $possible_paths = [
                    ['d', 'database', 'host'],
                    ['d', 'data usaha', 'host'],
                    ['d', 'host'],
                    ['host'],
                    ['d', 'dataUsaha', 'host']
                ];

                foreach ($possible_paths as $path) {
                    $temp = $result;
                    foreach ($path as $key) {
                        if (isset($temp[$key])) {
                            $temp = $temp[$key];
                        } else {
                            $temp = null;
                            break;
                        }
                    }
                    if ($temp && is_string($temp) && filter_var($temp, FILTER_VALIDATE_URL)) {
                        return $temp;
                    }
                }
            }
        }
        return false;
    } catch (Exception $e) {
        return false;
    }
}

function testEndpoint($host, $endpoint, $method = 'GET', $data = null) {
    $api_token = ACCURATE_API_TOKEN;
    $signature_secret = ACCURATE_SIGNATURE_SECRET;
    $timestamp = formatTimestamp();
    $signature = generateApiSignature($timestamp, $signature_secret);

    $url = $host . $endpoint;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $api_token",
        "X-Api-Timestamp: $timestamp",
        "X-Api-Signature: $signature",
        "Content-Type: application/x-www-form-urlencoded",
        "Accept: application/json"
    ]);
    
    if ($method == 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    return [
        'http_code' => $http_code,
        'response' => $response,
        'curl_error' => $curl_error,
        'url' => $url
    ];
}

// Mulai testing
file_put_contents($log_file, "\n======= ACCURATE BRAND ENDPOINT TEST =======\n", FILE_APPEND);
file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - Starting endpoint discovery...\n", FILE_APPEND);

echo "<h1>🔍 Test Endpoint Merek Barang Accurate Online</h1>";

$host = getAccurateHost($log_file);
if (!$host) {
    echo "<div style='color: red;'>❌ <strong>Gagal mendapatkan host Accurate!</strong><br>";
    echo "Periksa konfigurasi API Token Anda.</div>";
    exit;
}

echo "<div style='color: green;'>✅ <strong>Host Accurate berhasil didapat:</strong> $host</div><br>";

// Daftar endpoint yang akan ditest
$endpoints_to_test = [
    // Endpoint untuk LIST (mengetahui struktur data)
    '/accurate/api/item-brand/list.do' => 'GET',
    '/accurate/api/item/brand/list.do' => 'GET',
    '/accurate/api/brand/list.do' => 'GET',
    '/accurate/api/item-brand/detail.do' => 'GET',
    '/accurate/api/item/brand/detail.do' => 'GET',
    '/accurate/api/brand/detail.do' => 'GET',
    
    // Endpoint untuk SAVE
    '/accurate/api/item-brand/save.do' => 'POST',
    '/accurate/api/item/brand/save.do' => 'POST',
    '/accurate/api/brand/save.do' => 'POST',
    '/accurate/api/item-brand/bulk-save.do' => 'POST',
    '/accurate/api/item/brand/bulk-save.do' => 'POST'
];

echo "<h2>📋 Hasil Test Endpoint:</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Endpoint</th><th>Method</th><th>HTTP Code</th><th>Status</th><th>Response Preview</th>";
echo "</tr>";

$working_endpoints = [];

foreach ($endpoints_to_test as $endpoint => $method) {
    $test_data = null;
    
    // Untuk endpoint POST, siapkan data test
    if ($method == 'POST') {
        if (strpos($endpoint, 'bulk-save') !== false) {
            $test_data = [
                'data[0].name' => 'TEST_BRAND_YAMAHA',
                'data[0].notes' => 'Test brand untuk discovery',
                'data[0].suspended' => 'true' // Suspended agar tidak mengganggu data asli
            ];
        } else {
            $test_data = [
                'name' => 'TEST_BRAND_YAMAHA',
                'notes' => 'Test brand untuk discovery',
                'suspended' => 'true'
            ];
        }
    }
    
    $result = testEndpoint($host, $endpoint, $method, $test_data);
    
    $status = '';
    $bg_color = '';
    
    if ($result['http_code'] == 200) {
        $status = '✅ TERSEDIA';
        $bg_color = 'background-color: #d4edda;';
        $working_endpoints[] = $endpoint;
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ✅ WORKING: $endpoint ($method) - HTTP 200\n", FILE_APPEND);
    } elseif ($result['http_code'] == 404) {
        $status = '❌ TIDAK ADA';
        $bg_color = 'background-color: #f8d7da;';
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ❌ NOT FOUND: $endpoint ($method) - HTTP 404\n", FILE_APPEND);
    } elseif ($result['http_code'] == 401 || $result['http_code'] == 403) {
        $status = '🔐 BUTUH PERMISSION';
        $bg_color = 'background-color: #fff3cd;';
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - 🔐 PERMISSION: $endpoint ($method) - HTTP " . $result['http_code'] . "\n", FILE_APPEND);
    } else {
        $status = "⚠️ HTTP " . $result['http_code'];
        $bg_color = 'background-color: #e2e3e5;';
        file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - ⚠️ OTHER: $endpoint ($method) - HTTP " . $result['http_code'] . "\n", FILE_APPEND);
    }
    
    $response_preview = htmlspecialchars(substr($result['response'], 0, 100));
    if (strlen($result['response']) > 100) {
        $response_preview .= '...';
    }
    
    echo "<tr style='$bg_color'>";
    echo "<td><code>$endpoint</code></td>";
    echo "<td><strong>$method</strong></td>";
    echo "<td><strong>" . $result['http_code'] . "</strong></td>";
    echo "<td>$status</td>";
    echo "<td><small>$response_preview</small></td>";
    echo "</tr>";
}

echo "</table>";

// Tampilkan rekomendasi
echo "<h2>🎯 Rekomendasi:</h2>";

if (!empty($working_endpoints)) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; background-color: #d4edda;'>";
    echo "<strong>✅ Endpoint yang dapat digunakan:</strong><br>";
    foreach ($working_endpoints as $endpoint) {
        echo "• <code>$endpoint</code><br>";
    }
    echo "</div>";
    
    // Update kode save_barang_pabrik.php dengan endpoint yang bekerja
    echo "<h3>📝 Update Kode PHP:</h3>";
    echo "<div style='background-color: #f8f9fa; padding: 10px; border: 1px solid #dee2e6;'>";
    echo "<pre><code>";
    echo "// Ganti endpoint di function sendBrandToAccurate() dengan:\n";
    echo "\$endpoints = [\n";
    foreach ($working_endpoints as $endpoint) {
        echo "    '$endpoint',\n";
    }
    echo "];\n";
    echo "</code></pre>";
    echo "</div>";
} else {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; background-color: #f8d7da;'>";
    echo "<strong>❌ Tidak ada endpoint merek barang yang tersedia!</strong><br>";
    echo "Kemungkinan:<br>";
    echo "• API Token tidak memiliki permission untuk merek barang<br>";
    echo "• Fitur merek barang tidak tersedia di versi Accurate Anda<br>";
    echo "• Endpoint menggunakan nama yang berbeda<br><br>";
    echo "<strong>Solusi:</strong><br>";
    echo "1. Hubungi admin Accurate untuk menambah permission<br>";
    echo "2. Buat merek secara manual di: Accurate Online > Master Data > Item > Merek<br>";
    echo "3. Gunakan parameter <code>itemCategoryName</code> pada saat membuat item<br>";
    echo "</div>";
}

// Tampilkan informasi tambahan
echo "<h2>📖 Cara Manual Membuat Merek di Accurate:</h2>";
echo "<ol>";
echo "<li>Login ke Accurate Online</li>";
echo "<li>Buka menu <strong>Master Data</strong> → <strong>Item</strong> → <strong>Merek</strong></li>";
echo "<li>Klik <strong>Tambah</strong></li>";
echo "<li>Isi nama merek (contoh: YAMAHA, HONDA, SUZUKI)</li>";
echo "<li>Klik <strong>Simpan</strong></li>";
echo "</ol>";

echo "<h2>📋 Log File:</h2>";
echo "<p>Detail lengkap tersimpan di: <code>$log_file</code></p>";

file_put_contents($log_file, date('Y-m-d H:i:s') . " WIB - Test completed. Working endpoints: " . count($working_endpoints) . "\n", FILE_APPEND);
?>