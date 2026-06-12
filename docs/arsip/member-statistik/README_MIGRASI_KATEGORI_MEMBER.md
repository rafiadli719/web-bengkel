# 📘 PANDUAN MIGRASI: KATEGORI & GRUP PELANGGAN

## 🎯 Tujuan Migrasi

Menggabungkan sistem lama (`tblpelanggangrup`) dengan sistem baru (`statistik_pelanggan`) untuk menciptakan **single source of truth** dalam pengelolaan kategori member dan diskon pelanggan.

---

## 📁 File-File yang Dibuat

### 1. Dokumentasi
- ✅ `ANALISA_KATEGORI_GRUP_PELANGGAN.md` - Analisa lengkap perbandingan sistem lama vs baru
- ✅ `README_MIGRASI_KATEGORI_MEMBER.md` - Panduan implementasi (file ini)

### 2. Database
- ✅ `migrasi_kategori_ke_statistik.sql` - Script SQL untuk migrasi database

### 3. PHP Helper Functions
- ✅ `aplikasi/aplikasi/_admincab/_include_kategori_member.php` - Helper functions
- ✅ `aplikasi/aplikasi/_admincab/ajax_get_diskon.php` - AJAX get diskon
- ✅ `aplikasi/aplikasi/_admincab/ajax_get_info_member.php` - AJAX get info member

### 4. Contoh Implementasi
- ✅ `CONTOH_IMPLEMENTASI_KATEGORI_MEMBER.php` - Contoh lengkap penggunaan

---

## 🚀 Langkah-Langkah Implementasi

### STEP 1: BACKUP DATABASE ⚠️

**SANGAT PENTING!** Backup database sebelum migrasi:

```bash
# Via command line
mysqldump -u root -p fitmotor_dbbengkel > backup_before_migration_$(date +%Y%m%d).sql

# Atau via phpMyAdmin:
# 1. Buka phpMyAdmin
# 2. Pilih database fitmotor_dbbengkel
# 3. Klik tab "Export"
# 4. Klik "Go"
```

---

### STEP 2: JALANKAN SCRIPT MIGRASI

```bash
# Via command line
mysql -u root -p fitmotor_dbbengkel < migrasi_kategori_ke_statistik.sql

# Atau via phpMyAdmin:
# 1. Buka phpMyAdmin
# 2. Pilih database fitmotor_dbbengkel
# 3. Klik tab "SQL"
# 4. Copy-paste isi file migrasi_kategori_ke_statistik.sql
# 5. Klik "Go"
```

**Script akan melakukan:**
1. ✅ Backup tabel `tblpelanggangrup` → `tblpelanggangrup_backup_20251105`
2. ✅ Tambah field `diskon_persen` di `statistik_pelanggan`
3. ✅ Buat tabel `tbmaster_kategori_member` dengan data master
4. ✅ Update trigger `trg_after_service_bayar` untuk include diskon
5. ✅ Buat stored procedure `sp_recalculate_statistik_pelanggan`
6. ✅ Buat view `view_pelanggan_lengkap`
7. ✅ Buat function helper `fn_get_diskon_pelanggan` dan `fn_get_status_member`
8. ✅ Recalculate semua data pelanggan existing
9. ✅ Tampilkan verifikasi hasil migrasi

---

### STEP 3: VERIFIKASI HASIL MIGRASI

Setelah script selesai, cek hasil migrasi:

```sql
-- 1. Cek jumlah pelanggan per status member
SELECT 
    status_member,
    COUNT(*) AS jumlah_pelanggan,
    AVG(diskon_persen) AS avg_diskon
FROM statistik_pelanggan
GROUP BY status_member;

-- 2. Cek top 5 pelanggan
SELECT 
    no_pelanggan,
    status_member,
    diskon_persen,
    total_transaksi,
    total_nominal
FROM statistik_pelanggan
ORDER BY total_nominal DESC
LIMIT 5;

-- 3. Cek trigger sudah ada
SHOW TRIGGERS LIKE 'tblservice';
-- Harusnya muncul: trg_after_service_bayar

-- 4. Cek tabel master kategori member
SELECT * FROM tbmaster_kategori_member ORDER BY urutan;
```

