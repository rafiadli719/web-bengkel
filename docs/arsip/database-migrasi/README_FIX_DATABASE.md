# FIX DATABASE - Sistem Temuan & Penawaran Part

## ⚠️ PENTING: Perbedaan Struktur Database

Setelah analisa database `fitmotor_dbbengkel.sql`, ternyata struktur database Anda **BERBEDA** dengan asumsi awal.

### Perbedaan Nama Tabel & Field

| Asumsi Awal | Database Real Anda | Keterangan |
|-------------|-------------------|------------|
| `tbbarang` | `tblitem` | Nama tabel untuk item/barang |
| `kode_barang` | `noitem` | Field kode item |
| `nama_barang` | `namaitem` | Field nama item |
| `harga_jual` | `hargajual` | Field harga jual |
| `stok` | `quantity` | Field stok tersedia |
| `kategori` | `jenis` | Field kategori/jenis item |

---

## ✅ SOLUSI: File SQL yang Sudah Diperbaiki

Saya sudah membuat **2 versi file SQL**:

### 1. File Lama (Tidak Cocok)
❌ `sql_update_temuan_system.sql` - **JANGAN DIPAKAI** karena menggunakan `tbbarang`

### 2. File Baru (Sudah Diperbaiki)
✅ `sql_update_temuan_system_FIXED.sql` - **PAKAI INI!**

---

## 🚀 CARA INSTALASI YANG BENAR

### STEP 1: Jalankan SQL Script yang Sudah Diperbaiki

```sql
-- Buka phpMyAdmin
-- Pilih database: fitmotor_dbbengkel
-- Import atau copy-paste isi file: sql_update_temuan_system_FIXED.sql
-- Klik "Go"
```

**PENTING:** Pastikan tidak ada error saat execute!

---

### STEP 2: Cek Item yang Ada di Database

Sebelum isi mapping, cek dulu `noitem` yang tersedia:

```sql
-- Cek filter udara
SELECT noitem, namaitem, hargajual, quantity
FROM tblitem
WHERE namaitem LIKE '%filter%'
AND statusitem = '1'
ORDER BY namaitem;

-- Cek oli mesin
SELECT noitem, namaitem, hargajual, quantity
FROM tblitem
WHERE namaitem LIKE '%oli%'
AND statusitem = '1'
ORDER BY namaitem;

-- Cek kampas rem
SELECT noitem, namaitem, hargajual, quantity
FROM tblitem
WHERE namaitem LIKE '%kampas%'
OR namaitem LIKE '%rem%'
AND statusitem = '1'
ORDER BY namaitem;
```

**Catat `noitem` yang akan digunakan!**

---

### STEP 3: Isi Data Mapping dengan noitem yang Real

Setelah tahu `noitem` yang ada, update mapping:

```sql
-- CONTOH: Jika Anda punya item dengan noitem berikut:
-- Filter Beat Original = '011-FLT-BEAT-ORI'
-- Filter Beat KW = '011-FLT-BEAT-KW'
-- Oli 10W-40 Synthetic = '02-OLI-10W40-SYN'

INSERT INTO tbmaster_temuan_barang_mapping
(kode_temuan, noitem, is_primary, prioritas, qty_default, keterangan, status_aktif)
VALUES
-- Temuan: Filter Udara Kotor (TMN001)
('TMN001', '011-FLT-BEAT-ORI', 1, 1, 1, 'Filter Udara Beat Original (Rekomendasi)', 1),
('TMN001', '011-FLT-BEAT-KW', 0, 2, 1, 'Filter Udara Beat KW (Alternatif)', 1),

-- Temuan: Oli Mesin Kotor (TMN002)
('TMN002', '02-OLI-10W40-SYN', 1, 1, 1, 'Oli Synthetic 10W-40 (Rekomendasi)', 1)

ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;
```

**⚠️ GANTI `noitem` dengan yang REAL dari database Anda!**

---

### STEP 4: Test View Suggested Parts

Setelah isi mapping, test apakah view berfungsi:

```sql
-- Test view
SELECT * FROM view_suggested_parts WHERE kode_temuan = 'TMN001';
```

Harusnya muncul data part yang sudah di-mapping!

---

## 🔍 PENJELASAN TEKNIS

### Kenapa Pakai ALIAS di View?

Di `view_suggested_parts`, saya gunakan ALIAS agar code PHP tidak perlu diubah banyak:

