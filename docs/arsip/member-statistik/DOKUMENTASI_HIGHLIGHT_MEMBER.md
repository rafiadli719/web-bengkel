# 🎨 Dokumentasi Fitur Highlight Kategori Member

## 📋 Deskripsi
Fitur ini menambahkan **highlight warna otomatis** pada baris data pelanggan di halaman input servis berdasarkan **kategori member** (Bronze, Silver, Gold, Platinum). Setiap kategori memiliki warna yang dapat dikustomisasi melalui halaman setting.

---

## ✨ Fitur Utama

### 1. **Highlight Otomatis Berdasarkan Kategori**
- Setiap baris pelanggan di tabel input servis akan memiliki warna background berbeda
- Warna disesuaikan dengan kategori member: Bronze, Silver, Gold, Platinum
- Highlight memudahkan identifikasi visual kategori pelanggan

### 2. **Halaman Setting Kustomisasi**
- Admin dapat mengatur warna untuk setiap kategori
- Setting meliputi:
  - **Background Color** - Warna latar belakang baris
  - **Text Color** - Warna teks
  - **Border Color** - Warna border kiri
  - **Border Width** - Ketebalan border (0-10px)
  - **Opacity** - Transparansi (0.0-1.0)
  - **Text Bold** - Teks tebal atau normal
  - **Status Aktif** - Aktifkan/nonaktifkan highlight

### 3. **Preview Real-time**
- Setiap perubahan setting langsung terlihat di preview box
- Memudahkan penyesuaian warna sebelum disimpan

### 4. **Legend Kategori**
- Tampilan legend di atas tabel untuk referensi warna
- Tombol akses cepat ke halaman setting

---

## 📦 File yang Dibuat/Dimodifikasi

### 1. **Database Update**
📄 `database_update_highlight_member.sql`
```sql
- Tabel: setting_highlight_member
- Kolom: kategori_member, background_color, text_color, border_color, 
         border_width, is_bold, opacity, is_active
- Data default untuk 4 kategori member
```

### 2. **Halaman Input Servis (Modified)**
📄 `aplikasi/aplikasi/_admincab/servis-carinopol.php`

**Perubahan:**
- ✅ Load setting highlight dari database
- ✅ Generate CSS dinamis untuk setiap kategori
- ✅ Tambah class highlight pada baris tabel
- ✅ Tampilkan legend kategori member
- ✅ Tombol akses ke halaman setting

### 3. **Halaman Setting (New)**
📄 `aplikasi/aplikasi/_admincab/setting-highlight-member.php`

**Fitur:**
- ✅ Form setting untuk setiap kategori
- ✅ Color picker untuk warna
- ✅ Preview real-time
- ✅ Simpan per kategori
- ✅ Reset semua ke default
- ✅ Validasi input

---

## 🚀 Cara Instalasi

### Step 1: Import Database
```bash
1. Buka phpMyAdmin
2. Pilih database: fitmotor_dbbengkel
3. Import file: database_update_highlight_member.sql
4. Pastikan tabel 'setting_highlight_member' berhasil dibuat
```

### Step 2: Upload File PHP
```bash
1. Upload file servis-carinopol.php (replace existing)
   Lokasi: aplikasi/aplikasi/_admincab/servis-carinopol.php

2. Upload file setting-highlight-member.php (new file)
   Lokasi: aplikasi/aplikasi/_admincab/setting-highlight-member.php
```

### Step 3: Test Fitur
```bash
1. Login ke sistem
2. Buka menu: Servis Reguler > Input Servis
3. Lihat highlight warna pada baris pelanggan
4. Klik tombol "Atur Highlight" untuk setting
```

---

## 🎨 Default Color Scheme

### Bronze 🥉
- **Background**: `#FFF8DC` (Cornsilk)
- **Text**: `#8B4513` (Saddle Brown)
- **Border**: `#CD7F32` (Bronze) - 2px
- **Opacity**: 0.30
- **Bold**: No

### Silver 🥈
- **Background**: `#F5F5F5` (White Smoke)
- **Text**: `#4A4A4A` (Dark Gray)
- **Border**: `#C0C0C0` (Silver) - 2px
- **Opacity**: 0.40
- **Bold**: No

