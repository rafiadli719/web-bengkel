# 📋 PANDUAN IMPLEMENTASI: RENAME TABEL → tbuser

## 🎯 Tujuan

Rename `tb_master_karyawan` → `tbuser` (gabungan) agar:
- ✅ Tidak perlu mengubah file di `_admincab`
- ✅ Backward compatible dengan kode lama
- ✅ Minimal risk & cepat implementasi

---

## 📋 STEP-BY-STEP IMPLEMENTASI

### STEP 1: BACKUP DATABASE (CRITICAL!)

**Penting:** Backup database sebelum melakukan perubahan!

#### Opsi A: Backup via Command Line
```bash
# Windows Command Prompt
mysqldump -u root fitmotor_dbbengkel > C:\backup\fitmotor_dbbengkel_backup.sql

# Verify backup
dir C:\backup\fitmotor_dbbengkel_backup.sql
```

#### Opsi B: Backup via phpMyAdmin
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Pilih database: `fitmotor_dbbengkel`
3. Klik tab: `Export`
4. Pilih format: `SQL`
5. Klik: `Go` (download backup file)

#### Opsi C: Backup via MySQL Workbench
1. Buka MySQL Workbench
2. Connect ke server
3. Right-click database → `Export Schema`
4. Save file

---

### STEP 2: JALANKAN SQL MIGRATION

#### Opsi A: Via phpMyAdmin
1. Buka phpMyAdmin: `http://localhost/phpmyadmin`
2. Pilih database: `fitmotor_dbbengkel`
3. Klik tab: `SQL`
4. Copy-paste SQL dari file: `MIGRATION_TBUSER.sql`
5. Klik: `Go`

#### Opsi B: Via Command Line
```bash
# Windows Command Prompt
mysql -u root fitmotor_dbbengkel < C:\xampp\htdocs\web-bengkel\MIGRATION_TBUSER.sql
```

#### Opsi C: Via MySQL Workbench
1. Buka MySQL Workbench
2. Buka file: `MIGRATION_TBUSER.sql`
3. Klik: `Execute` (atau Ctrl+Shift+Enter)

---

### STEP 3: VERIFY DATA

Jalankan query untuk verify:

```sql
-- Check tbuser baru
SELECT id, nama_user, nama_lengkap, user_akses, is_active FROM tbuser;

-- Should return 11 rows:
-- 1, admin, Administrator, 1, active
-- 2, cs, CS & Kasir, 2, active
-- 3, kasir, CS & Kasir, 2, active
-- ... dst

-- Check kolom ada
DESCRIBE tbuser;

-- Check count
SELECT COUNT(*) FROM tbuser;  -- Should be 11
```

---

### STEP 4: TEST LOGIN

1. **Clear browser cache:**
   ```
   Ctrl+Shift+Delete
   ```

2. **Akses login page:**
   ```
   http://localhost/web-bengkel/aplikasi/aplikasi/login.php
   ```

3. **Login dengan:**
   ```
   Username: admin
   Password: admin
   Cabang: Bengkel Pusat
   ```

4. **Verify:**
   - ✅ Redirect ke `_admincab/index.php`
   - ✅ Tidak ada error 500
   - ✅ Dashboard tampil normal
   - ✅ Nama user muncul
   - ✅ Foto muncul

---

### STEP 5: TEST PROTECTED PAGES

Test semua folder `_*` untuk verify berfungsi:

```
✅ _admincab/index.php
✅ _cs/index.php
✅ _kasir/index.php
✅ _mekanik/index.php
✅ _pengadaan/index.php
✅ _crm/index.php
✅ _managemen/index.php
✅ _keuangan/index.php
✅ _hrd/index.php
```

---

### STEP 6: CLEANUP (OPTIONAL)

Setelah verify semua berfungsi, hapus tabel lama:

```sql
-- Hapus backup
DROP TABLE tbuser_karyawan;

-- Hapus tabel lama
DROP TABLE tb_user_account;
```

---

## 🔍 TROUBLESHOOTING

### Error: "Table 'tbuser' already exists"

**Solusi:** Hapus tbuser lama terlebih dahulu
```sql
DROP TABLE IF EXISTS tbuser;
```

### Error: "Foreign key constraint fails"

**Solusi:** Disable foreign key check
```sql
SET FOREIGN_KEY_CHECKS=0;
-- Run migration queries
SET FOREIGN_KEY_CHECKS=1;
```

### Error: "Column 'nama_user' doesn't exist"

**Solusi:** Verify kolom di tbuser
```sql
DESCRIBE tbuser;
-- Check apakah kolom ada
```

### Login masih error setelah migration

**Solusi:** 
1. Clear browser cache (Ctrl+Shift+Delete)
2. Restart Apache (XAMPP Control Panel)
3. Check error log: `C:\xampp\apache\logs\error.log`

---

## 📊 STRUKTUR AKHIR

### Tabel yang Tersisa

```
✅ tbuser (BARU - Gabungan)
   ├─ id, kode_karyawan, nama_user, nama_lengkap
   ├─ password, foto_user, foto
   ├─ user_akses, status_row, is_active
   ├─ email, telp, alamat
   ├─ kode_posisi, kode_level, kode_cabang
   ├─ role_name, department
   └─ created_at, updated_at, last_login

✅ tbcabang
✅ tb_user_roles
✅ tb_user_activity_log
```

### Tabel yang Dihapus

```
❌ tb_master_karyawan (rename → tbuser_karyawan, kemudian hapus)
❌ tb_user_account (hapus)
❌ tbuser (lama - sudah dihapus)
```

---

## ✅ CHECKLIST

- [ ] Backup database
- [ ] Run SQL migration (STEP 1-3)
- [ ] Verify data di tbuser
- [ ] Test login dengan admin/admin
- [ ] Verify _admincab/index.php tampil normal
- [ ] Test semua protected pages
- [ ] Cleanup tabel lama (optional)
- [ ] Update dokumentasi
- [ ] Inform team tentang perubahan

---

## 📝 NOTES

### Kolom yang Ditambah di tbuser (Baru)

```
Dari tb_master_karyawan:
- kode_karyawan
- nama_lengkap
- email
- telp
- alamat
- kode_posisi
- kode_level
- kode_cabang
- foto

Dari tb_user_account:
- password (dari password_hash)
- user_akses (dari user_akses_level)
- last_login

Dari tb_user_roles:
- role_name
- department
```

### Backward Compatibility

Semua file di `_admincab` bisa langsung menggunakan tbuser baru karena:
- ✅ Kolom `nama_user` ada (dari username)
- ✅ Kolom `password` ada (dari password_hash)
- ✅ Kolom `user_akses` ada (dari user_akses_level)
- ✅ Kolom `foto_user` ada (dari foto)
- ✅ Kolom `id` ada (primary key)

---

## 🚀 TIMELINE

- **Backup:** 2 menit
- **Migration:** 1 menit
- **Verify:** 1 menit
- **Testing:** 5 menit
- **Total:** ~10 menit

---

## 📞 SUPPORT

Jika ada error atau pertanyaan:
1. Check error log: `C:\xampp\apache\logs\error.log`
2. Check MySQL log: `C:\xampp\mysql\data\error.log`
3. Restore dari backup jika diperlukan

---

**Status:** ✅ **READY FOR IMPLEMENTATION**  
**Next Step:** Follow STEP 1-6 di atas  
**Estimasi:** 10 menit
