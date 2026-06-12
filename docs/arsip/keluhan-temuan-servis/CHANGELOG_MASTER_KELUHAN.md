# 📝 CHANGELOG - MASTER KELUHAN SIMPLIFIED

**Tanggal:** 6 November 2025  
**Versi:** 2.0 (Simplified)  
**Status:** ✅ **SELESAI**

---

## 🎯 TUJUAN PERUBAHAN

Menyederhanakan sistem master keluhan dengan menghapus field yang tidak diperlukan:
- ❌ **Prioritas** (rendah, sedang, tinggi, darurat)
- ❌ **Estimasi Waktu** (dalam menit)
- ❌ **Admin/Kasir** (kolom proses)
- ✅ **Status Aktif** (tetap ada untuk soft delete)

---

## 📊 PERUBAHAN DATABASE

### Tabel: `tbmaster_keluhan`

#### SEBELUM (Versi 1.0):
```sql
CREATE TABLE `tbmaster_keluhan` (
  `id` int(11) NOT NULL,
  `kode_keluhan` varchar(10) NOT NULL,
  `nama_keluhan` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `estimasi_waktu` int(11) DEFAULT 0,              -- ❌ DIHAPUS
  `tingkat_prioritas` enum(...) DEFAULT 'sedang',  -- ❌ DIHAPUS
  `workorder_default` varchar(10) DEFAULT NULL,    -- ❌ DIHAPUS
  `status_aktif` varchar(1) DEFAULT '1',           -- ✅ TETAP ADA
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
```

#### SESUDAH (Versi 2.0):
```sql
CREATE TABLE `tbmaster_keluhan` (
  `id` int(11) NOT NULL,
  `kode_keluhan` varchar(10) NOT NULL,
  `nama_keluhan` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `status_aktif` varchar(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
```

### Kolom yang Dihapus:
1. ❌ `estimasi_waktu` - Estimasi waktu pengerjaan (menit)
2. ❌ `tingkat_prioritas` - Tingkat prioritas keluhan
3. ❌ `workorder_default` - Work order default

### Kolom yang Tetap Ada:
1. ✅ `id` - Primary key
2. ✅ `kode_keluhan` - Kode keluhan (KEL001, KEL002, ...)
3. ✅ `nama_keluhan` - Nama keluhan
4. ✅ `deskripsi` - Deskripsi keluhan
5. ✅ `kategori` - Kategori keluhan
6. ✅ `status_aktif` - Status aktif (1=aktif, 0=nonaktif)
7. ✅ `created_at` - Tanggal dibuat
8. ✅ `updated_at` - Tanggal diupdate

---

## 📁 FILE YANG DIUBAH

### 1. **master-keluhan.php** ✅

#### Perubahan Form Input:
**SEBELUM:**
```php
<div class="row">
    <div class="col-md-4">
        <label>Kategori</label>
        <select name="kategori">...</select>
    </div>
    <div class="col-md-4">
        <label>Estimasi Waktu (menit)</label>
        <input type="number" name="estimasi_waktu">
    </div>
    <div class="col-md-4">
        <label>Tingkat Prioritas</label>
        <select name="tingkat_prioritas">...</select>
    </div>
</div>
```

**SESUDAH:**
```php
<div class="form-group">
    <label>Kategori</label>
    <select class="form-control" name="kategori">
        <option value="">Pilih Kategori</option>
        <option value="Mesin">Mesin</option>
        <option value="Rem">Rem</option>
        <option value="Kelistrikan">Kelistrikan</option>
        <option value="Transmisi">Transmisi</option>
        <option value="Ban">Ban</option>
        <option value="Body">Body</option>
        <option value="Lainnya">Lainnya</option>
    </select>
</div>
```

#### Perubahan Tabel List:
**SEBELUM:**
```
| No | Kode | Nama | Deskripsi | Kategori | Estimasi | Prioritas | Proses | Aksi |
```

**SESUDAH:**
```
| No | Kode | Nama Keluhan | Deskripsi | Kategori | Aksi |
```

