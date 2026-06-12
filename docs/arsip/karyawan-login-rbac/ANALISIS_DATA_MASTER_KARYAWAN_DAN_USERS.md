# 📊 ANALISIS LENGKAP: Data Master Karyawan & Users
**Database:** `fitmotor_dbbengkel.sql`  
**Tanggal Export:** 16 November 2025  
**Analyzer:** Cascade AI  

---

## 🗂️ RINGKASAN STRUKTUR DATABASE

Sistem ini memiliki **3 layer utama** untuk manajemen karyawan dan user:

### Layer 1: Master Data Karyawan
**Table: `tb_master_karyawan`** - Data lengkap karyawan/staff

### Layer 2: Master Posisi & Level
**Table: `tb_master_posisi`** - Daftar posisi/jabatan  
**Table: `tb_master_level`** - Level/grade dalam setiap posisi

### Layer 3: User Account & Access Control
**Table: `tbuser`** (Legacy) - User account lama  
**Table: `tb_user_account`** - User account baru  
**Table: `tb_user_roles`** - Role-based access control

---

## 📋 DETAIL STRUKTUR TABEL

### 1. **tb_master_karyawan** (Master Data Karyawan)

**Primary Key:** `id` (int, auto increment)  
**Unique Key:** `kode_karyawan` (varchar 20)

#### Struktur Kolom:
```
id                  INT(11)         - ID unik karyawan
kode_karyawan       VARCHAR(20)     - Kode unik karyawan (PK)
nik                 VARCHAR(20)     - Nomor Identitas (NIK/KTP)
nama_lengkap        VARCHAR(100)    - Nama lengkap
nama_panggilan      VARCHAR(50)     - Nama panggilan
kode_posisi         VARCHAR(20)     - FK ke tb_master_posisi.kode_posisi
kode_level          VARCHAR(20)     - FK ke tb_master_level.kode_level
kode_cabang         VARCHAR(20)     - Kode cabang
email               VARCHAR(100)    - Email karyawan
telp                VARCHAR(20)     - Nomor telepon
alamat              TEXT            - Alamat lengkap
tanggal_masuk       DATE            - Tanggal mulai bekerja
tanggal_keluar      DATE            - Tanggal keluar (NULL jika masih aktif)
spesialisasi        TEXT            - Bidang keahlian
sertifikat          TEXT            - Sertifikat yang dimiliki
foto                VARCHAR(255)    - Path foto profil
created_at          TIMESTAMP       - Waktu dibuat
updated_at          TIMESTAMP       - Waktu update terakhir
```

#### Sample Data (23 records):
```
ID  | Kode      | Nama Lengkap              | Posisi | Level | Cabang
----|-----------|---------------------------|--------|-------|--------
1   | KRY-00001 | Administrator             | ADM    | ADM-1 | CAB001
2   | KRY-00002 | CS & Kasir                | CS     | CS-1  | CAB001
3   | KRY-00003 | CS & Kasir                | CS     | CS-1  | CAB001
4   | KRY-00004 | Mekanik                   | MK     | MK-1  | CAB001
5   | KRY-00005 | Pengadaan                 | PGD    | PGD-1 | CAB001
6   | KRY-00006 | CRM                       | CRM    | CRM-1 | CAB001
7   | KRY-00007 | Manajemen                 | MNG    | MNG-1 | CAB001
8   | KRY-00008 | Keuangan                  | KEU    | KEU-1 | CAB001
9   | KRY-00009 | HRD                       | HRD    | HRD-1 | CAB001
10  | KRY-00010 | Kepala Mekanik            | KM     | KM-1  | CAB001
11  | KRY-00011 | Kepala Mekanik            | KM     | KM-1  | CAB001
16  | MK001     | ADIT PRASETIO             | KM     | KM-1  | CAB001
17  | MK002     | AHMAD FAIZAL              | KM     | KM-1  | CAB001
18  | MK003     | GITO SUPARDI              | KM     | KM-1  | CAB001
19  | MK004     | MUHAMMAD ARIFIAN N        | KM     | KM-1  | CAB001
20  | MK005     | Dedi Kurniawan            | MK     | MK-2  | CAB001
21  | MK006     | Eko Prasetyo              | MK     | MK-2  | CAB001
22  | MK007     | Fajar Nugroho             | MK     | MK-2  | CAB001
23  | MK008     | Gilang Ramadhan           | MK     | MK-2  | CAB001
```

