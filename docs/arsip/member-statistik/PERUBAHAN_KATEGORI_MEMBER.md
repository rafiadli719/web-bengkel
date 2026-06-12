# 📝 DOKUMENTASI PERUBAHAN: KATEGORI MEMBER PELANGGAN

## 🎯 Ringkasan Perubahan

Sistem kategori pelanggan telah diupdate dari sistem **grup manual** (tblpelanggangrup) menjadi sistem **kategori member otomatis** (statistik_pelanggan).

---

## 📁 File yang Diubah

### 1. **File Halaman Kategori Member**
- ✅ `master_kategori_member.php` → **DIGANTI** menjadi `pelanggan_kategori.php`
- Lokasi: `aplikasi/aplikasi/_admincab/pelanggan_kategori.php`

**Perubahan:**
- Menggunakan tabel `tbmaster_kategori_member` (bukan `master_kategori_member`)
- Menghapus tab "Berdasarkan Kunjungan" (hanya nominal)
- Menghapus field: `tipe_kategori`, `icon`, `warna`, `is_active`
- Field yang digunakan: `status_member`, `min_nominal`, `max_nominal`, `diskon_persen`, `benefit`, `urutan`
- Form lebih sederhana dan fokus pada pengaturan threshold nominal

### 2. **File Pencarian Pelanggan**
- ✅ `servis-carinopol.php`
- Lokasi: `aplikasi/aplikasi/_admincab/servis-carinopol.php`

**Perubahan:**
```php
// SEBELUM:
LEFT JOIN tblpelanggangrup pg ON p.kgrup = pg.kgrup
COALESCE(pg.grup, '-') as grup_pelanggan

// SESUDAH:
LEFT JOIN statistik_pelanggan s ON p.nopelanggan = s.no_pelanggan
COALESCE(s.status_member, 'Bronze') as kategori_member
```

**Tampilan:**
- Label kolom: "Grup" → "Kategori Member"
- Badge: Menampilkan emoji (🥉 Bronze, 🥈 Silver, 🥇 Gold, 💎 Platinum)
- Warna badge disesuaikan dengan level member

### 3. **File List Pelanggan**
- ✅ `pelanggan.php`
- Lokasi: `aplikasi/aplikasi/_admincab/pelanggan.php`

**Perubahan:**
- Menggunakan view `view_cari_pelanggan` yang sudah diupdate
- View otomatis menampilkan `status_member` dan `diskon_persen` dari `statistik_pelanggan`

---

## 🗄️ Perubahan Database

### 1. **View Diupdate**
File: `update_view_cari_pelanggan.sql`

```sql
CREATE VIEW view_cari_pelanggan AS
SELECT 
    p.*,
    COALESCE(s.status_member, 'Bronze') AS status_member,
    COALESCE(s.diskon_persen, 0) AS diskon_persen,
    COALESCE(s.total_transaksi, 0) AS total_transaksi,
    COALESCE(s.total_nominal, 0) AS total_nominal,
    CASE 
        WHEN s.status_member = 'Platinum' THEN 'MEMBER PLATINUM'
        WHEN s.status_member = 'Gold' THEN 'MEMBER GOLD'
        WHEN s.status_member = 'Silver' THEN 'MEMBER SILVER'
        WHEN s.status_member = 'Bronze' THEN 'MEMBER BRONZE'
        ELSE 'BENGKEL'
    END AS grup
FROM tblpelanggan p
LEFT JOIN statistik_pelanggan s ON p.nopelanggan = s.no_pelanggan;
```

### 2. **Tabel yang Digunakan**

#### Tabel Utama:
- ✅ `tbmaster_kategori_member` - Master kategori (Bronze, Silver, Gold, Platinum)
- ✅ `statistik_pelanggan` - Data statistik pelanggan (auto-update via trigger)
- ✅ `tblpelanggan` - Master data pelanggan

#### Tabel Deprecated:
- ⚠️ `tblpelanggangrup` - Sudah tidak digunakan (di-backup sebagai `tblpelanggangrup_backup_20251105`)

---

## 🔄 Mapping Kategori

### Sistem Lama → Sistem Baru

| Sistem Lama (tblpelanggangrup) | Sistem Baru (statistik_pelanggan) | Threshold | Diskon |
|--------------------------------|-----------------------------------|-----------|--------|
| BENGKEL (kgrup='001') | Bronze | < Rp 2 juta | 0% |
| MEMBER SILVER (kgrup='003') | Silver | Rp 2-5 juta | 5% |
| MEMBER GOLD (kgrup='002') | Gold | Rp 5-10 juta | 10% |
| - | Platinum | >= Rp 10 juta | 15% |

---

## 🎨 Tampilan UI

### Badge Kategori Member

```
🥉 BRONZE   - Label primary (biru)
🥈 SILVER   - Label default (abu-abu)
🥇 GOLD     - Label warning (kuning)
💎 PLATINUM - Label inverse (hitam)
```

