# Quick Guide - Implementasi Status Pengerjaan

## 🚀 Langkah Cepat (15 Menit per File)

### **STEP 0: Persiapan (Sekali Saja)**

1. **Run SQL untuk membuat views:**
```bash
mysql -u root -p fitmotor_dbbengkel < FIX_STATUS_PENGERJAAN_VIEWS.sql
```

2. **Verify views sudah dibuat:**
```sql
SHOW TABLES LIKE 'view_%';
```

---

### **STEP 1-5: Untuk Setiap File Input Servis**

#### **File yang perlu diupdate:**
1. `servis-input-reguler.php`
2. `servis-input-reguler-rst.php`
3. `servis-input-reguler-jemput.php`
4. `servis-input-reguler-jemput-rst.php`
5. `servis-garansi.php`

---

## 📝 Implementasi Per File

### **1. Update Tabel Keluhan**

**Cari bagian query keluhan (biasanya di tab Keluhan):**

```php
// CARI INI:
$query_keluhan = "SELECT * FROM tbservis_keluhan_status WHERE no_service='$no_service'";

// GANTI DENGAN:
$query_keluhan = "SELECT * FROM view_servis_keluhan_lengkap WHERE no_service='$no_service' ORDER BY created_at DESC";
```

**Ganti tabel keluhan dengan snippet:**
- Buka file `_snippet_tabel_keluhan_status.php`
- Copy semua isi file
- Ganti tabel keluhan yang lama dengan snippet ini

---

### **2. Update Tabel Work Order**

**Cari bagian query work order (biasanya di tab Work Order):**

```php
// CARI INI:
$query_wo = "SELECT wo.*, woh.nama_wo FROM tbservis_workorder wo LEFT JOIN tbworkorderheader woh...";

// GANTI DENGAN:
$query_wo = "SELECT * FROM view_servis_workorder_lengkap WHERE no_service='$no_service' ORDER BY created_at DESC";
```

**Ganti tabel work order dengan snippet:**
- Buka file `_snippet_tabel_workorder_status.php`
- Copy semua isi file
- Ganti tabel work order yang lama dengan snippet ini

---

### **3. Tambah Modals**

**Cari tag `</body>` atau bagian modals, lalu tambahkan:**
- Buka file `_snippet_modals_status.php`
- Copy semua isi file
- Paste sebelum `</body>` atau di bagian modals

---

### **4. Tambah JavaScript**

**Cari bagian `<script>` sebelum `</body>`, lalu tambahkan:**
- Buka file `_snippet_javascript_status.js`
- Copy semua isi file
- Paste di dalam tag `<script>` yang sudah ada

---

### **5. Tambah Handler PHP (Jika Belum Ada)**

**Cek apakah file sudah punya handler `btnupdatestatuswo`:**

```php
// Cari ini di file:
if(isset($_POST['btnupdatestatuswo']))
```

**Jika BELUM ADA:**
- Buka file `_snippet_handler_status_wo.php`
- Copy semua isi file
- Paste setelah handler keluhan (setelah `btnupdatestatuskeluhan`)

**File yang PERLU ditambah handler:**
- ✅ `servis-input-reguler.php` (PERLU)
- ❌ `servis-input-reguler-rst.php` (SUDAH ADA)
- ❌ `servis-input-reguler-jemput.php` (SUDAH ADA)
- ❌ `servis-input-reguler-jemput-rst.php` (SUDAH ADA)
- ✅ `servis-garansi.php` (PERLU)

---

## ✅ Checklist Per File

### **servis-input-reguler.php**
- [ ] Update query keluhan ke view
- [ ] Ganti tabel keluhan dengan snippet
- [ ] Update query work order ke view
- [ ] Ganti tabel work order dengan snippet
- [ ] Tambah modals
- [ ] Tambah JavaScript
- [ ] **Tambah handler status WO** ⭐
- [ ] Test semua fungsi

### **servis-input-reguler-rst.php**
- [ ] Update query keluhan ke view
- [ ] Ganti tabel keluhan dengan snippet
- [ ] Update query work order ke view
- [ ] Ganti tabel work order dengan snippet
- [ ] Tambah modals
- [ ] Tambah JavaScript
- [ ] ~~Tambah handler status WO~~ (sudah ada)
- [ ] Test semua fungsi

### **servis-input-reguler-jemput.php**
- [ ] Update query keluhan ke view
- [ ] Ganti tabel keluhan dengan snippet
- [ ] Update query work order ke view
- [ ] Ganti tabel work order dengan snippet
- [ ] Tambah modals
- [ ] Tambah JavaScript
- [ ] ~~Tambah handler status WO~~ (sudah ada)
- [ ] Test semua fungsi