---

### 2. **tb_master_posisi** (Master Posisi/Jabatan)

**Primary Key:** `id` (int, auto increment)  
**Unique Key:** `kode_posisi` (varchar 20)

#### Struktur Kolom:
```
id                  INT(11)         - ID unik posisi
kode_posisi         VARCHAR(20)     - Kode unik posisi
nama_posisi         VARCHAR(100)    - Nama posisi
departemen          VARCHAR(50)     - Departemen
deskripsi           TEXT            - Deskripsi posisi
user_akses_level    INT(11)         - Default access level (1-99)
is_active           ENUM            - Status (active/inactive)
created_at          TIMESTAMP       - Waktu dibuat
updated_at          TIMESTAMP       - Waktu update terakhir
```

#### Sample Data (10 records):
```
ID | Kode | Nama Posisi        | Departemen      | Level | Status
---|------|-------------------|-----------------|-------|--------
1  | ADM  | Administrator     | Management      | 1     | active
2  | MNG  | Manager           | Management      | 7     | active
3  | CS   | Customer Service  | Front Office    | 2     | active
4  | KSR  | Kasir             | Front Office    | 2     | active
5  | MK   | Mekanik           | Workshop        | 4     | active
6  | KM   | Kepala Mekanik    | Workshop        | 10    | active
7  | PGD  | Pengadaan         | Purchasing      | 5     | active
8  | CRM  | CRM Staff         | Marketing       | 6     | active
9  | KEU  | Keuangan          | Finance         | 8     | active
10 | HRD  | HRD Staff         | Human Resource  | 9     | active
```

#### Mapping ke User Access Level:
```
Access Level | Posisi              | Deskripsi
-------------|---------------------|----------------------------------
1            | Administrator       | Full system access
2            | CS & Kasir          | Customer service & cashier
4            | Mekanik             | Regular mechanic
5            | Pengadaan           | Procurement staff
6            | CRM                 | Customer relationship
7            | Manajemen           | Management level
8            | Keuangan            | Finance department
9            | HRD                 | HR department
10           | Kepala Mekanik      | Head mechanic/supervisor
```

---

### 3. **tb_master_level** (Master Level/Grade)

**Primary Key:** `id` (int, auto increment)  
**Composite Key:** `kode_posisi` + `kode_level`

#### Struktur Kolom:
```
id              INT(11)         - ID unik level
kode_posisi     VARCHAR(20)     - FK ke tb_master_posisi.kode_posisi
kode_level      VARCHAR(20)     - Kode level unik per posisi
nama_level      VARCHAR(100)    - Nama level
urutan          INT(11)         - Urutan level (1=junior, 2=menengah, 3=senior)
deskripsi       TEXT            - Deskripsi level
is_active       ENUM            - Status (active/inactive)
created_at      TIMESTAMP       - Waktu dibuat
updated_at      TIMESTAMP       - Waktu update terakhir
```

#### Sample Data (15 records):
```
ID | Posisi | Kode Level | Nama Level                  | Urutan | Status
---|--------|------------|----------------------------|--------|--------
1  | MK     | MK-1       | Mekanik Pemula              | 1      | active
2  | MK     | MK-2       | Mekanik Menengah            | 2      | active
3  | MK     | MK-3       | Mekanik Mahir               | 3      | active
4  | KM     | KM-1       | Kepala Mekanik Junior       | 1      | active
5  | KM     | KM-2       | Kepala Mekanik Senior       | 2      | active
6  | CS     | CS-1       | CS Junior                   | 1      | active
7  | CS     | CS-2       | CS Senior                   | 2      | active
8  | KSR    | KSR-1      | Kasir Junior                | 1      | active
9  | KSR    | KSR-2      | Kasir Senior                | 2      | active
10 | ADM    | ADM-1      | System Administrator        | 1      | active
11 | MNG    | MNG-1      | Manager                     | 1      | active
12 | PGD    | PGD-1      | Staff Pengadaan             | 1      | active
13 | CRM    | CRM-1      | CRM Staff                   | 1      | active
14 | KEU    | KEU-1      | Staff Keuangan              | 1      | active
15 | HRD    | HRD-1      | Staff HRD                   | 1      | active
```

