<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Password Baru - Dashboard User</title>
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
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: var(--card-shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-hover));
        }

        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            padding: 32px 32px 24px;
            text-align: center;
            position: relative;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 15px solid var(--primary-hover);
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .header i {
            font-size: 32px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 500;
        }

        .form-container {
            padding: 40px 32px 32px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: var(--primary-color);
            width: 16px;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            transition: var(--transition);
            background: white;
            position: relative;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-control.error {
            border-color: var(--danger-color);
            background: rgba(239, 68, 68, 0.05);
        }

        .form-control.success {
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.05);
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
            color: var(--text-muted);
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .password-strength {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
        }

        .strength-weak {
            color: var(--danger-color);
        }

        .strength-medium {
            color: var(--warning-color);
        }

        .strength-strong {
            color: var(--success-color);
        }

        .error-message {
            color: var(--danger-color);
            font-size: 14px;
            font-weight: 500;
            margin-top: 8px;
            display: none;
            padding: 8px 12px;
            background: rgba(239, 68, 68, 0.1);
            border-radius: 8px;
            border-left: 4px solid var(--danger-color);
        }

        .error-message.show {
            display: block;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            width: 100%;
            min-height: 54px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-hover));
            color: white;
            box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background: var(--background-light);
            color: var(--text-dark);
            border: 2px solid var(--border-color);
            margin-top: 12px;
        }

        .btn-secondary:hover {
            background: var(--border-color);
            transform: translateY(-1px);
        }

        .btn-toggle {
            background: var(--success-color);
            color: white;
            margin-bottom: 20px;
            font-size: 14px;
            padding: 12px 20px;
            min-height: auto;
        }

        .btn-toggle:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 32px;
            border-radius: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: var(--card-shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: scale(0.8) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .modal-icon {
            font-size: 48px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .modal-content h2 {
            margin-bottom: 16px;
            color: var(--text-dark);
            font-size: 24px;
            font-weight: 700;
        }

        .modal-content p {
            margin-bottom: 24px;
            color: var(--text-muted);
            font-size: 16px;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .modal-buttons button {
            flex: 1;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
        }

        .yes-button {
            background: var(--success-color);
            color: white;
        }

        .yes-button:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .no-button {
            background: var(--danger-color);
            color: white;
        }

        .no-button:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .info-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            border-left: 4px solid var(--primary-color);
        }

        .info-card h3 {
            color: var(--primary-color);
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .info-list li {
            color: var(--text-dark);
            font-size: 14px;
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-list li i {
            color: var(--primary-color);
            width: 16px;
            font-size: 12px;
        }

        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top: 2px solid #fff;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .btn-primary.loading {
            pointer-events: none;
        }

        .btn-primary.loading .loading {
            display: inline-block;
        }

        .btn-primary.loading .btn-text {
            display: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                max-width: none;
            }

            .header {
                padding: 24px 20px 20px;
            }

            .header h1 {
                font-size: 24px;
            }

            .form-container {
                padding: 24px 20px;
            }

            .modal-content {
                margin: 20px;
                padding: 24px;
            }

            .modal-buttons {
                flex-direction: column;
            }
        }

        /* Password strength indicator */
        .strength-indicator {
            margin-top: 8px;
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            transition: var(--transition);
            border-radius: 2px;
        }

        .strength-bar.weak {
            width: 33%;
            background: var(--danger-color);
        }

        .strength-bar.medium {
            width: 66%;
            background: var(--warning-color);
        }

        .strength-bar.strong {
            width: 100%;
            background: var(--success-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-key"></i> Buat Password Baru</h1>
            <p>Keamanan akun Anda adalah prioritas utama</p>
        </div>
        
        <div class="form-container">
            <div class="info-card">
                <h3><i class="fas fa-shield-alt"></i> Panduan Password Aman</h3>
                <ul class="info-list">
                    <li><i class="fas fa-check"></i> Minimal 5 karakter</li>
                    <li><i class="fas fa-check"></i> Kombinasi huruf dan angka</li>
                    <li><i class="fas fa-check"></i> Gunakan karakter khusus (!@#$%)</li>
                    <li><i class="fas fa-check"></i> Hindari informasi pribadi</li>
                </ul>
            </div>
            
            <form action="proses_buat_password.php" method="POST" id="passwordForm">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password Baru
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="strength-indicator">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Konfirmasi Password Baru
                    </label>
                    <div class="password-input-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="error-message" id="errorMessage"></div>
                </div>

                <button type="button" id="toggleAllButton" class="btn btn-toggle" onclick="toggleAllPasswords()">
                    <i class="fas fa-eye"></i> Tampilkan Semua Password
                </button>

                <button type="submit" class="btn btn-primary" id="submitButton">
                    <span class="btn-text">
                        <i class="fas fa-save"></i> Buat Password
                    </span>
                    <div class="loading"></div>
                </button>
                
                <a href="../dashboard/dashboard_user.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h2>Konfirmasi Ganti Password</h2>
            <p>Apakah Anda yakin ingin mengganti password? Pastikan Anda mengingat password baru.</p>
            <div class="modal-buttons">
                <button class="yes-button" onclick="submitForm()">
                    <i class="fas fa-check"></i> Ya, Ganti Password
                </button>
                <button class="no-button" onclick="closeModal()">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
        </div>
    </div>

    <script>
        let passwordVisible = false;
        let isSubmitting = false;

        // Password strength checker
        function checkPasswordStrength(password) {
            let strength = 0;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('passwordStrength');
            
            if (password.length >= 5) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            strengthBar.className = 'strength-bar';
            
            if (strength < 2) {
                strengthBar.classList.add('weak');
                strengthText.textContent = 'Password lemah';
                strengthText.className = 'password-strength strength-weak';
            } else if (strength < 4) {
                strengthBar.classList.add('medium');
                strengthText.textContent = 'Password sedang';
                strengthText.className = 'password-strength strength-medium';
            } else {
                strengthBar.classList.add('strong');
                strengthText.textContent = 'Password kuat';
                strengthText.className = 'password-strength strength-strong';
            }
        }

        // Toggle individual password visibility
        function togglePassword(fieldId, button) {
            const field = document.getElementById(fieldId);
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                field.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        // Toggle all passwords
        function toggleAllPasswords() {
            const newPasswordField = document.getElementById('new_password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const toggleButton = document.getElementById('toggleAllButton');
            const toggleButtons = document.querySelectorAll('.password-toggle');
            
            if (passwordVisible) {
                newPasswordField.type = 'password';
                confirmPasswordField.type = 'password';
                toggleButton.innerHTML = '<i class="fas fa-eye"></i> Tampilkan Semua Password';
                toggleButtons.forEach(btn => {
                    btn.querySelector('i').className = 'fas fa-eye';
                });
            } else {
                newPasswordField.type = 'text';
                confirmPasswordField.type = 'text';
                toggleButton.innerHTML = '<i class="fas fa-eye-slash"></i> Sembunyikan Semua Password';
                toggleButtons.forEach(btn => {
                    btn.querySelector('i').className = 'fas fa-eye-slash';
                });
            }
            
            passwordVisible = !passwordVisible;
        }

        // Form validation
        function validateForm() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorMessage = document.getElementById('errorMessage');
            const newPasswordField = document.getElementById('new_password');
            const confirmPasswordField = document.getElementById('confirm_password');
            
            // Reset previous states
            errorMessage.classList.remove('show');
            newPasswordField.classList.remove('error', 'success');
            confirmPasswordField.classList.remove('error', 'success');
            
            if (newPassword.length < 5) {
                showError("Password harus memiliki minimal 5 karakter.", newPasswordField);
                return false;
            }
            
            if (newPassword !== confirmPassword) {
                showError("Password dan konfirmasi password tidak cocok.", confirmPasswordField);
                return false;
            }
            
            // Success state
            newPasswordField.classList.add('success');
            confirmPasswordField.classList.add('success');
            return true;
        }

        // Show error message
        function showError(message, field) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorMessage.classList.add('show');
            field.classList.add('error');
            field.focus();
        }

        // Form submission handler
        function confirmPasswordChange(event) {
            event.preventDefault();
            
            if (isSubmitting) return;
            
            if (validateForm()) {
                document.getElementById('confirmationModal').style.display = 'flex';
            }
        }

        // Close modal
        function closeModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        // Submit form
        function submitForm() {
            if (isSubmitting) return;
            
            isSubmitting = true;
            const submitButton = document.getElementById('submitButton');
            submitButton.classList.add('loading');
            
            closeModal();
            
            // Simulate loading for better UX
            setTimeout(() => {
                document.getElementById('passwordForm').submit();
            }, 1000);
        }

        // Real-time password strength checking
        document.getElementById('new_password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        // Real-time validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            const errorMessage = document.getElementById('errorMessage');
            
            if (confirmPassword && newPassword !== confirmPassword) {
                this.classList.add('error');
                this.classList.remove('success');
                errorMessage.textContent = 'Password tidak cocok';
                errorMessage.classList.add('show');
            } else {
                this.classList.remove('error');
                errorMessage.classList.remove('show');
                if (confirmPassword && newPassword === confirmPassword) {
                    this.classList.add('success');
                }
            }
        });

        // Form submission
        document.getElementById('passwordForm').addEventListener('submit', confirmPasswordChange);

        // Close modal on outside click
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target === modal) {
                closeModal();
            }
        });

        // Prevent form submission on Enter key in password fields
        document.addEventListener('keypress', function(event) {
            if (event.key === 'Enter' && (event.target.type === 'password' || event.target.type === 'text')) {
                event.preventDefault();
                document.getElementById('passwordForm').dispatchEvent(new Event('submit'));
            }
        });

        // Add notification function
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 3000;
                padding: 16px 20px;
                border-radius: 12px;
                color: white;
                font-weight: 600;
                box-shadow: var(--card-shadow-lg);
                transform: translateX(100%);
                transition: var(--transition);
                max-width: 400px;
                background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#f59e0b'};
            `;
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>