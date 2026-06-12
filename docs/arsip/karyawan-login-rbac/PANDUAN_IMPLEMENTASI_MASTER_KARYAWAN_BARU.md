# 📘 PANDUAN IMPLEMENTASI: Unified Master Karyawan System

**Date:** 12 November 2025  
**Status:** 🎨 READY TO IMPLEMENT

---

## 🎯 KONSEP SISTEM BARU

### Struktur Baru:
```
tb_master_posisi (Master Jabatan)
  └─ tb_master_level (Level/Grade per Posisi)
       └─ tb_master_karyawan (Data Karyawan UNIFIED)
            ├─ tb_user_account (Login Account)
            ├─ tb_kepala_mekanik_schedule (Schedule KM)
            └─ tb_mekanik_progress (Progress Kerja)
```

### Keuntungan:
✅ **Satu sumber data** untuk semua karyawan  
✅ **Flexible position system** - mudah tambah posisi baru  
✅ **Level/grade system** - jelas career path  
✅ **Separation of concerns** - data karyawan ≠ login account  
✅ **Better security** - password hashing, login tracking  

---

## 📊 STRUKTUR TABLE BARU

### 1. `tb_master_posisi` - Master Jabatan
| Field | Type | Description |
|-------|------|-------------|
| kode_posisi | varchar(20) | ADM, MK, KM, CS, dll |
| nama_posisi | varchar(100) | Administrator, Mekanik, dll |
| departemen | varchar(50) | Workshop, Front Office, dll |
| user_akses_level | int | Default access level (1-10) |

**Sample Data:**
- ADM = Administrator (Level 1)
- MK = Mekanik (Level 4)
- KM = Kepala Mekanik (Level 10)
- CS = Customer Service (Level 2)
- KSR = Kasir (Level 2)

### 2. `tb_master_level` - Level/Grade
| Field | Type | Description |
|-------|------|-------------|
| kode_level | varchar(20) | MK-1, MK-2, MK-3, dll |
| kode_posisi | varchar(20) | FK ke tb_master_posisi |
| nama_level | varchar(100) | Mekanik Pemula, Menengah, Mahir |
| urutan | int | 1, 2, 3, ... (career progression) |
| gaji_min/max | decimal | Range gaji untuk level |

**Sample Data Mekanik:**
- MK-1 = Mekanik Pemula (Rp 3-4 juta)
- MK-2 = Mekanik Menengah (Rp 4-6 juta)
- MK-3 = Mekanik Mahir (Rp 6-8 juta)
- MK-4 = Master Technician (Rp 8-12 juta)

**Sample Data Kepala Mekanik:**
- KM-1 = Kepala Mekanik Junior (Rp 7-9 juta)
- KM-2 = Kepala Mekanik Senior (Rp 9-12 juta)

### 3. `tb_master_karyawan` - Data Karyawan UNIFIED
| Field | Type | Description |
|-------|------|-------------|
| kode_karyawan | varchar(20) | KRY-001, MK001, dll |
| nik | varchar(20) | NIK KTP |
| nama_lengkap | varchar(100) | Nama lengkap |
| kode_posisi | varchar(20) | FK ke tb_master_posisi |
| kode_level | varchar(20) | FK ke tb_master_level |
| kode_cabang | varchar(20) | FK ke tbcabang |
| email, telp, alamat | - | Kontak |
| tanggal_masuk | date | Tanggal mulai kerja |
| status_aktif | enum | aktif, nonaktif, cuti, resign |
| gaji_pokok | decimal | Gaji pokok |
| spesialisasi | text | Khusus mekanik |
| sertifikat | text | Khusus mekanik |

**Ini menggabungkan:**
- ❌ `tbuser` (data user)
- ❌ `tblmekanik` (data mekanik)
- ✅ Jadi 1 table `tb_master_karyawan`

### 4. `tb_user_account` - Login Account
| Field | Type | Description |
|-------|------|-------------|
| kode_karyawan | varchar(20) | FK ke tb_master_karyawan |
| username | varchar(50) | Username login |
| password_hash | varchar(255) | Hashed password (bcrypt) |
| user_akses_level | int | 1-10 |
| is_active | enum | active, inactive, locked |
| last_login | timestamp | Last login time |

**Notes:**
- Tidak semua karyawan punya user account
- Password di-hash dengan `password_hash()` PHP
- Track login history untuk security

---