---

### 4. **tb_user_account** (User Account - BARU)

**Primary Key:** `id` (int, auto increment)  
**Unique Key:** `username` (varchar 50)

#### Struktur Kolom:
```
id                      INT(11)         - ID unik user
kode_karyawan           VARCHAR(20)     - FK ke tb_master_karyawan.kode_karyawan
username                VARCHAR(50)     - Username untuk login
password_hash           VARCHAR(255)    - Password hash (AMAN!)
user_akses_level        INT(11)         - Access level (1-99)
is_active               ENUM            - Status (active/inactive/locked)
last_login              TIMESTAMP       - Waktu login terakhir
must_change_password    ENUM            - Flag perlu ganti password
created_at              TIMESTAMP       - Waktu dibuat
updated_at              TIMESTAMP       - Waktu update terakhir
```

#### Sample Data (11 records):
```
ID | Kode Karyawan | Username          | Level | Status
---|---------------|-------------------|-------|--------
1  | KRY-00001     | admin             | 1     | active
2  | KRY-00002     | cs                | 2     | active
3  | KRY-00003     | kasir             | 2     | active
4  | KRY-00004     | mekanik           | 4     | active
5  | KRY-00005     | pengadaan         | 5     | active
6  | KRY-00006     | crm               | 6     | active
7  | KRY-00007     | managemen         | 7     | active
8  | KRY-00008     | keuangan          | 8     | active
9  | KRY-00009     | hrd               | 9     | active
10 | KRY-00010     | kepala_mekanik1   | 10    | active
11 | KRY-00011     | kepala_mekanik2   | 10    | active
```

**⚠️ CATATAN:** Password masih dalam plain text! Harus di-hash dengan `password_hash()` PHP.

---

### 5. **tbuser** (User Account - LEGACY)

**Primary Key:** `id` (int, auto increment)  
**Unique Key:** `nama_user` (varchar 25)

#### Struktur Kolom:
```
id              INT(11)         - ID unik user
nama_user       VARCHAR(25)     - Username untuk login
password        VARCHAR(25)     - Password (PLAIN TEXT - BERBAHAYA!)
foto_user       VARCHAR(100)    - Path foto profil
status_row      VARCHAR(1)      - Status row (0=active, 1=deleted)
user_akses      INT(11)         - Access level
role_name       VARCHAR(50)     - Nama role
department      VARCHAR(50)     - Departemen
created_at      TIMESTAMP       - Waktu dibuat
updated_at      TIMESTAMP       - Waktu update terakhir
last_login      TIMESTAMP       - Waktu login terakhir
is_active       ENUM            - Status (active/inactive)
```

#### Sample Data (12 records):
```
ID | Username          | Password | Level | Status
---|-------------------|----------|-------|--------
1  | admin             | admin    | 1     | active
2  | cs                | 123      | 2     | active
3  | kasir             | 123      | 2     | active
4  | mekanik           | 123      | 4     | active
5  | pengadaan         | 123      | 5     | active
6  | crm               | 123      | 6     | active
7  | managemen         | 123      | 7     | active
8  | keuangan          | 123      | 8     | active
9  | hrd               | 123      | 9     | active
10 | kepala_mekanik1   | 123456   | 10    | deleted
11 | kepala_mekanik2   | 123456   | 10    | deleted
```

**🔴 CRITICAL ISSUE:** Password dalam plain text! Sangat berbahaya untuk keamanan.

---

### 6. **tb_user_roles** (Role-Based Access Control)

**Primary Key:** `role_id` (int, auto increment)  
**Unique Key:** `role_code` (int)

