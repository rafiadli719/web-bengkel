# 🎯 REKOMENDASI IMPLEMENTASI USER & KARYAWAN

## 📋 Daftar Isi
1. [Ringkasan Eksekutif](#ringkasan-eksekutif)
2. [Analisis Masalah](#analisis-masalah)
3. [Solusi & Implementasi](#solusi--implementasi)
4. [Roadmap Implementasi](#roadmap-implementasi)
5. [Code Examples](#code-examples)

---

## 📊 Ringkasan Eksekutif

### Situasi Saat Ini
- ✅ Database sudah terstruktur dengan baik
- ✅ Ada 11 user dengan 9 role berbeda
- ✅ Ada 23 karyawan dengan berbagai posisi
- ⚠️ Ada duplikasi data user (tbuser & tb_user_account)
- ⚠️ Password masih plain text (tidak aman)
- ⚠️ Tidak ada foreign key constraint
- ⚠️ Activity logging belum digunakan

### Rekomendasi Prioritas
1. **Immediate:** Implementasi login dengan tbuser + RBAC
2. **Short Term:** Migrate ke tb_user_account + password hashing
3. **Medium Term:** Implementasi activity logging
4. **Long Term:** Implementasi security features (2FA, etc)

---

## ⚠️ Analisis Masalah

### Masalah 1: Duplikasi Data User

**Situasi:**
```
tbuser (Legacy)
├─ 11 users
├─ Kolom: nama_user, password, user_akses, status_row
└─ Digunakan di: aplikasi lama

tb_user_account (Modern)
├─ 11 users (sama)
├─ Kolom: username, password_hash, user_akses_level, kode_karyawan
└─ Digunakan di: aplikasi baru (belum)
```

**Dampak:**
- ❌ Maintenance menjadi sulit (update 2 tempat)
- ❌ Data inconsistency (bisa berbeda)
- ❌ Confusion tentang tabel mana yang digunakan

**Solusi:**
```
FASE 1: Gunakan tbuser untuk login (sekarang)
FASE 2: Migrate ke tb_user_account (1-2 minggu)
FASE 3: Hapus tbuser (setelah migration selesai)
```

---

### Masalah 2: Password Plain Text

**Situasi:**
```
tbuser.password: 'admin', '123', '123456'
tb_user_account.password_hash: 'admin', '123', '123456'
```

**Dampak:**
- ❌ Jika database di-hack, semua password terlihat
- ❌ Tidak comply dengan security standards
- ❌ Tidak ada protection untuk user

**Solusi:**
```php
// Gunakan password_hash() atau bcrypt
$password_hash = password_hash('admin', PASSWORD_BCRYPT);
// Output: $2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcg7b3XeKeUxWdeS86E36P4/KFm

// Verifikasi password
if (password_verify('admin', $password_hash)) {
    // Password benar
}
```

---

### Masalah 3: Tidak Ada Foreign Key

**Situasi:**
```
tbuser.user_akses (1-10) → tb_user_roles.role_code (no FK)
tb_master_karyawan.kode_posisi → tb_master_posisi (no FK)
tb_master_karyawan.kode_level → tb_master_level (no FK)
```

**Dampak:**
- ❌ Data inconsistency (user_akses bisa invalid)
- ❌ Orphaned records (kode_posisi tidak ada di master)
- ❌ Tidak ada referential integrity

**Solusi:**
```sql
-- Tambahkan FK constraint
ALTER TABLE tbuser 
ADD CONSTRAINT fk_tbuser_role 
FOREIGN KEY (user_akses) REFERENCES tb_user_roles(role_code);

ALTER TABLE tb_master_karyawan 
ADD CONSTRAINT fk_karyawan_posisi 
FOREIGN KEY (kode_posisi) REFERENCES tb_master_posisi(kode_posisi);

ALTER TABLE tb_master_karyawan 
ADD CONSTRAINT fk_karyawan_level 
FOREIGN KEY (kode_level) REFERENCES tb_master_level(kode_level);
```

---

### Masalah 4: Activity Log Kosong

**Situasi:**
```
tb_user_activity_log: 0 records
```

**Dampak:**
- ❌ Tidak ada audit trail
- ❌ Tidak bisa track siapa yang melakukan apa
- ❌ Tidak bisa detect suspicious activity

**Solusi:**
```php
// Log setiap activity
function logActivity($user_id, $action, $module, $description, $ip_address) {
    $query = "INSERT INTO tb_user_activity_log 
              (user_id, action, module, description, ip_address, user_agent)
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("isssss", $user_id, $action, $module, $description, $ip_address, $_SERVER['HTTP_USER_AGENT']);
    $stmt->execute();
}

// Gunakan di login
logActivity($user_id, 'LOGIN', 'auth', 'User login', $_SERVER['REMOTE_ADDR']);
```

---

## 🔧 Solusi & Implementasi

### Solusi 1: Implementasi Login dengan RBAC

**File:** `login.php` (sudah dibuat)

**Fitur:**
- ✅ Dropdown username dari tbuser
- ✅ Dropdown cabang dari tbcabang
- ✅ Password input
- ✅ Remember me checkbox

**Query:**
```php
// Get username list
$query_user = "SELECT id, nama_user FROM tbuser 
               WHERE status_row='0' AND is_active='active' 
               ORDER BY nama_user ASC";

// Get cabang list
$query_cabang = "SELECT kode_cabang, nama_cabang FROM tbcabang 
                 ORDER BY nama_cabang ASC";
```

---

### Solusi 2: Implementasi Session & RBAC

**File:** `config/session_check.php` (sudah dibuat)

**Fitur:**
- ✅ Session validation
- ✅ Session timeout (30 menit)
- ✅ Access level checking
- ✅ User info retrieval

**Usage:**
```php
// Include di setiap protected page
include "config/session_check.php";

// Check access level
if (!hasAccess(1)) { // 1 = Administrator
    header("Location: unauthorized.php");
    exit;
}
```

---

### Solusi 3: Implementasi RBAC

**File:** `config/rbac.php` (sudah dibuat)

**Fitur:**
- ✅ Role definition
- ✅ Permission checking
- ✅ Access control functions

**Usage:**
```php
// Check if user has permission
if (hasPermission('service_read')) {
    // User bisa read service
}

// Check if user has role
if (hasRole('Administrator')) {
    // User adalah administrator
}
```

---

### Solusi 4: Implementasi Activity Logging

**File:** `config/activity_logger.php` (baru)

**Fitur:**
- ✅ Log user activity
- ✅ Track action & module
- ✅ Capture IP address & user agent

**Code:**
```php
<?php
function logActivity($user_id, $action, $module, $description = null) {
    global $koneksi;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $query = "INSERT INTO tb_user_activity_log 
              (user_id, action, module, description, ip_address, user_agent)
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $koneksi->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isssss", $user_id, $action, $module, $description, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}
?>
```

---

### Solusi 5: Implementasi Password Hashing

**File:** `config/password_utils.php` (baru)

**Code:**
```php
<?php
// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate random password
function generatePassword($length = 12) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $password;
}
?>
```

---

## 📅 Roadmap Implementasi

### FASE 1: IMMEDIATE (Sekarang - Minggu 1)

**Tujuan:** Login & RBAC berfungsi

**Tasks:**
- ✅ Implementasi login.php dengan dropdown user & cabang
- ✅ Implementasi cek_login.php dengan session
- ✅ Implementasi session_check.php middleware
- ✅ Implementasi rbac.php permission system
- ✅ Update protected pages dengan session check

**Deliverables:**
- Login page yang berfungsi
- Session management
- Basic RBAC

**Timeline:** 3-5 hari

---

### FASE 2: SHORT TERM (1-2 minggu)

**Tujuan:** Migrate ke tb_user_account + password hashing

**Tasks:**
- ✅ Implementasi password hashing
- ✅ Migrate data dari tbuser ke tb_user_account
- ✅ Update login untuk gunakan tb_user_account
- ✅ Tambahkan FK constraint
- ✅ Testing & validation

**Deliverables:**
- Password hashing implemented
- Data migration complete
- FK constraint added

**Timeline:** 1-2 minggu

---

### FASE 3: MEDIUM TERM (1 bulan)

**Tujuan:** Activity logging & audit trail

**Tasks:**
- ✅ Implementasi activity_logger.php
- ✅ Add logging di setiap action penting
- ✅ Buat activity log viewer
- ✅ Implementasi audit trail report
- ✅ Testing & validation

**Deliverables:**
- Activity logging working
- Audit trail reports
- Activity log viewer

**Timeline:** 2-3 minggu

---

### FASE 4: LONG TERM (Ongoing)

**Tujuan:** Security features & monitoring

**Tasks:**
- ✅ Implementasi 2FA (Two-Factor Authentication)
- ✅ Implementasi rate limiting
- ✅ Implementasi IP whitelisting
- ✅ Implementasi security monitoring
- ✅ Implementasi automated alerts

**Deliverables:**
- 2FA implemented
- Rate limiting
- Security monitoring

**Timeline:** Ongoing

---

## 💻 Code Examples

### 1. Login dengan RBAC

```php
<?php
session_start();
include "config/koneksi.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['txtnama'] ?? '';
    $password = $_POST['txtpass'] ?? '';
    $cabang = $_POST['cbocabang'] ?? '';
    
    // Query user
    $query = "SELECT * FROM tbuser 
              WHERE nama_user=? AND password=? 
              AND status_row='0' AND is_active='active'";
    
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Set session
        $_SESSION['_iduser'] = $user['id'];
        $_SESSION['_username'] = $user['nama_user'];
        $_SESSION['_cabang'] = $cabang;
        $_SESSION['_user_akses'] = $user['user_akses'];
        $_SESSION['_role_name'] = $user['role_name'];
        
        // Log activity
        logActivity($user['id'], 'LOGIN', 'auth', 'User login');
        
        // Redirect
        header("Location: _admincab/index.php");
        exit;
    } else {
        $_SESSION['login_error'] = 'Username atau password salah!';
    }
}
?>
```

---

### 2. Session Check Middleware

```php
<?php
// config/session_check.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['_iduser'])) {
    header("Location: ../index.php");
    exit;
}

// Check session timeout (30 menit)
$timeout = 30 * 60; // 30 minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
    session_destroy();
    header("Location: ../index.php?timeout=1");
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();

// Get user info
include "koneksi.php";
$user_id = $_SESSION['_iduser'];
$query = "SELECT * FROM tbuser WHERE id=?";
$stmt = $koneksi->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    session_destroy();
    header("Location: ../index.php");
    exit;
}
?>
```

---

### 3. RBAC Permission Check

```php
<?php
// config/rbac.php

$ROLES = [
    1 => ['name' => 'Administrator', 'permissions' => ['all']],
    2 => ['name' => 'CS & Kasir', 'permissions' => ['service_read', 'customer_read', 'payment_read']],
    4 => ['name' => 'Mekanik', 'permissions' => ['service_read', 'service_update_progress']],
    // ... more roles
];

function hasPermission($permission) {
    global $ROLES;
    
    if (!isset($_SESSION['_user_akses'])) {
        return false;
    }
    
    $role_code = $_SESSION['_user_akses'];
    if (!isset($ROLES[$role_code])) {
        return false;
    }
    
    $permissions = $ROLES[$role_code]['permissions'];
    
    // Check if 'all' permission
    if (in_array('all', $permissions)) {
        return true;
    }
    
    // Check specific permission
    return in_array($permission, $permissions);
}

function hasRole($role_name) {
    global $ROLES;
    
    if (!isset($_SESSION['_user_akses'])) {
        return false;
    }
    
    $role_code = $_SESSION['_user_akses'];
    if (!isset($ROLES[$role_code])) {
        return false;
    }
    
    return $ROLES[$role_code]['name'] === $role_name;
}
?>
```

---

### 4. Activity Logging

```php
<?php
// config/activity_logger.php

function logActivity($user_id, $action, $module, $description = null) {
    global $koneksi;
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $query = "INSERT INTO tb_user_activity_log 
              (user_id, action, module, description, ip_address, user_agent)
              VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $koneksi->prepare($query);
    if ($stmt) {
        $stmt->bind_param("isssss", $user_id, $action, $module, $description, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

// Usage
logActivity($_SESSION['_iduser'], 'CREATE', 'service', 'Created new service');
logActivity($_SESSION['_iduser'], 'UPDATE', 'customer', 'Updated customer data');
logActivity($_SESSION['_iduser'], 'DELETE', 'invoice', 'Deleted invoice #123');
?>
```

---

## ✅ Checklist Implementasi

### FASE 1
- [ ] Login page dengan dropdown user & cabang
- [ ] Session management (create, validate, destroy)
- [ ] RBAC permission system
- [ ] Protected pages dengan session check
- [ ] Logout functionality
- [ ] Testing login flow

### FASE 2
- [ ] Password hashing implementation
- [ ] Data migration dari tbuser ke tb_user_account
- [ ] Update login untuk tb_user_account
- [ ] FK constraint added
- [ ] Data validation & consistency check
- [ ] Testing & validation

### FASE 3
- [ ] Activity logging implementation
- [ ] Activity log viewer
- [ ] Audit trail report
- [ ] Log retention policy
- [ ] Testing & validation

### FASE 4
- [ ] 2FA implementation
- [ ] Rate limiting
- [ ] IP whitelisting
- [ ] Security monitoring
- [ ] Automated alerts

---

## 📝 Kesimpulan

**Rekomendasi Prioritas:**
1. ✅ **Immediate:** Implementasi login + RBAC (Fase 1)
2. ✅ **Short Term:** Migrate ke tb_user_account + password hashing (Fase 2)
3. ✅ **Medium Term:** Activity logging + audit trail (Fase 3)
4. ✅ **Long Term:** Security features (Fase 4)

**Estimasi Timeline:**
- Fase 1: 3-5 hari
- Fase 2: 1-2 minggu
- Fase 3: 2-3 minggu
- Fase 4: Ongoing

**Estimasi Effort:**
- Fase 1: 20-30 jam
- Fase 2: 15-20 jam
- Fase 3: 10-15 jam
- Fase 4: 5-10 jam per fitur

---

**Dibuat:** Nov 16, 2025  
**Status:** ✅ Rekomendasi Selesai  
**Next Step:** Implementasi Fase 1
