# PANDUAN IMPLEMENTASI SISTEM LOGIN & ROLE BARU

## 📋 Ringkasan Singkat

Anda memiliki 2 sistem login yang berbeda:
1. **login_dashboard** - Modern, dengan RBAC dan dynamic sidebar ✅
2. **_admincab** - Lama, menggunakan tabel tbuser dan akses control basic ❌

Tujuan: **Seragamkan kedua sistem ke master karyawan baru** dengan role-based access control.

---

## 🎯 Tujuan Akhir

- ✅ 1 sistem login terpusat
- ✅ 1 master karyawan (tb_master_karyawan)
- ✅ Role-based access control (RBAC) di semua modul
- ✅ Dynamic sidebar berdasarkan role
- ✅ Permission management per user
- ✅ Audit trail untuk semua akses

---

## 📁 File yang Sudah Dibuat

### 1. **ANALISA_SISTEM_LOGIN_DAN_AKSES_ROLE.md**
   - Analisa lengkap perbedaan kedua sistem
   - Mapping role lama ke role baru
   - Query template untuk update

### 2. **role_check.php** (Helper)
   - Lokasi: `_admincab/includes/role_check.php`
   - Fungsi untuk check role, permission, session
   - Fungsi helper untuk mapping role lama ke baru

### 3. **TEMPLATE_UPDATE_ADMINCAB_INDEX.php**
   - Template lengkap untuk update index.php
   - Contoh kode yang perlu diganti
   - Testing checklist

---

## 🚀 Langkah Implementasi

### PHASE 1: Persiapan (Sudah Selesai ✅)

- ✅ Master karyawan baru dibuat
- ✅ Data dimigrasikan dari users.sql + masterkeys.sql
- ✅ Role sudah dimapping
- ✅ Helper file dibuat

### PHASE 2: Update _admincab/index.php (NEXT)

**Waktu estimasi:** 30 menit

**Langkah:**

1. **Backup file original**
   ```bash
   cp _admincab/index.php _admincab/index.php.backup
   ```

2. **Buat folder includes jika belum ada**
   ```bash
   mkdir -p _admincab/includes
   ```

3. **Copy role_check.php ke folder includes**
   ```bash
   cp role_check.php _admincab/includes/
   ```

4. **Update index.php sesuai TEMPLATE_UPDATE_ADMINCAB_INDEX.php**
   - Ganti session check (Line 1-6)
   - Ganti query user (Line 11-21)
   - Ganti query cabang (Line 24-30)
   - Tambah role check dan logging

5. **Test login**
   - Login dengan super_admin
   - Cek apakah dashboard muncul
   - Cek error_log jika ada error

---

### PHASE 3: Update File Lain di _admincab

**Waktu estimasi:** 2-3 hari (tergantung jumlah file)

**Prioritas:**
1. File yang sering diakses (master data, laporan)
2. File yang memerlukan akses tertentu (admin only, kasir only)
3. File yang query dari tbuser atau tblmekanik

**Untuk setiap file:**

1. **Tambah role check di awal file**
   ```php
   <?php
   session_start();
   include "../config/koneksi.php";
   include "includes/role_check.php";
   
   // Check role - sesuaikan dengan kebutuhan
   checkRole(['ADM', 'MNG']);  // Hanya admin dan manager
   
   // Sekarang file ini aman dari akses unauthorized
   ?>
   ```

2. **Ganti query dari tbuser ke tb_master_karyawan**
   ```php
   // DARI:
   // SELECT * FROM tbuser WHERE id='$id_user'
   
   // MENJADI:
   // SELECT * FROM tb_master_karyawan WHERE kode_karyawan='$kode_karyawan'
   ```

3. **Ganti query dari tblmekanik ke tb_master_karyawan**
   ```php
   // DARI:
   // SELECT * FROM tblmekanik WHERE nomekanik='$nomekanik'
   
   // MENJADI:
   // SELECT * FROM tb_master_karyawan 
   // WHERE kode_karyawan='$nomekanik' 
   // AND kode_posisi IN ('MK','KM')
   ```

