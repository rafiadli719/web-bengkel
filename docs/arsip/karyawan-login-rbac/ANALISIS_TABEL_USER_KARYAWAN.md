# 📊 ANALISIS TABEL DATABASE USER & KARYAWAN

## 📋 Ringkasan Eksekutif

Database `fitmotor_dbbengkel` memiliki **5 tabel utama** yang terkait dengan user dan karyawan:

| Tabel | Tujuan | Status |
|-------|--------|--------|
| `tbuser` | User login legacy (lama) | ✅ Aktif, ada data |
| `tb_master_karyawan` | Master data karyawan | ✅ Aktif, ada data |
| `tb_user_account` | User account modern (baru) | ✅ Aktif, ada data |
| `tb_user_roles` | Role & permission management | ✅ Aktif, ada data |
| `tb_user_activity_log` | Activity logging | ✅ Aktif, kosong |

---

## 🗂️ DETAIL SETIAP TABEL

### 1️⃣ TABEL `tbuser` (Legacy - Lama)

**Tujuan:** User login untuk aplikasi lama

**Struktur:**
```sql
CREATE TABLE `tbuser` (
  `id` int(11) PRIMARY KEY,
  `nama_user` varchar(25) NOT NULL,
  `password` varchar(25) NOT NULL,
  `foto_user` varchar(100),
  `status_row` varchar(1) DEFAULT '0',
  `user_akses` int(11) NOT NULL,
  `role_name` varchar(50),
  `department` varchar(50),
  `is_active` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp,
  `updated_at` timestamp,
  `last_login` timestamp
)
```

**Kolom Penting:**
- `id` - Primary key (1-11)
- `nama_user` - Username untuk login
- `password` - Password plain text (⚠️ TIDAK AMAN!)
- `user_akses` - Access level (1-10)
- `status_row` - Status: '0'=aktif, '1'=non-aktif
- `is_active` - Status: 'active' atau 'inactive'

**Data Sample (11 users):**
```
ID | Username | Password | User Akses | Role | Department
1  | admin | admin | 1 | Administrator | Management
2  | cs | 123 | 2 | CS & Kasir | Front Office
3  | kasir | 123 | 2 | CS & Kasir | Front Office
4  | mekanik | 123 | 4 | Mekanik | Workshop
5  | pengadaan | 123 | 5 | Pengadaan | Purchasing
6  | crm | 123 | 6 | CRM | Marketing
7  | managemen | 123 | 7 | Manajemen | Management
8  | keuangan | 123 | 8 | Keuangan | Finance
9  | hrd | 123 | 9 | HRD | Human Resource
10 | kepala_mekanik1 | 123456 | 10 | Kepala Mekanik | Workshop
11 | kepala_mekanik2 | 123456 | 10 | Kepala Mekanik | Workshop
```

**Query untuk Login:**
```sql
SELECT * FROM tbuser 
WHERE nama_user='admin' AND password='admin' 
AND status_row='0' AND is_active='active'
```

---

### 2️⃣ TABEL `tb_master_karyawan` (Master Data Karyawan)

**Tujuan:** Master data karyawan lengkap dengan detail personal

**Struktur:**
```sql
CREATE TABLE `tb_master_karyawan` (
  `id` int(11) PRIMARY KEY,
  `kode_karyawan` varchar(20) NOT NULL UNIQUE,
  `nik` varchar(20),
  `nama_lengkap` varchar(100) NOT NULL,
  `nama_panggilan` varchar(50),
  `kode_posisi` varchar(20) NOT NULL,
  `kode_level` varchar(20),
  `kode_cabang` varchar(20) NOT NULL,
  `email` varchar(100),
  `telp` varchar(20),
  `alamat` text,
  `tanggal_masuk` date,
  `tanggal_keluar` date,
  `spesialisasi` text,
  `sertifikat` text,
  `foto` varchar(255),
  `created_at` timestamp,
  `updated_at` timestamp
)
```

**Kolom Penting:**
- `kode_karyawan` - Kode unik karyawan (KRY-00001, MK001, dll)
- `nama_lengkap` - Nama lengkap karyawan
- `kode_posisi` - FK ke `tb_master_posisi` (ADM, CS, MK, KM, dll)
- `kode_level` - FK ke `tb_master_level` (ADM-1, CS-1, MK-1, dll)
- `kode_cabang` - FK ke `tbcabang` (CAB001, PST, SBY, dll)

**Data Sample (23 karyawan):**
```
ID | Kode | Nama | Posisi | Level | Cabang
1  | KRY-00001 | Administrator | ADM | ADM-1 | CAB001
2  | KRY-00002 | CS & Kasir | CS | CS-1 | CAB001
...
16 | MK001 | ADIT PRASETIO | KM | KM-1 | CAB001
17 | MK002 | AHMAD FAIZAL | KM | KM-1 | CAB001
...
```