**Expected Output:**
```
status_member | jumlah_pelanggan | avg_diskon
--------------|------------------|------------
Bronze        | 150              | 0.00
Silver        | 45               | 5.00
Gold          | 12               | 10.00
Platinum      | 3                | 15.00
```

---

### STEP 4: UPDATE FILE PHP

#### A. Update File Servis Input

**File yang perlu diupdate:**
- `servis-input-reguler.php`
- `servis-input-jemput.php`
- `servis-input-antar.php`
- Dan file servis lainnya

**Langkah:**

1. **Include helper di bagian atas file:**

```php
<?php
// Di bagian atas file, setelah include koneksi
include "_koneksi.php";
include "_include_kategori_member.php"; // ← TAMBAHKAN INI
?>
```

2. **Tampilkan info member saat pelanggan dipilih:**

```php
<?php
// Setelah pelanggan dipilih (misal dari GET/POST)
$no_pelanggan = isset($_GET['nopel']) ? $_GET['nopel'] : '';

if (!empty($no_pelanggan)) {
    // Tampilkan info member
    echo displayInfoKategoriMemberCompact($koneksi, $no_pelanggan);
    
    // Atau tampilkan versi lengkap:
    // echo displayInfoKategoriMember($koneksi, $no_pelanggan);
}
?>
```

3. **Update form untuk hitung diskon otomatis:**

```php
<!-- Di bagian form input jasa/barang -->
<script>
function hitungTotal() {
    var no_pelanggan = document.getElementById('no_pelanggan').value;
    var total_jasa = parseFloat(document.getElementById('total_jasa').value) || 0;
    var total_barang = parseFloat(document.getElementById('total_barang').value) || 0;
    var subtotal = total_jasa + total_barang;
    
    if (no_pelanggan && subtotal > 0) {
        // AJAX get diskon
        fetch('ajax_get_diskon.php?no_pelanggan=' + no_pelanggan)
            .then(response => response.json())
            .then(data => {
                var diskon_persen = data.diskon_persen;
                var diskon_nominal = subtotal * (diskon_persen / 100);
                var total_bayar = subtotal - diskon_nominal;
                
                // Update display
                document.getElementById('display_diskon_persen').innerText = diskon_persen;
                document.getElementById('display_diskon_nominal').innerText = formatRupiah(diskon_nominal);
                document.getElementById('display_total_bayar').innerText = formatRupiah(total_bayar);
                
                // Update hidden fields
                document.getElementById('diskon_persen').value = diskon_persen;
                document.getElementById('diskon_nominal').value = diskon_nominal;
                document.getElementById('total_bayar').value = total_bayar;
            });
    }
}

function formatRupiah(angka) {
    return 'Rp ' + angka.toLocaleString('id-ID');
}
</script>
```

4. **Update proses pembayaran (TIDAK PERLU UBAH BANYAK!):**

```php
<?php
// Proses pembayaran tetap sama seperti sebelumnya
if (isset($_POST['btnbayar'])) {
    $no_service = $_POST['txtnosrv'];
    $total_akhir = $_POST['txtnet']; // Sudah include diskon dari form
    
    // Update status servis menjadi bayar
    $query = "UPDATE tblservice 
              SET status_servis = 'bayar',
                  total_akhir = '$total_akhir'
              WHERE no_service = '$no_service'";
    
    mysqli_query($koneksi, $query);
    
    // ⚡ TRIGGER OTOMATIS JALAN!
    // Statistik pelanggan otomatis update
    // Diskon otomatis tersimpan di statistik_pelanggan
    
    // Redirect
    echo "<script>window.location='servis-input-reguler.php?snoserv=$no_service';</script>";
}
?>
```

#### B. Update File List/Report Pelanggan

