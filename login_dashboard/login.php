<?php
// Start output buffering to prevent header issues
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mysqli = new mysqli('localhost', 'fitmotor_LOGIN', 'Sayalupa12', 'fitmotor_maintance-beta');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

session_start();

// Force session start debugging
if (session_status() !== PHP_SESSION_ACTIVE) {
    error_log("Session failed to start!");
    ini_set('session.save_handler', 'files');
    ini_set('session.save_path', sys_get_temp_dir());
    session_start();
}

// Menghapus cache
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$error_message = "";
$login_successful = false;
$redirect_url = "";

// Handle error messages from redirect
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'missing_branch_data':
            $error_message = "Data cabang tidak lengkap. Silakan hubungi administrator untuk mengatur data cabang Anda.";
            break;
        case 'session_expired':
            $error_message = "Sesi Anda telah berakhir. Silakan login kembali.";
            break;
        default:
            $error_message = "Terjadi kesalahan. Silakan coba lagi.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
    $password = isset($_POST['password']) ? strtolower(mysqli_real_escape_string($mysqli, $_POST['password'])) : '';
    $cabang_input = isset($_POST['cabang']) ? mysqli_real_escape_string($mysqli, $_POST['cabang']) : '';
    $karyawan = isset($_POST['karyawan']) ? strtolower(mysqli_real_escape_string($mysqli, $_POST['karyawan'])) : '';

    // PERBAIKAN: Validasi input tidak boleh kosong
    if (empty($karyawan)) {
        $error_message = "Silakan pilih karyawan.";
    } elseif (empty($cabang_input)) {
        $error_message = "Silakan pilih cabang.";
    } elseif (empty($password)) {
        $error_message = "Silakan masukkan password.";
    } else {
        // Memisahkan kode_cabang dan nama_cabang dari input cabang
        $cabang_parts = explode('|', $cabang_input);
        if (count($cabang_parts) != 2) {
            $error_message = "Format cabang tidak valid.";
        } else {
            list($kode_cabang, $nama_cabang) = $cabang_parts;

            // PERBAIKAN: Validasi format cabang
            if (empty($kode_cabang) || empty($nama_cabang)) {
                $error_message = "Data cabang tidak lengkap.";
            } else {
                // Memeriksa apakah data karyawan ada di tabel users
                $sql_check_user = "SELECT kode_karyawan, nama_karyawan, password, role, nama_cabang, kode_cabang FROM users WHERE kode_karyawan = ?";
                $stmt_check_user = $mysqli->prepare($sql_check_user);

                if ($stmt_check_user) {
                    $stmt_check_user->bind_param("s", $karyawan);
                    $stmt_check_user->execute();
                    $stmt_check_user->bind_result($kode_karyawan_db, $nama_karyawan, $password_db, $role, $db_nama_cabang, $db_kode_cabang);

                    if ($stmt_check_user->fetch()) {
                        if (empty($password_db)) {
                            $password_db = $karyawan; // Default password jika kosong
                        }

                        if ($password === $password_db) {
                            $login_successful = true;

                            // PERBAIKAN: Standardisasi dan validasi session keys
                            $_SESSION['nama_karyawan'] = strtoupper($nama_karyawan);
                            $_SESSION['kode_karyawan'] = $kode_karyawan_db;
                            $_SESSION['role'] = $role;
                            
                            // PERBAIKAN: Prioritas data cabang - gunakan dari form input, fallback ke database
                            $final_kode_cabang = !empty($kode_cabang) ? $kode_cabang : $db_kode_cabang;
                            $final_nama_cabang = !empty($nama_cabang) ? $nama_cabang : $db_nama_cabang;
                            
                            // PERBAIKAN: Double validation - pastikan data cabang tidak kosong
                            if (empty($final_kode_cabang) || empty($final_nama_cabang) || 
                                $final_kode_cabang === '0' || $final_nama_cabang === '0') {
                                $error_message = "Data cabang tidak lengkap atau tidak valid. Silakan hubungi administrator.";
                                $stmt_check_user->close();
                            } else {
                                // PERBAIKAN: Simpan dengan standardized keys
                                $_SESSION['kode_cabang'] = $final_kode_cabang;
                                $_SESSION['nama_cabang'] = $final_nama_cabang;
                                $_SESSION['cabang'] = $final_nama_cabang; // Backward compatibility
                                
                                // PERBAIKAN: Tambahan validasi panjang dan format
                                if (strlen($final_kode_cabang) < 3 || strlen($final_nama_cabang) < 3) {
                                    $error_message = "Format data cabang tidak valid.";
                                    $stmt_check_user->close();
                                } else {
                                    // Update database user jika ada perubahan cabang
                                    $stmt_check_user->close();
                                    
                                    $needs_update = false;
                                    if (strtolower($db_nama_cabang) !== strtolower($final_nama_cabang) || 
                                        $db_kode_cabang !== $final_kode_cabang) {
                                        $needs_update = true;
                                    }
                                    
                                    if ($needs_update) {
                                        $sql_update_cabang = "UPDATE users SET nama_cabang = ?, kode_cabang = ? WHERE kode_karyawan = ?";
                                        $stmt_update_cabang = $mysqli->prepare($sql_update_cabang);

                                        if ($stmt_update_cabang) {
                                            $stmt_update_cabang->bind_param("sss", $final_nama_cabang, $final_kode_cabang, $kode_karyawan_db);
                                            $stmt_update_cabang->execute();
                                            $stmt_update_cabang->close();
                                        }
                                    }

                                    // PERBAIKAN: Log successful login untuk debugging
                                    error_log("Login successful - Karyawan: $kode_karyawan_db, Cabang: $final_kode_cabang|$final_nama_cabang");

                                    // PERBAIKAN: Set login timestamp
                                    $_SESSION['login_time'] = time();
                                    $_SESSION['last_activity'] = time();

                                    // Check various possible dashboard locations
                                    $dashboard_paths = [
                                        'dashboard/dashboard_user.php',
                                        './dashboard/dashboard_user.php',
                                        '../dashboard/dashboard_user.php',
                                        'dashboard_user.php'
                                    ];
                                    
                                    $redirect_url = 'dashboard/dashboard_user.php'; // default
                                    
                                    foreach ($dashboard_paths as $path) {
                                        if (file_exists($path)) {
                                            $redirect_url = $path;
                                            error_log("Found dashboard at: " . $path);
                                            break;
                                        }
                                    }

                                    // Don't redirect immediately - let JavaScript handle it with animation
                                    // Store the redirect URL for JavaScript to use
                                }
                            }
                        } else {
                            $error_message = "Password yang Anda masukkan salah.";
                            $stmt_check_user->close();
                        }
                    } else {
                        $error_message = "Data karyawan tidak ditemukan.";
                        $stmt_check_user->close();
                    }
                } else {
                    $error_message = "Terjadi kesalahan sistem. Silakan coba lagi.";
                }
            }
        }
    }
}

