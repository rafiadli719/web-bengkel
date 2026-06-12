<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config/koneksi.php";

// Destroy session if user is already logged in
if (isset($_SESSION['_iduser'])) {
    header("Location: panel/");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIT MOTOR - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        body {
            background: linear-gradient(-45deg, #f5f7fa, #e4e9f2, #dfe6f0, #f0f4f8);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.05) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s ease-in-out infinite;
        }

        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
            max-width: 1100px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .login-form-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 48px 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.08),
                        0 0 0 1px rgba(255, 255, 255, 0.5);
            max-height: 90vh;
            overflow-y: auto;
            animation: fadeInUp 0.8s ease-out;
            border: 1px solid rgba(255, 255, 255, 0.8);
        }

        .login-illustration {
            display: flex;
            justify-content: center;
            align-items: center;
            animation: slideInRight 0.8s ease-out;
        }

        .login-illustration svg {
            filter: drop-shadow(0 10px 30px rgba(99, 102, 241, 0.15));
            animation: float 6s ease-in-out infinite;
        }

        .brand-section {
            text-align: left;
            margin-bottom: 36px;
            position: relative;
        }

        .brand-logo {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }

        .logo-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
        }

        .logo-icon svg {
            width: 28px;
            height: 28px;
        }

        .brand-title {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            color: #1e293b;
            margin: 0;
        }

        .brand-title span {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .brand-subtitle {
            font-size: 14px;
            color: #64748b;
            font-weight: 400;
            margin-top: 4px;
        }

        .form-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1e293b;
            letter-spacing: -0.3px;
        }

        .form-subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 28px;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 20px;
            animation: fadeInUp 0.6s ease-out backwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }

        .form-group label {
            font-size: 13px;
            font-weight: 500;
            color: #475569;
            margin-bottom: 8px;
            display: block;
            letter-spacing: -0.1px;
        }

        .form-control {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 13px 16px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background-color: #f8fafc;
            color: #1e293b;
            box-sizing: border-box;
            font-weight: 400;
        }

        .form-control:hover {
            border-color: #cbd5e1;
            background-color: #fff;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.08);
            outline: none;
            background-color: #fff;
            transform: translateY(-1px);
        }

        .form-control::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .input-group {
            display: flex;
            align-items: center;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .input-group-text {
            position: absolute;
            left: 14px;
            color: #6366f1;
            font-size: 18px;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .input-group .form-control:focus ~ .input-group-text,
        .form-control:focus + .input-group-text {
            transform: scale(1.1);
        }

        .input-group .form-control {
            padding-left: 44px;
        }

        .input-group .password-toggle {
            position: absolute;
            right: 14px;
            cursor: pointer;
            color: #64748b;
            font-size: 18px;
            user-select: none;
            transition: all 0.3s ease;
            padding: 4px;
        }

        .input-group .password-toggle:hover {
            color: #6366f1;
            transform: scale(1.1);
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%236366f1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
            padding-right: 44px;
            cursor: pointer;
            min-height: 48px;
            font-weight: 400;
        }

        select.form-control option {
            padding: 12px;
            background-color: #fff;
            color: #1e293b;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 10px;
            cursor: pointer;
            accent-color: #6366f1;
            border-radius: 4px;
        }

        .form-check-label {
            font-size: 13px;
            color: #64748b;
            cursor: pointer;
            font-weight: 400;
        }

        .forgot-password {
            font-size: 13px;
            color: #6366f1;
            text-decoration: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: #4f46e5;
            text-decoration: none;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            animation: fadeInUp 0.6s ease-out 0.4s backwards;
        }

        .btn-login {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            letter-spacing: 0.3px;
            animation: fadeInUp 0.6s ease-out 0.5s backwards;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 24px;
            font-size: 13px;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: fadeInUp 0.5s ease-out;
            font-weight: 500;
        }

        .alert-danger {
            background-color: #fef2f2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background-color: #f0fdf4;
            color: #16a34a;
            border-left: 4px solid #16a34a;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 24px 0;
            color: #94a3b8;
            font-size: 13px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }

        .divider span {
            padding: 0 12px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
            }

            .login-form-wrapper {
                padding: 36px 28px;
            }

            .login-illustration {
                display: none;
            }

            .brand-title {
                font-size: 24px;
            }

            .form-title {
                font-size: 22px;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Form Section -->
        <div class="login-form-wrapper">
            <div class="brand-section">
                <div class="brand-logo">
                    <div class="logo-icon">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                            <path d="M2 17L12 22L22 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12L12 17L22 12" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <h1 class="brand-title">FIT <span>MOTOR</span></h1>
                </div>
                <p class="brand-subtitle">Sistem Informasi Bengkel Profesional</p>
            </div>

            <div class="form-title">Selamat Datang</div>
            <p class="form-subtitle">Silakan masuk dengan akun Anda untuk melanjutkan</p>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span><?php echo $_SESSION['login_error']; ?></span>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['login_success'])): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <span><?php echo $_SESSION['login_success']; ?></span>
                </div>
                <?php unset($_SESSION['login_success']); ?>
            <?php endif; ?>

            <form method="post" action="cek_login.php" id="loginForm">
                <!-- Username -->
                <div class="form-group">
                    <label for="txtnama">Username / Karyawan</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <select class="form-control" id="txtnama" name="txtnama" required>
                            <option value="">-- Pilih Username --</option>
                            <?php
                            // Query username dari database tbuser (gabungan)
                            $query_user = "SELECT id, nama_user, nama_lengkap 
                                          FROM tbuser
                                          WHERE status_row='0' AND is_active='active' 
                                          ORDER BY nama_lengkap ASC";
                            $result_user = mysqli_query($koneksi, $query_user);
                            
                            if ($result_user && mysqli_num_rows($result_user) > 0) {
                                while ($row_user = mysqli_fetch_assoc($result_user)) {
                                    $display_name = $row_user['nama_lengkap'] ? 
                                        htmlspecialchars($row_user['nama_lengkap']) . ' (' . htmlspecialchars($row_user['nama_user']) . ')' :
                                        htmlspecialchars($row_user['nama_user']);
                                    echo '<option value="' . htmlspecialchars($row_user['nama_user']) . '">' . $display_name . '</option>';
                                }
                            } else {
                                // Fallback jika tidak ada user
                                echo '<option value="admin">admin</option>';
                                echo '<option value="cs">cs</option>';
                                echo '<option value="kasir">kasir</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="txtpass">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                            </svg>
                        </span>
                        <input type="password" class="form-control" id="txtpass" name="txtpass"
                               placeholder="Masukkan password" required autocomplete="off">
                        <span class="password-toggle" onclick="togglePassword()" id="toggleIcon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- Branch Selection -->
                <div class="form-group">
                    <label for="cbocabang">Cabang</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </span>
                        <select class="form-control" id="cbocabang" name="cbocabang" required>
                            <option value="">-- Pilih Cabang --</option>
                            <?php
                            // Query cabang dari database tbcabang
                            $query_cabang = "SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY nama_cabang ASC";
                            $result_cabang = mysqli_query($koneksi, $query_cabang);
                            
                            if ($result_cabang && mysqli_num_rows($result_cabang) > 0) {
                                while ($row_cabang = mysqli_fetch_assoc($result_cabang)) {
                                    echo '<option value="' . htmlspecialchars($row_cabang['kode_cabang']) . '">' . htmlspecialchars($row_cabang['nama_cabang']) . '</option>';
                                }
                            } else {
                                // Fallback jika database kosong
                                echo '<option value="PST">Bengkel Pusat</option>';
                                echo '<option value="PESALAKAN">Cabang Pesalakan</option>';
                                echo '<option value="SBY">Cabang Surabaya</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="remember-forgot">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Ingat saya
                        </label>
                    </div>
                    <a href="#" class="forgot-password">Lupa Password?</a>
                </div>

                <!-- Login Button -->
                <button type="submit" class="btn-login">Log in</button>
            </form>
        </div>

        <!-- Illustration Section -->
        <div class="login-illustration">
            <svg width="400" height="400" viewBox="0 0 400 400" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="primaryGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="secondaryGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#f8fafc;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#e2e8f0;stop-opacity:1" />
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>

                <!-- Background circles -->
                <circle cx="200" cy="200" r="180" fill="#f1f5f9" opacity="0.4">
                    <animate attributeName="r" values="180;185;180" dur="4s" repeatCount="indefinite"/>
                </circle>
                <circle cx="200" cy="200" r="140" fill="#e2e8f0" opacity="0.3">
                    <animate attributeName="r" values="140;145;140" dur="3s" repeatCount="indefinite"/>
                </circle>

                <!-- Main device/laptop -->
                <g transform="translate(75, 80)">
                    <!-- Laptop base -->
                    <rect x="0" y="0" width="250" height="160" rx="16" fill="url(#primaryGradient)" opacity="0.9">
                        <animateTransform attributeName="transform" type="translate" values="0,0; 0,-2; 0,0" dur="3s" repeatCount="indefinite"/>
                    </rect>

                    <!-- Screen -->
                    <rect x="10" y="10" width="230" height="140" rx="12" fill="white"/>
                    <rect x="15" y="15" width="220" height="130" rx="8" fill="#f8fafc"/>

                    <!-- Lock icon on screen -->
                    <g transform="translate(95, 45)">
                        <rect x="10" y="20" width="40" height="50" rx="8" fill="url(#primaryGradient)" opacity="0.2"/>
                        <rect x="15" y="25" width="30" height="40" rx="6" fill="url(#primaryGradient)">
                            <animate attributeName="opacity" values="0.8;1;0.8" dur="2s" repeatCount="indefinite"/>
                        </rect>
                        <path d="M 20 25 Q 20 10 30 10 Q 40 10 40 25" stroke="url(#primaryGradient)" stroke-width="4" fill="none" stroke-linecap="round"/>
                        <circle cx="30" cy="45" r="3" fill="white" opacity="0.8"/>
                        <line x1="30" y1="48" x2="30" y2="55" stroke="white" stroke-width="2" stroke-linecap="round" opacity="0.8"/>
                    </g>

                    <!-- Input fields indicator -->
                    <line x1="40" y1="100" x2="160" y2="100" stroke="#cbd5e1" stroke-width="3" stroke-linecap="round"/>
                    <line x1="40" y1="115" x2="200" y2="115" stroke="#cbd5e1" stroke-width="3" stroke-linecap="round"/>

                    <!-- Login button indicator -->
                    <rect x="80" y="128" width="90" height="16" rx="8" fill="url(#primaryGradient)" opacity="0.6">
                        <animate attributeName="opacity" values="0.6;0.9;0.6" dur="2s" repeatCount="indefinite"/>
                    </rect>

                    <!-- Laptop stand -->
                    <path d="M 50 165 L 125 185 L 200 165 Z" fill="url(#primaryGradient)" opacity="0.7"/>

                    <!-- Shadow -->
                    <ellipse cx="125" cy="200" rx="100" ry="12" fill="url(#primaryGradient)" opacity="0.15">
                        <animate attributeName="opacity" values="0.15;0.25;0.15" dur="3s" repeatCount="indefinite"/>
                    </ellipse>
                </g>

                <!-- Floating security icons -->
                <g opacity="0.4">
                    <!-- Shield -->
                    <path d="M 60 100 L 60 140 Q 60 150 70 160 L 80 165 L 90 160 Q 100 150 100 140 L 100 100 Z" fill="url(#primaryGradient)">
                        <animateTransform attributeName="transform" type="translate" values="0,0; 0,-10; 0,0" dur="4s" repeatCount="indefinite"/>
                    </path>

                    <!-- Key -->
                    <g transform="translate(310, 250)">
                        <circle cx="0" cy="0" r="12" fill="url(#primaryGradient)">
                            <animateTransform attributeName="transform" type="rotate" values="0 0 0; 15 0 0; 0 0 0" dur="3s" repeatCount="indefinite"/>
                        </circle>
                        <circle cx="0" cy="0" r="6" fill="white"/>
                        <rect x="-3" y="8" width="6" height="25" rx="2" fill="url(#primaryGradient)"/>
                        <rect x="-3" y="28" width="10" height="3" rx="1" fill="url(#primaryGradient)"/>
                        <rect x="-3" y="33" width="10" height="3" rx="1" fill="url(#primaryGradient)"/>
                        <animateTransform attributeName="transform" type="translate" values="0,0; 0,-8; 0,0" dur="5s" repeatCount="indefinite"/>
                    </g>

                    <!-- Check mark -->
                    <g transform="translate(340, 120)">
                        <circle cx="0" cy="0" r="14" fill="#10b981" opacity="0.2"/>
                        <path d="M -6 0 L -2 4 L 6 -4" stroke="#10b981" stroke-width="2.5" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <animate attributeName="opacity" values="0;1;0" dur="2s" repeatCount="indefinite"/>
                        </path>
                        <animateTransform attributeName="transform" type="translate" values="0,0; 0,10; 0,0" dur="4.5s" repeatCount="indefinite"/>
                    </g>
                </g>

                <!-- Decorative dots -->
                <circle cx="50" cy="300" r="4" fill="#6366f1" opacity="0.3">
                    <animate attributeName="opacity" values="0.3;0.6;0.3" dur="3s" repeatCount="indefinite"/>
                </circle>
                <circle cx="350" cy="80" r="5" fill="#8b5cf6" opacity="0.3">
                    <animate attributeName="opacity" values="0.3;0.7;0.3" dur="2.5s" repeatCount="indefinite"/>
                </circle>
                <circle cx="70" cy="220" r="3" fill="#6366f1" opacity="0.4">
                    <animate attributeName="opacity" values="0.4;0.8;0.4" dur="3.5s" repeatCount="indefinite"/>
                </circle>
                <circle cx="330" cy="310" r="6" fill="#8b5cf6" opacity="0.3">
                    <animate attributeName="opacity" values="0.3;0.6;0.3" dur="4s" repeatCount="indefinite"/>
                </circle>
            </svg>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('txtpass');
            const toggleIcon = document.getElementById('toggleIcon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                `;
            } else {
                passwordInput.type = 'password';
                toggleIcon.innerHTML = `
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                `;
            }
        }

        // Form input animations
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
            });

            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Submit button loading animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.btn-login');
            const originalText = submitBtn.textContent;

            submitBtn.innerHTML = `
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite; display: inline-block;">
                    <style>
                        @keyframes spin {
                            from { transform: rotate(0deg); }
                            to { transform: rotate(360deg); }
                        }
                    </style>
                    <line x1="12" y1="2" x2="12" y2="6"></line>
                    <line x1="12" y1="18" x2="12" y2="22"></line>
                    <line x1="4.93" y1="4.93" x2="7.76" y2="7.76"></line>
                    <line x1="16.24" y1="16.24" x2="19.07" y2="19.07"></line>
                    <line x1="2" y1="12" x2="6" y2="12"></line>
                    <line x1="18" y1="12" x2="22" y2="12"></line>
                    <line x1="4.93" y1="19.07" x2="7.76" y2="16.24"></line>
                    <line x1="16.24" y1="7.76" x2="19.07" y2="4.93"></line>
                </svg>
                <span style="margin-left: 8px;">Memproses...</span>
            `;
            submitBtn.disabled = true;

            // Remember me functionality
            const rememberMe = document.getElementById('rememberMe').checked;
            const username = document.getElementById('txtnama').value;

            if (rememberMe) {
                localStorage.setItem('rememberMe', 'true');
                localStorage.setItem('username', username);
            } else {
                localStorage.removeItem('rememberMe');
                localStorage.removeItem('username');
            }
        });

        // Load remembered user on page load
        window.addEventListener('load', function() {
            const rememberMe = localStorage.getItem('rememberMe');
            const username = localStorage.getItem('username');

            if (rememberMe === 'true' && username) {
                document.getElementById('txtnama').value = username;
                document.getElementById('rememberMe').checked = true;
            }

            // Add entrance animations
            setTimeout(() => {
                document.querySelector('.login-form-wrapper').style.opacity = '1';
            }, 100);
        });

        // Add ripple effect to button
        document.querySelector('.btn-login').addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;

            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.5);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            `;

            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });

        // Add ripple animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
