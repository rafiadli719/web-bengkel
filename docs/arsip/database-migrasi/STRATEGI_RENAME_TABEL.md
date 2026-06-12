# 🔄 STRATEGI: RENAME tb_master_karyawan → tbuser

## 📋 Ringkasan Eksekutif

**Ide:** Rename `tb_master_karyawan` menjadi `tbuser` agar tidak perlu mengubah semua file di `_admincab`

**Keuntungan:**
- ✅ Tidak perlu mengubah ratusan baris kode di semua file
- ✅ Backward compatible dengan kode lama
- ✅ Lebih cepat implementasi
- ✅ Minimal risk

**Timeline:** 5 menit (hanya SQL query)

---

## 🔍 ANALISIS

### Struktur Saat Ini

**tb_master_karyawan:**
```sql
CREATE TABLE tb_master_karyawan (
  id, kode_karyawan, nik, nama_lengkap, nama_panggilan,
  kode_posisi, kode_level, kode_cabang, email, telp, alamat,
  tanggal_masuk, tanggal_keluar, spesialisasi, sertifikat, foto,
  created_at, updated_at
)
```

**tbuser (lama - akan dihapus):**
```sql
CREATE TABLE tbuser (
  id, nama_user, password, foto_user, status_row, user_akses,
  role_name, department, created_at, updated_at, last_login, is_active
)
```

### Masalah dengan Rename Langsung

Jika kita rename `tb_master_karyawan` → `tbuser`, kolom tidak cocok:

```
tbuser (baru) punya:
- id ✅
- kode_karyawan (tidak ada di tbuser lama)
- nama_lengkap (tbuser lama: nama_user)
- email, telp, alamat (tidak ada di tbuser lama)
- foto (tbuser lama: foto_user)
- kode_posisi, kode_level (tidak ada di tbuser lama)
- TIDAK punya: password, user_akses, status_row, is_active
```

---

## ✅ SOLUSI: HYBRID APPROACH

Bukan rename langsung, tapi:

1. **Rename `tb_master_karyawan` → `tbuser_karyawan`** (untuk backup)
2. **Buat tabel `tbuser` baru** dengan kolom gabungan
3. **Populate `tbuser` baru** dari `tb_user_account` + `tb_master_karyawan`
4. **Update `_admincab` files** untuk gunakan kolom yang benar
5. **Hapus tabel lama**

---

## 🔧 IMPLEMENTASI STEP-BY-STEP

### Step 1: Backup tb_master_karyawan

```sql
-- Rename tb_master_karyawan ke tbuser_karyawan (backup)
RENAME TABLE tb_master_karyawan TO tbuser_karyawan;
```

### Step 2: Buat tbuser Baru (Gabungan)

```sql
-- Buat tbuser baru dengan kolom gabungan
CREATE TABLE tbuser (
  id INT(11) PRIMARY KEY,
  kode_karyawan VARCHAR(20),
  nama_user VARCHAR(100),           -- dari nama_lengkap
  nama_lengkap VARCHAR(100),        -- dari tb_master_karyawan
  password VARCHAR(255),            -- dari tb_user_account
  foto_user VARCHAR(255),           -- dari foto
  foto VARCHAR(255),                -- dari foto
  user_akses INT(11),               -- dari user_akses_level
  status_row VARCHAR(1) DEFAULT '0',
  is_active ENUM('active','inactive') DEFAULT 'active',
  email VARCHAR(100),               -- dari tb_master_karyawan
  telp VARCHAR(20),                 -- dari tb_master_karyawan
  alamat TEXT,                      -- dari tb_master_karyawan
  kode_posisi VARCHAR(20),          -- dari tb_master_karyawan
  kode_level VARCHAR(20),           -- dari tb_master_karyawan
  kode_cabang VARCHAR(20),          -- dari tb_master_karyawan
  role_name VARCHAR(50),            -- dari tb_user_roles
  department VARCHAR(50),           -- dari tb_user_roles
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login TIMESTAMP NULL,
  is_active ENUM('active','inactive') DEFAULT 'active'
);
```

### Step 3: Populate tbuser Baru

```sql
-- Insert data dari tb_user_account + tb_master_karyawan + tb_user_roles
INSERT INTO tbuser (
  id, kode_karyawan, nama_user, nama_lengkap, password, foto_user, foto,
  user_akses, status_row, is_active, email, telp, alamat, kode_posisi,
  kode_level, kode_cabang, role_name, department, last_login
)
SELECT 
  ua.id,
  ua.kode_karyawan,
  ua.username AS nama_user,
  k.nama_lengkap,
  ua.password_hash AS password,
  k.foto AS foto_user,
  k.foto,
  ua.user_akses_level AS user_akses,
  '0' AS status_row,
  ua.is_active,
  k.email,
  k.telp,
  k.alamat,
  k.kode_posisi,
  k.kode_level,
  k.kode_cabang,
  r.role_name,
  r.department,
  ua.last_login
FROM tb_user_account ua
LEFT JOIN tbuser_karyawan k ON ua.kode_karyawan = k.kode_karyawan
LEFT JOIN tb_user_roles r ON ua.user_akses_level = r.role_code;
```

