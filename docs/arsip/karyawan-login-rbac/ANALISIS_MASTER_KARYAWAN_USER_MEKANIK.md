# 📊 ANALISIS LENGKAP: Master Karyawan, User, Mekanik, Admin, Kepala Mekanik

**Date:** 12 November 2025  
**Analyst:** Cascade AI  
**Scope:** Database Structure & Application Pages

---

## 🗄️ STRUKTUR DATABASE

### 1. **Table: `tbuser`** (User/Karyawan)

**Primary Key:** `id` (int, auto increment)  
**Unique Key:** `nama_user` (username)

#### Columns:
| Column | Type | Description | Default |
|--------|------|-------------|---------|
| `id` | int(11) | User ID (PK) | AUTO_INCREMENT |
| `nama_user` | varchar(25) | Username untuk login | - |
| `password` | varchar(25) | Password (plain text ⚠️) | - |
| `foto_user` | varchar(100) | Path foto profil | - |
| `status_row` | varchar(1) | Status row ('0'=active, '1'=deleted) | '0' |
| `user_akses` | int(11) | Level akses (role ID) | - |
| `role_name` | varchar(50) | Nama role untuk display | NULL |
| `department` | varchar(50) | Departemen user | NULL |
| `created_at` | timestamp | Waktu dibuat | CURRENT_TIMESTAMP |
| `updated_at` | timestamp | Waktu update | CURRENT_TIMESTAMP ON UPDATE |
| `last_login` | timestamp | Waktu login terakhir | NULL |
| `is_active` | enum | 'active' atau 'inactive' | 'active' |

#### User Access Levels (user_akses):
| Level | Role Name | Department | Description |
|-------|-----------|------------|-------------|
| 1 | Administrator | Management | Full access |
| 2 | CS & Kasir | Front Office | Customer service & cashier |
| 3 | Kasir | Front Office | Cashier only |
| 4 | Mekanik | Workshop | Regular mechanic |
| 5 | Pengadaan | Purchasing | Procurement |
| 6 | CRM | Marketing | Customer relationship |
| 7 | Manajemen | Management | Management level |
| 8 | Keuangan | Finance | Finance department |
| 9 | HRD | Human Resource | HR department |
| 10 | Kepala Mekanik | Workshop | Head mechanic/supervisor |

#### Default Users:
```sql
admin / admin (Level 1 - Administrator)
cs / 123 (Level 2 - CS & Kasir)
kasir / 123 (Level 2 - CS & Kasir)
mekanik / 123 (Level 4 - Mekanik)
pengadaan / 123 (Level 5 - Pengadaan)
crm / 123 (Level 6 - CRM)
managemen / 123 (Level 7 - Manajemen)
keuangan / 123 (Level 8 - Keuangan)
hrd / 123 (Level 9 - HRD)
kepala_mekanik1 / 123456 (Level 10 - Kepala Mekanik)
kepala_mekanik2 / 123456 (Level 10 - Kepala Mekanik)
```

---

### 2. **Table: `tblmekanik`** (Data Mekanik)

**Primary Key:** `nomekanik` (varchar)

#### Columns:
| Column | Type | Description | Default |
|--------|------|-------------|---------|
| `nomekanik` | varchar(20) | Kode mekanik (PK) | - |
| `nama` | varchar(100) | Nama lengkap mekanik | - |
| `alamat` | text | Alamat lengkap | NULL |
| `telp` | varchar(20) | Nomor telepon | NULL |
| `email` | varchar(100) | Email | NULL |
| `keahlian` | enum('1','2','3') | Level keahlian | '1' |
| `status` | enum('aktif','nonaktif') | Status aktif/nonaktif | 'aktif' |
| `tanggal_masuk` | date | Tanggal masuk kerja | NULL |
| `gaji_pokok` | decimal(10,0) | Gaji pokok | NULL |
| `spesialisasi` | text | Spesialisasi keahlian | NULL |
| `sertifikat` | text | Sertifikat yang dimiliki | NULL |
| `created_at` | timestamp | Waktu dibuat | CURRENT_TIMESTAMP |
| `updated_at` | timestamp | Waktu update | CURRENT_TIMESTAMP ON UPDATE |

#### Keahlian Levels:
| Value | Description |
|-------|-------------|
| '1' | Kepala Mekanik (Head Mechanic) |
| '2' | Mekanik Senior (Senior Mechanic) |
| '3' | Mekanik Junior (Junior Mechanic) |

