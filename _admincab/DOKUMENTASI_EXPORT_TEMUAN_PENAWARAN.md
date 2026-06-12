# DOKUMENTASI FITUR EXPORT TEMUAN & PENAWARAN PART

## 📋 DESKRIPSI

Fitur export data Temuan & Penawaran Part memungkinkan user untuk mendownload data dalam format **Excel (.xls)** atau **PDF** untuk analisa, reporting, atau dokumentasi.

---

## 📁 FILE TERKAIT

| File | Lokasi | Fungsi |
|------|--------|--------|
| **temuan-penawaran-export.php** | `_admincab/temuan-penawaran-export.php` | Script utama untuk export data |
| **tab-temuan-penawaran-content-improved.php** | `_admincab/_template/tab-temuan-penawaran-content-improved.php` | Template UI dengan tombol download |
| **dompdf/** | `_admincab/dompdf/` | Library untuk generate PDF |

---

## 🎯 FITUR EXPORT

### 1. **Format Export**
- ✅ **Excel (.xls)** - Format Microsoft Excel dengan formatting warna
- ✅ **PDF** - Format PDF dengan dompdf library (landscape, A4)

### 2. **Tipe Data yang Bisa Diexport**

#### A. **Temuan Hasil Pengecekan**
Data yang termasuk:
- No. Service
- Tanggal Service
- Nama Temuan (dari master atau custom)
- Kode Temuan
- Kategori Temuan
- Jenis Perbaikan (Setting/Penggantian Part)
- Tingkat Urgensi (Rendah/Sedang/Tinggi/Kritis)
- Status Temuan
- Estimasi Biaya & Biaya Aktual
- Mekanik yang menemukan
- Keluhan terkait
- Deskripsi detail
- Keterangan jika tidak selesai
- User & Timestamp

#### B. **Penawaran Part**
Data yang termasuk:
- No. Service
- Tanggal Service
- Nama Pelanggan
- Temuan terkait (atau "Umum" jika tidak ada)
- Kode & Nama Part
- Quantity
- Harga Satuan & Total Harga
- Status (Pending/Disetujui/Ditolak)
- Alasan penolakan (jika ditolak)
- Keterangan tolak
- User Penawaran & User Respon
- Tanggal penawaran & respon
- Sumber (Auto-Suggest / Manual)

### 3. **Filter yang Tersedia**

#### Filter by Type:
- **All** - Export semua data (temuan + penawaran)
- **Temuan** - Export temuan saja
- **Penawaran** - Export penawaran saja

#### Filter by Status (khusus penawaran):
- **Pending** - Penawaran yang menunggu approval
- **Disetujui** - Penawaran yang sudah disetujui
- **Ditolak** - Penawaran yang ditolak

#### Filter by Service:
- Otomatis filter berdasarkan `no_service` dari halaman servis-input-reguler.php

---

## 🔗 URL ENDPOINT & PARAMETER

### **Base URL:**
```
_admincab/temuan-penawaran-export.php
```

### **Parameter:**

| Parameter | Type | Values | Default | Deskripsi |
|-----------|------|--------|---------|-----------|
| `format` | string | `excel`, `pdf` | `excel` | Format file download |
| `no_service` | string | - | *(empty)* | Filter by nomor service |
| `type` | string | `all`, `temuan`, `penawaran` | `all` | Jenis data yang diexport |
| `status` | string | `pending`, `disetujui`, `ditolak` | *(empty)* | Filter status penawaran |

### **Contoh URL:**

```
# Export semua data (temuan + penawaran) dalam Excel
temuan-penawaran-export.php?format=excel&type=all&no_service=SV25000000123

# Export penawaran pending saja dalam PDF
temuan-penawaran-export.php?format=pdf&type=penawaran&status=pending&no_service=SV25000000123

# Export temuan saja dalam PDF
temuan-penawaran-export.php?format=pdf&type=temuan&no_service=SV25000000123
```

---

## 🎨 TAMPILAN UI

### **Tombol Download di Tab Temuan & Penawaran**

Tombol download muncul di atas **Statistics Summary** dengan design:
- Background gradient ungu (modern)
- Dropdown menu dengan 11 pilihan export
- Icon & color coding untuk setiap menu item

### **Preview Dropdown Menu:**

```
┌─────────────────────────────────────────────┐
│ 📊 Format Excel (.xls)                      │
├─────────────────────────────────────────────┤
│ • Temuan & Penawaran (Excel)                │
│ • Temuan Saja (Excel)                       │
│ • Penawaran Saja (Excel)                    │
├─────────────────────────────────────────────┤
│ 📄 Format PDF (.pdf)                        │
├─────────────────────────────────────────────┤
│ • Temuan & Penawaran (PDF)                  │
│ • Temuan Saja (PDF)                         │
│ • Penawaran Saja (PDF)                      │
├─────────────────────────────────────────────┤
│ 🔍 Filter Status Penawaran                  │
├─────────────────────────────────────────────┤
│ • Penawaran Pending (Excel)                 │
│ • Penawaran Disetujui (Excel)               │
│ • Penawaran Ditolak (Excel)                 │
└─────────────────────────────────────────────┘
```

---

## 📊 FORMAT OUTPUT

### **A. Excel Format (.xls)**

**1. Section Temuan:**
- Header berwarna biru (#3498db)
- Border pada setiap cell
- Alignment yang rapih (right align untuk angka)
- Auto-format number (Rp 1.000.000)

**2. Section Penawaran:**
- Header berwarna hijau (#27ae60)
- Color coding per row:
  - **Pending** = Background kuning (#fff3cd)
  - **Disetujui** = Background hijau muda (#d4edda)
  - **Ditolak** = Background merah muda (#f8d7da)
- Summary row di footer dengan total nilai

**3. Footer:**
- Timestamp export
- User yang melakukan export

### **B. PDF Format**

**1. Layout:**
- Orientation: **Landscape**
- Paper Size: **A4**
- Font: **DejaVu Sans** (support karakter Indonesia)
- Font Size: 10pt (body), 9pt (table)

**2. Design:**
- Header dengan judul besar "LAPORAN TEMUAN & PENAWARAN PART"
- Border bawah biru pada judul
- Meta info: Tanggal export, User, No. Service
- Section headers berwarna (Biru untuk Temuan, Hijau untuk Penawaran)

**3. Table Styling:**
- Border pada setiap cell
- Zebra striping (row genap background abu-abu muda)
- Color coding status penawaran:
  - **Pending** = Background kuning
  - **Disetujui** = Background hijau muda
  - **Ditolak** = Background merah muda
- Summary row dengan background biru & teks putih

**4. Features:**
- Auto page break
- Professional formatting
- Clean & readable
- Ready to print

---

## 💡 USE CASES

### **1. Reporting untuk Management**
Export data penawaran yang **disetujui** dalam **PDF** untuk:
- Presentasi ke management
- Dokumentasi formal
- File yang tidak bisa diedit

### **2. Follow-up Customer**
Export penawaran **pending** dalam **Excel** untuk:
- Reminder approval ke customer
- Data yang bisa diolah lebih lanjut
- Import ke CRM

### **3. Analisa Rejection**
Export penawaran **ditolak** dalam **Excel** untuk:
- Analisa di spreadsheet
- Create pivot table
- Visualisasi data

### **4. Audit & Documentation**
Export **all data** dalam **PDF** untuk:
- Dokumentasi lengkap per service
- Archive file
- Legal compliance

### **5. Presentasi & Meeting**
Export dalam **PDF** untuk:
- Presentasi ke stakeholder
- Meeting review
- Professional appearance

---

## 🔒 KEAMANAN

### **Session Validation:**
```php
session_start();
if(empty($_SESSION['_iduser'])){
    header("location:../index.php");
    exit;
}
```

### **SQL Injection Prevention:**
- Parameter `no_service` di-escape menggunakan single quotes
- Filter status menggunakan exact match
- Tidak ada raw input langsung ke query

### **XSS Prevention:**
- `htmlspecialchars()` untuk output HTML (Excel & PDF)
- No script injection di output
- Clean HTML for dompdf

---

## 📝 CARA PENGGUNAAN

### **Step by Step:**

1. **Buka halaman Input Servis Reguler**
   - URL: `servis-input-reguler.php?snoserv=SV25000000123`

2. **Klik tab "Temuan & Penawaran"**
   - Tab akan menampilkan semua temuan dan penawaran

3. **Cari tombol "Download Semua Data"**
   - Lokasi: Di atas statistics summary
   - Background ungu gradient
   - Icon download

4. **Klik tombol, pilih format & tipe data**
   - Pilih **Excel** untuk data yang bisa diolah
   - Pilih **PDF** untuk dokumen formal/presentasi
   - Pilih tipe: All / Temuan / Penawaran
   - Atau filter by status

5. **File otomatis terdownload**
   - Excel: `temuan_penawaran_[NO_SERVICE]_[TIMESTAMP].xls`
   - PDF: `temuan_penawaran_[NO_SERVICE]_[TIMESTAMP].pdf`

---

## 🎯 KAPAN PAKAI EXCEL vs PDF?

### **Gunakan EXCEL jika:**
- ✅ Perlu analisa lebih lanjut (pivot, chart, formula)
- ✅ Data akan diedit/disesuaikan
- ✅ Import ke sistem lain (BI tools, CRM)
- ✅ Sharing ke tim untuk kolaborasi
- ✅ Butuh raw data untuk processing

### **Gunakan PDF jika:**
- ✅ Dokumentasi formal
- ✅ Presentasi ke management/customer
- ✅ Archive/backup yang tidak bisa diedit
- ✅ Print untuk meeting
- ✅ Email ke customer sebagai laporan resmi
- ✅ Legal compliance (audit trail)

---

## 🐛 TROUBLESHOOTING

### **Problem: PDF tidak bisa dibuka atau corrupt**

**Solusi:**
1. Pastikan library dompdf sudah terinstall di `_admincab/dompdf/`
2. Cek tidak ada output sebelum header (whitespace, echo, dll)
3. Pastikan tidak ada error PHP (cek error log)
4. Gunakan browser berbeda atau PDF reader terbaru
5. Download ulang dan buka dengan Adobe Reader

### **Problem: Font tidak support karakter Indonesia**

**Solusi:**
1. Dompdf sudah menggunakan DejaVu Sans yang support UTF-8
2. Jika masih ada masalah, cek encoding file PHP harus UTF-8
3. Font DejaVu Sans ada di `dompdf/lib/fonts/`

### **Problem: PDF terlalu panjang/banyak page**

**Solusi:**
1. Filter data dengan parameter (by service, by status)
2. Export per tipe (temuan saja atau penawaran saja)
3. Use landscape orientation (sudah default)
4. Reduce font size di CSS (sekarang 9pt)

### **Problem: File Excel corrupt atau tidak bisa dibuka**

**Solusi:**
1. Pastikan tidak ada output sebelum header (whitespace, echo, dll)
2. Clear browser cache dan download ulang
3. Buka dengan "Import Data" di Excel, pilih encoding UTF-8
4. Gunakan LibreOffice jika Excel error

### **Problem: Data tidak muncul di export**

**Solusi:**
1. Cek `no_service` di URL sudah benar
2. Cek data temuan/penawaran sudah ada di database
3. Cek session user masih aktif
4. Lihat error log di `accurate_debug.log`

### **Problem: Tombol tidak muncul**

**Solusi:**
1. Pastikan file `tab-temuan-penawaran-content-improved.php` sudah terupdate
2. Clear cache browser (Ctrl+F5)
3. Cek file include di `servis-input-reguler.php`

---

## 🔄 CHANGELOG

### **Version 2.0 (2025-12-05)**
- ✅ **BREAKING CHANGE:** Mengganti CSV dengan PDF
- ✅ PDF support dengan dompdf library
- ✅ Landscape orientation untuk PDF
- ✅ Professional table styling untuk PDF
- ✅ Color coding status di PDF
- ✅ Meta info header di PDF
- ✅ Summary row di PDF
- ✅ Support font Indonesia (DejaVu Sans)
- ✅ Auto page break
- ✅ Clean & readable PDF output

### **Version 1.0 (2025-12-05)**
- ✅ Initial release
- ✅ Support Excel & CSV format
- ✅ Filter by type (all/temuan/penawaran)
- ✅ Filter by status (pending/disetujui/ditolak)
- ✅ Color coding untuk Excel
- ✅ Summary row untuk penawaran
- ✅ Session validation
- ✅ SQL injection prevention
- ✅ XSS prevention
- ✅ Responsive UI dengan dropdown menu

---

## 📊 PERBANDINGAN FORMAT

| Feature | Excel | PDF |
|---------|-------|-----|
| **Editable** | ✅ Ya | ❌ Tidak |
| **Analisa Data** | ✅ Pivot, Formula | ❌ Read-only |
| **Professional Look** | ⚠️ Tergantung viewer | ✅ Konsisten |
| **Print Quality** | ⚠️ Perlu adjust | ✅ Ready to print |
| **File Size** | ⚠️ Bisa besar | ✅ Lebih kecil |
| **Security** | ❌ Bisa diedit | ✅ Read-only |
| **Share ke Customer** | ⚠️ Bisa diedit | ✅ Professional |
| **Import ke Sistem Lain** | ✅ Ya | ❌ Susah |
| **Archive/Backup** | ⚠️ Tergantung | ✅ Ideal |
| **Legal Compliance** | ⚠️ Bisa diubah | ✅ Tamper-proof |

---

## 📞 SUPPORT

Jika ada pertanyaan atau issue terkait fitur export:
1. Cek dokumentasi ini terlebih dahulu
2. Cek file `ANALISA_TEMUAN_PENAWARAN_SERVIS_REGULER.md` untuk context
3. Hubungi developer atau buat issue ticket

---

## 🎓 TIPS & BEST PRACTICES

### **Excel:**
- ✅ Gunakan untuk analisa internal
- ✅ Freeze top row untuk header
- ✅ Filter & sort untuk analisa
- ✅ Create pivot table untuk summary
- ✅ Save as .xlsx untuk feature lengkap

### **PDF:**
- ✅ Gunakan untuk dokumen eksternal
- ✅ Password protect jika data sensitif
- ✅ Compress jika file terlalu besar
- ✅ Gunakan PDF viewer terbaru
- ✅ Print landscape mode

---

**Created:** 2025-12-05
**Version:** 2.0
**Author:** FitMotor Development Team
**Status:** ✅ Ready to Use
**Format:** Excel (.xls) & PDF
**Library:** dompdf 0.8.x
