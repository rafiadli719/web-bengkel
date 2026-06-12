# PANDUAN CLEANUP: Master Mekanik & Kepala Mekanik

## 📋 Ringkasan

Menghapus tabel lama `tblmekanik` dan `tbl_master_kepala_mekanik`, lalu menggantinya dengan `tb_master_karyawan` yang sudah digabung.

---

## 🎯 Tujuan

1. ✅ Backup tabel lama
2. ✅ Migrasi data ke tb_master_karyawan
3. ✅ Buat VIEW kompatibilitas untuk backward compatibility
4. ✅ Update referensi di tabel lain
5. ✅ Siapkan aplikasi untuk update query

---

## 📊 Tabel yang Akan Dihapus

### 1. **tblmekanik**
**Struktur:**
```sql
CREATE TABLE `tblmekanik` (
  `nomekanik` varchar(20) NOT NULL,        -- Kode mekanik (MK001, MK002, etc)
  `nama` varchar(100) NOT NULL,            -- Nama mekanik
  `alamat` text,
  `telp` varchar(20),
  `keahlian` enum('1','2','3'),            -- 1=Kepala, 2=Senior, 3=Junior
  `status` enum('aktif','nonaktif'),
  `email` varchar(100),
  `tanggal_masuk` date,
  `gaji_pokok` decimal(10,0),
  `spesialisasi` text,
  `sertifikat` text,
  `created_at` timestamp,
  `updated_at` timestamp
)
```

**Data saat ini:**
- 8 mekanik (MK001 - MK008)
- 4 Kepala Mekanik (keahlian = 1)
- 4 Mekanik Senior (keahlian = 2)

### 2. **tbl_master_kepala_mekanik**
**Struktur:**
```sql
CREATE TABLE `tbl_master_kepala_mekanik` (
  `id` int(11) NOT NULL,
  `kode_cabang` varchar(20) NOT NULL,
  `nama_kepala_mekanik` varchar(100) NOT NULL,
  `nip_karyawan` varchar(50),
  `no_telepon` varchar(20),
  `tanggal_mulai` date,
  `tanggal_selesai` date,
  `status_aktif` enum('aktif','nonaktif'),
  `created_by` int(11),
  `created_at` timestamp,
  `updated_at` timestamp
)
```

**Data saat ini:**
- 2 kepala mekanik (ADIT PRASETIO, ROZAK)

### 3. **tbl_kepala_mekanik_harian** (Akan diupdate, tidak dihapus)
**Struktur:**
```sql
CREATE TABLE `tbl_kepala_mekanik_harian` (
  `id` int(11) NOT NULL,
  `kode_cabang` varchar(20) NOT NULL,
  `tanggal_kerja` date NOT NULL,
  `kepala_mekanik_1` varchar(100) NOT NULL,
  `kepala_mekanik_2` varchar(100),
  `shift_kerja` enum('full','pagi','siang','malam'),
  `keterangan` text,
  `created_by` int(11),
  `created_at` timestamp,
  `updated_at` timestamp
)
```

**Data saat ini:**
- 7 record jadwal kepala mekanik harian

---

## 🔄 Mapping Data

### tblmekanik → tb_master_karyawan

| tblmekanik | tb_master_karyawan | Keterangan |
|---|---|---|
| nomekanik | kode_karyawan | MK001, MK002, etc |
| nama | nama_lengkap | Nama lengkap |
| alamat | alamat | Alamat |
| telp | no_telepon | Nomor telepon |
| keahlian (1,2,3) | kode_level (KM,MS,MJ) | 1=KM, 2=MS, 3=MJ |
| status | status_aktif | aktif/nonaktif |
| email | email | Email |
| tanggal_masuk | tanggal_masuk | Tanggal masuk |
| gaji_pokok | gaji_pokok | Gaji pokok |
| spesialisasi | spesialisasi | Spesialisasi |
| sertifikat | sertifikat | Sertifikat |
| created_at | created_at | Waktu dibuat |
| updated_at | updated_at | Waktu diupdate |

### tbl_master_kepala_mekanik → tb_master_karyawan

| tbl_master_kepala_mekanik | tb_master_karyawan | Keterangan |
|---|---|---|
| id | kode_karyawan | Kode mekanik |
| nama_kepala_mekanik | nama_lengkap | Nama lengkap |
| nip_karyawan | kode_karyawan | Kode karyawan |
| no_telepon | no_telepon | Nomor telepon |
| tanggal_mulai | tanggal_masuk | Tanggal masuk |
| status_aktif | status_aktif | Status aktif |

---

## 📁 File yang Dibuat

### 1. **CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql**
Script SQL untuk:
- Backup tabel lama
- Migrasi data
- Buat VIEW kompatibilitas
- Update referensi di tabel lain