**Relasi:**
- Terhubung ke `tb_master_posisi` via `kode_posisi`
- Terhubung ke `tb_master_level` via `kode_level`
- Terhubung ke `tbcabang` via `kode_cabang`

---

### 3️⃣ TABEL `tb_user_account` (Modern - Baru)

**Tujuan:** User account baru dengan password hashing dan security lebih baik

**Struktur:**
```sql
CREATE TABLE `tb_user_account` (
  `id` int(11) PRIMARY KEY,
  `kode_karyawan` varchar(20) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `user_akses_level` int(11) NOT NULL,
  `is_active` enum('active','inactive','locked') DEFAULT 'active',
  `last_login` timestamp,
  `must_change_password` enum('yes','no') DEFAULT 'no',
  `created_at` timestamp,
  `updated_at` timestamp
)
```

**Kolom Penting:**
- `kode_karyawan` - FK ke `tb_master_karyawan`
- `username` - Username untuk login
- `password_hash` - Password dengan hashing (lebih aman)
- `user_akses_level` - Access level (1-10)
- `is_active` - Status: 'active', 'inactive', atau 'locked'
- `must_change_password` - Force password change

**Data Sample (11 users):**
```
ID | Kode | Username | Password | Level | Status
1  | KRY-00001 | admin | admin | 1 | active
2  | KRY-00002 | cs | 123 | 2 | active
...
```

**Relasi:**
- Terhubung ke `tb_master_karyawan` via `kode_karyawan`

---

### 4️⃣ TABEL `tb_user_roles` (Role & Permission)

**Tujuan:** Mendefinisikan role dan permission untuk RBAC

**Struktur:**
```sql
CREATE TABLE `tb_user_roles` (
  `role_id` int(11) PRIMARY KEY,
  `role_code` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text,
  `department` varchar(50),
  `permissions` longtext (JSON array),
  `is_active` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp,
  `updated_at` timestamp
)
```

**Kolom Penting:**
- `role_code` - Kode role (1-10)
- `role_name` - Nama role
- `permissions` - JSON array permission

**Data Sample (9 roles):**
```
Role ID | Code | Role Name | Permissions
1 | 1 | Administrator | ["all"]
2 | 2 | CS & Kasir | ["service_read","service_create","customer_read",...]
3 | 4 | Mekanik | ["service_read","service_update_progress","task_read",...]
4 | 5 | Pengadaan | ["purchase_read","purchase_create","inventory_read"]
5 | 6 | CRM | ["customer_read","customer_update","marketing_read"]
6 | 7 | Manajemen | ["report_read","dashboard_read","analytics_read"]
7 | 8 | Keuangan | ["finance_read","finance_create","report_read"]
8 | 9 | HRD | ["employee_read","employee_create","payroll_read"]
9 | 10 | Kepala Mekanik | ["service_read","service_update","team_assign","quality_check"]
```

---

### 5️⃣ TABEL `tb_user_activity_log` (Activity Logging)

**Tujuan:** Mencatat semua aktivitas user untuk audit trail

**Struktur:**
```sql
CREATE TABLE `tb_user_activity_log` (
  `log_id` int(11) PRIMARY KEY,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50),
  `description` text,
  `ip_address` varchar(45),
  `user_agent` text,
  `created_at` timestamp
)
```

**Status:** Kosong (belum ada data logging)

---

## 🔗 RELASI ANTAR TABEL

```
tbuser (Legacy)
├── user_akses → tb_user_roles.role_code
└── department → tb_user_roles.department

tb_master_karyawan
├── kode_posisi → tb_master_posisi.kode_posisi
├── kode_level → tb_master_level.kode_level
└── kode_cabang → tbcabang.kode_cabang

tb_user_account (Modern)
├── kode_karyawan → tb_master_karyawan.kode_karyawan
└── user_akses_level → tb_user_roles.role_code

tb_user_roles
└── role_code → user_akses (di tbuser & tb_user_account)

tb_user_activity_log
└── user_id → tbuser.id atau tb_user_account.id
```

---

## 🎯 MAPPING USER AKSES LEVEL

```
Level | Role Name | Department | Permissions
1 | Administrator | Management | Full access
2 | CS & Kasir | Front Office | Service, Customer, Payment
3 | (Reserved) | | 
4 | Mekanik | Workshop | Service, Task
5 | Pengadaan | Purchasing | Purchase, Inventory
6 | CRM | Marketing | Customer, Marketing
7 | Manajemen | Management | Report, Dashboard
8 | Keuangan | Finance | Finance, Report
9 | HRD | Human Resource | Employee, Payroll
10 | Kepala Mekanik | Workshop | Service, Team, Quality
```

---

## ⚠️ MASALAH & REKOMENDASI

### Masalah 1: Duplikasi Data User
**Masalah:** Ada 2 tabel user (`tbuser` dan `tb_user_account`)
```
tbuser (Legacy) - 11 users
tb_user_account (Modern) - 11 users (sama)
```