#### Perubahan Query:
**SEBELUM:**
```php
INSERT INTO tbmaster_keluhan 
(kode_keluhan, nama_keluhan, deskripsi, kategori, estimasi_waktu, tingkat_prioritas) 
VALUES ('$kode','$nama','$desk','$kat','$est','$prior')
```

**SESUDAH:**
```php
INSERT INTO tbmaster_keluhan 
(kode_keluhan, nama_keluhan, deskripsi, kategori) 
VALUES ('$kode','$nama','$desk','$kat')
```

---

### 2. **modal-search-keluhan.php** ✅

#### Perubahan Tabel Modal:
**SEBELUM:**
```
| Kode | Nama Keluhan | Kategori | Prioritas | Estimasi | Aksi |
| 10%  | 35%          | 20%      | 15%       | 10%      | 10%  |
```

**SESUDAH:**
```
| Kode | Nama Keluhan | Kategori | Aksi |
| 15%  | 50%          | 20%      | 15%  |
```

#### Perubahan Query:
**SEBELUM:**
```php
$sql_keluhan = mysqli_query($koneksi,"SELECT * FROM view_master_keluhan ORDER BY nama_keluhan");
while ($keluhan = mysqli_fetch_array($sql_keluhan)) {
    $priority_class = 'priority-' . $keluhan['tingkat_prioritas'];
    // Display priority badge
    // Display estimasi waktu
}
```

**SESUDAH:**
```php
$sql_keluhan = mysqli_query($koneksi,"SELECT * FROM tbmaster_keluhan WHERE status_aktif='1' ORDER BY nama_keluhan");
while ($keluhan = mysqli_fetch_array($sql_keluhan)) {
    // Hanya tampilkan kode, nama, deskripsi, kategori
}
```

#### CSS Dihapus:
```css
/* DIHAPUS */
.priority-badge { ... }
.priority-rendah { background-color: #5cb85c; }
.priority-sedang { background-color: #f0ad4e; }
.priority-tinggi { background-color: #d9534f; }
.priority-darurat { background-color: #d9534f; animation: blink 1s infinite; }
```

---

## 🗄️ SQL MIGRATION

### File: `SQL_ALTER_MASTER_KELUHAN.sql` ✅

```sql
-- Hapus kolom estimasi_waktu
ALTER TABLE `tbmaster_keluhan` 
DROP COLUMN `estimasi_waktu`;

-- Hapus kolom tingkat_prioritas
ALTER TABLE `tbmaster_keluhan` 
DROP COLUMN `tingkat_prioritas`;

-- Hapus kolom workorder_default
ALTER TABLE `tbmaster_keluhan` 
DROP COLUMN `workorder_default`;

-- Update view (opsional)
DROP VIEW IF EXISTS `view_master_keluhan`;
CREATE VIEW `view_master_keluhan` AS
SELECT 
    id, kode_keluhan, nama_keluhan, deskripsi, kategori,
    status_aktif, created_at, updated_at
FROM tbmaster_keluhan
WHERE status_aktif = '1'
ORDER BY nama_keluhan;
```

---

## 🎨 PERUBAHAN UI

### Master Keluhan (master-keluhan.php):

**Form Input:**
- ✅ Kode Keluhan (readonly, auto)
- ✅ Nama Keluhan (required)
- ✅ Deskripsi (textarea)
- ✅ Kategori (dropdown)
- ❌ ~~Estimasi Waktu~~ (dihapus)
- ❌ ~~Tingkat Prioritas~~ (dihapus)

**Tabel List:**
- ✅ No
- ✅ Kode
- ✅ Nama Keluhan
- ✅ Deskripsi (80 karakter)
- ✅ Kategori (badge)
- ❌ ~~Estimasi~~ (dihapus)
- ❌ ~~Prioritas~~ (dihapus)
- ❌ ~~Proses~~ (dihapus)
- ✅ Aksi (Hapus)

### Modal Search Keluhan:

**Tabel Modal:**
- ✅ Kode (15%)
- ✅ Nama Keluhan (50%)
- ✅ Deskripsi (100 karakter)
- ✅ Kategori (20%)
- ❌ ~~Prioritas~~ (dihapus)
- ❌ ~~Estimasi~~ (dihapus)
- ✅ Aksi (15%)

