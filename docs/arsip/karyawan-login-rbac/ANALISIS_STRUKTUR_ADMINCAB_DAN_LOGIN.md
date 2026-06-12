# 📊 ANALISIS LENGKAP: Struktur _admincab & Login System

**Folder:** `aplikasi/aplikasi/_admincab/`  
**Login:** `aplikasi/aplikasi/login_dashboard/`  
**Database:** fitmotor_dbbengkel  
**Tanggal Analisis:** 16 November 2025

---

## 🗂️ STRUKTUR FOLDER _ADMINCAB

### Total Files: 51+ files
**Kategori Utama:**
1. **Master Data Management** (barang, keluhan, workorder, level, posisi)
2. **AJAX Handlers** (search, get, save, delete operations)
3. **Utility Files** (config, patch, logs)
4. **Backup/Copy Files** (deprecated versions)

---

## 📋 FILE UTAMA DALAM _ADMINCAB

### 1. **INDEX & DASHBOARD**
```
index.php                    - Halaman utama admin cabang
dashboard/                   - Dashboard folder (22 items)
menu_master01a.php          - Menu sidebar untuk master data
menu_adm01.php              - Menu admin
menu_servis01.php           - Menu service
```

### 2. **MASTER DATA - BARANG**
```
barang.php                  - Halaman daftar barang (MAIN)
barang_add.php              - Form tambah barang
barang_add_improved.php     - Form tambah barang (improved version)
barang_edit.php             - Form edit barang
barang_edit_improved.php    - Form edit barang (improved version)
barang_del.php              - Proses delete barang
barang_asli.php             - Backup file
barang_kategori.php         - Master kategori barang
barang_kategori_add.php     - Tambah kategori
barang_kategori_edit.php    - Edit kategori
barang_kategori_del.php     - Delete kategori
barang_kartu_stok.php       - Kartu stok barang
barang_kartu_stok_rst.php   - Reset kartu stok
barang_history_hp.php       - History barang
barang_list_improved.php    - View card mode barang
barang_rst.php              - Reset/search barang
barang-search-popup.php     - Search popup
barang_edit_proses.php      - Proses edit barang
```

### 3. **MASTER DATA - KELUHAN**
```
master-keluhan.php          - Master keluhan (MAIN)
master-keluhan-crud.php     - CRUD operations untuk keluhan
master-workorder-mapping.php - Mapping keluhan ke workorder
```

### 4. **AJAX HANDLERS**
```
ajax-search-barang.php              - Search barang
ajax-search-keluhan-dynamic.php     - Search keluhan dinamis
ajax-search-workorder-dynamic.php   - Search workorder dinamis
ajax-get-customer-by-vehicle.php    - Get customer by vehicle
ajax-check-mapping-exists.php       - Check mapping exists
ajax-submit-keluhan-baru-debug.php  - Submit keluhan baru
ajax-save-proses-tracking.php       - Save proses tracking
ajax-preview-proses.php             - Preview proses
ajax-hitung-tarif-jemput.php        - Hitung tarif jemput
ajax-hapus-keluhan-workorder.php    - Hapus keluhan workorder
ajax-get-workorder-detail.php       - Get workorder detail
```

### 5. **UTILITY & CONFIG**
```
apply_patch.php             - Apply patch
AUTO_PATCH.bat              - Batch file untuk patch
config/                     - Config folder (5 items)
lib/                        - Library folder (5 items)
```

### 6. **LOG & DEBUG FILES**
```
accurate_debug.log          - Debug log untuk Accurate API
accurate_*.log.txt          - Various log files
error_log                   - Error log
```

### 7. **BACKUP/DEPRECATED FILES**
```
barang_add - Copy.php
barang_edit - Copy.php
barang_edit_proses - Copy.php
barang_kategori_backup.php
barang_kategori_del_old.php
barang_kategori_del_new.php
```

---

## 🔐 LOGIN SYSTEM ANALYSIS

### Folder: `login_dashboard/`

#### Files:
```
login.php               - Main login page (51KB)
logout.php              - Logout handler
config.php              - Configuration
get_branches.php        - Get branches data
get_employees.php       - Get employees data
dashboard/              - Dashboard folder (22 items)
password/               - Password management (7 items)
error_log               - Error log (89KB)
```

---

## 🔑 LOGIN FLOW ANALYSIS

### 1. **Legacy Login System** (`cek_login.php`)

**Location:** `aplikasi/aplikasi/cek_login.php`

**Flow:**
```
User Input (index.php)
    ↓
POST to cek_login.php
    ↓
Query tbuser table
    ↓
Check credentials (PLAIN TEXT PASSWORD!)
    ↓
Set session variables
    ↓
Redirect based on user_akses level
```

