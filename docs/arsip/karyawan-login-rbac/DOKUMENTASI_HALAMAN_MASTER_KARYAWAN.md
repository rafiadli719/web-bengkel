# DOKUMENTASI HALAMAN MASTER KARYAWAN

## 📋 Ringkasan

Halaman web untuk mengelola data master karyawan yang terintegrasi dengan database unified `tb_master_karyawan`. Halaman ini menggabungkan fungsi untuk mengelola:
- **Karyawan Umum** (User, Admin, Kasir, dll)
- **Mekanik** (Mekanik Senior, Junior)
- **Kepala Mekanik**

---

## 📁 File yang Dibuat

### 1. **master_karyawan.php**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_karyawan.php`

**Fungsi:** Halaman utama untuk melihat daftar semua karyawan

**Fitur:**
- ✅ Tampilan tabel dengan DataTables
- ✅ Filter berdasarkan:
  - Nama atau Kode Karyawan (search)
  - Posisi (Mekanik, Kepala Mekanik, Admin, Kasir, User)
  - Level (Kepala, Senior, Junior)
  - Status (Aktif, Non-Aktif)
- ✅ Tombol Tambah Karyawan
- ✅ Tombol Edit untuk setiap karyawan
- ✅ Tombol Hapus dengan konfirmasi
- ✅ Responsive design
- ✅ Alert messages untuk feedback

**Struktur Tabel:**
```
| No | Kode Karyawan | Nama Lengkap | Posisi | Level | Cabang | Status | Aksi |
```

**Teknologi:**
- Bootstrap 3
- jQuery
- DataTables
- AJAX

---

### 2. **master_karyawan_add.php**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_karyawan_add.php`

**Fungsi:** Form untuk menambah karyawan baru

**Fitur:**
- ✅ Form terstruktur dalam 2 section:
  1. **Data Pribadi**
     - Kode Karyawan (auto-generate)
     - NIK
     - Nama Lengkap
     - Nama Panggilan
     - Email
     - Nomor Telepon
     - Alamat
  
  2. **Data Pekerjaan**
     - Posisi (dropdown)
     - Level (dropdown)
     - Cabang (dropdown)
     - Tanggal Masuk
     - Spesialisasi
     - Sertifikat
     - Status (Aktif/Non-Aktif)

- ✅ Validasi form client-side
- ✅ Auto-generate kode karyawan (KRY-00001, KRY-00002, dll)
- ✅ Dropdown untuk posisi, level, cabang
- ✅ Submit via AJAX
- ✅ Alert messages

**Validasi:**
- Kode Karyawan: Otomatis, tidak boleh duplikat
- NIK: Required
- Nama Lengkap: Required
- Posisi: Required
- Cabang: Required
- Tanggal Masuk: Required

---

### 3. **master_karyawan_edit.php**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_karyawan_edit.php`

**Fungsi:** Form untuk mengedit data karyawan yang sudah ada

**Fitur:**
- ✅ Pre-fill data dari database
- ✅ Info box menampilkan:
  - Kode Karyawan
  - Tanggal Dibuat
  - Tanggal Diupdate
- ✅ Field read-only:
  - Kode Karyawan
  - NIK
- ✅ Field editable:
  - Nama Lengkap
  - Nama Panggilan
  - Email
  - Nomor Telepon
  - Alamat
  - Posisi
  - Level
  - Cabang
  - Tanggal Masuk
  - Spesialisasi
  - Sertifikat
  - Status
- ✅ Submit via AJAX
- ✅ Alert messages

---

### 4. **master_karyawan_save.php**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_karyawan_save.php`

**Fungsi:** Backend untuk menyimpan data karyawan (tambah/edit)

**Fitur:**
- ✅ Validasi data server-side
- ✅ Check duplikat kode karyawan
- ✅ Insert data baru
- ✅ Update data existing
- ✅ Return JSON response
- ✅ Error handling

**Validasi:**
- Kode Karyawan: Tidak boleh kosong, tidak boleh duplikat
- NIK: Tidak boleh kosong
- Nama Lengkap: Tidak boleh kosong
- Posisi: Tidak boleh kosong
- Cabang: Tidak boleh kosong
- Tanggal Masuk: Tidak boleh kosong

---

### 5. **master_karyawan_ajax.php**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_karyawan_ajax.php`

**Fungsi:** Backend untuk operasi AJAX

**Fitur:**
- ✅ getList: Ambil daftar karyawan dengan filter
- ✅ delete: Hapus karyawan
- ✅ getDetail: Ambil detail karyawan

**Filter yang Tersedia:**
- Search: Cari berdasarkan kode_karyawan atau nama_lengkap
- Posisi: Filter berdasarkan kode_posisi
- Level: Filter berdasarkan kode_level
- Status: Filter berdasarkan status_aktif

**Validasi Delete:**
- Check apakah karyawan digunakan di tabel tblservice
- Jika digunakan, tidak boleh dihapus