### 2. **PANDUAN_CLEANUP_MASTER_MEKANIK.md** (File ini)
Dokumentasi lengkap untuk cleanup

---

## 🚀 Langkah Implementasi

### PHASE 1: Backup & Verifikasi (5 menit)

**Langkah:**

1. **Backup database**
   ```bash
   mysqldump -u fitmotor_LOGIN -p fitmotor_dbbengkel > backup_before_cleanup_20251115.sql
   ```

2. **Jalankan SQL script**
   - Buka file: `CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql`
   - Jalankan di phpMyAdmin atau MySQL client
   - Script akan:
     - ✅ Backup tabel lama ke `_backup_*`
     - ✅ Migrasi data ke tb_master_karyawan
     - ✅ Buat VIEW kompatibilitas
     - ✅ Update tb_kepala_mekanik_harian

3. **Verifikasi hasil**
   ```sql
   -- Check VIEW tblmekanik
   SELECT * FROM tblmekanik;
   
   -- Check VIEW tbl_master_kepala_mekanik
   SELECT * FROM tbl_master_kepala_mekanik;
   
   -- Check data tb_kepala_mekanik_harian
   SELECT * FROM tb_kepala_mekanik_harian;
   ```

---

### PHASE 2: Test Aplikasi (1 hari)

**Test Scenarios:**

1. **Test modul mekanik**
   - Lihat daftar mekanik
   - Lihat detail mekanik
   - Edit mekanik
   - Cek tidak ada error

2. **Test modul kepala mekanik**
   - Lihat daftar kepala mekanik
   - Lihat jadwal kepala mekanik harian
   - Cek tidak ada error

3. **Test modul service**
   - Lihat daftar service
   - Assign mekanik ke service
   - Assign kepala mekanik ke service
   - Cek tidak ada error

4. **Check error_log**
   - Tidak ada error PHP
   - Tidak ada query error
   - Tidak ada undefined variable

---

### PHASE 3: Update Query di Aplikasi (2-3 hari)

**Identifikasi file yang perlu update:**

```bash
# Cari file yang query dari tblmekanik
grep -r "tblmekanik" /path/to/aplikasi/

# Cari file yang query dari tbl_master_kepala_mekanik
grep -r "tbl_master_kepala_mekanik" /path/to/aplikasi/
```

**Contoh query yang perlu diupdate:**

**DARI:**
```php
// Ambil data mekanik
$query = "SELECT * FROM tblmekanik WHERE nomekanik = 'MK001'";

// Ambil daftar mekanik
$query = "SELECT * FROM tblmekanik WHERE status = 'aktif'";

// Ambil kepala mekanik
$query = "SELECT * FROM tbl_master_kepala_mekanik WHERE status_aktif = 'aktif'";
```

**MENJADI:**
```php
// Ambil data mekanik
$query = "SELECT * FROM tb_master_karyawan 
          WHERE kode_karyawan = 'MK001' 
          AND kode_posisi IN ('MK', 'KM')";

// Ambil daftar mekanik
$query = "SELECT * FROM tb_master_karyawan 
          WHERE status_aktif = 'aktif' 
          AND kode_posisi IN ('MK', 'KM')";

// Ambil kepala mekanik
$query = "SELECT * FROM tb_master_karyawan 
          WHERE status_aktif = 'aktif' 
          AND kode_posisi = 'KM'";
```

---

### PHASE 4: Cleanup Tabel Lama (1 minggu kemudian)

**Setelah 1 minggu testing dan aplikasi sudah terupdate:**

1. **Hapus VIEW kompatibilitas**
   ```sql
   DROP VIEW IF EXISTS `tblmekanik`;
   DROP VIEW IF EXISTS `tbl_master_kepala_mekanik`;
   ```

2. **Hapus tabel lama**
   ```sql
   DROP TABLE IF EXISTS `_old_tblmekanik_20251115`;
   DROP TABLE IF EXISTS `_old_tbl_master_kepala_mekanik_20251115`;
   DROP TABLE IF EXISTS `_old_tbl_kepala_mekanik_harian_20251115`;
   ```

3. **Backup final**
   ```bash
   mysqldump -u fitmotor_LOGIN -p fitmotor_dbbengkel > backup_after_cleanup_20251115.sql
   ```

---

## 📝 Foreign Key References

Tabel yang reference ke `tblmekanik`:

