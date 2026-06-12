# 📝 UPDATE: SESUAIKAN QUERY DARI tb_master_karyawan → tbuser

## 📋 Ringkasan

Semua file yang menggunakan `tb_master_karyawan` sudah diupdate untuk menggunakan `tbuser` (gabungan).

---

## ✅ FILE YANG SUDAH DIUPDATE

### 1. **cek_login.php** ✅
**Status:** Updated

**Perubahan:**
```php
// LAMA:
SELECT ua.id, ua.kode_karyawan, ua.username, ua.password_hash, ua.user_akses_level, ua.is_active,
       k.nama_lengkap, k.email, k.telp, k.foto, k.kode_posisi, k.kode_level,
       r.role_name, r.permissions
FROM tb_user_account ua
LEFT JOIN tb_master_karyawan k ON ua.kode_karyawan = k.kode_karyawan
LEFT JOIN tb_user_roles r ON ua.user_akses_level = r.role_code
WHERE ua.username = ? AND ua.is_active = 'active'

// BARU:
SELECT id, kode_karyawan, nama_user, nama_lengkap, password, foto_user, foto,
       user_akses, status_row, is_active, email, telp, alamat, kode_posisi,
       kode_level, kode_cabang, role_name, department, last_login
FROM tbuser
WHERE nama_user = ? AND status_row = '0' AND is_active = 'active'
```

**Variable Mapping:**
```php
$lvl_akses = $user_data['user_akses'];           // (bukan user_akses_level)
$stored_password = $user_data['password'];       // (bukan password_hash)
$foto = $user_data['foto_user'] ?? $user_data['foto'];  // (dual kolom)
$permissions = null;                             // (tidak ada di tbuser, ada di tb_user_roles)
```

---

### 2. **login.php** ✅
**Status:** Updated

**Perubahan:**
```php
// LAMA:
SELECT ua.id, ua.username, k.nama_lengkap 
FROM tb_user_account ua
LEFT JOIN tb_master_karyawan k ON ua.kode_karyawan = k.kode_karyawan
WHERE ua.is_active='active'

// BARU:
SELECT id, nama_user, nama_lengkap 
FROM tbuser
WHERE status_row='0' AND is_active='active'
```

**Display:**
```php
// LAMA: "Nama Lengkap (username)"
// BARU: "Nama Lengkap (nama_user)"
```

---

### 3. **_admincab/index.php** ✅
**Status:** Updated (sebelumnya)

**Perubahan:**
```php
// LAMA:
$cari_kd=mysqli_query($koneksi,"SELECT nama_user, password, user_akses, foto_user 
                                FROM tbuser WHERE id='$id_user'");

// BARU:
$_nama = $_SESSION['_nama_lengkap'] ?? $_SESSION['_username'] ?? 'User';
$lvl_akses = $_SESSION['user_akses'] ?? 0;
$foto_user = $_SESSION['_foto'] ?? 'file_upload/avatar.png';
```

---

## 🔍 VERIFIKASI: FILE YANG MENGGUNAKAN tb_master_karyawan

**Hasil Pencarian:**
```
✅ Tidak ada file PHP yang menggunakan tb_master_karyawan
✅ Semua file sudah menggunakan tbuser atau session data
```

---

## 📊 STRUKTUR KOLOM tbuser (BARU)

```sql
CREATE TABLE tbuser (
  id INT(11) PRIMARY KEY,
  kode_karyawan VARCHAR(20),
  nama_user VARCHAR(100),              -- Username untuk login
  nama_lengkap VARCHAR(100),           -- Nama lengkap karyawan
  password VARCHAR(255),               -- Password hash
  foto_user VARCHAR(255),              -- Path foto (dari tb_master_karyawan.foto)
  foto VARCHAR(255),                   -- Duplikat foto
  user_akses INT(11),                  -- Access level (1-10)
  status_row VARCHAR(1) DEFAULT '0',   -- '0'=aktif, '1'=non-aktif
  is_active ENUM('active','inactive'), -- Status aktif
  email VARCHAR(100),                  -- Email karyawan
  telp VARCHAR(20),                    -- Telepon karyawan
  alamat TEXT,                         -- Alamat karyawan
  kode_posisi VARCHAR(20),             -- Kode posisi
  kode_level VARCHAR(20),              -- Kode level
  kode_cabang VARCHAR(20),             -- Kode cabang
  role_name VARCHAR(50),               -- Nama role
  department VARCHAR(50),              -- Departemen
  last_login TIMESTAMP,                -- Terakhir login
  created_at TIMESTAMP,
  updated_at TIMESTAMP
)
```

---

## 🧪 TESTING CHECKLIST

- [ ] Backup database
- [ ] Run SQL migration (MIGRATION_TBUSER.sql)
- [ ] Verify data di tbuser
- [ ] Test login dengan admin/admin
- [ ] Verify dropdown user muncul dengan benar
- [ ] Verify _admincab/index.php tampil normal
- [ ] Verify session data lengkap
- [ ] Test semua protected pages

---

## 📝 CATATAN PENTING

### Kolom yang Berubah

| Kolom | Sumber Lama | Sumber Baru |
|-------|-------------|------------|
| `nama_user` | tbuser.nama_user | tb_user_account.username |
| `password` | tbuser.password | tb_user_account.password_hash |
| `user_akses` | tbuser.user_akses | tb_user_account.user_akses_level |
| `nama_lengkap` | - | tb_master_karyawan.nama_lengkap |
| `email` | - | tb_master_karyawan.email |
| `telp` | - | tb_master_karyawan.telp |
| `foto_user` | tbuser.foto_user | tb_master_karyawan.foto |
| `kode_posisi` | - | tb_master_karyawan.kode_posisi |
| `kode_level` | - | tb_master_karyawan.kode_level |
| `kode_cabang` | - | tb_master_karyawan.kode_cabang |
| `role_name` | - | tb_user_roles.role_name |
| `department` | - | tb_user_roles.department |

### Backward Compatibility

✅ Semua file di `_admincab` dan folder `_*` lainnya **tidak perlu diubah** karena:
- Kolom `nama_user` ada (dari username)
- Kolom `password` ada (dari password_hash)
- Kolom `user_akses` ada (dari user_akses_level)
- Kolom `foto_user` ada (dari foto)
- Kolom `id` ada (primary key)

---

## 🚀 NEXT STEPS

1. **Backup Database**
   ```bash
   mysqldump -u root fitmotor_dbbengkel > backup.sql
   ```

2. **Run Migration**
   ```bash
   mysql -u root fitmotor_dbbengkel < MIGRATION_TBUSER.sql
   ```

3. **Test Login**
   ```
   Username: admin
   Password: admin
   Cabang: Bengkel Pusat
   ```

4. **Verify All Pages**
   - Login page
   - _admincab/index.php
   - All protected pages

---

**Status:** ✅ **SEMUA FILE SUDAH DIUPDATE**  
**Siap untuk:** Database migration & testing
