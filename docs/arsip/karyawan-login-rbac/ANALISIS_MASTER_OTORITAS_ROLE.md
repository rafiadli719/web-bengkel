# 📊 ANALISIS: MASTER OTORITAS ROLE & PENGATURAN

## 🎯 Tujuan

Menganalisa dan merancang implementasi master otoritas role untuk semua file di folder `_admincab` agar:
- ✅ Setiap user hanya bisa akses fitur sesuai role-nya
- ✅ Setiap action (view, add, edit, delete) dikontrol dengan permission
- ✅ Audit trail untuk setiap action
- ✅ Flexible dan mudah di-maintain

---

## 📋 STRUKTUR DATABASE YANG ADA

### 1. **tbuser** (User Account)
```sql
id, kode_karyawan, nama_user, nama_lengkap, password, foto_user, foto,
user_akses, status_row, is_active, email, telp, alamat, kode_posisi,
kode_level, kode_cabang, role_name, department, last_login, created_at, updated_at
```

**Kolom Penting:**
- `user_akses` (INT) - Access level 1-10
- `role_name` (VARCHAR) - Nama role
- `department` (VARCHAR) - Departemen
- `is_active` (ENUM) - Status aktif/inactive
- `kode_cabang` (VARCHAR) - Cabang yang diakses

---

### 2. **tb_user_roles** (Role & Permission)
```sql
role_id, role_code, role_name, role_description, department,
permissions (JSON), is_active, created_at, updated_at
```

**Kolom Penting:**
- `role_code` (INT) - Kode role (1-10)
- `role_name` (VARCHAR) - Nama role
- `permissions` (JSON) - Array permissions
- `is_active` (ENUM) - Status aktif/inactive

**Contoh Permissions (JSON):**
```json
{
  "barang": {
    "view": true,
    "add": true,
    "edit": true,
    "delete": false
  },
  "keluhan": {
    "view": true,
    "add": true,
    "edit": true,
    "delete": false,
    "approve": true,
    "reject": true
  },
  "workorder": {
    "view": true,
    "add": true,
    "edit": true,
    "delete": false,
    "update_status": true
  },
  "reports": {
    "view": true,
    "export": true
  },
  "users": {
    "view": false,
    "add": false,
    "edit": false,
    "delete": false
  },
  "settings": {
    "access": false
  }
}
```

---

### 3. **tb_user_activity_log** (Audit Trail)
```sql
log_id, user_id, username, action, module, description,
ip_address, user_agent, status, created_at
```

**Kolom Penting:**
- `user_id` (INT) - ID user yang melakukan action
- `action` (VARCHAR) - Aksi (view, add, edit, delete, approve, reject)
- `module` (VARCHAR) - Modul (barang, keluhan, workorder, dll)
- `description` (TEXT) - Deskripsi detail
- `ip_address` (VARCHAR) - IP address user
- `status` (VARCHAR) - Status (success, failed)

---

## 🔍 ANALISIS FILE DI FOLDER _admincab

### Kategori File:

#### 1. **Main Pages** (50 file)
```
index.php                    - Dashboard
barang_*.php                 - Manajemen barang (kategori, stok, dll)
keluhan_*.php                - Manajemen keluhan
workorder_*.php              - Manajemen work order
akun_*.php                   - Manajemen akun
laporan_*.php                - Laporan
master_*.php                 - Master data
```

#### 2. **AJAX Handlers** (30+ file)
```
ajax-*.php                   - AJAX endpoints untuk dynamic data
ajax_*.php                   - AJAX endpoints (underscore)
```

#### 3. **Utility Files**
```
lib/                         - Library files
assets/                      - CSS, JS, images
```

---

## 🎯 ROLE & PERMISSION MAPPING

### Role Levels (dari tbuser.user_akses):

| Level | Role | Department | Permissions |
|-------|------|-----------|------------|
| 1 | Administrator | Management | ALL |
| 2 | CS (Customer Service) | Front Office | view, add, edit (limited) |
| 3 | Kasir (Cashier) | Finance | view, process_payment, print |
| 4 | Mekanik (Mechanic) | Workshop | view, update_status, add_notes |
| 5 | Pengadaan (Procurement) | Purchasing | view, add, edit (barang only) |
| 6 | CRM | Marketing | view, add_customers, edit_customers |
| 7 | Manajemen (Management) | Management | view, export, analytics |
| 8 | Keuangan (Finance) | Finance | view, export, reports |
| 9 | HRD | Human Resource | view, manage_employees |
| 10 | Kepala Mekanik | Workshop | view, approve, manage_team |

---

## 📝 PERMISSION STRUCTURE

