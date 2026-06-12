# CARA CLEAR CACHE BROWSER

File `master_posisi.php` sudah diupdate, tetapi browser masih menampilkan versi lama karena cache.

## 🔄 Solusi: Clear Browser Cache

### Opsi 1: Hard Refresh (Recommended)

**Windows/Linux:**
```
Ctrl + Shift + Delete
```

**macOS:**
```
Cmd + Shift + Delete
```

Atau:

**Windows/Linux:**
```
Ctrl + F5
```

**macOS:**
```
Cmd + Shift + R
```

### Opsi 2: Manual Clear Cache

#### Chrome/Edge:
1. Buka browser
2. Tekan `Ctrl + Shift + Delete`
3. Pilih "All time" untuk Time range
4. Centang "Cached images and files"
5. Klik "Clear data"
6. Refresh halaman

#### Firefox:
1. Buka browser
2. Tekan `Ctrl + Shift + Delete`
3. Pilih "Everything" untuk Time range
4. Klik "Clear Now"
5. Refresh halaman

#### Safari:
1. Buka Safari
2. Klik "Safari" → "Preferences"
3. Klik "Privacy"
4. Klik "Manage Website Data..."
5. Pilih website dan klik "Remove"
6. Refresh halaman

### Opsi 3: Incognito/Private Mode

Buka halaman di mode incognito/private untuk bypass cache:

**Chrome/Edge:**
```
Ctrl + Shift + N
```

**Firefox:**
```
Ctrl + Shift + P
```

**Safari:**
```
Cmd + Shift + N
```

Kemudian akses:
```
http://localhost/aplikasi/aplikasi/_admincab/master_posisi.php
```

---

## ✅ Verifikasi Perubahan

Setelah clear cache, halaman harus menampilkan:

1. **Navbar** - Logo dan user profile
2. **Sidebar** - Menu navigasi
3. **Breadcrumb** - Home > Master Posisi
4. **Filter Section** - Search box dan tombol Filter/Reset
5. **Data Table** - Tabel dengan 4 kolom:
   - No
   - Kode Posisi
   - Nama Posisi
   - Deskripsi

**TIDAK ada:**
- ❌ Form "Tambah Posisi Baru"
- ❌ Field input (Kode Posisi, Nama Posisi, dll)
- ❌ Tombol Simpan/Reset di form

---

## 🔍 Debug

Jika masih tidak berubah setelah clear cache:

### 1. Check file modification time
```
File: C:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\master_posisi.php
Last Modified: 15 November 2025 (recent)
```

### 2. Check browser console
1. Buka halaman
2. Tekan F12
3. Klik tab "Console"
4. Lihat apakah ada error

### 3. Check network
1. Buka halaman
2. Tekan F12
3. Klik tab "Network"
4. Refresh halaman
5. Cari request ke `master_posisi.php`
6. Lihat response size dan status

### 4. Restart Apache
```
1. Buka XAMPP Control Panel
2. Klik "Stop" untuk Apache
3. Tunggu 3 detik
4. Klik "Start" untuk Apache
5. Refresh halaman
```

---

## 📝 Checklist

- [ ] Clear browser cache (Ctrl+Shift+Delete)
- [ ] Refresh halaman (F5 atau Ctrl+R)
- [ ] Verifikasi template sudah berubah
- [ ] Tidak ada form "Tambah Posisi Baru"
- [ ] Tabel menampilkan data posisi dengan benar
- [ ] Filter berfungsi

---

**Last Updated:** 15 November 2025
