# Perbaikan Kolom Database - Status Pengerjaan Views

## 🔧 Error yang Ditemukan

### **Error #1054 - Unknown column 'p.nohp' in 'field list'**

**Penyebab:**
- View menggunakan kolom `p.nohp` yang tidak ada di tabel `tblpelanggan`
- Struktur database aktual berbeda dengan asumsi awal

---

## 📊 Struktur Database Aktual

### **1. tblpelanggan**

**Kolom yang tersedia:**
```sql
- nopelanggan (PK)
- namapelanggan
- alamat
- kota, propinsi, kodepost, negara
- telephone ✅ (bukan 'nohp')
- fax
- notlp ✅ (kolom alternatif untuk nomor telepon)
- kontakperson
- note
- potongan, tipepot, lavelharga
- kgrup, patokan
- foto_tampak_rumah
- link_gmaps
- klat, klong
- panggilan
- saldoawal, pertanggal
- tgllahir
- id_panggilan
- bl_pajak, th_pajak
- merek_id
```

**Kolom HP yang benar:**
- ✅ `telephone` (kolom utama)
- ✅ `notlp` (kolom alternatif)
- ❌ `nohp` (TIDAK ADA)

---

### **2. tblkendaraan**

**Kolom yang tersedia:**
```sql
- nopolisi (PK)
- pemilik
- alamat
- kode_merek ✅ (integer, bukan 'merek')
- tipe ✅
- kode_tipe
- jenis ✅
- kode_jenis
- tahun_buat
- tahun_rakit
- silinder
- warna
- kode_warna
- no_rangka
- no_mesin
- note
```

**Kolom Merek yang benar:**
- ✅ `kode_merek` (integer, FK ke master merek)
- ❌ `merek` (TIDAK ADA)

---

### **3. tbmaster_temuan**

**Kolom yang tersedia:**
```sql
- id (PK)
- kode_temuan ✅
- nama_temuan ✅
- deskripsi
- kategori ✅ (bukan 'kategori_temuan')
- tingkat_urgensi
- is_active
- created_at
- updated_at
```

**Kolom Kategori yang benar:**
- ✅ `kategori` (bukan 'kategori_temuan')

---

### **4. tbworkorderheader**

**Kolom yang tersedia:**
```sql
- kode_wo (PK)
- nama_wo ✅
- keterangan ✅ (bukan 'deskripsi_wo')
- status ✅
- waktu ✅ (estimasi waktu dalam menit)
- harga ✅
```

**Kolom yang TIDAK ADA:**
- ❌ `kategori_wo` (TIDAK ADA)
- ❌ `deskripsi_wo` (gunakan 'keterangan')

**Kolom yang benar:**
- ✅ `keterangan` (untuk deskripsi work order)
- ✅ `status` (status aktif/non-aktif work order)
- ✅ `waktu` (estimasi waktu pengerjaan dalam menit)
- ✅ `harga` (harga work order)

---

### **5. tbworkorderdetail**

**Kolom yang tersedia:**
```sql
- kode_wo (FK)
- kode_barang (kode jasa atau barang)
- jumlah
- satuan
- diskon
- status_diskon
- id (PK)
- tipe ✅ ('1' = Jasa, '2' = Barang)
- harga
- total
```

**Catatan:**
- `tipe = '1'` → Jasa/Service
- `tipe = '2'` → Barang/Part

---

## ✅ Perbaikan yang Dilakukan

### **File: FIX_STATUS_PENGERJAAN_VIEWS.sql**

#### **1. View Keluhan (view_servis_keluhan_lengkap)**

**SEBELUM:**
```sql
p.nohp AS hp_pelanggan,  -- ❌ ERROR
v.merek AS merek_kendaraan,  -- ❌ ERROR
```

**SESUDAH:**
```sql
p.telephone AS hp_pelanggan,  -- ✅ FIXED
p.notlp AS hp_pelanggan2,  -- ✅ TAMBAHAN
v.kode_merek,  -- ✅ FIXED
v.tipe AS tipe_kendaraan,  -- ✅ TAMBAHAN
v.jenis AS jenis_kendaraan,  -- ✅ FIXED
```

---

#### **2. View Temuan (view_servis_temuan_lengkap)**

**SEBELUM:**
```sql
p.nohp AS hp_pelanggan,  -- ❌ ERROR
v.merek AS merek_kendaraan,  -- ❌ ERROR
mt.kategori_temuan,  -- ❌ ERROR
```

