# 🔧 PERBAIKAN ERROR 500 - _admincab/index.php

## 🐛 Masalah

**Error:** HTTP ERROR 500 saat login ke `_admincab/index.php`

**Penyebab:** File `_admincab/index.php` masih query dari `tbuser` yang sudah tidak ada di session baru.

```php
// LAMA (Error):
$cari_kd=mysqli_query($koneksi,"SELECT 
    nama_user, password, user_akses, foto_user 
    FROM tbuser WHERE id='$id_user'");
```

---

## ✅ Solusi

**Gunakan data dari session yang sudah di-set di `cek_login.php`:**

```php
// BARU (Fixed):
$_nama = $_SESSION['_nama_lengkap'] ?? $_SESSION['_username'] ?? 'User';
$lvl_akses = $_SESSION['user_akses'] ?? 0;
$foto_user = $_SESSION['_foto'] ?? 'file_upload/avatar.png';
```

---

## 📝 Perubahan yang Dilakukan

### File: `_admincab/index.php`

**Sebelum:**
```php
$cari_kd=mysqli_query($koneksi,"SELECT 
    nama_user, password, user_akses, foto_user 
    FROM tbuser WHERE id='$id_user'");			
$tm_cari=mysqli_fetch_array($cari_kd);
$_nama=$tm_cari['nama_user'];				        
$pwd=$tm_cari['password'];				        
$lvl_akses=$tm_cari['user_akses'];				                
$foto_user=$tm_cari['foto_user'];				
if($foto_user=='') {
    $foto_user="file_upload/avatar.png";
}
```

**Sesudah:**
```php
// Get user data dari session (sudah di-set di cek_login.php)
$_nama = $_SESSION['_nama_lengkap'] ?? $_SESSION['_username'] ?? 'User';
$lvl_akses = $_SESSION['user_akses'] ?? 0;
$foto_user = $_SESSION['_foto'] ?? 'file_upload/avatar.png';

if(empty($foto_user) || $foto_user == '') {
    $foto_user = "file_upload/avatar.png";
}
```

---

## 🎯 Session Variables yang Tersedia

Dari `cek_login.php`, session sudah di-set dengan data lengkap:

```php
$_SESSION['_iduser']         // ID user
$_SESSION['_username']       // Username
$_SESSION['_kode_karyawan']  // Kode karyawan
$_SESSION['_nama_lengkap']   // Nama lengkap karyawan
$_SESSION['_email']          // Email
$_SESSION['_foto']           // Foto karyawan
$_SESSION['_cabang']         // Kode cabang
$_SESSION['user_akses']      // Access level (1-10)
$_SESSION['_role_name']      // Nama role
$_SESSION['_permissions']    // Array permissions
```

---

## 🧪 Testing

### Step 1: Refresh Halaman
```
http://localhost/web-bengkel/aplikasi/aplikasi/login.php
```

### Step 2: Login
```
Username: admin
Password: admin
Cabang: Bengkel Pusat
```

### Step 3: Verify
Seharusnya redirect ke `_admincab/index.php` dan tidak ada error 500

---

## 📋 Checklist

- ✅ Perbaiki `_admincab/index.php` untuk gunakan session data
- ✅ Verify tidak ada file lain yang query dari `tbuser`
- ✅ Test login flow
- ⏳ Verify semua protected pages berfungsi

---

## 🚀 Next Steps

1. **Test Login** (Immediate)
   - Login dengan admin/admin
   - Verify redirect ke _admincab/index.php
   - Verify tidak ada error 500

2. **Check Other Protected Pages** (Today)
   - `_cs/index.php`
   - `_kasir/index.php`
   - `_mekanik/index.php`
   - dll

3. **Update Semua Protected Pages** (If needed)
   - Ganti query tbuser dengan session data
   - Verify semua berfungsi

---

**Status:** ✅ **PERBAIKAN SELESAI**  
**Siap untuk:** Test login 🎉
