# DOKUMENTASI MASTER KATEGORI MEMBER

## 📋 RINGKASAN

Sistem CRUD untuk mengatur threshold status member berdasarkan **Nominal** dan **Kunjungan** secara dinamis tanpa perlu edit trigger database.

---

## 🎯 FITUR UTAMA

### 1. **Pengaturan Kategori Berdasarkan Nominal**
- Bronze: Rp 0 - 1.999.999
- Silver: Rp 2.000.000 - 4.999.999
- Gold: Rp 5.000.000 - 9.999.999
- Platinum: Rp 10.000.000 ke atas

### 2. **Pengaturan Kategori Berdasarkan Kunjungan**
- Bronze: 0 - 4 kunjungan
- Silver: 5 - 9 kunjungan
- Gold: 10 - 19 kunjungan
- Platinum: 20+ kunjungan

### 3. **Fitur Lengkap**
- ✅ CRUD (Create, Read, Update, Delete)
- ✅ Pengaturan min/max value
- ✅ Pengaturan diskon per kategori
- ✅ Pengaturan benefit member
- ✅ Kustomisasi icon & warna badge
- ✅ Status aktif/nonaktif
- ✅ Urutan tampilan
- ✅ Tab terpisah untuk nominal & kunjungan

---

## 📁 FILE YANG DIBUAT

### 1. **database_master_kategori_member.sql**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\`

**Isi:**
- Tabel `master_kategori_member`
- Function `fn_get_status_member_nominal()`
- Function `fn_get_status_member_kunjungan()`
- View `view_master_kategori_member`
- Data default (8 kategori: 4 nominal + 4 kunjungan)

### 2. **master_kategori_member.php**
**Lokasi:** `c:\xampp\htdocs\web-bengkel\aplikasi\aplikasi\_admincab\`

**Fitur:**
- Halaman CRUD lengkap
- Tab untuk nominal & kunjungan
- Form modal untuk tambah/edit
- Preview badge dengan warna
- Konfirmasi hapus
- Responsive design

### 3. **update_statistik_berbasis_kedatangan.sql** (UPDATED)
**Yang Diubah:**
- Trigger sekarang menggunakan function dari tabel master
- Tidak lagi hardcode threshold di trigger

---

## 🗄️ STRUKTUR DATABASE

### Tabel: `master_kategori_member`

```sql
CREATE TABLE `master_kategori_member` (
  `id_kategori` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` ENUM('Bronze','Silver','Gold','Platinum') NOT NULL,
  `tipe_kategori` ENUM('nominal','kunjungan') NOT NULL,
  `min_value` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `max_value` DECIMAL(15,2) DEFAULT NULL,
  `diskon_persen` INT(11) DEFAULT 0,
  `benefit_text` TEXT DEFAULT NULL,
  `icon` VARCHAR(10) DEFAULT NULL,
  `warna` VARCHAR(7) DEFAULT NULL,
  `urutan` INT(11) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_kategori`),
  UNIQUE KEY `unique_kategori_tipe` (`nama_kategori`, `tipe_kategori`)
) ENGINE=InnoDB;
```

### Kolom Penting:

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `nama_kategori` | ENUM | Bronze, Silver, Gold, Platinum |
| `tipe_kategori` | ENUM | nominal atau kunjungan |
| `min_value` | DECIMAL | Nilai minimum untuk kategori |
| `max_value` | DECIMAL | Nilai maksimum (NULL = unlimited) |
| `diskon_persen` | INT | Diskon dalam persen |
| `benefit_text` | TEXT | Deskripsi benefit (multiline) |
| `icon` | VARCHAR | Icon emoji (🥉🥈🥇💎) |
| `warna` | VARCHAR | Warna hex untuk badge |
| `is_active` | TINYINT | 1=aktif, 0=nonaktif |

---

## 🚀 CARA INSTALASI

### Step 1: Import Database
```bash
mysql -u root -p fitmotor_dbbengkel < database_master_kategori_member.sql
```

### Step 2: Update Trigger (Opsional jika sudah ada)
```bash
mysql -u root -p fitmotor_dbbengkel < update_statistik_berbasis_kedatangan.sql
```

### Step 3: Akses Halaman CRUD
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/master_kategori_member.php
```

### Step 4: Verifikasi
```sql
-- Cek data default
SELECT * FROM master_kategori_member ORDER BY tipe_kategori, urutan;

-- Test function
SELECT fn_get_status_member_nominal(3000000); -- Hasilnya: Silver
SELECT fn_get_status_member_kunjungan(15); -- Hasilnya: Gold
```

---

## 📊 CONTOH DATA DEFAULT

