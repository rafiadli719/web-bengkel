# RINGKASAN CLEANUP: Master Mekanik & Kepala Mekanik

## 📋 Status Saat Ini

### Tabel yang Ada Sekarang

1. **tblmekanik** (Tabel Lama)
   - 8 mekanik (MK001 - MK008)
   - Struktur: nomekanik, nama, alamat, telp, keahlian, status, email, dll

2. **tbl_master_kepala_mekanik** (Tabel Lama)
   - 2 kepala mekanik
   - Struktur: id, kode_cabang, nama_kepala_mekanik, nip_karyawan, dll

3. **tbl_kepala_mekanik_harian** (Tabel Baru)
   - 7 record jadwal kepala mekanik harian
   - Perlu diupdate untuk gunakan kode_karyawan

4. **tb_master_karyawan** (Tabel Baru - Unified)
   - 8 mekanik sudah dimigrasikan
   - Struktur: kode_karyawan, nama_lengkap, kode_posisi (MK/KM), kode_level, dll

---

## 🎯 Tujuan Cleanup

Menghapus duplikasi data dengan:
1. ✅ Backup tabel lama
2. ✅ Migrasi data ke tb_master_karyawan (sudah selesai)
3. ✅ Buat VIEW kompatibilitas untuk backward compatibility
4. ✅ Update referensi di tabel lain (tbl_kepala_mekanik_harian)
5. ✅ Siapkan aplikasi untuk update query

---

## 📁 File yang Dibuat

### 1. **CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\`

**Fungsi:**
- Backup tabel lama ke `_backup_*_20251115`
- Migrasi data ke tb_master_karyawan
- Buat VIEW kompatibilitas:
  - `tblmekanik` → query dari tb_master_karyawan
  - `tbl_master_kepala_mekanik` → query dari tb_master_karyawan
- Update tb_kepala_mekanik_harian untuk gunakan kode_karyawan
- Hapus foreign key constraints

**Status:** ✅ Siap dijalankan

### 2. **PANDUAN_CLEANUP_MASTER_MEKANIK.md**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\`

**Isi:**
- Dokumentasi lengkap cleanup
- Mapping data dari tabel lama ke baru
- Langkah implementasi (4 phase)
- Verifikasi hasil
- Troubleshooting
- Checklist implementasi

**Status:** ✅ Siap digunakan

---

## 🔄 Mapping Data

### tblmekanik → tb_master_karyawan

```
nomekanik (MK001)     → kode_karyawan (MK001)
nama                  → nama_lengkap
alamat                → alamat
telp                  → no_telepon
keahlian (1,2,3)      → kode_level (KM,MS,MJ)
status                → status_aktif
email                 → email
tanggal_masuk         → tanggal_masuk
gaji_pokok            → gaji_pokok
spesialisasi          → spesialisasi
sertifikat            → sertifikat
```

### tbl_master_kepala_mekanik → tb_master_karyawan

```
id                    → kode_karyawan
nama_kepala_mekanik   → nama_lengkap
nip_karyawan          → kode_karyawan
no_telepon            → no_telepon
tanggal_mulai         → tanggal_masuk
status_aktif          → status_aktif
```

---

## 📊 Data yang Akan Dimigrasi

### Mekanik (dari tblmekanik)

| Kode | Nama | Keahlian | Status |
|------|------|----------|--------|
| MK001 | ADIT PRASETIO | 1 (Kepala) | aktif |
| MK002 | AHMAD FAIZAL | 1 (Kepala) | aktif |
| MK003 | GITO SUPARDI | 1 (Kepala) | aktif |
| MK004 | MUHAMMAD ARIFIAN N | 1 (Kepala) | aktif |
| MK005 | Dedi Kurniawan | 2 (Senior) | aktif |
| MK006 | Eko Prasetyo | 2 (Senior) | aktif |
| MK007 | Fajar Nugroho | 2 (Senior) | aktif |
| MK008 | Gilang Ramadhan | 2 (Senior) | aktif |

**Status:** ✅ Sudah ada di tb_master_karyawan

### Kepala Mekanik (dari tbl_master_kepala_mekanik)

| ID | Nama | Cabang | Status |
|----|----|--------|--------|
| 1 | ADIT PRASETIO | PESALAKAN | aktif |
| 2 | ROZAK | PESALAKAN | aktif |

**Status:** ✅ Sudah ada di tb_master_karyawan

---

## 🚀 Langkah Implementasi

### PHASE 1: Backup & Verifikasi (5 menit)

1. **Backup database**
   ```bash
   mysqldump -u fitmotor_LOGIN -p fitmotor_dbbengkel > backup_before_cleanup_20251115.sql
   ```

2. **Jalankan CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql**
   - Buka di phpMyAdmin
   - Jalankan script
   - Script akan backup tabel lama dan buat VIEW

3. **Verifikasi hasil**
   ```sql
   SELECT * FROM tblmekanik;
   SELECT * FROM tbl_master_kepala_mekanik;
   SELECT * FROM tb_kepala_mekanik_harian LIMIT 1;
   ```

### PHASE 2: Test Aplikasi (1 hari)

- Test modul mekanik
- Test modul kepala mekanik
- Test modul service
- Check error_log

### PHASE 3: Update Query di Aplikasi (2-3 hari)

**Cari file yang perlu update:**
```bash
grep -r "tblmekanik" /path/to/aplikasi/
grep -r "tbl_master_kepala_mekanik" /path/to/aplikasi/
```

