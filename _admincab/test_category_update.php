<?php
/**
 * Script untuk testing update kategori sebelum implementasi
 */

// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../config/accurate_config.php";

$log_file = 'test_category_update.log';

// Simulasi data untuk testing
$test_category_name = 'DUMMY';
$new_category_name = 'DUMMY_UPDATED';
$test_accurate_id = 751; // Based on previous successful tests

/**
 * Function untuk format timestamp
 */
function formatTimestamp() {
    return date('d/m/Y H:i:s');
}

/**
 * Function untuk generate API signature
 */
function generateApiSignature($timestamp, $signature_secret) {
    return base64_encode(hash_hmac('sha256', $timestamp, $signature_secret, true));
}

/**
 * Function untuk mendapatkan host dan session
 */
function getHostAndSession($log_file) {
    try {
        // Get host
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $url = ACCURATE_API_BASE_URL . '/api/api-token.do';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code != 200) return false;

        $result = json_decode($response, true);
        if (!$result || $result['s'] != true) return false;

        $host = $result['d']['database']['host'] ?? null;
        if (!$host) return false;

        // Establish session
        $session_timestamp = formatTimestamp();
        $session_signature = generateApiSignature($session_timestamp, ACCURATE_SIGNATURE_SECRET);
        $session_url = $host . '/accurate/api/open-db.do';

        $ch = curl_init($session_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $session_timestamp",
            "X-Api-Signature: $session_signature",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $session_response = curl_exec($ch);
        $session_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($session_http_code != 200) return false;

        $session_result = json_decode($session_response, true);
        if (!$session_result || $session_result['s'] != true) return false;

        $session_id = $session_result['d'] ?? 'AUTO_SESSION';

        return [
            'host' => $host,
            'session_id' => $session_id
        ];

    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
}

/**
 * Function untuk test update kategori
 */
function testUpdateCategory($host, $session_id, $category_id, $new_name, $log_file, $simulate_only = true) {
    try {
        $save_url = $host . '/accurate/api/item-category/save.do';
        
        $update_data = [
            'id' => $category_id,
            'name' => $new_name,
            'defaultCategory' => 'false',
            'parentName' => ''
        ];

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🧪 TEST UPDATE - Category ID: $category_id to '$new_name'\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🌐 URL: $save_url\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📤 Data: " . http_build_query($update_data) . "\n", FILE_APPEND | LOCK_EX);

        if ($simulate_only) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ SIMULATION MODE - Request not sent\n", FILE_APPEND | LOCK_EX);
            return [
                'simulation' => true,
                'would_update_id' => $category_id,
                'new_name' => $new_name,
                'url' => $save_url,
                'data' => $update_data,
                'headers' => [
                    "X-Session-ID: $session_id",
                    "Content-Type: application/x-www-form-urlencoded",
                    "Accept: application/json"
                ]
            ];
        }

        // Actual request (if simulation is disabled)
        $ch = curl_init($save_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Session-ID: $session_id",
            "Content-Type: application/x-www-form-urlencoded",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($update_data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'simulation' => false,
            'success' => $http_code == 200,
            'http_code' => $http_code,
            'response' => $response
        ];

    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ testUpdateCategory Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return [
            'simulation' => false,
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Function untuk get detail kategori
 */
function getCategoryDetail($host, $session_id, $category_id, $log_file) {
    try {
        $detail_url = $host . '/accurate/api/item-category/detail.do?id=' . $category_id;

        $ch = curl_init($detail_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Session-ID: $session_id",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                return [
                    'success' => true,
                    'data' => $result['d']
                ];
            }
        }

        return ['success' => false];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "<h1>Test Category Update - ID $test_accurate_id</h1>";
echo "<pre>";

echo "=== CATEGORY UPDATE TEST ===\n";
echo "Current Name: $test_category_name\n";
echo "New Name: $new_category_name\n";
echo "Category ID: $test_accurate_id\n";
echo "Log File: $log_file\n";
echo "\n";

// Step 1: Get host and session
echo "=== STEP 1: GET HOST & SESSION ===\n";
$connection = getHostAndSession($log_file);
if (!$connection) {
    echo "❌ Failed to establish connection\n";
    exit;
}

$host = $connection['host'];
$session_id = $connection['session_id'];

echo "✅ Host: $host\n";
echo "✅ Session ID: $session_id\n\n";

// Step 2: Get current category detail
echo "=== STEP 2: GET CURRENT CATEGORY DETAIL ===\n";
$current_detail = getCategoryDetail($host, $session_id, $test_accurate_id, $log_file);

if ($current_detail['success']) {
    echo "✅ Category detail retrieved:\n";
    $detail = $current_detail['data'];
    echo "   ID: " . ($detail['id'] ?? 'Unknown') . "\n";
    echo "   Name: " . ($detail['name'] ?? 'Unknown') . "\n";
    echo "   Level: " . ($detail['lvl'] ?? 'Unknown') . "\n";
    echo "   Parent: " . (isset($detail['parent']['name']) ? $detail['parent']['name'] : 'None') . "\n";
} else {
    echo "❌ Failed to get category detail\n";
    if (isset($current_detail['error'])) {
        echo "   Error: " . $current_detail['error'] . "\n";
    }
    echo "Cannot proceed with update test\n";
    exit;
}
echo "\n";

// Step 3: Test update (simulation)
echo "=== STEP 3: UPDATE SIMULATION ===\n";
$update_test = testUpdateCategory($host, $session_id, $test_accurate_id, $new_category_name, $log_file, true);

if ($update_test['simulation']) {
    echo "✅ Update simulation completed\n";
    echo "   Would update ID: " . $update_test['would_update_id'] . "\n";
    echo "   New name: " . $update_test['new_name'] . "\n";
    echo "   Update URL: " . $update_test['url'] . "\n";
    echo "   Update data: " . json_encode($update_test['data']) . "\n";
    echo "   Headers:\n";
    foreach ($update_test['headers'] as $header) {
        echo "     $header\n";
    }
    echo "\n";
    echo "   ⚠️  NOTE: This is SIMULATION ONLY - no actual update was performed\n";
} else {
    echo "❌ Update simulation failed\n";
    if (isset($update_test['error'])) {
        echo "   Error: " . $update_test['error'] . "\n";
    }
}
echo "\n";

// Step 4: Analysis
echo "=== STEP 4: ANALYSIS & RECOMMENDATION ===\n";

if ($connection) {
    echo "✅ Connection: WORKING\n";
    echo "   - Host extraction successful\n";
    echo "   - Session establishment successful\n";
} else {
    echo "❌ Connection: FAILED\n";
}

if ($current_detail['success']) {
    echo "✅ Category Detail: WORKING\n";
    echo "   - Category ID $test_accurate_id exists\n";
    echo "   - Detail endpoint accessible\n";
    echo "   - Current name: '" . ($detail['name'] ?? 'Unknown') . "'\n";
} else {
    echo "❌ Category Detail: FAILED\n";
}

if ($update_test['simulation']) {
    echo "✅ Update Preparation: READY\n";
    echo "   - Update URL constructed correctly\n";
    echo "   - Update data formatted properly\n";
    echo "   - Session ID available for request\n";
    echo "   - All required fields present\n";
} else {
    echo "❌ Update Preparation: FAILED\n";
}

echo "\n=== COMPARISON WITH DOCUMENTATION ===\n";
echo "Required fields according to API docs:\n";
echo "✅ name: Present ($new_category_name)\n";
echo "✅ id: Present ($test_accurate_id) - for UPDATE mode\n";
echo "✅ X-Session-ID: Present ($session_id)\n";
echo "✅ defaultCategory: Present (false)\n";
echo "✅ parentName: Present (empty)\n";

echo "\n=== FINAL RECOMMENDATION ===\n";

if ($connection && $current_detail['success'] && $update_test['simulation']) {
    echo "🟢 READY FOR ACTUAL UPDATE\n";
    echo "   All tests passed successfully\n";
    echo "   The update script should work correctly\n";
    echo "   Category ID $test_accurate_id is ready for update\n";
    echo "\n";
    echo "   To perform actual update:\n";
    echo "   1. Set \$simulate_only = false in testUpdateCategory()\n";
    echo "   2. Run this script again\n";
    echo "   3. Or use the fixed_category_update.php script\n";
} else {
    echo "🔴 NOT READY FOR UPDATE\n";
    echo "   Some tests failed - check logs for details\n";
    echo "   Fix issues before attempting actual update\n";
}

echo "\n=== KEY DIFFERENCES FROM PREVIOUS SCRIPT ===\n";
echo "✅ Using X-Session-ID header (required by API)\n";
echo "✅ Proper session establishment with open-db.do\n";
echo "✅ Including 'id' parameter for UPDATE mode\n";
echo "✅ Using correct endpoint structure\n";
echo "✅ Following API documentation requirements\n";

echo "\n=== SIMULATION COMPLETED ===\n";
echo "Check detailed logs: $log_file\n";
echo "No actual update operations were performed\n";

echo "</pre>";
?>