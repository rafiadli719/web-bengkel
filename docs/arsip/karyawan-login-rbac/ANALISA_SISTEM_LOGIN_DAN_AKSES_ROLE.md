# ANALISA SISTEM LOGIN DAN AKSES ROLE

## Status Saat Ini

### 1. Sistem Login di `login_dashboard/login.php`
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\login_dashboard\login.php`

**Cara Kerja:**
- Query dari tabel `users` (dari users.sql)
- Ambil: `kode_karyawan`, `nama_karyawan`, `password`, `role`, `kode_cabang`, `nama_cabang`
- Set session: `$_SESSION['role']` = `super_admin`, `admin`, `kasir`, `user`
- Set session: `$_SESSION['kode_karyawan']`, `$_SESSION['nama_karyawan']`, `$_SESSION['kode_cabang']`, `$_SESSION['nama_cabang']`

**Database yang digunakan:** `fitmotor_maintance-beta` (dari users.sql)

---

### 2. Sistem Akses di `login_dashboard/dashboard/dashboard_user.php`
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\login_dashboard\dashboard\dashboard_user.php`

**Cara Kerja:**
```php
// Line 23-27: Define access roles
$is_super_admin = $role === 'super_admin';
$is_admin = $role === 'admin';
$is_user = $role === 'user';
$is_kasir = $role === 'kasir';

// Line 79-88: Dynamic Sidebar visibility
$query = "
    SELECT ds.id, ds.sidebar_name, ds.sidebar_url, ds.parent_id
    FROM dynamic_sidebars ds
    JOIN user_sidebar_settings uss ON ds.id = uss.sidebar_id
    WHERE uss.kode_karyawan = ? AND uss.is_visible = 1
    ORDER BY ds.parent_id ASC, ds.id ASC";
```

**Fitur:**
- ✅ Role-based access control (RBAC)
- ✅ Dynamic sidebar berdasarkan `user_sidebar_settings`
- ✅ Per-user permission management
- ✅ Session validation (redirect ke login jika tidak ada session)

---

### 3. Sistem Lama di `_admincab/index.php`
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\index.php`

**Cara Kerja:**
```php
// Line 3-4: Session check (BASIC)
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
}

