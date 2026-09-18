<?php
// Friendly fallback for old/guessed Master Item URL.
session_start();
http_response_code(404);

if (empty($_SESSION['_iduser'])) {
    header('Location: ../login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Halaman Master Item Tidak Digunakan</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/ace.min.css">
    <style>
        body { background: #f5f7fa; }
        .fallback-card { max-width: 680px; margin: 80px auto; padding: 32px; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; box-shadow: 0 8px 28px rgba(15, 23, 42, .08); }
        .fallback-card h1 { margin-top: 0; color: #1f2937; }
        .fallback-card p { color: #64748b; line-height: 1.6; }
        .actions { margin-top: 24px; }
        .actions .btn { margin: 4px; }
    </style>
</head>
<body class="no-skin">
    <main class="fallback-card text-center">
        <h1><i class="fa fa-info-circle text-primary"></i> Halaman Master Item Tidak Digunakan</h1>
        <p>
            URL <code>master_item.php</code> bukan halaman menu aktif di aplikasi ini.
            Untuk data item/barang, gunakan halaman Master Barang atau daftar Work Order/Paket.
        </p>
        <div class="actions">
            <a href="barang.php" class="btn btn-primary"><i class="fa fa-cubes"></i> Buka Master Barang</a>
            <a href="workorder-list.php" class="btn btn-info"><i class="fa fa-list"></i> Buka Work Order/Paket</a>
            <a href="index.php" class="btn btn-default"><i class="fa fa-home"></i> Dashboard</a>
        </div>
    </main>
</body>
</html>
