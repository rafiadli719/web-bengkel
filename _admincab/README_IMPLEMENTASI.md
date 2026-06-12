# 🚀 IMPLEMENTASI FITUR PERHITUNGAN JARAK OTOMATIS
## Folder: aplikasi/_admincab

---

## ✅ FILE YANG SUDAH DIBUAT:

| No | File | Status | Deskripsi |
|----|------|--------|-----------|
| 1 | **haversine-distance.js** | ✅ READY | Library JavaScript perhitungan jarak GPS |
| 2 | **PATCH_servis-reguler-jemput.txt** | ✅ READY | Panduan patch servis-reguler-jemput.php |
| 3 | **PATCH_cabang.php.txt** | ✅ READY | Panduan patch cabang.php |
| 4 | **servis-reguler-jemput.php.backup** | ✅ BACKUP | Backup otomatis file original |
| 5 | **README_IMPLEMENTASI.md** | ✅ READY | File ini |

---

## 📂 LOKASI FILE:

```
C:\xampp\htdocs\web-bengkel/aplikasi/aplikasi/_admincab/
├── ✅ assets/js/haversine-distance.js (SUDAH DIBUAT)
├── ✅ PATCH_servis-reguler-jemput.txt (PANDUAN PATCH)
├── ✅ PATCH_cabang.php.txt (PANDUAN PATCH)
├── ✅ servis-reguler-jemput.php.backup (BACKUP)
├── ✅ README_IMPLEMENTASI.md (FILE INI)
│
├── ⚠️ servis-reguler-jemput.php (PERLU DI-PATCH)
└── ⚠️ cabang.php (PERLU DI-PATCH)
```

---

## 🎯 LANGKAH IMPLEMENTASI (3 MENIT):

### Step 1: Update Database (1 menit)
```bash
1. Buka phpMyAdmin
2. Pilih database: fitmotor_dbbengkel
3. Import file: C:\xampp\htdocs\web-bengkel\UPDATE_DATABASE_JARAK_OTOMATIS.sql
4. ✅ Done!
```

### Step 2: Patch File PHP (2 menit)

#### A. Patch `servis-reguler-jemput.php`
```bash
1. Buka file: PATCH_servis-reguler-jemput.txt
2. Ikuti 5 patch yang ada:
   - PATCH 1: Update query cabang
   - PATCH 2: Update INSERT query
   - PATCH 3: Update UPDATE query
   - PATCH 4: Include JavaScript library
   - PATCH 5: Update JavaScript function
3. Save file
```

#### B. Patch `cabang.php`
```bash
1. Buka file: PATCH_cabang.php.txt
2. Ikuti 2 patch yang ada:
   - PATCH 1: Update table header
   - PATCH 2: Update table body
3. Save file
```

### Step 3: Testing (1 menit)
```bash
1. Login ke sistem
2. Buka Master Cabang → Cek kolom baru (Alamat, Google Maps, Koordinat)
3. Buka Jadwal Penjemputan → Pilih kendaraan
4. ✅ Jarak otomatis dihitung!
5. ✅ Tarif otomatis muncul!
```

---

## 📋 CHECKLIST IMPLEMENTASI:

### Database:
- [ ] Import UPDATE_DATABASE_JARAK_OTOMATIS.sql
- [ ] Verifikasi field `tbcabang` (alamat_cabang, google_maps_cabang, lat_cabang, long_cabang)
- [ ] Verifikasi field `tblservice` (kondisi_motor, jarak_jemput, tarif_jemput)

### File PHP (_admincab):
- [✅] Backup servis-reguler-jemput.php (DONE)
- [ ] Patch servis-reguler-jemput.php (5 patches)
- [ ] Patch cabang.php (2 patches)

### JavaScript:
- [✅] File haversine-distance.js (DONE)
- [ ] Include di servis-reguler-jemput.php

### Testing:
- [ ] Test cabang.php → Kolom baru tampil
- [ ] Test servis-reguler-jemput.php → Jarak otomatis
- [ ] Test tarif motor jalan
- [ ] Test tarif motor mogok
- [ ] Test simpan ke database
- [ ] Verify data di tblservice

---

## 🔍 DETAIL PATCHES:

### File: servis-reguler-jemput.php
**Total Patches: 5**

1. **PATCH 1** - Update Query Cabang (Line ~31-43)
   - Tambah field: alamat_cabang, google_maps_cabang, lat_cabang, long_cabang
   - Tambah variable: $lat_cabang, $long_cabang, $google_maps_cabang

2. **PATCH 2** - Update INSERT Query (Line ~204-213)
   - Tambah field: kondisi_motor, jarak_jemput, tarif_jemput
   - Ambil dari form: $_POST['txtkondisi'], $_POST['txtjarak'], $_POST['txttarif']

3. **PATCH 3** - Update UPDATE Query (Line ~214-227)
   - Tambah field: kondisi_motor, jarak_jemput, tarif_jemput
   - Update bind_param

4. **PATCH 4** - Include JavaScript (Line ~289)
   - Tambah: `<script src="assets/js/haversine-distance.js"></script>`

5. **PATCH 5** - Update JavaScript Function (Line ~828-907)
   - Tambah function: autoCalculateDistance()
   - Update function: hitungTarif()
   - Tambah event listeners

