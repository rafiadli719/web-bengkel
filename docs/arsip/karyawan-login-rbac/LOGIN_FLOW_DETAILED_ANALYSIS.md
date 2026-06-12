# 🔐 LOGIN FLOW - DETAILED ANALYSIS

**System:** FIT MOTOR Bengkel Management  
**Database:** fitmotor_dbbengkel  
**Tanggal:** 16 November 2025

---

## 📊 LOGIN SYSTEM OVERVIEW

### Two Login Systems Exist:

#### System 1: Legacy Login (ACTIVE)
- **File:** `aplikasi/aplikasi/index.php` → `cek_login.php`
- **Database:** `tbuser` table
- **Status:** Currently in use
- **Security:** ⚠️ VULNERABLE

#### System 2: New Login (ALTERNATIVE)
- **File:** `aplikasi/aplikasi/login_dashboard/login.php`
- **Database:** `users` table
- **Status:** Alternative system
- **Security:** ⚠️ PARTIALLY IMPROVED

---

## 🔄 LEGACY LOGIN FLOW (ACTIVE)

### Step 1: Login Page (`index.php`)

**Location:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\index.php`

**HTML Form:**
```html
<form method="post" class="form" action="cek_login.php">
    <label for="txtnama">User Name</label>
    <input name="txtnama" class="form-content" type="text" required />
    
    <label for="user-password">Password</label>
    <input id="user-password" class="form-content" type="password" name="txtpass" required />
    
    <label for="txtnama">Cabang</label>
    <select class="form-content" name="cbocabang" id="cbocabang">
        <option value="">- Pilih -</option>
        <?php
        $sql = "SELECT kode_cabang, nama_cabang FROM tbcabang";
        $sql_row = mysqli_query($koneksi, $sql);
        while ($sql_res = mysqli_fetch_assoc($sql_row)) {
        ?>
            <option value="<?php echo $sql_res['kode_cabang']; ?>">
                <?php echo $sql_res['nama_cabang']; ?>
            </option>
        <?php } ?>
    </select>
    
    <input id="submit-btn" type="submit" name="submit" value="LOGIN" />
</form>
```

**Input Fields:**
- `txtnama` - Username
- `txtpass` - Password
- `cbocabang` - Branch code

**Branches Loaded From:**
```sql
SELECT kode_cabang, nama_cabang FROM tbcabang
```

---

### Step 2: Login Processing (`cek_login.php`)

**Location:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\cek_login.php`

**Phase 1: Configuration Loading**
```php
<?php
session_start();
include 'config/koneksi.php';

// Load Accurate API config
$config_loaded = false;
$config_error = null;

if (file_exists('config/accurate_config.php')) {
    try {
        include_once 'config/accurate_config.php';
        $config_loaded = true;
    } catch (Exception $e) {
        $config_error = "Error loading accurate_config.php: " . $e->getMessage();
        error_log($config_error);
    }
}
```

**Phase 2: Input Sanitization**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txtnama = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $txtpass = mysqli_real_escape_string($koneksi, $_POST['txtpass']);
    $cbocabang = mysqli_real_escape_string($koneksi, $_POST['cbocabang']);
