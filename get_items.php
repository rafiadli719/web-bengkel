<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['access_token']) || !isset($_SESSION['host']) || !isset($_SESSION['session'])) {
    die("Token, host, atau session tidak ditemukan. Silakan lakukan otorisasi dan buka database terlebih dahulu.");
}

$access_token = $_SESSION['access_token'];
$host = $_SESSION['host'];
$session = $_SESSION['session'];

$url = "$host/accurate/api/item/list.do?fields=id,name,no&filter.itemType=INVENTORY";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "X-Session-ID: $session"
]);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    die("Error cURL: " . curl_error($ch));
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "HTTP Status Code: $http_code<br>";
curl_close($ch);

$result = json_decode($response, true);

if ($result['s']) {
    echo "Daftar Barang: <br><pre>";
    print_r($result['d']);
    echo "</pre>";
} else {
    echo "Gagal mengambil data barang: <br><pre>";
    print_r($result);
    echo "</pre>";
}
?>