**Code Structure:**
```php
// 1. Session start & config load
session_start();
include 'config/koneksi.php';

// 2. Load Accurate API config
if (file_exists('config/accurate_config.php')) {
    include_once 'config/accurate_config.php';
}

// 3. POST request handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txtnama = mysqli_real_escape_string($koneksi, $_POST['txtnama']);
    $txtpass = mysqli_real_escape_string($koneksi, $_POST['txtpass']);
    $cbocabang = mysqli_real_escape_string($koneksi, $_POST['cbocabang']);
    
    // 4. Query database
    $data = mysqli_query($koneksi, "SELECT * FROM tbuser 
                                    WHERE nama_user='$txtnama' 
                                    AND password='$txtpass' 
                                    AND status_row='0'");
    
    // 5. Check result
    if (mysqli_num_rows($data) > 0) {
        // 6. Get user data
        $cari_kd = mysqli_query($koneksi, "SELECT id, user_akses FROM tbuser 
                                           WHERE nama_user='$txtnama'");
        $tm_cari = mysqli_fetch_array($cari_kd);
        
        // 7. Set session
        $_SESSION['_iduser'] = $tm_cari['id'];
        $_SESSION['_cabang'] = $cbocabang;
        $_SESSION['user_akses'] = $tm_cari['user_akses'];
        
        // 8. Redirect based on access level
        switch ($lvl_akses) {
            case '1': $location = '_admin/index.php'; break;
            case '2': $location = '_cs/index.php'; break;
            case '3': $location = '_kasir/index.php'; break;
            // ... etc
        }
        
        header("Location: $base_url$location");
    } else {
        $_SESSION['login_error'] = "Username atau Password salah!";
        header("Location: index.php");
    }
}
```

**Session Variables Set:**
```php
$_SESSION['_iduser']         - User ID dari tbuser
$_SESSION['_cabang']         - Kode cabang (dari form)
$_SESSION['user_akses']      - Access level (1-9)
$_SESSION['accurate_host']   - Accurate API host
$_SESSION['accurate_status'] - Accurate API connection status
```

**Access Levels & Redirects:**
```
Level 1 (Admin)      → _admin/index.php atau _admincab/index.php
Level 2 (CS)         → _cs/index.php
Level 3 (Kasir)      → _kasir/index.php
Level 4 (Mekanik)    → _mekanik/index.php
Level 5 (Pengadaan)  → _pengadaan/index.php
Level 6 (CRM)        → _crm/index.php
Level 7 (Manajemen)  → _managemen/index.php
Level 8 (Keuangan)   → _keuangan/index.php
Level 9 (HRD)        → _hrd/index.php
```

---

### 2. **New Login System** (`login_dashboard/login.php`)

**Location:** `aplikasi/aplikasi/login_dashboard/login.php`

**Features:**
- Uses `users` table (not `tbuser`)
- Prepared statements (more secure)
- Branch & employee selection
- Password validation
- Session management

**Code Structure:**
```php
// 1. Database connection
$mysqli = new mysqli('localhost', 'fitmotor_LOGIN', 'Sayalupa12', 
                     'fitmotor_maintance-beta');

// 2. Session start
session_start();

// 3. POST handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = strtolower(mysqli_real_escape_string($mysqli, $_POST['password']));
    $cabang_input = mysqli_real_escape_string($mysqli, $_POST['cabang']);
    $karyawan = strtolower(mysqli_real_escape_string($mysqli, $_POST['karyawan']));
    
    // 4. Input validation
    if (empty($karyawan) || empty($cabang_input) || empty($password)) {
        $error_message = "Silakan isi semua field!";
    } else {
        // 5. Parse cabang input
        list($kode_cabang, $nama_cabang) = explode('|', $cabang_input);
        
        // 6. Prepared statement query
        $sql_check_user = "SELECT kode_karyawan, nama_karyawan, password, role, 
                                  nama_cabang, kode_cabang FROM users 
                           WHERE kode_karyawan = ?";
        $stmt_check_user = $mysqli->prepare($sql_check_user);
        $stmt_check_user->bind_param("s", $karyawan);
        $stmt_check_user->execute();
        $stmt_check_user->bind_result($kode_karyawan_db, $nama_karyawan, 
                                      $password_db, $role, $db_nama_cabang, 
                                      $db_kode_cabang);
        
        // 7. Fetch result
        if ($stmt_check_user->fetch()) {
            // 8. Verify password
            if ($password === $password_db) {
                // 9. Set session
                $_SESSION['kode_karyawan'] = $kode_karyawan_db;
                $_SESSION['nama_karyawan'] = $nama_karyawan;
                $_SESSION['role'] = $role;
                $_SESSION['kode_cabang'] = $kode_cabang;
                $_SESSION['nama_cabang'] = $nama_cabang;
                
                // 10. Redirect
                header("Location: dashboard/index.php");
            } else {
                $error_message = "Password salah!";
            }
        } else {
            $error_message = "Karyawan tidak ditemukan!";
        }
    }
}
```

