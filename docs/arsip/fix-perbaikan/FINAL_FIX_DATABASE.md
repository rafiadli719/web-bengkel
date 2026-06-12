# FINAL FIX - Database Temuan & Penawaran

**Tanggal:** 7 November 2025  
**Issue:** Kolom `harga_jual` tidak ada, seharusnya `harga`

---

## 🐛 MASALAH YANG DITEMUKAN

Error saat create view:
```
#1054 - Unknown column 'mnb.harga_jual' in 'field list'
```

**Penyebab:** 
- Tabel `tbmaster_nama_barang` menggunakan kolom `harga`, bukan `harga_jual`

---

## ✅ PERBAIKAN YANG SUDAH DILAKUKAN

### 1. File SQL Diperbaiki
**File:** `fix_missing_views_triggers_v2.sql`
- View simplified (tanpa kolom harga dulu)
- Semua triggers tetap ada

### 2. Handler PHP Diperbaiki
**File:** `_handler_temuan_penawaran.php`
- Query menggunakan `mnb.harga as harga_jual`
- Berlaku untuk:
  - `get_parts_by_kategori` (Fast moves)
  - `search_part` (Search global)

---

## 🚀 LANGKAH EKSEKUSI

### STEP 1: Jalankan SQL File V2

**Via phpMyAdmin:**
1. Buka phpMyAdmin
2. Pilih database `fitmotor_dbbengkel`
3. Tab "SQL"
4. Copy-paste isi file `fix_missing_views_triggers_v2.sql`
5. Klik "Go"

**Via Command Line:**
```bash
mysql -u root -p fitmotor_dbbengkel < fix_missing_views_triggers_v2.sql
```

### STEP 2: Verify Database

Refresh halaman check:
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/check_database_temuan.php
```

**Yang Harus Muncul:**
- ✅ view_penawaran_part_lengkap → EXISTS
- ✅ view_fastmoves_barang → EXISTS
- ✅ 5 Triggers → EXISTS

### STEP 3: Test Sistem

1. **Buka servis input:**
   ```
   http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reguler.php?snoserv=SV25000000154
   ```

2. **Test Tab Temuan & Penawaran:**
   - Klik tab "Temuan & Penawaran"
   - Klik "Tambah Temuan"
   - Klik icon search → Modal muncul
   - Klik "Pilih" → Value terisi

3. **Test Fast Moves:**
   - Klik "Fast Moves"
   - Klik kategori (misal: "Filter Udara")
   - **Jika muncul part** → SUCCESS ✅
   - **Jika kosong** → Perlu mapping barang

---

## 📊 STRUKTUR KOLOM YANG BENAR

### tbmaster_nama_barang
```
- kode_barang (VARCHAR)
- nama_barang (VARCHAR)
- harga (DECIMAL/DOUBLE) ← BUKAN harga_jual
- satuan (VARCHAR)
- status (VARCHAR)
```

### Query yang Benar
```sql
SELECT 
    mnb.kode_barang,
    mnb.nama_barang,
    mnb.harga as harga_jual,  -- Alias untuk compatibility
    mnb.satuan
FROM tbmaster_nama_barang mnb
```

---

## 🔧 MAPPING BARANG KE FAST MOVES

Setelah views & triggers OK, mapping barang:

### Cara 1: Via Halaman Admin
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/master-fastmoves.php
```
- Tab "Mapping Barang"
- Klik "Tambah Barang"
- Pilih kategori: FLT (Filter Udara)
- Pilih barang: [Cari filter udara yang ada]
- Simpan

### Cara 2: Via SQL
```sql
-- Cek dulu kode barang yang ada
SELECT kode_barang, nama_barang 
FROM tbmaster_nama_barang 
WHERE nama_barang LIKE '%filter%' 
LIMIT 10;

-- Insert mapping
INSERT INTO tbmaster_barang_fastmoves 
(kode_kategori, kode_barang, is_featured, urutan) 
VALUES 
('FLT', 'KODE_FILTER_ANDA', 1, 1);
```

---

## ✅ CHECKLIST FINAL

Setelah semua langkah:

- [ ] SQL V2 berhasil dijalankan
- [ ] Check database: 2 views EXISTS
- [ ] Check database: 5 triggers EXISTS
- [ ] Handler PHP sudah update (otomatis)
- [ ] Tab "Temuan & Penawaran" tidak kosong
- [ ] Modal "Search Temuan" berfungsi
- [ ] Modal "Fast Moves" berfungsi
- [ ] Tidak ada error di console
- [ ] Mapping minimal 1 barang ke fast moves
- [ ] Test add temuan → Berhasil
- [ ] Test add penawaran → Berhasil

---

## 🎯 EXPECTED RESULT

### Modal Fast Moves Setelah Mapping
```
Klik "Filter Udara" →
┌─────────────────────────────────────────┐
│ Kode Part │ Nama      │ Satuan │ Stok  │
├─────────────────────────────────────────┤
│ FLT001    │ Filter    │ PCS    │ 10    │
│           │ Udara     │        │ [+]   │
└─────────────────────────────────────────┘
```

### Jika Masih Kosong
Berarti belum ada mapping. Lakukan mapping via:
1. Halaman master-fastmoves.php, ATAU
2. SQL manual insert

---

## 📞 TROUBLESHOOTING

### Error: View still not created
- Check apakah SQL V2 sudah dijalankan
- Check error message di phpMyAdmin
- Pastikan database yang dipilih benar

### Fast Moves Kosong
- Normal jika belum ada mapping
- Lakukan mapping barang ke kategori
- Minimal 1 barang per kategori untuk testing

### Handler Error
- File `_handler_temuan_penawaran.php` sudah auto-update
- Jika masih error, cek kolom di tbmaster_nama_barang:
  ```sql
  SHOW COLUMNS FROM tbmaster_nama_barang;
  ```

---

**File siap dijalankan!** 🚀

Jalankan `fix_missing_views_triggers_v2.sql` dan test lagi.
