<?php
session_start();

// Check if user is logged in
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

include "../config/koneksi.php";
include "../config/accurate_config.php";

// Get item code from GET parameter
$txtid = mysqli_real_escape_string($koneksi, $_GET['kd']);

// Function to log API responses
function logAccurateResponse($message, $success = true) {
    $log_file = '../logs/accurate_item_delete_log.txt';
    $log_message = date('Y-m-d H:i:s') . ' - ' . ($success ? 'SUCCESS' : 'ERROR') . ': ' . $message . PHP_EOL;
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Function to check Accurate API connection
function checkAccurateConnection() {
    if (!defined('ACCURATE_API_TOKEN') || !defined('ACCURATE_SIGNATURE_SECRET') || !defined('ACCURATE_API_BASE_URL')) {
        return [
            'status' => 'disconnected',
            'message' => 'Konfigurasi API tidak lengkap'
        ];
    }

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
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if (!empty($curl_error)) {
            return [
                'status' => 'disconnected',
                'message' => 'Connection error: ' . $curl_error
            ];
        }

        if ($http_code == 200) {
            $result = json_decode($response, true);
            if ($result && isset($result['s']) && $result['s'] == true) {
                return [
                    'status' => 'connected',
                    'message' => 'Terhubung dengan Accurate Online'
                ];
            }
        }

        return [
            'status' => 'disconnected',
            'message' => 'API Token tidak valid atau permission tidak mencukupi'
        ];
    } catch (Exception $e) {
        return [
            'status' => 'disconnected',
            'message' => 'Exception: ' . $e->getMessage()
        ];
    }
}

// Function to check if item exists in Accurate
function checkAccurateItemExists($item_no, $session_id) {
    $timestamp = formatTimestamp();
    $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
    $url = ACCURATE_API_BASE_URL . '/api/item/detail.do?no=' . urlencode($item_no);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ACCURATE_API_TOKEN,
        "X-Api-Timestamp: $timestamp",
        "X-Api-Signature: $signature",
        "X-Session-ID: $session_id",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if (!empty($curl_error)) {
        logAccurateResponse("Check item exists error: $curl_error", false);
        return false;
    }

    if ($http_code == 200) {
        $result = json_decode($response, true);
        logAccurateResponse("Check item exists response: " . print_r($result, true));
        return $result && isset($result['s']) && $result['s'] === true;
    }
    logAccurateResponse("Check item exists HTTP error $http_code: $response", false);
    return false;
}

// Function to check item dependencies in Accurate
function checkItemDependencies($item_no, $session_id) {
    $timestamp = formatTimestamp();
    $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
    $url = ACCURATE_API_BASE_URL . '/api/item/stock-mutation-history.do?filter.no.val[0]=' . urlencode($item_no);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ACCURATE_API_TOKEN,
        "X-Api-Timestamp: $timestamp",
        "X-Api-Signature: $signature",
        "X-Session-ID: $session_id",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if (!empty($curl_error)) {
        logAccurateResponse("Check dependencies error: $curl_error", false);
        return true; // Assume dependencies exist to be safe
    }

    if ($http_code == 200) {
        $result = json_decode($response, true);
        logAccurateResponse("Check dependencies response: " . print_r($result, true));
        return $result && isset($result['d']) && count($result['d']) > 0;
    }
    logAccurateResponse("Check dependencies HTTP error $http_code: $response", false);
    return true; // Assume dependencies exist to be safe
}