### **servis-input-reguler-jemput-rst.php**
- [ ] Update query keluhan ke view
- [ ] Ganti tabel keluhan dengan snippet
- [ ] Update query work order ke view
- [ ] Ganti tabel work order dengan snippet
- [ ] Tambah modals
- [ ] Tambah JavaScript
- [ ] ~~Tambah handler status WO~~ (sudah ada)
- [ ] Test semua fungsi

### **servis-garansi.php**
- [ ] Update query keluhan ke view
- [ ] Ganti tabel keluhan dengan snippet
- [ ] Update query work order ke view
- [ ] Ganti tabel work order dengan snippet
- [ ] Tambah modals
- [ ] Tambah JavaScript
- [ ] **Tambah handler status WO** ⭐
- [ ] Test semua fungsi

---

## 🎯 Tips Implementasi

### **1. Mulai dari 1 File Dulu**
Implementasikan di `servis-input-reguler.php` terlebih dahulu, test sampai berhasil, baru copy ke file lainnya.

### **2. Backup File Sebelum Edit**
```bash
cp servis-input-reguler.php servis-input-reguler.php.backup
```

### **3. Cari dengan Ctrl+F**
Gunakan fitur search untuk menemukan bagian yang perlu diupdate:
- Search: `tbservis_keluhan_status` → untuk query keluhan
- Search: `tbservis_workorder` → untuk query work order
- Search: `</body>` → untuk tambah modals
- Search: `<script>` → untuk tambah JavaScript

### **4. Test Setiap Langkah**
Setelah update query, refresh halaman dan pastikan data masih muncul sebelum lanjut ke langkah berikutnya.

---

## 🐛 Troubleshooting

### **Error: Table 'view_servis_keluhan_lengkap' doesn't exist**
```bash
# Run SQL fix
mysql -u root -p fitmotor_dbbengkel < FIX_STATUS_PENGERJAAN_VIEWS.sql
```

### **Error: Undefined index 'status_badge_color'**
View belum di-create atau query belum diupdate ke view.

### **Modal tidak muncul**
- Check apakah jQuery sudah di-load
- Check console browser untuk JavaScript error
- Pastikan modal sudah ditambahkan sebelum `</body>`

### **Button update status tidak berfungsi**
- Check apakah JavaScript sudah ditambahkan
- Check apakah class button sudah benar: `btn-update-status-keluhan` atau `btn-update-status-wo`

### **Data tidak tersimpan saat update status**
- Check apakah handler PHP sudah ditambahkan
- Check apakah nama button submit sudah benar: `btnupdatestatuskeluhan` atau `btnupdatestatuswo`

---

## 📊 Hasil Akhir

Setelah implementasi selesai, Anda akan punya:

### **Tabel Keluhan dengan:**
- ✅ Kolom Status (dengan badge warna)
- ✅ Kolom Keterangan (untuk yang tidak selesai)
- ✅ Button Update Status
- ✅ Highlight row merah untuk yang tidak selesai

### **Tabel Work Order dengan:**
- ✅ Kolom Status (dengan badge warna)
- ✅ Kolom Progress (dengan progress bar)
- ✅ Kolom Keterangan (untuk yang tidak selesai)
- ✅ Button Update Status
- ✅ Highlight row merah untuk yang tidak selesai

### **Modal Update Status:**
- ✅ Modal untuk update status keluhan
- ✅ Modal untuk update status work order
- ✅ Auto show/hide field keterangan
- ✅ Validasi wajib isi keterangan jika tidak selesai

---

## 📁 File Reference

| File | Fungsi |
|------|--------|
| `FIX_STATUS_PENGERJAAN_VIEWS.sql` | SQL create views |
| `_snippet_tabel_keluhan_status.php` | Snippet tabel keluhan |
| `_snippet_tabel_workorder_status.php` | Snippet tabel work order |
| `_snippet_modals_status.php` | Snippet modals |
| `_snippet_javascript_status.js` | Snippet JavaScript |
| `_snippet_handler_status_wo.php` | Snippet handler PHP |
| `IMPLEMENTASI_UI_STATUS_SERVIS.md` | Dokumentasi lengkap |
| `QUICK_GUIDE_IMPLEMENTASI.md` | File ini |

---

## ⏱️ Estimasi Waktu

- **Persiapan (run SQL):** 2 menit
- **Per file implementasi:** 15 menit
- **Total untuk 5 files:** ~1.5 jam
- **Testing:** 30 menit

**Total:** ~2 jam

---

## 🎉 Selamat!

Setelah semua selesai, sistem Anda akan punya:
- ✅ Tracking status pengerjaan keluhan
- ✅ Tracking status pengerjaan work order
- ✅ Progress bar visual
- ✅ Keterangan otomatis untuk item tidak selesai
- ✅ UI yang informatif dan user-friendly

---

**Last Updated:** 2025-01-09  
**Version:** 1.0  
**Status:** Ready to Use
