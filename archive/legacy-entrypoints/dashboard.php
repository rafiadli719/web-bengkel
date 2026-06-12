<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'get_items.php';

if (!isset($_SESSION['access_token']) || !isset($_SESSION['host']) || !isset($_SESSION['session'])) {
    die("Token, host, atau session tidak ditemukan. Silakan lakukan otorisasi dan buka database terlebih dahulu. <a href='login_accurate.php'>Login Ulang</a>");
}

$access_token = $_SESSION['access_token'];
$host = $_SESSION['host'];
$session = $_SESSION['session'];

// Fungsi untuk mengambil data barang
function getItems($host, $access_token, $session) {
    $url = "$host/accurate/api/item/list.do?fields=id,name,no&filter.itemType=INVENTORY";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "X-Session-ID: $session"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// Proses CRUD berdasarkan action
$action = $_POST['action'] ?? '';
$result = getItems($host, $access_token, $session);

if ($action === 'insert') {
    $data = [
        'no' => $_POST['no'],
        'name' => $_POST['name'],
        'itemType' => 'INVENTORY',
        'price' => $_POST['price'],
        'unit' => $_POST['unit']
    ];
    $url = "$host/accurate/api/item/save.do";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "X-Session-ID: $session",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if ($result['s']) {
        $message = "Berhasil menambahkan barang.";
    } else {
        $message = "Gagal menambahkan barang: " . json_encode($result);
    }
} elseif ($action === 'update') {
    $data = [
        'id' => $_POST['id'],
        'no' => $_POST['no'],
        'name' => $_POST['name'],
        'itemType' => 'INVENTORY',
        'price' => $_POST['price']
    ];
    $url = "$host/accurate/api/item/save.do";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "X-Session-ID: $session",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if ($result['s']) {
        $message = "Berhasil mengupdate barang.";
    } else {
        $message = "Gagal mengupdate barang: " . json_encode($result);
    }
} elseif ($action === 'delete') {
    $data = ['id' => $_POST['id']];
    $url = "$host/accurate/api/item/delete.do";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $access_token",
        "X-Session-ID: $session",
        "Content-Type: application/json"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($response, true);
    if ($result['s']) {
        $message = "Berhasil menghapus barang.";
    } else {
        $message = "Gagal menghapus barang: " . json_encode($result);
    }
    // Refresh data setelah delete
    $result = getItems($host, $access_token, $session);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Dashboard Bengkel</title>
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-container { margin: 20px 0; }
        .message { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Dashboard Bengkel</h1>
    <h2>Daftar Sparepart</h2>

    <?php if (isset($message)): ?>
        <p class="<?php echo strpos($message, 'Gagal') !== false ? 'error' : 'message'; ?>"><?php echo $message; ?></p>
    <?php endif; ?>

    <!-- Form Tambah Barang -->
    <div class="form-container">
        <h3>Tambah Barang Baru</h3>
        <form method="post" action="">
            <input type="hidden" name="action" value="insert">
            <label>Kode Barang: <input type="text" name="no" required></label><br>
            <label>Nama Barang: <input type="text" name="name" required></label><br>
            <label>Harga: <input type="number" name="price" required></label><br>
            <label>Satuan: <input type="text" name="unit" required></label><br>
            <button type="submit">Tambah</button>
        </form>
    </div>

    <!-- Tabel Daftar Barang -->
    <table>
        <tr>
            <th>ID</th>
            <th>Kode</th>
            <th>Nama</th>
            <th>Aksi</th>
        </tr>
        <?php if (isset($result['s']) && $result['s'] && !empty($result['d'])): ?>
            <?php foreach ($result['d'] as $item): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo $item['no']; ?></td>
                    <td>
                        <form method="post" action="" style="display:inline;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="no" value="<?php echo $item['no']; ?>">
                            <input type="text" name="name" value="<?php echo $item['name']; ?>" required style="width:150px;">
                        </form>
                    </td>
                    <td>
                        <button type="submit" form="update-form-<?php echo $item['id']; ?>">Update</button>
                        <form method="post" action="" onsubmit="return confirm('Yakin hapus barang ini?');" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="4">Tidak ada data atau error.</td></tr>
        <?php endif; ?>
    </table>
    <a href="login_accurate.php">Kembali ke Login</a>
</body>
</html>