```sql
CREATE OR REPLACE VIEW view_suggested_parts AS
SELECT
    m.kode_temuan,
    mt.nama_temuan,
    m.noitem AS kode_barang,          -- ALIAS!
    i.namaitem AS nama_barang,        -- ALIAS!
    i.hargajual AS harga_jual,        -- ALIAS!
    i.quantity AS stok,               -- ALIAS!
    i.jenis AS kategori,
    m.is_primary,
    m.prioritas,
    m.qty_default,
    m.keterangan,
    CASE
        WHEN i.quantity > 5 THEN 'ready_stock'
        WHEN i.quantity > 0 THEN 'stok_terbatas'
        ELSE 'indent'
    END AS status_stok
FROM tbmaster_temuan_barang_mapping m
INNER JOIN tbmaster_temuan mt ON m.kode_temuan = mt.kode_temuan
INNER JOIN tblitem i ON m.noitem = i.noitem    -- JOIN ke tblitem!
WHERE m.status_aktif = 1
  AND i.statusitem = '1'
ORDER BY m.kode_temuan, m.is_primary DESC, m.prioritas ASC;
```

Dengan ALIAS:
- `noitem` menjadi `kode_barang`
- `namaitem` menjadi `nama_barang`
- `hargajual` menjadi `harga_jual`
- `quantity` menjadi `stok`

Code PHP bisa tetap pakai nama lama!

---

## 📝 FILE PHP YANG SUDAH DIPERBAIKI

File-file ini sudah saya update agar compatible:

### 1. `ajax-get-suggested-parts.php`
✅ Sudah OK - pakai view dengan alias

### 2. `servis-temuan-add-proses.php`
✅ Sudah diperbaiki - query ke `tblitem` dengan field yang benar:
```php
$query_barang = "SELECT namaitem, hargajual, quantity FROM tblitem WHERE noitem='$kode_barang'";
```

### 3. `servis-penawaran-approve.php`
✅ Tidak perlu diubah - langsung ambil dari tbservis_penawaran_part

---

## ⚠️ CATATAN PENTING

### Field `kode_barang` di tbservis_penawaran_part

Di tabel `tbservis_penawaran_part`, field `kode_barang` **sebenarnya menyimpan `noitem`**.

Ini tidak masalah karena:
1. Nama field tetap `kode_barang` (untuk compatibility)
2. Isinya adalah `noitem` dari tblitem
3. View dan query sudah handle ini dengan benar

### Mapping Table Structure

```
tbmaster_temuan_barang_mapping
├── kode_temuan (TMN001, TMN002, dll)
├── noitem (link ke tblitem.noitem)  ← BUKAN kode_barang!
├── is_primary (1 atau 0)
├── prioritas (1, 2, 3, ...)
└── qty_default (default quantity)
```

---

## 🧪 TESTING WORKFLOW

### Test 1: Cek View

```sql
SELECT * FROM view_suggested_parts WHERE kode_temuan = 'TMN001';
```

Expected result: Muncul part yang sudah di-mapping

### Test 2: Cek via PHP

1. Buka browser: `http://localhost/web-bengkel/_admincab/ajax-get-suggested-parts.php`
2. POST parameter: `kode_temuan=TMN001`
3. Expected: JSON response dengan list parts

### Test 3: Full Workflow

1. Buka halaman servis
2. Input keluhan
3. Tab "Temuan & Penawaran"
4. Pilih temuan "Filter Udara Kotor"
5. Sistem auto-suggest part
6. Simpan → Check database

---

## 🐛 TROUBLESHOOTING

### Error: "Table 'fitmotor_dbbengkel.tbbarang' doesn't exist"

**Penyebab:** Masih pakai SQL file lama

**Solusi:**
1. Drop semua tabel yang baru dibuat
2. Pakai file **sql_update_temuan_system_FIXED.sql**

### Auto-suggest tidak muncul part

**Penyebab:** Mapping masih pakai `noitem` sample yang tidak ada

**Solusi:**
1. Cek: `SELECT * FROM tbmaster_temuan_barang_mapping;`
2. Cek: `SELECT * FROM tblitem WHERE noitem = '[noitem-dari-mapping]';`
3. Pastikan `noitem` di mapping ADA di tblitem

### Part muncul tapi stok 0

**Normal!** Artinya `quantity` di tblitem = 0. Part tetap bisa ditawarkan dengan status "Indent".

---

## 📞 BANTUAN

Jika masih ada error:

1. ✅ Cek error message di browser console (F12)
2. ✅ Cek MySQL error log
3. ✅ Pastikan file SQL FIXED sudah dijalankan
4. ✅ Pastikan mapping pakai `noitem` yang valid

---

## 🎉 KESIMPULAN

Perbedaan utama:
- Database Anda pakai **`tblitem`** bukan `tbbarang`
- Field pakai **`noitem`, `namaitem`, `hargajual`**
- SQL & PHP sudah **DIPERBAIKI** di file versi FIXED

**Gunakan file dengan suffix `_FIXED` untuk instalasi!**

---

_Last Updated: 2025-11-26_
_Version: 1.1 (Fixed)_
