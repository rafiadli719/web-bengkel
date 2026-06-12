# DEBUGGING MASTER KARYAWAN ERROR 500

## 🔍 Masalah yang Dihadapi

```
POST http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/master_karyawan_ajax.php 500 (Internal Server Error)
```

## ✅ Perbaikan yang Sudah Dilakukan

### 1. Backend Error Handling
- ✅ Ditambahkan error reporting di `master_karyawan_ajax.php`
- ✅ Ditambahkan error reporting di `master_karyawan_save.php`
- ✅ Ditambahkan try-catch blocks
- ✅ Ditambahkan HTTP response codes
- ✅ Ditambahkan detail error messages

### 2. Frontend Error Handling
- ✅ Ditambahkan console logging di AJAX error handler
- ✅ Ditambahkan parsing error response
- ✅ Ditambahkan user-friendly error messages
- ✅ Ditambahkan status code checking

### 3. Browser Issues
- ✅ Dihapus external CDN yang menyebabkan tracking prevention
- ✅ Removed: Highcharts, FullCalendar CDN

## 🛠️ Cara Debug Error 500

### Step 1: Test Database Connection
```
URL: http://localhost/aplikasi/aplikasi/_admincab/test_ajax.php
```

Lihat response JSON:
- ✅ Database: Connected
- ✅ Tables: EXISTS
- ✅ Karyawan Count: > 0
- ✅ Session User: SET

### Step 2: Check Browser Console
1. Buka halaman: `http://localhost/aplikasi/aplikasi/_admincab/master_karyawan.php`
2. Tekan F12 untuk buka Developer Tools
3. Klik tab **Console**
4. Lihat error messages yang muncul

### Step 3: Check Network Tab
1. Buka tab **Network**
2. Refresh halaman
3. Cari request ke `master_karyawan_ajax.php`
4. Klik request tersebut
5. Lihat tab **Response** untuk error detail

### Step 4: Common Error Messages

**Error: "Database connection failed"**
- Cek koneksi database di `../config/koneksi.php`
- Pastikan MySQL service berjalan
- Pastikan database `fitmotor_dbbengkel` ada

**Error: "Query error: Table 'tb_master_karyawan' doesn't exist"**
- Jalankan SQL migration script
- Pastikan tabel sudah dibuat di database

**Error: "Unauthorized"**
- Pastikan sudah login
- Cek session `$_SESSION['_iduser']`

**Error: "Invalid action"**
- Pastikan parameter `action` dikirim dari frontend
- Cek nilai action: 'getList', 'delete', 'getDetail'

## 📋 File yang Sudah Diperbaiki

### Backend Files
1. **master_karyawan_ajax.php**
   - Error reporting enabled
   - Try-catch blocks added
   - HTTP response codes added
   - Database connection check added

2. **master_karyawan_save.php**
   - Error reporting enabled
   - Try-catch blocks added
   - HTTP response codes added
   - Database connection check added

3. **test_ajax.php** (Baru)
   - Database connection test
   - Table existence check
   - Query execution test
   - Session status check

### Frontend Files
1. **master_karyawan.php**
   - Enhanced error handler di loadKaryawanData()
   - Enhanced error handler di deleteKaryawan()
   - Removed external CDN (Highcharts, FullCalendar)
   - Console logging added

## 🚀 Langkah Selanjutnya

### 1. Verify Database
```bash
# Login ke MySQL
mysql -u root -p

# Pilih database
USE fitmotor_dbbengkel;

# Check tables
SHOW TABLES LIKE 'tb_master%';

# Check data
SELECT COUNT(*) FROM tb_master_karyawan;
```

### 2. Test AJAX Endpoint
```bash
# Test dengan curl
curl -X POST http://localhost/aplikasi/aplikasi/_admincab/master_karyawan_ajax.php \
  -d "action=getList" \
  -H "Content-Type: application/x-www-form-urlencoded"
```

### 3. Check PHP Error Log
```
C:\xampp\apache\logs\error.log
C:\xampp\mysql\data\*.err
```

### 4. Enable PHP Error Display
Edit `C:\xampp\php\php.ini`:
```ini
display_errors = On
error_reporting = E_ALL
```

## 📊 Expected Response Format

### Success Response
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "kode_karyawan": "MK001",
      "nama_lengkap": "ADIT PRASETIO",
      "kode_posisi": "MK",
      "kode_level": "KM",
      "kode_cabang": "001",
      "status_aktif": "aktif",
      "email": "adit@example.com",
      "telp": "081234567890",
      "tanggal_masuk": "2025-01-01"
    }
  ],
  "count": 1
}
```

### Error Response
```json
{
  "success": false,
  "message": "Query error: Table 'tb_master_karyawan' doesn't exist",
  "query": "SELECT id, kode_karyawan, ... FROM tb_master_karyawan WHERE 1=1"
}
```

## 🔐 Security Notes

- ✅ Input validation added
- ✅ SQL injection prevention (mysqli_real_escape_string)
- ✅ Session validation added
- ✅ HTTP response codes used
- ✅ Error messages sanitized

## 📞 Support

Jika masih ada error, share:
1. Screenshot dari Console tab (F12)
2. Screenshot dari Network tab (Response)
3. Output dari `test_ajax.php`
4. Error log dari PHP/MySQL

---

**Last Updated:** 15 November 2025
**Status:** ✅ Ready for Testing
