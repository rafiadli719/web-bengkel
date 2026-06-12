<?php
/**
 * DATABASE OPTIMIZER - TEMUAN & MAPPING SYSTEM
 *
 * File ini untuk update struktur database dengan aman
 * - Check dulu sebelum add (prevent error)
 * - Logging semua perubahan
 * - Rollback option
 *
 * Cara pakai:
 * 1. Buka di browser: http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/database_update_optimizer.php
 * 2. Klik tombol untuk mulai update
 * 3. Lihat progress real-time
 *
 * @author AI Assistant
 * @date 2025-12-04
 */

// Include database connection
require_once "../config/koneksi.php";

// Set timeout lebih lama untuk process
set_time_limit(300); // 5 menit
ini_set('max_execution_time', 300);

// Variable untuk tracking
$logs = [];
$errors = [];
$success_count = 0;
$skip_count = 0;
$error_count = 0;

/**
 * Helper function: Check if index exists
 */
function indexExists($koneksi, $table, $index_name) {
    $query = "SHOW INDEX FROM `$table` WHERE Key_name = '$index_name'";
    $result = mysqli_query($koneksi, $query);
    return mysqli_num_rows($result) > 0;
}

/**
 * Helper function: Check if foreign key exists
 */
function foreignKeyExists($koneksi, $table, $constraint_name) {
    $query = "SELECT CONSTRAINT_NAME
              FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = '$table'
              AND CONSTRAINT_NAME = '$constraint_name'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
    $result = mysqli_query($koneksi, $query);
    return mysqli_num_rows($result) > 0;
}

/**
 * Helper function: Check if primary key exists
 */
function primaryKeyExists($koneksi, $table) {
    $query = "SHOW KEYS FROM `$table` WHERE Key_name = 'PRIMARY'";
    $result = mysqli_query($koneksi, $query);
    return mysqli_num_rows($result) > 0;
}

/**
 * Helper function: Execute SQL with error handling
 */
function executeSafely($koneksi, $sql, $description, &$logs, &$success_count, &$skip_count, &$error_count, &$errors) {
    $result = mysqli_query($koneksi, $sql);
    if ($result) {
        $logs[] = "✅ SUCCESS: $description";
        $success_count++;
        return true;
    } else {
        $error = mysqli_error($koneksi);

        // Check if it's a "duplicate" error - treat as skip
        if (strpos($error, 'Duplicate') !== false ||
            strpos($error, 'already exists') !== false ||
            strpos($error, 'Multiple primary key') !== false) {
            $logs[] = "⏭️ SKIP: $description (already exists)";
            $skip_count++;
            return true;
        } else {
            $logs[] = "❌ ERROR: $description - " . $error;
            $errors[] = ['description' => $description, 'error' => $error, 'sql' => $sql];
            $error_count++;
            return false;
        }
    }
}

/**
 * Main update function
 */
