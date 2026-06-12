<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include 'database_connection.php';

// MySQLi connection for additional queries if needed
$mysqli = new mysqli('localhost', 'fitmotor_LOGIN', 'Sayalupa12', 'fitmotor_maintance-beta');

// Redirect to login if user session is not set
if (!isset($_SESSION['nama_karyawan']) || !isset($_SESSION['kode_karyawan'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch current user data from session
$profil_karyawan = ($_SESSION['nama_karyawan'] ?? '') . ' (' . ($_SESSION['kode_karyawan'] ?? '') . ')';
$cabang_user = $_SESSION['nama_cabang'] ?? '';
$role = $_SESSION['role'] ?? '';

// Define access roles
$is_super_admin = $role === 'super_admin';
$is_admin = $role === 'admin';
$is_user = $role === 'user';
$is_kasir = $role === 'kasir';

// Retrieve kode_cabang and nama_cabang if missing from session
if (!isset($_SESSION['kode_cabang']) || !isset($_SESSION['nama_cabang'])) {
    $kode_karyawan = $_SESSION['kode_karyawan'] ?? '';
    $sql = "SELECT u.kode_karyawan, u.nama_karyawan, u.role, u.kode_cabang, c.nama_cabang 
            FROM users u 
            LEFT JOIN cabang c ON u.kode_cabang = c.kode_cabang 
            WHERE u.kode_karyawan = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$kode_karyawan]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Store both `kode_cabang` and `nama_cabang` in session
    if ($user) {
        $_SESSION['kode_cabang'] = $user['kode_cabang'] ?? '';
        $_SESSION['nama_cabang'] = $user['nama_cabang'] ?? '';
    }
}

// Populate branches dropdown using multi-database connection
include 'multi_database_connection.php';
$cabang_options = "";

try {
    $multiDB = new MultiDatabaseConnection();
    $branches = $multiDB->getAllBranches();
    
    if (!empty($branches)) {
        foreach ($branches as $branch) {
            $selected = ($branch === $cabang_user) ? 'selected' : '';
            $cabang_options .= '<option value="' . htmlspecialchars($branch) . '" ' . $selected . '>' 
                              . htmlspecialchars($branch) . '</option>';
        }
    }
    $multiDB->closeConnections();
} catch (Exception $e) {
    error_log("Error loading branches: " . $e->getMessage());
    // Fallback to single database
    $stmt = $pdo->prepare("SELECT DISTINCT nama_cabang FROM users");
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($result)) {
        foreach ($result as $row) {
            $selected = ($row['nama_cabang'] === $cabang_user) ? 'selected' : '';
            $cabang_options .= '<option value="' . htmlspecialchars($row['nama_cabang'] ?? '') . '" ' . $selected . '>' 
                              . htmlspecialchars($row['nama_cabang'] ?? '') . '</option>';
        }
    }
}

// Dynamic Sidebar visibility based on user roles and permissions - FIXED QUERY
$query = "
    SELECT ds.id, ds.sidebar_name, ds.sidebar_url, ds.parent_id
    FROM dynamic_sidebars ds
    JOIN user_sidebar_settings uss ON ds.id = uss.sidebar_id
    WHERE uss.kode_karyawan = ? AND uss.is_visible = 1
    ORDER BY ds.parent_id ASC, ds.id ASC";
$stmt = $pdo->prepare($query);
$stmt->execute([$_SESSION['kode_karyawan'] ?? '']);
$sidebars = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #667eea;
            --primary-hover: #5a6fd8;
            --secondary-color: #1e293b;
            --sidebar-bg: #0f172a;
            --background-light: #f8fafc;
            --text-light: #f1f5f9;
            --text-dark: #334155;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --card-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --card-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--background-light) 0%, #e2e8f0 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
        }

        /* FIXED: Simplified Sidebar Styles */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: var(--card-shadow-lg);
        }

        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
        }

        .sidebar-header h1 {
            color: white;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-header i {
            font-size: 20px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-item {
            margin-bottom: 4px;
        }

        /* FIXED: Simplified sidebar links */
        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            transition: var(--transition);
            position: relative;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }

        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }

        .sidebar-link.active {
            background: linear-gradient(90deg, var(--primary-color), transparent);
            color: white;
        }

        .sidebar-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }

        /* FIXED: Simplified sub-sidebar */
        .sub-sidebar {
            display: none;
            background: rgba(0, 0, 0, 0.2);
            border-left: 2px solid var(--primary-color);
            margin-left: 20px;
        }

        .sub-sidebar.active {
            display: block;
        }

        .sub-sidebar .sidebar-link {
            padding: 10px 20px 10px 40px;
            font-size: 13px;
        }

        .main-content {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
            transition: var(--transition);
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            box-shadow: var(--card-shadow);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 4px;
        }

        .header-subtitle {
            color: var(--text-muted);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-subtitle i {
            color: var(--primary-color);
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: var(--background-light);
            border-radius: 50px;
            border: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .user-info h3 {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .user-info p {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .content {
            padding: 30px;
        }

        .welcome-card {
            background: white;
            border-radius: 16px;
            padding: 32px;
            margin-bottom: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .welcome-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        }

        .welcome-card h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .welcome-text {
            color: var(--text-muted);
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .info-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 20px;
        }

        .info-tag {
            background: var(--background-light);
            color: var(--text-dark);
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-tag i {
            color: var(--primary-color);
        }

        .instruction-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            padding: 24px;
            margin-top: 24px;
            border-left: 4px solid var(--primary-color);
        }

        .instruction-card h3 {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .instruction-list {
            list-style: none;
            space-y: 8px;
        }

        .instruction-list li {
            color: var(--text-dark);
            font-size: 14px;
            padding: 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .instruction-list li i {
            color: var(--primary-color);
            width: 16px;
        }

        .management-section {
            background: white;
            border-radius: 16px;
            padding: 32px;
            margin-top: 32px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            display: none;
        }

        .management-section.active {
            display: block;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-dark);
        }

        .btn-back {
            background: var(--background-light);
            color: var(--text-dark);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }

        .btn-back:hover {
            background: var(--border-color);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: var(--transition);
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            width: 100%;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: var(--card-shadow-lg);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-secondary {
            background: var(--background-light);
            color: var(--text-dark);
            border: 1px solid var(--border-color);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border-color);
            margin-top: 24px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th {
            background: var(--background-light);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
            border-bottom: 1px solid var(--border-color);
        }

        .table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .table tbody tr:hover {
            background: var(--background-light);
        }

        .popup {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .popup-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: var(--card-shadow-lg);
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .popup-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
        }

        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
        }

        .logout-btn {
            width: 100%;
            background: var(--danger-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .logout-btn:hover {
            background: #dc2626;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .toggle-button {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border: none;
        }

        .toggle-button:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        code {
            background: var(--background-light);
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 12px;
            color: var(--primary-color);
        }


        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar-toggle {
                display: block;
            }

            .main-content {
                margin-left: 0;
            }

            .header {
                padding: 16px 20px;
            }

            .content {
                padding: 20px;
            }

            .header-content {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .user-profile {
                width: 100%;
                justify-content: flex-start;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top: 3px solid #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <button class="sidebar-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h1><i class="fas fa-tachometer-alt"></i> Dashboard User</h1>
        </div>
        
        <div class="sidebar-menu">
            <?php foreach ($sidebars as $sidebar): ?>
                <?php if ($sidebar['parent_id'] === null): ?>
                    <div class="sidebar-item" data-sidebar-id="<?php echo htmlspecialchars($sidebar['id'] ?? ''); ?>">
                        <a href="#" class="sidebar-link dynamic-link" data-url="<?php echo htmlspecialchars($sidebar['sidebar_url'] ?? ''); ?>">
                            <i class="fas fa-folder"></i>
                            <span><?php echo htmlspecialchars($sidebar['sidebar_name'] ?? ''); ?></span>
                        </a>
                        <div class="sub-sidebar">
                            <ul>
                                <?php foreach ($sidebars as $subSidebar): ?>
                                    <?php if ($subSidebar['parent_id'] === $sidebar['id']): ?>
                                        <li class="sidebar-item" data-sidebar-id="<?php echo htmlspecialchars($subSidebar['id'] ?? ''); ?>">
                                            <a href="#" class="sidebar-link dynamic-link" data-url="<?php echo htmlspecialchars($subSidebar['sidebar_url'] ?? ''); ?>">
                                                <i class="fas fa-file"></i>
                                                <?php echo htmlspecialchars($subSidebar['sidebar_name'] ?? ''); ?>
                                            </a>
                                            <div class="sub-sidebar">
                                                <ul>
                                                    <?php foreach ($sidebars as $nestedSidebar): ?>
                                                        <?php if ($nestedSidebar['parent_id'] === $subSidebar['id']): ?>
                                                            <li class="sidebar-item">
                                                                <a href="#" class="sidebar-link dynamic-link" data-url="<?php echo htmlspecialchars($nestedSidebar['sidebar_url'] ?? ''); ?>">
                                                                    <i class="fas fa-chevron-right"></i>
                                                                    <?php echo htmlspecialchars($nestedSidebar['sidebar_name'] ?? ''); ?>
                                                                </a>
                                                            </li>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <?php if ($is_super_admin || $is_admin): ?>
                <a href="../../web_kasir/website_kasir/admin_dashboard.php" class="sidebar-link" target="_blank">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin Panel</span>
                    <i class="fas fa-external-link-alt" style="margin-left: auto; font-size: 10px; opacity: 0.7;"></i>
                </a>
            <?php endif; ?>
            
            <?php if ($is_kasir): ?>
                <a href="../../web_kasir/website_kasir/index_kasir.php" class="sidebar-link" target="_blank">
                    <i class="fas fa-cash-register"></i>
                    <span>Dashboard Kasir</span>
                    <i class="fas fa-external-link-alt" style="margin-left: auto; font-size: 10px; opacity: 0.7;"></i>
                </a>
            <?php endif; ?>
            
            <a href="../password/pengaturan_user.php" class="sidebar-link" target="_blank">
                <i class="fas fa-user-cog"></i>
                <span>Pengaturan User</span>
                <i class="fas fa-external-link-alt" style="margin-left: auto; font-size: 10px; opacity: 0.7;"></i>
            </a>
            
            <?php if ($is_super_admin): ?>
                <button onclick="toggleUserManagement(); return false;" class="sidebar-link">
                    <i class="fas fa-users-cog"></i>
                    <span>Pengaturan User Otoritas</span>
                </button>
                <button onclick="toggleSideBar(); return false;" class="sidebar-link">
                    <i class="fas fa-list"></i>
                    <span>Pengaturan Sidebar</span>
                </button>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-footer">
            <form action="../logout.php" method="POST">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </button>
            </form>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <div class="header-content">
                <div class="header-title">
                    <h1>Selamat datang, <?php echo htmlspecialchars($profil_karyawan); ?></h1>
                    <div class="header-subtitle">
                        <i class="fas fa-building"></i>
                        <span>Cabang: <?php echo htmlspecialchars($_SESSION['kode_cabang'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($_SESSION['nama_cabang'] ?? 'N/A'); ?></span>
                    </div>
                </div>
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($profil_karyawan, 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($_SESSION['nama_karyawan'] ?? 'User'); ?></h3>
                        <p><?php echo htmlspecialchars($role); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="welcome-card">
                <h2>🚀 Selamat datang di Dashboard!</h2>
                <p class="welcome-text">
                    Anda masuk sebagai <strong><?php echo htmlspecialchars($role); ?></strong> untuk cabang: 
                    <strong><?php echo htmlspecialchars($_SESSION['kode_cabang'] ?? 'N/A'); ?> - <?php echo htmlspecialchars($_SESSION['nama_cabang'] ?? 'N/A'); ?></strong>
                </p>
                <p class="welcome-text">Silakan mulai melakukan pekerjaan Anda.</p>
                
                <div class="info-tags">
                    <div class="info-tag">
                        <i class="fas fa-user"></i>
                        Role: <?php echo htmlspecialchars($role); ?>
                    </div>
                    <div class="info-tag">
                        <i class="fas fa-building"></i>
                        <?php echo htmlspecialchars($_SESSION['nama_cabang'] ?? 'N/A'); ?>
                    </div>
                    <div class="info-tag">
                        <i class="fas fa-calendar"></i>
                        <?php echo date('d F Y'); ?>
                    </div>
                </div>

                <div class="instruction-card">
                    <h3><i class="fas fa-lightbulb"></i> Panduan Penggunaan</h3>
                    <ul class="instruction-list">
                        <li><i class="fas fa-mouse-pointer"></i> Klik satu kali pada sidebar untuk membuka sub-sidebar</li>
                        <li><i class="fas fa-mouse"></i> Klik dua kali pada sidebar untuk membuka halaman di tab baru</li>
                        <li><i class="fas fa-external-link-alt"></i> Link dengan ikon eksternal otomatis terbuka di tab baru</li>
                        <li><i class="fas fa-window-restore"></i> Dashboard tetap terbuka saat mengakses halaman lain</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- User Management Section -->
        <div class="management-section" id="userManagement">
            <div class="section-header">
                <h2 class="section-title">👥 Manajemen Pengguna</h2>
                <button class="btn-back" onclick="toggleUserManagement()">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
            </div>
            
            <div class="form-group">
                <label for="branchSelect" class="form-label">Pilih Cabang:</label>
                <select id="branchSelect" class="form-control" onchange="loadEmployees()">
                    <option value="">Pilih Cabang</option>
                    <?php echo $cabang_options; ?>
                </select>
                <small style="color: #666; font-size: 12px;">
                    <a href="debug_database.php" target="_blank">🔧 Debug Database Connection</a>
                </small>
            </div>
            
            <div class="form-group">
                <label for="employeeSelect" class="form-label">Pilih Karyawan:</label>
                <select id="employeeSelect" class="form-control" onchange="loadPermissions()">
                    <option value="">Pilih Karyawan</option>
                </select>
            </div>
            
            <div id="permissionsCheckboxes" class="form-group"></div>
            
            <button class="btn btn-primary" onclick="saveUserSettings()">
                <i class="fas fa-save"></i> Simpan Pengaturan
            </button>
        </div>

        <!-- Sidebar Settings Section -->
        <div class="management-section" id="pengaturanSidebar">
            <div class="section-header">
                <h2 class="section-title">⚙️ Pengaturan Sidebar</h2>
                <button class="btn-back" onclick="toggleSideBar()">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Info:</strong> Fungsi ini ../ untuk kembali ke folder sebelumnya
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Sidebar</th>
                            <th>URL Sidebar</th>
                            <th>Parent ID</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            // Koneksi ke database menggunakan PDO
                            $pdo_table = new PDO('mysql:host=localhost;dbname=fitmotor_maintance-beta', 'fitmotor_LOGIN', 'Sayalupa12');
                            $pdo_table->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                            // Eksekusi kueri untuk mengambil data sidebars
                            $query = "
                                SELECT ds.id, ds.sidebar_name, ds.sidebar_url, ds.parent_id
                                FROM dynamic_sidebars ds
                                ORDER BY ds.parent_id ASC, ds.id ASC
                            ";
                            $stmt = $pdo_table->query($query);

                            // Iterasi hasil kueri
                            if ($stmt->rowCount() > 0) {
                                while ($sidebar = $stmt->fetch(PDO::FETCH_ASSOC)): 
                                    $url_status = '';
                                    if (empty($sidebar['sidebar_url']) || $sidebar['sidebar_url'] === 'null' || $sidebar['sidebar_url'] === 'undefined') {
                                        $url_status = ' style="background: #fef2f2; color: #991b1b;"';
                                    }
                                ?>
                                    <tr<?php echo $url_status; ?>>
                                        <td><?php echo htmlspecialchars($sidebar['id'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($sidebar['sidebar_name'] ?? ''); ?></td>
                                        <td>
                                            <?php if (empty($sidebar['sidebar_url']) || $sidebar['sidebar_url'] === 'null'): ?>
                                                <span style="color: #ef4444; font-weight: bold;">⚠️ URL Kosong/Null</span>
                                            <?php else: ?>
                                                <code><?php echo htmlspecialchars($sidebar['sidebar_url'] ?? ''); ?></code>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($sidebar['parent_id'] ?? '-'); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <button class="btn btn-warning btn-sm" onclick="showEditPopup('<?php echo htmlspecialchars($sidebar['sidebar_name'] ?? ''); ?>')">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="showDeletePopup('<?php echo htmlspecialchars($sidebar['sidebar_name'] ?? ''); ?>')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            } else {
                                echo "<tr><td colspan='5' style='text-align: center; color: var(--text-muted);'>Tidak ada data sidebar yang ditemukan.</td></tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='5' style='text-align: center; color: var(--danger-color);'>Koneksi atau kueri gagal: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <div class="action-buttons">
                <button class="toggle-button" onclick="togglePenambahanSidebar()">
                    <i class="fas fa-plus"></i> Tambah Sidebar
                </button>
                <button class="toggle-button" onclick="togglePenambahanSubSidebar()">
                    <i class="fas fa-plus-circle"></i> Tambah Sub Sidebar
                </button>
            </div>
        </div>

        <!-- Form Tambah Sidebar -->
        <div class="management-section" id="PenambahanSidebar">
            <div class="section-header">
                <h2 class="section-title">➕ Tambah Sidebar</h2>
                <button class="btn-back" onclick="togglePenambahanSidebar()">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
            </div>
            
            <form id="addSidebarForm">
                <div class="form-group">
                    <label for="sidebarName" class="form-label">Nama Sidebar:</label>
                    <input type="text" id="sidebarName" name="sidebarName" class="form-control" placeholder="Masukkan nama sidebar" required>
                </div>
                
                <div class="form-group">
                    <label for="sidebarUrl" class="form-label">URL Sidebar:</label>
                    <input type="text" id="sidebarUrl" name="sidebarUrl" class="form-control" placeholder="Masukkan URL sidebar (contoh: ../folder/file.php)" required>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="addSidebar()">
                    <i class="fas fa-plus"></i> Tambah Sidebar
                </button>
            </form>
        </div>

        <!-- Form Tambah Sub Sidebar -->
        <div class="management-section" id="penambahansubsidebar">
            <div class="section-header">
                <h2 class="section-title">➕ Tambah Sub Sidebar</h2>
                <button class="btn-back" onclick="togglePenambahanSubSidebar()">
                    <i class="fas fa-arrow-left"></i> Kembali
                </button>
            </div>
            
            <form id="addSubSidebarForm">
                <div class="form-group">
                    <label for="parentSidebarSelect" class="form-label">Parent Sidebar:</label>
                    <select id="parentSidebarSelect" name="parentSidebarName" class="form-control">
                        <option value="">Pilih Parent Sidebar</option>
                        <?php foreach ($sidebars as $sidebar): ?>
                            <option value="<?php echo htmlspecialchars($sidebar['sidebar_name'] ?? ''); ?>">
                                <?php echo htmlspecialchars($sidebar['sidebar_name'] ?? ''); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subsidebarName" class="form-label">Nama Sub Sidebar:</label>
                    <input type="text" id="subsidebarName" name="subSidebarName" class="form-control" placeholder="Masukkan nama sub sidebar" required>
                </div>
                
                <div class="form-group">
                    <label for="subsidebarUrl" class="form-label">URL Sub Sidebar:</label>
                    <input type="text" id="subsidebarUrl" name="subSidebarUrl" class="form-control" placeholder="Masukkan URL sub sidebar" required>
                </div>
                
                <button type="button" class="btn btn-primary" onclick="addSubSidebar()">
                    <i class="fas fa-plus-circle"></i> Tambah Sub Sidebar
                </button>
            </form>
        </div>
    </div>

    <!-- Pop-up Konfirmasi Edit -->
    <div id="editPopup" class="popup">
        <div class="popup-content">
            <i class="fas fa-edit" style="font-size: 48px; color: var(--warning-color); margin-bottom: 16px;"></i>
            <h3>Konfirmasi Edit</h3>
            <p>Apakah Anda yakin ingin mengedit sidebar ini?</p>
            <div class="popup-actions">
                <button class="btn btn-warning" onclick="confirmEdit('sidebar')">
                    <i class="fas fa-check"></i> Ya, Edit
                </button>
                <button class="btn btn-secondary" onclick="closePopup('editPopup')">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
    </div>

    <!-- Pop-up Konfirmasi Hapus -->
    <div id="deletePopup" class="popup">
        <div class="popup-content">
            <i class="fas fa-exclamation-triangle" style="font-size: 48px; color: var(--danger-color); margin-bottom: 16px;"></i>
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus sidebar ini? <br><strong>Tindakan ini tidak dapat dibatalkan!</strong></p>
            <div class="popup-actions">
                <button class="btn btn-danger" onclick="confirmDelete('sidebar')">
                    <i class="fas fa-trash"></i> Hapus Sidebar
                </button>
                <button class="btn btn-secondary" onclick="closePopup('deletePopup')">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
    </div>

    <script>
        // FIXED: Simplified event handling
        let clickTimer = null;
        let lastClickedElement = null;
        let isDoubleClick = false;

        // FIXED: Initialize after DOM is ready but with simpler approach
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing sidebar...');
            initializeSidebarEvents();
            debugSidebarData();
        });

        // FIXED: Simplified initialization function
        function initializeSidebarEvents() {
            // Handle dynamic sidebar links (data-driven links)
            document.querySelectorAll('.sidebar-link.dynamic-link').forEach(link => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = link.getAttribute('data-url');
                    const isSubSidebar = link.closest('.sub-sidebar') !== null;
                    sidebarClick(url, link, isSubSidebar);
                });
            });

            // Handle static sidebar links with href attribute
            document.querySelectorAll('.sidebar-link[href]:not(.dynamic-link)').forEach(link => {
                // Skip if it's a button or has onclick attribute
                if (link.tagName.toLowerCase() === 'button' || link.hasAttribute('onclick')) {
                    return;
                }
                
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    const url = link.getAttribute('href');
                    console.log('Opening static link in new tab:', url);
                    window.open(url, '_blank');
                    showNotification('Halaman dibuka di tab baru.', 'success');
                });
            });

            console.log('Sidebar events initialized successfully');
        }

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('active');
        }
        
        // FIXED: Improved sidebar click handling with new tab/window option
        function sidebarClick(url, element, isSubSidebar = false) {
            // Validate URL before navigation
            if (!url || url === 'null' || url === 'undefined' || url.trim() === '' || url === '#') {
                console.warn('Invalid URL detected:', url);
                showNotification('URL tidak valid untuk item sidebar ini. Silakan hubungi administrator.', 'warning');
                return;
            }

            const clickedSidebarId = element.parentElement.getAttribute('data-sidebar-id');
            const subSidebar = element.nextElementSibling;

            // Handle double click for sub-sidebar items - open in new tab
            if (isSubSidebar) {
                if (isDoubleClick) {
                    console.log('Opening in new tab:', url);
                    window.open(url, '_blank');
                    showNotification('Halaman dibuka di tab baru.', 'success');
                    isDoubleClick = false;
                    return;
                }

                isDoubleClick = true;
                setTimeout(() => {
                    isDoubleClick = false;
                }, 300);
            }

            // Handle single/double click logic
            if (lastClickedElement === element) {
                clearTimeout(clickTimer);
                if (subSidebar && subSidebar.classList.contains('active')) {
                    subSidebar.classList.remove('active');
                    lastClickedElement = null;
                } else if (!isSubSidebar) {
                    console.log('Opening in new tab:', url);
                    window.open(url, '_blank');
                    showNotification('Halaman dibuka di tab baru.', 'success');
                }
                return;
            }

            lastClickedElement = element;

            clearTimeout(clickTimer);
            clickTimer = setTimeout(function() {
                // Close all open sub-sidebars
                document.querySelectorAll('.sub-sidebar.active').forEach((sidebar) => {
                    sidebar.classList.remove('active');
                });

                // Open current sub-sidebar and its parents
                let currentElement = element.parentElement;
                while (currentElement && currentElement.classList.contains('sidebar-item')) {
                    const currentSubSidebar = currentElement.querySelector('.sub-sidebar');
                    if (currentSubSidebar) {
                        currentSubSidebar.classList.add('active');
                    }
                    currentElement = currentElement.closest('.sidebar-item[data-parent-id]');
                }

                // Show immediate sub-sidebar if exists
                if (subSidebar) {
                    subSidebar.classList.add('active');
                }
            }, 300);
        }

        function debugSidebarData() {
            console.log('=== SIDEBAR DEBUG INFO ===');
            document.querySelectorAll('.sidebar-link').forEach((link, index) => {
                const url = link.getAttribute('data-url') || link.getAttribute('href');
                const text = link.textContent.trim();
                const hasOnclick = link.getAttribute('onclick');
                console.log(`${index + 1}. "${text}" -> URL: "${url}", onclick: ${hasOnclick ? 'yes' : 'no'}`);
                
                if (link.classList.contains('dynamic-link') && (!url || url === 'null' || url === 'undefined' || url.trim() === '')) {
                    console.warn(`⚠️ Invalid URL detected for: "${text}"`);
                }
            });
            console.log('=== END DEBUG INFO ===');
        }

        function addSidebar() {
            const sidebarName = document.getElementById('sidebarName').value;
            const sidebarUrl = document.getElementById('sidebarUrl').value;

            if (sidebarName && sidebarUrl) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'add_sidebar.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        showNotification('Sidebar berhasil ditambahkan.', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification('Gagal menambah sidebar.', 'error');
                    }
                };

                xhr.send('sidebar_name=' + encodeURIComponent(sidebarName) +
                        '&sidebar_url=' + encodeURIComponent(sidebarUrl));
            } else {
                showNotification('Nama dan URL sidebar tidak boleh kosong.', 'warning');
            }
        }

        function addSubSidebar() {
            const subSidebarName = document.getElementById('subsidebarName').value;
            const subSidebarUrl = document.getElementById('subsidebarUrl').value;
            const parentSidebarName = document.getElementById('parentSidebarSelect').value;

            if (parentSidebarName && subSidebarName && subSidebarUrl) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'add_sub_sidebar.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        showNotification('Sub-sidebar berhasil ditambahkan.', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification('Gagal menambahkan sub-sidebar.', 'error');
                    }
                };

                xhr.send('parent_sidebar_name=' + encodeURIComponent(parentSidebarName) +
                          '&sub_sidebar_name=' + encodeURIComponent(subSidebarName) + 
                          '&sub_sidebar_url=' + encodeURIComponent(subSidebarUrl));
            } else {
                showNotification('Silakan pilih sidebar parent dan isi nama serta URL sub-sidebar.', 'warning');
            }
        }

        let currentSidebarName = '';

        function showEditPopup(sidebarName) {
            currentSidebarName = sidebarName;
            document.getElementById('editPopup').style.display = 'flex';
        }

        function showDeletePopup(sidebarName) {
            currentSidebarName = sidebarName;
            document.getElementById('deletePopup').style.display = 'flex';
        }

        function closePopup(popupId) {
            document.getElementById(popupId).style.display = 'none';
        }

        function confirmEdit(type) {
            if (type === 'sidebar') {
                editSidebar(currentSidebarName);
            }
            closePopup('editPopup');
        }

        function confirmDelete(type) {
            if (type === 'sidebar') {
                deleteSidebar(currentSidebarName);
            }
            closePopup('deletePopup');
        }

        function editSidebar(sidebarName) {
            const newSidebarName = prompt("Masukkan nama sidebar baru:", sidebarName);
            const newSidebarUrl = prompt("Masukkan URL sidebar baru:");

            if (newSidebarName && newSidebarUrl) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'edit_sidebar.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        showNotification('Sidebar berhasil diedit.', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification('Gagal mengedit sidebar.', 'error');
                    }
                };

                xhr.send('sidebar_name=' + encodeURIComponent(sidebarName) + 
                        '&new_sidebar_name=' + encodeURIComponent(newSidebarName) + 
                        '&sidebar_url=' + encodeURIComponent(newSidebarUrl));
            }
        }

        function deleteSidebar(sidebarName) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'delete_sidebar.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    showNotification('Sidebar berhasil dihapus.', 'success');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showNotification('Gagal menghapus sidebar.', 'error');
                }
            };

            xhr.send('sidebar_name=' + encodeURIComponent(sidebarName));
        }

        // FIXED: Simplified toggle functions
        function toggleUserManagement() {
            hideAllSections();
            const userManagement = document.getElementById('userManagement');
            userManagement.classList.toggle('active');
        }

        function toggleSideBar() {
            hideAllSections();
            const sidebarSettings = document.getElementById('pengaturanSidebar');
            sidebarSettings.classList.toggle('active');
        }

        function togglePenambahanSidebar() {
            hideAllSections();
            const tambahsidebar = document.getElementById('PenambahanSidebar');
            tambahsidebar.classList.toggle('active');
        }

        function togglePenambahanSubSidebar() {
            hideAllSections();
            const tambahsubsidebar = document.getElementById('penambahansubsidebar');
            tambahsubsidebar.classList.toggle('active');
        }

        function hideAllSections() {
            document.querySelectorAll('.management-section').forEach(section => {
                section.classList.remove('active');
            });
        }

        function loadEmployees() {
            var cabang = document.getElementById('branchSelect').value;
            var employeeSelect = document.getElementById('employeeSelect');

            if (cabang) {
                // Show loading
                employeeSelect.innerHTML = '<option value="">Loading...</option>';
                
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'get_employees_multi.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function () {
                    if (this.status === 200) {
                        var response = this.responseText.trim();
                        console.log('Server response:', response);
                        
                        if (response && response !== '') {
                            employeeSelect.innerHTML = '<option value="">Pilih Karyawan</option>' + response;
                            
                            // Count options (excluding the default "Pilih Karyawan" option)
                            var optionCount = employeeSelect.options.length - 1;
                            if (optionCount > 0) {
                                showNotification(`${optionCount} karyawan berhasil dimuat dari multi-database.`, 'success');
                            } else {
                                showNotification('Tidak ada karyawan ditemukan untuk cabang ini.', 'warning');
                                employeeSelect.innerHTML = '<option value="">Tidak ada karyawan</option>';
                            }
                        } else {
                            showNotification('Server mengembalikan response kosong.', 'warning');
                            employeeSelect.innerHTML = '<option value="">Response kosong</option>';
                        }
                    } else {
                        console.error('HTTP Error:', this.status, this.statusText);
                        showNotification('Terjadi kesalahan HTTP: ' + this.status, 'error');
                        employeeSelect.innerHTML = '<option value="">Error HTTP</option>';
                    }
                };
                
                xhr.onerror = function() {
                    console.error('Network error occurred');
                    showNotification('Terjadi kesalahan jaringan.', 'error');
                    employeeSelect.innerHTML = '<option value="">Network Error</option>';
                };
                
                xhr.send('cabang=' + encodeURIComponent(cabang));
                console.log('Sending request for cabang:', cabang);
            } else {
                employeeSelect.innerHTML = '<option value="">Pilih Cabang dulu</option>';
            }
        }

        function loadPermissions() {
            const employeeId = document.getElementById('employeeSelect').value;
            const permissionsCheckboxes = document.getElementById('permissionsCheckboxes');

            if (!employeeId) {
                showNotification('Silakan pilih karyawan terlebih dahulu.', 'warning');
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_sidebars.php?employee_id=' + employeeId, true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const sidebars = JSON.parse(xhr.responseText);
                        
                        permissionsCheckboxes.innerHTML = '';
                        
                        sidebars.forEach(function(sidebar) {
                            const isVisible = sidebar.is_visible ? 'checked' : '';
                            permissionsCheckboxes.innerHTML += `
                                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                    <input type="checkbox" value="${sidebar.id}" ${isVisible} style="margin: 0;"> 
                                    <span>${sidebar.sidebar_name}</span>
                                </div>
                            `;
                        });

                    } catch (e) {
                        showNotification('Terjadi kesalahan saat memuat izin.', 'error');
                    }
                } else {
                    showNotification('Gagal memuat data sidebars.', 'error');
                }
            };

            xhr.send();
        }

        function saveUserSettings() {
            const employee = document.getElementById('employeeSelect').value;
            const permissionCheckboxes = document.querySelectorAll('#permissionsCheckboxes input[type="checkbox"]');
            const permissions = [];

            permissionCheckboxes.forEach((checkbox) => {
                permissions.push({
                    id: checkbox.value,
                    is_visible: checkbox.checked ? 1 : 0
                });
            });

            if (employee) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'save_user_settings.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

                xhr.onload = function() {
                    if (xhr.status === 200) {
                        showNotification('Pengaturan berhasil disimpan.', 'success');
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showNotification('Gagal menyimpan pengaturan.', 'error');
                    }
                };

                let postData = `employee_id=${encodeURIComponent(employee)}`;
                if (permissions.length > 0) {
                    postData += `&permissions=${encodeURIComponent(JSON.stringify(permissions))}`;
                }

                xhr.send(postData);
            } else {
                showNotification('Pilih karyawan terlebih dahulu.', 'warning');
            }
        }

        // FIXED: Improved notification system
        function showNotification(message, type) {
            // Remove existing notifications
            const existingNotification = document.querySelector('.notification');
            if (existingNotification) {
                existingNotification.remove();
            }

            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;

            // Add styles
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 3000;
                padding: 16px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 500;
                box-shadow: var(--card-shadow-lg);
                transform: translateX(100%);
                transition: var(--transition);
                max-width: 400px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b'};
            `;

            document.body.appendChild(notification);

            // Show notification
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);

            // Hide notification
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 3000);
        }

        // Close popups when clicking outside
        document.addEventListener('click', function(event) {
            const popups = document.querySelectorAll('.popup');
            popups.forEach(popup => {
                if (event.target === popup) {
                    popup.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>