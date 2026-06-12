# 🧪 CARA TEST MODAL STATISTIK PELANGGAN

## 📋 LANGKAH-LANGKAH TEST

### Step 1: Clear Cache Browser
```
1. Tekan Ctrl + Shift + Delete
2. Pilih "Cached images and files"
3. Klik "Clear data"
4. Tutup browser
5. Buka browser lagi
```

### Step 2: Akses Halaman Input Servis
```
1. Login ke aplikasi
2. Menu: Servis Reguler
3. Klik: Input Servis Reguler
```

### Step 3: Pilih Pelanggan
```
1. Cari nomor polisi (contoh: AD 1234 AB)
2. Klik nomor polisi yang muncul
3. Tunggu data pelanggan load
4. Pastikan nama pelanggan muncul
```

### Step 4: Buka Tab Detail Servis
```
1. Klik tab "Detail Servis" (tab ke-2)
2. Scroll ke bawah
3. Cari field "Statistik Pelanggan"
```

### Step 5: Cek Tombol
**YANG HARUS MUNCUL:**
```
┌─────────────────────────────────────────────┐
│ Statistik Pelanggan:                        │
│ ┌─────────────────────────────────────────┐ │
│ │ 📊 Lihat Statistik Pelanggan Lengkap ➡️ │ │
│ │ Kategori Member, Kendaraan, Riwayat...  │ │
│ └─────────────────────────────────────────┘ │
└─────────────────────────────────────────────┘
```

**JIKA TIDAK MUNCUL:**
- Berarti pelanggan belum dipilih
- Atau variabel `$kode_pelanggan` kosong

### Step 6: View Page Source
```
1. Klik kanan di halaman
2. Pilih "View Page Source" atau tekan Ctrl + U
3. Cari teks: "DEBUG: kode_pelanggan"
```

**YANG HARUS ADA:**
```html
<!-- DEBUG: kode_pelanggan = AD 1234 AB -->
<!-- DEBUG: Rendering modal untuk pelanggan: AD 1234 AB -->
<!-- DEBUG: Fungsi renderModalStatistikPelanggan DITEMUKAN -->
```

**JIKA TIDAK ADA:**
```html
<!-- DEBUG: kode_pelanggan kosong, modal tidak di-render -->
```
Berarti pelanggan belum dipilih dengan benar.

### Step 7: Cari Modal di Source
Di View Page Source, cari:
```html
<div class="modal fade" id="modalStatistikPelanggan"
```

**JIKA ADA:** Modal sudah ter-render ✅  
**JIKA TIDAK ADA:** Modal tidak ter-render ❌

### Step 8: Buka Console Browser
```
1. Tekan F12
2. Klik tab "Console"
```

**YANG HARUS MUNCUL:**
```
✅ Tombol Statistik Pelanggan ditemukan
✅ Modal Statistik Pelanggan ditemukan
```

**JIKA TIDAK MUNCUL:**
```
⚠️ Tombol Statistik Pelanggan TIDAK ditemukan
⚠️ Modal Statistik Pelanggan TIDAK ditemukan
```

### Step 9: Test jQuery
Di Console, ketik:
```javascript
typeof jQuery
```

**Harus muncul:** `"function"`  
**Jika muncul:** `"undefined"` → jQuery belum load

### Step 10: Test Bootstrap Modal
Di Console, ketik:
```javascript
typeof $.fn.modal
```

**Harus muncul:** `"function"`  
**Jika muncul:** `"undefined"` → Bootstrap JS belum load

### Step 11: Cek Tombol Ada
Di Console, ketik:
```javascript
$('button[data-target="#modalStatistikPelanggan"]').length
```

**Harus muncul:** Angka > 0 (contoh: 1)  
**Jika muncul:** 0 → Tombol tidak ada

### Step 12: Cek Modal Ada
Di Console, ketik:
```javascript
$('#modalStatistikPelanggan').length
```

**Harus muncul:** Angka > 0 (contoh: 1)  
**Jika muncul:** 0 → Modal tidak ada

### Step 13: Test Buka Modal Manual
Di Console, ketik:
```javascript
$('#modalStatistikPelanggan').modal('show');
```

**Jika modal muncul:** Modal OK, masalah di tombol  
**Jika modal TIDAK muncul:** Modal bermasalah

### Step 14: Klik Tombol
Klik tombol **"Lihat Statistik Pelanggan Lengkap"**

**YANG HARUS TERJADI:**
- Modal pop-up muncul
- Ada 6 section: Info, Member Nominal, Member Kunjungan, Benefit, Kendaraan, Riwayat
- Data pelanggan tampil lengkap

---

## 🔍 DEBUGGING BERDASARKAN HASIL

### Hasil 1: Tombol Tidak Muncul
**Penyebab:** Pelanggan belum dipilih atau `$kode_pelanggan` kosong

**Solusi:**
1. Pastikan sudah pilih nomor polisi
2. Cek nama pelanggan sudah muncul
3. Refresh halaman (Ctrl + F5)

---

### Hasil 2: Tombol Muncul, Tapi Tidak Bisa Diklik
**Penyebab:** JavaScript error atau Bootstrap belum load