// Mengambil data karyawan dari tabel users
$karyawan_options = "";
$sql_karyawan = "SELECT DISTINCT kode_karyawan, nama_karyawan, role FROM users ORDER BY nama_karyawan";
$result_karyawan = $mysqli->query($sql_karyawan);

if ($result_karyawan && $result_karyawan->num_rows > 0) {
    while ($row = $result_karyawan->fetch_assoc()) {
        $kode_karyawan = $row['kode_karyawan'];
        $nama_karyawan = $row['nama_karyawan'];
        $role = $row['role'];
        $karyawan_options .= '<option value="' . htmlspecialchars($kode_karyawan) . '">' . htmlspecialchars($kode_karyawan . ' - ' . $nama_karyawan . ' (' . $role . ')') . '</option>';
    }
    $result_karyawan->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitMotor System - Welcome back</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Simplified background - removed complex animations */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(255, 255, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(255, 255, 255, 0.04) 0%, transparent 50%);
            pointer-events: none;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 480px;
            box-shadow: 
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            opacity: 0;
            transform: translateY(20px);
            animation: slideUp 0.6s ease-out forwards;
        }

        @keyframes slideUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 140px;
            height: 64px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
            /* Removed hover animations to prevent flickering */
        }

        /* Simplified logo shimmer - reduced complexity */
        .logo-icon::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 4s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .logo-text {
            display: flex;
            align-items: center;
            font-weight: 800;
            font-size: 20px;
            letter-spacing: 1px;
            font-family: 'Inter', sans-serif;
            position: relative;
            z-index: 1;
        }

        .logo-fit {
            color: #ffffff;
            margin-right: 3px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .logo-motor {
            color: #e0e7ff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .welcome-text {
            color: #1a1a1a;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 16px;
            font-weight: 400;
            margin-bottom: 40px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 16px 20px;
            font-size: 16px;
            color: #1f2937;
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            box-sizing: border-box;
            /* Optimized transitions - only what's necessary */
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .searchable-select {
            position: relative;
            width: 100%;
        }

        .searchable-input {
            width: 100%;
            padding-right: 50px !important;
            cursor: pointer;
            position: relative;
            z-index: 1;
        }

        .dropdown-arrow {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
            z-index: 2;
            /* Simplified transition */
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .dropdown-arrow:hover {
            color: #667eea;
        }

        .dropdown-arrow.open {
            transform: translateY(-50%) rotate(180deg);
        }

        .dropdown-options {
            position: absolute;
            top: calc(100% + 2px);
            left: 0;
            right: 0;
            background: #ffffff;
            border: 2px solid #667eea;
            border-radius: 12px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            margin-top: 0;
            /* Optimized show animation */
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .dropdown-options.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .dropdown-option {
            padding: 14px 16px;
            cursor: pointer;
            font-size: 14px;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
            line-height: 1.4;
            word-wrap: break-word;
            /* Simplified hover effect */
            transition: background-color 0.15s ease;
        }

        .dropdown-option:last-child {
            border-bottom: none;
        }

        .dropdown-option:hover {
            background-color: #f8fafc;
        }

        .dropdown-option.selected {
            background-color: #667eea;
            color: white;
        }

        .dropdown-option:first-child {
            color: #9ca3af;
            font-style: italic;
            background-color: #f9fafb;
        }

        .dropdown-option:first-child:hover {
            background-color: #f3f4f6;
        }

        /* Hide the original select but keep it functional */
        select[name="karyawan"] {
            position: absolute !important;
            top: -9999px !important;
            left: -9999px !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }

        /* Optimized scrollbar */
        .dropdown-options::-webkit-scrollbar {
            width: 6px;
        }

        .dropdown-options::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .dropdown-options::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .dropdown-options::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Proper z-index layering */
        .form-group:nth-child(1) { z-index: 1001; }
        .form-group:nth-child(2) { z-index: 1000; }
        .form-group:nth-child(3) { z-index: 999; }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-input::placeholder {
            color: #9ca3af;
        }

        .password-input-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            font-size: 16px;
            padding: 4px;
            border-radius: 4px;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            /* Optimized hover effect */
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .login-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .login-button:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            /* Simplified shake animation */
            animation: shake 0.3s ease-in-out;
        }

        .error-message i {
            margin-right: 8px;
            color: #dc2626;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-3px); }
            75% { transform: translateX(3px); }
        }

        .version-info {
            position: absolute;
            bottom: 20px;
            right: 20px;
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }

        /* Optimized Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.95) 0%, rgba(118, 75, 162, 0.95) 100%);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.show {
            opacity: 1;
        }

        .loading-content {
            text-align: center;
            color: white;
            position: relative;
        }

        /* Optimized Loading Spinner */
        .loading-spinner {
            width: 60px;
            height: 60px;
            position: relative;
            margin: 0 auto 30px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Simplified Loading Progress Bar */
        .loading-progress {
            width: 200px;
            height: 3px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 2px;
            margin: 20px auto;
            overflow: hidden;
            position: relative;
        }

        .loading-progress-bar {
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 2px;
            animation: progressSlide 2s ease-in-out infinite;
            transform: translateX(-100%);
        }

        @keyframes progressSlide {
            0% { transform: translateX(-100%); width: 20%; }
            50% { width: 60%; }
            100% { transform: translateX(200%); width: 20%; }
        }

        .loading-text {
            font-size: 18px;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        .loading-subtext {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }

        /* Optimized Success Animation */
        .success-checkmark {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: none;
            position: relative;
            margin: 0 auto 20px;
            background: rgba(34, 197, 94, 0.2);
            border: 3px solid #22c55e;
        }

        .success-checkmark.show {
            display: block;
            animation: scaleIn 0.4s ease-out;
        }

        .success-checkmark::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            width: 20px;
            height: 35px;
            border: solid #22c55e;
            border-width: 0 3px 3px 0;
            animation: checkmarkDraw 0.4s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes checkmarkDraw {
            0% { 
                width: 0;
                height: 0;
                opacity: 0;
            }
            100% { 
                width: 20px;
                height: 35px;
                opacity: 1;
            }
        }

        /* Responsive Design */
        @media (max-width: 576px) {
            .login-container {
                padding: 32px 24px;
                margin: 20px;
                border-radius: 20px;
            }

            .welcome-text {
                font-size: 24px;
            }

            .subtitle {
                font-size: 14px;
            }

            .dropdown-options {
                max-height: 150px;
            }

            .logo-icon {
                width: 120px;
                height: 52px;
            }

            .logo-text {
                font-size: 16px;
            }

            .form-group {
                margin-bottom: 20px;
            }

            .dropdown-option {
                padding: 12px 14px;
                font-size: 13px;
            }

            .loading-spinner {
                width: 50px;
                height: 50px;
            }

            .loading-progress {
                width: 150px;
            }
        }

        /* Focus states for accessibility */
        .form-input:focus-visible,
        .form-select:focus-visible,
        .login-button:focus-visible {
            outline: 2px solid #667eea;
            outline-offset: 2px;
        }

        /* Performance optimization - prevent unnecessary repaints */
        .login-container, .loading-overlay, .dropdown-options {
            will-change: auto;
        }

        /* Disable animations for users who prefer reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo-icon">
                <div class="logo-text">
                    <span class="logo-fit">FIT</span>
                    <span class="logo-motor">MOTOR</span>
                </div>
            </div>
            <h1 class="welcome-text">Login</h1>
            <p class="subtitle">Isi Form Login Dengan Teliti Dan Tidak Salah Pilih Cabang</p>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Debug info when form is submitted -->
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <div style="background: #e3f2fd; border: 2px solid #2196f3; color: #1976d2; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; font-weight: 500;">
                <strong>🔍 DEBUG INFO:</strong><br>
                <strong>Request Method:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?><br>
                <strong>POST Data Available:</strong> <?php echo !empty($_POST) ? 'YES' : 'NO'; ?><br>
                <strong>Processing Login:</strong> <?php echo ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) ? 'YES' : 'NO'; ?><br>
                <strong>Karyawan Posted:</strong> <?php echo isset($_POST['karyawan']) ? htmlspecialchars($_POST['karyawan']) : 'NOT SET'; ?><br>
                <strong>Cabang Posted:</strong> <?php echo isset($_POST['cabang']) ? htmlspecialchars($_POST['cabang']) : 'NOT SET'; ?><br>
                <strong>Password Posted:</strong> <?php echo isset($_POST['password']) ? '***SET***' : 'NOT SET'; ?><br>
                <strong>Login Button Posted:</strong> <?php echo isset($_POST['login']) ? 'YES' : 'NO (FIXED - NOT REQUIRED)'; ?><br>
                
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)): ?>
                    <strong>Processing Result:</strong> 
                    <?php if ($login_successful): ?>
                        <span style='color: green; font-weight: bold;'>✅ SUCCESS - Will redirect to dashboard</span><br>
                        <strong>Session Data:</strong><br>
                        - Nama: <?php echo isset($_SESSION['nama_karyawan']) ? $_SESSION['nama_karyawan'] : 'NOT SET'; ?><br>
                        - Kode: <?php echo isset($_SESSION['kode_karyawan']) ? $_SESSION['kode_karyawan'] : 'NOT SET'; ?><br>
                        - Role: <?php echo isset($_SESSION['role']) ? $_SESSION['role'] : 'NOT SET'; ?><br>
                        - Cabang: <?php echo isset($_SESSION['nama_cabang']) ? $_SESSION['nama_cabang'] : 'NOT SET'; ?>
                    <?php else: ?>
                        <span style='color: red; font-weight: bold;'>❌ FAILED</span><br>
                        <strong>Error:</strong> <?php echo $error_message ? htmlspecialchars($error_message) : 'Unknown error'; ?>
                    <?php endif; ?>
                <?php endif; ?>
                
                <br><strong>Current Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="karyawan">Pilih Karyawan</label>
                <div class="searchable-select" id="employeeDropdown">
                    <input type="text" 
                           class="form-input searchable-input" 
                           id="employeeSearch" 
                           placeholder="Search karyawan.." 
                           autocomplete="off"
                           readonly>
                    <div class="dropdown-arrow" id="dropdownArrow">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-options" id="employeeOptions">
                        <div class="dropdown-option" data-value="">Pilih User</div>
                    </div>
                </div>
                <select name="karyawan" id="karyawan" style="display: none;" required>
                    <option value="">Select Employee</option>
                    <?php echo $karyawan_options; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="cabang">Pilih Cabang</label>
                <select name="cabang" id="cabang" class="form-select" required>
                    <option value="">Pilih cabang</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" id="passwordToggle">
                        <i class="fas fa-eye" id="passwordToggleIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login" class="login-button" id="loginButton">
                <span id="loginButtonText">Sign in</span>
                <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
            </button>
        </form>
    </div>

    <div class="version-info">
        v2.2 - Performance Optimized
    </div>

    <!-- Optimized Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="loading-spinner" id="loadingSpinner"></div>
            <div class="success-checkmark" id="successCheckmark"></div>
            <div class="loading-text" id="loadingText">Memverifikasi kredensial...</div>
            <div class="loading-progress">
                <div class="loading-progress-bar"></div>
            </div>
            <div class="loading-subtext" id="loadingSubtext">Mohon tunggu sebentar</div>
        </div>
    </div>

    <script>
        // Cache DOM elements for better performance
        const elements = {
            employeeSearch: null,
            employeeOptions: null,
            dropdownArrow: null,
            karyawan: null,
            cabang: null,
            password: null,
            passwordToggle: null,
            passwordToggleIcon: null,
            loginForm: null,
            loginButton: null,
            loginButtonText: null,
            loadingOverlay: null,
            loadingSpinner: null,
            successCheckmark: null,
            loadingText: null,
            loadingSubtext: null
        };

        let selectedEmployeeValue = '';
        let debounceTimer = null;
        
        // Check if login was successful and trigger loading animation
        const loginSuccessful = <?php echo $login_successful ? 'true' : 'false'; ?>;
        const redirectUrl = <?php echo $login_successful ? '"' . $redirect_url . '"' : 'null'; ?>;

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            cacheElements();
            initializeEventListeners();
            buildDropdownOptions();
            
            if (loginSuccessful && redirectUrl) {
                showSuccessLoading();
            }
        });

        function cacheElements() {
            // Cache all frequently used elements
            Object.keys(elements).forEach(key => {
                const element = document.getElementById(key) || 
                               document.querySelector(`#${key}`) || 
                               document.querySelector(`.${key}`);
                if (element) elements[key] = element;
            });

            // Additional elements not following the pattern
            elements.employeeSearch = document.getElementById('employeeSearch');
            elements.employeeOptions = document.getElementById('employeeOptions');
            elements.dropdownArrow = document.getElementById('dropdownArrow');
            elements.karyawan = document.getElementById('karyawan');
            elements.cabang = document.getElementById('cabang');
            elements.password = document.getElementById('password');
            elements.passwordToggle = document.getElementById('passwordToggle');
            elements.passwordToggleIcon = document.getElementById('passwordToggleIcon');
            elements.loginForm = document.getElementById('loginForm');
            elements.loginButton = document.getElementById('loginButton');
            elements.loginButtonText = document.getElementById('loginButtonText');
            elements.loadingOverlay = document.getElementById('loadingOverlay');
            elements.loadingSpinner = document.getElementById('loadingSpinner');
            elements.successCheckmark = document.getElementById('successCheckmark');
            elements.loadingText = document.getElementById('loadingText');
            elements.loadingSubtext = document.getElementById('loadingSubtext');
        }

        function initializeEventListeners() {
            // Employee dropdown events
            if (elements.employeeSearch) {
                elements.employeeSearch.addEventListener('click', toggleDropdown);
                elements.employeeSearch.addEventListener('keyup', debounceFilter);
                elements.employeeSearch.addEventListener('keydown', handleKeyNavigation);
            }

            if (elements.dropdownArrow) {
                elements.dropdownArrow.addEventListener('click', toggleDropdown);
            }

            // Password toggle
            if (elements.passwordToggle) {
                elements.passwordToggle.addEventListener('click', togglePassword);
            }

            // Form submission
            if (elements.loginForm) {
                elements.loginForm.addEventListener('submit', handleFormSubmit);
            }

            // Click outside to close dropdown
            document.addEventListener('click', handleOutsideClick);

            // Prevent dropdown close when clicking inside
            if (elements.employeeOptions) {
                elements.employeeOptions.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }
        }

        function debounceFilter() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(filterEmployees, 150);
        }

        function toggleDropdown() {
            if (!elements.employeeOptions || !elements.dropdownArrow) return;

            const isOpen = elements.employeeOptions.classList.contains('show');
            
            if (isOpen) {
                hideDropdown();
            } else {
                showDropdown();
            }
        }

        function showDropdown() {
            // Close other dropdowns first
            document.querySelectorAll('.dropdown-options.show').forEach(d => {
                d.classList.remove('show');
            });
            document.querySelectorAll('.dropdown-arrow.open').forEach(a => {
                a.classList.remove('open');
            });
            
            elements.employeeOptions.classList.add('show');
            elements.dropdownArrow.classList.add('open');
            
            // Remove readonly to allow search
            elements.employeeSearch.removeAttribute('readonly');
            elements.employeeSearch.focus();
        }

        function hideDropdown() {
            if (!elements.employeeOptions || !elements.dropdownArrow) return;
            
            elements.employeeOptions.classList.remove('show');
            elements.dropdownArrow.classList.remove('open');
            
            // Add readonly back to prevent keyboard
            elements.employeeSearch.setAttribute('readonly', true);
        }

        function filterEmployees() {
            if (!elements.employeeSearch || !elements.employeeOptions) return;

            const filter = elements.employeeSearch.value.toLowerCase();
            const options = elements.employeeOptions.querySelectorAll('.dropdown-option');
            let hasVisibleOptions = false;
            
            options.forEach((option, index) => {
                const text = option.textContent.toLowerCase();
                if (index === 0) {
                    // Always show the placeholder option
                    option.style.display = 'block';
                    return;
                }
                
                if (text.includes(filter)) {
                    option.style.display = 'block';
                    hasVisibleOptions = true;
                } else {
                    option.style.display = 'none';
                }
            });

            // Show dropdown when typing if it's not already shown
            if (!elements.employeeOptions.classList.contains('show')) {
                showDropdown();
            }
        }

        function selectEmployee(value, text) {
            if (!elements.karyawan || !elements.employeeSearch) return;
            
            // Update hidden select element
            elements.karyawan.value = value;
            
            // Update search input display
            elements.employeeSearch.value = text;
            selectedEmployeeValue = value;
            hideDropdown();
            
            // Clear previous selection highlighting
            elements.employeeOptions.querySelectorAll('.dropdown-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Highlight selected option
            const selectedOption = elements.employeeOptions.querySelector(`[data-value="${value}"]`);
            if (selectedOption) {
                selectedOption.classList.add('selected');
            }
            
            // Load branches
            loadBranches();
        }

        function buildDropdownOptions() {
            if (!elements.karyawan || !elements.employeeOptions) return;

            // Clear existing options except the first placeholder
            const placeholder = elements.employeeOptions.querySelector('.dropdown-option[data-value=""]');
            elements.employeeOptions.innerHTML = '';
            
            if (placeholder) {
                elements.employeeOptions.appendChild(placeholder);
            } else {
                // Create placeholder if it doesn't exist
                const newPlaceholder = document.createElement('div');
                newPlaceholder.className = 'dropdown-option';
                newPlaceholder.dataset.value = '';
                newPlaceholder.textContent = 'Pilih User';
                elements.employeeOptions.appendChild(newPlaceholder);
            }
            
            // Add options from hidden select
            Array.from(elements.karyawan.options).forEach(option => {
                if (option.value !== '') {
                    const dropdownOption = document.createElement('div');
                    dropdownOption.className = 'dropdown-option';
                    dropdownOption.dataset.value = option.value;
                    dropdownOption.textContent = option.textContent;
                    elements.employeeOptions.appendChild(dropdownOption);
                    
                    // Add click listener
                    dropdownOption.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        selectEmployee(this.dataset.value, this.textContent);
                    });
                }
            });
        }

        function loadBranches() {
            if (!elements.karyawan || !elements.cabang) return;
            
            const karyawan = elements.karyawan.value;

            if (karyawan) {
                // Show loading state
                elements.cabang.innerHTML = '<option value="">Loading branches...</option>';
                
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'get_branches.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function () {
                    if (this.status === 200) {
                        elements.cabang.innerHTML = this.responseText;
                    } else {
                        elements.cabang.innerHTML = '<option value="">Error loading branches</option>';
                    }
                };
                xhr.onerror = function() {
                    elements.cabang.innerHTML = '<option value="">Error loading branches</option>';
                };
                xhr.send('karyawan=' + encodeURIComponent(karyawan));
            } else {
                elements.cabang.innerHTML = '<option value="">Pilih cabang</option>';
            }
        }

        function togglePassword() {
            if (!elements.password || !elements.passwordToggleIcon) return;
            
            if (elements.password.type === "password") {
                elements.password.type = "text";
                elements.passwordToggleIcon.className = "fas fa-eye-slash";
            } else {
                elements.password.type = "password";
                elements.passwordToggleIcon.className = "fas fa-eye";
            }
        }

        function handleKeyNavigation(e) {
            if (e.key === 'Escape') {
                hideDropdown();
                elements.employeeSearch.blur();
            }
        }

        function handleOutsideClick(e) {
            const employeeDropdown = document.getElementById('employeeDropdown');
            if (employeeDropdown && !employeeDropdown.contains(e.target)) {
                hideDropdown();
            }
        }

        function handleFormSubmit(e) {
            // Don't prevent if already successful
            if (loginSuccessful) {
                return;
            }

            const karyawan = elements.karyawan.value;
            const cabang = elements.cabang.value;
            const password = elements.password.value;

            // Remove any existing error states
            document.querySelectorAll('.form-input, .form-select').forEach(el => {
                el.style.borderColor = '#e5e7eb';
            });

            if (!karyawan) {
                e.preventDefault();
                showFieldError('employeeSearch', 'Please select an employee');
                return;
            }

            if (!cabang) {
                e.preventDefault();
                showFieldError('cabang', 'Please select a branch');
                return;
            }

            if (!password) {
                e.preventDefault();
                showFieldError('password', 'Please enter your password');
                return;
            }

            // Validate branch format
            if (cabang.includes('|')) {
                const cabangParts = cabang.split('|');
                if (cabangParts.length !== 2 || !cabangParts[0].trim() || !cabangParts[1].trim()) {
                    e.preventDefault();
                    showFieldError('cabang', 'Incomplete branch data. Please select again.');
                    return;
                }
            }

            // Show loading state
            if (elements.loginButton && elements.loginButtonText) {
                elements.loginButton.disabled = true;
                elements.loginButtonText.textContent = 'Signing in...';
                elements.loginButton.style.opacity = '0.7';
            }
            
            // Show loading animation
            showLoadingAnimation();
        }

        function showLoadingAnimation() {
            if (!elements.loadingOverlay) return;
            
            // Reset states
            if (elements.loadingSpinner) elements.loadingSpinner.style.display = 'block';
            if (elements.successCheckmark) elements.successCheckmark.classList.remove('show');
            
            // Show loading overlay
            elements.loadingOverlay.style.display = 'flex';
            requestAnimationFrame(() => {
                elements.loadingOverlay.classList.add('show');
            });

            // Update loading text
            if (elements.loadingText) elements.loadingText.textContent = 'Memproses login...';
            if (elements.loadingSubtext) elements.loadingSubtext.textContent = 'Memverifikasi data Anda';

            // Auto-hide after timeout
            setTimeout(() => {
                if (elements.loadingOverlay.style.display === 'flex') {
                    hideLoadingAnimation();
                }
            }, 8000);
        }

        function showSuccessLoading() {
            if (!elements.loadingOverlay) return;

            // Show loading overlay
            elements.loadingOverlay.style.display = 'flex';
            requestAnimationFrame(() => {
                elements.loadingOverlay.classList.add('show');
            });

            // Phase 1: Authenticating (1 second)
            if (elements.loadingText) elements.loadingText.textContent = 'Memverifikasi kredensial...';
            if (elements.loadingSubtext) elements.loadingSubtext.textContent = 'Mohon tunggu sebentar';

            setTimeout(() => {
                // Phase 2: Success animation
                if (elements.loadingText) elements.loadingText.textContent = 'Login berhasil!';
                if (elements.loadingSubtext) elements.loadingSubtext.textContent = 'Mempersiapkan dashboard...';
                
                // Hide spinner and show checkmark
                if (elements.loadingSpinner) elements.loadingSpinner.style.display = 'none';
                if (elements.successCheckmark) elements.successCheckmark.classList.add('show');
                
                setTimeout(() => {
                    // Phase 3: Redirecting
                    if (elements.loadingText) elements.loadingText.textContent = 'Mengarahkan ke dashboard...';
                    if (elements.loadingSubtext) elements.loadingSubtext.textContent = 'Selamat datang!';
                    
                    setTimeout(() => {
                        // Final redirect
                        window.location.href = redirectUrl;
                    }, 400);
                }, 800);
            }, 1000);
        }

        function hideLoadingAnimation() {
            if (!elements.loadingOverlay) return;
            
            elements.loadingOverlay.classList.remove('show');
            setTimeout(() => {
                elements.loadingOverlay.style.display = 'none';
            }, 300);
            
            // Reset button
            if (elements.loginButton && elements.loginButtonText) {
                elements.loginButton.disabled = false;
                elements.loginButtonText.textContent = 'Sign in';
                elements.loginButton.style.opacity = '1';
            }
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.style.borderColor = '#dc2626';
                field.focus();
            }
            
            // Hide loading overlay
            hideLoadingAnimation();
            
            // Create or update error message
            let errorDiv = document.querySelector('.error-message');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.className = 'error-message';
                elements.loginForm.insertBefore(errorDiv, elements.loginForm.firstChild);
            }
            errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${message}`;
            
            // Auto-hide error after 5 seconds
            setTimeout(() => {
                if (errorDiv && errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }
    </script>
</body>
</html>