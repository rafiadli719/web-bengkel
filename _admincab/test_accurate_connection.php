<?php
/**
 * File: test_accurate_connection.php
 * Untuk testing dan troubleshooting koneksi Accurate API
 */

session_start();
include "../config/koneksi.php";
include "../config/accurate_config.php";

// Function untuk testing koneksi Accurate
function testAccurateConnection($verbose = false) {
    $results = [];
    
    // Test 1: Cek konfigurasi
    $results['config'] = [
        'ACCURATE_API_TOKEN' => defined('ACCURATE_API_TOKEN') ? 'Defined' : 'Not Defined',
        'ACCURATE_SIGNATURE_SECRET' => defined('ACCURATE_SIGNATURE_SECRET') ? 'Defined' : 'Not Defined', 
        'ACCURATE_API_BASE_URL' => defined('ACCURATE_API_BASE_URL') ? ACCURATE_API_BASE_URL : 'Not Defined',
        'Token Length' => defined('ACCURATE_API_TOKEN') ? strlen(ACCURATE_API_TOKEN) : 0
    ];
    
    if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
        $results['error'] = 'Konfigurasi API tidak lengkap';
        return $results;
    }
    
    // Test 2: Test API Token
    try {
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($verbose) {
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            $verbose_log = fopen('php://temp', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $verbose_log);
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        if ($verbose) {
            rewind($verbose_log);
            $results['curl_verbose'] = stream_get_contents($verbose_log);
            fclose($verbose_log);
        }
        
        curl_close($ch);

        $results['token_test'] = [
            'http_code' => $http_code,
            'curl_error' => $curl_error,
            'response' => $response,
            'response_json' => json_decode($response, true)
        ];
        
        if ($http_code === 200 && !empty($response)) {
            $json = json_decode($response, true);
            if ($json && isset($json['s']) && $json['s'] === true) {
                $results['token_valid'] = true;
                $results['permissions'] = $json['r'] ?? [];
            } else {
                $results['token_valid'] = false;
                $results['token_error'] = $json['e'] ?? 'Unknown error';
            }
        } else {
            $results['token_valid'] = false;
        }
        
    } catch (Exception $e) {
        $results['token_test_error'] = $e->getMessage();
    }
    
    // Test 3: Test Session
    if (isset($results['token_valid']) && $results['token_valid']) {
        try {
            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
            
            $session_url = ACCURATE_API_BASE_URL . '/api/open-db.do';
            $ch = curl_init($session_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . ACCURATE_API_TOKEN,
                "X-Api-Timestamp: $timestamp",
                "X-Api-Signature: $signature",
                "Content-Type: application/x-www-form-urlencoded"
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $results['session_test'] = [
                'http_code' => $http_code,
                'response' => $response,
                'response_json' => json_decode($response, true)
            ];
            
            if ($http_code === 200) {
                $json = json_decode($response, true);
                if ($json && isset($json['session'])) {
                    $results['session_valid'] = true;
                    $results['session_id'] = $json['session'];
                } else {
                    $results['session_valid'] = false;
                }
            }
            
        } catch (Exception $e) {
            $results['session_test_error'] = $e->getMessage();
        }
    }
    
    // Test 4: Test Item List (jika session berhasil)
    if (isset($results['session_valid']) && $results['session_valid']) {
        try {
            $api_url = ACCURATE_API_BASE_URL . '/api/item/list.do';
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Session-ID: " . $results['session_id']
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, 'sp.pageSize=5'); // Ambil 5 item saja untuk test
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $results['item_test'] = [
                'http_code' => $http_code,
                'response_preview' => substr($response, 0, 500) . '...',
                'can_access_items' => $http_code === 200
            ];
            
        } catch (Exception $e) {
            $results['item_test_error'] = $e->getMessage();
        }
    }
    
    return $results;
}

