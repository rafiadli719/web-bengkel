<?php
// Pembuka halaman standar modul Keuangan Kasir untuk halaman ringan (detail/edit/print):
// head + :root vars + sidebar + <div class="main-content">. Pasangan page_close.php.
// Pemanggil wajib sudah require koneksi_kasir.php dan set $page_title (+ opsional $page_icon).
$page_title = $page_title ?? 'Keuangan Kasir';
$page_icon  = $page_icon ?? 'fa-file-invoice-dollar';
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Keuangan Kasir</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="includes/sidebar.css" rel="stylesheet">
    <style>
        :root { --primary-color:#2563eb; --secondary-color:#64748b; --success-color:#16a34a; --danger-color:#dc2626;
                --warning-color:#d97706; --border-color:#e2e8f0; --text-muted:#64748b; --bg-color:#f8fafc; --card-bg:#fff; }
        body { margin:0; font-family:"Segoe UI",Arial,sans-serif; background:#f1f5f9; color:#1e293b; }
        .main-content { width:auto !important; box-sizing:border-box; padding:20px; }
        .kp-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:20px; margin-bottom:16px; }
        .kp-card h1 { margin:0 0 4px; font-size:22px; }
        .kp-card p.sub { margin:0; color:#64748b; font-size:13px; }
        .kp-filter { display:flex; gap:10px; flex-wrap:wrap; align-items:flex-end; }
        .kp-filter label { display:block; font-size:12px; color:#64748b; margin-bottom:3px; }
        .kp-filter input, .kp-filter select, .kp-form input, .kp-form textarea, .kp-form select
            { padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:14px; }
        .kp-form .row { margin-bottom:14px; } .kp-form label { display:block; font-size:13px; font-weight:600; margin-bottom:4px; }
        .kp-form input, .kp-form textarea { width:100%; max-width:420px; box-sizing:border-box; }
        .kp-btn { display:inline-block; padding:8px 14px; border-radius:6px; border:0; background:#2563eb; color:#fff;
                  font-size:14px; text-decoration:none; cursor:pointer; }
        .kp-btn.sec { background:#e2e8f0; color:#1e293b; } .kp-btn.ok { background:#16a34a; }
        table.kp-table { width:100%; border-collapse:collapse; font-size:13px; }
        table.kp-table th { background:#f1f5f9; text-align:left; padding:8px; border-bottom:2px solid #e2e8f0; }
        table.kp-table td { padding:8px; border-bottom:1px solid #eef2f7; vertical-align:top; }
        .kp-right { text-align:right; } .kp-total td { font-weight:700; background:#f8fafc; }
        .kp-alert { padding:10px 14px; border-radius:6px; margin-bottom:14px; font-size:14px; }
        .kp-alert.err { background:#fee2e2; color:#991b1b; } .kp-alert.okk { background:#dcfce7; color:#166534; }
        .kp-empty { text-align:center; padding:30px; color:#64748b; }
        @media print { .sidebar, .no-print, .admin-sidebar-toggle { display:none !important; } .main-content { margin:0 !important; } }
    </style>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>
<script>
function toggleCat(id) {
    var el = document.getElementById('cat-' + id);
    if (el) { el.classList.toggle('open'); }
}
</script>
<div class="main-content">