### Kategori Nominal:
```
┌──────────┬──────────────┬──────────────┬─────────┬────────────────────────┐
│ Kategori │ Min          │ Max          │ Diskon  │ Benefit                │
├──────────┼──────────────┼──────────────┼─────────┼────────────────────────┤
│ Bronze   │ Rp 0         │ Rp 1.999.999 │ 0%      │ Member standar         │
│ Silver   │ Rp 2.000.000 │ Rp 4.999.999 │ 10%     │ Diskon 10%, Prioritas  │
│ Gold     │ Rp 5.000.000 │ Rp 9.999.999 │ 15%     │ Diskon 15%, Gratis cuci│
│ Platinum │ Rp 10.000.000│ Unlimited    │ 20%     │ Diskon 20%, VIP, dll   │
└──────────┴──────────────┴──────────────┴─────────┴────────────────────────┘
```

### Kategori Kunjungan:
```
┌──────────┬──────┬──────┬─────────┬────────────────────────┐
│ Kategori │ Min  │ Max  │ Diskon  │ Benefit                │
├──────────┼──────┼──────┼─────────┼────────────────────────┤
│ Bronze   │ 0x   │ 4x   │ 0%      │ Member standar         │
│ Silver   │ 5x   │ 9x   │ 10%     │ Diskon 10%, Prioritas  │
│ Gold     │ 10x  │ 19x  │ 15%     │ Diskon 15%, Gratis cuci│
│ Platinum │ 20x  │ ∞    │ 20%     │ Diskon 20%, VIP, dll   │
└──────────┴──────┴──────┴─────────┴────────────────────────┘
```

---

## 🎨 CARA MENGGUNAKAN HALAMAN CRUD

### 1. **Tambah Kategori Baru**
1. Klik tombol "Tambah Kategori Baru"
2. Pilih nama kategori (Bronze/Silver/Gold/Platinum)
3. Pilih tipe (Nominal/Kunjungan)
4. Isi nilai minimum & maksimum
5. Isi diskon & benefit
6. Pilih icon & warna
7. Klik "Simpan"

### 2. **Edit Kategori**
1. Klik tombol "Edit" pada baris yang ingin diubah
2. Ubah data yang diperlukan
3. Klik "Simpan"

### 3. **Hapus Kategori**
1. Klik tombol "Hapus" pada baris yang ingin dihapus
2. Konfirmasi penghapusan
3. Data akan terhapus

### 4. **Nonaktifkan Kategori**
1. Edit kategori
2. Uncheck checkbox "Aktif"
3. Simpan
4. Kategori tidak akan digunakan dalam perhitungan

---

## 🔧 CARA MENGUBAH THRESHOLD

### Contoh: Ubah Silver dari Rp 2jt menjadi Rp 3jt

**Cara 1: Via Halaman CRUD**
1. Buka `master_kategori_member.php`
2. Tab "Berdasarkan Nominal"
3. Klik "Edit" pada baris Silver
4. Ubah "Min. Nominal" dari 2000000 menjadi 3000000
5. Klik "Simpan"

**Cara 2: Via SQL**
```sql
UPDATE master_kategori_member 
SET min_value = 3000000 
WHERE nama_kategori = 'Silver' 
  AND tipe_kategori = 'nominal';
```

**Hasil:**
- Trigger akan otomatis menggunakan threshold baru
- Tidak perlu edit trigger
- Tidak perlu restart server
- Langsung berlaku untuk transaksi berikutnya

---

## 📝 CONTOH QUERY

### 1. Lihat Semua Kategori
```sql
SELECT * FROM view_master_kategori_member;
```

### 2. Lihat Kategori Nominal Saja
```sql
SELECT * FROM master_kategori_member 
WHERE tipe_kategori = 'nominal' 
  AND is_active = 1 
ORDER BY urutan;
```

### 3. Lihat Kategori Kunjungan Saja
```sql
SELECT * FROM master_kategori_member 
WHERE tipe_kategori = 'kunjungan' 
  AND is_active = 1 
ORDER BY urutan;
```

### 4. Test Function
```sql
-- Cek status member untuk nominal Rp 3.500.000
SELECT fn_get_status_member_nominal(3500000);
-- Hasilnya: Silver

-- Cek status member untuk 12 kunjungan
SELECT fn_get_status_member_kunjungan(12);
-- Hasilnya: Gold
```

### 5. Update Threshold
```sql
-- Ubah Gold nominal menjadi Rp 6jt - 12jt
UPDATE master_kategori_member 
SET min_value = 6000000, max_value = 12000000 
WHERE nama_kategori = 'Gold' AND tipe_kategori = 'nominal';

-- Ubah Platinum kunjungan menjadi 25+
UPDATE master_kategori_member 
SET min_value = 25 
WHERE nama_kategori = 'Platinum' AND tipe_kategori = 'kunjungan';
```