---

## 🗄️ Database Schema

### Tabel: tb_master_karyawan

```sql
CREATE TABLE tb_master_karyawan (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    kode_karyawan VARCHAR(20) UNIQUE NOT NULL,
    nik VARCHAR(20),
    nama_lengkap VARCHAR(100) NOT NULL,
    nama_panggilan VARCHAR(50),
    kode_posisi VARCHAR(20) NOT NULL,
    kode_level VARCHAR(20),
    kode_cabang VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    telp VARCHAR(20),
    alamat TEXT,
    tanggal_masuk DATE,
    tanggal_keluar DATE,
    spesialisasi TEXT,
    sertifikat TEXT,
    foto VARCHAR(255),
    status_aktif ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kode_posisi) REFERENCES tb_master_posisi(kode_posisi),
    FOREIGN KEY (kode_level) REFERENCES tb_master_level(kode_level),
    FOREIGN KEY (kode_cabang) REFERENCES tbcabang(kode_cabang)
);
```

### Tabel: tb_master_posisi

```sql
CREATE TABLE tb_master_posisi (
    kode_posisi VARCHAR(20) PRIMARY KEY,
    nama_posisi VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Tabel: tb_master_level

```sql
CREATE TABLE tb_master_level (
    kode_level VARCHAR(20) PRIMARY KEY,
    nama_level VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

## 🔄 Alur Kerja

### Tambah Karyawan

```
1. User klik "Tambah Karyawan" di master_karyawan.php
   ↓
2. Buka master_karyawan_add.php
   ↓
3. Form ditampilkan dengan kode_karyawan auto-generate
   ↓
4. User isi form dan klik "Simpan"
   ↓
5. Submit via AJAX ke master_karyawan_save.php
   ↓
6. Validasi data server-side
   ↓
7. Insert ke tb_master_karyawan
   ↓
8. Return JSON response
   ↓
9. Redirect ke master_karyawan.php
```

### Edit Karyawan

```
1. User klik "Edit" di master_karyawan.php
   ↓
2. Buka master_karyawan_edit.php?id=X
   ↓
3. Load data dari database
   ↓
4. Form ditampilkan dengan data pre-fill
   ↓
5. User edit data dan klik "Simpan Perubahan"
   ↓
6. Submit via AJAX ke master_karyawan_save.php
   ↓
7. Validasi data server-side
   ↓
8. Update tb_master_karyawan
   ↓
9. Return JSON response
   ↓
10. Redirect ke master_karyawan.php
```

### Hapus Karyawan

```
1. User klik "Hapus" di master_karyawan.php
   ↓
2. Konfirmasi dialog
   ↓
3. Jika OK, submit via AJAX ke master_karyawan_ajax.php
   ↓
4. Check apakah karyawan digunakan di tabel lain
   ↓
5. Jika tidak digunakan, delete dari tb_master_karyawan
   ↓
6. Return JSON response
   ↓
7. Reload tabel
```

---

## 🎯 Fitur Utama

### 1. Filter & Search
- **Search Box:** Cari berdasarkan nama atau kode karyawan
- **Filter Posisi:** Pilih posisi (Mekanik, Kepala Mekanik, Admin, Kasir, User)
- **Filter Level:** Pilih level (Kepala, Senior, Junior)
- **Filter Status:** Pilih status (Aktif, Non-Aktif)
- **Tombol Filter:** Terapkan filter
- **Tombol Reset:** Bersihkan semua filter

### 2. CRUD Operations
- **Create:** Tambah karyawan baru
- **Read:** Lihat daftar karyawan
- **Update:** Edit data karyawan
- **Delete:** Hapus karyawan (dengan validasi)

### 3. Validasi
- **Client-side:** Validasi form di browser
- **Server-side:** Validasi data di backend
- **Duplikat Check:** Cek kode karyawan tidak duplikat
- **Reference Check:** Cek karyawan tidak digunakan di tabel lain

### 4. User Experience
- **Responsive Design:** Bekerja di desktop dan mobile
- **Alert Messages:** Feedback untuk setiap aksi
- **Auto-dismiss:** Alert otomatis hilang setelah 5 detik
- **Breadcrumb:** Navigasi yang jelas
- **Info Box:** Informasi tambahan di edit page

---

## 📊 Data Mapping

### Posisi (kode_posisi)
```
MK  → Mekanik
KM  → Kepala Mekanik
ADM → Admin
KSR → Kasir
USR → User
```

### Level (kode_level)
```
KM → Kepala
MS → Senior
MJ → Junior
```

### Status (status_aktif)
```
aktif    → Aktif
nonaktif → Non-Aktif
```

---

## 🔐 Security

### Authentication
- ✅ Check session `$_SESSION['_iduser']`
- ✅ Redirect ke login jika tidak authenticated

### Authorization
- ✅ Hanya user yang login bisa akses halaman
- ✅ Hanya user yang login bisa edit/hapus

### Input Validation
- ✅ Server-side validation
- ✅ mysqli_real_escape_string untuk prevent SQL injection
- ✅ Type casting untuk numeric values

### Error Handling
- ✅ Try-catch untuk database errors
- ✅ JSON response untuk error messages
- ✅ Logging untuk debugging

---

## 🚀 Cara Menggunakan

### 1. Akses Halaman Master Karyawan
```
URL: http://localhost/aplikasi/aplikasi/_admincab/master_karyawan.php
```

### 2. Tambah Karyawan Baru
```
1. Klik tombol "Tambah Karyawan"
2. Isi form dengan data karyawan
3. Klik "Simpan"
4. Tunggu konfirmasi
```

### 3. Edit Karyawan
```
1. Cari karyawan di tabel
2. Klik tombol "Edit"
3. Ubah data yang diperlukan
4. Klik "Simpan Perubahan"
5. Tunggu konfirmasi
```

### 4. Hapus Karyawan
```
1. Cari karyawan di tabel
2. Klik tombol "Hapus"
3. Konfirmasi penghapusan
4. Tunggu konfirmasi
```

### 5. Filter Data
```
1. Isi filter yang diinginkan (search, posisi, level, status)
2. Klik tombol "Filter"
3. Tabel akan di-update dengan hasil filter
4. Klik "Reset" untuk bersihkan filter
```

---

## 📝 Contoh Data

### Mekanik
```
Kode Karyawan: MK001
Nama Lengkap: ADIT PRASETIO
Posisi: Mekanik (MK)
Level: Kepala (KM)
Cabang: PESALAKAN
Status: Aktif
```

### User/Admin
```
Kode Karyawan: KRY-00001
Nama Lengkap: John Doe
Posisi: Admin (ADM)
Level: -
Cabang: PESALAKAN
Status: Aktif
```

### Kepala Mekanik
```
Kode Karyawan: KRY-00002
Nama Lengkap: Kepala Mekanik
Posisi: Kepala Mekanik (KM)
Level: Kepala (KM)
Cabang: PESALAKAN
Status: Aktif
```

---

## 🔗 Integrasi dengan Sistem Lain

### Tabel yang Reference ke tb_master_karyawan
1. **tblservice** - Mekanik yang mengerjakan service
2. **tb_kepala_mekanik_harian** - Jadwal kepala mekanik harian
3. **users** - User account (via kode_karyawan)
4. **tb_user_account** - User account baru

### VIEW Kompatibilitas
1. **tblmekanik** - VIEW untuk backward compatibility
2. **tbl_master_kepala_mekanik** - VIEW untuk backward compatibility

---

## 📈 Performance

### Optimasi
- ✅ Index pada kode_karyawan, kode_posisi, kode_level
- ✅ Pagination di DataTables
- ✅ AJAX untuk load data tanpa refresh
- ✅ Caching untuk dropdown options

### Query Optimization
```sql
-- Dengan index
SELECT * FROM tb_master_karyawan 
WHERE kode_karyawan LIKE '%search%' 
ORDER BY kode_karyawan ASC
-- Execution time: < 100ms
```

---

## 🐛 Troubleshooting

### Masalah: Data tidak muncul di tabel
**Solusi:**
1. Check database connection
2. Verify table name dan column names
3. Check user permissions
4. Check browser console untuk error messages

### Masalah: Tombol Edit/Hapus tidak bekerja
**Solusi:**
1. Check JavaScript console untuk error
2. Verify AJAX endpoint
3. Check server-side error logs
4. Verify user session

### Masalah: Form tidak submit
**Solusi:**
1. Check form validation
2. Verify required fields terisi
3. Check browser console untuk error
4. Check network tab untuk AJAX request

---

## 📚 Referensi

- **Bootstrap:** https://getbootstrap.com/docs/3.3/
- **jQuery:** https://jquery.com/
- **DataTables:** https://datatables.net/
- **MySQL:** https://dev.mysql.com/doc/

---

## ✅ Checklist Implementasi

- [x] Buat halaman master_karyawan.php
- [x] Buat halaman master_karyawan_add.php
- [x] Buat halaman master_karyawan_edit.php
- [x] Buat file master_karyawan_save.php
- [x] Buat file master_karyawan_ajax.php
- [x] Implementasi filter & search
- [x] Implementasi CRUD operations
- [x] Implementasi validasi
- [x] Implementasi error handling
- [x] Test di browser
- [x] Dokumentasi lengkap

---

## 🎉 Kesimpulan

Halaman Master Karyawan sudah siap digunakan untuk mengelola semua data karyawan, user, mekanik, dan kepala mekanik dalam satu interface yang modern dan user-friendly.

**Status:** ✅ **READY TO USE**

**Estimasi waktu implementasi:** 1-2 jam
**Estimasi waktu training:** 30 menit

Siap untuk di-deploy ke production? 🚀