#### Sample Data:
```
MK001 - ADIT PRASETIO (Kepala Mekanik)
MK002 - AHMAD FAIZAL (Kepala Mekanik)
MK003 - GITO SUPARDI (Kepala Mekanik)
MK004 - MUHAMMAD ARIFIAN N (Kepala Mekanik)
MK005 - Dedi Kurniawan (Mekanik Senior)
MK006 - Eko Prasetyo (Mekanik Senior)
MK007 - Fajar Nugroho (Mekanik Senior)
MK008 - Gilang Ramadhan (Mekanik Senior)
```

---

### 3. **Table: `tb_user_mekanik_mapping`** (Relasi User-Mekanik)

**Purpose:** Menghubungkan user account dengan data mekanik

#### Columns:
| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | ID mapping (PK) |
| `user_id` | int(11) | FK ke tbuser.id |
| `mekanik_code` | varchar(20) | FK ke tblmekanik.nomekanik |
| `is_primary` | enum('yes','no') | Primary mapping atau tidak |
| `created_at` | timestamp | Waktu dibuat |

**Use Case:**
- Satu user bisa punya multiple mekanik codes (untuk kepala mekanik yang supervise beberapa mekanik)
- Satu mekanik code bisa punya multiple users (shift system)
- `is_primary='yes'` menandakan mapping utama

---

### 4. **Table: `tbl_master_kepala_mekanik`** (Master Kepala Mekanik)

**Purpose:** Master data kepala mekanik per cabang

#### Columns:
| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | ID (PK) |
| `kode_cabang` | varchar(20) | Kode cabang |
| `nama_kepala_mekanik` | varchar(100) | Nama kepala mekanik |
| `nomekanik` | varchar(20) | FK ke tblmekanik.nomekanik |
| `status` | enum('aktif','nonaktif') | Status |
| `created_at` | timestamp | Waktu dibuat |
| `updated_at` | timestamp | Waktu update |

---

### 5. **Table: `tbl_kepala_mekanik_harian`** (Kepala Mekanik Harian)

**Purpose:** Assignment kepala mekanik per hari (shift/schedule)

#### Columns:
| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | ID (PK) |
| `kode_cabang` | varchar(20) | Kode cabang |
| `tanggal_kerja` | date | Tanggal kerja |
| `kepala_mekanik_id` | int(11) | FK ke tbl_master_kepala_mekanik.id |
| `shift` | enum('pagi','siang','malam') | Shift kerja |
| `status` | enum('hadir','tidak_hadir','cuti') | Status kehadiran |
| `created_by` | int(11) | User yang input |
| `created_at` | timestamp | Waktu dibuat |
| `updated_at` | timestamp | Waktu update |

---

### 6. **Table: `tbmekanik_level`** (Level Mekanik)

**Purpose:** Master level/grade mekanik

#### Columns:
| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | ID (PK) |
| `mekanik_level` | varchar(20) | Nama level |

**Sample Data:**
```
Junior Mechanic
Senior Mechanic
Head Mechanic
Master Technician
```

---

### 7. **Table: `tb_progress_mekanik`** (Progress Kerja Mekanik)

**Purpose:** Tracking progress pekerjaan mekanik per service

#### Columns:
| Column | Type | Description |
|--------|------|-------------|
| `id` | int(11) | ID (PK) |
| `no_service` | varchar(30) | Nomor service |
| `no_antrian` | varchar(20) | Nomor antrian |
| `nomekanik` | varchar(20) | Kode mekanik |
| `status_progress` | enum | Status progress |
| `waktu_mulai` | datetime | Waktu mulai kerja |
| `waktu_selesai` | datetime | Waktu selesai |
| `catatan` | text | Catatan mekanik |
| `created_at` | timestamp | Waktu dibuat |
| `updated_at` | timestamp | Waktu update |

---

### 8. **Table: `tb_user_roles`** (Master Roles)

**Purpose:** Master data roles untuk RBAC (Role-Based Access Control)

#### Columns:
| Column | Type | Description |
|--------|------|-------------|
| `role_id` | int(11) | ID role (PK) |
| `role_code` | int(11) | Kode role (sama dengan user_akses) |
| `role_name` | varchar(50) | Nama role |
| `description` | text | Deskripsi role |
| `permissions` | text | JSON permissions |
| `created_at` | timestamp | Waktu dibuat |
| `updated_at` | timestamp | Waktu update |

---