## 🔄 MIGRATION PLAN

### Step 1: Backup Database
```bash
# Backup dulu sebelum migration!
mysqldump -u root -p fitmotor_dbbengkel > backup_before_migration.sql
```

### Step 2: Run SQL Script
```bash
# Import SQL script yang sudah dibuat
mysql -u root -p fitmotor_dbbengkel < DATABASE_REFACTORING_MASTER_KARYAWAN.sql
```

Script akan:
1. ✅ Create 6 table baru
2. ✅ Insert master data posisi & level
3. ⏸️ Migration data (commented, perlu adjust)

### Step 3: Migrate Data Manual

#### A. Migrate Mekanik → Karyawan
```sql
INSERT INTO tb_master_karyawan 
  (kode_karyawan, nama_lengkap, kode_posisi, kode_level, kode_cabang, 
   email, telp, alamat, tanggal_masuk, status_aktif, gaji_pokok, 
   spesialisasi, sertifikat)
SELECT 
  nomekanik as kode_karyawan,
  nama as nama_lengkap,
  CASE keahlian WHEN '1' THEN 'KM' ELSE 'MK' END as kode_posisi,
  CASE keahlian 
    WHEN '1' THEN 'KM-1'  -- Kepala Mekanik → KM-1
    WHEN '2' THEN 'MK-2'  -- Mekanik Senior → MK-2
    WHEN '3' THEN 'MK-1'  -- Mekanik Junior → MK-1
  END as kode_level,
  'CAB001' as kode_cabang,  -- ADJUST SESUAI CABANG!
  email,
  telp,
  alamat,
  tanggal_masuk,
  status as status_aktif,
  gaji_pokok,
  spesialisasi,
  sertifikat
FROM tblmekanik
WHERE status = 'aktif';
```

#### B. Create Karyawan untuk User Non-Mekanik
```sql
-- Untuk user yang bukan mekanik, create karyawan baru
INSERT INTO tb_master_karyawan 
  (kode_karyawan, nama_lengkap, kode_posisi, kode_level, kode_cabang, status_aktif)
SELECT 
  CONCAT('KRY-', LPAD(id, 4, '0')) as kode_karyawan,
  nama_user as nama_lengkap,
  CASE user_akses
    WHEN 1 THEN 'ADM'
    WHEN 2 THEN 'CS'
    WHEN 5 THEN 'PGD'
    WHEN 6 THEN 'CRM'
    WHEN 7 THEN 'MNG'
    WHEN 8 THEN 'KEU'
    WHEN 9 THEN 'HRD'
    ELSE 'CS'
  END as kode_posisi,
  CASE user_akses
    WHEN 1 THEN 'ADM-1'
    WHEN 2 THEN 'CS-1'
    WHEN 5 THEN 'PGD-1'
    WHEN 6 THEN 'CRM-1'
    WHEN 7 THEN 'MNG-1'
    WHEN 8 THEN 'KEU-1'
    WHEN 9 THEN 'HRD-1'
    ELSE 'CS-1'
  END as kode_level,
  'CAB001' as kode_cabang,  -- ADJUST!
  'aktif' as status_aktif
FROM tbuser
WHERE user_akses NOT IN (4, 10)  -- Exclude mekanik & kepala mekanik
  AND is_active = 'active';
```

#### C. Migrate User Accounts
```sql
-- Migrate user accounts dengan password hashing
INSERT INTO tb_user_account 
  (kode_karyawan, username, password_hash, user_akses_level, is_active, must_change_password)
SELECT 
  k.kode_karyawan,
  u.nama_user as username,
  -- IMPORTANT: Hash password properly!
  MD5(u.password) as password_hash,  -- TEMPORARY! Ganti dengan password_hash()
  u.user_akses as user_akses_level,
  u.is_active,
  'yes' as must_change_password  -- Force password change
FROM tbuser u
LEFT JOIN tb_master_karyawan k ON (
  (u.user_akses IN (4, 10) AND k.nama_lengkap = u.nama_user) OR
  (u.user_akses NOT IN (4, 10) AND k.kode_karyawan = CONCAT('KRY-', LPAD(u.id, 4, '0')))
)
WHERE k.kode_karyawan IS NOT NULL;
```