**Session Variables Set:**
```php
$_SESSION['kode_karyawan']   - Kode karyawan
$_SESSION['nama_karyawan']   - Nama karyawan
$_SESSION['role']            - Role/posisi
$_SESSION['kode_cabang']     - Kode cabang
$_SESSION['nama_cabang']     - Nama cabang
```

---

## 📄 MAIN PAGE ANALYSIS

### 1. **barang.php** (Master Barang)

**Purpose:** Display daftar item/barang dengan filter ORI/NON-ORI

**Key Features:**
- Session check & user data retrieval
- Branch data loading
- Item statistics (total, ORI, NON-ORI, pending)
- Filter by type & status
- Pagination (50 items per page)
- Add/Edit/Delete buttons
- Card mode view

**Database Queries:**
```php
// Get user data
SELECT nama_user, password, user_akses, foto_user FROM tbuser WHERE id='$id_user'

// Get branch data
SELECT nama_cabang, tipe_cabang FROM tbcabang WHERE kode_cabang='$kd_cabang'

// Count total items
SELECT count(noitem) as tot FROM view_cari_item

// Count ORI items
SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='ORI' AND statusitem='1'

// Count NON-ORI items
SELECT COUNT(*) as count FROM tblitem WHERE tipe_item='NON_ORI' AND statusitem='1'

// Count pending items
SELECT COUNT(*) as count FROM tblitem WHERE status_validasi='pending_validation' AND statusitem='1'
```

**UI Components:**
- Navbar dengan user profile & logout
- Sidebar dengan menu
- Breadcrumb navigation
- Filter buttons (ORI/NON-ORI/Status)
- Data table dengan pagination
- Add/Edit/Delete action buttons

---

### 2. **master-keluhan.php** (Master Keluhan)

**Purpose:** Manage keluhan (complaints) dengan approval workflow

**Key Features:**
- Form input untuk keluhan baru
- Approval workflow (pending → approved/rejected)
- Auto-generate kode keluhan
- Filter by kategori & status
- Pagination
- Modal untuk approve/reject
- Rejection reason tracking

**Database Queries:**
```php
// Get max kode keluhan
SELECT MAX(CAST(SUBSTRING(kode_keluhan, 4) AS UNSIGNED)) as max_no 
FROM tbmaster_keluhan WHERE kode_keluhan LIKE 'KEL%'

// Get keluhan data
SELECT * FROM tbmaster_keluhan WHERE status_aktif='1' 
ORDER BY status_approval, kategori, nama_keluhan

// Insert keluhan
INSERT INTO tbmaster_keluhan 
(kode_keluhan, nama_keluhan, deskripsi, kategori, status_approval, requested_by, requested_from) 
VALUES (...)

// Update keluhan (approval)
UPDATE tbmaster_keluhan SET status_approval='approved', approved_by='$_nama', approved_at=NOW() 
WHERE id='$id'
```

**Approval Status:**
- **pending** - Menunggu approval dari pusat
- **approved** - Sudah disetujui, bisa digunakan
- **rejected** - Ditolak dengan alasan

**Kategori Keluhan:**
- Mesin
- Rem
- Kelistrikan
- Transmisi
- Ban
- Body
- Lainnya

---

## 🔄 SESSION MANAGEMENT

### Session Variables Used Across Pages:

```php
// User identification
$_SESSION['_iduser']         - User ID (legacy system)
$_SESSION['kode_karyawan']   - Employee code (new system)
$_SESSION['nama_karyawan']   - Employee name (new system)

// Access control
$_SESSION['_cabang']         - Branch code
$_SESSION['user_akses']      - Access level (1-9)
$_SESSION['role']            - Role/position (new system)

// API integration
$_SESSION['accurate_host']   - Accurate API host
$_SESSION['accurate_status'] - API connection status
$_SESSION['access_token']    - OAuth token
$_SESSION['session']         - API session ID
```

### Session Check Pattern:
```php
<?php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
} else {
    $id_user = $_SESSION['_iduser'];
    $kd_cabang = $_SESSION['_cabang'];
    // ... rest of page
}
?>
```

---

## 🔌 AJAX HANDLERS

### Common AJAX Operations:

#### 1. **Search Operations**
```
ajax-search-barang.php              - Search items by name/code
ajax-search-keluhan-dynamic.php     - Search complaints dynamically
ajax-search-workorder-dynamic.php   - Search work orders dynamically
```

