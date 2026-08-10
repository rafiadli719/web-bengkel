<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['refresh_token'])) {
    die("Refresh Token tidak ditemukan. Silakan lakukan otorisasi ulang.");
}

$client_id = '369ae985-b01f-474d-9dad-80dd9c853212';
$client_secret = '86f149e1e88327434843618cf7c7c840';
$refresh_token = $_SESSION['refresh_token'];

$url = 'https://account.accurate.id/oauth/token';
$auth = base64_encode("$client_id:$client_secret");

$data = [
    'grant_type' => 'refresh_token',
    'refresh_token' => $refresh_token
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
    echo "Error cURL: " . curl_error($ch) . "<br>";
} else {
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    echo "HTTP Status Code: $http_code<br>";
}

curl_close($ch);

$result = json_decode($response, true);

if (isset($result['access_token'])) {
    echo "Berhasil memperbarui Access Token: <br><pre>";
    print_r($result);
    echo "</pre>";

    // Simpan token baru ke sesi
    $_SESSION['access_token'] = $result['access_token'];
    $_SESSION['refresh_token'] = $result['refresh_token'];
    $_SESSION['scope'] = $result['scope'];

    // Redirect kembali ke dashboard
    header("Location: https://fitmotor.web.id/beta/aplikasi/dashboard.php");
    exit;
} else {
    echo "Gagal memperbarui token: <br><pre>";
    print_r($result);
    echo "</pre>";
}
?>