**Contoh update:**

DARI:
```php
SELECT * FROM tblmekanik WHERE nomekanik = 'MK001'
```

MENJADI:
```php
SELECT * FROM tb_master_karyawan 
WHERE kode_karyawan = 'MK001' 
AND kode_posisi IN ('MK', 'KM')
```

### PHASE 4: Cleanup Tabel Lama (1 minggu kemudian)

```sql
-- Hapus VIEW kompatibilitas
DROP VIEW IF EXISTS `tblmekanik`;
DROP VIEW IF EXISTS `tbl_master_kepala_mekanik`;

-- Hapus tabel lama
DROP TABLE IF EXISTS `_old_tblmekanik_20251115`;
DROP TABLE IF EXISTS `_old_tbl_master_kepala_mekanik_20251115`;
```

---

## 📝 Foreign Key References

**Tabel yang reference ke tblmekanik:**

1. **tblservice** (9 columns)
   - mekanik1, mekanik2, mekanik3, mekanik4, mekanik5
   - kepala_mekanik1, kepala_mekanik2
   - admin1, admin2

2. **tb_user_mekanik_mapping**
   - mekanik_code

**Status:** ✅ Foreign key di tb_user_mekanik_mapping sudah dihapus

---

## ✅ Verifikasi Sebelum & Sesudah

### Sebelum Cleanup

```sql
SELECT COUNT(*) FROM tblmekanik;
-- Result: 8

SELECT COUNT(*) FROM tbl_master_kepala_mekanik;
-- Result: 2

SELECT COUNT(*) FROM tb_master_karyawan WHERE kode_posisi IN ('MK', 'KM');
-- Result: 8
```

### Setelah Cleanup

```sql
SELECT COUNT(*) FROM tblmekanik;  -- VIEW
-- Result: 8

SELECT COUNT(*) FROM tbl_master_kepala_mekanik;  -- VIEW
-- Result: 8 (semua mekanik dengan kode_posisi = 'KM')

SELECT COUNT(*) FROM tb_master_karyawan WHERE kode_posisi IN ('MK', 'KM');
-- Result: 8
```

---

## ⏱️ Estimasi Waktu

| Phase | Waktu | Status |
|-------|-------|--------|
| Phase 1: Backup & Verifikasi | 5 menit | Next |
| Phase 2: Test Aplikasi | 1 hari | Pending |
| Phase 3: Update Query | 2-3 hari | Pending |
| Phase 4: Cleanup Tabel Lama | 1 hari | Pending (1 minggu kemudian) |
| **TOTAL** | **3-5 hari** | |

---

## 📋 Checklist Implementasi

### Pre-Cleanup
- [ ] Backup database
- [ ] Review CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql
- [ ] Review PANDUAN_CLEANUP_MASTER_MEKANIK.md

### Phase 1: Backup & Verifikasi
- [ ] Jalankan SQL script
- [ ] Verifikasi VIEW tblmekanik
- [ ] Verifikasi VIEW tbl_master_kepala_mekanik
- [ ] Verifikasi tb_kepala_mekanik_harian

### Phase 2: Test Aplikasi
- [ ] Test modul mekanik
- [ ] Test modul kepala mekanik
- [ ] Test modul service
- [ ] Check error_log

### Phase 3: Update Query
- [ ] Identifikasi file yang perlu update
- [ ] Update query di aplikasi
- [ ] Test setiap modul
- [ ] Document perubahan

### Phase 4: Cleanup (1 minggu kemudian)
- [ ] Hapus VIEW kompatibilitas
- [ ] Hapus tabel lama
- [ ] Backup final
- [ ] Update dokumentasi

---

## 🔗 Hubungan dengan Sistem Login & Role

Cleanup ini adalah **prerequisite** untuk sistem login & role baru:

1. ✅ Master karyawan sudah unified (tb_master_karyawan)
2. ✅ Mekanik & kepala mekanik sudah di master karyawan
3. ⏳ Cleanup tabel lama (tblmekanik, tbl_master_kepala_mekanik)
4. ⏳ Update query di aplikasi untuk gunakan tb_master_karyawan
5. ⏳ Implementasi role-based access control di _admincab

---

## 📚 Referensi File

- **CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql** - SQL script
- **PANDUAN_CLEANUP_MASTER_MEKANIK.md** - Dokumentasi lengkap
- **DATABASE_REFACTORING_MASTER_KARYAWAN.sql** - Struktur tabel baru
- **INTEGRASI_USERS_MASTERKEYS_TO_MASTER_KARYAWAN.sql** - Migrasi data

---

## ✅ Kesimpulan

**Cleanup master mekanik & kepala mekanik sudah siap dijalankan:**

1. ✅ SQL script sudah dibuat
2. ✅ Dokumentasi sudah lengkap
3. ✅ Mapping data sudah jelas
4. ✅ Verifikasi sudah disiapkan
5. ✅ Checklist sudah lengkap

**Langkah selanjutnya:**
1. Jalankan CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql
2. Test aplikasi untuk memastikan VIEW berfungsi
3. Update query di aplikasi untuk gunakan tb_master_karyawan
4. Setelah 1 minggu, hapus tabel lama dan VIEW kompatibilitas

**Estimasi waktu total: 3-5 hari**

Siap untuk Phase 1? 🚀