**Rekomendasi:**
- ✅ Gunakan `tb_user_account` untuk sistem baru
- ✅ Migrate `tbuser` ke `tb_user_account`
- ✅ Hapus `tbuser` setelah migration selesai

---

### Masalah 2: Password Plain Text
**Masalah:** `tbuser.password` dan `tb_user_account.password_hash` masih plain text
```
Password: 'admin', '123', '123456' (tidak di-hash)
```

**Rekomendasi:**
- ✅ Gunakan `password_hash()` atau `bcrypt`
- ✅ Update semua password dengan hash
- ✅ Implementasi password strength validation

---

### Masalah 3: Tidak Ada Relasi Foreign Key
**Masalah:** Tidak ada FK constraint antar tabel
```
tbuser.user_akses → tb_user_roles.role_code (tidak ada FK)
tb_master_karyawan.kode_posisi → tb_master_posisi (tidak ada FK)
```

**Rekomendasi:**
- ✅ Tambahkan FK constraint
- ✅ Implementasi referential integrity
- ✅ Validasi data consistency

---

### Masalah 4: Activity Log Kosong
**Masalah:** `tb_user_activity_log` tidak digunakan
```
Status: 0 records
```

**Rekomendasi:**
- ✅ Implementasi activity logging di setiap action
- ✅ Log: user_id, action, module, description, ip_address
- ✅ Gunakan untuk audit trail & security monitoring

---

## 📊 QUERY YANG SERING DIGUNAKAN

### 1. Login User
```sql
-- Menggunakan tbuser (legacy)
SELECT * FROM tbuser 
WHERE nama_user='admin' AND password='admin' 
AND status_row='0' AND is_active='active'

-- Menggunakan tb_user_account (modern)
SELECT * FROM tb_user_account 
WHERE username='admin' AND password_hash='admin' 
AND is_active='active'
```

### 2. Get User dengan Role & Department
```sql
SELECT 
  u.id, u.nama_user, u.user_akses,
  r.role_name, r.permissions, r.department
FROM tbuser u
LEFT JOIN tb_user_roles r ON u.user_akses = r.role_code
WHERE u.status_row='0' AND u.is_active='active'
```

### 3. Get Karyawan dengan Posisi & Level
```sql
SELECT 
  k.id, k.kode_karyawan, k.nama_lengkap,
  p.nama_posisi, l.nama_level, c.nama_cabang
FROM tb_master_karyawan k
LEFT JOIN tb_master_posisi p ON k.kode_posisi = p.kode_posisi
LEFT JOIN tb_master_level l ON k.kode_level = l.kode_level
LEFT JOIN tbcabang c ON k.kode_cabang = c.kode_cabang
```

### 4. Get User Account dengan Karyawan Info
```sql
SELECT 
  ua.id, ua.username, ua.user_akses_level,
  k.kode_karyawan, k.nama_lengkap, k.email, k.telp,
  r.role_name, r.permissions
FROM tb_user_account ua
LEFT JOIN tb_master_karyawan k ON ua.kode_karyawan = k.kode_karyawan
LEFT JOIN tb_user_roles r ON ua.user_akses_level = r.role_code
WHERE ua.is_active='active'
```

### 5. Log User Activity
```sql
INSERT INTO tb_user_activity_log 
(user_id, action, module, description, ip_address, user_agent)
VALUES (1, 'LOGIN', 'auth', 'User login', '192.168.1.1', 'Mozilla/5.0...')
```

---

## 🚀 REKOMENDASI IMPLEMENTASI

### Fase 1: Immediate (Sekarang)
- ✅ Gunakan `tbuser` untuk login (sudah ada data)
- ✅ Implementasi session management
- ✅ Implementasi RBAC dengan `tb_user_roles`

### Fase 2: Short Term (1-2 minggu)
- ✅ Migrate ke `tb_user_account` (modern)
- ✅ Implementasi password hashing
- ✅ Tambahkan FK constraint

### Fase 3: Medium Term (1 bulan)
- ✅ Implementasi activity logging
- ✅ Implementasi audit trail
- ✅ Cleanup legacy `tbuser`

### Fase 4: Long Term (Ongoing)
- ✅ Implementasi 2FA (Two-Factor Authentication)
- ✅ Implementasi role-based permission checking
- ✅ Implementasi security monitoring

---

## 📝 KESIMPULAN

**Sistem User & Karyawan:**
- ✅ Struktur database sudah ada dan terorganisir
- ✅ Ada 11 user dengan 9 role berbeda
- ✅ Ada 23 karyawan dengan berbagai posisi
- ⚠️ Masih ada duplikasi data dan security issues
- 🚀 Siap untuk implementasi RBAC & session management

**Rekomendasi Prioritas:**
1. Gunakan `tb_user_account` untuk login modern
2. Implementasi password hashing
3. Implementasi activity logging
4. Cleanup legacy data

---

**Dibuat:** Nov 16, 2025  
**Database:** fitmotor_dbbengkel  
**Status:** ✅ Analisis Selesai