### Gold 🥇
- **Background**: `#FFFACD` (Lemon Chiffon)
- **Text**: `#B8860B` (Dark Goldenrod)
- **Border**: `#FFD700` (Gold) - 3px
- **Opacity**: 0.50
- **Bold**: Yes

### Platinum 💎
- **Background**: `#F0F8FF` (Alice Blue)
- **Text**: `#2F4F4F` (Dark Slate Gray)
- **Border**: `#E5E4E2` (Platinum) - 3px
- **Opacity**: 0.60
- **Bold**: Yes

---

## 💡 Cara Penggunaan

### Untuk User (CS/Kasir)
1. Buka halaman **Input Servis**
2. Lihat **legend kategori** di atas tabel
3. Baris pelanggan akan memiliki warna sesuai kategori:
   - **Bronze** = Warna cream/coklat muda
   - **Silver** = Warna abu-abu terang
   - **Gold** = Warna kuning terang + Bold
   - **Platinum** = Warna biru muda + Bold
4. Hover mouse ke baris untuk efek highlight penuh

### Untuk Admin
1. Klik tombol **"Atur Highlight"** di halaman Input Servis
2. Atau akses: `setting-highlight-member.php`
3. Sesuaikan warna untuk setiap kategori:
   - Pilih warna menggunakan color picker
   - Atur border width dan opacity
   - Centang "Text Bold" jika ingin teks tebal
   - Lihat preview real-time
4. Klik **"Simpan Setting"** untuk menyimpan
5. Klik **"Reset Semua ke Default"** untuk kembalikan ke warna default

---

## 🔧 Kustomisasi Lanjutan

### Menambah Kategori Baru
Jika ingin menambah kategori member baru:

1. **Update Enum di Database**
```sql
ALTER TABLE setting_highlight_member 
MODIFY kategori_member ENUM('Bronze','Silver','Gold','Platinum','Diamond') NOT NULL;
```

2. **Insert Data Kategori Baru**
```sql
INSERT INTO setting_highlight_member 
(kategori_member, background_color, text_color, border_color, border_width, is_bold, opacity, is_active) 
VALUES
('Diamond', '#E0FFFF', '#000080', '#B9F2FF', 4, 1, 0.70, 1);
```

3. **Update Icon di PHP**
```php
$icons = [
    'Bronze' => '🥉', 
    'Silver' => '🥈', 
    'Gold' => '🥇', 
    'Platinum' => '💎',
    'Diamond' => '💠'  // Tambahkan ini
];
```

### Mengubah Efek Hover
Edit di `servis-carinopol.php`:
```css
.member-highlight-gold:hover {
    opacity: 1 !important;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.5); /* Ganti warna shadow */
    transform: scale(1.02); /* Tambah efek zoom */
}
```

---

## 📊 Struktur Database

### Tabel: `setting_highlight_member`
```sql
+-------------------+------------------------------------------+
| Kolom             | Tipe Data                                |
+-------------------+------------------------------------------+
| id                | int(11) AUTO_INCREMENT PRIMARY KEY       |
| kategori_member   | enum('Bronze','Silver','Gold','Platinum')|
| background_color  | varchar(7) - Format HEX (#FFFFFF)        |
| text_color        | varchar(7) - Format HEX (#000000)        |
| border_color      | varchar(7) - Format HEX (#FF0000)        |
| border_width      | int(11) - Dalam pixel (0-10)            |
| is_bold           | tinyint(1) - 1=Bold, 0=Normal           |
| opacity           | decimal(3,2) - 0.00 sampai 1.00         |
| is_active         | tinyint(1) - 1=Aktif, 0=Nonaktif        |
| created_at        | timestamp                                |
| updated_at        | timestamp                                |
+-------------------+------------------------------------------+
```

---

## 🐛 Troubleshooting

### Problem 1: Highlight Tidak Muncul
**Solusi:**
1. Pastikan tabel `setting_highlight_member` sudah dibuat
2. Cek apakah data default sudah ter-insert
3. Pastikan kolom `is_active` = 1
4. Clear browser cache (Ctrl + F5)

### Problem 2: Warna Tidak Berubah Setelah Setting
**Solusi:**
1. Pastikan klik tombol "Simpan Setting"
2. Refresh halaman input servis (F5)
3. Cek query UPDATE berhasil di database