// Function untuk test Item Adjustment
function testItemAdjustment($session_id, $test_item_code = null) {
    if (!$session_id) {
        return ['error' => 'Session ID required'];
    }
    
    // Gunakan item test atau default
    $item_code = $test_item_code ?: 'TEST001';
    
    $adjustment_data = [
        'transDate' => date('d/m/Y'),
        'description' => 'Test Item Adjustment - ' . date('Y-m-d H:i:s'),
        'number' => 'TEST-' . date('YmdHis'),
        'autoNumber' => 'false',
        'detailItem[0].itemNo' => $item_code,
        'detailItem[0].itemAdjustmentType' => 'ADJUSTMENT_OUT',
        'detailItem[0].quantity' => 1,
        'detailItem[0].unitCost' => 1000,
        'detailItem[0].warehouseName' => 'UTAMA',
        'detailItem[0].detailName' => 'Test Item',
        'detailItem[0].detailNotes' => 'Test Adjustment'
    ];
    
    try {
        $api_url = ACCURATE_API_BASE_URL . '/api/item-adjustment/save.do';
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "X-Session-ID: $session_id",
            "Content-Type: application/x-www-form-urlencoded"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($adjustment_data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'http_code' => $http_code,
            'request_data' => $adjustment_data,
            'response' => $response,
            'response_json' => json_decode($response, true),
            'success' => $http_code === 200
        ];
        
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

// Helper functions
if (!function_exists('formatTimestamp')) {
    function formatTimestamp() {
        return date('d/m/Y H:i:s');
    }
}

if (!function_exists('generateApiSignature')) {
    function generateApiSignature($timestamp, $signature_secret) {
        return base64_encode(hash_hmac('sha256', $timestamp, $signature_secret, true));
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Accurate API Test & Troubleshooting</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .test-section { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f1f1f1; padding: 10px; overflow: auto; border-radius: 3px; }
        .btn { padding: 8px 16px; margin: 5px; border: none; border-radius: 3px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Accurate API Test & Troubleshooting</h1>
        
        <?php if ($_GET['action'] ?? '' === 'test'): ?>
            
            <div class="test-section">
                <h2>📊 Test Results</h2>
                
                <?php
                $verbose = isset($_GET['verbose']);
                $test_results = testAccurateConnection($verbose);
                ?>
                
                <h3>1. Configuration Check</h3>
                <table>
                    <?php foreach ($test_results['config'] as $key => $value): ?>
                    <tr>
                        <td><strong><?= $key ?></strong></td>
                        <td class="<?= $value === 'Not Defined' ? 'error' : 'success' ?>"><?= $value ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                
                <?php if (isset($test_results['error'])): ?>
                    <div class="error">
                        <h4>❌ Configuration Error</h4>
                        <p><?= $test_results['error'] ?></p>
                    </div>
                <?php else: ?>
                
                    <h3>2. API Token Test</h3>
                    <?php if (isset($test_results['token_valid'])): ?>
                        <p class="<?= $test_results['token_valid'] ? 'success' : 'error' ?>">
                            <?= $test_results['token_valid'] ? '✅ Token Valid' : '❌ Token Invalid' ?>
                        </p>
                        
                        <?php if (!$test_results['token_valid'] && isset($test_results['token_error'])): ?>
                            <div class="error">
                                <strong>Error:</strong> <?= htmlspecialchars($test_results['token_error']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($test_results['permissions'])): ?>
                            <h4>Permissions:</h4>
                            <pre><?= json_encode($test_results['permissions'], JSON_PRETTY_PRINT) ?></pre>
                        <?php endif; ?>
                        
                    <?php endif; ?>
                    
                    <h4>Token Test Details:</h4>
                    <table>
                        <tr><td><strong>HTTP Code</strong></td><td><?= $test_results['token_test']['http_code'] ?></td></tr>
                        <tr><td><strong>CURL Error</strong></td><td><?= $test_results['token_test']['curl_error'] ?: 'None' ?></td></tr>
                    </table>
                    
                    <?php if ($verbose && isset($test_results['curl_verbose'])): ?>
                        <h4>CURL Verbose Log:</h4>
                        <pre><?= htmlspecialchars($test_results['curl_verbose']) ?></pre>
                    <?php endif; ?>
                    
                    <h3>3. Session Test</h3>
                    <?php if (isset($test_results['session_valid'])): ?>
                        <p class="<?= $test_results['session_valid'] ? 'success' : 'error' ?>">
                            <?= $test_results['session_valid'] ? '✅ Session Valid' : '❌ Session Invalid' ?>
                        </p>
                        
                        <?php if ($test_results['session_valid']): ?>
                            <p><strong>Session ID:</strong> <?= htmlspecialchars($test_results['session_id']) ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <h3>4. Item Access Test</h3>
                    <?php if (isset($test_results['item_test'])): ?>
                        <p class="<?= $test_results['item_test']['can_access_items'] ? 'success' : 'error' ?>">
                            <?= $test_results['item_test']['can_access_items'] ? '✅ Can Access Items' : '❌ Cannot Access Items' ?>
                        </p>
                        <p><strong>HTTP Code:</strong> <?= $test_results['item_test']['http_code'] ?></p>
                    <?php endif; ?>
                    
                    <!-- Test Item Adjustment -->
                    <?php if (isset($test_results['session_valid']) && $test_results['session_valid']): ?>
                        <h3>5. Item Adjustment Test</h3>
                        <form method="get" style="margin: 10px 0;">
                            <input type="hidden" name="action" value="test_adjustment">
                            <input type="hidden" name="session_id" value="<?= $test_results['session_id'] ?>">
                            <input type="text" name="item_code" placeholder="Item Code (e.g., BRG001)" value="<?= $_GET['item_code'] ?? '' ?>">
                            <button type="submit" class="btn btn-warning">Test Item Adjustment</button>
                        </form>
                    <?php endif; ?>
                    
                <?php endif; ?>
                
                <h3>Raw Response Data</h3>
                <pre><?= htmlspecialchars(json_encode($test_results, JSON_PRETTY_PRINT)) ?></pre>
            </div>
            
        <?php elseif ($_GET['action'] ?? '' === 'test_adjustment'): ?>
            
            <div class="test-section">
                <h2>🧪 Item Adjustment Test Results</h2>
                
                <?php
                $session_id = $_GET['session_id'] ?? '';
                $item_code = $_GET['item_code'] ?? '';
                $adjustment_result = testItemAdjustment($session_id, $item_code);
                ?>
                
                <h3>Test Parameters</h3>
                <table>
                    <tr><td><strong>Session ID</strong></td><td><?= htmlspecialchars($session_id) ?></td></tr>
                    <tr><td><strong>Item Code</strong></td><td><?= htmlspecialchars($item_code) ?></td></tr>
                </table>
                
                <h3>Results</h3>
                <?php if (isset($adjustment_result['error'])): ?>
                    <p class="error">❌ Error: <?= htmlspecialchars($adjustment_result['error']) ?></p>
                <?php else: ?>
                    <table>
                        <tr><td><strong>HTTP Code</strong></td><td><?= $adjustment_result['http_code'] ?></td></tr>
                        <tr><td><strong>Success</strong></td><td class="<?= $adjustment_result['success'] ? 'success' : 'error' ?>">
                            <?= $adjustment_result['success'] ? 'Yes' : 'No' ?>
                        </td></tr>
                    </table>
                    
                    <h4>Request Data Sent:</h4>
                    <pre><?= htmlspecialchars(json_encode($adjustment_result['request_data'], JSON_PRETTY_PRINT)) ?></pre>
                    
                    <h4>Response from Accurate:</h4>
                    <pre><?= htmlspecialchars($adjustment_result['response']) ?></pre>
                    
                    <?php if ($adjustment_result['response_json']): ?>
                        <h4>Parsed JSON Response:</h4>
                        <pre><?= htmlspecialchars(json_encode($adjustment_result['response_json'], JSON_PRETTY_PRINT)) ?></pre>
                        
                        <?php if (isset($adjustment_result['response_json']['s']) && $adjustment_result['response_json']['s'] === true): ?>
                            <div class="success">
                                <h4>✅ Item Adjustment Created Successfully!</h4>
                                <?php if (isset($adjustment_result['response_json']['r']['id'])): ?>
                                    <p><strong>Accurate ID:</strong> <?= $adjustment_result['response_json']['r']['id'] ?></p>
                                <?php endif; ?>
                            </div>
                        <?php elseif (isset($adjustment_result['response_json']['e'])): ?>
                            <div class="error">
                                <h4>❌ Error from Accurate API:</h4>
                                <p><?= htmlspecialchars(is_array($adjustment_result['response_json']['e']) ? 
                                    implode(', ', $adjustment_result['response_json']['e']) : 
                                    $adjustment_result['response_json']['e']) ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
                
                <p><a href="?action=test" class="btn btn-primary">← Back to Main Test</a></p>
            </div>
            
        <?php else: ?>
            
            <!-- Main Test Menu -->
            <div class="test-section">
                <h2>🧪 Available Tests</h2>
                <p>Pilih test yang ingin dijalankan untuk troubleshooting koneksi Accurate API:</p>
                
                <a href="?action=test" class="btn btn-primary">Basic Connection Test</a>
                <a href="?action=test&verbose=1" class="btn btn-warning">Detailed Test (with CURL verbose)</a>
                
                <h3>📋 Checklist Troubleshooting</h3>
                <div style="background: white; padding: 15px; border-left: 4px solid #17a2b8;">
                    <h4>1. Konfigurasi API Token</h4>
                    <ul>
                        <li>✅ Pastikan file <code>accurate_config.php</code> ada dan berisi konstanta yang diperlukan</li>
                        <li>✅ Cek API Token tidak kosong dan masih valid</li>
                        <li>✅ Cek Signature Secret sudah benar</li>
                        <li>✅ Cek Base URL sudah benar</li>
                    </ul>
                    
                    <h4>2. Permission API Token</h4>
                    <ul>
                        <li>✅ Token memiliki permission <strong>item_adjustment_save</strong></li>
                        <li>✅ Token memiliki permission <strong>item_adjustment_view</strong></li>
                        <li>✅ Token memiliki permission <strong>item_view</strong></li>
                        <li>✅ Scope database sudah benar</li>
                    </ul>
                    
                    <h4>3. Data Master Accurate</h4>
                    <ul>
                        <li>✅ Item sudah ada di master Accurate</li>
                        <li>✅ Warehouse "UTAMA" sudah dibuat</li>
                        <li>✅ Account untuk adjustment sudah dikonfigurasi</li>
                    </ul>
                    
                    <h4>4. Network & Server</h4>
                    <ul>
                        <li>✅ Server dapat mengakses internet</li>
                        <li>✅ Firewall tidak memblokir koneksi ke Accurate</li>
                        <li>✅ CURL extension PHP sudah terinstall</li>
                        <li>✅ SSL/TLS configuration sudah benar</li>
                    </ul>
                </div>
                
                <h3>📝 Common Error Solutions</h3>
                <div style="background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;">
                    <h4>Error: "Item not found"</h4>
                    <p>🔧 <strong>Solution:</strong> Pastikan item dengan kode yang sama sudah dibuat di master item Accurate Online.</p>
                    
                    <h4>Error: "Warehouse not found"</h4>
                    <p>🔧 <strong>Solution:</strong> Buat warehouse dengan nama "UTAMA" di Accurate Online.</p>
                    
                    <h4>Error: "Permission denied"</h4>
                    <p>🔧 <strong>Solution:</strong> Periksa permission API Token di menu Developer → API Token di Accurate Online.</p>
                    
                    <h4>Error: "Invalid signature"</h4>
                    <p>🔧 <strong>Solution:</strong> Periksa Signature Secret dan pastikan timestamp format sudah benar.</p>
                </div>
            </div>
            
        <?php endif; ?>
        
        <div class="test-section">
            <h3>📊 System Information</h3>
            <table>
                <tr><td><strong>PHP Version</strong></td><td><?= PHP_VERSION ?></td></tr>
                <tr><td><strong>CURL Version</strong></td><td><?= curl_version()['version'] ?? 'Not Available' ?></td></tr>
                <tr><td><strong>OpenSSL Version</strong></td><td><?= curl_version()['ssl_version'] ?? 'Not Available' ?></td></tr>
                <tr><td><strong>Server Time</strong></td><td><?= date('Y-m-d H:i:s T') ?></td></tr>
                <tr><td><strong>Timezone</strong></td><td><?= date_default_timezone_get() ?></td></tr>
            </table>
        </div>
        
        <div class="test-section">
            <p><small>
                <strong>Note:</strong> File ini untuk troubleshooting koneksi Accurate API. 
                Setelah masalah terselesaikan, hapus atau rename file ini untuk keamanan.
            </small></p>
        </div>
        
    </div>
</body>
</html>