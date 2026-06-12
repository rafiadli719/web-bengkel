<?php
session_start();

// Simple authentication for database updater
$auth_required = false; // Set to true for production, false for easy access
$auth_username = 'admin';
$auth_password = 'update123'; // Change this password for security

// Check basic authentication
if ($auth_required) {
    if (!isset($_SERVER['PHP_AUTH_USER']) ||
        $_SERVER['PHP_AUTH_USER'] != $auth_username ||
        $_SERVER['PHP_AUTH_PW'] != $auth_password) {

        header('WWW-Authenticate: Basic realm="Database Updater"');
        header('HTTP/1.0 401 Unauthorized');
        echo '<h1>401 Unauthorized</h1>';
        echo '<p>You need valid credentials to access this database updater.</p>';
        echo '<p><strong>Username:</strong> admin<br><strong>Password:</strong> update123</p>';
        exit;
    }
}

include "../config/koneksi.php";

$message = '';
$message_type = '';
$executed_queries = [];
$failed_queries = [];

// Execute database update
if(isset($_POST['execute_update'])) {
    // Read the SQL file
    $sql_file = 'database_update_user_management.sql';

    if(!file_exists($sql_file)) {
        $message = "File database_update_user_management.sql tidak ditemukan!";
        $message_type = "danger";
    } else {
        $sql_content = file_get_contents($sql_file);

        // Remove comments and split into individual queries
        $sql_lines = explode("\n", $sql_content);
        $current_query = '';
        $queries = [];

        foreach($sql_lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if(empty($line) || substr($line, 0, 2) == '--' || substr($line, 0, 1) == '#') {
                continue;
            }

            $current_query .= $line . "\n";

            // If line ends with semicolon, it's end of query
            if(substr($line, -1) == ';') {
                $queries[] = trim($current_query);
                $current_query = '';
            }
        }

        // Execute queries one by one
        $success_count = 0;
        $total_queries = count($queries);

        mysqli_autocommit($koneksi, false); // Start transaction

        foreach($queries as $query) {
            if(!empty($query)) {
                $result = mysqli_query($koneksi, $query);
                if($result) {
                    $success_count++;
                    $executed_queries[] = substr($query, 0, 100) . '...'; // Store first 100 chars
                } else {
                    $failed_queries[] = [
                        'query' => substr($query, 0, 100) . '...',
                        'error' => mysqli_error($koneksi)
                    ];
                }
            }
        }

        if(count($failed_queries) == 0) {
            mysqli_commit($koneksi); // Commit if all success
            $message = "Database berhasil diupdate! $success_count dari $total_queries query berhasil dieksekusi.";
            $message_type = "success";
        } else {
            mysqli_rollback($koneksi); // Rollback if any failed
            $message = "Update database gagal! " . count($failed_queries) . " query gagal dieksekusi.";
            $message_type = "danger";
        }

        mysqli_autocommit($koneksi, true); // Restore autocommit
    }
}

// Check if tables already exist
$tables_exist = false;
$check_tables = ['tb_user_roles', 'tb_user_mekanik_mapping', 'tb_permissions', 'tb_user_activity_log'];
$existing_tables = [];

foreach($check_tables as $table) {
    $result = mysqli_query($koneksi, "SHOW TABLES LIKE '$table'");
    if(mysqli_num_rows($result) > 0) {
        $existing_tables[] = $table;
    }
}

if(count($existing_tables) > 0) {
    $tables_exist = true;
}

// Check if new columns exist in tbuser
$new_columns_exist = false;
$check_columns = ['role_name', 'department', 'is_active', 'last_login'];
$existing_columns = [];

$result = mysqli_query($koneksi, "DESCRIBE tbuser");
$current_columns = [];
while($row = mysqli_fetch_assoc($result)) {
    $current_columns[] = $row['Field'];
}

foreach($check_columns as $column) {
    if(in_array($column, $current_columns)) {
        $existing_columns[] = $column;
    }
}