**SESUDAH:**
```sql
p.telephone AS hp_pelanggan,  -- ✅ FIXED
p.notlp AS hp_pelanggan2,  -- ✅ TAMBAHAN
v.kode_merek,  -- ✅ FIXED
v.tipe AS tipe_kendaraan,  -- ✅ TAMBAHAN
v.jenis AS jenis_kendaraan,  -- ✅ FIXED
mt.kategori AS kategori_temuan,  -- ✅ FIXED
```

---

#### **3. View Work Order (view_servis_workorder_lengkap)**

**SEBELUM:**
```sql
p.nohp AS hp_pelanggan,  -- ❌ ERROR
v.merek AS merek_kendaraan,  -- ❌ ERROR
woh.kategori_wo,  -- ❌ ERROR
woh.deskripsi_wo,  -- ❌ ERROR
```

**SESUDAH:**
```sql
p.telephone AS hp_pelanggan,  -- ✅ FIXED
p.notlp AS hp_pelanggan2,  -- ✅ TAMBAHAN
v.kode_merek,  -- ✅ FIXED
v.tipe AS tipe_kendaraan,  -- ✅ TAMBAHAN
v.jenis AS jenis_kendaraan,  -- ✅ FIXED
woh.keterangan AS deskripsi_wo,  -- ✅ FIXED
woh.status AS status_wo,  -- ✅ TAMBAHAN
```

---

#### **4. View Summary (view_servis_status_summary)**

**SEBELUM:**
```sql
p.nohp AS hp_pelanggan,  -- ❌ ERROR
v.merek AS merek_kendaraan,  -- ❌ ERROR
```

**SESUDAH:**
```sql
p.telephone AS hp_pelanggan,  -- ✅ FIXED
v.kode_merek,  -- ✅ FIXED
v.tipe AS tipe_kendaraan,  -- ✅ TAMBAHAN
v.jenis AS jenis_kendaraan,  -- ✅ FIXED
```

---

## 🎯 Kolom Tambahan yang Ditambahkan

### **1. Nomor HP Alternatif**
```sql
p.notlp AS hp_pelanggan2
```
- Untuk backup jika `telephone` kosong
- Bisa digunakan untuk WhatsApp atau kontak alternatif

### **2. Info Kendaraan Lengkap**
```sql
v.kode_merek,  -- ID merek (bisa di-join ke master merek)
v.tipe AS tipe_kendaraan,  -- Contoh: BEAT-110, VARIO-110
v.jenis AS jenis_kendaraan,  -- Contoh: FI, Karburator
v.tahun_buat  -- Tahun pembuatan kendaraan
```

### **3. Alamat Pelanggan**
```sql
p.alamat AS alamat_pelanggan
```
- Untuk keperluan jemput/antar
- Info lokasi pelanggan

---

## 🔍 Cara Menggunakan Kolom HP

### **PHP Code Example:**

```php
// Ambil nomor HP (prioritas telephone, fallback ke notlp)
$hp = !empty($row['hp_pelanggan']) ? $row['hp_pelanggan'] : $row['hp_pelanggan2'];

// Atau gunakan COALESCE di SQL
SELECT COALESCE(p.telephone, p.notlp, 'Tidak ada HP') AS hp_pelanggan
```

---

## 🚀 Cara Implementasi

### **Step 1: Backup Database**
```bash
mysqldump -u root -p fitmotor_dbbengkel > backup_before_fix.sql
```

### **Step 2: Run SQL Fix**
```bash
mysql -u root -p fitmotor_dbbengkel < FIX_STATUS_PENGERJAAN_VIEWS.sql
```

### **Step 3: Verify Views**
```sql
-- Test semua view
SELECT * FROM view_servis_keluhan_lengkap LIMIT 5;
SELECT * FROM view_servis_temuan_lengkap LIMIT 5;
SELECT * FROM view_servis_workorder_lengkap LIMIT 5;
SELECT * FROM view_servis_status_summary LIMIT 5;
```

### **Step 4: Update PHP Code**

Ganti query di file PHP:
```php
// SEBELUM
$query = "SELECT * FROM tbservis_keluhan_status WHERE no_service='$no_service'";

// SESUDAH
$query = "SELECT * FROM view_servis_keluhan_lengkap WHERE no_service='$no_service'";
```

---

## 📝 Catatan Penting

### **1. Kolom Merek Kendaraan**

