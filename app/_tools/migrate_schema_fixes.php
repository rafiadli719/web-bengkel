<?php
// Tool: migrate_schema_fixes.php
// Jalankan via: panel/_tools/migrate_schema_fixes.php?confirm=1
// Hanya bisa dijalankan dari localhost.

if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    exit('Akses ditolak. Tool ini hanya bisa dijalankan dari localhost.');
}

include dirname(__DIR__, 2) . '/config/koneksi.php';

$confirm = isset($_GET['confirm']) && $_GET['confirm'] === '1';
$sqlFile = dirname(__DIR__, 2) . '/tools/sql/migration_schema_fixes_2026.sql';

?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Migration: Schema Fixes 2026</title>
<style>
body { font-family: monospace; padding: 24px; background: #1e1e2e; color: #cdd6f4; }
h2   { color: #89b4fa; }
.ok  { color: #a6e3a1; }
.err { color: #f38ba8; }
.warn{ color: #f9e2af; }
.box { background: #313244; padding: 12px 16px; border-radius: 6px; margin: 8px 0; }
a.btn{ display:inline-block; background:#89b4fa; color:#1e1e2e; padding:8px 20px;
       border-radius:4px; text-decoration:none; font-weight:bold; margin-top:12px; }
</style>
</head>
<body>
<h2>Migration: Schema Fixes 2026</h2>

<?php if (!$confirm): ?>
<div class="box warn">
⚠️ Migration berikut akan dijalankan:<br><br>
<b>File:</b> tools/sql/migration_schema_fixes_2026.sql<br><br>
Perubahan:
<ol>
  <li><b>[CRITICAL]</b> tblitem_stok.kode_cabang: varchar(3) → varchar(10)</li>
  <li>tbuser_karyawan.kode_cabang: varchar(20) → varchar(10)</li>
  <li>no_item varchar(50) → varchar(20) di tabel transaksi &amp; staging</li>
  <li>no_supplier varchar(50) → varchar(20) di tabel transaksi &amp; staging</li>
  <li><b>[BARU]</b> CREATE TABLE tblitem_harga_cabang (harga per item per cabang)</li>
  <li>ADD INDEX tbstok.idx_sync_key</li>
</ol>
</div>
<a class="btn" href="?confirm=1">▶ Jalankan Migration</a>
<?php return; endif; ?>

<?php
if (!$koneksi) {
    echo '<div class="box err">❌ Koneksi database gagal.</div>';
    exit;
}

if (!file_exists($sqlFile)) {
    echo '<div class="box err">❌ File SQL tidak ditemukan: ' . htmlspecialchars($sqlFile) . '</div>';
    exit;
}

$sqlContent = file_get_contents($sqlFile);

// Pecah per statement, skip baris hanya komentar
$raw = explode(';', $sqlContent);
$statements = [];
foreach ($raw as $s) {
    $s = trim($s);
    if ($s === '' || preg_match('/^(--)/', $s)) continue;
    // Hilangkan baris komentar di awal multi-line
    $lines = array_filter(
        explode("\n", $s),
        fn($l) => trim($l) !== '' && !str_starts_with(trim($l), '--')
    );
    $clean = trim(implode("\n", $lines));
    if (strlen($clean) > 3) {
        $statements[] = $clean;
    }
}

$ok = 0; $fail = 0;

foreach ($statements as $sql) {
    $preview = htmlspecialchars(mb_substr(preg_replace('/\s+/', ' ', $sql), 0, 130));
    $result  = mysqli_query($koneksi, $sql);

    if ($result === false) {
        $err = mysqli_error($koneksi);
        $ignorable = str_contains($err, 'Duplicate key name')
                  || str_contains($err, "Table") && str_contains($err, "doesn't exist")
                  || str_contains($err, "Unknown table");
        if ($ignorable) {
            echo '<div class="box warn">⚠ SKIP — ' . $preview . '<br><small>' . htmlspecialchars($err) . '</small></div>';
        } else {
            echo '<div class="box err">❌ GAGAL — ' . $preview . '<br><small>' . htmlspecialchars($err) . '</small></div>';
            $fail++;
        }
    } else {
        $info = '';
        if (is_object($result) && ($row = mysqli_fetch_assoc($result))) {
            $info = ' &rarr; ' . htmlspecialchars(implode(', ', $row));
        }
        echo '<div class="box ok">✅ OK — ' . $preview . $info . '</div>';
        $ok++;
    }
}

echo '<hr>';
$cls = $fail > 0 ? 'err' : 'ok';
echo "<div class='box {$cls}'><b>Selesai.</b> OK: {$ok} | Gagal: {$fail}</div>";
?>
</body>
</html>
