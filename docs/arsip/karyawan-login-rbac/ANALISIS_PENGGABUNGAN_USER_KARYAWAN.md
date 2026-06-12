# 🔄 ANALISIS: PENGGABUNGAN tbuser DENGAN tb_master_karyawan

## 📋 Ringkasan Eksekutif

**Pertanyaan:** Apakah `tbuser` perlu? Bisa diganti dengan `tb_master_karyawan`?

**Jawaban:** ✅ **BISA!** Dengan catatan kecil.

**Rekomendasi:** Gunakan `tb_user_account` + `tb_master_karyawan` (hapus `tbuser`)

---

## 📊 Perbandingan Tabel

### Tabel Saat Ini

```
tbuser (Legacy)
├─ Tujuan: User login
├─ Kolom: id, nama_user, password, user_akses, status_row, is_active
├─ Data: 11 users
└─ Masalah: Duplikasi dengan tb_user_account

tb_user_account (Modern)
├─ Tujuan: User account dengan security
├─ Kolom: id, kode_karyawan, username, password_hash, user_akses_level, is_active
├─ Data: 11 users
└─ Relasi: FK ke tb_master_karyawan

tb_master_karyawan (Master Data)
├─ Tujuan: Master data karyawan
├─ Kolom: id, kode_karyawan, nama_lengkap, kode_posisi, kode_level, kode_cabang, email, telp, alamat, foto
├─ Data: 23 karyawan
└─ Relasi: FK ke posisi, level, cabang
```

---

## ✅ ANALISIS: BISA DIGABUNG

### Alasan 1: Struktur Sudah Mendukung

**tb_user_account sudah punya:**
- ✅ `kode_karyawan` (FK ke tb_master_karyawan)
- ✅ `username` (untuk login)
- ✅ `password_hash` (untuk password)
- ✅ `user_akses_level` (untuk role/permission)
- ✅ `is_active` (untuk status)
- ✅ `last_login` (untuk tracking)

**tb_master_karyawan sudah punya:**
- ✅ `nama_lengkap` (nama karyawan)
- ✅ `email` (email karyawan)
- ✅ `telp` (nomor telepon)
- ✅ `foto` (foto karyawan)
- ✅ `kode_posisi` (posisi/jabatan)
- ✅ `kode_level` (level/grade)
- ✅ `kode_cabang` (cabang)

---

### Alasan 2: Relasi Sudah Ada

```
tb_user_account.kode_karyawan → tb_master_karyawan.kode_karyawan
```

Ini berarti:
- Setiap user account sudah terhubung ke data karyawan
- Bisa ambil nama, email, foto, posisi, dll dari tb_master_karyawan

---

### Alasan 3: Menghilangkan Duplikasi

**Saat ini:**
```
tbuser (11 users) + tb_user_account (11 users) = Duplikasi!
```

**Setelah penggabungan:**
```
tb_user_account (11 users) + tb_master_karyawan (23 karyawan) = Efisien!
```

---

## 🔧 SOLUSI: STRUKTUR BARU

### Opsi 1: Gunakan tb_user_account + tb_master_karyawan (RECOMMENDED)

**Struktur:**
```
Login Flow:
1. User input username & password
2. Query tb_user_account WHERE username=? AND password_hash=?
3. Get kode_karyawan dari tb_user_account
4. Query tb_master_karyawan WHERE kode_karyawan=?
5. Get nama_lengkap, email, foto, posisi, cabang, dll
6. Set session dengan semua data
```

**Keuntungan:**
- ✅ Tidak ada duplikasi data
- ✅ Satu sumber kebenaran (single source of truth)
- ✅ Mudah maintain
- ✅ Relasi jelas (FK)
- ✅ Data karyawan lengkap (nama, email, foto, posisi, dll)

**Tabel yang digunakan:**
- ✅ tb_user_account (untuk login)
- ✅ tb_master_karyawan (untuk data karyawan)
- ✅ tb_user_roles (untuk permission)
- ❌ tbuser (HAPUS)

---

### Opsi 2: Tambah Kolom ke tb_master_karyawan (ALTERNATIVE)

**Jika ingin lebih simple, tambahkan kolom ke tb_master_karyawan:**

```sql
ALTER TABLE tb_master_karyawan ADD COLUMN (
    username varchar(50) UNIQUE,
    password_hash varchar(255),
    user_akses_level int(11),
    is_active enum('active','inactive','locked') DEFAULT 'active',
    last_login timestamp NULL,
    must_change_password enum('yes','no') DEFAULT 'no'
);
```

**Keuntungan:**
- ✅ Hanya 1 tabel untuk user & karyawan
- ✅ Lebih simple
- ✅ Semua data di satu tempat

**Kekurangan:**
- ❌ tb_master_karyawan menjadi terlalu besar
- ❌ Tidak semua karyawan perlu login (misal: karyawan yang sudah keluar)
- ❌ Mixing concerns (master data + authentication)

---

## 🎯 REKOMENDASI: OPSI 1

**Gunakan:**
- ✅ `tb_user_account` untuk login
- ✅ `tb_master_karyawan` untuk data karyawan
- ✅ `tb_user_roles` untuk permission

**Hapus:**
- ❌ `tbuser` (sudah tidak perlu)

**Alasan:**
1. Struktur sudah ada (tidak perlu migration besar)
2. Relasi sudah jelas (FK)
3. Tidak ada duplikasi
4. Mudah maintain
5. Scalable (bisa tambah karyawan tanpa login)

---

## 📝 IMPLEMENTASI QUERY

### Query Login (Opsi 1: Recommended)

