# PANDUAN UPDATE MASTER POSISI & KARYAWAN

## 📋 Ringkasan

Anda telah meminta untuk:
1. ✅ Membuat `master_posisi.php` dengan template seperti `index.php`
2. ✅ Menambahkan `master_karyawan.php` dan `master_posisi.php` ke sidebar
3. ✅ Menghapus `mekanik.php` dan `master_kepala_mekanik.php` dari sidebar
4. ✅ Delete file `mekanik.php` dan `master_kepala_mekanik.php`

---

## ✅ File yang Dibuat

### 1. master_posisi.php
- **Path:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_posisi.php`
- **Fitur:**
  - Template ACE seperti `index.php`
  - Navbar dengan user profile
  - Sidebar dengan menu
  - Filter data posisi
  - Tabel daftar posisi
  - AJAX untuk load data

### 2. master_posisi_ajax.php
- **Path:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_posisi_ajax.php`
- **Fitur:**
  - Backend untuk load data posisi
  - Error handling lengkap
  - JSON response

---

## 📝 Perubahan Sidebar

### File: menu_dashboard.php

**Sebelum:**
```
Mekanik
├── Master Mekanik (mekanik.php) ❌ DIHAPUS
├── Level Mekanik (mekanik_level.php)
├── Master Kepala Mekanik (master_kepala_mekanik.php) ❌ DIHAPUS
└── Tarif Jemput Antar (master-tarif-jemput.php)
```

**Sesudah:**
```
Mekanik
├── Master Karyawan (master_karyawan.php) ✅ BARU
├── Master Posisi (master_posisi.php) ✅ BARU
├── Level Mekanik (mekanik_level.php)
└── Tarif Jemput Antar (master-tarif-jemput.php)
```

---

## 🗑️ File yang Dihapus

### Opsi 1: Menggunakan Batch File (Recommended)

**Step 1:** Double-click file:
```
C:\xampp\htdocs\web-bengkel\backup_and_delete_old_files.bat
```

**Step 2:** Script akan:
- ✅ Membuat backup file lama
- ✅ Menghapus file lama
- ✅ Menampilkan summary

**File yang dihapus:**
- `mekanik.php`
- `master_kepala_mekanik.php`

**Backup disimpan di:**
```
C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\_backup_deleted_files\
```

---

### Opsi 2: Manual Delete

**Step 1:** Buka Windows Explorer

**Step 2:** Navigate ke:
```
C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\
```

**Step 3:** Delete file:
- `mekanik.php`
- `master_kepala_mekanik.php`

---

## 🧪 Testing

### Test 1: Sidebar Menu
```
1. Buka halaman: http://localhost/aplikasi/aplikasi/_admincab/index.php
2. Lihat sidebar menu
3. Verifikasi:
   ✅ Master Karyawan ada
   ✅ Master Posisi ada
   ✅ Master Mekanik TIDAK ada
   ✅ Master Kepala Mekanik TIDAK ada
```

### Test 2: Master Karyawan
```
1. Klik menu "Master Karyawan"
2. Expected: Halaman master_karyawan.php terbuka
3. Verifikasi:
   ✅ Template sama seperti index.php
   ✅ Navbar ada
   ✅ Sidebar ada
   ✅ Data karyawan ditampilkan
   ✅ Filter berfungsi
```

### Test 3: Master Posisi
```
1. Klik menu "Master Posisi"
2. Expected: Halaman master_posisi.php terbuka
3. Verifikasi:
   ✅ Template sama seperti index.php
   ✅ Navbar ada
   ✅ Sidebar ada
   ✅ Data posisi ditampilkan
   ✅ Filter berfungsi
```

---

## 📊 Struktur File Setelah Update

```
_admincab/
├── index.php (Dashboard)
├── master_karyawan.php ✅ (Master Karyawan - BARU)
├── master_karyawan_ajax.php ✅
├── master_karyawan_add.php ✅
├── master_karyawan_edit.php ✅
├── master_karyawan_save.php ✅
├── master_posisi.php ✅ (Master Posisi - BARU)
├── master_posisi_ajax.php ✅ (AJAX - BARU)
├── mekanik.php ❌ (DIHAPUS)
├── master_kepala_mekanik.php ❌ (DIHAPUS)
├── mekanik_level.php (Tetap)
├── master-tarif-jemput.php (Tetap)
└── menu_dashboard.php ✅ (UPDATED)
```

---

## 📋 Checklist

- [ ] File `master_posisi.php` sudah dibuat
- [ ] File `master_posisi_ajax.php` sudah dibuat
- [ ] Sidebar menu sudah diupdate
- [ ] File `mekanik.php` sudah di-backup
- [ ] File `master_kepala_mekanik.php` sudah di-backup
- [ ] File lama sudah dihapus
- [ ] Test Master Karyawan
- [ ] Test Master Posisi
- [ ] Test Sidebar Menu

---

## 🔄 Rollback

Jika ada masalah, Anda bisa restore file dari backup:

```
C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\_backup_deleted_files\
```

Copy file `.backup_*` kembali ke folder `_admincab` dan rename ke nama aslinya.

---

## 📞 Troubleshooting

### Error: "File not found"
**Solusi:** Pastikan file sudah dibuat di lokasi yang benar

### Error: "404 Not Found"
**Solusi:** Pastikan sidebar menu sudah diupdate di `menu_dashboard.php`

### Data tidak muncul di Master Posisi
**Solusi:** Check database, pastikan tabel `tb_master_posisi` ada dan memiliki data

### Sidebar menu tidak berubah
**Solusi:** Clear browser cache (Ctrl+Shift+Delete) dan refresh halaman

---

## 📝 File Dokumentasi

1. **PANDUAN_UPDATE_MASTER_POSISI_KARYAWAN.md** - Panduan ini
2. **DELETED_FILES_LOG.md** - Log file yang dihapus
3. **backup_and_delete_old_files.bat** - Script untuk backup & delete

---

## ✅ Status

**Selesai:** 15 November 2025

**Langkah Selanjutnya:**
1. Run batch file untuk delete file lama
2. Test halaman master_karyawan.php
3. Test halaman master_posisi.php
4. Verifikasi sidebar menu

---

**Last Updated:** 15 November 2025
