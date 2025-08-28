<?php
/**
 * Script untuk mendapatkan detail kategori berdasarkan ID
 * File: get_category_detail.php
 */

// Aktifkan error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../config/accurate_config.php";

$log_file = 'category_detail_debug.log';

// ID kategori yang akan dicek (dari log sebelumnya: 700)
$category_id = 700;

/**
 * Function untuk mendapatkan detail kategori berdasarkan ID
 */
function getCategoryDetailById($host, $category_id, $log_file) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        // Endpoint untuk mendapatkan detail kategori
        $detail_url = $host . '/accurate/api/item-category/detail.do';
        
        // Parameter untuk detail
        $detail_params = [
            'id' => $category_id
        ];
        
        $full_url = $detail_url . '?' . http_build_query($detail_params);
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Getting category detail URL: $full_url\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $detail_response = curl_exec($ch);
        $detail_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $detail_curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📊 Get category detail HTTP Code: $detail_http_code\n", FILE_APPEND | LOCK_EX);

        if (!empty($detail_curl_error)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Get category detail cURL Error: $detail_curl_error\n", FILE_APPEND | LOCK_EX);
            return null;
        }

        if ($detail_http_code == 200) {
            $detail_result = json_decode($detail_response, true);
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📥 Detail response: " . json_encode($detail_result, JSON_PRETTY_PRINT) . "\n", FILE_APPEND | LOCK_EX);
            
            if ($detail_result && isset($detail_result['s']) && $detail_result['s'] == true) {
                return $detail_result['d'] ?? null;
            } else {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Detail request failed\n", FILE_APPEND | LOCK_EX);
                return null;
            }
        } else {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Get category detail HTTP Error: $detail_http_code\n", FILE_APPEND | LOCK_EX);
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] Response: " . $detail_response . "\n", FILE_APPEND | LOCK_EX);
            return null;
        }
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ getCategoryDetailById Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return null;
    }
}

/**
 * Function untuk mendapatkan semua kategori tanpa filter
 */
function getAllCategories($host, $log_file, $page_size = 50) {
    try {
        $timestamp = formatTimestamp();
        $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
        
        // Endpoint untuk list semua kategori
        $list_url = $host . '/accurate/api/item-category/list.do';
        
        // Parameter tanpa filter untuk mendapatkan semua kategori
        $list_params = [
            'sp.pageSize' => $page_size,
            'fields' => 'id,name,code,description'
        ];
        
        $full_url = $list_url . '?' . http_build_query($list_params);
        
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 🔍 Getting all categories URL: $full_url\n", FILE_APPEND | LOCK_EX);

        $ch = curl_init($full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . ACCURATE_API_TOKEN,
            "X-Api-Timestamp: $timestamp",
            "X-Api-Signature: $signature",
            "Accept: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');

        $list_response = curl_exec($ch);
        $list_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $list_curl_error = curl_error($ch);
        curl_close($ch);

        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📊 Get all categories HTTP Code: $list_http_code\n", FILE_APPEND | LOCK_EX);

        if (!empty($list_curl_error)) {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Get all categories cURL Error: $list_curl_error\n", FILE_APPEND | LOCK_EX);
            return null;
        }

        if ($list_http_code == 200) {
            $list_result = json_decode($list_response, true);
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] 📥 All categories response: " . json_encode($list_result, JSON_PRETTY_PRINT) . "\n", FILE_APPEND | LOCK_EX);
            
            if ($list_result && isset($list_result['s']) && $list_result['s'] == true) {
                return $list_result['d'] ?? [];
            } else {
                file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ List request failed\n", FILE_APPEND | LOCK_EX);
                return null;
            }
        } else {
            file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ Get all categories HTTP Error: $list_http_code\n", FILE_APPEND | LOCK_EX);
            return null;
        }
    } catch (Exception $e) {
        file_put_contents($log_file, "[" . date('Y-m-d H:i:s') . " WIB] ❌ getAllCategories Exception: " . $e->getMessage() . "\n", FILE_APPEND | LOCK_EX);
        return null;
    }
}

echo "<h1>Category Detail Debug Script</h1>";
echo "<pre>";

// Test koneksi dan dapatkan host
echo "=== STEP 1: GET HOST ===\n";
$host = getAccurateHost();
if (!$host) {
    echo "❌ Failed to get Accurate host\n";
    exit;
}
echo "✅ Host: $host\n\n";

// Test mendapatkan detail kategori dengan ID 700
echo "=== STEP 2: GET CATEGORY DETAIL (ID: $category_id) ===\n";
$category_detail = getCategoryDetailById($host, $category_id, $log_file);
if ($category_detail) {
    echo "✅ Category detail found:\n";
    print_r($category_detail);
} else {
    echo "❌ Failed to get category detail\n";
}
echo "\n";

// Test mendapatkan semua kategori
echo "=== STEP 3: GET ALL CATEGORIES (Sample) ===\n";
$all_categories = getAllCategories($host, $log_file, 10);
if ($all_categories && is_array($all_categories)) {
    echo "✅ Found " . count($all_categories) . " categories:\n";
    foreach ($all_categories as $index => $category) {
        $id = $category['id'] ?? 'no-id';
        $name = $category['name'] ?? 'no-name';
        $code = $category['code'] ?? 'no-code';
        $description = $category['description'] ?? 'no-description';
        echo "  [$index] ID: $id, Name: '$name', Code: '$code', Description: '$description'\n";
    }
} else {
    echo "❌ Failed to get categories list\n";
}
echo "\n";

// Cari kategori DUMMY secara manual dari list
echo "=== STEP 4: MANUAL SEARCH FOR 'DUMMY' ===\n";
if ($all_categories && is_array($all_categories)) {
    $found_dummy = false;
    foreach ($all_categories as $category) {
        $id = $category['id'] ?? '';
        $name = $category['name'] ?? '';
        $code = $category['code'] ?? '';
        $description = $category['description'] ?? '';
        
        // Check if any field contains "DUMMY" (case insensitive)
        if (stripos($name, 'DUMMY') !== false || 
            stripos($code, 'DUMMY') !== false || 
            stripos($description, 'DUMMY') !== false) {
            echo "✅ Found DUMMY category:\n";
            echo "   ID: $id\n";
            echo "   Name: '$name'\n";
            echo "   Code: '$code'\n";
            echo "   Description: '$description'\n";
            $found_dummy = true;
            break;
        }
    }
    
    if (!$found_dummy) {
        echo "❌ No DUMMY category found in the list\n";
        echo "Checking if ID 700 exists in the list...\n";
        
        foreach ($all_categories as $category) {
            if (($category['id'] ?? '') == '700') {
                echo "✅ Found category with ID 700:\n";
                print_r($category);
                $found_dummy = true;
                break;
            }
        }
        
        if (!$found_dummy) {
            echo "❌ ID 700 not found in the list either\n";
        }
    }
} else {
    echo "❌ Cannot perform manual search - no categories list available\n";
}

echo "\n=== SCRIPT COMPLETED ===\n";
echo "Check log file: $log_file for detailed debugging information\n";

echo "</pre>";
?>