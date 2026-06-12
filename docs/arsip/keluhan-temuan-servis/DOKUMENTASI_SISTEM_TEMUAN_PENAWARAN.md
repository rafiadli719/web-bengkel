# DOKUMENTASI SISTEM TEMUAN & PENAWARAN PART

**Tanggal:** 7 November 2025  
**Versi:** 1.0

---

## 📋 OVERVIEW

Sistem ini menambahkan fitur **Temuan & Penawaran Part** yang terintegrasi dengan sistem servis existing.

### Fitur Utama:
1. ✅ **Master Temuan** - Template temuan hasil pengecekan
2. ✅ **Temuan per Servis** - Track temuan dengan status
3. ✅ **Penawaran Part** - Tawarkan part ke customer
4. ✅ **Fast Moves** - Pilih part cepat berdasarkan kategori
5. ✅ **Barang Non-Item** - Request barang yang belum terdaftar
6. ✅ **Approval System** - Validasi dari pusat (optional)

---

## 🗄️ DATABASE

### 1. tbmaster_temuan
Master data temuan hasil pengecekan

**Kolom Utama:**
- `kode_temuan` - Kode unik temuan
- `nama_temuan` - Nama temuan
- `kategori` - Kategori (Mesin, Kelistrikan, dll)
- `tingkat_urgensi` - rendah/sedang/tinggi/kritis

### 2. tbservis_temuan
Temuan per servis

**Kolom Utama:**
- `no_service` - Link ke tblservice
- `keluhan_id` - Link ke keluhan (optional)
- `jenis_perbaikan` - setting/penggantian_part
- `status_temuan` - ditemukan/ditawarkan/disetujui/ditolak/selesai

### 3. tbservis_penawaran_part
Penawaran part ke customer

**Kolom Utama:**
- `no_service` - Link ke tblservice
- `temuan_id` - Link ke temuan (optional)
- `status_penawaran` - pending/disetujui/ditolak
- `alasan_tolak` - Alasan jika ditolak

### 4. tbmaster_kategori_fastmoves
Kategori untuk fast moves

**Sample Data:**
- FLT - Filter Udara
- OLI - Oli & Pelumas
- BAN - Ban & Velg
- REM - Kampas Rem
- AKI - Aki & Battery

### 5. tbmaster_barang_nonitem
Barang yang belum terdaftar

**Kolom Utama:**
- `status_approval` - pending/approved/rejected
- `perlu_approval` - 1=Perlu approval, 0=Auto approved

---

## 🔄 ALUR PROSES

### Alur 1: Setting Saja
```
Temuan → Jenis: Setting → Langsung Kerjakan → Selesai
```

### Alur 2: Penggantian Part - Disetujui
```
Temuan → Jenis: Penggantian Part → Penawaran ke Customer
  → Customer Setuju → Masuk Item Barang → Dikerjakan → Selesai
```

### Alur 3: Penggantian Part - Ditolak
```
Temuan → Jenis: Penggantian Part → Penawaran ke Customer
  → Customer Tolak → Pilih Alasan → Masuk Catatan
```

**Alasan Penolakan:**
1. Customer tidak mau
2. Stok bengkel kosong
3. Stok supplier kosong
4. Harga tidak cocok
5. Lainnya

---

## 📝 IMPLEMENTASI

### File yang Dibuat:

1. **database_master_temuan_penawaran.sql**
   - Semua tabel database
   - Sample data
   - Triggers & views

2. **modal-search-temuan.php**
   - Modal pencarian temuan
   - Input manual temuan

3. **modal-fastmoves-part.php**
   - Modal fast moves
   - Filter kategori
   - Pilih part cepat

4. **tab-temuan-penawaran.php** (partial)
   - Tab UI untuk temuan & penawaran
   - Form input
   - Tabel display

---

## 🚀 CARA PENGGUNAAN

### 1. Install Database
```sql
-- Run file SQL
source database_master_temuan_penawaran.sql;
```

### 2. Tambahkan Tab di Servis Input
```php
// Di file servis-input-*.php
include '_template/tab-temuan-penawaran.php';
```

### 3. Tambahkan Handler PHP
```php
// Handler tambah temuan
if(isset($_POST['btnaddtemuan'])) { ... }

// Handler tambah penawaran
if(isset($_POST['btnaddpenawaran'])) { ... }

// Handler setujui penawaran
if(isset($_POST['btnsetujuipenawaran'])) { ... }

// Handler tolak penawaran
if(isset($_POST['btntolakpenawaran'])) { ... }
```

---

## ⚙️ KONFIGURASI

### Approval System
Edit di tabel `tbmaster_barang_nonitem`:

```sql
-- Jika PERLU approval dari pusat
UPDATE tbmaster_barang_nonitem 
SET perlu_approval = 1;

-- Jika TIDAK PERLU approval (auto approved)
UPDATE tbmaster_barang_nonitem 
SET perlu_approval = 0;
```

### Mapping Barang ke Fast Moves
```sql
-- Tambahkan barang ke kategori
INSERT INTO tbmaster_barang_fastmoves 
(kode_kategori, kode_barang, is_featured, urutan) 
VALUES 
('FLT', 'FILTER001', 1, 1);
```

---

## 📊 REPORTS & ANALYTICS

### Query: Temuan Terbanyak
```sql
SELECT 
    kode_temuan,
    nama_temuan,
    COUNT(*) as jumlah
FROM tbservis_temuan st
LEFT JOIN tbmaster_temuan mt ON st.kode_temuan = mt.kode_temuan
WHERE MONTH(st.created_at) = MONTH(CURDATE())
GROUP BY kode_temuan
ORDER BY jumlah DESC
LIMIT 10;
```

### Query: Penawaran Ditolak
```sql
SELECT 
    alasan_tolak,
    COUNT(*) as jumlah,
    SUM(total_harga) as total_nilai
FROM tbservis_penawaran_part
WHERE status_penawaran = 'ditolak'
GROUP BY alasan_tolak;
```

---

## 🔧 MAINTENANCE

### Cleanup Old Data
```sql
-- Hapus temuan > 1 tahun
DELETE FROM tbservis_temuan 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Hapus log penawaran > 6 bulan
DELETE FROM tbservis_penawaran_log 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH);
```

---

## 📞 SUPPORT

Untuk pertanyaan atau issue, hubungi tim development.