if(count($existing_columns) > 0) {
    $new_columns_exist = true;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Database Updater - User Management System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & FontAwesome -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/font-awesome/4.5.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/ace.min.css">

    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-success { background: #d4edda; color: #155724; }
        .status-danger { background: #f8d7da; color: #721c24; }
        .status-warning { background: #fff3cd; color: #856404; }
        .query-log {
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>

<body class="no-skin">
    <div class="main-container">
        <div class="main-content">
            <div class="main-content-inner">
                <div class="page-content">
                    <div class="container-fluid">

                        <!-- Header -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="page-header">
                                    <h1>
                                        <i class="ace-icon fa fa-database blue"></i>
                                        Database Updater
                                        <small class="text-muted">User Management System</small>
                                    </h1>
                                </div>
                            </div>
                        </div>

                        <!-- Alert Messages -->
                        <?php if(!empty($message)): ?>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible">
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                    <i class="fa fa-<?php echo $message_type == 'success' ? 'check' : 'exclamation-triangle'; ?>"></i>
                                    <?php echo $message; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Status Check -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-blue">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-check-square-o"></i>
                                            Database Status Check
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5><i class="fa fa-table"></i> New Tables Status:</h5>
                                                    <ul class="list-unstyled">
                                                        <?php foreach($check_tables as $table): ?>
                                                        <li>
                                                            <?php if(in_array($table, $existing_tables)): ?>
                                                            <span class="status-badge status-success">
                                                                <i class="fa fa-check"></i> EXISTS
                                                            </span>
                                                            <?php else: ?>
                                                            <span class="status-badge status-danger">
                                                                <i class="fa fa-times"></i> MISSING
                                                            </span>
                                                            <?php endif; ?>
                                                            <code><?php echo $table; ?></code>
                                                        </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>

                                                <div class="col-md-6">
                                                    <h5><i class="fa fa-columns"></i> New Columns Status (tbuser):</h5>
                                                    <ul class="list-unstyled">
                                                        <?php foreach($check_columns as $column): ?>
                                                        <li>
                                                            <?php if(in_array($column, $existing_columns)): ?>
                                                            <span class="status-badge status-success">
                                                                <i class="fa fa-check"></i> EXISTS
                                                            </span>
                                                            <?php else: ?>
                                                            <span class="status-badge status-danger">
                                                                <i class="fa fa-times"></i> MISSING
                                                            </span>
                                                            <?php endif; ?>
                                                            <code><?php echo $column; ?></code>
                                                        </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="space-10"></div>

                                            <div class="alert alert-info">
                                                <i class="fa fa-info-circle"></i>
                                                <strong>Status:</strong>
                                                <?php if($tables_exist && $new_columns_exist): ?>
                                                    <span class="text-success">Database sudah terupdate! Update mungkin tidak diperlukan.</span>
                                                <?php elseif($tables_exist || $new_columns_exist): ?>
                                                    <span class="text-warning">Database sebagian sudah terupdate. Silakan jalankan update untuk melengkapi.</span>
                                                <?php else: ?>
                                                    <span class="text-danger">Database belum terupdate. Silakan jalankan update sekarang.</span>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Update Execution -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-green">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-bolt"></i>
                                            Database Update Execution
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">

                                            <div class="alert alert-warning">
                                                <i class="fa fa-warning"></i>
                                                <strong>PERHATIAN:</strong>
                                                Pastikan Anda sudah membackup database sebelum menjalankan update ini.
                                                Update akan mengubah struktur database dan data existing.
                                            </div>

                                            <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin menjalankan database update? Pastikan sudah backup database!')">
                                                <div class="form-group">
                                                    <label>File SQL yang akan dieksekusi:</label>
                                                    <div class="input-group">
                                                        <span class="input-group-addon">
                                                            <i class="fa fa-file-code-o"></i>
                                                        </span>
                                                        <input type="text" class="form-control" value="database_update_user_management.sql" readonly>
                                                        <span class="input-group-addon">
                                                            <?php if(file_exists('database_update_user_management.sql')): ?>
                                                            <span class="text-success"><i class="fa fa-check"></i> Found</span>
                                                            <?php else: ?>
                                                            <span class="text-danger"><i class="fa fa-times"></i> Not Found</span>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="form-actions">
                                                    <?php if(file_exists('database_update_user_management.sql')): ?>
                                                    <button type="submit" name="execute_update" class="btn btn-success btn-lg">
                                                        <i class="ace-icon fa fa-bolt bigger-110"></i>
                                                        Execute Database Update
                                                    </button>
                                                    <?php else: ?>
                                                    <button type="button" class="btn btn-danger btn-lg" disabled>
                                                        <i class="ace-icon fa fa-times"></i>
                                                        SQL File Not Found
                                                    </button>
                                                    <?php endif; ?>

                                                    <a href="user_management.php" class="btn btn-primary">
                                                        <i class="ace-icon fa fa-users"></i>
                                                        Go to User Management
                                                    </a>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Execution Log -->
                        <?php if(count($executed_queries) > 0 || count($failed_queries) > 0): ?>
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-purple">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-list"></i>
                                            Execution Log
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">

                                            <?php if(count($executed_queries) > 0): ?>
                                            <div class="alert alert-success">
                                                <h5><i class="fa fa-check-circle"></i> Successfully Executed Queries (<?php echo count($executed_queries); ?>):</h5>
                                                <div class="query-log">
                                                    <?php foreach($executed_queries as $query): ?>
                                                    <div><i class="fa fa-check text-success"></i> <?php echo htmlspecialchars($query); ?></div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <?php if(count($failed_queries) > 0): ?>
                                            <div class="alert alert-danger">
                                                <h5><i class="fa fa-times-circle"></i> Failed Queries (<?php echo count($failed_queries); ?>):</h5>
                                                <div class="query-log">
                                                    <?php foreach($failed_queries as $failed): ?>
                                                    <div>
                                                        <i class="fa fa-times text-danger"></i>
                                                        <?php echo htmlspecialchars($failed['query']); ?>
                                                        <br><small class="text-danger">Error: <?php echo htmlspecialchars($failed['error']); ?></small>
                                                    </div>
                                                    <hr>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Instructions -->
                        <div class="row">
                            <div class="col-xs-12">
                                <div class="widget-box">
                                    <div class="widget-header widget-header-info">
                                        <h4 class="widget-title">
                                            <i class="ace-icon fa fa-info-circle"></i>
                                            Instructions
                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <h5>Yang akan diupdate:</h5>
                                                    <ul>
                                                        <li>Menambahkan kolom baru di tabel <code>tbuser</code></li>
                                                        <li>Menambahkan kolom baru di tabel <code>tblmekanik</code></li>
                                                        <li>Membuat tabel baru untuk role management</li>
                                                        <li>Membuat tabel mapping user-mekanik</li>
                                                        <li>Menggabungkan role CS & Kasir</li>
                                                    </ul>
                                                </div>
                                                <div class="col-md-6">
                                                    <h5>Setelah update berhasil:</h5>
                                                    <ul>
                                                        <li>Akses halaman <a href="user_management.php">User Management</a></li>
                                                        <li>Akses halaman <a href="mekanik_management.php">Mechanic Management</a></li>
                                                        <li>Cek role CS & Kasir sudah tergabung</li>
                                                        <li>Test semua fungsi CRUD</li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery-2.1.4.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/ace.min.js"></script>

    <script>
        // Auto-hide alerts after 10 seconds
        setTimeout(function() {
            $('.alert-dismissible').fadeOut('slow');
        }, 10000);
    </script>
</body>
</html>