### Module: BARANG (Inventory)
```json
{
  "barang": {
    "view": true,           // Bisa lihat daftar barang
    "add": false,           // Bisa tambah barang
    "edit": false,          // Bisa edit barang
    "delete": false,        // Bisa hapus barang
    "view_stok": true,      // Bisa lihat stok
    "edit_stok": false,     // Bisa edit stok
    "export": false         // Bisa export data
  }
}
```

### Module: KELUHAN (Complaint)
```json
{
  "keluhan": {
    "view": true,           // Bisa lihat keluhan
    "add": true,            // Bisa buat keluhan
    "edit": true,           // Bisa edit keluhan
    "delete": false,        // Bisa hapus keluhan
    "approve": false,       // Bisa approve keluhan
    "reject": false,        // Bisa reject keluhan
    "export": false         // Bisa export data
  }
}
```

### Module: WORKORDER
```json
{
  "workorder": {
    "view": true,           // Bisa lihat workorder
    "add": true,            // Bisa buat workorder
    "edit": true,           // Bisa edit workorder
    "delete": false,        // Bisa hapus workorder
    "update_status": true,  // Bisa update status
    "add_notes": true,      // Bisa tambah catatan
    "export": false         // Bisa export data
  }
}
```

### Module: REPORTS
```json
{
  "reports": {
    "view": true,           // Bisa lihat laporan
    "export": false,        // Bisa export laporan
    "analytics": false      // Bisa lihat analytics
  }
}
```

### Module: USERS (User Management)
```json
{
  "users": {
    "view": false,          // Bisa lihat user
    "add": false,           // Bisa tambah user
    "edit": false,          // Bisa edit user
    "delete": false,        // Bisa hapus user
    "manage_roles": false   // Bisa manage roles
  }
}
```

### Module: SETTINGS
```json
{
  "settings": {
    "access": false,        // Bisa akses settings
    "edit_config": false    // Bisa edit konfigurasi
  }
}
```

---

## 🔐 IMPLEMENTASI STRATEGY

### STEP 1: Load Role & Permissions di Session

**File: `cek_login.php`** (sudah ada)
```php
$_SESSION['_permissions'] = json_decode($permissions, true);
$_SESSION['_role_name'] = $role_name;
$_SESSION['user_akses'] = $lvl_akses;
```

---

### STEP 2: Create Permission Check Function

**File: `config/permission_check.php`** (baru)
```php
<?php
/**
 * Permission Check Functions
 * Fungsi untuk cek permission user
 */

function hasPermission($module, $action = 'view') {
    if (empty($_SESSION['_permissions'])) {
        return false;
    }
    
    $permissions = $_SESSION['_permissions'];
    
    // Administrator (level 1) punya akses ke semua
    if ($_SESSION['user_akses'] == 1) {
        return true;
    }
    
    // Check permission
    if (isset($permissions[$module][$action])) {
        return $permissions[$module][$action] === true;
    }
    
    return false;
}

function checkPermissionOrDie($module, $action = 'view') {
    if (!hasPermission($module, $action)) {
        logActivity('DENIED', $module, "Unauthorized access attempt to $action $module");
        die("Access Denied: Anda tidak memiliki izin untuk mengakses fitur ini.");
    }
}

function logActivity($status, $module, $description) {
    global $koneksi;
    
    $user_id = $_SESSION['_iduser'] ?? 0;
    $username = $_SESSION['_username'] ?? 'Unknown';
    $action = $_POST['action'] ?? $_GET['action'] ?? 'view';
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $sql = "INSERT INTO tb_user_activity_log 
            (user_id, username, action, module, description, ip_address, user_agent, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $koneksi->prepare($sql);
    $stmt->bind_param("isssssss", $user_id, $username, $action, $module, $description, $ip_address, $user_agent, $status);
    $stmt->execute();
    $stmt->close();
}
?>
```

---

### STEP 3: Add Permission Check di Setiap File

**Template untuk setiap file di `_admincab`:**

```php
<?php
session_start();
include "../config/koneksi.php";
include "../config/permission_check.php";

// Check session
if (empty($_SESSION['_iduser'])) {
    header("location:../index.php");
    exit;
}

// Check permission untuk module ini
checkPermissionOrDie('barang', 'view');  // Untuk halaman view
// atau
checkPermissionOrDie('barang', 'add');   // Untuk halaman add
// atau
checkPermissionOrDie('barang', 'edit');  // Untuk halaman edit
// atau
checkPermissionOrDie('barang', 'delete'); // Untuk halaman delete

// ... rest of code ...
?>
```

---

### STEP 4: Conditional UI Based on Permission

**Template untuk menampilkan button/menu berdasarkan permission:**

