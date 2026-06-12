# ANALISA: KATEGORI & GRUP PELANGGAN vs STATISTIK PELANGGAN

## 📋 Ringkasan Eksekutif

Saat ini sistem memiliki **2 sistem berbeda** untuk mengelompokkan pelanggan:

### 1. **Sistem LAMA: `tblpelanggangrup`** (Grup Manual)
- Field: `kgrup` di tabel `tblpelanggan`
- Diisi manual oleh admin/kasir
- Tidak otomatis update

### 2. **Sistem BARU: `statistik_pelanggan`** (Status Member Otomatis)
- Field: `status_member` di tabel `statistik_pelanggan`
- Update otomatis via trigger berdasarkan total transaksi
- Lebih akurat dan real-time

---

## 🔍 Analisa Detail Struktur Database

### A. Tabel `tblpelanggan` (Master Pelanggan)

```sql
CREATE TABLE `tblpelanggan` (
  `nopelanggan` varchar(20) PRIMARY KEY,
  `namapelanggan` varchar(50),
  `alamat` varchar(100),
  `telephone` varchar(20),
  `kgrup` varchar(3),  -- ⚠️ SISTEM LAMA (Manual)
  ...
);
```

**Field `kgrup`:**
- Tipe: `varchar(3)`
- Nilai: `'001'`, `'002'`, `'003'`, dll
- **Diisi manual** oleh kasir/admin
- **Tidak otomatis update** saat pelanggan transaksi

**Contoh data:**
```
nopelanggan | namapelanggan | kgrup
------------|---------------|-------
AD 3219 RV  | USMAN, BPK    | 001    (BENGKEL)
AD 3407 RI  | HAIFA, IBU    | 001    (BENGKEL)
AD 3493 OH  | MAZID, MAS    | 001    (BENGKEL)
```

---

### B. Tabel `tblpelanggangrup` (Master Grup - SISTEM LAMA)

```sql
CREATE TABLE `tblpelanggangrup` (
  `kgrup` varchar(3) PRIMARY KEY,
  `grup` varchar(30),
  `diskon` decimal(10,0)
);
```

**Data yang ada:**
```sql
INSERT INTO `tblpelanggangrup` VALUES
('001', 'BENGKEL', 0),
('002', 'MEMBER GOLD', 5),
('003', 'MEMBER SILVER', 10);
```

**Karakteristik:**
- ✅ Ada diskon per grup
- ❌ Tidak ada kategori Bronze/Platinum
- ❌ Tidak ada threshold berdasarkan nominal transaksi
- ❌ Harus diupdate manual oleh admin

**Masalah:**
1. Kasir harus ingat untuk update `kgrup` pelanggan
2. Tidak ada aturan kapan pelanggan naik level
3. Diskon tidak konsisten (Gold = 5%, Silver = 10% ← terbalik!)

---

### C. Tabel `statistik_pelanggan` (SISTEM BARU - Otomatis)

```sql
CREATE TABLE `statistik_pelanggan` (
  `id_statistik` int(11) PRIMARY KEY AUTO_INCREMENT,
  `no_pelanggan` varchar(20),
  `total_transaksi` int(11) DEFAULT 0,
  `total_nominal` decimal(15,2) DEFAULT 0.00,
  `status_member` enum('Bronze','Silver','Gold','Platinum') DEFAULT 'Bronze',
  `tanggal_pertama_transaksi` date,
  `tanggal_terakhir_transaksi` date,
  `lama_tidak_datang` int(11),
  `estimasi_datang_berikutnya` date,
  ...
);
```

**Field `status_member`:**
- Tipe: `enum('Bronze','Silver','Gold','Platinum')`
- **Update otomatis** via trigger MySQL
- Berdasarkan `total_nominal` transaksi

**Aturan Status Member (dari trigger):**
```
Bronze   : < Rp 2.000.000
Silver   : Rp 2.000.000 - Rp 4.999.999
Gold     : Rp 5.000.000 - Rp 9.999.999
Platinum : >= Rp 10.000.000
```

**Karakteristik:**
- ✅ Update otomatis saat pelanggan bayar
- ✅ Ada 4 level yang jelas (Bronze, Silver, Gold, Platinum)
- ✅ Threshold berdasarkan total nominal transaksi
- ✅ Tracking lengkap (jumlah transaksi, rata-rata, dll)
- ❌ Belum ada field `diskon` (perlu ditambahkan)

---

## 🔄 Perbandingan Kedua Sistem

| Aspek | `tblpelanggangrup` (LAMA) | `statistik_pelanggan` (BARU) |
|-------|---------------------------|------------------------------|
| **Update** | Manual oleh admin | Otomatis via trigger |
| **Akurasi** | Tergantung admin ingat | 100% akurat real-time |
| **Kategori** | 3 level (Bengkel, Gold, Silver) | 4 level (Bronze, Silver, Gold, Platinum) |
| **Threshold** | Tidak ada | Jelas berdasarkan nominal |
| **Diskon** | Ada (0%, 5%, 10%) | Belum ada |
| **Tracking** | Tidak ada | Lengkap (transaksi, nominal, tanggal, dll) |
| **Konsistensi** | Tidak konsisten (Gold < Silver) | Konsisten (Bronze < Silver < Gold < Platinum) |