```

⚠️ **Issue:** Using `mysqli_real_escape_string()` is NOT safe for SQL injection!

**Phase 3: Database Query**
```php
$data = mysqli_query($koneksi, "SELECT * FROM tbuser 
                                WHERE nama_user='$txtnama' 
                                AND password='$txtpass' 
                                AND status_row='0'");
$cek = mysqli_num_rows($data);
```

⚠️ **Issues:**
1. Direct string concatenation (SQL injection vulnerable)
2. Plain text password comparison
3. No prepared statements

**Phase 4: Credential Verification**
```php
if ($cek > 0) {
    // Credentials match
    $cari_kd = mysqli_query($koneksi, "SELECT id, user_akses FROM tbuser 
                                       WHERE nama_user='$txtnama'");
    $tm_cari = mysqli_fetch_array($cari_kd);
    $id_user = $tm_cari['id'];
    $lvl_akses = $tm_cari['user_akses'];
} else {
    // Credentials don't match
    $_SESSION['login_error'] = "Username atau Password salah!";
    header("Location: index.php");
    exit;
}
```

**Phase 5: Session Variables Setup**
```php
$_SESSION['_iduser'] = $id_user;
$_SESSION['_cabang'] = $cbocabang;
$_SESSION['user_akses'] = $lvl_akses;
```

**Phase 6: Branch Validation**
```php
if (($lvl_akses == '2' || $lvl_akses == '3' || $lvl_akses == '4' || 
     $lvl_akses == '5') && $cbocabang == '') {
    $_SESSION['login_error'] = "Anda Harus Memilih Cabang Terlebih Dahulu!";
    header("Location: index.php");
    exit;
}
```

**Access Levels Requiring Branch Selection:**
- Level 2: CS
- Level 3: Kasir
- Level 4: Mekanik
- Level 5: Pengadaan

**Phase 7: Accurate API Connection Test**
```php
if ($config_loaded) {
    try {
        $test_host = getAccurateHostForLogin();
        if ($test_host) {
            $_SESSION['accurate_host'] = $test_host;
            $_SESSION['accurate_status'] = 'connected';
        } else {
            $_SESSION['accurate_status'] = 'disconnected';
        }
    } catch (Exception $e) {
        $_SESSION['accurate_status'] = 'error';
        error_log("Accurate API Error: " . $e->getMessage());
    }
}
```

**Phase 8: Redirect Based on Access Level**
```php
$base_url = "https://fitmotor.web.id/beta/aplikasi/";

switch ($lvl_akses) {
    case '1':
        $location = $cbocabang == '' ? '_admin/index.php' : '_admincab/index.php';
        break;
    case '2':
        $location = '_cs/index.php';
        break;
    case '3':
        $location = '_kasir/index.php';
        break;
    case '4':
        $location = '_mekanik/index.php';
        break;
    case '5':
        $location = '_pengadaan/index.php';
        break;
    case '6':
        $location = '_crm/index.php';
        break;
    case '7':
        $location = '_managemen/index.php';
        break;
    case '8':
        $location = '_keuangan/index.php';
        break;
    case '9':
        $location = '_hrd/index.php';
        break;
    default:
        $location = 'index.php';
}

header("Location: $base_url$location");
exit;
```

---

### Step 3: Dashboard Access

**After successful login, user is redirected to:**

| Access Level | Role | Redirect URL |
|---|---|---|
| 1 | Administrator | `_admin/index.php` or `_admincab/index.php` |
| 2 | Customer Service | `_cs/index.php` |
| 3 | Cashier | `_kasir/index.php` |
| 4 | Mechanic | `_mekanik/index.php` |
| 5 | Procurement | `_pengadaan/index.php` |
| 6 | CRM | `_crm/index.php` |
| 7 | Management | `_managemen/index.php` |
| 8 | Finance | `_keuangan/index.php` |
| 9 | HRD | `_hrd/index.php` |

---

## 🔄 NEW LOGIN FLOW (ALTERNATIVE)

### Location: `login_dashboard/login.php`

**Features:**
- Uses `users` table instead of `tbuser`
- Prepared statements (safer)
- Employee & branch selection
- Better error handling

**Flow:**

#### Step 1: Get Branches & Employees
```php
// get_branches.php
SELECT kode_cabang, nama_cabang FROM tbcabang ORDER BY nama_cabang

// get_employees.php
SELECT kode_karyawan, nama_karyawan FROM users ORDER BY nama_karyawan
```

#### Step 2: Form Submission
```html
<form method="POST">
    <select name="karyawan" required>
        <!-- Employees list -->
    </select>
    <select name="cabang" required>
        <!-- Branches list -->
    </select>
    <input type="password" name="password" required>
    <button type="submit">Login</button>
</form>
```

#### Step 3: Input Validation
```php
if (empty($karyawan)) {
    $error_message = "Silakan pilih karyawan.";
} elseif (empty($cabang_input)) {
    $error_message = "Silakan pilih cabang.";
} elseif (empty($password)) {
    $error_message = "Silakan masukkan password.";
}
```

#### Step 4: Prepared Statement Query
```php
$sql_check_user = "SELECT kode_karyawan, nama_karyawan, password, role, 
                          nama_cabang, kode_cabang FROM users 
                   WHERE kode_karyawan = ?";
$stmt_check_user = $mysqli->prepare($sql_check_user);
$stmt_check_user->bind_param("s", $karyawan);
$stmt_check_user->execute();
$stmt_check_user->bind_result($kode_karyawan_db, $nama_karyawan, $password_db, 
                              $role, $db_nama_cabang, $db_kode_cabang);
```

#### Step 5: Password Verification
```php
if ($stmt_check_user->fetch()) {
    if ($password === $password_db) {
        // Password matches
        $_SESSION['kode_karyawan'] = $kode_karyawan_db;
        $_SESSION['nama_karyawan'] = $nama_karyawan;
        $_SESSION['role'] = $role;
        $_SESSION['kode_cabang'] = $kode_cabang;
        $_SESSION['nama_cabang'] = $nama_cabang;
        
        header("Location: dashboard/index.php");
    } else {
        $error_message = "Password salah!";
    }
} else {
    $error_message = "Karyawan tidak ditemukan!";
}
```

---

## 📋 TBUSER TABLE STRUCTURE

**Table:** `tbuser`

```sql
CREATE TABLE `tbuser` (
  `id` INT(11) PRIMARY KEY,
  `nama_user` VARCHAR(25) NOT NULL UNIQUE,
  `password` VARCHAR(25) NOT NULL,  -- PLAIN TEXT!
  `foto_user` VARCHAR(100),
  `status_row` VARCHAR(1) DEFAULT '0',  -- 0=active, 1=deleted
  `user_akses` INT(11) NOT NULL,  -- Access level 1-9
  `role_name` VARCHAR(50),
  `department` VARCHAR(50),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL,
  `is_active` ENUM('active','inactive') DEFAULT 'active'
);
```

**Sample Data:**
```
ID | nama_user      | password | user_akses | role_name | status_row
---|----------------|----------|------------|-----------|----------
1  | admin          | admin    | 1          | Admin     | 0
2  | cs             | 123      | 2          | CS        | 0
3  | kasir          | 123      | 3          | Kasir     | 0
4  | mekanik        | 123      | 4          | Mekanik   | 0
5  | pengadaan      | 123      | 5          | Pengadaan | 0
6  | crm            | 123      | 6          | CRM       | 0
7  | managemen      | 123      | 7          | Manager   | 0
8  | keuangan       | 123      | 8          | Finance   | 0
9  | hrd            | 123      | 9          | HRD       | 0
10 | kepala_mekanik1| 123456   | 10         | Kepala MK | 1
11 | kepala_mekanik2| 123456   | 10         | Kepala MK | 1
```

---

## 📋 USERS TABLE STRUCTURE (NEW SYSTEM)

**Table:** `users`

```sql
CREATE TABLE `users` (
  `id` INT(11) PRIMARY KEY,
  `kode_karyawan` VARCHAR(20) NOT NULL UNIQUE,
  `nama_karyawan` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255),  -- Should be hashed
  `role` VARCHAR(50),
  `kode_cabang` VARCHAR(20),
  `nama_cabang` VARCHAR(100),
  `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🔐 SESSION VARIABLES AFTER LOGIN

### Legacy System (`tbuser`):
```php
$_SESSION['_iduser']         // User ID (int)
$_SESSION['_cabang']         // Branch code (string)
$_SESSION['user_akses']      // Access level (int: 1-9)
$_SESSION['accurate_host']   // Accurate API host (string)
$_SESSION['accurate_status'] // API status (string: connected/disconnected/error)
```

### New System (`users`):
```php
$_SESSION['kode_karyawan']   // Employee code (string)
$_SESSION['nama_karyawan']   // Employee name (string)
$_SESSION['role']            // Role/position (string)
$_SESSION['kode_cabang']     // Branch code (string)
$_SESSION['nama_cabang']     // Branch name (string)
```

---

## 🔒 SECURITY VULNERABILITIES

### 1. SQL Injection
**Vulnerable Code:**
```php
$data = mysqli_query($koneksi, "SELECT * FROM tbuser 
                                WHERE nama_user='$txtnama' 
                                AND password='$txtpass'");
```

**Attack Example:**
```
Username: admin' OR '1'='1
Password: anything
Result: Bypasses authentication
```

**Fix:** Use prepared statements
```php
$stmt = $koneksi->prepare("SELECT * FROM tbuser WHERE nama_user=? AND password=?");
$stmt->bind_param("ss", $txtnama, $txtpass);
$stmt->execute();
```

### 2. Plain Text Passwords
**Issue:** Passwords stored as plain text in database
```
Password: 123
Stored as: 123 (readable)
```

**Fix:** Use password hashing
```php
$hashed = password_hash($password, PASSWORD_DEFAULT);
// Verify: password_verify($input, $hashed)
```

### 3. No Session Timeout
**Issue:** Sessions never expire
**Fix:** Add session timeout
```php
$timeout = 30 * 60; // 30 minutes
if (time() - $_SESSION['last_activity'] > $timeout) {
    session_destroy();
    header("Location: index.php?expired=1");
}
$_SESSION['last_activity'] = time();
```

### 4. No CSRF Protection
**Issue:** No CSRF tokens in forms
**Fix:** Add CSRF token
```php
// Generate token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In form
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

// Verify
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("CSRF token validation failed");
}
```

### 5. No Rate Limiting
**Issue:** Unlimited login attempts
**Fix:** Implement rate limiting
```php
$attempts_key = "login_attempts_" . $_SERVER['REMOTE_ADDR'];
$attempts = $_SESSION[$attempts_key] ?? 0;

if ($attempts >= 5) {
    die("Too many login attempts. Please try again later.");
}

if (login_failed) {
    $_SESSION[$attempts_key] = $attempts + 1;
}
```

---

## ✅ RECOMMENDED SECURITY IMPROVEMENTS

### Priority 1 (URGENT):
1. **Implement Password Hashing**
   - Use `password_hash()` for new passwords
   - Migrate existing passwords
   - Use `password_verify()` for login

2. **Convert to Prepared Statements**
   - Replace all `mysqli_real_escape_string()` calls
   - Use parameterized queries
   - Prevent SQL injection

3. **Add Session Timeout**
   - Implement 30-minute timeout
   - Regenerate session ID after login
   - Clear session on logout

### Priority 2 (HIGH):
4. **Add CSRF Protection**
   - Generate tokens for all forms
   - Validate tokens on submission

5. **Implement Rate Limiting**
   - Limit login attempts
   - Block after N failures
   - Add delay between attempts

6. **Add Audit Logging**
   - Log all login attempts
   - Log failed authentications
   - Track user activities

### Priority 3 (MEDIUM):
7. **Implement 2FA**
   - Add OTP or authenticator app
   - Require 2FA for admin accounts

8. **Add Account Lockout**
   - Lock account after N failed attempts
   - Require admin unlock

9. **Implement Password Policy**
   - Minimum length (12 chars)
   - Complexity requirements
   - Password expiration

---

## 📊 LOGIN STATISTICS

**Test Accounts:**
```
Username: admin      | Password: admin    | Level: 1 (Admin)
Username: cs         | Password: 123      | Level: 2 (CS)
Username: kasir      | Password: 123      | Level: 3 (Kasir)
Username: mekanik    | Password: 123      | Level: 4 (Mekanik)
Username: pengadaan  | Password: 123      | Level: 5 (Pengadaan)
Username: crm        | Password: 123      | Level: 6 (CRM)
Username: managemen  | Password: 123      | Level: 7 (Manager)
Username: keuangan   | Password: 123      | Level: 8 (Finance)
Username: hrd        | Password: 123      | Level: 9 (HRD)
```

⚠️ **All passwords are weak and easily guessable!**

---

## 🔗 RELATED FILES

**Login Related:**
- `aplikasi/aplikasi/index.php` - Login form
- `aplikasi/aplikasi/cek_login.php` - Login processor
- `aplikasi/aplikasi/login_dashboard/login.php` - Alternative login
- `aplikasi/aplikasi/login_dashboard/logout.php` - Logout handler

**Configuration:**
- `aplikasi/aplikasi/config/koneksi.php` - Database connection
- `aplikasi/aplikasi/config/accurate_config.php` - Accurate API config

**Protected Pages:**
- `aplikasi/aplikasi/_admincab/index.php` - Admin dashboard
- `aplikasi/aplikasi/_admin/index.php` - Admin panel
- `aplikasi/aplikasi/_cs/index.php` - CS dashboard
- etc.

---

**Last Updated:** 16 November 2025  
**Version:** 1.0  
**Status:** ⚠️ CRITICAL SECURITY ISSUES - URGENT FIXES NEEDED
