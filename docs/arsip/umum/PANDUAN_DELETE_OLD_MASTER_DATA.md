# PANDUAN DELETE OLD MASTER MEKANIK & KEPALA MEKANIK DATA

## 📋 Daftar Isi
1. [Ringkasan](#ringkasan)
2. [Prasyarat](#prasyarat)
3. [Cara Menjalankan](#cara-menjalankan)
4. [Verifikasi](#verifikasi)
5. [Rollback](#rollback)

---

## 📌 Ringkasan

Anda ingin menghapus data dari tabel lama:
- `_old_tblmekanik_20251115` (backup tabel mekanik lama)
- `_old_tbl_master_kepala_mekanik_20251115` (backup tabel kepala mekanik lama)

**Alasan:** Data sudah di-migrate ke `tb_master_karyawan` dan compatibility views sudah dibuat.

---

## ✅ Prasyarat

Sebelum menjalankan cleanup, pastikan:

- ✅ Data sudah di-migrate ke `tb_master_karyawan`
- ✅ Compatibility views sudah dibuat:
  - `tblmekanik` (VIEW)
  - `tbl_master_kepala_mekanik` (VIEW)
- ✅ Backup tables masih ada:
  - `_backup_tblmekanik_20251115`
  - `_backup_tbl_master_kepala_mekanik_20251115`
- ✅ Application sudah menggunakan `tb_master_karyawan`
- ✅ Tidak ada referensi langsung ke tabel lama

---

## 🚀 Cara Menjalankan

### Opsi 1: Menggunakan phpMyAdmin (Recommended untuk pemula)

**Step 1: Buka phpMyAdmin**
```
http://localhost/phpmyadmin
```

**Step 2: Pilih database `fitmotor_dbbengkel`**

**Step 3: Klik tab "SQL"**

**Step 4: Copy-paste script berikut:**
```sql
-- Verify data integrity
SELECT 'Total Mekanik (MK)' as description, COUNT(*) as count
FROM tb_master_karyawan WHERE kode_posisi = 'MK'
UNION ALL
SELECT 'Total Kepala Mekanik (KM)', COUNT(*)
FROM tb_master_karyawan WHERE kode_posisi = 'KM';

-- Delete old tables
DROP TABLE IF EXISTS `_old_tblmekanik_20251115`;
DROP TABLE IF EXISTS `_old_tbl_master_kepala_mekanik_20251115`;

-- Verify views still work
SELECT COUNT(*) as mekanik_view_count FROM tblmekanik;
SELECT COUNT(*) as kepala_mekanik_view_count FROM tbl_master_kepala_mekanik;
```

**Step 5: Klik "Go" untuk menjalankan**

---

### Opsi 2: Menggunakan Command Line

**Step 1: Buka Command Prompt / PowerShell**

**Step 2: Navigate ke folder project:**
```bash
cd C:\xampp\htdocs\web-bengkel
```

**Step 3: Jalankan script:**
```bash
mysql -u root -p fitmotor_dbbengkel < CLEANUP_OLD_MASTER_TABLES.sql
```

**Step 4: Tekan Enter (password kosong jika tidak ada password)**

---

### Opsi 3: Menggunakan Batch File (Windows)

**Step 1: Double-click file:**
```
C:\xampp\htdocs\web-bengkel\run_cleanup.bat
```

**Step 2: Script akan otomatis menjalankan cleanup**

**Step 3: Tekan Enter untuk menutup**

---

## 🔍 Verifikasi

Setelah menjalankan cleanup, verifikasi dengan:

### 1. Check tabel sudah dihapus
```sql
-- Ini akan return "Table doesn't exist" error (yang berarti berhasil)
SELECT * FROM `_old_tblmekanik_20251115`;
SELECT * FROM `_old_tbl_master_kepala_mekanik_20251115`;
```

### 2. Check views masih berfungsi
```sql
-- Ini harus return data
SELECT COUNT(*) FROM tblmekanik;
SELECT COUNT(*) FROM tbl_master_kepala_mekanik;
```

### 3. Check data di tb_master_karyawan
```sql
-- Lihat total karyawan
SELECT COUNT(*) as total FROM tb_master_karyawan;

-- Lihat mekanik
SELECT COUNT(*) as mekanik FROM tb_master_karyawan WHERE kode_posisi = 'MK';

-- Lihat kepala mekanik
SELECT COUNT(*) as kepala_mekanik FROM tb_master_karyawan WHERE kode_posisi = 'KM';
```

### 4. Test aplikasi
```
1. Buka halaman master_karyawan.php
2. Pastikan data masih muncul dengan benar
3. Filter posisi "Mekanik" dan "Kepala Mekanik"
4. Pastikan semuanya berfungsi normal
```

---

## 🔄 Rollback

Jika ada masalah, Anda masih bisa restore dari backup tables:

### Restore dari backup
```sql
-- Restore tblmekanik
INSERT INTO `_old_tblmekanik_20251115` 
SELECT * FROM `_backup_tblmekanik_20251115`;

-- Restore tbl_master_kepala_mekanik
INSERT INTO `_old_tbl_master_kepala_mekanik_20251115` 
SELECT * FROM `_backup_tbl_master_kepala_mekanik_20251115`;
```

---

## 📊 Tabel yang Akan Dihapus

| Tabel | Tipe | Status | Alasan |
|-------|------|--------|--------|
| `_old_tblmekanik_20251115` | Backup | ❌ DIHAPUS | Data sudah di-migrate |
| `_old_tbl_master_kepala_mekanik_20251115` | Backup | ❌ DIHAPUS | Data sudah di-migrate |
| `_backup_tblmekanik_20251115` | Backup | ✅ TETAP | Untuk reference |
| `_backup_tbl_master_kepala_mekanik_20251115` | Backup | ✅ TETAP | Untuk reference |
| `tblmekanik` | VIEW | ✅ TETAP | Compatibility view |
| `tbl_master_kepala_mekanik` | VIEW | ✅ TETAP | Compatibility view |
| `tb_master_karyawan` | TABLE | ✅ TETAP | Unified master table |

---

## 📝 Struktur Setelah Cleanup

```
Database: fitmotor_dbbengkel
├── tb_master_karyawan (TABLE - unified data)
│   ├── Mekanik (posisi = 'MK')
│   └── Kepala Mekanik (posisi = 'KM')
├── tblmekanik (VIEW - compatibility)
│   └── Pulls from tb_master_karyawan
├── tbl_master_kepala_mekanik (VIEW - compatibility)
│   └── Pulls from tb_master_karyawan
├── _backup_tblmekanik_20251115 (TABLE - reference only)
└── _backup_tbl_master_kepala_mekanik_20251115 (TABLE - reference only)
```

---

## ⚠️ Catatan Penting

1. **Backup Aman:** Backup tables masih ada jika Anda perlu reference
2. **Views Tetap:** Compatibility views tetap berfungsi
3. **Aplikasi Tidak Terpengaruh:** Aplikasi akan terus berfungsi normal
4. **Data Terpusat:** Semua data sekarang terpusat di `tb_master_karyawan`
5. **Mudah Rollback:** Jika ada masalah, bisa restore dari backup

---

## 🎯 Checklist Sebelum Cleanup

- [ ] Backup database sudah dibuat (export dari phpMyAdmin)
- [ ] Data sudah di-migrate ke tb_master_karyawan
- [ ] Compatibility views sudah dibuat dan berfungsi
- [ ] Aplikasi sudah menggunakan tb_master_karyawan
- [ ] Tidak ada referensi langsung ke tabel lama di aplikasi
- [ ] Sudah test halaman master_karyawan.php
- [ ] Sudah verify data integrity

---

## 📞 Troubleshooting

### Error: "Table doesn't exist"
**Solusi:** Tabel sudah dihapus sebelumnya, tidak perlu cleanup lagi

### Error: "Access denied"
**Solusi:** Pastikan username dan password MySQL benar

### Error: "Foreign key constraint fails"
**Solusi:** Ada data yang masih direferensi, jangan hapus sampai referensi dihapus

### Views tidak berfungsi
**Solusi:** Recreate views menggunakan script di `INTEGRASI_USERS_MASTERKEYS_TO_MASTER_KARYAWAN.sql`

---

## 📋 File yang Tersedia

1. **DELETE_OLD_MASTER_DATA.sql** - Script untuk verify sebelum delete
2. **CLEANUP_OLD_MASTER_TABLES.sql** - Script untuk delete tabel lama
3. **run_cleanup.bat** - Batch file untuk Windows
4. **PANDUAN_DELETE_OLD_MASTER_DATA.md** - Dokumentasi ini

---

**Last Updated:** 15 November 2025  
**Status:** ✅ Ready for Execution

Silakan jalankan cleanup sesuai dengan opsi yang Anda pilih! 🚀
