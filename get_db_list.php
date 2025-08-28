<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['access_token']) || !isset($_SESSION['_iduser'])) {
    die("Access Token atau login bengkel tidak ditemukan. Silakan login ulang. <a href='index.php'>Login</a>");
}

$access_token = $_SESSION['access_token'];
$url = 'https://account.accurate.id/api/db-list.do';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token"
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pilih Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .error {
            color: red;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Pilih Database</h1>
    <?php if ($result['s']): ?>
        <form action="open_db.php" method="post">
            <label>Pilih Database ID: <input type="text" name="db_id" placeholder="Masukkan Database ID" required></label><br>
            <p>Daftar Database:</p>
            <pre><?php print_r($result['d']); ?></pre>
            <button type="submit">Buka Database</button>
        </form>
    <?php else: ?>
        <p class="error">Gagal mengambil daftar database: <?php print_r($result); ?></p>
    <?php endif; ?>
    <p><a href="logout.php">Logout</a></p>
</body>
</html>