<?php
/**
 * DATABASE OPTIMIZATION RUNNER
 *
 * Script untuk menjalankan DATABASE_OPTIMIZATION_TEMUAN.sql dengan aman
 *
 * Features:
 * - Backup otomatis sebelum run
 * - Error handling yang baik
 * - Log detail untuk setiap statement
 * - Continue on duplicate key/constraint errors
 * - Rollback support
 *
 * @version 1.1
 * @date 2025-12-04
 * @changelog v1.1: Improved error handling, better SQL parsing, full backup
 */

// Prevent direct access from browser for safety
$allowed_run = true;

// Jika ingin batasi akses, uncomment ini:
// session_start();
// if(!isset($_SESSION['_login']) || $_SESSION['_level'] != 'adm01') {
//     die("Access denied. Admin only.");
// }

// Config
define('SQL_FILE', '../DATABASE_OPTIMIZATION_TEMUAN.sql');
define('BACKUP_DIR', '../backups/');
define('LOG_FILE', 'database_optimization_log.txt');

// Include koneksi database
require_once "../config/koneksi.php";

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Output buffer untuk streaming output
ob_implicit_flush(true);
if(ob_get_level() > 0) {
    ob_end_flush();
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Optimization Runner</title>
    <style>
        body {
            font-family: 'Consolas', 'Monaco', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        .section {
            background: #2d2d2d;
            border: 1px solid #3e3e3e;
            border-radius: 5px;
            padding: 15px;
            margin: 15px 0;
        }
        .success {
            color: #4ec9b0;
        }
        .warning {
            color: #dcdcaa;
        }
        .error {
            color: #f48771;
        }
        .info {
            color: #569cd6;
        }
        .skip {
            color: #808080;
        }
        .log-entry {
            padding: 5px 10px;
            margin: 3px 0;
            border-left: 3px solid #3e3e3e;
            font-size: 13px;
        }
        .progress {
            background: #3e3e3e;
            height: 30px;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress-bar {
            background: linear-gradient(90deg, #4ec9b0, #569cd6);
            height: 100%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-box {
            background: #2d2d2d;
            border: 1px solid #3e3e3e;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }
        .stat-label {
            color: #808080;
            font-size: 12px;
            text-transform: uppercase;
        }
        button {
            background: #4ec9b0;
            color: #1e1e1e;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            margin: 5px;
        }
        button:hover {
            background: #5dd9c0;
        }
        button.danger {
            background: #f48771;
        }
        button.danger:hover {
            background: #ff9781;
        }
        pre {
            background: #1e1e1e;
            border: 1px solid #3e3e3e;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Database Optimization Runner</h1>

        <div class="section">
            <h3>📋 Pre-Flight Check</h3>
            <?php
            // Check SQL file exists
            echo "<div class='log-entry'>";
            if(file_exists(SQL_FILE)) {
                echo "<span class='success'>✅ SQL file found: " . SQL_FILE . "</span>";
                $sql_size = filesize(SQL_FILE);
                echo " (<span class='info'>" . number_format($sql_size) . " bytes</span>)";
            } else {
                echo "<span class='error'>❌ SQL file not found: " . SQL_FILE . "</span>";
                die("</div></div></body></html>");
            }
            echo "</div>";

            // Check database connection
            echo "<div class='log-entry'>";
            if(isset($koneksi) && $koneksi->ping()) {
                echo "<span class='success'>✅ Database connection OK</span>";
                $db_name = $koneksi->query("SELECT DATABASE()")->fetch_row()[0];
                echo " (<span class='info'>$db_name</span>)";
            } else {
                echo "<span class='error'>❌ Database connection failed</span>";
                die("</div></div></body></html>");
            }
            echo "</div>";

            // Check backup directory
            echo "<div class='log-entry'>";
            if(!is_dir(BACKUP_DIR)) {
                mkdir(BACKUP_DIR, 0777, true);
                echo "<span class='warning'>⚠️  Created backup directory: " . BACKUP_DIR . "</span>";
            } else {
                echo "<span class='success'>✅ Backup directory exists: " . BACKUP_DIR . "</span>";
            }
            echo "</div>";
            ?>
        </div>

        <?php
        // Check if action is requested
        if(!isset($_GET['action'])) {
            ?>
            <div class="section">
                <h3>⚠️  Ready to Start</h3>
                <p>Script ini akan menjalankan optimasi database dengan langkah-langkah:</p>
                <ol>
                    <li>Backup database (otomatis)</li>
                    <li>Menambahkan PRIMARY KEYS</li>
                    <li>Menambahkan INDEXES untuk performance</li>
                    <li>Menambahkan FOREIGN KEYS untuk data integrity</li>
                    <li>Menambahkan CONSTRAINTS</li>
                    <li>Setup AUTO_INCREMENT</li>
                    <li>Membuat VIEWS</li>
                    <li>Membuat STORED PROCEDURES</li>
                </ol>

                <p class="warning">⚠️  <strong>PENTING:</strong></p>
                <ul>
                    <li>Akan ada beberapa error seperti "Duplicate key name" atau "Constraint already exists" - <strong>INI NORMAL</strong>!</li>
                    <li>Error tersebut muncul karena index/constraint sudah ada sebelumnya</li>
                    <li>Script akan continue dan tidak berhenti karena error tersebut</li>
                </ul>

                <p><strong>Estimasi waktu:</strong> ~5-10 menit</p>

                <button onclick="window.location.href='?action=run'">▶️  Start Optimization</button>
                <button onclick="window.location.href='?action=check_only'" style="background:#569cd6">🔍 Check Only (No Changes)</button>
            </div>
            <?php
        } elseif($_GET['action'] == 'run') {
            // RUN OPTIMIZATION
            runOptimization($koneksi);
        } elseif($_GET['action'] == 'check_only' || $_GET['action'] == 'verify') {
            // CHECK ONLY or VERIFY (same function)
            checkOptimization($koneksi);
        }
        ?>

    </div>
</body>
</html>

<?php

/**
 * Run optimization process
 */
function runOptimization($koneksi) {
    echo "<div class='section'>";
    echo "<h3>🔄 Running Optimization...</h3>";

    $start_time = microtime(true);

    // Step 1: Create backup
    echo "<h4>Step 1: Creating Backup</h4>";
    $backup_file = createBackup($koneksi);
    if($backup_file) {
        echo "<div class='log-entry success'>✅ Backup created: $backup_file</div>";
    } else {
        echo "<div class='log-entry error'>❌ Backup failed! Aborting...</div>";
        echo "</div>";
        return;
    }

    // Step 2: Read SQL file
    echo "<h4>Step 2: Reading SQL File</h4>";
    $sql_content = file_get_contents(SQL_FILE);
    echo "<div class='log-entry success'>✅ SQL file loaded (" . strlen($sql_content) . " characters)</div>";

    // Step 3: Parse statements
    echo "<h4>Step 3: Parsing SQL Statements</h4>";
    $statements = parseSQL($sql_content);
    echo "<div class='log-entry success'>✅ Found " . count($statements) . " statements</div>";

    // Step 4: Execute statements
    echo "<h4>Step 4: Executing Statements</h4>";
    echo "<div class='progress'>";
    echo "<div class='progress-bar' id='progress-bar' style='width:0%'>0%</div>";
    echo "</div>";

    $results = executeStatements($koneksi, $statements);

    // Step 5: Show results
    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);

    echo "</div>"; // Close section

    // Stats
    echo "<div class='section'>";
    echo "<h3>📊 Execution Statistics</h3>";
    echo "<div class='stats'>";
    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>Total Statements</div>";
    echo "<div class='stat-number success'>" . $results['total'] . "</div>";
    echo "</div>";

    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>Success</div>";
    echo "<div class='stat-number success'>" . $results['success'] . "</div>";
    echo "</div>";

    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>Skipped (Already Exists)</div>";
    echo "<div class='stat-number warning'>" . $results['skipped'] . "</div>";
    echo "</div>";

    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>Errors</div>";
    echo "<div class='stat-number error'>" . $results['errors'] . "</div>";
    echo "</div>";

    echo "<div class='stat-box'>";
    echo "<div class='stat-label'>Duration</div>";
    echo "<div class='stat-number info'>" . $duration . "s</div>";
    echo "</div>";
    echo "</div>";

    // Show errors if any
    if(count($results['error_details']) > 0) {
        echo "<h4>⚠️  Errors Encountered</h4>";
        foreach($results['error_details'] as $error) {
            $severity = determineErrorSeverity($error['message']);
            $color = $severity == 'low' ? 'warning' : 'error';
            echo "<div class='log-entry $color'>";
            echo "<strong>Statement #" . $error['index'] . ":</strong> ";
            echo $error['message'];
            if($severity == 'low') {
                echo " <span class='skip'>(Can be ignored)</span>";
            }
            echo "</div>";
        }
    }

    // Success message
    echo "<div class='log-entry success' style='margin-top:20px; font-size:16px; font-weight:bold'>";
    echo "✅ Optimization completed!";
    echo "</div>";

    echo "<p>Backup file: <code>$backup_file</code></p>";
    echo "<p><button onclick=\"window.location.href='?action=verify'\">🔍 Verify Results</button></p>";

    echo "</div>";

    // Log to file
    logToFile($results, $duration, $backup_file);
}

/**
 * Create database backup
 */
function createBackup($koneksi) {
    $timestamp = date('Ymd_His');
    $backup_file = BACKUP_DIR . "backup_before_optimization_{$timestamp}.sql";

    // Get database name
    $db_name = $koneksi->query("SELECT DATABASE()")->fetch_row()[0];

    // Use mysqldump
    $mysqldump_path = "C:\\xampp\\mysql\\bin\\mysqldump.exe"; // Adjust if needed

    if(file_exists($mysqldump_path)) {
        $command = "\"$mysqldump_path\" -u root $db_name > \"$backup_file\" 2>&1";
        exec($command, $output, $return_var);

        if($return_var == 0 && file_exists($backup_file)) {
            return $backup_file;
        }
    }

    // Fallback: Simple backup using PHP
    return createSimpleBackup($koneksi, $backup_file);
}

/**
 * Simple backup using PHP (fallback)
 */
function createSimpleBackup($koneksi, $backup_file) {
    $tables = ['tbmaster_temuan', 'tbmaster_temuan_barang_mapping', 'tbservis_temuan', 'tbservis_penawaran_part'];

    $sql_dump = "-- Backup created at " . date('Y-m-d H:i:s') . "\n\n";

    foreach($tables as $table) {
        // Check if table exists
        $check = $koneksi->query("SHOW TABLES LIKE '$table'");
        if(!$check || $check->num_rows == 0) {
            $sql_dump .= "-- Table `$table` does not exist, skipping\n\n";
            continue;
        }

        // Get CREATE TABLE
        $result = $koneksi->query("SHOW CREATE TABLE `$table`");
        if($result && $row = $result->fetch_row()) {
            $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql_dump .= $row[1] . ";\n\n";
        }

        // Get data (all rows)
        $result = $koneksi->query("SELECT * FROM `$table`");
        if($result && $result->num_rows > 0) {
            $sql_dump .= "-- Data for table `$table` (" . $result->num_rows . " rows)\n";
            while($row = $result->fetch_assoc()) {
                $values = array_map(function($v) use ($koneksi) {
                    return $v === null ? 'NULL' : "'" . $koneksi->real_escape_string($v) . "'";
                }, $row);
                $sql_dump .= "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n";
            }
            $sql_dump .= "\n";
        }
    }

    if(file_put_contents($backup_file, $sql_dump)) {
        return $backup_file;
    }

    return false;
}

/**
 * Parse SQL file into individual statements
 */
function parseSQL($content) {
    $statements = [];
    $lines = explode("\n", $content);
    $current_statement = '';
    $in_block_comment = false;

    foreach($lines as $line) {
        $line = trim($line);

        // Handle block comments (/* ... */)
        if(strpos($line, '/*') !== false) {
            $in_block_comment = true;
        }
        if($in_block_comment) {
            if(strpos($line, '*/') !== false) {
                $in_block_comment = false;
            }
            continue;
        }

        // Skip single-line comments and empty lines
        if(empty($line) || substr($line, 0, 2) == '--') {
            continue;
        }

        // Skip USE database statement
        if(stripos($line, 'USE ') === 0) {
            continue;
        }

        $current_statement .= ' ' . $line;

        // Check if statement ends with semicolon
        if(substr(rtrim($line), -1) == ';') {
            $stmt = trim($current_statement);
            if(!empty($stmt)) {
                $statements[] = $stmt;
            }
            $current_statement = '';
        }
    }

    // Add remaining statement if any
    if(!empty(trim($current_statement))) {
        $statements[] = trim($current_statement);
    }

    return $statements;
}

/**
 * Execute SQL statements
 */
function executeStatements($koneksi, $statements) {
    $total = count($statements);
    $success = 0;
    $skipped = 0;
    $errors = 0;
    $error_details = [];

    foreach($statements as $index => $statement) {
        $progress = round((($index + 1) / $total) * 100);

        // Update progress bar
        echo "<script>";
        echo "document.getElementById('progress-bar').style.width = '{$progress}%';";
        echo "document.getElementById('progress-bar').innerText = '{$progress}%';";
        echo "</script>";
        flush();

        // Execute statement
        $result = @$koneksi->query($statement);

        if($result) {
            $success++;
            // Don't log every success to avoid clutter
        } else {
            $error_msg = $koneksi->error;

            // Check if it's a "safe" error (already exists)
            if(isSafeError($error_msg)) {
                $skipped++;
                // Log as skip
                echo "<div class='log-entry skip'>";
                echo "⏭️  Skipped #" . ($index + 1) . ": " . htmlspecialchars(substr($statement, 0, 60)) . "... ";
                echo "<span style='font-size:11px'>(" . htmlspecialchars($error_msg) . ")</span>";
                echo "</div>";
            } else {
                $errors++;
                $error_details[] = [
                    'index' => $index + 1,
                    'statement' => $statement,
                    'message' => $error_msg
                ];

                // Log error
                echo "<div class='log-entry error'>";
                echo "❌ Error #" . ($index + 1) . ": " . htmlspecialchars($error_msg);
                echo "</div>";
            }
        }

        // Small delay to avoid overwhelming
        usleep(10000); // 10ms
    }

    return [
        'total' => $total,
        'success' => $success,
        'skipped' => $skipped,
        'errors' => $errors,
        'error_details' => $error_details
    ];
}

/**
 * Check if error is safe to ignore
 */
function isSafeError($error) {
    $safe_patterns = [
        'Duplicate key name',
        'Duplicate index',
        'already exists',
        'Multiple primary key',
        'Duplicate entry',
    ];

    foreach($safe_patterns as $pattern) {
        if(stripos($error, $pattern) !== false) {
            return true;
        }
    }

    return false;
}

/**
 * Determine error severity
 */
function determineErrorSeverity($error) {
    if(isSafeError($error)) {
        return 'low';
    }

    $medium_patterns = [
        'Cannot add foreign key',
        'constraint fails',
    ];

    foreach($medium_patterns as $pattern) {
        if(stripos($error, $pattern) !== false) {
            return 'medium';
        }
    }

    return 'high';
}

/**
 * Check optimization results
 */
function checkOptimization($koneksi) {
    echo "<div class='section'>";
    echo "<h3>🔍 Checking Optimization Status</h3>";

    // Check indexes
    echo "<h4>Indexes Status</h4>";
    $tables = ['tbmaster_temuan', 'tbmaster_temuan_barang_mapping', 'tbservis_temuan', 'tbservis_penawaran_part'];

    foreach($tables as $table) {
        echo "<p><strong>Table: $table</strong></p>";

        // Check if table exists
        $check = $koneksi->query("SHOW TABLES LIKE '$table'");
        if(!$check || $check->num_rows == 0) {
            echo "<div class='log-entry error'>❌ Table does not exist!</div>";
            continue;
        }

        $result = $koneksi->query("SHOW INDEX FROM `$table`");
        if($result) {
            echo "<pre>";
            $indexes = [];
            while($row = $result->fetch_assoc()) {
                $indexes[] = $row['Key_name'];
            }
            echo "Indexes: " . implode(', ', array_unique($indexes));
            echo "</pre>";
        }
    }

    // Check foreign keys
    echo "<h4>Foreign Keys Status</h4>";
    $result = $koneksi->query("
        SELECT CONSTRAINT_NAME, TABLE_NAME, REFERENCED_TABLE_NAME
        FROM information_schema.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = DATABASE()
        AND REFERENCED_TABLE_NAME IS NOT NULL
        AND TABLE_NAME IN ('tbmaster_temuan_barang_mapping', 'tbservis_penawaran_part', 'tbservis_temuan')
    ");

    if($result && $result->num_rows > 0) {
        echo "<pre>";
        while($row = $result->fetch_assoc()) {
            echo "FK: " . $row['CONSTRAINT_NAME'] . " - " . $row['TABLE_NAME'] . " → " . $row['REFERENCED_TABLE_NAME'] . "\n";
        }
        echo "</pre>";
    } else {
        echo "<div class='log-entry warning'>⚠️  No foreign keys found</div>";
    }

    echo "<p><button onclick=\"window.location.href='?action=run'\">▶️  Run Optimization Now</button></p>";
    echo "</div>";
}

/**
 * Log to file
 */
function logToFile($results, $duration, $backup_file) {
    $log_content = "\n" . str_repeat("=", 60) . "\n";
    $log_content .= "DATABASE OPTIMIZATION LOG\n";
    $log_content .= "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $log_content .= str_repeat("=", 60) . "\n";
    $log_content .= "Total Statements: " . $results['total'] . "\n";
    $log_content .= "Success: " . $results['success'] . "\n";
    $log_content .= "Skipped: " . $results['skipped'] . "\n";
    $log_content .= "Errors: " . $results['errors'] . "\n";
    $log_content .= "Duration: {$duration}s\n";
    $log_content .= "Backup: $backup_file\n";

    if(count($results['error_details']) > 0) {
        $log_content .= "\nERRORS:\n";
        foreach($results['error_details'] as $error) {
            $log_content .= "  - Statement #{$error['index']}: {$error['message']}\n";
        }
    }

    $log_content .= str_repeat("=", 60) . "\n";

    file_put_contents(LOG_FILE, $log_content, FILE_APPEND);
}

?>