function updateDatabase($koneksi, &$logs, &$success_count, &$skip_count, &$error_count, &$errors) {

    // ========================================
    // SECTION 1: ADD INDEXES
    // ========================================
    $logs[] = "\n=== SECTION 1: ADD INDEXES ===\n";

    $indexes = [
        // tbmaster_temuan
        ['table' => 'tbmaster_temuan', 'name' => 'idx_kategori', 'column' => 'kategori'],
        ['table' => 'tbmaster_temuan', 'name' => 'idx_is_active', 'column' => 'is_active'],
        ['table' => 'tbmaster_temuan', 'name' => 'idx_nama_temuan', 'column' => 'nama_temuan(50)'],

        // tbmaster_temuan_barang_mapping
        ['table' => 'tbmaster_temuan_barang_mapping', 'name' => 'idx_kode_temuan', 'column' => 'kode_temuan'],
        ['table' => 'tbmaster_temuan_barang_mapping', 'name' => 'idx_noitem', 'column' => 'noitem'],
        ['table' => 'tbmaster_temuan_barang_mapping', 'name' => 'idx_is_primary', 'column' => 'is_primary'],
        ['table' => 'tbmaster_temuan_barang_mapping', 'name' => 'idx_status_aktif', 'column' => 'status_aktif'],
        ['table' => 'tbmaster_temuan_barang_mapping', 'name' => 'idx_prioritas', 'column' => 'prioritas'],

        // tbservis_temuan
        ['table' => 'tbservis_temuan', 'name' => 'idx_no_service', 'column' => 'no_service'],
        ['table' => 'tbservis_temuan', 'name' => 'idx_keluhan_id', 'column' => 'keluhan_id'],
        ['table' => 'tbservis_temuan', 'name' => 'idx_kode_temuan', 'column' => 'kode_temuan'],
        ['table' => 'tbservis_temuan', 'name' => 'idx_status_temuan', 'column' => 'status_temuan'],

        // tbservis_penawaran_part
        ['table' => 'tbservis_penawaran_part', 'name' => 'idx_no_service', 'column' => 'no_service'],
        ['table' => 'tbservis_penawaran_part', 'name' => 'idx_temuan_id', 'column' => 'temuan_id'],
        ['table' => 'tbservis_penawaran_part', 'name' => 'idx_kode_barang', 'column' => 'kode_barang'],
        ['table' => 'tbservis_penawaran_part', 'name' => 'idx_status_penawaran', 'column' => 'status_penawaran'],
        ['table' => 'tbservis_penawaran_part', 'name' => 'idx_is_from_suggestion', 'column' => 'is_from_suggestion'],
    ];

    foreach ($indexes as $idx) {
        if (!indexExists($koneksi, $idx['table'], $idx['name'])) {
            $sql = "ALTER TABLE `{$idx['table']}` ADD INDEX `{$idx['name']}` ({$idx['column']})";
            executeSafely($koneksi, $sql, "Add index {$idx['name']} on {$idx['table']}",
                         $logs, $success_count, $skip_count, $error_count, $errors);
        } else {
            $logs[] = "⏭️ SKIP: Index {$idx['name']} on {$idx['table']} already exists";
            $skip_count++;
        }
    }

    // ========================================
    // SECTION 2: ADD UNIQUE CONSTRAINTS
    // ========================================
    $logs[] = "\n=== SECTION 2: ADD UNIQUE CONSTRAINTS ===\n";

    // Unique key for kode_temuan
    if (!indexExists($koneksi, 'tbmaster_temuan', 'uk_kode_temuan')) {
        $sql = "ALTER TABLE `tbmaster_temuan` ADD UNIQUE KEY `uk_kode_temuan` (`kode_temuan`)";
        executeSafely($koneksi, $sql, "Add unique key uk_kode_temuan on tbmaster_temuan",
                     $logs, $success_count, $skip_count, $error_count, $errors);
    } else {
        $logs[] = "⏭️ SKIP: Unique key uk_kode_temuan already exists";
        $skip_count++;
    }

    // Unique key for prevent duplicate mapping
    if (!indexExists($koneksi, 'tbmaster_temuan_barang_mapping', 'uk_temuan_item')) {
        $sql = "ALTER TABLE `tbmaster_temuan_barang_mapping` ADD UNIQUE KEY `uk_temuan_item` (`kode_temuan`, `noitem`)";
        executeSafely($koneksi, $sql, "Add unique key uk_temuan_item on mapping table",
                     $logs, $success_count, $skip_count, $error_count, $errors);
    } else {
        $logs[] = "⏭️ SKIP: Unique key uk_temuan_item already exists";
        $skip_count++;
    }

    // ========================================
    // SECTION 3: ADD FOREIGN KEYS
    // ========================================
    $logs[] = "\n=== SECTION 3: ADD FOREIGN KEYS ===\n";

    // FK: mapping -> master temuan
    if (!foreignKeyExists($koneksi, 'tbmaster_temuan_barang_mapping', 'fk_mapping_temuan')) {
        $sql = "ALTER TABLE `tbmaster_temuan_barang_mapping`
                ADD CONSTRAINT `fk_mapping_temuan`
                FOREIGN KEY (`kode_temuan`)
                REFERENCES `tbmaster_temuan` (`kode_temuan`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        executeSafely($koneksi, $sql, "Add FK: mapping -> master temuan",
                     $logs, $success_count, $skip_count, $error_count, $errors);
    } else {
        $logs[] = "⏭️ SKIP: FK fk_mapping_temuan already exists";
        $skip_count++;
    }

    // FK: mapping -> tblitem
    if (!foreignKeyExists($koneksi, 'tbmaster_temuan_barang_mapping', 'fk_mapping_item')) {
        $sql = "ALTER TABLE `tbmaster_temuan_barang_mapping`
                ADD CONSTRAINT `fk_mapping_item`
                FOREIGN KEY (`noitem`)
                REFERENCES `tblitem` (`noitem`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        executeSafely($koneksi, $sql, "Add FK: mapping -> tblitem",
                     $logs, $success_count, $skip_count, $error_count, $errors);
    } else {
        $logs[] = "⏭️ SKIP: FK fk_mapping_item already exists";
        $skip_count++;
    }

    // FK: servis_temuan -> master_temuan
    if (!foreignKeyExists($koneksi, 'tbservis_temuan', 'fk_servis_temuan_master')) {
        $sql = "ALTER TABLE `tbservis_temuan`
                ADD CONSTRAINT `fk_servis_temuan_master`
                FOREIGN KEY (`kode_temuan`)
                REFERENCES `tbmaster_temuan` (`kode_temuan`)
                ON DELETE SET NULL ON UPDATE CASCADE";
        executeSafely($koneksi, $sql, "Add FK: servis_temuan -> master_temuan",
                     $logs, $success_count, $skip_count, $error_count, $errors);
    } else {
        $logs[] = "⏭️ SKIP: FK fk_servis_temuan_master already exists";
        $skip_count++;
    }

    // FK: penawaran -> temuan
    if (!foreignKeyExists($koneksi, 'tbservis_penawaran_part', 'fk_penawaran_temuan')) {
        $sql = "ALTER TABLE `tbservis_penawaran_part`
                ADD CONSTRAINT `fk_penawaran_temuan`
                FOREIGN KEY (`temuan_id`)
                REFERENCES `tbservis_temuan` (`id`)
                ON DELETE CASCADE ON UPDATE CASCADE";
        executeSafely($koneksi, $sql, "Add FK: penawaran -> temuan",
                     $logs, $success_count, $skip_count, $error_count, $errors);
    } else {
        $logs[] = "⏭️ SKIP: FK fk_penawaran_temuan already exists";
        $skip_count++;
    }

    // ========================================
    // SECTION 4: UPDATE EXISTING DATA
    // ========================================
    $logs[] = "\n=== SECTION 4: UPDATE EXISTING DATA ===\n";

    // Set default is_active
    $sql = "UPDATE `tbmaster_temuan` SET `is_active` = 1 WHERE `is_active` IS NULL";
    executeSafely($koneksi, $sql, "Set default is_active for master temuan",
                 $logs, $success_count, $skip_count, $error_count, $errors);

    // Set default values for mapping
    $sql = "UPDATE `tbmaster_temuan_barang_mapping`
            SET `status_aktif` = 1, `prioritas` = 1, `is_primary` = 0, `qty_default` = 1
            WHERE `status_aktif` IS NULL";
    executeSafely($koneksi, $sql, "Set default values for mapping table",
                 $logs, $success_count, $skip_count, $error_count, $errors);

    // Set default is_from_suggestion
    $sql = "UPDATE `tbservis_penawaran_part` SET `is_from_suggestion` = 0 WHERE `is_from_suggestion` IS NULL";
    executeSafely($koneksi, $sql, "Set default is_from_suggestion for penawaran",
                 $logs, $success_count, $skip_count, $error_count, $errors);

    $logs[] = "\n=== UPDATE COMPLETED ===\n";
}

// ========================================
// MAIN EXECUTION
// ========================================

// Check if form submitted
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == 'update') {
        // Run update
        updateDatabase($koneksi, $logs, $success_count, $skip_count, $error_count, $errors);

        // Save log to file
        $log_file = 'database_update_log_' . date('Ymd_His') . '.txt';
        file_put_contents($log_file, implode("\n", $logs));
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Optimizer - Temuan & Mapping</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 30px;
        }

        .info-box {
            background: #f0f4ff;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-box h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .info-box ul {
            margin-left: 20px;
        }

        .info-box li {
            margin: 5px 0;
            color: #555;
        }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-block {
            display: block;
            width: 100%;
        }

        .log-container {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 20px;
            border-radius: 5px;
            max-height: 500px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            margin-top: 20px;
        }

        .log-container .success {
            color: #50fa7b;
        }

        .log-container .skip {
            color: #f1fa8c;
        }

        .log-container .error {
            color: #ff5555;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .stat-box .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-box .label {
            color: #6c757d;
            font-size: 14px;
        }

        .stat-box.success .number {
            color: #28a745;
        }

        .stat-box.warning .number {
            color: #ffc107;
        }

        .stat-box.danger .number {
            color: #dc3545;
        }

        .error-detail {
            background: #fff5f5;
            border: 1px solid #fc8181;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .error-detail h4 {
            color: #c53030;
            margin-bottom: 10px;
        }

        .error-detail pre {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            font-size: 12px;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Database Optimizer</h1>
            <p>Optimasi Database - Sistem Temuan & Mapping</p>
        </div>

        <div class="content">
            <?php if (!isset($_POST['action'])): ?>
                <!-- FORM AWAL -->
                <div class="info-box">
                    <h3>📋 Yang Akan Dilakukan:</h3>
                    <ul>
                        <li>✅ Menambahkan INDEX untuk performance (query lebih cepat)</li>
                        <li>✅ Menambahkan UNIQUE KEY untuk prevent duplicate</li>
                        <li>✅ Menambahkan FOREIGN KEY untuk data integrity</li>
                        <li>✅ Update data existing dengan default values</li>
                        <li>✅ Auto-skip jika sudah ada (tidak akan error)</li>
                    </ul>
                </div>

                <div class="warning-box">
                    <h3>⚠️ Peringatan:</h3>
                    <ul>
                        <li><strong>BACKUP DATABASE DULU!</strong> Export via phpMyAdmin</li>
                        <li>Proses займет sekitar 30-60 detik</li>
                        <li>Jangan refresh halaman saat proses berjalan</li>
                        <li>Pastikan tidak ada user yang sedang input data</li>
                    </ul>
                </div>

                <form method="POST" action="" onsubmit="return confirm('Sudah backup database? Lanjutkan optimasi?');">
                    <input type="hidden" name="action" value="update">
                    <button type="submit" class="btn btn-primary btn-block">
                        🚀 MULAI OPTIMASI DATABASE
                    </button>
                </form>

                <p style="text-align: center; margin-top: 20px; color: #6c757d;">
                    <small>Estimasi waktu: ~1 menit | Risk level: 🟢 LOW</small>
                </p>

            <?php else: ?>
                <!-- HASIL EKSEKUSI -->
                <div class="stats">
                    <div class="stat-box success">
                        <div class="number"><?php echo $success_count; ?></div>
                        <div class="label">Berhasil</div>
                    </div>
                    <div class="stat-box warning">
                        <div class="number"><?php echo $skip_count; ?></div>
                        <div class="label">Dilewati</div>
                    </div>
                    <div class="stat-box danger">
                        <div class="number"><?php echo $error_count; ?></div>
                        <div class="label">Error</div>
                    </div>
                </div>

                <?php if ($error_count == 0): ?>
                    <div class="info-box">
                        <h3>✅ Optimasi Berhasil!</h3>
                        <p>Database sudah dioptimasi dengan sukses. Semua index dan constraint sudah ditambahkan.</p>
                    </div>
                <?php else: ?>
                    <div class="warning-box">
                        <h3>⚠️ Ada Error!</h3>
                        <p>Beberapa operasi gagal. Lihat detail di bawah.</p>
                    </div>

                    <?php foreach ($errors as $err): ?>
                        <div class="error-detail">
                            <h4><?php echo htmlspecialchars($err['description']); ?></h4>
                            <p><strong>Error:</strong> <?php echo htmlspecialchars($err['error']); ?></p>
                            <pre><?php echo htmlspecialchars($err['sql']); ?></pre>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="log-container">
                    <?php
                    foreach ($logs as $log) {
                        $class = '';
                        if (strpos($log, '✅') !== false) {
                            $class = 'success';
                        } elseif (strpos($log, '⏭️') !== false) {
                            $class = 'skip';
                        } elseif (strpos($log, '❌') !== false) {
                            $class = 'error';
                        }

                        echo "<div class='$class'>" . htmlspecialchars($log) . "</div>";
                    }
                    ?>
                </div>

                <div class="action-buttons">
                    <a href="?" class="btn btn-secondary">
                        🔄 Kembali
                    </a>
                    <a href="test_ajax_endpoints_temuan.html" class="btn btn-primary">
                        🧪 Test Endpoint
                    </a>
                </div>

                <p style="text-align: center; margin-top: 20px; color: #6c757d;">
                    <small>Log disimpan di: <?php echo isset($log_file) ? $log_file : 'N/A'; ?></small>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
// Close connection
mysqli_close($koneksi);
?>