### 9. **Table: `tb_user_activity_log`** (Activity Log)

**Purpose:** Logging aktivitas user

#### Columns:
| Column | Type | Description |
|--------|------|-------------|
| `log_id` | int(11) | ID log (PK) |
| `user_id` | int(11) | FK ke tbuser.id |
| `action` | varchar(100) | Aksi yang dilakukan |
| `description` | text | Deskripsi detail |
| `ip_address` | varchar(50) | IP address |
| `created_at` | timestamp | Waktu aksi |

---

## 📄 HALAMAN APLIKASI

### A. **User Management**

#### 1. `user_management.php`
**Purpose:** Halaman utama manajemen user  
**Access:** Admin (level 1) & Manajemen (level 7)  
**Features:**
- ✅ List semua user dengan filter
- ✅ Add new user
- ✅ Edit user data
- ✅ Delete user (soft delete)
- ✅ Change password
- ✅ Activate/deactivate user
- ✅ Assign role & department
- ✅ Link user dengan mekanik (untuk mekanik & kepala mekanik)

#### 2. `user_management_ajax.php`
**Purpose:** AJAX endpoint untuk user management  
**Functions:**
- Get user list (DataTables)
- Get user detail
- Update user status
- Reset password
- Check username availability

#### 3. `user.php` (Legacy)
**Purpose:** Halaman user lama (mungkin deprecated)  
**Status:** Masih digunakan atau sudah diganti dengan user_management.php?

#### 4. `user_add.php`
**Purpose:** Form tambah user baru  
**Features:**
- Input username, password
- Select role/level akses
- Input department
- Upload foto
- Link dengan mekanik (optional)

#### 5. `user_edit.php`
**Purpose:** Form edit user  
**Features:**
- Edit username (jika belum digunakan)
- Edit role & department
- Update foto
- Change status

#### 6. `user_edit_proses.php`
**Purpose:** Proses update user  
**Method:** POST

#### 7. `user_del.php`
**Purpose:** Soft delete user  
**Method:** GET/POST  
**Action:** Set `status_row='1'` atau `is_active='inactive'`

#### 8. `save_user.php`
**Purpose:** Save/insert user baru  
**Method:** POST

---

### B. **Mekanik Management**

#### 1. `mekanik_management.php`
**Purpose:** Halaman utama manajemen mekanik  
**Access:** Admin (1), Manajemen (7), Kepala Mekanik (10)  
**Features:**
- ✅ List semua mekanik dengan filter
- ✅ Add new mekanik
- ✅ Edit mekanik data
- ✅ Delete mekanik
- ✅ View detail mekanik
- ✅ Auto create user account untuk mekanik
- ✅ Assign keahlian level
- ✅ Input gaji, spesialisasi, sertifikat

#### 2. `mekanik_management_ajax.php`
**Purpose:** AJAX endpoint untuk mekanik management  
**Functions:**
- Get mekanik list (DataTables)
- Get mekanik detail
- Update mekanik status
- Get mekanik by keahlian
- Search mekanik

#### 3. `mekanik.php` (Legacy)
**Purpose:** Halaman mekanik lama  
**Status:** Masih digunakan atau sudah diganti?

#### 4. `mekanik_add.php`
**Purpose:** Form tambah mekanik baru  
**Features:**
- Input kode mekanik (auto generate?)
- Input nama, alamat, telp, email
- Select keahlian level
- Input tanggal masuk
- Input gaji pokok
- Input spesialisasi & sertifikat
- Option: Create user account

#### 5. `mekanik_edit.php`
**Purpose:** Form edit mekanik  
**Features:**
- Edit semua data mekanik
- Update status aktif/nonaktif

#### 6. `mekanik_edit_proses.php`
**Purpose:** Proses update mekanik  
**Method:** POST

#### 7. `mekanik_del.php`
**Purpose:** Delete mekanik  
**Method:** GET/POST  
**Action:** Set `status='nonaktif'` atau hard delete

#### 8. `save_mekanik.php`
**Purpose:** Save/insert mekanik baru  
**Method:** POST

---

### C. **Kepala Mekanik Management**

#### 1. `master_kepala_mekanik.php`
**Purpose:** Master data kepala mekanik per cabang  
**Access:** Admin (1), Manajemen (7)  
**Features:**
- List kepala mekanik per cabang
- Add kepala mekanik
- Edit kepala mekanik
- Delete kepala mekanik
- Assign mekanik sebagai kepala mekanik