4. **Ganti check akses dari user_akses ke role**
   ```php
   // DARI:
   // if($lvl_akses >= 7) { ... }
   
   // MENJADI:
   // if(hasAnyRole(['ADM', 'MNG'])) { ... }
   ```

---

### PHASE 4: Testing & Validation

**Waktu estimasi:** 1 hari

**Test Scenarios:**

1. **Test Login dengan berbagai role:**
   ```
   - Super Admin (ADM) → Bisa akses semua
   - Admin/Manager (MNG) → Bisa akses master data
   - Kasir (KSR) → Hanya kasir module
   - Mekanik (MK) → Hanya mekanik module
   - Kepala Mekanik (KM) → Mekanik + supervisi
   ```

2. **Test Akses File:**
   ```
   - Unauthorized access ditolak
   - Redirect ke login jika session expired
   - Error handling bekerja
   ```

3. **Test Session:**
   ```
   - Session timeout bekerja
   - Logout bekerja
   - Multi-login tidak ada conflict
   ```

4. **Check Error Log:**
   ```
   - Tidak ada error PHP
   - Tidak ada query error
   - Tidak ada undefined variable
   ```

---

### PHASE 5: Cleanup & Documentation

**Waktu estimasi:** 1 hari

1. **Backup tabel lama**
   ```sql
   RENAME TABLE tbuser TO _backup_tbuser_20251115;
   RENAME TABLE tblmekanik TO _backup_tblmekanik_20251115;
   ```

2. **Buat VIEW kompatibilitas (optional)**
   ```sql
   CREATE VIEW tbuser AS
   SELECT ... FROM tb_user_account ...
   
   CREATE VIEW tblmekanik AS
   SELECT ... FROM tb_master_karyawan ...
   ```

3. **Update dokumentasi**
   - Catat file mana saja yang sudah diupdate
   - Catat perubahan yang dilakukan
   - Catat issue yang ditemukan dan cara mengatasinya

---

## 📊 Role Mapping Reference

| Kode | Nama | Deskripsi | Akses Level |
|------|------|-----------|-------------|
| ADM | Administrator | Full system access | 1 |
| MNG | Manager | Management level | 7 |
| CS | Customer Service | Handle customer | 2 |
| KSR | Kasir | Handle cashier | 2 |
| MK | Mekanik | Perform repair | 4 |
| KM | Kepala Mekanik | Supervise mechanics | 10 |
| PGD | Pengadaan | Handle procurement | 5 |
| CRM | CRM Staff | Customer relationship | 6 |
| KEU | Keuangan | Financial operations | 8 |
| HRD | HRD Staff | HR operations | 9 |

---

## 🔐 Session Keys Baru

```php
$_SESSION['kode_karyawan']    // Kode karyawan (string)
$_SESSION['nama_karyawan']    // Nama lengkap (string)
$_SESSION['role']             // Role (ADM, MNG, CS, KSR, MK, KM, PGD, CRM, KEU, HRD)
$_SESSION['kode_cabang']      // Kode cabang (string)
$_SESSION['nama_cabang']      // Nama cabang (string)
$_SESSION['login_time']       // Waktu login (timestamp)
$_SESSION['last_activity']    // Aktivitas terakhir (timestamp)
```

---

## 🛠️ Helper Functions

```php
// Check role
checkRole(['ADM', 'MNG']);           // Hanya admin dan manager
hasRole('ADM');                       // Check role tertentu
hasAnyRole(['ADM', 'MNG']);          // Check multiple roles

// Role shortcuts
isSuperAdmin();                       // Check if super admin
isAdmin();                            // Check if admin/manager
isMekanik();                          // Check if mekanik
isKasir();                            // Check if kasir

// Get user info
getUserInfo();                        // Get all user info
getCurrentUserCode();                 // Get kode_karyawan
getCurrentUserName();                 // Get nama_karyawan
getCurrentUserRole();                 // Get role
getCurrentBranchCode();               // Get kode_cabang
getCurrentBranchName();               // Get nama_cabang

// Session management
isSessionValid($timeout);             // Check if session valid
logoutUser();                         // Logout user

// Role mapping
mapOldRoleToNew($user_akses);        // Map 1-10 to ADM/MNG/etc
mapNewRoleToOld($role_code);         // Map ADM/MNG/etc to 1-10
```