```php
<?php
// Login dengan tb_user_account + tb_master_karyawan

$username = $_POST['txtnama'];
$password = $_POST['txtpass'];
$cabang = $_POST['cbocabang'];

// Query tb_user_account
$query = "SELECT 
    ua.id, ua.kode_karyawan, ua.username, ua.user_akses_level, ua.is_active,
    k.nama_lengkap, k.email, k.telp, k.foto, k.kode_posisi, k.kode_level,
    r.role_name, r.permissions
FROM tb_user_account ua
LEFT JOIN tb_master_karyawan k ON ua.kode_karyawan = k.kode_karyawan
LEFT JOIN tb_user_roles r ON ua.user_akses_level = r.role_code
WHERE ua.username=? AND ua.password_hash=? AND ua.is_active='active'";

$stmt = $koneksi->prepare($query);
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Set session
    $_SESSION['_iduser'] = $user['id'];
    $_SESSION['_username'] = $user['username'];
    $_SESSION['_nama_lengkap'] = $user['nama_lengkap'];
    $_SESSION['_email'] = $user['email'];
    $_SESSION['_telp'] = $user['telp'];
    $_SESSION['_foto'] = $user['foto'];
    $_SESSION['_kode_karyawan'] = $user['kode_karyawan'];
    $_SESSION['_kode_posisi'] = $user['kode_posisi'];
    $_SESSION['_kode_level'] = $user['kode_level'];
    $_SESSION['_cabang'] = $cabang;
    $_SESSION['_user_akses'] = $user['user_akses_level'];
    $_SESSION['_role_name'] = $user['role_name'];
    $_SESSION['_permissions'] = json_decode($user['permissions'], true);
    
    // Update last login
    $update_query = "UPDATE tb_user_account SET last_login=NOW() WHERE id=?";
    $update_stmt = $koneksi->prepare($update_query);
    $update_stmt->bind_param("i", $user['id']);
    $update_stmt->execute();
    
    // Log activity
    logActivity($user['id'], 'LOGIN', 'auth', 'User login');
    
    // Redirect
    header("Location: _admincab/index.php");
    exit;
} else {
    $_SESSION['login_error'] = 'Username atau password salah!';
}
?>
```

---

## 🔄 MIGRATION PLAN

### Step 1: Verify Data (Sekarang)
```sql
-- Check if all users in tbuser have corresponding karyawan
SELECT u.nama_user, k.nama_lengkap
FROM tbuser u
LEFT JOIN tb_master_karyawan k ON u.id = k.id
WHERE k.id IS NULL;
-- Result: Harus 0 rows (semua user ada di karyawan)
```

### Step 2: Verify tb_user_account (Sekarang)
```sql
-- Check if tb_user_account sudah lengkap
SELECT COUNT(*) FROM tb_user_account;
-- Result: 11 (sama dengan tbuser)
```

### Step 3: Update Login.php (Hari 1)
```
Ganti query dari tbuser ke tb_user_account + tb_master_karyawan
```

### Step 4: Test Login (Hari 1-2)
```
Test semua user bisa login
Test session data lengkap
Test permission checking
```

### Step 5: Backup tbuser (Hari 3)
```sql
-- Backup tbuser sebelum dihapus
CREATE TABLE tbuser_backup AS SELECT * FROM tbuser;
```

### Step 6: Hapus tbuser (Hari 3)
```sql
-- Hapus tbuser setelah yakin tidak digunakan
DROP TABLE tbuser;
```

---

## 📊 PERBANDINGAN SEBELUM & SESUDAH

### SEBELUM (Saat Ini)

```
Tabel:
├─ tbuser (11 users) - LEGACY
├─ tb_user_account (11 users) - MODERN
├─ tb_master_karyawan (23 karyawan)
├─ tb_user_roles (9 roles)
└─ tb_user_activity_log (0 records)

Masalah:
├─ Duplikasi data user
├─ 2 tabel untuk user (confusing)
├─ tbuser tidak digunakan
└─ Maintenance sulit
```

### SESUDAH (Recommended)

```
Tabel:
├─ tb_user_account (11 users) - LOGIN
├─ tb_master_karyawan (23 karyawan) - DATA KARYAWAN
├─ tb_user_roles (9 roles) - PERMISSION
└─ tb_user_activity_log (0 records) - LOGGING

Keuntungan:
├─ Tidak ada duplikasi
├─ 1 tabel untuk user (clear)
├─ Relasi jelas (FK)
├─ Maintenance mudah
└─ Scalable
```

---

## ✅ CHECKLIST IMPLEMENTASI

- [ ] Verify data consistency (tbuser vs tb_user_account)
- [ ] Update login.php untuk gunakan tb_user_account + tb_master_karyawan
- [ ] Update cek_login.php dengan query baru
- [ ] Test login dengan semua user
- [ ] Test session data lengkap
- [ ] Test permission checking
- [ ] Backup tbuser
- [ ] Hapus tbuser dari database
- [ ] Update dokumentasi
- [ ] Inform team tentang perubahan

---

## 🎯 KESIMPULAN

**Pertanyaan:** Apakah `tbuser` perlu? Bisa diganti dengan `tb_master_karyawan`?

**Jawaban:**
- ✅ `tbuser` TIDAK PERLU (sudah ada `tb_user_account`)
- ✅ Bisa digabung dengan `tb_master_karyawan` via `tb_user_account`
- ✅ Gunakan `tb_user_account` + `tb_master_karyawan` (recommended)
- ✅ Hapus `tbuser` (sudah tidak digunakan)

**Timeline:**
- Hari 1: Update login.php
- Hari 2: Testing
- Hari 3: Backup & Hapus tbuser

**Effort:**
- Minimal (hanya update query login)
- Tidak ada data migration (sudah ada di tb_user_account)
- Tidak ada downtime

---

**Dibuat:** Nov 16, 2025  
**Status:** ✅ Analisis Selesai  
**Next Step:** Implementasi (Update login.php)
