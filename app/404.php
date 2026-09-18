<?php
http_response_code(404);

$dashboardUrl = 'index.php';
$requestedPath = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, Helvetica, sans-serif;
            background: #f5f7fa;
            color: #1f2937;
        }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #f5f7fa;
        }
        .error-card {
            width: min(92vw, 620px);
            padding: 42px 34px;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 12px 34px rgba(15, 23, 42, .08);
            text-align: center;
        }
        .error-code {
            margin: 0;
            color: #2563eb;
            font-size: 72px;
            line-height: 1;
        }
        h1 { margin: 18px 0 10px; font-size: 26px; }
        p { color: #64748b; line-height: 1.6; }
        .requested-path {
            display: block;
            margin: 18px 0;
            padding: 10px 12px;
            overflow-wrap: anywhere;
            border-radius: 8px;
            background: #f8fafc;
            color: #475569;
            font-family: Consolas, monospace;
            font-size: 13px;
        }
        .actions { margin-top: 24px; }
        .button {
            display: inline-block;
            padding: 11px 18px;
            border-radius: 8px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }
        .button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <main class="error-card" role="main">
        <p class="error-code">404</p>
        <h1>Halaman tidak ditemukan</h1>
        <p>Alamat yang Anda buka tidak tersedia atau sudah dipindahkan.</p>
        <?php if ($requestedPath !== ''): ?>
            <code class="requested-path"><?php echo $requestedPath; ?></code>
        <?php endif; ?>
        <div class="actions">
            <a class="button" href="<?php echo $dashboardUrl; ?>">Kembali ke Dashboard</a>
        </div>
    </main>
</body>
</html>