#### 2. **Data Retrieval**
```
ajax-get-customer-by-vehicle.php    - Get customer info by vehicle
ajax-get-workorder-detail.php       - Get work order details
```

#### 3. **Data Validation**
```
ajax-check-mapping-exists.php       - Check if mapping exists
```

#### 4. **Data Manipulation**
```
ajax-save-proses-tracking.php       - Save process tracking
ajax-hapus-keluhan-workorder.php    - Delete complaint from work order
```

#### 5. **Calculation**
```
ajax-hitung-tarif-jemput.php        - Calculate pickup tariff
```

---

## 📊 DATABASE TABLES USED IN _ADMINCAB

### Master Data Tables:
```
tbuser                  - User account (legacy)
tbcabang                - Branch data
tblitem                 - Item/barang master
tbmaster_keluhan        - Complaint master
tbmaster_workorder      - Work order master
```

### Transaction Tables:
```
tblservice              - Service transactions
tblkeluhan              - Complaint transactions
tblworkorder            - Work order transactions
```

### Reference Tables:
```
view_cari_item          - Item search view
tb_master_posisi        - Position master
tb_master_level         - Level master
tb_master_karyawan      - Employee master
```

---

## 🎯 WORKFLOW PATTERNS

### Master Data Management Pattern:
```
1. Display List (barang.php)
   ↓
2. Form Input (barang_add.php / barang_edit.php)
   ↓
3. Process (barang_edit_proses.php)
   ↓
4. Redirect to List
```

### Approval Workflow Pattern:
```
1. Submit Request (master-keluhan.php)
   ↓
2. Status: pending
   ↓
3. Admin Review (approve/reject)
   ↓
4. Status: approved/rejected
   ↓
5. Notification to Requester
```

### Search & Filter Pattern:
```
1. User Input Filter (form)
   ↓
2. POST/GET to same page
   ↓
3. Build WHERE clause
   ↓
4. Query database
   ↓
5. Display filtered results
```

---

## ⚠️ SECURITY ISSUES IDENTIFIED

### 1. **SQL Injection Vulnerabilities**
- Using `mysqli_real_escape_string()` instead of prepared statements
- Direct string concatenation in queries
- Example: `WHERE nama_user='$txtnama' AND password='$txtpass'`

### 2. **Password Security**
- Plain text password storage in tbuser
- No password hashing
- Passwords visible in database

### 3. **Session Security**
- No session timeout
- No CSRF token protection
- No session regeneration after login

### 4. **Access Control**
- Weak access level checking (numeric 1-9)
- No role-based permission system
- No granular permission control

### 5. **Error Handling**
- Errors displayed to users
- Debug information in logs
- No proper error logging

---

## ✅ IMPROVEMENTS NEEDED

### Priority 1 (URGENT):
1. Convert all queries to prepared statements
2. Implement password hashing
3. Add session timeout
4. Add CSRF token protection

### Priority 2 (HIGH):
5. Implement role-based access control
6. Add audit logging
7. Add input validation
8. Implement rate limiting

### Priority 3 (MEDIUM):
9. Refactor duplicate code
10. Add API documentation
11. Implement caching
12. Add unit tests

---

## 📝 FILE STATISTICS

**Total Files in _admincab:** 51+

**Breakdown:**
- Main pages: 5 (index, barang, keluhan, workorder, etc)
- Form pages: 15+ (add, edit, delete variants)
- AJAX handlers: 11
- Config/Utility: 8
- Logs/Backup: 12+

**Total Lines of Code:** ~50,000+ lines

**Languages Used:**
- PHP: 95%
- JavaScript: 3%
- SQL: 2%

---

## 🔗 INTEGRATION POINTS

### With Other Modules:
```
_admincab ↔ _admin (Admin functions)
_admincab ↔ _kasir (Cashier functions)
_admincab ↔ _mekanik (Mechanic functions)
_admincab ↔ _pengadaan (Procurement)
_admincab ↔ _managemen (Management)
```

### With External APIs:
```
_admincab ↔ Accurate API (Accounting system)
_admincab ↔ OAuth (Authentication)
```

### With Database:
```
_admincab ↔ fitmotor_dbbengkel (Main database)
_admincab ↔ fitmotor_maintance-beta (Maintenance DB)
```

---

## 📞 NEXT STEPS

1. **Security Audit** - Review all SQL queries for injection vulnerabilities
2. **Password Migration** - Implement password hashing
3. **Session Management** - Add timeout & CSRF protection
4. **Code Refactoring** - Remove duplicate code
5. **Documentation** - Create API documentation
6. **Testing** - Implement unit tests

---

**Last Updated:** 16 November 2025  
**Version:** 1.0  
**Status:** ⚠️ SECURITY ISSUES IDENTIFIED - NEEDS URGENT FIXES