// Function to delete item from Accurate API
function deleteAccurateItem($item_no, $session_id) {
    $timestamp = formatTimestamp();
    $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);
    $url = ACCURATE_API_BASE_URL . '/api/item/delete.do?no=' . urlencode($item_no);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . ACCURATE_API_TOKEN,
        "X-Api-Timestamp: $timestamp",
        "X-Api-Signature: $signature",
        "X-Session-ID: $session_id",
        "Accept: application/json"
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if (!empty($curl_error)) {
        logAccurateResponse("cURL error: $curl_error", false);
        return [
            'success' => false,
            'message' => "Connection error: $curl_error"
        ];
    }

    if ($http_code == 200) {
        $result = json_decode($response, true);
        if ($result && isset($result['s']) && $result['s'] === true) {
            logAccurateResponse("Item $item_no deleted successfully from Accurate");
            return [
                'success' => true,
                'message' => "Item $item_no deleted successfully from Accurate"
            ];
        } else {
            $error_message = isset($result['d']) ? $result['d'] : 'Unknown error';
            logAccurateResponse("Failed to delete item $item_no: $error_message", false);
            return [
                'success' => false,
                'message' => "Failed to delete item: $error_message"
            ];
        }
    } else {
        logAccurateResponse("HTTP error $http_code: $response", false);
        return [
            'success' => false,
            'message' => "HTTP error $http_code: $response"
        ];
    }
}

// Check Accurate connection
$accurate_connection = checkAccurateConnection();
$_SESSION['accurate_status'] = $accurate_connection['status'];
$_SESSION['accurate_message'] = $accurate_connection['message'];

// Fetch session ID from Accurate if connected
$session_id = null;
if ($accurate_connection['status'] === 'connected') {
    $url = ACCURATE_API_BASE_URL . '/api/open-db.do';
    $timestamp = formatTimestamp();
    $signature = generateApiSignature($timestamp, ACCURATE_SIGNATURE_SECRET);

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($http_code == 200 && !empty($response)) {
        $result = json_decode($response, true);
        logAccurateResponse("Open DB response: " . print_r($result, true));
        if ($result && isset($result['d']['session'])) {
            $session_id = $result['d']['session'];
        } else {
            logAccurateResponse("Failed to get session ID: " . print_r($result, true), false);
        }
    } else {
        logAccurateResponse("Open DB HTTP error $http_code: $response, cURL error: $curl_error", false);
    }
}

// Begin database transaction
mysqli_begin_transaction($koneksi);

try {
    // Delete from tblitem
    $delete_item = mysqli_query($koneksi, "DELETE FROM tblitem WHERE noitem='$txtid'");
    if (!$delete_item) {
        throw new Exception("Failed to delete item from tblitem: " . mysqli_error($koneksi));
    }

    // Delete from tblitem_stok
    $delete_stock = mysqli_query($koneksi, "DELETE FROM tblitem_stok WHERE noitem='$txtid'");
    if (!$delete_stock) {
        throw new Exception("Failed to delete item from tblitem_stok: " . mysqli_error($koneksi));
    }

    // Delete from Accurate if connected and session ID is available
    $accurate_result = ['success' => true, 'message' => 'No Accurate sync attempted'];
    if ($accurate_connection['status'] === 'connected' && $session_id) {
        if (checkAccurateItemExists($txtid, $session_id)) {
            if (!checkItemDependencies($txtid, $session_id)) {
                $accurate_result = deleteAccurateItem($txtid, $session_id);
                if (!$accurate_result['success']) {
                    throw new Exception("Accurate API deletion failed: " . $accurate_result['message']);
                }
            } else {
                throw new Exception("Cannot delete item $txtid: Used in Accurate transactions");
            }
        } else {
            logAccurateResponse("Item $txtid not found in Accurate", false);
            $accurate_result = ['success' => false, 'message' => "Item $txtid not found in Accurate"];
        }
    }

    // Commit transaction
    mysqli_commit($koneksi);

    // Prepare success message
    $message = "Data Barang Berhasil dihapus!";
    if ($accurate_connection['status'] === 'connected' && $session_id) {
        if ($accurate_result['success']) {
            $message .= " Item juga dihapus dari Accurate Online.";
        } else {
            $message .= " (Accurate sync gagal: " . $accurate_result['message'] . ")";
        }
    } elseif ($accurate_connection['status'] !== 'connected') {
        $message .= " (Accurate sync tidak dilakukan: " . $accurate_connection['message'] . ")";
    }

    echo "<script>window.alert('$message');window.location='barang.php';</script>";
} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($koneksi);
    logAccurateResponse("Error deleting item $txtid: " . $e->getMessage(), false);
    echo "<script>window.alert('Gagal menghapus barang: " . addslashes($e->getMessage()) . "');window.location='barang.php';</script>";
}
?>