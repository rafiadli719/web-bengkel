<?php
/**
 * Robust Accurate Online API Configuration
 * File: config/accurate_config.php
 * Version: 2.0 - Robust with better error handling
 */

// Set timezone first
date_default_timezone_set('Asia/Jakarta');

// API Configuration Constants
if (!defined('ACCURATE_API_TOKEN')) {
    define('ACCURATE_API_TOKEN', 'aat.NTA.eyJ2IjoxLCJ1Ijo4NjEwMzcsImQiOjE5MjczNjQsImFpIjo1NTUxOCwiYWsiOiIzYjNjNzk3OS02M2ExLTQ5M2EtYWZkNi01Y2NiNGIyZjNkNzIiLCJhbiI6IlBST0dSQU0gQkVOR0tFTCBGSVQgTU9UT1IiLCJhcCI6IjM2OWFlOTg1LWIwMWYtNDc0ZC05ZGFkLTgwZGQ5Yzg1MzIxMiIsInQiOjE3NTA5OTI5NTEyMTF9.3Uvr3VgjSHXVJ6GavI1py2dGKf6J4bWgW4+NtO861Jfd6Y02KvVyyNbxT2MVuDl4Jiv9wJ8c+sbQWseia74lT9L/N+ksNvOXi8yP0QAqsIRRqUee+SsuvkJzP0Crtn5kFWR6ZzbooTawDau+CxTA7C/F9WG2EaL5wMQfXPXNLbfld3FMVIGkYh2ysA/WEYYBSF9a3CcoTY8=.kaxP/eUEXWOMQFTirgp/xyR71dD+h60OZv2ox/sWw7A');
}

if (!defined('ACCURATE_SIGNATURE_SECRET')) {
    define('ACCURATE_SIGNATURE_SECRET', '6mJgVhxLeA0rwWht8cRZd3NbHDONE51oyrQ9WAXu12nmCRMGObpoi3xzNEfYFZa1');
}

if (!defined('ACCURATE_API_BASE_URL')) {
    define('ACCURATE_API_BASE_URL', 'https://account.accurate.id');
}

// Global variables untuk caching
$GLOBALS['ACCURATE_HOST'] = null;
$GLOBALS['ACCURATE_SESSION_ID'] = null;

/**
 * Utility function untuk logging
 */
