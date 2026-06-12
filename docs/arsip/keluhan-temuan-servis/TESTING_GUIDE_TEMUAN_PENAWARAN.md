# TESTING GUIDE - Fitur Temuan & Penawaran Part

## Status Implementasi

**SUDAH DIIMPLEMENTASIKAN!** Tab "Temuan & Penawaran" sudah ditambahkan ke:
- _admincab/servis-input-reguler.php

---

## Prerequisites - WAJIB LAKUKAN DULU!

Sebelum testing, pastikan sudah:

### 1. Import SQL FINAL
```
File: sql_update_temuan_system_FINAL.sql
Status: Harus sudah dijalankan tanpa error
```

### 2. Insert Mapping Data
```
File: INSERT_MAPPING_REAL_DATA.sql
Status: Minimal untuk TMN001, TMN004, TMN006
```

### 3. Test VIEW
```sql
SELECT * FROM view_suggested_parts WHERE kode_temuan = 'TMN001';
-- Harus muncul data filter udara
```

---

## CARA TESTING

### STEP 1: Buka Halaman Input Servis

1. Start Apache di XAMPP
2. Browser: http://localhost/web-bengkel/_admincab/
3. Login
4. Menu: Servis Reguler > Input Servis

### STEP 2: Cek Tab Baru

Harus melihat 6 TAB:
```
[Detail Servis] [Work Order] [Item Barang] [Item Jasa] [Temuan & Penawaran] [Actions]
```

### STEP 3: Klik Tab "Temuan & Penawaran"

Harus melihat:
- Info box biru (Fitur Auto-Suggest)
- Form Input Temuan (blue header)
- Daftar Temuan (green header)

### STEP 4: Test Auto-Suggest

1. Pilih Keluhan (buat dulu jika belum ada)
2. Pilih Master Temuan: "TMN001 - Filter Udara Kotor"
3. **MAGIC!** Section "Suggested Parts" muncul otomatis dengan 4 filter
4. Centang part yang diinginkan
5. Pilih "Penggantian Part"
6. Klik "Simpan Temuan"

### STEP 5: Verifikasi

Database check:
```sql
SELECT * FROM tbservis_temuan WHERE no_service = 'NOSERV-XXX';
SELECT * FROM tbservis_penawaran_part WHERE no_service = 'NOSERV-XXX';
```

---

## TEST CASES

- [ ] TMN001 (Filter) - muncul 4 options
- [ ] TMN004 (Aki) - muncul 4 options
- [ ] TMN006 (Busi) - muncul 4 options
- [ ] Input manual tanpa auto-suggest
- [ ] Upload foto temuan

---

## TROUBLESHOOTING

### Tab tidak muncul
- Restart Apache
- Clear browser cache
- Refresh (Ctrl+F5)

### Auto-suggest tidak muncul
- Cek VIEW: `SELECT * FROM view_suggested_parts`
- Re-run INSERT_MAPPING_REAL_DATA.sql

### AJAX Error
- Cek browser console (F12)
- Pastikan ajax-get-suggested-parts.php ada

---

## REPORT FORMAT

```
HASIL TESTING
1. SQL Import: [OK/FAIL]
2. Tab Muncul: [OK/FAIL]
3. Auto-Suggest: [OK/FAIL]
4. Data Tersimpan: [OK/FAIL]

Screenshot: [attach]
```