### File: cabang.php
**Total Patches: 2**

1. **PATCH 1** - Update Table Header (Line ~221-227)
   - Tambah kolom: Alamat, Google Maps, Koordinat

2. **PATCH 2** - Update Table Body (Line ~242-258)
   - Tampilkan alamat (max 50 char)
   - Tampilkan link Google Maps (button)
   - Tampilkan status koordinat GPS (icon)

---

## 💡 TIPS IMPLEMENTASI:

### Cara Apply Patch:
```bash
1. Buka file dengan code editor (VS Code, Sublime, Notepad++)
2. Gunakan Ctrl+F (Find) untuk cari kode yang perlu diganti
3. Copy kode baru dari file PATCH_xxx.txt
4. Replace dengan kode baru
5. Save file (Ctrl+S)
6. Refresh browser (F5)
```

### Jika Ada Error:
```bash
1. Restore backup:
   - servis-reguler-jemput.php.backup → servis-reguler-jemput.php

2. Cek browser Console (F12) untuk error JavaScript

3. Cek koordinat:
   - Cabang: lat_cabang, long_cabang harus terisi
   - Pelanggan: klat, klong harus terisi

4. Cek database:
   - Field kondisi_motor, jarak_jemput, tarif_jemput sudah ada?
```

---

## 🎓 CARA KERJA SISTEM:

### Flow Perhitungan Jarak:

```
1. User pilih nomor polisi
   ↓
2. Auto-fill data pelanggan (termasuk koordinat GPS)
   ↓
3. JavaScript ambil koordinat cabang (dari PHP)
   ↓
4. Hitung jarak dengan Haversine Formula
   ↓
5. Tampilkan jarak di form (auto-fill)
   ↓
6. User pilih kondisi motor (Jalan/Mogok)
   ↓
7. Klik "Hitung" → Tarif otomatis muncul
   ↓
8. Klik "Jadwalkan" → Data tersimpan
```

### Formula Haversine:
```javascript
// JavaScript sudah ada di: assets/js/haversine-distance.js
const R = 6371; // Radius bumi dalam KM
const dLat = toRad(lat2 - lat1);
const dLon = toRad(lon2 - lon1);
const a = sin²(dLat/2) + cos(lat1) × cos(lat2) × sin²(dLon/2);
const c = 2 × atan2(√a, √(1−a));
const distance = R × c;
```

### Formula Tarif:
```javascript
// Motor Jalan
if (jarak > 1.0) {
  if (jarak >= 1.5) {
    tarif = 8000 + (kelipatan_0.5km × 2000);
  }
}

// Motor Mogok
if (jarak > 1.0) {
  if (jarak >= 1.5) {
    tarif = 11000 + (kelipatan_0.5km × 3000);
  }
}
```

---

## 📊 EXAMPLE OUTPUT:

### Sebelum Patch:
```
Master Cabang:
┌────┬──────┬──────────────┬────────┬──────┐
│ No │ Kode │ Nama Cabang  │ Tipe   │ Aksi │
├────┼──────┼──────────────┼────────┼──────┤
│ 1  │ PST  │ Bengkel Pusat│ Pusat  │ Edit │
└────┴──────┴──────────────┴────────┴──────┘
```

### Setelah Patch:
```
Master Cabang:
┌────┬──────┬──────────────┬─────────────────┬────────┬──────┬──────┬──────┐
│ No │ Kode │ Nama Cabang  │ Alamat          │ Tipe   │ Maps │ GPS  │ Aksi │
├────┼──────┼──────────────┼─────────────────┼────────┼──────┼──────┼──────┤
│ 1  │ PST  │ Bengkel Pusat│ Jl. Industri... │ Pusat  │[Maps]│ ✓GPS │ Edit │
└────┴──────┴──────────────┴─────────────────┴────────┴──────┴──────┴──────┘
```

---

## 🔐 KEAMANAN:

✅ Prepared Statements (mysqli)
✅ Input sanitization (mysqli_real_escape_string)
✅ XSS prevention (htmlspecialchars)
✅ File upload validation
✅ Coordinate validation (numeric check)

---

## 📞 SUPPORT:

### Dokumentasi Lengkap:
- **ROOT:** `C:\xampp\htdocs\web-bengkel\`
  - `QUICK_START_GUIDE.txt` - Quick reference
  - `README_FITUR_BARU.md` - Overview lengkap
  - `IMPLEMENTASI_PERHITUNGAN_JARAK_OTOMATIS.md` - Step-by-step detail
  - `UPDATE_DATABASE_JARAK_OTOMATIS.sql` - Database schema

### File di Folder Ini:
- `PATCH_servis-reguler-jemput.txt` - Panduan patch servis
- `PATCH_cabang.php.txt` - Panduan patch cabang
- `README_IMPLEMENTASI.md` - File ini

---

## 🎉 SELESAI!

**Status:** ✅ Ready to Implement
**Estimasi:** 3-5 menit
**Kesulitan:** ⭐⭐☆☆☆ (Easy)

Semua file sudah siap, tinggal apply patch dan testing! 🚀

---

**Last Updated:** 2025-10-06
**Version:** 1.0
**Developer:** Claude Code