---

## ⚠️ MASALAH UTAMA: DUPLIKASI & INKONSISTENSI

### Masalah 1: Dua Sistem Berbeda
```
Pelanggan "Budi" (AD 1234 AB):
- tblpelanggan.kgrup = '001' (BENGKEL)
- statistik_pelanggan.status_member = 'Gold'

❓ Mana yang benar? Bengkel atau Gold?
```

### Masalah 2: Diskon Terbalik
```
tblpelanggangrup:
- MEMBER GOLD   = 5%  diskon  ← Lebih rendah
- MEMBER SILVER = 10% diskon  ← Lebih tinggi

❌ Tidak masuk akal! Gold harusnya diskon lebih besar dari Silver
```

### Masalah 3: Tidak Sinkron
```
Skenario:
1. Pelanggan transaksi Rp 5.000.000
2. Trigger update: status_member = 'Gold' ✅
3. Tapi tblpelanggan.kgrup masih '001' (BENGKEL) ❌

Hasil: Data tidak sinkron!
```

---

## 💡 SOLUSI YANG DIREKOMENDASIKAN

### Opsi 1: MIGRASI PENUH ke Sistem Baru (RECOMMENDED ⭐)

**Langkah:**
1. Tambahkan field `diskon` ke tabel `statistik_pelanggan`
2. Buat aturan diskon yang konsisten
3. Update trigger untuk sync `kgrup` di `tblpelanggan`
4. Deprecate tabel `tblpelanggangrup` (tidak dipakai lagi)

**Struktur baru:**
```sql
ALTER TABLE statistik_pelanggan 
ADD COLUMN diskon_persen decimal(5,2) DEFAULT 0 
COMMENT 'Persentase diskon berdasarkan status member';
```

**Aturan diskon baru (konsisten):**
```
Bronze   : 0%  diskon  (< Rp 2 juta)
Silver   : 5%  diskon  (Rp 2-5 juta)
Gold     : 10% diskon  (Rp 5-10 juta)
Platinum : 15% diskon  (>= Rp 10 juta)
```

**Keuntungan:**
- ✅ Satu sumber kebenaran (single source of truth)
- ✅ Update otomatis, tidak perlu manual
- ✅ Konsisten dan akurat
- ✅ Mudah maintenance

---

### Opsi 2: SINKRONISASI Dua Sistem

**Langkah:**
1. Update trigger untuk sync `kgrup` saat `status_member` berubah
2. Mapping status member ke kgrup
3. Tetap pakai kedua tabel

**Mapping:**
```sql
Bronze   → kgrup = '001' (BENGKEL)
Silver   → kgrup = '003' (MEMBER SILVER)
Gold     → kgrup = '002' (MEMBER GOLD)
Platinum → kgrup = '004' (MEMBER PLATINUM) -- perlu ditambahkan
```

**Kelemahan:**
- ❌ Masih ada duplikasi data
- ❌ Lebih kompleks untuk maintenance
- ❌ Diskon masih terbalik (Gold 5%, Silver 10%)

---

## 🎯 REKOMENDASI FINAL

### Gunakan **Opsi 1: Migrasi Penuh**

**Alasan:**
1. **Lebih sederhana** - Satu sistem, satu sumber data
2. **Lebih akurat** - Update otomatis via trigger
3. **Lebih konsisten** - Aturan diskon yang masuk akal
4. **Lebih mudah maintenance** - Tidak perlu sync 2 tabel

---

## 📝 RENCANA IMPLEMENTASI

### Step 1: Tambah Field Diskon
```sql
ALTER TABLE statistik_pelanggan 
ADD COLUMN diskon_persen decimal(5,2) DEFAULT 0 
COMMENT 'Persentase diskon berdasarkan status member';
```

### Step 2: Update Trigger (Tambahkan Logic Diskon)
```sql
-- Di dalam trigger trg_after_service_bayar
-- Setelah set status_member, tambahkan:

SET v_diskon = CASE v_status_member
    WHEN 'Bronze' THEN 0
    WHEN 'Silver' THEN 5
    WHEN 'Gold' THEN 10
    WHEN 'Platinum' THEN 15
END;

-- Update dengan diskon
INSERT INTO statistik_pelanggan (..., diskon_persen)
VALUES (..., v_diskon)
ON DUPLICATE KEY UPDATE
    ...,
    diskon_persen = v_diskon;
```

### Step 3: Sync kgrup (Opsional - untuk backward compatibility)
```sql
-- Update kgrup di tblpelanggan berdasarkan status_member
UPDATE tblpelanggan p
JOIN statistik_pelanggan s ON p.nopelanggan = s.no_pelanggan
SET p.kgrup = CASE s.status_member
    WHEN 'Bronze' THEN '001'
    WHEN 'Silver' THEN '003'
    WHEN 'Gold' THEN '002'
    WHEN 'Platinum' THEN '004'  -- perlu tambah di tblpelanggangrup
END;
```