#### D. Migrate Progress Mekanik
```sql
INSERT INTO tb_mekanik_progress 
  (no_service, kode_karyawan, status_progress, waktu_mulai, waktu_selesai, catatan)
SELECT 
  no_service,
  nomekanik as kode_karyawan,
  status_progress,
  waktu_mulai,
  waktu_selesai,
  catatan
FROM tb_progress_mekanik;
```

### Step 4: Verify Migration
```sql
-- Check jumlah data
SELECT 'Karyawan' as tabel, COUNT(*) as jumlah FROM tb_master_karyawan
UNION ALL
SELECT 'User Account', COUNT(*) FROM tb_user_account
UNION ALL
SELECT 'Progress', COUNT(*) FROM tb_mekanik_progress;

-- Check mapping posisi
SELECT kode_posisi, COUNT(*) as jumlah 
FROM tb_master_karyawan 
GROUP BY kode_posisi;

-- Check karyawan tanpa user account
SELECT kode_karyawan, nama_lengkap, kode_posisi
FROM tb_master_karyawan k
LEFT JOIN tb_user_account u ON k.kode_karyawan = u.kode_karyawan
WHERE u.id IS NULL;
```

### Step 5: Backup Old Tables
```sql
-- Setelah yakin migration berhasil
RENAME TABLE tbuser TO _backup_tbuser;
RENAME TABLE tblmekanik TO _backup_tblmekanik;
RENAME TABLE tb_progress_mekanik TO _backup_tb_progress_mekanik;
RENAME TABLE tbl_master_kepala_mekanik TO _backup_tbl_master_kepala_mekanik;
RENAME TABLE tbl_kepala_mekanik_harian TO _backup_tbl_kepala_mekanik_harian;
```

---

## 💻 UPDATE APPLICATION CODE

### Files yang Perlu Diupdate:

#### 1. Login System
**File:** `index.php`, `login.php`

**OLD:**
```php
$query = "SELECT * FROM tbuser WHERE nama_user='$username' AND password='$password'";
```

**NEW:**
```php
// Get user account
$stmt = $koneksi->prepare("SELECT ua.*, k.nama_lengkap, k.kode_posisi, k.foto 
                           FROM tb_user_account ua
                           JOIN tb_master_karyawan k ON ua.kode_karyawan = k.kode_karyawan
                           WHERE ua.username = ? AND ua.is_active = 'active'");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Verify password
if($user && password_verify($password, $user['password_hash'])) {
    // Login success
    $_SESSION['_iduser'] = $user['id'];
    $_SESSION['_kode_karyawan'] = $user['kode_karyawan'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['_nama'] = $user['nama_lengkap'];
    $_SESSION['_akses'] = $user['user_akses_level'];
    
    // Update last login
    $stmt = $koneksi->prepare("UPDATE tb_user_account SET last_login = NOW() WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
}
```

#### 2. User Management Pages
**Files:** `user_management.php`, `user_add.php`, `user_edit.php`

**Changes:**
- Query dari `tbuser` → `tb_user_account` + `tb_master_karyawan`
- Form tambah user: pilih dari existing karyawan atau create new
- Password hashing dengan `password_hash()`

#### 3. Mekanik Management Pages
**Files:** `mekanik_management.php`, `mekanik_add.php`, `mekanik_edit.php`

**Changes:**
- Query dari `tblmekanik` → `tb_master_karyawan` WHERE `kode_posisi IN ('MK', 'KM')`
- Form: pilih posisi (MK/KM) dan level (MK-1, MK-2, dll)
- Option: create user account otomatis

#### 4. Service Input Pages
**Files:** `servis-input-reguler.php`, dll

**Changes:**
- Dropdown mekanik: query dari `tb_master_karyawan` WHERE `kode_posisi = 'MK'`
- Progress tracking: update `tb_mekanik_progress`

#### 5. Kepala Mekanik Schedule
**Files:** `input_kepala_mekanik_harian.php`

**Changes:**
- Query dari `tbl_kepala_mekanik_harian` → `tb_kepala_mekanik_schedule`
- Dropdown KM: query dari `tb_master_karyawan` WHERE `kode_posisi = 'KM'`

---

## 🔐 SECURITY IMPROVEMENTS

### 1. Password Hashing
```php
// Saat create/update password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Saat login
if(password_verify($input_password, $password_hash_from_db)) {
    // Login success
}
```

### 2. Prepared Statements
```php
// OLD (VULNERABLE!)
$query = "SELECT * FROM tbuser WHERE id='$id'";

// NEW (SAFE!)
$stmt = $koneksi->prepare("SELECT * FROM tb_user_account WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

### 3. Session Security
```php
// Set session timeout
ini_set('session.gc_maxlifetime', 3600); // 1 hour