// Line 11-17: Query dari tbuser (database lama)
$cari_kd=mysqli_query($koneksi,"SELECT 
    nama_user, password, user_akses, foto_user 
    FROM tbuser WHERE id='$id_user'");

$lvl_akses=$tm_cari['user_akses'];  // Hanya ambil user_akses (angka)
```

**Masalah:**
- ❌ Menggunakan `tbuser` (database lama)
- ❌ Session key: `$_SESSION['_iduser']`, `$_SESSION['_cabang']` (tidak konsisten)
- ❌ Tidak ada role-based access control
- ❌ Tidak ada dynamic sidebar
- ❌ Hanya check `user_akses` (angka), bukan role name
- ❌ Tidak ada permission management per user

---

## Perbedaan Utama

| Aspek | login_dashboard | _admincab |
|-------|-----------------|----------|
| **Database** | `fitmotor_maintance-beta` (users.sql) | `fitmotor_dbbengkel` (tbuser) |
| **Tabel User** | `users` | `tbuser` |
| **Session Key** | `kode_karyawan`, `role`, `nama_cabang` | `_iduser`, `_cabang` |
| **Role Type** | String: `super_admin`, `admin`, `kasir`, `user` | Integer: 1, 2, 3, 4, 5... |
| **Access Control** | ✅ Role-based (RBAC) | ❌ Basic level-based |
| **Sidebar** | ✅ Dynamic per user | ❌ Static |
| **Permission Mgmt** | ✅ `user_sidebar_settings` | ❌ Tidak ada |
| **Session Validation** | ✅ Comprehensive | ❌ Minimal |

---

## Yang Perlu Diperbaiki di `_admincab`

### STEP 1: Update Session Keys
**File:** `_admincab/index.php` (Line 3-6)

**Dari:**
```php
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
} else {
    $id_user=$_SESSION['_iduser'];	
    $kd_cabang=$_SESSION['_cabang'];
```

**Menjadi:**
```php
if(empty($_SESSION['kode_karyawan']) || empty($_SESSION['role'])){
    header("location:../login_dashboard/login.php");
    exit();
} else {
    $kode_karyawan=$_SESSION['kode_karyawan'];	
    $role=$_SESSION['role'];
    $kode_cabang=$_SESSION['kode_cabang'];
    $nama_cabang=$_SESSION['nama_cabang'];
```

---

### STEP 2: Update Query ke Master Karyawan Baru
**File:** `_admincab/index.php` (Line 11-18)

**Dari:**
```php
$cari_kd=mysqli_query($koneksi,"SELECT 
    nama_user, password, user_akses, foto_user 
    FROM tbuser WHERE id='$id_user'");
$tm_cari=mysqli_fetch_array($cari_kd);
$_nama=$tm_cari['nama_user'];
$lvl_akses=$tm_cari['user_akses'];
```

**Menjadi:**
```php
$cari_kd=mysqli_query($koneksi,"SELECT 
    k.nama_lengkap, k.foto, p.nama_posisi, a.user_akses_level
    FROM tb_master_karyawan k
    LEFT JOIN tb_master_posisi p ON p.kode_posisi = k.kode_posisi
    LEFT JOIN tb_user_account a ON a.kode_karyawan = k.kode_karyawan
    WHERE k.kode_karyawan='".$kode_karyawan."'");
$tm_cari=mysqli_fetch_array($cari_kd);
$_nama=$tm_cari['nama_lengkap'];
$lvl_akses=$tm_cari['user_akses_level'];
$nama_posisi=$tm_cari['nama_posisi'];
```

---

### STEP 3: Tambah Role-Based Access Control
**File:** `_admincab/index.php` (Setelah session check)

**Tambahkan:**
```php
// Define access roles based on kode_posisi
$is_super_admin = $role === 'super_admin' || $role === 'ADM';
$is_admin = $role === 'admin' || $role === 'MNG';
$is_kasir = $role === 'kasir' || $role === 'KSR';
$is_mekanik = $role === 'mekanik' || $role === 'MK';
$is_kepala_mekanik = $role === 'kepala_mekanik' || $role === 'KM';

// Redirect jika tidak punya akses
if(!$is_super_admin && !$is_admin) {
    header("location:../login_dashboard/login.php?error=unauthorized");
    exit();
}
```

---

### STEP 4: Buat File Helper untuk Role Check
**File Baru:** `_admincab/includes/role_check.php`

```php
<?php
// Role Check Helper
function checkRole($required_roles = []) {
    if (empty($_SESSION['role'])) {
        header("location:../login_dashboard/login.php");
        exit();
    }
    
    $user_role = $_SESSION['role'];
    
    if (!empty($required_roles) && !in_array($user_role, $required_roles)) {
        header("location:index.php?error=unauthorized");
        exit();
    }
    
    return true;
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function hasAnyRole($roles = []) {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles);
}

function getUserInfo() {
    return [
        'kode_karyawan' => $_SESSION['kode_karyawan'] ?? '',
        'nama_karyawan' => $_SESSION['nama_karyawan'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'kode_cabang' => $_SESSION['kode_cabang'] ?? '',
        'nama_cabang' => $_SESSION['nama_cabang'] ?? ''
    ];
}
?>
```

---

### STEP 5: Update Semua File di `_admincab` untuk Gunakan Role Check

**Contoh untuk file yang memerlukan admin/super_admin:**

```php
<?php
session_start();
include "../config/koneksi.php";
include "includes/role_check.php";

// Check role - hanya admin dan super_admin
checkRole(['ADM', 'MNG', 'admin', 'super_admin']);

// Sekarang file ini aman dari akses unauthorized
?>
```

---

## Mapping Role Lama ke Role Baru

| Role Lama (tbuser.user_akses) | Role Baru (tb_master_posisi.kode_posisi) | Deskripsi |
|------|------|-----------|
| 1 | ADM | Administrator |
| 2 | CS | Customer Service |
| 3 | KSR | Kasir |
| 4 | MK | Mekanik |
| 5 | PGD | Pengadaan |
| 6 | CRM | CRM Staff |
| 7 | MNG | Manager |
| 8 | KEU | Keuangan |
| 9 | HRD | HRD Staff |
| 10 | KM | Kepala Mekanik |

---

## Langkah Implementasi

### Phase 1: Persiapan (Sudah Selesai)
- ✅ Tabel master karyawan baru sudah dibuat
- ✅ Data sudah dimigrasikan
- ✅ Role sudah dimapping

### Phase 2: Update Sistem Login (NEXT)
1. Update `_admincab/index.php` untuk gunakan session baru
2. Buat file `role_check.php` helper
3. Update query ke tabel master karyawan baru

### Phase 3: Refactor Per Modul
1. Identifikasi file yang memerlukan akses tertentu
2. Tambahkan `checkRole()` di awal file
3. Ganti query dari `tbuser` ke `tb_user_account` + `tb_master_karyawan`

### Phase 4: Testing & Validation
1. Test login dengan berbagai role
2. Test akses file yang seharusnya restricted
3. Test redirect jika unauthorized

---

## File yang Perlu Diupdate

**Priority 1 (Critical):**
- `_admincab/index.php` - Main dashboard
- Login system - Pastikan set session dengan role baru

**Priority 2 (High):**
- Semua file di `_admincab/` yang query dari `tbuser`
- File yang check `user_akses` level

**Priority 3 (Medium):**
- Sidebar/menu generation
- Permission-based UI elements

---

## Database Mapping

**Tabel Lama:**
- `tbuser` (id, nama_user, password, user_akses, role_name, department, is_active)
- `tblmekanik` (nomekanik, nama, keahlian, ...)

**Tabel Baru:**
- `tb_master_karyawan` (kode_karyawan, nama_lengkap, kode_posisi, kode_level, ...)
- `tb_user_account` (kode_karyawan, username, password_hash, user_akses_level, is_active)
- `tb_master_posisi` (kode_posisi, nama_posisi, departemen, user_akses_level)
- `tb_master_level` (kode_level, nama_level, kode_posisi)

---

## Query Template untuk Update

### Template 1: Get User Info
```sql
SELECT 
    k.kode_karyawan,
    k.nama_lengkap,
    k.foto,
    p.kode_posisi,
    p.nama_posisi,
    l.kode_level,
    l.nama_level,
    a.user_akses_level,
    a.is_active
FROM tb_master_karyawan k
LEFT JOIN tb_master_posisi p ON p.kode_posisi = k.kode_posisi
LEFT JOIN tb_master_level l ON l.kode_level = k.kode_level
LEFT JOIN tb_user_account a ON a.kode_karyawan = k.kode_karyawan
WHERE k.kode_karyawan = ?;
```

### Template 2: Check Access
```sql
SELECT 
    a.user_akses_level,
    p.kode_posisi,
    p.nama_posisi
FROM tb_user_account a
JOIN tb_master_karyawan k ON k.kode_karyawan = a.kode_karyawan
JOIN tb_master_posisi p ON p.kode_posisi = k.kode_posisi
WHERE a.kode_karyawan = ? AND a.is_active = 'active';
```

---

## Rekomendasi

1. **Jangan hapus tabel lama dulu** - Buat VIEW kompatibilitas terlebih dahulu
2. **Update bertahap per modul** - Jangan semuanya sekaligus
3. **Test di development dulu** - Pastikan tidak ada yang rusak
4. **Backup database** - Sebelum eksekusi perubahan
5. **Dokumentasi perubahan** - Catat file apa saja yang sudah diupdate

---

## Kesimpulan

Sistem login di `login_dashboard` sudah modern dengan RBAC dan dynamic sidebar.
Sistem di `_admincab` masih menggunakan tabel lama dan akses control yang basic.

Perlu dilakukan refactor untuk:
1. Update session keys ke yang baru
2. Update query ke tabel master karyawan baru
3. Implementasi role-based access control
4. Tambah permission management per user