### Step 4: Update Aplikasi
```php
// Ganti semua referensi dari kgrup ke status_member
// File yang perlu diupdate:
// - servis-input-reguler.php
// - servis-input-jemput.php
// - pelanggan-list.php
// - dll

// SEBELUM:
$query = "SELECT kgrup FROM tblpelanggan WHERE nopelanggan = '$no'";

// SESUDAH:
$query = "SELECT s.status_member, s.diskon_persen 
          FROM statistik_pelanggan s 
          WHERE s.no_pelanggan = '$no'";
```

### Step 5: Deprecate tblpelanggangrup
```sql
-- Rename tabel (jangan hapus dulu, untuk backup)
RENAME TABLE tblpelanggangrup TO tblpelanggangrup_deprecated;

-- Atau tambahkan comment
ALTER TABLE tblpelanggangrup 
COMMENT 'DEPRECATED: Gunakan statistik_pelanggan.status_member';
```

---

## 📊 CONTOH PENGGUNAAN SETELAH MIGRASI

### Cek Status & Diskon Pelanggan
```sql
SELECT 
    p.nopelanggan,
    p.namapelanggan,
    s.status_member,
    s.diskon_persen,
    s.total_transaksi,
    s.total_nominal
FROM tblpelanggan p
LEFT JOIN statistik_pelanggan s ON p.nopelanggan = s.no_pelanggan
WHERE p.nopelanggan = 'AD 1234 AB';
```

**Output:**
```
nopelanggan | namapelanggan | status_member | diskon_persen | total_transaksi | total_nominal
------------|---------------|---------------|---------------|-----------------|---------------
AD 1234 AB  | Budi Santoso  | Gold          | 10.00         | 15              | 7500000.00
```

### Hitung Total Bayar dengan Diskon
```php
<?php
// Get diskon dari statistik_pelanggan
$query = "SELECT diskon_persen FROM statistik_pelanggan 
          WHERE no_pelanggan = '$no_pelanggan'";
$result = mysqli_query($koneksi, $query);
$row = mysqli_fetch_assoc($result);
$diskon_persen = $row['diskon_persen'] ?? 0;

// Hitung total
$subtotal = 1000000; // Rp 1 juta
$diskon = $subtotal * ($diskon_persen / 100);
$total_bayar = $subtotal - $diskon;

echo "Subtotal: Rp " . number_format($subtotal, 0, ',', '.');
echo "Diskon ($diskon_persen%): Rp " . number_format($diskon, 0, ',', '.');
echo "Total Bayar: Rp " . number_format($total_bayar, 0, ',', '.');
?>
```

**Output:**
```
Subtotal: Rp 1.000.000
Diskon (10%): Rp 100.000
Total Bayar: Rp 900.000
```

---

## 🎁 BENEFIT SETELAH MIGRASI

### Untuk Kasir:
- ✅ Tidak perlu ingat update grup pelanggan
- ✅ Diskon otomatis muncul saat input servis
- ✅ Lihat status member real-time

### Untuk Pelanggan:
- ✅ Otomatis naik level saat transaksi mencapai threshold
- ✅ Diskon yang adil dan konsisten
- ✅ Transparansi progress ke level berikutnya

### Untuk Owner:
- ✅ Data akurat dan real-time
- ✅ Laporan statistik lengkap
- ✅ Mudah analisa pelanggan VIP
- ✅ Sistem loyalty program yang jelas

---

## ❓ FAQ

### Q1: Apakah data lama di `tblpelanggangrup` akan hilang?
**A:** Tidak. Tabel akan di-rename menjadi `tblpelanggangrup_deprecated` sebagai backup. Data tidak hilang.

### Q2: Apakah perlu update manual semua pelanggan?
**A:** Tidak. Trigger akan otomatis update saat pelanggan transaksi berikutnya.

### Q3: Bagaimana dengan pelanggan yang belum pernah transaksi?
**A:** Pelanggan baru otomatis dapat status Bronze (0% diskon) sampai mereka transaksi.

### Q4: Apakah bisa custom diskon per pelanggan?
**A:** Bisa. Tambahkan field `diskon_custom` di `tblpelanggan` untuk override diskon default.

### Q5: Bagaimana jika ingin ubah threshold (misal Silver jadi Rp 3 juta)?
**A:** Tinggal update trigger. Semua pelanggan akan otomatis re-kalkulasi saat transaksi berikutnya.

---

## 📌 KESIMPULAN

### Situasi Saat Ini:
- ❌ Dua sistem berbeda (kgrup vs status_member)
- ❌ Tidak sinkron
- ❌ Diskon tidak konsisten
- ❌ Update manual, rawan lupa

### Setelah Migrasi:
- ✅ Satu sistem terpadu
- ✅ Update otomatis
- ✅ Diskon konsisten dan adil
- ✅ Data akurat real-time

### Next Steps:
1. Review dan approve rencana migrasi
2. Backup database
3. Implementasi Step 1-5
4. Testing
5. Deploy ke production

---

**Dibuat:** 5 November 2025  
**Versi:** 1.0  
**Status:** Menunggu Approval  
**Developer:** Fit Motor Development Team
