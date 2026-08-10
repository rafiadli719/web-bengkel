<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['access_token']) || !isset($_SESSION['_iduser']) || !isset($_POST['db_id'])) {
    die("Access Token, login bengkel, atau Database ID tidak ditemukan. Silakan login ulang. <a href='index.php'>Login</a>");
}

$access_token = $_SESSION['access_token'];
$db_id = $_POST['db_id'];

$url = "https://account.accurate.id/api/open-db.do?id=$db_id";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if ($result['s']) {
    $_SESSION['host'] = $result['host'];
    $_SESSION['session'] = $result['session'];
    // Arahkan ke halaman tujuan, misalnya master_barang.php
    header("Location: https://fitmotor.web.id/beta/aplikasi/master_barang.php");
    exit;
} else {
    echo "Gagal membuka database: <br><pre>";
    print_r($result);
    echo "</pre>";
    echo '<p><a href="get_db_list.php">Kembali</a></p>';
}
?>