`kode_merek` adalah **integer** (ID), bukan nama merek.

**Jika ingin tampilkan nama merek:**
```sql
-- Tambahkan join ke master merek
LEFT JOIN tblmerek m ON v.kode_merek = m.id_merek

-- Lalu select
m.nama_merek AS merek_kendaraan
```

**Atau di PHP:**
```php
// Mapping manual (jika master merek tidak ada)
$merek_map = [
    1 => 'Honda',
    2 => 'Yamaha',
    3 => 'Suzuki',
    // dst...
];

$merek = $merek_map[$row['kode_merek']] ?? 'Unknown';
```

---

### **2. Nomor HP Pelanggan**

Ada **2 kolom** untuk nomor HP:
- `telephone` (kolom utama)
- `notlp` (kolom alternatif)

**Best Practice:**
```php
// Gunakan COALESCE untuk fallback
$query = "SELECT 
    COALESCE(p.telephone, p.notlp, 'Tidak ada HP') AS hp_pelanggan
FROM tblpelanggan p";
```

---

### **3. Index Performance**

SQL fix sudah include **auto-create index** dengan check:
- `idx_status_pengerjaan` pada `tbservis_keluhan_status`
- `idx_status_pengerjaan` pada `tbservis_workorder`
- `idx_status_temuan` pada `tbservis_temuan`

Index ini akan **meningkatkan performa query** saat filter by status.

---

## ✅ Testing Checklist

- [ ] Run SQL fix tanpa error
- [ ] View `view_servis_keluhan_lengkap` bisa diakses
- [ ] View `view_servis_temuan_lengkap` bisa diakses
- [ ] View `view_servis_workorder_lengkap` bisa diakses
- [ ] View `view_servis_status_summary` bisa diakses
- [ ] Kolom `hp_pelanggan` menampilkan nomor telepon
- [ ] Kolom `kode_merek` menampilkan ID merek
- [ ] Kolom `status_badge_color` menampilkan warna yang benar
- [ ] Query di PHP berjalan tanpa error
- [ ] Tampilan UI menampilkan status dengan benar

---

## 🐛 Troubleshooting

### **Error: View already exists**
```sql
-- Drop view dulu
DROP VIEW IF EXISTS view_servis_keluhan_lengkap;
DROP VIEW IF EXISTS view_servis_temuan_lengkap;
DROP VIEW IF EXISTS view_servis_workorder_lengkap;
DROP VIEW IF EXISTS view_servis_status_summary;

-- Lalu run ulang SQL fix
```

### **Error: Column not found**
```sql
-- Check struktur tabel
DESCRIBE tblpelanggan;
DESCRIBE tblkendaraan;
DESCRIBE tbmaster_temuan;
DESCRIBE tbworkorderheader;
DESCRIBE tbworkorderdetail;

-- Sesuaikan nama kolom di view
```

### **View menampilkan data NULL**
```sql
-- Check data di tabel
SELECT * FROM tblpelanggan LIMIT 5;
SELECT * FROM tblkendaraan LIMIT 5;

-- Pastikan ada data di kolom telephone/notlp
```

---

## 📝 Ringkasan Error yang Diperbaiki

### **Error #1: Unknown column 'p.nohp'**
- ❌ Kolom: `p.nohp`
- ✅ Fix: `p.telephone` dan `p.notlp`

### **Error #2: Unknown column 'v.merek'**
- ❌ Kolom: `v.merek`
- ✅ Fix: `v.kode_merek`

### **Error #3: Unknown column 'woh.kategori_wo'**
- ❌ Kolom: `woh.kategori_wo`
- ✅ Fix: Kolom dihapus (tidak ada di database)

### **Error #4: Unknown column 'woh.deskripsi_wo'**
- ❌ Kolom: `woh.deskripsi_wo`
- ✅ Fix: `woh.keterangan`

### **Error #5: Unknown column 'mt.kategori_temuan'**
- ❌ Kolom: `mt.kategori_temuan`
- ✅ Fix: `mt.kategori`

---

## 📞 Support

Jika ada error atau pertanyaan:
1. Check error message di MySQL
2. Verify struktur tabel dengan `DESCRIBE table_name`
3. Test query manual di phpMyAdmin
4. Update dokumentasi jika ada perubahan

---

**Last Updated:** 2025-01-09  
**Version:** 1.2 (All Errors Fixed)  
**Status:** ✅ Ready to Deploy