if (!function_exists('logAccurateDebug')) {
    function logAccurateDebug($message, $file = 'accurate_debug.log') {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[$timestamp] $message\n";
        file_put_contents($file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Format timestamp untuk Accurate API
 */
if (!function_exists('formatTimestamp')) {
    function formatTimestamp($format = 'accurate') {
        switch($format) {
            case 'iso8601':
                return date('Y-m-d\TH:i:s');
            case 'unix':
                return time();
            case 'accurate':
            default:
                return date('d/m/Y H:i:s');
        }
    }
}

/**
 * Generate API signature
 */
if (!function_exists('generateApiSignature')) {
    function generateApiSignature($timestamp, $signature_secret) {
        $hash = hash_hmac('sha256', $timestamp, $signature_secret, true);
        return base64_encode($hash);
    }
}

/**
 * ROBUST: Get Accurate Host dengan multiple fallback paths
 */
if (!function_exists('getAccurateHost')) {
    function getAccurateHost($use_cache = true) {
        try {
            global $GLOBALS;
            
            // Use cached host if available
            if ($use_cache && !empty($GLOBALS['ACCURATE_HOST'])) {
                logAccurateDebug("Using cached host: " . $GLOBALS['ACCURATE_HOST']);
                return $GLOBALS['ACCURATE_HOST'];
            }
            
            $api_token = ACCURATE_API_TOKEN;
            $signature_secret = ACCURATE_SIGNATURE_SECRET;
            $base_url = ACCURATE_API_BASE_URL;
            
            // Generate timestamp and signature
            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, $signature_secret);
            
            $url = $base_url . '/api/api-token.do';
            
            logAccurateDebug("Making API call to: $url");
            logAccurateDebug("Timestamp: $timestamp");
            logAccurateDebug("Signature: " . substr($signature, 0, 20) . "...");
            
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
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'FitMotor/1.0');
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            logAccurateDebug("HTTP Code: $http_code");
            if ($curl_error) {
                logAccurateDebug("cURL Error: $curl_error");
                return false;
            }
            
            logAccurateDebug("Response length: " . strlen($response) . " bytes");
            logAccurateDebug("Response preview: " . substr($response, 0, 200) . "...");
            
            if ($http_code == 200) {
                $result = json_decode($response, true);
                if ($result === null) {
                    logAccurateDebug("JSON decode failed. Raw response: $response");
                    return false;
                }
                
                logAccurateDebug("JSON decoded successfully");
                
                if (isset($result['s']) && $result['s'] == true) {
                    logAccurateDebug("API call successful");
                    
                    // Multiple paths to check for host
                    $host_paths = [
                        ['d', 'database', 'host'],
                        ['d', 'data usaha', 'host'],
                        ['d', 'dataUsaha', 'host'],
                        ['d', 'company', 'host'],
                        ['d', 'host'],
                        ['host']
                    ];
                    
                    foreach ($host_paths as $path) {
                        $temp = $result;
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
                        
                        if ($found && is_string($temp) && filter_var($temp, FILTER_VALIDATE_URL)) {
                            logAccurateDebug("Host found at path '$path_str': $temp");
                            $GLOBALS['ACCURATE_HOST'] = $temp;
                            return $temp;
                        } else {
                            logAccurateDebug("No valid host at path '$path_str'");
                        }
                    }
                    
                    // If no host found in expected paths, log the full response for debugging
                    logAccurateDebug("No host found in any expected path. Full response: " . json_encode($result, JSON_PRETTY_PRINT));
                    
                } else {
                    $error_detail = isset($result['d']) ? json_encode($result['d']) : 'Unknown error';
                    logAccurateDebug("API call failed. Error: $error_detail");
                }
            } else {
                logAccurateDebug("HTTP error: $http_code. Response: $response");
            }
            
            return false;
            
        } catch (Exception $e) {
            logAccurateDebug("Exception in getAccurateHost: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Test connection to Accurate API
 */
if (!function_exists('testAccurateConnection')) {
    function testAccurateConnection() {
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
                "Authorization: Bearer $api_token",
                "X-Api-Timestamp: $timestamp",
                "X-Api-Signature: $signature", 
                "Content-Type: application/x-www-form-urlencoded",
                "Accept: application/json"
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if (!empty($curl_error)) {
                return [
                    'success' => false,
                    'error' => "cURL Error: $curl_error"
                ];
            }
            
            if ($http_code == 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['s']) && $result['s'] == true) {
                    return [
                        'success' => true,
                        'message' => 'Connection successful',
                        'data' => $result,
                        'response_raw' => $response
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'API response invalid',
                        'data' => $result,
                        'response_raw' => $response
                    ];
                }
            } else {
                return [
                    'success' => false,
                    'error' => "HTTP Error: $http_code",
                    'response_raw' => $response
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}

/**
 * Validate configuration
 */
if (!function_exists('validateAccurateConfig')) {
    function validateAccurateConfig() {
        $errors = [];
        
        if (!defined('ACCURATE_API_TOKEN') || empty(ACCURATE_API_TOKEN)) {
            $errors[] = 'ACCURATE_API_TOKEN not configured';
        }
        
        if (!defined('ACCURATE_SIGNATURE_SECRET') || empty(ACCURATE_SIGNATURE_SECRET)) {
            $errors[] = 'ACCURATE_SIGNATURE_SECRET not configured';
        }
        
        if (!defined('ACCURATE_API_BASE_URL') || empty(ACCURATE_API_BASE_URL)) {
            $errors[] = 'ACCURATE_API_BASE_URL not configured';
        }
        
        return empty($errors) ? true : $errors;
    }
}

/**
 * Establish Accurate session
 */
if (!function_exists('establishAccurateSession')) {
    function establishAccurateSession($host) {
        try {
            global $GLOBALS;
            
            if (!empty($GLOBALS['ACCURATE_SESSION_ID'])) {
                return $GLOBALS['ACCURATE_SESSION_ID'];
            }
            
            $api_token = ACCURATE_API_TOKEN;
            $signature_secret = ACCURATE_SIGNATURE_SECRET;
            
            $timestamp = formatTimestamp();
            $signature = generateApiSignature($timestamp, $signature_secret);
            
            $url = $host . '/accurate/api/open-db.do';
            
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
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code == 200) {
                $result = json_decode($response, true);
                if ($result && isset($result['s']) && $result['s'] == true) {
                    $session_id = isset($result['d']) && is_string($result['d']) ? $result['d'] : 'AUTO_SESSION';
                    $GLOBALS['ACCURATE_SESSION_ID'] = $session_id;
                    return $session_id;
                }
            }
            
            return false;
            
        } catch (Exception $e) {
            logAccurateDebug("establishAccurateSession Exception: " . $e->getMessage());
            return false;
        }
    }
}

// Log that config is loaded
logAccurateDebug("Accurate config loaded successfully");

// Validate config on load
$validation = validateAccurateConfig();
if ($validation !== true) {
    logAccurateDebug("Config validation errors: " . implode(', ', $validation));
}

?>