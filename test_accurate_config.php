<?php
/**
 * Test Script untuk Accurate Configuration
 * File: test_accurate_config.php
 * Gunakan untuk memverifikasi konfigurasi Accurate API
 */

// Include config file
include_once 'config/accurate_config.php';

echo "=== ACCURATE API CONFIGURATION TEST ===\n\n";

// Test 1: Validasi Konfigurasi
echo "1. Testing Configuration Validation...\n";
$validation = validateAccurateConfig();
if ($validation === true) {
    echo "   ✅ Configuration is valid\n";
} else {
    echo "   ❌ Configuration errors:\n";
    foreach ($validation as $error) {
        echo "      - $error\n";
    }
}
echo "\n";

// Test 2: Test Functions
echo "2. Testing Required Functions...\n";
$required_functions = [
    'formatTimestamp',
    'generateApiSignature', 
    'getAccurateHost',
    'testAccurateConnection',
    'validateAccurateConfig'
];

foreach ($required_functions as $func) {
    if (function_exists($func)) {
        echo "   ✅ Function $func exists\n";
    } else {
        echo "   ❌ Function $func NOT exists\n";
    }
}
echo "\n";

// Test 3: Test Constants
echo "3. Testing Required Constants...\n";
$required_constants = [
    'ACCURATE_API_TOKEN',
    'ACCURATE_SIGNATURE_SECRET',
    'ACCURATE_API_BASE_URL'
];

foreach ($required_constants as $const) {
    if (defined($const)) {
        $value = constant($const);
        $masked_value = substr($value, 0, 20) . '...' . substr($value, -10);
        echo "   ✅ Constant $const = $masked_value\n";
    } else {
        echo "   ❌ Constant $const NOT defined\n";
    }
}
echo "\n";

// Test 4: Test API Connection
echo "4. Testing API Connection...\n";
echo "   Connecting to Accurate API...\n";
$connection_test = testAccurateConnection();
if ($connection_test['success']) {
    echo "   ✅ API Connection successful!\n";
    echo "   📄 Message: " . $connection_test['message'] . "\n";
    
    if (isset($connection_test['data']['d']['user']['email'])) {
        echo "   👤 User: " . $connection_test['data']['d']['user']['email'] . "\n";
    }
    if (isset($connection_test['data']['d']['application']['name'])) {
        echo "   📱 App: " . $connection_test['data']['d']['application']['name'] . "\n";
    }
} else {
    echo "   ❌ API Connection failed!\n";
    echo "   📄 Error: " . $connection_test['error'] . "\n";
}
echo "\n";

// Test 5: Test Get Host
echo "5. Testing Get Host Function...\n";
echo "   Getting Accurate host...\n";
$host = getAccurateHost();
if ($host) {
    echo "   ✅ Host retrieved successfully: $host\n";
} else {
    echo "   ❌ Failed to get host\n";
}
echo "\n";

// Test 6: Test Timestamp and Signature
echo "6. Testing Timestamp and Signature Generation...\n";
$timestamp = formatTimestamp();
echo "   📅 Timestamp: $timestamp\n";

$signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
$masked_signature = substr($signature, 0, 20) . '...' . substr($signature, -10);
echo "   🔑 Signature: $masked_signature\n";
echo "\n";

// Summary
echo "=== TEST SUMMARY ===\n";
if ($validation === true && $connection_test['success'] && $host) {
    echo "🎉 ALL TESTS PASSED! Accurate API configuration is working properly.\n";
    echo "\nYou can now:\n";
    echo "- Use the login system with Accurate API integration\n";
    echo "- Run stock management with Accurate synchronization\n";
    echo "- Check logs in accurate_api.log for detailed activity\n";
} else {
    echo "⚠️  SOME TESTS FAILED. Please check the errors above.\n";
    echo "\nCommon issues:\n";
    echo "- Check if API token is valid and not expired\n";
    echo "- Verify signature secret is correct\n";
    echo "- Ensure internet connection is working\n";
    echo "- Check if Accurate license is active\n";
}

echo "\n=== END TEST ===\n";
?>