**File yang perlu diupdate:**
- `pelanggan-list.php`
- `pelanggan-detail.php`
- `report-pelanggan.php`

**Contoh update query:**

```php
<?php
// SEBELUM (sistem lama):
$query = "SELECT 
            p.nopelanggan,
            p.namapelanggan,
            p.kgrup,
            g.grup,
            g.diskon
          FROM tblpelanggan p
          LEFT JOIN tblpelanggangrup g ON p.kgrup = g.kgrup";

// SESUDAH (sistem baru):
$query = "SELECT 
            p.nopelanggan,
            p.namapelanggan,
            s.status_member,
            s.diskon_persen,
            s.total_transaksi,
            s.total_nominal
          FROM tblpelanggan p
          LEFT JOIN statistik_pelanggan s ON p.nopelanggan = s.no_pelanggan";

// Atau gunakan view yang sudah dibuat:
$query = "SELECT * FROM view_pelanggan_lengkap";
?>
```

---

### STEP 5: TESTING

#### A. Test Input Servis Baru

1. Buka halaman input servis
2. Pilih pelanggan
3. Cek apakah info member muncul
4. Input jasa/barang
5. Cek apakah diskon otomatis terhitung
6. Klik BAYAR
7. Cek database:

```sql
-- Cek tblservice
SELECT * FROM tblservice ORDER BY no_service DESC LIMIT 1;

-- Cek statistik_pelanggan terupdate
SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB';
```

#### B. Test Upgrade Status Member

1. Pilih pelanggan Bronze dengan total nominal mendekati Rp 2 juta
2. Input servis dengan nominal yang membuat total >= Rp 2 juta
3. Klik BAYAR
4. Cek apakah status member otomatis naik ke Silver:

```sql
SELECT 
    no_pelanggan,
    status_member,
    diskon_persen,
    total_nominal
FROM statistik_pelanggan
WHERE no_pelanggan = 'AD 1234 AB';
```

**Expected:** `status_member = 'Silver'` dan `diskon_persen = 5.00`

#### C. Test Diskon Teraplikasi

1. Input servis untuk pelanggan Silver (diskon 5%)
2. Subtotal: Rp 1.000.000
3. Expected diskon: Rp 50.000
4. Expected total bayar: Rp 950.000

---

### STEP 6: DEPLOY KE PRODUCTION

**Checklist sebelum deploy:**

- [ ] Backup database production
- [ ] Test semua fitur di development
- [ ] Verifikasi trigger jalan dengan baik
- [ ] Verifikasi diskon terhitung dengan benar
- [ ] Verifikasi status member update otomatis
- [ ] Informasikan ke tim kasir tentang perubahan
- [ ] Siapkan rollback plan jika ada masalah

**Rollback Plan (jika ada masalah):**

```sql
-- 1. Restore tabel lama
RENAME TABLE tblpelanggangrup_backup_20251105 TO tblpelanggangrup;

-- 2. Drop trigger baru
DROP TRIGGER IF EXISTS trg_after_service_bayar;

-- 3. Restore trigger lama (dari backup)
-- (jalankan script trigger lama)

-- 4. Restore database dari backup
-- mysql -u root -p fitmotor_dbbengkel < backup_before_migration.sql
```

---

## 📊 Monitoring Setelah Deploy

### Query untuk Monitoring

```sql
-- 1. Monitor distribusi status member
SELECT 
    status_member,
    COUNT(*) AS jumlah,
    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM statistik_pelanggan), 2) AS persentase
FROM statistik_pelanggan
GROUP BY status_member;

-- 2. Monitor total diskon yang diberikan (per hari)
SELECT 
    DATE(tanggal) AS tanggal,
    COUNT(*) AS jumlah_transaksi,
    SUM(diskon) AS total_diskon,
    SUM(total_akhir) AS total_revenue
FROM tblservice
WHERE status_servis = 'bayar'
AND DATE(tanggal) = CURDATE()
GROUP BY DATE(tanggal);

-- 3. Monitor pelanggan yang baru upgrade
SELECT 
    p.nopelanggan,
    p.namapelanggan,
    s.status_member,
    s.total_nominal,
    s.tanggal_terakhir_transaksi
FROM tblpelanggan p
JOIN statistik_pelanggan s ON p.nopelanggan = s.no_pelanggan
WHERE s.tanggal_terakhir_transaksi >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
AND s.status_member IN ('Silver', 'Gold', 'Platinum')
ORDER BY s.tanggal_terakhir_transaksi DESC;
```

