# 🧪 Testing Checklist - Modal Temuan & Fast Moves

## ✅ Pre-Testing Setup

- [x] SQL script `fix_modal_temuan_fastmoves.sql` sudah dijalankan
- [x] Database memiliki 10 data di `tbmaster_temuan`
- [x] Database memiliki 49 kategori di `tbmaster_kategori_fastmoves`
- [ ] File modal sudah di-update (modal-search-temuan.php, modal-fastmoves-v2.php)
- [ ] Browser cache sudah di-clear (Ctrl + Shift + Delete)

---

## 🔍 Test 1: Debug Tool (Optional)

**URL:** `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/test_modal_debug.html`

### Checklist:
- [ ] Halaman terbuka tanpa error
- [ ] Status jQuery: ✓ (hijau)
- [ ] Status Bootstrap: ✓ (hijau)
- [ ] Status Callbacks: ✓ (hijau)
- [ ] Status AJAX Handler: ✓ (hijau)
- [ ] Klik "Test AJAX Get Kategori" → berhasil dapat data
- [ ] Klik "Test Callback Functions" → form terisi

**Jika ada yang merah (✗):**
- jQuery/Bootstrap merah → Cek koneksi internet atau CDN
- Callbacks merah → Normal, karena callback didefinisikan di halaman utama
- AJAX Handler merah → Cek path file `_handler_temuan_penawaran.php`

---

## 🔍 Test 2: Modal Cari Temuan

**URL:** `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reguler.php?snoserv=SV2500000155`

### Langkah Testing:

#### 2.1 Buka Modal
- [ ] Klik tab "Temuan & Penawaran"
- [ ] Klik button "Tambah Temuan"
- [ ] Klik icon search (🔍) di field "Temuan"
- [ ] Modal "Cari Temuan" muncul
- [ ] Tabel menampilkan 10 data temuan

#### 2.2 Test Search
- [ ] Ketik "filter" di search box
- [ ] Hanya temuan dengan kata "filter" yang muncul
- [ ] Clear search → semua temuan muncul lagi

#### 2.3 Test Filter Kategori
- [ ] Pilih kategori "Mesin" di dropdown
- [ ] Hanya temuan kategori Mesin yang muncul
- [ ] Pilih "-- Semua Kategori --" → semua muncul lagi

#### 2.4 Test Pilih Temuan
- [ ] Klik button "Pilih" pada temuan "Filter Udara Kotor"
- [ ] **Buka Console Browser (F12)**
- [ ] Cek ada log: `Temuan dipilih: {kode: "TMN001", nama: "Filter Udara Kotor", ...}`
- [ ] Cek ada log: `Callback onTemuanSelected dipanggil`
- [ ] Modal tertutup otomatis
- [ ] Field "Temuan" terisi dengan "Filter Udara Kotor"
- [ ] Field hidden "kode_temuan" terisi dengan "TMN001"

#### 2.5 Test Input Manual
- [ ] Buka modal lagi
- [ ] Klik button "Input Manual"
- [ ] Modal "Input Temuan Manual" muncul
- [ ] Isi nama temuan: "Rantai Kendor"
- [ ] Pilih kategori: "Transmisi"
- [ ] Pilih urgensi: "Sedang"
- [ ] Klik "Simpan"
- [ ] Cek console: `Temuan manual dibuat: ...`
- [ ] Cek console: `Callback onTemuanManualCreated dipanggil`
- [ ] Field "Temuan" terisi dengan "Rantai Kendor"

### ❌ Jika Gagal:
**Gejala:** Klik "Pilih" tidak ada reaksi
- Buka Console (F12) → cek error
- Jika ada error "onTemuanSelected is not defined":
  - Pastikan file `tab-temuan-penawaran-content.php` sudah di-include
  - Pastikan script callback diload sebelum modal

**Gejala:** Data tidak masuk ke form
- Cek console → harus ada log "Callback dipanggil"
- Jika tidak ada log → callback tidak terpanggil
- Cek ID field: harus `id="kode_temuan"` dan `id="nama_temuan"`

---

## 🔍 Test 3: Modal Fast Moves

**URL:** `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-input-reguler.php?snoserv=SV2500000155`

### Langkah Testing:

#### 3.1 Buka Modal
- [ ] Klik tab "Temuan & Penawaran"
- [ ] Scroll ke section "Penawaran Part ke Customer"
- [ ] Klik button "Fast Moves" (⚡)
- [ ] Modal "Fast Moves - Pilih Part Cepat" muncul
- [ ] Terlihat 3 section kategori:
  - SERVIS RUTIN STANDAR (biru)
  - SERVIS CVT MATIC (kuning)
  - SERVIS BARANG LAIN (hijau)

#### 3.2 Test Klik Kategori
- [ ] **Buka Console Browser (F12)**
- [ ] Klik button "OLI MESIN"
- [ ] Cek console: `Kategori diklik: {kategori: "OLI", nama: "OLI MESIN"}`
- [ ] Cek console: `Loading parts untuk kategori: OLI`
- [ ] Section "Part untuk: OLI MESIN" muncul di bawah
- [ ] Tabel part muncul dengan loading spinner
- [ ] Setelah loading, tabel menampilkan daftar oli

**Jika tidak ada data:**
- [ ] Cek console: `Response dari server: []`
- [ ] Artinya: Tabel `tbmaster_barang_fastmoves` kosong
- [ ] Perlu mapping barang ke kategori (lihat section "Mapping Barang")

#### 3.3 Test Search Global
- [ ] Ketik "yamalube" di search box
- [ ] Tekan Enter
- [ ] Section "Hasil Pencarian: yamalube" muncul
- [ ] Tabel menampilkan part yang cocok