### Problem 3: Preview Tidak Real-time
**Solusi:**
1. Pastikan JavaScript enabled di browser
2. Cek console browser untuk error (F12)
3. Pastikan fungsi `updatePreview()` tidak error

### Problem 4: Legend Tidak Tampil
**Solusi:**
1. Pastikan query `$highlight_settings` berhasil load
2. Cek apakah ada data di tabel `setting_highlight_member`
3. Pastikan foreach loop berjalan

---

## 📸 Screenshot Fitur

### Halaman Input Servis dengan Highlight
```
┌─────────────────────────────────────────────────────────┐
│ Legend: 🥉 BRONZE  🥈 SILVER  🥇 GOLD  💎 PLATINUM     │
│                                    [Atur Highlight]     │
├─────────────────────────────────────────────────────────┤
│ Aksi │ No.Polisi │ Nama      │ Kategori               │
├─────────────────────────────────────────────────────────┤
│ [▼]  │ B 1234 AB │ Budi      │ 🥉 BRONZE   ← Cream   │
│ [▼]  │ B 5678 CD │ Siti      │ 🥈 SILVER   ← Gray    │
│ [▼]  │ B 9012 EF │ Andi      │ 🥇 GOLD     ← Yellow  │
│ [▼]  │ B 3456 GH │ Dewi      │ 💎 PLATINUM ← Blue    │
└─────────────────────────────────────────────────────────┘
```

### Halaman Setting Highlight
```
┌─────────────────────────────────────────────────────────┐
│ 🥉 Kategori Bronze                                      │
├─────────────────────────────────────────────────────────┤
│ Background Color: [#FFF8DC] 🎨                         │
│ Text Color:       [#8B4513] 🎨                         │
│ Border Color:     [#CD7F32] 🎨                         │
│ Border Width:     [2] px                                │
│ Opacity:          [0.30]                                │
│ ☐ Text Bold       ☑ Aktif                              │
├─────────────────────────────────────────────────────────┤
│ Preview: Contoh tampilan baris pelanggan Bronze 🥉     │
├─────────────────────────────────────────────────────────┤
│ [Simpan Setting]                                        │
└─────────────────────────────────────────────────────────┘
```

---

## ⚡ Performance Tips

1. **Gunakan Opacity Rendah** (0.2-0.5) untuk highlight yang tidak mengganggu
2. **Batasi Border Width** maksimal 3-4px untuk tampilan optimal
3. **Pilih Warna Kontras** antara background dan text untuk readability
4. **Aktifkan Bold** hanya untuk kategori premium (Gold, Platinum)

---

## 🔐 Security Notes

1. Setting highlight hanya bisa diakses oleh user dengan level akses tertentu
2. Input warna divalidasi format HEX (#RRGGBB)
3. SQL Injection protected dengan `mysqli_real_escape_string()`
4. XSS protected dengan `htmlspecialchars()`

---

## 📝 Changelog

### Version 1.0 (6 November 2025)
- ✅ Initial release
- ✅ 4 kategori member (Bronze, Silver, Gold, Platinum)
- ✅ Halaman setting dengan preview real-time
- ✅ Legend kategori di halaman input servis
- ✅ Reset to default functionality
- ✅ Hover effect untuk highlight

---

## 👨‍💻 Developer Notes

### CSS Classes Generated
```css
.member-highlight-bronze { /* Dynamic from DB */ }
.member-highlight-silver { /* Dynamic from DB */ }
.member-highlight-gold   { /* Dynamic from DB */ }
.member-highlight-platinum { /* Dynamic from DB */ }
```

### PHP Functions
- `updatePreview(kategori)` - JavaScript untuk update preview real-time
- Query highlight settings di awal page load
- Dynamic CSS generation dari database

---

## 📞 Support

Jika ada pertanyaan atau issue:
1. Cek dokumentasi ini terlebih dahulu
2. Lihat section Troubleshooting
3. Cek log error di browser console (F12)
4. Hubungi developer untuk bantuan lebih lanjut

---

## 🎯 Roadmap Future Enhancement

- [ ] Export/Import setting highlight
- [ ] Preset color themes (Dark, Light, Colorful)
- [ ] Gradient background support
- [ ] Animation effects (fade, slide)
- [ ] Mobile responsive optimization
- [ ] Multi-language support

---

**Dibuat pada:** 6 November 2025  
**Versi:** 1.0  
**Status:** Production Ready ✅