---

## ✅ TESTING CHECKLIST

### Database:
- [ ] Backup database sebelum ALTER TABLE
- [ ] Jalankan SQL script `SQL_ALTER_MASTER_KELUHAN.sql`
- [ ] Verifikasi struktur tabel dengan `DESCRIBE tbmaster_keluhan`
- [ ] Test query `SELECT * FROM tbmaster_keluhan WHERE status_aktif='1'`
- [ ] Test view `SELECT * FROM view_master_keluhan`

### Master Keluhan:
- [ ] Buka halaman `master-keluhan.php`
- [ ] Test tambah keluhan baru
- [ ] Verifikasi form hanya ada: Kode, Nama, Deskripsi, Kategori
- [ ] Test simpan data
- [ ] Verifikasi tabel list tampil dengan benar
- [ ] Test hapus keluhan (soft delete)

### Modal Search:
- [ ] Buka halaman input servis
- [ ] Klik tombol "Tambah Keluhan"
- [ ] Modal muncul dengan tabel sederhana
- [ ] Verifikasi kolom: Kode, Nama, Kategori, Aksi
- [ ] Test search keluhan
- [ ] Test filter kategori
- [ ] Test pilih keluhan
- [ ] Modal close dan field terisi

### Input Servis:
- [ ] Test input servis reguler
- [ ] Test input servis jemput
- [ ] Test input servis garansi
- [ ] Pilih keluhan dari modal
- [ ] Verifikasi keluhan tersimpan ke `tbservis_keluhan_status`

---

## 📊 DAMPAK PERUBAHAN

### Positif:
✅ **Lebih Sederhana** - Form dan tabel lebih ringkas  
✅ **Lebih Cepat** - Query lebih ringan  
✅ **Lebih Fokus** - Hanya data penting yang ditampilkan  
✅ **Lebih Mudah** - User tidak bingung dengan banyak field  

### Perhatian:
⚠️ **Data Lama** - Kolom yang dihapus akan hilang permanen  
⚠️ **View** - View `view_master_keluhan` perlu diupdate  
⚠️ **Laporan** - Laporan yang menggunakan kolom dihapus akan error  

---

## 🔄 ROLLBACK

Jika ingin mengembalikan kolom yang dihapus:

```sql
ALTER TABLE `tbmaster_keluhan` 
ADD COLUMN `estimasi_waktu` int(11) DEFAULT 0 COMMENT 'dalam menit' AFTER `kategori`,
ADD COLUMN `tingkat_prioritas` enum('rendah','sedang','tinggi','darurat') DEFAULT 'sedang' AFTER `estimasi_waktu`,
ADD COLUMN `workorder_default` varchar(10) DEFAULT NULL AFTER `tingkat_prioritas`;
```

**CATATAN:** Data lama tidak akan kembali, hanya struktur tabel!

---

## 📝 CATATAN PENTING

1. ✅ **Backup Database** - Wajib backup sebelum ALTER TABLE
2. ✅ **Test Dulu** - Test di development sebelum production
3. ✅ **Dokumentasi** - Update dokumentasi sistem
4. ✅ **Training** - Inform user tentang perubahan
5. ✅ **Monitoring** - Monitor error setelah deployment

---

## 🎯 HASIL AKHIR

### Struktur Baru:
```
tbmaster_keluhan:
├── id (PK)
├── kode_keluhan (KEL001, KEL002, ...)
├── nama_keluhan
├── deskripsi
├── kategori (Mesin, Rem, Kelistrikan, dll)
├── status_aktif (1=aktif, 0=nonaktif)
├── created_at
└── updated_at
```

### Fitur yang Tetap Ada:
✅ CRUD master keluhan  
✅ Auto generate kode  
✅ Search & filter kategori  
✅ Modal pilih keluhan  
✅ Soft delete  
✅ Timestamp tracking  

### Fitur yang Dihapus:
❌ Prioritas keluhan  
❌ Estimasi waktu  
❌ Work order default  
❌ Kolom proses  

---

**Status:** ✅ **SELESAI & SIAP DEPLOY**

**Catatan:** Pastikan backup database sebelum menjalankan SQL script!