#### Struktur Kolom:
```
role_id             INT(11)         - ID unik role
role_code           INT(11)         - Kode role (sama dengan user_akses_level)
role_name           VARCHAR(50)     - Nama role
role_description    TEXT            - Deskripsi role
department          VARCHAR(50)     - Departemen
permissions         LONGTEXT        - JSON array permissions
is_active           ENUM            - Status (active/inactive)
created_at          TIMESTAMP       - Waktu dibuat
updated_at          TIMESTAMP       - Waktu update terakhir
```

#### Sample Data (3+ records):
```
Role ID | Code | Nama              | Department    | Permissions
--------|------|-------------------|---------------|-----------------------------------
1       | 1    | Administrator     | Management    | ["all"]
2       | 2    | CS & Kasir        | Front Office  | ["service_read", "service_create", ...]
3       | 4    | Mekanik           | Workshop      | ["service_read", "service_update_progress", ...]
```

---

### 7. **tb_user_mekanik_mapping** (Relasi User-Mekanik)

**Purpose:** Menghubungkan user account dengan data mekanik

#### Struktur Kolom:
```
id              INT(11)         - ID mapping
user_id         INT(11)         - FK ke tb_user_account.id
mekanik_code    VARCHAR(20)     - FK ke tb_master_karyawan.kode_karyawan
is_primary      ENUM            - Primary mapping (yes/no)
created_at      TIMESTAMP       - Waktu dibuat
```

**Use Case:**
- Satu user bisa punya multiple mekanik codes (untuk kepala mekanik yang supervise beberapa mekanik)
- Satu mekanik code bisa punya multiple users (shift system)

---

## 🔄 RELASI ANTAR TABLE

```
tb_user_account (User Login)
  ├─ kode_karyawan → tb_master_karyawan.kode_karyawan
  ├─ user_akses_level → tb_user_roles.role_code (Role)
  └─ id → tb_user_mekanik_mapping.user_id

tb_master_karyawan (Data Karyawan)
  ├─ kode_posisi → tb_master_posisi.kode_posisi (Posisi)
  ├─ kode_level → tb_master_level.kode_level (Level)
  ├─ kode_cabang → tbcabang.kode_cabang (Cabang)
  └─ kode_karyawan → tb_user_mekanik_mapping.mekanik_code

tb_master_posisi (Master Posisi)
  ├─ kode_posisi → tb_master_level.kode_posisi (Level)
  └─ user_akses_level → tb_user_roles.role_code (Role)

tb_master_level (Master Level)
  └─ kode_posisi → tb_master_posisi.kode_posisi (Posisi)

tb_user_roles (Role-Based Access)
  └─ role_code → tb_user_account.user_akses_level (User Access)

tbuser (LEGACY - Deprecated)
  └─ user_akses → tb_user_roles.role_code (Role)
```

---

## 📄 FILE APLIKASI TERKAIT

### Master Karyawan Management
- `aplikasi/_admincab/master_karyawan.php` - Halaman utama master karyawan
- `aplikasi/_admincab/master_karyawan_add.php` - Form tambah karyawan
- `aplikasi/_admincab/master_karyawan_edit.php` - Form edit karyawan
- `aplikasi/_admincab/master_karyawan_save.php` - Proses simpan karyawan
- `aplikasi/_admincab/master_karyawan_ajax.php` - AJAX handler

### Master Posisi Management
- `aplikasi/_admincab/master-posisi.php` - Halaman master posisi (CURRENT FILE)

### Master Level Management
- `aplikasi/_admincab/master_level.php` - Halaman master level
- `aplikasi/_admincab/master_level_add.php` - Form tambah level
- `aplikasi/_admincab/master_level_edit.php` - Form edit level

### User Management (Legacy)
- `aplikasi/_admin/user.php` - Halaman user (legacy)
- `aplikasi/_admin/user_add.php` - Form tambah user
- `aplikasi/_admin/user_edit.php` - Form edit user

---

## ⚠️ ISSUES & RECOMMENDATIONS

### 🔴 CRITICAL ISSUES:

