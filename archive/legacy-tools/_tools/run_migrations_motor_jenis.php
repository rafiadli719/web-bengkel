<?php
// Simple migration runner for motor jenis mapping (STRICT)
// Usage: open this file via browser or CLI to execute migrations in order.

declare(strict_types=1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== Motor/Jenis Migration Runner ===\n";

$baseDir = __DIR__;
$rootDir = dirname(__DIR__);

// Load DB connection (expects $koneksi as mysqli)
$koneksiPath = $rootDir . '/config/koneksi.php';
if (!file_exists($koneksiPath)) {
    echo "ERROR: Cannot find DB config at: {$koneksiPath}\n";
    exit(1);
}
require_once $koneksiPath;

if (!isset($koneksi) || !($koneksi instanceof mysqli)) {
    echo "ERROR: DB connection (mysqli) not initialized by config.\n";
    exit(1);
}

$koneksi->set_charset('utf8mb4');

// Identify current database
$dbName = '';
if ($res = $koneksi->query('SELECT DATABASE() AS db')) {
    if ($row = $res->fetch_assoc()) { $dbName = (string)$row['db']; }
    $res->free();
}
echo "Database: " . ($dbName !== '' ? $dbName : '(unknown)') . "\n\n";

// Ordered migrations
$migrations = [
    $rootDir . '/db/migrations/2026-01-07_motor_jenis_mapping.sql',
    $rootDir . '/db/migrations/2026-01-07_motor_jenis_mapping_views.sql',
];

function runFile(mysqli $db, string $file): bool {
    if (!file_exists($file)) {
        echo "[SKIP] File not found: {$file}\n";
        return false;
    }
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        echo "[SKIP] File empty: {$file}\n";
        return false;
    }

    echo "[RUN ] " . basename($file) . "\n";

    // Use multi_query to allow multiple statements in one file
    if (!$db->multi_query($sql)) {
        echo "[FAIL] multi_query error: (" . $db->errno . ") " . $db->error . "\n";
        echo "File: {$file}\n";
        return false;
    }

    $ok = true;
    do {
        // Store and free any result set
        if ($result = $db->store_result()) { $result->free(); }
        if ($db->errno) {
            echo "[FAIL] step error: (" . $db->errno . ") " . $db->error . "\n";
            $ok = false;
            break;
        }
    } while ($db->more_results() && $db->next_result());

    if ($ok) {
        echo "[OK  ] Completed: " . basename($file) . "\n\n";
    } else {
        echo "[WARN] Completed with errors: " . basename($file) . "\n\n";
    }

    return $ok;
}

$allOk = true;
foreach ($migrations as $path) {
    $res = runFile($koneksi, $path);
    if (!$res) { $allOk = false; break; }
}

echo $allOk ? "=== ALL DONE ===\n" : "=== DONE WITH ERRORS ===\n";

// Hints
if (!$allOk) {
    echo "\nHint:\n";
    echo "- Ensure you are using the correct database/schema.\n";
    echo "- Run files in order: mapping.sql then views.sql.\n";
    echo "- Check if prior partial runs created objects that may conflict with ALTER statements.\n";
}