**Solusi:**
1. Cek Console (F12) ada error?
2. Test: `typeof $.fn.modal` harus "function"
3. Refresh halaman (Ctrl + F5)

---

### Hasil 3: Klik Tombol, Modal Tidak Muncul
**Penyebab:** Modal HTML tidak ter-render

**Solusi:**
1. View Page Source → Cari "modalStatistikPelanggan"
2. Jika tidak ada → Modal tidak ter-render
3. Cek debug comment: "DEBUG: kode_pelanggan"
4. Test manual: `$('#modalStatistikPelanggan').modal('show');`

---

### Hasil 4: Modal Muncul Tapi Kosong
**Penyebab:** Data pelanggan tidak ada atau query error

**Solusi:**
1. Cek database: `SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB'`
2. Cek database: `SELECT * FROM tblpelanggan WHERE nopelanggan = 'AD 1234 AB'`
3. Cek error PHP di halaman

---

### Hasil 5: Error "$ is not defined"
**Penyebab:** jQuery belum load

**Solusi:**
1. Refresh halaman (Ctrl + F5)
2. Clear cache browser
3. Test: `typeof jQuery` harus "function"

---

## 📸 SCREENSHOT YANG DIBUTUHKAN

Jika masih error, kirim screenshot:

### 1. Screenshot Halaman Input Servis
- Tampilkan tab "Detail Servis"
- Tampilkan field "Statistik Pelanggan"
- Tampilkan apakah tombol muncul atau tidak

### 2. Screenshot View Page Source
- Cari "DEBUG: kode_pelanggan"
- Screenshot bagian tersebut

### 3. Screenshot Console (F12)
- Tab "Console"
- Tampilkan semua pesan error
- Tampilkan hasil test jQuery

### 4. Screenshot Network (F12)
- Tab "Network"
- Refresh halaman
- Tampilkan file yang di-load
- Cek apakah ada file yang error (merah)

---

## ✅ CHECKLIST LENGKAP

**Persiapan:**
- [ ] Clear cache browser
- [ ] Tutup & buka browser lagi
- [ ] Login ke aplikasi

**Test Halaman:**
- [ ] Buka halaman Input Servis Reguler
- [ ] Pilih nomor polisi pelanggan
- [ ] Nama pelanggan muncul
- [ ] Buka tab "Detail Servis"

**Test Tombol:**
- [ ] Field "Statistik Pelanggan" ada
- [ ] Tombol "Lihat Statistik Pelanggan Lengkap" muncul
- [ ] Tombol bisa diklik

**Test Source:**
- [ ] View Page Source (Ctrl + U)
- [ ] Cari "DEBUG: kode_pelanggan"
- [ ] Cari "modalStatistikPelanggan"
- [ ] Modal HTML ada di source

**Test Console:**
- [ ] Buka Console (F12)
- [ ] Tidak ada error merah
- [ ] Pesan debug muncul
- [ ] `typeof jQuery` = "function"
- [ ] `typeof $.fn.modal` = "function"

**Test Manual:**
- [ ] `$('button[data-target="#modalStatistikPelanggan"]').length` > 0
- [ ] `$('#modalStatistikPelanggan').length` > 0
- [ ] `$('#modalStatistikPelanggan').modal('show');` → Modal muncul

**Test Klik:**
- [ ] Klik tombol
- [ ] Modal muncul
- [ ] Data pelanggan tampil
- [ ] 6 section lengkap

---

## 🎯 HASIL YANG DIHARAPKAN

### ✅ Jika Berhasil:

**Di Halaman:**
- Tombol "Lihat Statistik Pelanggan Lengkap" muncul
- Tombol bisa diklik
- Modal pop-up muncul
- Data lengkap tampil

**Di Console:**
```
Tombol Statistik Pelanggan ditemukan
Modal Statistik Pelanggan ditemukan
Modal Statistik Pelanggan dibuka
Modal Statistik Pelanggan sudah tampil
```

**Di View Source:**
```html
<!-- DEBUG: kode_pelanggan = AD 1234 AB -->
<!-- DEBUG: Rendering modal untuk pelanggan: AD 1234 AB -->
<!-- DEBUG: Fungsi renderModalStatistikPelanggan DITEMUKAN -->
<div class="modal fade" id="modalStatistikPelanggan" ...>
```

---

## 🚨 TROUBLESHOOTING CEPAT

### Problem: "Tidak ada perubahan"
**Solusi:**
1. Ctrl + F5 (Hard Refresh)
2. Clear cache browser
3. Tutup & buka browser
4. Cek file sudah ter-upload ke server

### Problem: "Tombol tidak muncul"
**Solusi:**
1. Pastikan sudah pilih pelanggan
2. Cek View Source ada "DEBUG: kode_pelanggan"
3. Jika kosong → Pelanggan belum dipilih

### Problem: "Modal tidak muncul"
**Solusi:**
1. Test manual: `$('#modalStatistikPelanggan').modal('show');`
2. Jika muncul → Masalah di tombol
3. Jika tidak → Masalah di modal HTML

---

**Ikuti langkah-langkah di atas secara berurutan!**  
**Kirim screenshot jika masih error!**
