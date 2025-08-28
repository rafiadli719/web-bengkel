<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config/accurate_config.php';

$log_file = 'oauth_callback_log.txt';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - GET: " . json_encode($_GET) . "\n", FILE_APPEND);

if (!isset($_GET['code'])) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Authorization code tidak ditemukan.\n", FILE_APPEND);
    die("Authorization code tidak ditemukan.");
}

$code = $_GET['code'];
$client_id = CLIENT_ID;
$client_secret = CLIENT_SECRET;
$redirect_uri = REDIRECT_URI;

$url = 'https://account.accurate.id/oauth/token';
$auth = base64_encode("$client_id:$client_secret");

$data = [
    'code' => $code,
    'grant_type' => 'authorization_code',
    'redirect_uri' => $redirect_uri
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic $auth",
    'Content-Type: application/x-www-form-urlencoded'
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - cURL Error: " . curl_error($ch) . "\n", FILE_APPEND);
    die("Error cURL: " . curl_error($ch));
}

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
file_put_contents($log_file, date('Y-m-d H:i:s') . " - HTTP Status Code: $http_code, Response: $response\n", FILE_APPEND);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['access_token'])) {
    $_SESSION['access_token'] = $result['access_token'];
    $_SESSION['refresh_token'] = $result['refresh_token'];
    $_SESSION['scope'] = $result['scope'];
    $_SESSION['accurate_user'] = $result['user'] ?? [];
    header("Location: https://fitmotor.web.id/beta/aplikasi/index.php");
    exit;
} else {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Gagal mendapatkan token: " . json_encode($result) . "\n", FILE_APPEND);
    die("Gagal mendapatkan token: " . json_encode($result, JSON_PRETTY_PRINT));
}
?>