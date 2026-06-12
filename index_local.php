<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "config/koneksi.php";

// Destroy session if user is already logged in
if (isset($_SESSION['_iduser'])) {
    header("Location: _admincab/index.php");
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
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: center;
            max-width: 1000px;
            width: 100%;
        }

        .login-form-wrapper {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInLeft 0.8s ease-out;
        }

        .login-illustration {
            display: flex;
            justify-content: center;
            align-items: center;
            animation: slideInRight 0.8s ease-out;
        }

        .brand-title {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }

        .brand-title span {
            color: #667eea;
        }

        .form-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 30px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: #555;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            width: 100%;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
            background-color: #fbfbfb;
            color: #333;
            box-sizing: border-box;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
            outline: none;
            background-color: #fff;
        }

        .form-control::placeholder {
            color: #999;
        }

        .input-group {
            display: flex;
            align-items: center;
            position: relative;
        }

        .input-group-text {
            position: absolute;
            left: 12px;
            color: #667eea;
            font-size: 16px;
            pointer-events: none;
        }

        .input-group .form-control {
            padding-left: 40px;
        }

        .input-group .password-toggle {
            position: absolute;
            right: 12px;
            cursor: pointer;
            color: #667eea;
            font-size: 16px;
            user-select: none;
            transition: color 0.3s ease;
        }

        .input-group .password-toggle:hover {
            color: #764ba2;
        }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 12px;
            padding-right: 36px;
            cursor: pointer;
            min-height: 44px;
        }

        select.form-control option {
            padding: 10px;
            background-color: #fff;
            color: #333;
            line-height: 1.5;
        }

        select.form-control option:checked {
            background: linear-gradient(#667eea, #667eea);
            background-color: #667eea !important;
            color: white !important;
        }

        .form-check {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .form-check-input {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
            accent-color: #667eea;
        }

        .form-check-label {
            font-size: 13px;
            color: #666;
            cursor: pointer;
        }

        .forgot-password {
            font-size: 13px;
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .forgot-password:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .alert {
            border-radius: 10px;
            border: none;
            margin-bottom: 20px;
            font-size: 13px;
            padding: 12px 16px;
        }

        .alert-danger {
            background-color: #fee;
            color: #c33;
        }

        .alert-success {
            background-color: #efe;
            color: #3c3;
        }

        .loading-spinner {
            display: none;
            margin-right: 8px;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.3);
            pointer-events: none;
        }

        .particle-1 {
            width: 100px;
            height: 100px;
            top: 10%;
            right: 10%;
            animation: float 4s ease-in-out infinite;
        }

        .particle-2 {
            width: 60px;
            height: 60px;
            top: 60%;
            right: 5%;
            animation: float 5s ease-in-out infinite 1s;
        }

        .particle-3 {
            width: 80px;
            height: 80px;
            top: 30%;
            left: 5%;
            animation: float 6s ease-in-out infinite 2s;
        }

        .particle-4 {
            width: 50px;
            height: 50px;
            bottom: 10%;
            left: 10%;
            animation: float 4.5s ease-in-out infinite 1.5s;
        }

        .svg-illustration {
            position: relative;
            width: 350px;
            height: 350px;
            filter: drop-shadow(0 0 10px rgba(102, 126, 234, 0.6));
            animation: pulse 2s ease-in-out infinite;
        }

        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .login-form-wrapper {
                padding: 30px;
            }

            .login-illustration {
                display: none;
            }

            .form-title {
                font-size: 24px;
            }

            .brand-title {
                font-size: 20px;
            }

            .particle {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Form Section -->
        <div class="login-form-wrapper">
            <div class="brand-title">
                FIT <span>MOTOR</span>
            </div>

            <div class="form-title">Log in</div>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger">
                    ⚠️ <?php echo $_SESSION['login_error']; ?>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['login_success'])): ?>
                <div class="alert alert-success">
                    ✓ <?php echo $_SESSION['login_success']; ?>
                </div>
                <?php unset($_SESSION['login_success']); ?>
            <?php endif; ?>

            <form method="post" action="cek_login.php" id="loginForm">
                <!-- Username -->
                <div class="form-group">
                    <label for="txtnama">Username</label>
                    <div class="input-group">
                        <span class="input-group-text">👤</span>
                        <input type="text" class="form-control" id="txtnama" name="txtnama" 
                               placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="txtpass">Password</label>
                    <div class="input-group">
                        <span class="input-group-text">🔒</span>
                        <input type="password" class="form-control" id="txtpass" name="txtpass" 
                               placeholder="Masukkan password" required autocomplete="off">
                        <span class="password-toggle" onclick="togglePassword()" title="Toggle password visibility">👁️</span>
                    </div>
                </div>

                <!-- Branch Selection -->
                <div class="form-group">
                    <label for="cbocabang">Cabang</label>
                    <div class="input-group">
                        <span class="input-group-text">🏢</span>
                        <select class="form-control" id="cbocabang" name="cbocabang" required>
                            <option value="">-- Pilih Cabang --</option>
                            <?php
                            $sql = "SELECT kode_cabang, nama_cabang FROM tbcabang WHERE status_row='0' ORDER BY nama_cabang";
                            $sql_row = mysqli_query($koneksi, $sql);
                            $cabang_count = 0;
                            
                            if ($sql_row && mysqli_num_rows($sql_row) > 0) {
                                while ($sql_res = mysqli_fetch_assoc($sql_row)) {
                                    echo '<option value="' . htmlspecialchars($sql_res['kode_cabang']) . '">' . htmlspecialchars($sql_res['nama_cabang']) . '</option>';
                                    $cabang_count++;
                                }
                            } else {
                                // Fallback data jika tabel kosong
                                $fallback_cabang = [
                                    ['C001', 'Cabang Jakarta Pusat'],
                                    ['C002', 'Cabang Jakarta Selatan'],
                                    ['C003', 'Cabang Jakarta Timur'],
                                    ['C004', 'Cabang Jakarta Barat'],
                                    ['C005', 'Cabang Tangerang'],
                                    ['C006', 'Cabang Bekasi']
                                ];
                                
                                foreach ($fallback_cabang as $cabang) {
                                    echo '<option value="' . htmlspecialchars($cabang[0]) . '">' . htmlspecialchars($cabang[1]) . '</option>';
                                    $cabang_count++;
                                }
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
                <button type="submit" class="btn-login">
                    <span class="loading-spinner" id="loadingSpinner"></span>
                    <span id="btnText">Log in</span>
                </button>
            </form>
        </div>

        <!-- Illustration Section -->
        <div class="login-illustration">
            <!-- Floating Particles -->
            <div class="particle particle-1"></div>
            <div class="particle particle-2"></div>
            <div class="particle particle-3"></div>
            <div class="particle particle-4"></div>

            <svg class="svg-illustration" viewBox="0 0 350 350" fill="none" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#667eea;stop-opacity:0.1" />
                        <stop offset="100%" style="stop-color:#764ba2;stop-opacity:0.1" />
                    </linearGradient>
                    <linearGradient id="monitorGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                    </linearGradient>
                </defs>

                <!-- Background Circle -->
                <circle cx="175" cy="175" r="160" fill="url(#bgGradient)" stroke="#667eea" stroke-width="1" opacity="0.3"/>

                <!-- Monitor Frame -->
                <rect x="70" y="50" width="210" height="140" rx="12" fill="url(#monitorGradient)" stroke="#667eea" stroke-width="3"/>
                <rect x="80" y="60" width="190" height="115" rx="8" fill="#ffffff" stroke="none"/>
                <rect x="85" y="65" width="180" height="105" rx="6" fill="#f8f9ff"/>

                <!-- Monitor Stand -->
                <rect x="155" y="195" width="40" height="25" rx="4" fill="url(#monitorGradient)"/>
                <ellipse cx="175" cy="225" rx="55" ry="8" fill="url(#monitorGradient)" opacity="0.5"/>

                <!-- User Icon -->
                <circle cx="175" cy="80" r="15" fill="#667eea" stroke="#764ba2" stroke-width="2"/>
                <path d="M 165 100 Q 175 108 185 100" stroke="#667eea" stroke-width="2.5" fill="none" stroke-linecap="round"/>

                <!-- Username Field -->
                <rect x="95" y="115" width="160" height="18" rx="4" fill="#e8e8ff" stroke="#667eea" stroke-width="1.5"/>
                <circle cx="105" cy="124" r="2" fill="#667eea"/>
                <line x1="112" y1="124" x2="140" y2="124" stroke="#667eea" stroke-width="1.5" stroke-linecap="round"/>

                <!-- Password Field -->
                <rect x="95" y="140" width="160" height="18" rx="4" fill="#e8e8ff" stroke="#667eea" stroke-width="1.5"/>
                <circle cx="105" cy="149" r="2" fill="#667eea"/>
                <circle cx="115" cy="149" r="2" fill="#667eea"/>
                <circle cx="125" cy="149" r="2" fill="#667eea"/>
                <circle cx="135" cy="149" r="2" fill="#667eea"/>
                <circle cx="145" cy="149" r="2" fill="#667eea"/>

                <!-- Login Button -->
                <rect x="110" y="165" width="130" height="22" rx="6" fill="#FF6B6B" stroke="#FF5252" stroke-width="2"/>
                <text x="175" y="180" font-family="Arial" font-size="12" font-weight="600" fill="white" text-anchor="middle">Log in</text>

                <!-- Lock Icon -->
                <g transform="translate(280, 70)">
                    <rect x="0" y="5" width="16" height="12" rx="2" fill="none" stroke="#667eea" stroke-width="1.5"/>
                    <path d="M 4 5 V 2 Q 4 0 6 0 L 10 0 Q 12 0 12 2 V 5" stroke="#667eea" stroke-width="1.5" fill="none" stroke-linecap="round"/>
                    <circle cx="8" cy="11" r="1.5" fill="#667eea"/>
                </g>
            </svg>
        </div>
    </div>

    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const passwordInput = document.getElementById('txtpass');
            const toggleIcon = document.querySelector('.password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }

        // Form Submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const loadingSpinner = document.getElementById('loadingSpinner');
            const btnText = document.getElementById('btnText');
            
            loadingSpinner.style.display = 'inline-block';
            btnText.textContent = 'Sedang Login...';
        });

        // Remember Me functionality
        window.addEventListener('load', function() {
            const rememberMe = localStorage.getItem('rememberMe');
            const username = localStorage.getItem('username');
            
            if (rememberMe === 'true' && username) {
                document.getElementById('txtnama').value = username;
                document.getElementById('rememberMe').checked = true;
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function() {
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
    </script>
</body>
</html>