#### 2. `input_kepala_mekanik_harian.php`
**Purpose:** Input schedule kepala mekanik harian  
**Access:** Admin (1), Manajemen (7), HRD (9)  
**Features:**
- Input kepala mekanik untuk hari tertentu
- Select shift (pagi/siang/malam)
- Input status kehadiran
- View calendar/schedule

#### 3. `get_kepala_mekanik_harian.php`
**Purpose:** AJAX get data kepala mekanik harian  
**Method:** GET/POST  
**Return:** JSON data kepala mekanik untuk tanggal tertentu

---

### D. **Mekanik Level Management**

#### 1. `mekanik_level.php`
**Purpose:** Master level/grade mekanik  
**Access:** Admin (1), Manajemen (7), HRD (9)  
**Features:**
- List semua level mekanik
- Add new level
- Edit level
- Delete level

#### 2. `mekanik_level_add.php`
**Purpose:** Form tambah level mekanik  

#### 3. `mekanik_level_edit.php`
**Purpose:** Form edit level mekanik  

#### 4. `mekanik_level_edit_proses.php`
**Purpose:** Proses update level  

#### 5. `mekanik_level_del.php`
**Purpose:** Delete level  

#### 6. `save_mekanik_level.php`
**Purpose:** Save level baru  

---

### E. **Menu Pages**

#### 1. `menu_user.php`
**Purpose:** Menu/sidebar untuk user management  
**Content:** Links ke halaman user-related

#### 2. `menu_mekanik01.php`
**Purpose:** Menu untuk mekanik management (version 1)  

#### 3. `menu_mekanik02.php`
**Purpose:** Menu untuk mekanik management (version 2)  

---

### F. **Template/Component Files**

#### 1. `_template/_servis_mekanik_fields.php`
**Purpose:** Form fields untuk input mekanik di halaman service  
**Usage:** Included di halaman service input

#### 2. `_template/_servis_progress_mekanik.php`
**Purpose:** Component untuk tracking progress mekanik  
**Features:**
- Display progress mekanik
- Update status progress
- Input waktu mulai/selesai
- Input catatan

---

### G. **AJAX Handlers**

#### 1. `_ajax/ajax-update-progress-mekanik.php`
**Purpose:** Update progress kerja mekanik  
**Method:** POST  
**Parameters:**
- no_service
- nomekanik
- status_progress
- catatan

#### 2. `_ajax/auto_save_mekanik.php`
**Purpose:** Auto save data mekanik (draft?)  
**Method:** POST  
**Use Case:** Save progress saat input data mekanik

---

## 🔄 RELASI ANTAR TABLE

```
tbuser (User Account)
  ├─ user_akses → tb_user_roles.role_code (Role)
  └─ id → tb_user_mekanik_mapping.user_id
           └─ mekanik_code → tblmekanik.nomekanik

tblmekanik (Mekanik Data)
  ├─ nomekanik → tb_user_mekanik_mapping.mekanik_code
  ├─ nomekanik → tbl_master_kepala_mekanik.nomekanik
  ├─ nomekanik → tb_progress_mekanik.nomekanik
  └─ keahlian → tbmekanik_level.id (optional)

tbl_master_kepala_mekanik
  ├─ kode_cabang → tbcabang.kode_cabang
  ├─ nomekanik → tblmekanik.nomekanik
  └─ id → tbl_kepala_mekanik_harian.kepala_mekanik_id

tbl_kepala_mekanik_harian
  ├─ kepala_mekanik_id → tbl_master_kepala_mekanik.id
  ├─ kode_cabang → tbcabang.kode_cabang
  └─ created_by → tbuser.id

tb_progress_mekanik
  ├─ no_service → tbservice.no_service
  ├─ nomekanik → tblmekanik.nomekanik
  └─ no_antrian → (antrian table)

tb_user_activity_log
  └─ user_id → tbuser.id
```

---

## ⚠️ ISSUES & RECOMMENDATIONS

### 🔴 CRITICAL ISSUES:

#### 1. **Password Storage**
**Problem:** Password disimpan dalam **plain text** di database  
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

#### 2. **SQL Injection**
**Problem:** Beberapa query masih vulnerable  
**Risk:** Database bisa di-hack  
**Solution:**
```php
// Gunakan prepared statements
$stmt = $koneksi->prepare("SELECT * FROM tbuser WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
```