---

## ❓ FAQ & Troubleshooting

### Q1: Trigger tidak jalan setelah migrasi?

**A:** Cek apakah trigger sudah ada:

```sql
SHOW TRIGGERS LIKE 'tblservice';
```

Jika tidak ada, jalankan ulang bagian CREATE TRIGGER dari file migrasi.

---

### Q2: Diskon tidak muncul di form input servis?

**A:** Cek beberapa hal:

1. Apakah file `_include_kategori_member.php` sudah di-include?
2. Apakah AJAX `ajax_get_diskon.php` bisa diakses?
3. Cek console browser untuk error JavaScript
4. Cek apakah pelanggan sudah ada di `statistik_pelanggan`

---

### Q3: Status member tidak update setelah transaksi?

**A:** Cek trigger:

```sql
-- Test trigger manual
UPDATE tblservice 
SET status_servis = 'bayar', total_akhir = 2000000 
WHERE no_service = 'SV25000000001';

-- Cek apakah statistik_pelanggan terupdate
SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB';
```

---

### Q4: Bagaimana cara ubah threshold status member?

**A:** Update tabel master:

```sql
-- Misal: Ubah threshold Silver jadi Rp 3 juta
UPDATE tbmaster_kategori_member
SET min_nominal = 3000000,
    max_nominal = 5999999.99
WHERE status_member = 'Silver';

-- Recalculate semua pelanggan
CALL sp_recalculate_statistik_pelanggan();
```

---

### Q5: Bagaimana cara ubah persentase diskon?

**A:** Update tabel master:

```sql
-- Misal: Ubah diskon Gold jadi 12%
UPDATE tbmaster_kategori_member
SET diskon_persen = 12.00
WHERE status_member = 'Gold';

-- Recalculate semua pelanggan
CALL sp_recalculate_statistik_pelanggan();
```

---

## 🎉 Benefit Setelah Migrasi

### Untuk Kasir:
- ✅ Tidak perlu ingat update grup pelanggan manual
- ✅ Diskon otomatis muncul saat input servis
- ✅ Lihat status member real-time
- ✅ Workflow tetap sama, tidak ada perubahan besar

### Untuk Pelanggan:
- ✅ Otomatis naik level saat transaksi mencapai threshold
- ✅ Diskon yang adil dan konsisten
- ✅ Transparansi progress ke level berikutnya
- ✅ Benefit yang jelas per level

### Untuk Owner:
- ✅ Data akurat dan real-time
- ✅ Laporan statistik lengkap
- ✅ Mudah analisa pelanggan VIP
- ✅ Sistem loyalty program yang jelas
- ✅ Tidak perlu maintenance manual

---

## 📞 Support

Jika ada pertanyaan atau masalah saat implementasi, silakan hubungi:

**Fit Motor Development Team**
- Email: dev@fitmotor.com
- WhatsApp: 081234567890

---

## 📝 Changelog

### Version 1.0 (5 November 2025)
- ✅ Initial release
- ✅ Migrasi dari tblpelanggangrup ke statistik_pelanggan
- ✅ Tambah field diskon_persen
- ✅ Buat tabel tbmaster_kategori_member
- ✅ Update trigger untuk include diskon
- ✅ Buat helper functions PHP
- ✅ Buat AJAX endpoints
- ✅ Buat contoh implementasi

---

**Selamat menggunakan sistem kategori member yang baru! 🎉**
