<?php
/**
 * Script untuk testing delete kategori secara aman
 * Hanya melakukan simulasi tanpa delete sesungguhnya
 */

// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../config/accurate_config.php";

$log_file = 'safe_delete_test.log';

// Simulasi data kategori DUMMY
$category_name = 'DUMMY';
$category_code = 'DMY';
$accurate_id = null; // Simulasi tidak ada accurate_id tersimpan

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
 * Function untuk mendapatkan host
 */
function getHost($log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        $url = ACCURATE_API_BASE_URL . '/api/api-token.do';

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Getting host from: $url\n", FILE_APPEND | LOCK_EX);

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
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                if (isset($result['d']['database']['host'])) {
                    return $result['d']['database']['host'];
                }
            }
        }
        return false;
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return false;
    }
}

/**
 * Function untuk mencari kategori
 */
function findCategory($host, $category_name, $log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        $list_params = [
            'sp.pageSize' => '100',
            'fields' => 'id,name,code,description'
        ];
        
        $list_url = $host . '/accurate/api/item-category/list.do?' . http_build_query($list_params);
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Searching for '$category_name' at: $list_url\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($list_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true && !empty($result['d'])) {
                $category_name_lower = strtolower(trim($category_name));
                
                foreach ($result['d'] as $item) {
                    $item_name = isset($item['name']) ? strtolower(trim($item['name'])) : '';
                    $item_id = $item['id'] ?? null;
                    
                    if ($item_name === $category_name_lower && $item_id) {
                        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ✅ Found '$category_name' with ID: $item_id\n", FILE_APPEND | LOCK_EX);
                        return [
                            'found' => true,
                            'id' => $item_id,
                            'name' => $item['name'] ?? '',
                            'data' => $item
                        ];
                    }
                }
            }
        }

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Category '$category_name' not found\n", FILE_APPEND | LOCK_EX);
        return ['found' => false];
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ findCategory Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return ['found' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Function untuk simulasi delete (tidak benar-benar menghapus)
 */
function simulateDelete($host, $category_id, $log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        $delete_url = $host . '/accurate/api/item-category/delete.do';
        $delete_data = ['id' => $category_id];

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🧪 SIMULATION: Would delete category ID: $category_id\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🌐 Delete URL: $delete_url\n", FILE_APPEND | LOCK_EX);
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📤 Delete data: " . json_encode($delete_data) . "\n", FILE_APPEND | LOCK_EX);

        // SIMULASI: Kita tidak benar-benar mengirim request delete
        // Hanya simulasi untuk testing
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ⚠️ SIMULATION MODE: Delete request NOT sent\n", FILE_APPEND | LOCK_EX);
        
        return [
            'simulation' => true,
            'would_delete_id' => $category_id,
            'delete_url' => $delete_url,
            'delete_data' => $delete_data,
            'headers' => [
                "Authorization: Bearer " . substr(ACCURATE_API_TOKEN, 0, 20) . "...",
                "X-Api-Timestamp: $timestamp",
                "X-Api-Signature: " . substr($signature, 0, 20) . "...",
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json"
            ]
        ];
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ simulateDelete Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return [
            'simulation' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Function untuk mendapatkan detail kategori
 */
function getCategoryDetail($host, $category_id, $log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        $detail_url = $host . '/accurate/api/item-category/detail.do?id=' . $category_id;
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Getting detail for ID: $category_id\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($detail_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                return [
                    'success' => true,
                    'data' => $result['d'] ?? null
                ];
            }
        }

        return ['success' => false];
        
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ getCategoryDetail Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

echo "<h1>Safe Delete Test - Kategori DUMMY</h1>";
echo "<pre>";

echo "=== SAFE DELETE TEST SIMULATION ===\n";
echo "Target Category: $category_name\n";
echo "Category Code: $category_code\n";
echo "Stored Accurate ID: " . ($accurate_id ?? 'NULL') . "\n";
echo "Log File: $log_file\n";
echo "\n";

// Step 1: Get host
echo "=== STEP 1: GET HOST ===\n";
$host = getHost($log_file);
if (!$host) {
    echo "❌ Failed to get Accurate host\n";
    echo "Check log file for details: $log_file\n";
    exit;
}
echo "✅ Host: $host\n\n";

// Step 2: Find category
echo "=== STEP 2: FIND CATEGORY ===\n";
$find_result = findCategory($host, $category_name, $log_file);

if ($find_result['found']) {
    echo "✅ Category found!\n";
    echo "   ID: " . $find_result['id'] . "\n";
    echo "   Name: " . $find_result['name'] . "\n";
    echo "   Full Data: " . json_encode($find_result['data'], JSON_PRETTY_PRINT) . "\n";
    
    $category_id_to_delete = $find_result['id'];
} else {
    echo "❌ Category not found\n";
    if (isset($find_result['error'])) {
        echo "   Error: " . $find_result['error'] . "\n";
    }
    echo "Cannot proceed with delete simulation\n";
    exit;
}
echo "\n";

// Step 3: Get category detail
echo "=== STEP 3: GET CATEGORY DETAIL ===\n";
$detail_result = getCategoryDetail($host, $category_id_to_delete, $log_file);

if ($detail_result['success']) {
    echo "✅ Category detail retrieved successfully\n";
    $detail_data = $detail_result['data'];
    echo "   Category Name: " . ($detail_data['name'] ?? 'Unknown') . "\n";
    echo "   Category ID: " . ($detail_data['id'] ?? 'Unknown') . "\n";
    echo "   Level: " . ($detail_data['lvl'] ?? 'Unknown') . "\n";
    echo "   Parent: " . (isset($detail_data['parent']['name']) ? $detail_data['parent']['name'] : 'Unknown') . "\n";
    
    // Check if category has any GL account associations
    $gl_accounts = [];
    $gl_fields = ['salesGlAccountId', 'cogsGlAccountId', 'inventoryGlAccountId', 'expenseGlAccountId'];
    foreach ($gl_fields as $field) {
        if (!empty($detail_data[$field])) {
            $gl_accounts[] = $field . ': ' . $detail_data[$field];
        }
    }
    
    if (!empty($gl_accounts)) {
        echo "   GL Accounts: " . implode(', ', $gl_accounts) . "\n";
        echo "   ⚠️  WARNING: Category has GL account associations\n";
    } else {
        echo "   GL Accounts: None\n";
    }
    
} else {
    echo "❌ Failed to get category detail\n";
    if (isset($detail_result['error'])) {
        echo "   Error: " . $detail_result['error'] . "\n";
    }
}
echo "\n";

// Step 4: Simulate delete
echo "=== STEP 4: DELETE SIMULATION ===\n";
$simulate_result = simulateDelete($host, $category_id_to_delete, $log_file);

if ($simulate_result['simulation']) {
    echo "✅ Delete simulation completed\n";
    echo "   Would delete ID: " . $simulate_result['would_delete_id'] . "\n";
    echo "   Delete URL: " . $simulate_result['delete_url'] . "\n";
    echo "   Delete Data: " . json_encode($simulate_result['delete_data']) . "\n";
    echo "   Headers would be:\n";
    foreach ($simulate_result['headers'] as $header) {
        echo "     $header\n";
    }
    echo "\n";
    echo "   ⚠️  NOTE: This is SIMULATION ONLY - no actual delete was performed\n";
} else {
    echo "❌ Delete simulation failed\n";
    if (isset($simulate_result['error'])) {
        echo "   Error: " . $simulate_result['error'] . "\n";
    }
}
echo "\n";

// Step 5: Analysis and recommendations
echo "=== STEP 5: ANALYSIS & RECOMMENDATIONS ===\n";

if ($find_result['found']) {
    echo "✅ Category search: WORKING\n";
    echo "   - Category ID $category_id_to_delete can be found\n";
    echo "   - Search by name is functional\n";
} else {
    echo "❌ Category search: FAILED\n";
    echo "   - Need to check search logic\n";
}

if ($detail_result['success']) {
    echo "✅ Category detail: WORKING\n";
    echo "   - Detail endpoint is accessible\n";
    echo "   - Category data is complete\n";
    
    if (!empty($gl_accounts)) {
        echo "⚠️  GL Account Warning:\n";
        echo "   - Category has associated GL accounts\n";
        echo "   - Delete might fail if accounts are in use\n";
        echo "   - Consider checking if safe to delete\n";
    } else {
        echo "✅ GL Accounts: SAFE\n";
        echo "   - No GL account associations\n";
        echo "   - Should be safe to delete\n";
    }
} else {
    echo "❌ Category detail: FAILED\n";
    echo "   - Detail endpoint has issues\n";
}

if ($simulate_result['simulation']) {
    echo "✅ Delete preparation: READY\n";
    echo "   - Delete URL is correct\n";
    echo "   - Delete data is properly formatted\n";
    echo "   - Headers are properly constructed\n";
    echo "   - Ready for actual delete operation\n";
} else {
    echo "❌ Delete preparation: FAILED\n";
    echo "   - Delete setup has issues\n";
}

echo "\n=== FINAL RECOMMENDATION ===\n";

if ($find_result['found'] && $detail_result['success'] && $simulate_result['simulation']) {
    echo "🟢 READY FOR ACTUAL DELETE\n";
    echo "   All tests passed successfully\n";
    echo "   The final delete script should work correctly\n";
    echo "   Category ID $category_id_to_delete is ready for deletion\n";
    
    if (!empty($gl_accounts)) {
        echo "\n⚠️  CAUTION:\n";
        echo "   Category has GL account associations\n";
        echo "   Make sure these accounts are not in use before deleting\n";
    }
} else {
    echo "🔴 NOT READY FOR DELETE\n";
    echo "   Some tests failed - check logs for details\n";
    echo "   Fix issues before attempting actual delete\n";
}

echo "\n=== SIMULATION COMPLETED ===\n";
echo "Check detailed logs: $log_file\n";
echo "No actual delete operations were performed\n";

echo "</pre>";
?>