### Contoh Tampilan di servis-carinopol.php:

**Sebelum:**
```
Grup: MEMBER GOLD
```

**Sesudah:**
```
Kategori Member: 🥇 GOLD
```

---

## 📊 Alur Kerja Baru

### 1. **Halaman Kategori Member** (`pelanggan_kategori.php`)

**Fungsi:**
- Mengelola master kategori member (Bronze, Silver, Gold, Platinum)
- Mengatur threshold nominal untuk setiap level
- Mengatur diskon per level
- Mengatur benefit per level

**Akses:**
- Menu: Master Data → Kategori Member Pelanggan

### 2. **Pencarian Pelanggan** (`servis-carinopol.php`)

**Fungsi:**
- Mencari pelanggan berdasarkan no polisi/nama/telepon
- Menampilkan kategori member pelanggan
- Badge kategori member dengan emoji dan warna

### 3. **List Pelanggan** (`pelanggan.php`)

**Fungsi:**
- Menampilkan daftar semua pelanggan
- Menampilkan kategori member dari view
- Filter dan sorting pelanggan

---

## 🚀 Cara Implementasi

### Step 1: Jalankan Script SQL

```bash
# 1. Update view
mysql -u root -p fitmotor_dbbengkel < update_view_cari_pelanggan.sql

# 2. Jika belum, jalankan migrasi kategori member
mysql -u root -p fitmotor_dbbengkel < migrasi_kategori_ke_statistik.sql
```

### Step 2: Verifikasi

```sql
-- Cek view
SELECT * FROM view_cari_pelanggan LIMIT 10;

-- Cek kategori member
SELECT * FROM tbmaster_kategori_member ORDER BY urutan;

-- Cek statistik pelanggan
SELECT 
    no_pelanggan,
    status_member,
    diskon_persen,
    total_transaksi,
    total_nominal
FROM statistik_pelanggan
ORDER BY total_nominal DESC
LIMIT 10;
```

### Step 3: Test Halaman

1. **Test Halaman Kategori Member:**
   - Buka: `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/pelanggan_kategori.php`
   - Cek apakah data kategori tampil
   - Test tambah/edit/hapus kategori

2. **Test Pencarian Pelanggan:**
   - Buka: `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/servis-carinopol.php`
   - Cari pelanggan
   - Cek apakah kategori member tampil dengan benar

3. **Test List Pelanggan:**
   - Buka: `http://localhost/web-bengkel/aplikasi/aplikasi/_admincab/pelanggan.php`
   - Cek apakah kolom grup menampilkan kategori member

---

## ⚠️ Catatan Penting

### Backward Compatibility

View `view_cari_pelanggan` tetap menyediakan kolom `grup` untuk backward compatibility:
- Mapping otomatis dari `status_member` ke text grup
- Aplikasi lama yang menggunakan field `grup` tetap berfungsi

### Field `kgrup` di tblpelanggan

Field `kgrup` di `tblpelanggan` akan di-sync otomatis oleh trigger:
```sql
-- Mapping di trigger:
Bronze   → kgrup = '001'
Silver   → kgrup = '003'
Gold     → kgrup = '002'
Platinum → kgrup = '004'
```

### Data Lama

- Tabel `tblpelanggangrup` di-backup sebagai `tblpelanggangrup_backup_20251105`
- Data tidak hilang, hanya tidak digunakan lagi
- Bisa di-restore jika diperlukan

---

## 🐛 Troubleshooting

### Problem 1: Kategori member tidak tampil

**Solusi:**
```sql
-- Jalankan recalculate
CALL sp_recalculate_statistik_pelanggan();
```

### Problem 2: View error

**Solusi:**
```sql
-- Drop dan recreate view
DROP VIEW IF EXISTS view_cari_pelanggan;
-- Jalankan ulang script update_view_cari_pelanggan.sql
```

### Problem 3: Badge tidak tampil dengan benar

**Solusi:**
- Clear browser cache
- Cek encoding file (harus UTF-8)
- Pastikan emoji support di browser

---

## 📞 Support

Jika ada masalah atau pertanyaan:
- Email: dev@fitmotor.com
- WhatsApp: 081234567890

---

## 📅 Changelog

### Version 1.0 (5 November 2025)
- ✅ Rename `master_kategori_member.php` → `pelanggan_kategori.php`
- ✅ Update query di `servis-carinopol.php` untuk menggunakan `statistik_pelanggan`
- ✅ Update view `view_cari_pelanggan` untuk backward compatibility
- ✅ Simplify form kategori member (hapus field yang tidak perlu)
- ✅ Update tampilan badge kategori member dengan emoji
- ✅ Dokumentasi lengkap perubahan

---

**Selesai!** Sistem kategori member pelanggan sudah terupdate dan siap digunakan. 🎉