```php
<?php if (hasPermission('barang', 'add')) { ?>
    <a href="barang_add.php" class="btn btn-primary">
        <i class="fa fa-plus"></i> Tambah Barang
    </a>
<?php } ?>

<?php if (hasPermission('barang', 'edit')) { ?>
    <a href="barang_edit.php?id=<?php echo $id; ?>" class="btn btn-warning">
        <i class="fa fa-edit"></i> Edit
    </a>
<?php } ?>

<?php if (hasPermission('barang', 'delete')) { ?>
    <a href="barang_del.php?id=<?php echo $id; ?>" class="btn btn-danger" onclick="return confirm('Yakin?')">
        <i class="fa fa-trash"></i> Hapus
    </a>
<?php } ?>
```

---

### STEP 5: AJAX Permission Check

**Template untuk AJAX handlers:**

```php
<?php
session_start();
include "../config/koneksi.php";
include "../config/permission_check.php";

// Check permission
checkPermissionOrDie('barang', 'add');

// Log activity
logActivity('SUCCESS', 'barang', 'Added new barang: ' . $_POST['nama_barang']);

// Process AJAX request
// ...
?>
```

---

## 📊 IMPLEMENTASI ROADMAP

### Phase 1: Foundation (Hari 1)
- [ ] Create `config/permission_check.php`
- [ ] Update `cek_login.php` untuk load permissions dari tb_user_roles
- [ ] Create `config/activity_logger.php`
- [ ] Test permission functions

### Phase 2: Core Modules (Hari 2-3)
- [ ] Update `barang_*.php` files
- [ ] Update `keluhan_*.php` files
- [ ] Update `workorder_*.php` files
- [ ] Update `akun_*.php` files

### Phase 3: AJAX Handlers (Hari 4)
- [ ] Update `ajax-*.php` files
- [ ] Update `ajax_*.php` files

### Phase 4: Testing & Refinement (Hari 5)
- [ ] Test dengan setiap role
- [ ] Verify audit logs
- [ ] Performance testing

---

## 🧪 TESTING CHECKLIST

### Role 1 (Administrator)
- [ ] Akses semua fitur
- [ ] Bisa view, add, edit, delete semua module

### Role 2 (CS)
- [ ] Akses barang (view only)
- [ ] Akses keluhan (view, add, edit)
- [ ] Akses workorder (view, add, edit)
- [ ] Tidak bisa akses users, settings

### Role 3 (Kasir)
- [ ] Akses barang (view only)
- [ ] Akses keluhan (view only)
- [ ] Akses workorder (view only)
- [ ] Bisa process payment
- [ ] Bisa print invoice

### Role 4 (Mekanik)
- [ ] Akses barang (view only)
- [ ] Akses keluhan (view only)
- [ ] Akses workorder (view, update_status, add_notes)
- [ ] Tidak bisa edit/delete

### Role 5 (Pengadaan)
- [ ] Akses barang (view, add, edit)
- [ ] Tidak bisa akses keluhan, workorder

### Role 6 (CRM)
- [ ] Akses customers (view, add, edit)
- [ ] Akses keluhan (view only)
- [ ] Tidak bisa akses users, settings

### Role 7 (Manajemen)
- [ ] Akses reports (view, export)
- [ ] Akses analytics
- [ ] Tidak bisa edit data

### Role 8 (Keuangan)
- [ ] Akses reports (view, export)
- [ ] Akses financial data
- [ ] Tidak bisa edit master data

### Role 9 (HRD)
- [ ] Akses employees (view, add, edit)
- [ ] Tidak bisa akses barang, keluhan, workorder

### Role 10 (Kepala Mekanik)
- [ ] Akses workorder (view, approve)
- [ ] Akses team management
- [ ] Tidak bisa akses users, settings

---

## 📈 BENEFIT IMPLEMENTASI

1. **Security** ✅
   - Setiap user hanya akses fitur yang diizinkan
   - Prevent unauthorized access
   - Audit trail untuk semua action

2. **Maintainability** ✅
   - Centralized permission management
   - Easy to add/remove permissions
   - Flexible role definition

3. **Scalability** ✅
   - Easy to add new roles
   - Easy to add new modules
   - Easy to add new permissions

4. **Compliance** ✅
   - Audit trail untuk compliance
   - Activity logging
   - User accountability

---

## 🚀 NEXT STEPS

1. **Review** struktur role & permission
2. **Create** `config/permission_check.php`
3. **Update** `cek_login.php` untuk load permissions
4. **Update** setiap file di `_admincab` dengan permission check
5. **Test** dengan setiap role
6. **Monitor** activity logs

---

**Status:** ✅ **ANALISIS SELESAI**  
**Siap untuk:** Implementasi Phase 1