#### 1. **Dual User System (Legacy vs New)**
**Problem:** Ada 2 sistem user yang berjalan bersamaan:
- `tbuser` (LEGACY) - Password plain text
- `tb_user_account` (NEW) - Password hash

**Impact:** Confusing, maintenance nightmare, security risk  
**Solution:** Migrate semua ke `tb_user_account`, hapus `tbuser`

#### 2. **Password Security**
**Problem:** 
- `tbuser` menggunakan plain text password
- `tb_user_account` juga masih plain text (belum di-hash)

**Risk:** SANGAT BERBAHAYA! Jika database bocor, semua password terbaca  
**Solution:**
```php
// Gunakan password_hash()
$hashed = password_hash($password, PASSWORD_DEFAULT);

// Verify saat login
if(password_verify($input_password, $hashed_from_db)) {
    // Login success
}
```

#### 3. **SQL Injection Vulnerabilities**
**Problem:** File `master-posisi.php` masih menggunakan string concatenation
```php
// VULNERABLE!
$sql = "SELECT * FROM tb_master_posisi WHERE id = '$edit_id'";
```

**Solution:** Gunakan prepared statements
```php
// SAFE!
$stmt = $koneksi->prepare("SELECT * FROM tb_master_posisi WHERE id = ?");
$stmt->bind_param("i", $edit_id);
$stmt->execute();
```

---

### 🟡 MEDIUM ISSUES:

#### 4. **Inconsistent Naming Convention**
**Problem:**
- `kode_karyawan` vs `nomekanik`
- `kode_posisi` vs `nama_posisi`
- `kode_level` vs `nama_level`

**Impact:** Confusing untuk developer  
**Solution:** Standardize naming convention

#### 5. **Missing Foreign Key Constraints**
**Problem:** Relasi antar tabel tidak ada FK constraint di database
**Impact:** Data integrity tidak terjamin, orphan records bisa terjadi  
**Solution:** Tambah FK constraints:
```sql
ALTER TABLE tb_master_karyawan 
ADD CONSTRAINT fk_karyawan_posisi 
FOREIGN KEY (kode_posisi) REFERENCES tb_master_posisi(kode_posisi);

ALTER TABLE tb_master_karyawan 
ADD CONSTRAINT fk_karyawan_level 
FOREIGN KEY (kode_level) REFERENCES tb_master_level(kode_level);

ALTER TABLE tb_user_account 
ADD CONSTRAINT fk_user_karyawan 
FOREIGN KEY (kode_karyawan) REFERENCES tb_master_karyawan(kode_karyawan);
```

#### 6. **Missing Database Indexes**
**Problem:** Tidak ada index di foreign keys
**Impact:** Query lambat untuk join  
**Solution:**
```sql
ALTER TABLE tb_master_karyawan ADD INDEX idx_kode_posisi (kode_posisi);
ALTER TABLE tb_master_karyawan ADD INDEX idx_kode_level (kode_level);
ALTER TABLE tb_master_karyawan ADD INDEX idx_kode_cabang (kode_cabang);
ALTER TABLE tb_user_account ADD INDEX idx_kode_karyawan (kode_karyawan);
ALTER TABLE tb_user_account ADD INDEX idx_user_akses_level (user_akses_level);
ALTER TABLE tb_master_level ADD INDEX idx_kode_posisi (kode_posisi);
```

#### 7. **No Audit Trail**
**Problem:** `tb_user_activity_log` ada tapi tidak digunakan konsisten
**Impact:** Sulit tracking siapa melakukan apa  
**Solution:** Implement logging di semua CRUD operations

---

### 🟢 MINOR ISSUES:

#### 8. **Inconsistent Soft Delete**
**Problem:**
- `tbuser` uses `status_row` and `is_active`
- `tb_master_karyawan` tidak ada soft delete column
- `tb_master_posisi` uses `is_active`

**Solution:** Standardize soft delete mechanism dengan `is_active` enum