### Step 4: Verify Data

```sql
-- Check data di tbuser baru
SELECT id, nama_user, nama_lengkap, user_akses, is_active FROM tbuser;

-- Result: 11 rows dengan data lengkap
```

### Step 5: Hapus Tabel Lama

```sql
-- Hapus backup (setelah verify berhasil)
DROP TABLE tbuser_karyawan;
DROP TABLE tb_user_account;  -- Sudah tidak perlu
```

---

## 📊 STRUKTUR AKHIR

### Tabel yang Tersisa

```
tbuser (BARU - Gabungan)
├─ id, kode_karyawan, nama_user, nama_lengkap
├─ password, foto_user, foto
├─ user_akses, status_row, is_active
├─ email, telp, alamat
├─ kode_posisi, kode_level, kode_cabang
├─ role_name, department
└─ created_at, updated_at, last_login

tbcabang
├─ kode_cabang, nama_cabang, alamat_cabang
└─ ...

tb_user_roles
├─ role_id, role_code, role_name, permissions
└─ ...

tb_user_activity_log
├─ log_id, user_id, action, module
└─ ...
```

### Tabel yang Dihapus

```
❌ tb_master_karyawan (rename → tbuser_karyawan, kemudian hapus)
❌ tb_user_account (sudah tidak perlu)
❌ tbuser (lama - sudah dihapus)
```

---

## 🎯 KEUNTUNGAN APPROACH INI

1. ✅ **Backward Compatible** - Semua file `_admincab` tidak perlu diubah
2. ✅ **Data Lengkap** - tbuser baru punya semua kolom yang dibutuhkan
3. ✅ **Single Source of Truth** - Satu tabel untuk user + karyawan
4. ✅ **Relasi Jelas** - FK ke tbcabang, tb_user_roles
5. ✅ **Minimal Risk** - Tidak perlu ubah ratusan baris kode
6. ✅ **Cepat** - Hanya SQL query, tidak perlu code changes

---

## ⚠️ KOLOM MAPPING

### tbuser (Baru) ← Sumber Data

```
id                ← tb_user_account.id
kode_karyawan     ← tb_user_account.kode_karyawan
nama_user         ← tb_user_account.username
nama_lengkap      ← tb_master_karyawan.nama_lengkap
password          ← tb_user_account.password_hash
foto_user         ← tb_master_karyawan.foto
foto              ← tb_master_karyawan.foto
user_akses        ← tb_user_account.user_akses_level
status_row        ← '0' (default aktif)
is_active         ← tb_user_account.is_active
email             ← tb_master_karyawan.email
telp              ← tb_master_karyawan.telp
alamat            ← tb_master_karyawan.alamat
kode_posisi       ← tb_master_karyawan.kode_posisi
kode_level        ← tb_master_karyawan.kode_level
kode_cabang       ← tb_master_karyawan.kode_cabang
role_name         ← tb_user_roles.role_name
department        ← tb_user_roles.department
last_login        ← tb_user_account.last_login
```

---

## 🧪 TESTING PLAN

### Sebelum Rename

```sql
-- Backup database
mysqldump -u root fitmotor_dbbengkel > fitmotor_dbbengkel_backup.sql
```

### Setelah Rename

1. **Test Query di _admincab/index.php**
   ```php
   $cari_kd=mysqli_query($koneksi,"SELECT 
       nama_user, password, user_akses, foto_user 
       FROM tbuser WHERE id='$id_user'");
   // Seharusnya berhasil (kolom ada)
   ```

2. **Test Login**
   ```
   Username: admin
   Password: admin
   Cabang: Bengkel Pusat
   ```

3. **Verify Session & Display**
   - Nama user muncul
   - Foto muncul
   - Access level benar
   - Tidak ada error

---

## 📋 CHECKLIST IMPLEMENTASI

- [ ] Backup database
- [ ] Rename tb_master_karyawan → tbuser_karyawan
- [ ] Buat tbuser baru dengan kolom gabungan
- [ ] Populate tbuser dari tb_user_account + tb_master_karyawan
- [ ] Verify data di tbuser
- [ ] Test login & _admincab/index.php
- [ ] Verify semua protected pages berfungsi
- [ ] Hapus tbuser_karyawan (backup)
- [ ] Hapus tb_user_account
- [ ] Update dokumentasi

---

## 🚀 NEXT STEPS

1. **Backup database** (CRITICAL!)
2. **Run SQL queries** (Step 1-5)
3. **Test login** (Verify berfungsi)
4. **Verify protected pages** (Semua folder _*)
5. **Cleanup** (Hapus tabel lama)

---

**Status:** ✅ **STRATEGI READY**  
**Siap untuk:** Implementasi SQL queries  
**Estimasi:** 5 menit