---

## ⚠️ Hal-Hal Penting

1. **Jangan hapus tabel lama dulu**
   - Buat VIEW kompatibilitas terlebih dahulu
   - Pastikan semua file sudah update
   - Baru hapus tabel lama setelah 1-2 minggu

2. **Update bertahap per modul**
   - Jangan semuanya sekaligus
   - Test setiap modul sebelum lanjut
   - Backup database sebelum eksekusi

3. **Test di development dulu**
   - Jangan langsung di production
   - Cek error_log untuk debugging
   - Minta approval sebelum deploy

4. **Dokumentasi perubahan**
   - Catat file apa saja yang diupdate
   - Catat perubahan yang dilakukan
   - Catat issue yang ditemukan

5. **Password hashing**
   - Password di tb_user_account masih plain text
   - Perlu di-hash ulang dari aplikasi
   - Gunakan password_hash() dari PHP

---

## 📝 Checklist Implementasi

### Pre-Implementation
- [ ] Backup database
- [ ] Backup file _admincab/index.php
- [ ] Review ANALISA_SISTEM_LOGIN_DAN_AKSES_ROLE.md
- [ ] Review TEMPLATE_UPDATE_ADMINCAB_INDEX.php

### Phase 2: Update index.php
- [ ] Create folder _admincab/includes
- [ ] Copy role_check.php ke includes
- [ ] Update session check
- [ ] Update query user
- [ ] Update query cabang
- [ ] Tambah role check
- [ ] Test login dengan super_admin
- [ ] Check error_log

### Phase 3: Update File Lain
- [ ] Identifikasi file yang perlu update
- [ ] Prioritaskan file penting
- [ ] Update file satu per satu
- [ ] Test setiap file
- [ ] Document perubahan

### Phase 4: Testing
- [ ] Test login dengan berbagai role
- [ ] Test akses file
- [ ] Test session timeout
- [ ] Test logout
- [ ] Test error handling
- [ ] Check error_log

### Phase 5: Cleanup
- [ ] Backup tabel lama
- [ ] Buat VIEW kompatibilitas (optional)
- [ ] Update dokumentasi
- [ ] Deploy ke production

---

## 🆘 Troubleshooting

### Error: "Session expired"
- Check apakah login_dashboard/login.php sudah set session dengan benar
- Check apakah role_check.php sudah include di file
- Check error_log untuk detail error

### Error: "Unauthorized access"
- Check apakah role user sesuai dengan required role
- Check mapping role lama ke role baru
- Verify user role di database

### Error: "Database connection failed"
- Check koneksi database di config/koneksi.php
- Check username dan password database
- Check apakah database sudah ada

### Error: "User not found"
- Check apakah user sudah ada di tb_master_karyawan
- Check apakah kode_karyawan sesuai
- Verify data migration dari users.sql

### Error: "Undefined variable"
- Check apakah variable sudah di-declare
- Check apakah query berhasil dijalankan
- Check error_log untuk detail error

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Check error_log di server
2. Review ANALISA_SISTEM_LOGIN_DAN_AKSES_ROLE.md
3. Review TEMPLATE_UPDATE_ADMINCAB_INDEX.php
4. Hubungi developer untuk bantuan

---

## 📚 Referensi File

- **ANALISA_SISTEM_LOGIN_DAN_AKSES_ROLE.md** - Analisa lengkap
- **TEMPLATE_UPDATE_ADMINCAB_INDEX.php** - Template update
- **role_check.php** - Helper functions
- **DATABASE_REFACTORING_MASTER_KARYAWAN.sql** - Struktur tabel baru
- **INTEGRASI_USERS_MASTERKEYS_TO_MASTER_KARYAWAN.sql** - Migrasi data

---

## ✅ Kesimpulan

Dengan mengikuti panduan ini, Anda akan:
1. ✅ Menyatukan sistem login menjadi 1 sistem
2. ✅ Implementasi role-based access control
3. ✅ Meningkatkan security aplikasi
4. ✅ Memudahkan maintenance dan scaling

**Estimasi waktu total: 3-5 hari**

Selamat implementasi! 🚀