#### 3. **No Session Security**
**Problem:** Session tidak ada timeout, tidak ada CSRF protection  
**Risk:** Session hijacking  
**Solution:**
- Implement session timeout
- Add CSRF token
- Regenerate session ID setelah login

---

### 🟡 MEDIUM ISSUES:

#### 4. **Duplicate User Management Pages**
**Problem:** Ada `user.php` dan `user_management.php`  
**Impact:** Confusing, maintenance nightmare  
**Solution:** Standardize ke satu halaman saja

#### 5. **No Audit Trail**
**Problem:** `tb_user_activity_log` ada tapi tidak digunakan konsisten  
**Impact:** Sulit tracking siapa melakukan apa  
**Solution:** Implement logging di semua CRUD operations

#### 6. **No Role-Based Permissions**
**Problem:** `tb_user_roles` ada tapi tidak digunakan untuk granular permissions  
**Impact:** Access control terlalu kasar (hanya by level)  
**Solution:** Implement proper RBAC dengan permissions matrix

---

### 🟢 MINOR ISSUES:

#### 7. **Inconsistent Naming**
**Problem:** 
- `tblmekanik` vs `tb_user_mekanik_mapping`
- `nomekanik` vs `mekanik_code`  
**Impact:** Confusing untuk developer  
**Solution:** Standardize naming convention

#### 8. **No Soft Delete Consistency**
**Problem:** 
- `tbuser` uses `status_row` and `is_active`
- `tblmekanik` uses `status`  
**Impact:** Inconsistent delete behavior  
**Solution:** Standardize soft delete mechanism

#### 9. **Missing Indexes**
**Problem:** Tidak ada index di foreign keys  
**Impact:** Query lambat untuk join  
**Solution:**
```sql
ALTER TABLE tb_user_mekanik_mapping ADD INDEX idx_user_id (user_id);
ALTER TABLE tb_user_mekanik_mapping ADD INDEX idx_mekanik_code (mekanik_code);
```

---

## ✅ GOOD PRACTICES FOUND:

1. ✅ **Timestamps:** `created_at` dan `updated_at` ada di semua table
2. ✅ **Enum Types:** Menggunakan ENUM untuk status yang terbatas
3. ✅ **Mapping Table:** `tb_user_mekanik_mapping` untuk many-to-many relationship
4. ✅ **Separation of Concerns:** User account terpisah dari data mekanik
5. ✅ **Activity Log Table:** Ada struktur untuk logging (tinggal digunakan)
6. ✅ **Role Management:** Ada struktur untuk RBAC (tinggal diimplementasi)

---

## 🎯 RECOMMENDED IMPROVEMENTS:

### Priority 1 (URGENT):
1. **Implement Password Hashing** ← CRITICAL!
2. **Add SQL Injection Protection** ← CRITICAL!
3. **Add Session Security** ← CRITICAL!

### Priority 2 (HIGH):
4. **Standardize User Management Pages**
5. **Implement Activity Logging Consistently**
6. **Add RBAC Permissions**

### Priority 3 (MEDIUM):
7. **Add Database Indexes**
8. **Standardize Naming Convention**
9. **Add Input Validation**
10. **Add Error Handling**

### Priority 4 (LOW):
11. **Add API Documentation**
12. **Add Unit Tests**
13. **Optimize Queries**
14. **Add Caching**

---

## 📝 SUMMARY

### Database Structure: ⭐⭐⭐⭐ (4/5)
- Well-structured dengan proper relationships
- Good separation of concerns
- Missing some indexes

### Security: ⭐⭐ (2/5)
- **CRITICAL:** Plain text passwords
- **CRITICAL:** SQL injection vulnerabilities
- No CSRF protection
- No session security

### Code Quality: ⭐⭐⭐ (3/5)
- Functional but needs refactoring
- Some duplicate code
- Inconsistent naming
- Missing error handling

### Features: ⭐⭐⭐⭐ (4/5)
- Comprehensive user management
- Good mekanik management
- Kepala mekanik scheduling
- Progress tracking
- Missing: Proper RBAC, audit trail

---

## 🚀 NEXT STEPS

1. **Security Audit:** Fix password storage & SQL injection ASAP
2. **Code Review:** Standardize all user/mekanik management pages
3. **Testing:** Test all CRUD operations
4. **Documentation:** Document all APIs and workflows
5. **Training:** Train users on new security features

---

**Last Updated:** 12 November 2025 11:15  
**Version:** 1.0  
**Status:** ⚠️ NEEDS SECURITY FIXES URGENTLY