#### 3.4 Test Tambah Part
- [ ] Klik kategori "AKI"
- [ ] Pilih salah satu aki dari list
- [ ] Ubah quantity jadi 2
- [ ] Klik button "+" (hijau)
- [ ] Cek console: `Part dipilih: {kode: "AKI-001", nama: "...", qty: 2}`
- [ ] Cek console: `Callback onFastMovesPartSelected dipanggil`
- [ ] Button berubah jadi checkmark (✓) sebentar
- [ ] Counter "Total Dipilih" bertambah jadi 1
- [ ] Modal tetap terbuka
- [ ] Form "Tambah Penawaran" di bawah modal terbuka otomatis
- [ ] Field terisi:
  - Kode Part: AKI-001
  - Nama Part: (nama aki)
  - Qty: 2
  - Harga Satuan: (harga dari database)
  - Total: (qty × harga)

#### 3.5 Test Multiple Parts
- [ ] Klik kategori lain (misal: "BUSI")
- [ ] Tambah busi → counter jadi 2
- [ ] Klik kategori lain (misal: "FILTER UDARA")
- [ ] Tambah filter → counter jadi 3
- [ ] Semua part masuk ke form penawaran

#### 3.6 Test Close Modal
- [ ] Klik "Tutup" atau X
- [ ] Modal tertutup
- [ ] Counter reset jadi 0
- [ ] Search box kosong

### ❌ Jika Gagal:

**Gejala:** Klik kategori tidak ada reaksi
- Buka Console (F12)
- Jika ada error "Data kategori tidak lengkap":
  - Cek HTML button → harus ada `data-kategori` dan `data-nama`
- Jika tidak ada log sama sekali:
  - Event handler tidak terpasang
  - Pastikan file modal-fastmoves-v2.php sudah di-include
  - Pastikan jQuery sudah loaded

**Gejala:** AJAX error / tidak ada data
- Cek console → lihat error message
- Jika "Error loading data: ...":
  - Klik untuk lihat detail error
  - Cek response text di Network tab
- Jika response kosong `[]`:
  - Normal jika belum ada mapping barang
  - Perlu insert data ke `tbmaster_barang_fastmoves`

**Gejala:** Part tidak masuk ke form
- Cek console → harus ada log "Callback dipanggil"
- Jika ada error "onFastMovesPartSelected tidak ditemukan":
  - Callback belum didefinisikan
  - Pastikan `tab-temuan-penawaran-content.php` sudah di-include

---

## 🗄️ Mapping Barang ke Kategori (Jika Diperlukan)

Jika AJAX berhasil tapi tidak ada data, perlu mapping barang:

```sql
-- Contoh: Mapping oli ke kategori OLI
INSERT INTO tbmaster_barang_fastmoves 
(kode_kategori, kode_barang, is_featured, urutan)
VALUES
('OLI', 'OLI-001', 1, 1),
('OLI', 'OLI-002', 1, 2),
('OLI', 'OLI-003', 0, 3);

-- Contoh: Mapping aki ke kategori AKI
INSERT INTO tbmaster_barang_fastmoves 
(kode_kategori, kode_barang, is_featured, urutan)
VALUES
('AKI', 'AKI-001', 1, 1),
('AKI', 'AKI-002', 1, 2);

-- Cek hasil
SELECT 
    kfm.nama_kategori,
    mbf.kode_barang,
    mnb.nama_barang,
    mnb.harga_jual
FROM tbmaster_barang_fastmoves mbf
JOIN tbmaster_kategori_fastmoves kfm ON mbf.kode_kategori = kfm.kode_kategori
JOIN tbmaster_nama_barang mnb ON mbf.kode_barang = mnb.kode_barang
WHERE kfm.kode_kategori = 'OLI'
ORDER BY mbf.urutan;
```

---

## 📊 Summary Report

Setelah semua test selesai, isi summary:

### Modal Cari Temuan
- Status: [ ] ✅ Working | [ ] ⚠️ Partial | [ ] ❌ Failed
- Search: [ ] OK | [ ] Failed
- Filter: [ ] OK | [ ] Failed
- Pilih: [ ] OK | [ ] Failed
- Input Manual: [ ] OK | [ ] Failed
- Notes: _______________________

### Modal Fast Moves
- Status: [ ] ✅ Working | [ ] ⚠️ Partial | [ ] ❌ Failed
- Klik Kategori: [ ] OK | [ ] Failed
- AJAX Load: [ ] OK | [ ] Failed
- Search: [ ] OK | [ ] Failed
- Tambah Part: [ ] OK | [ ] Failed
- Callback: [ ] OK | [ ] Failed
- Notes: _______________________

### Issues Found
1. _______________________
2. _______________________
3. _______________________

---

## 🆘 Quick Fixes

### Fix 1: Clear Cache
```
Ctrl + Shift + Delete → Clear cached files → Reload (Ctrl + F5)
```

### Fix 2: Check File Includes
```php
// Di servis-input-reguler.php, pastikan ada:
<?php include '_template/tab-temuan-penawaran-content.php'; ?>
<?php include '_template/modal-search-temuan.php'; ?>
<?php include '_template/modal-fastmoves-v2.php'; ?>
```

### Fix 3: Check jQuery Order
```html
<!-- Urutan yang benar: -->
<script src="jquery.min.js"></script>
<script src="bootstrap.min.js"></script>
<!-- Baru kemudian modal files -->
```

### Fix 4: Restart Apache
```bash
# Jika ada perubahan PHP
# Restart XAMPP Apache
```

---

**Happy Testing! 🚀**