1. **tblservice**
   - `mekanik1` → `tblmekanik.nomekanik`
   - `mekanik2` → `tblmekanik.nomekanik`
   - `mekanik3` → `tblmekanik.nomekanik`
   - `mekanik4` → `tblmekanik.nomekanik`
   - `mekanik5` → `tblmekanik.nomekanik`
   - `kepala_mekanik1` → `tblmekanik.nomekanik`
   - `kepala_mekanik2` → `tblmekanik.nomekanik`
   - `admin1` → `tblmekanik.nomekanik`
   - `admin2` → `tblmekanik.nomekanik`

2. **tb_user_mekanik_mapping**
   - `mekanik_code` → `tblmekanik.nomekanik`

**Status:**
- ✅ Foreign key di tb_user_mekanik_mapping sudah dihapus di CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql
- ✅ Data tblservice tetap konsisten (nomekanik = kode_karyawan)

---

## 🔍 Verifikasi Hasil

### Sebelum Cleanup

```sql
-- Cek data tblmekanik
SELECT COUNT(*) FROM tblmekanik;
-- Result: 8

-- Cek data tbl_master_kepala_mekanik
SELECT COUNT(*) FROM tbl_master_kepala_mekanik;
-- Result: 2

-- Cek data tb_master_karyawan (mekanik)
SELECT COUNT(*) FROM tb_master_karyawan 
WHERE kode_posisi IN ('MK', 'KM');
-- Result: 8 (sudah dimigrasikan)
```

### Setelah Cleanup

```sql
-- Cek VIEW tblmekanik
SELECT COUNT(*) FROM tblmekanik;
-- Result: 8 (dari VIEW)

-- Cek VIEW tbl_master_kepala_mekanik
SELECT COUNT(*) FROM tbl_master_kepala_mekanik;
-- Result: 8 (dari VIEW, semua mekanik dengan kode_posisi = 'KM')

-- Cek data tb_master_karyawan (mekanik)
SELECT COUNT(*) FROM tb_master_karyawan 
WHERE kode_posisi IN ('MK', 'KM');
-- Result: 8 (tetap sama)

-- Cek data tb_kepala_mekanik_harian (sudah diupdate)
SELECT * FROM tb_kepala_mekanik_harian LIMIT 1;
-- kepala_mekanik_1 sekarang berisi kode_karyawan (MK001, etc)
```

---

## ⚠️ Hal-Hal Penting

1. **Jangan hapus tabel lama dulu**
   - Buat VIEW kompatibilitas terlebih dahulu
   - Pastikan aplikasi sudah terupdate
   - Baru hapus tabel lama setelah 1-2 minggu

2. **Test di development dulu**
   - Jangan langsung di production
   - Cek error_log untuk debugging
   - Minta approval sebelum cleanup

3. **Backup database sebelum eksekusi**
   - Gunakan mysqldump
   - Simpan di tempat aman
   - Siap untuk rollback jika ada masalah

4. **Update dokumentasi**
   - Catat file apa saja yang sudah diupdate
   - Catat perubahan yang dilakukan
   - Catat issue yang ditemukan

5. **Monitor error_log**
   - Cek error_log setelah cleanup
   - Cek aplikasi untuk error
   - Cek database untuk inconsistency

---

## 📊 Checklist Implementasi

### Pre-Cleanup
- [ ] Backup database
- [ ] Review CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql
- [ ] Review panduan ini
- [ ] Siap untuk eksekusi

### Phase 1: Backup & Verifikasi
- [ ] Jalankan SQL script
- [ ] Verifikasi VIEW tblmekanik
- [ ] Verifikasi VIEW tbl_master_kepala_mekanik
- [ ] Verifikasi data tb_kepala_mekanik_harian

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

## 🆘 Troubleshooting

### Error: "Table 'tblmekanik' doesn't exist"
- Kemungkinan tabel sudah dihapus
- Check apakah VIEW sudah dibuat
- Restore dari backup jika perlu

### Error: "Foreign key constraint fails"
- Check apakah foreign key sudah dihapus
- Jalankan STEP 3 di CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql

### Error: "Data tidak sesuai"
- Check mapping data di STEP 5 & 6
- Verifikasi data di tb_master_karyawan
- Restore dari backup jika perlu

### Error: "Aplikasi error setelah cleanup"
- Check error_log
- Verify query di aplikasi
- Restore dari backup jika perlu

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Check error_log di server
2. Review CLEANUP_MASTER_MEKANIK_KEPALA_MEKANIK.sql
3. Review panduan ini
4. Hubungi developer untuk bantuan

---

## ✅ Kesimpulan

Dengan cleanup ini, Anda akan:
1. ✅ Menghapus duplikasi data (tblmekanik & tb_master_karyawan)
2. ✅ Menyederhanakan struktur database
3. ✅ Mempermudah maintenance
4. ✅ Mempersiapkan untuk sistem login & role baru

**Estimasi waktu total: 3-5 hari**

Selamat cleanup! 🚀
