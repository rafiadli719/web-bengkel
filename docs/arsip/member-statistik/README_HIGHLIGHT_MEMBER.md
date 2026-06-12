# 🎨 Fitur Highlight Kategori Member - Quick Start

## 📦 Instalasi Cepat (3 Langkah)

### 1️⃣ Import Database
```bash
phpMyAdmin → fitmotor_dbbengkel → Import → database_update_highlight_member.sql
```

### 2️⃣ File Sudah Siap
✅ `servis-carinopol.php` - Sudah diupdate dengan highlight  
✅ `setting-highlight-member.php` - Halaman setting baru

### 3️⃣ Test Fitur
```
Login → Servis Reguler → Input Servis
Lihat highlight warna pada baris pelanggan!
```

---

## 🎯 Apa yang Baru?

### ✨ Highlight Otomatis
Setiap baris pelanggan di tabel input servis akan memiliki warna berbeda berdasarkan kategori member:

| Kategori | Warna | Icon |
|----------|-------|------|
| Bronze | 🟡 Cream | 🥉 |
| Silver | ⚪ Gray | 🥈 |
| Gold | 🟨 Yellow + Bold | 🥇 |
| Platinum | 🔵 Blue + Bold | 💎 |

### ⚙️ Halaman Setting
Akses: **Input Servis → Tombol "Atur Highlight"**

Atur untuk setiap kategori:
- 🎨 Background Color
- 📝 Text Color  
- 🔲 Border Color & Width
- 👁️ Opacity (Transparansi)
- **B** Text Bold
- ✅ Status Aktif

### 🔄 Preview Real-time
Setiap perubahan langsung terlihat di preview box sebelum disimpan!

---

## 📸 Preview

### Sebelum:
```
┌──────────────────────────────────┐
│ B 1234 AB │ Budi    │ Bronze    │ ← Semua sama
│ B 5678 CD │ Siti    │ Silver    │ ← Semua sama
│ B 9012 EF │ Andi    │ Gold      │ ← Semua sama
└──────────────────────────────────┘
```

### Sesudah:
```
┌──────────────────────────────────┐
│ B 1234 AB │ Budi    │ 🥉 Bronze    │ ← Cream background
│ B 5678 CD │ Siti    │ 🥈 Silver    │ ← Gray background
│ B 9012 EF │ Andi    │ 🥇 Gold      │ ← Yellow + Bold
│ B 3456 GH │ Dewi    │ 💎 Platinum  │ ← Blue + Bold
└──────────────────────────────────┘
```

---

## 🚀 Cara Pakai

### Untuk CS/Kasir:
1. Buka **Input Servis**
2. Lihat legend di atas tabel
3. Baris pelanggan otomatis berwarna sesuai kategori
4. Hover untuk highlight penuh

### Untuk Admin:
1. Klik **"Atur Highlight"**
2. Pilih warna dengan color picker
3. Lihat preview real-time
4. Klik **"Simpan Setting"**
5. Done! ✅

---

## 🔧 Reset ke Default
Jika warna tidak sesuai, klik tombol **"Reset Semua ke Default"** di halaman setting.

---

## ❓ Troubleshooting

**Highlight tidak muncul?**
- ✅ Cek tabel `setting_highlight_member` sudah dibuat
- ✅ Refresh browser (Ctrl + F5)

**Warna tidak berubah?**
- ✅ Pastikan klik "Simpan Setting"
- ✅ Refresh halaman input servis

---

## 📚 Dokumentasi Lengkap
Lihat: `DOKUMENTASI_HIGHLIGHT_MEMBER.md`

---

## ✅ Checklist Instalasi

- [ ] Import `database_update_highlight_member.sql`
- [ ] Cek tabel `setting_highlight_member` ada di database
- [ ] Upload `servis-carinopol.php` (replace)
- [ ] Upload `setting-highlight-member.php` (new)
- [ ] Test buka halaman Input Servis
- [ ] Test buka halaman Setting Highlight
- [ ] Test ubah warna dan simpan
- [ ] Test reset ke default

---

**Status:** ✅ Production Ready  
**Versi:** 1.0  
**Tanggal:** 6 November 2025