---

## 🎯 USE CASE

### Use Case 1: Promo Spesial
**Skenario:** Promo akhir tahun, turunkan threshold Gold

**Sebelum:**
- Gold: Rp 5.000.000 - 9.999.999

**Promo:**
```sql
UPDATE master_kategori_member 
SET min_value = 3000000 
WHERE nama_kategori = 'Gold' AND tipe_kategori = 'nominal';
```

**Hasil:**
- Gold: Rp 3.000.000 - 9.999.999
- Lebih banyak pelanggan jadi Gold
- Setelah promo, ubah kembali ke Rp 5jt

---

### Use Case 2: Tambah Kategori Diamond
**Skenario:** Tambah kategori baru untuk pelanggan super VIP

**Langkah:**
1. Buka halaman CRUD
2. Klik "Tambah Kategori Baru"
3. Isi:
   - Nama: Platinum (atau buat enum baru)
   - Tipe: nominal
   - Min: 20000000
   - Max: (kosongkan)
   - Diskon: 25%
   - Benefit: "Diskon 25%\nVIP Lounge\nFree maintenance 1 tahun"
   - Icon: 💠
   - Warna: #B9F2FF
4. Simpan

---

### Use Case 3: Nonaktifkan Kategori Sementara
**Skenario:** Sementara hanya ada Bronze dan Gold

**Langkah:**
```sql
UPDATE master_kategori_member 
SET is_active = 0 
WHERE nama_kategori IN ('Silver', 'Platinum');
```

**Hasil:**
- Silver & Platinum tidak digunakan
- Pelanggan hanya bisa Bronze atau Gold

---

## ⚠️ PENTING!

### 1. **Jangan Hapus Semua Kategori**
- Minimal harus ada 1 kategori aktif per tipe
- Jika tidak ada, function akan return 'Bronze' sebagai default

### 2. **Hindari Overlap Range**
- Pastikan range tidak overlap
- Contoh SALAH:
  - Silver: 2jt - 5jt
  - Gold: 4jt - 10jt ← OVERLAP!
- Contoh BENAR:
  - Silver: 2jt - 4.999.999
  - Gold: 5jt - 9.999.999

### 3. **Backup Sebelum Ubah**
```sql
-- Backup tabel
CREATE TABLE master_kategori_member_backup AS 
SELECT * FROM master_kategori_member;
```

### 4. **Test Setelah Ubah**
```sql
-- Test dengan berbagai nilai
SELECT fn_get_status_member_nominal(1000000);  -- Bronze
SELECT fn_get_status_member_nominal(3000000);  -- Silver
SELECT fn_get_status_member_nominal(7000000);  -- Gold
SELECT fn_get_status_member_nominal(15000000); -- Platinum
```

---

## 🔄 INTEGRASI DENGAN SISTEM

### 1. **Trigger Otomatis**
Trigger `trg_after_service_bayar` sudah menggunakan function dari tabel master:

```sql
-- Sebelum (Hardcode)
IF v_total_nominal >= 10000000 THEN
    SET v_status_member = 'Platinum';
...

-- Sekarang (Dynamic)
SET v_status_member = fn_get_status_member_nominal(v_total_nominal);
SET v_kategori_member_kunjungan = fn_get_status_member_kunjungan(v_jumlah_kunjungan);
```

### 2. **Halaman Servis Input**
Display member otomatis mengambil data dari tabel master via trigger.

### 3. **Dashboard Statistik**
Dashboard menampilkan kategori sesuai dengan threshold di tabel master.

---

## ✅ KESIMPULAN

**Keunggulan Sistem Master Kategori:**
- ✅ Ubah threshold tanpa edit trigger
- ✅ Ubah benefit tanpa edit code
- ✅ Ubah warna & icon tanpa edit CSS
- ✅ Promo mudah (turunkan threshold sementara)
- ✅ Fleksibel untuk berbagai strategi bisnis
- ✅ Audit trail (created_at, updated_at)
- ✅ Bisa nonaktifkan kategori tanpa hapus

**File yang Dibuat:**
1. ✅ `database_master_kategori_member.sql` - Database
2. ✅ `master_kategori_member.php` - Halaman CRUD
3. ✅ `update_statistik_berbasis_kedatangan.sql` - Updated trigger

**Cara Akses:**
```
http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/master_kategori_member.php
```

**Sistem sudah siap digunakan!** 🎉