#### 9. **No Input Validation**
**Problem:** File `master-posisi.php` hanya sanitasi, tidak ada validation
**Solution:** Tambah validation untuk:
- Kode posisi format (uppercase, alphanumeric)
- Nama posisi tidak boleh kosong
- Level akses range 1-99
- Departemen dari predefined list

#### 10. **No Error Handling**
**Problem:** Error handling masih basic (alert + redirect)
**Solution:** Implement proper error logging dan user-friendly error messages

---

## ✅ GOOD PRACTICES FOUND:

1. ✅ **Timestamps:** `created_at` dan `updated_at` ada di semua table
2. ✅ **Enum Types:** Menggunakan ENUM untuk status yang terbatas
3. ✅ **Separation of Concerns:** User account terpisah dari data karyawan
4. ✅ **Master Data Structure:** Posisi dan Level terpisah (normalisasi baik)
5. ✅ **Role-Based Access:** `tb_user_roles` dengan JSON permissions
6. ✅ **Activity Log Table:** Ada struktur untuk logging

---

## 🎯 RECOMMENDED IMPROVEMENTS

### Priority 1 (URGENT - Security):
1. **Implement Password Hashing** - Use `password_hash()` PHP
2. **Add SQL Injection Protection** - Use prepared statements
3. **Add Foreign Key Constraints** - Ensure data integrity
4. **Migrate from tbuser** - Consolidate to `tb_user_account`

### Priority 2 (HIGH - Functionality):
5. **Add Database Indexes** - Improve query performance
6. **Implement Activity Logging** - Track all CRUD operations
7. **Add Input Validation** - Validate all user inputs
8. **Add Error Handling** - Proper error messages & logging

### Priority 3 (MEDIUM - Code Quality):
9. **Standardize Naming Convention** - Consistent naming across codebase
10. **Refactor Duplicate Code** - Remove code duplication
11. **Add API Documentation** - Document all endpoints
12. **Add Unit Tests** - Test critical functions

### Priority 4 (LOW - Optimization):
13. **Optimize Queries** - Add query optimization
14. **Add Caching** - Implement caching for frequently accessed data
15. **Add Pagination** - For large datasets
16. **Add Search/Filter** - Improve data discovery

---

## 📊 DATA SUMMARY

### Master Karyawan:
- **Total Records:** 23
- **Posisi:** 10 (ADM, MNG, CS, KSR, MK, KM, PGD, CRM, KEU, HRD)
- **Level:** 15 (3 untuk MK, 2 untuk KM, 2 untuk CS, 2 untuk KSR, 1 untuk lainnya)
- **Cabang:** 1 (CAB001)

### User Account:
- **Total Records:** 11 (di `tb_user_account`)
- **Total Records:** 12 (di `tbuser` - LEGACY)
- **Active Users:** 11
- **Inactive Users:** 1

### Access Levels:
```
Level 1  = Administrator (1 user)
Level 2  = CS & Kasir (2 users)
Level 4  = Mekanik (1 user)
Level 5  = Pengadaan (1 user)
Level 6  = CRM (1 user)
Level 7  = Manajemen (1 user)
Level 8  = Keuangan (1 user)
Level 9  = HRD (1 user)
Level 10 = Kepala Mekanik (2 users)
```

---

## 🔐 SECURITY CHECKLIST

- [ ] Hash all passwords in `tb_user_account`
- [ ] Remove plain text passwords from `tbuser`
- [ ] Add prepared statements to all queries
- [ ] Add CSRF token protection
- [ ] Implement session timeout
- [ ] Add rate limiting for login
- [ ] Implement 2FA (optional)
- [ ] Add audit logging
- [ ] Add IP whitelisting (optional)
- [ ] Regular security audits

---

## 📝 NEXT STEPS

1. **Immediate:** Fix password security (Priority 1)
2. **Short-term:** Add SQL injection protection & FK constraints
3. **Medium-term:** Migrate from `tbuser` to `tb_user_account`
4. **Long-term:** Refactor code & add comprehensive testing

---

**Last Updated:** 16 November 2025  
**Version:** 1.0  
**Status:** ⚠️ NEEDS SECURITY FIXES URGENTLY