// Regenerate session ID after login
session_regenerate_id(true);

// Check session timeout
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_destroy();
    header("location: index.php");
}
$_SESSION['last_activity'] = time();
```

---

## 📝 SAMPLE QUERIES

### Get All Karyawan dengan Posisi & Level
```sql
SELECT 
  k.kode_karyawan,
  k.nama_lengkap,
  p.nama_posisi,
  l.nama_level,
  k.status_aktif,
  k.gaji_pokok
FROM tb_master_karyawan k
JOIN tb_master_posisi p ON k.kode_posisi = p.kode_posisi
LEFT JOIN tb_master_level l ON k.kode_level = l.kode_level
WHERE k.status_aktif = 'aktif'
ORDER BY p.nama_posisi, l.urutan;
```

### Get Mekanik dengan Level
```sql
SELECT 
  k.kode_karyawan,
  k.nama_lengkap,
  l.nama_level,
  k.spesialisasi,
  k.gaji_pokok
FROM tb_master_karyawan k
JOIN tb_master_level l ON k.kode_level = l.kode_level
WHERE k.kode_posisi = 'MK'
  AND k.status_aktif = 'aktif'
ORDER BY l.urutan DESC;
```

### Get User dengan Karyawan Info
```sql
SELECT 
  u.username,
  k.nama_lengkap,
  p.nama_posisi,
  l.nama_level,
  u.last_login,
  u.is_active
FROM tb_user_account u
JOIN tb_master_karyawan k ON u.kode_karyawan = k.kode_karyawan
JOIN tb_master_posisi p ON k.kode_posisi = p.kode_posisi
LEFT JOIN tb_master_level l ON k.kode_level = l.kode_level
WHERE u.is_active = 'active';
```

### Get Kepala Mekanik Schedule
```sql
SELECT 
  s.tanggal_kerja,
  s.shift,
  k.nama_lengkap,
  l.nama_level,
  s.status_kehadiran
FROM tb_kepala_mekanik_schedule s
JOIN tb_master_karyawan k ON s.kode_karyawan = k.kode_karyawan
JOIN tb_master_level l ON k.kode_level = l.kode_level
WHERE s.tanggal_kerja >= CURDATE()
ORDER BY s.tanggal_kerja, s.shift;
```

---

## ✅ CHECKLIST IMPLEMENTASI

### Database:
- [ ] Backup database existing
- [ ] Run SQL script create tables
- [ ] Migrate data mekanik
- [ ] Migrate data user non-mekanik
- [ ] Migrate user accounts
- [ ] Migrate progress mekanik
- [ ] Verify migration results
- [ ] Backup old tables

### Application Code:
- [ ] Update login system
- [ ] Update user management
- [ ] Update mekanik management
- [ ] Update service input pages
- [ ] Update kepala mekanik schedule
- [ ] Update reports
- [ ] Implement password hashing
- [ ] Implement prepared statements
- [ ] Add session security

### Testing:
- [ ] Test login dengan user lama
- [ ] Test create new karyawan
- [ ] Test create new user account
- [ ] Test assign mekanik to service
- [ ] Test kepala mekanik schedule
- [ ] Test progress tracking
- [ ] Test reports
- [ ] Test security (SQL injection, XSS)

### Documentation:
- [ ] Update user manual
- [ ] Update API documentation
- [ ] Train users on new system

---

## 🚀 ROLLBACK PLAN

Jika ada masalah setelah migration:

```sql
-- Drop new tables
DROP TABLE IF EXISTS tb_mekanik_progress;
DROP TABLE IF EXISTS tb_kepala_mekanik_schedule;
DROP TABLE IF EXISTS tb_user_account;
DROP TABLE IF EXISTS tb_master_karyawan;
DROP TABLE IF EXISTS tb_master_level;
DROP TABLE IF EXISTS tb_master_posisi;

-- Restore old tables
RENAME TABLE _backup_tbuser TO tbuser;
RENAME TABLE _backup_tblmekanik TO tblmekanik;
RENAME TABLE _backup_tb_progress_mekanik TO tb_progress_mekanik;
```

---

**READY TO IMPLEMENT?** 🚀

Silakan review dulu, kalau sudah OK baru jalankan migration!
