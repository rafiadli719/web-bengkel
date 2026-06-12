# DOKUMENTASI SISTEM STATISTIK PELANGGAN OTOMATIS

## 📋 Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Arsitektur Sistem](#arsitektur-sistem)
3. [Instalasi](#instalasi)
4. [Struktur Database](#struktur-database)
5. [Cara Kerja Trigger](#cara-kerja-trigger)
6. [View dan Query](#view-dan-query)
7. [Integrasi PHP](#integrasi-php)
8. [Otomasi WhatsApp](#otomasi-whatsapp)
9. [Testing](#testing)
10. [Troubleshooting](#troubleshooting)

---

## 🎯 Pengenalan

Sistem Statistik Pelanggan Otomatis adalah solusi modern untuk tracking dan analisis perilaku pelanggan secara real-time. Sistem ini menggunakan:
- **Trigger MySQL** untuk update otomatis
- **View** untuk query yang optimal
- **Event Scheduler** untuk maintenance harian
- **WhatsApp API** untuk komunikasi pelanggan

### Fitur Utama:
✅ Update statistik otomatis saat transaksi  
✅ Klasifikasi member (Bronze, Silver, Gold, Platinum)  
✅ Deteksi pelanggan yang perlu follow-up  
✅ Otomasi pengiriman WhatsApp  
✅ Dashboard real-time  
✅ Top pelanggan ranking  

---

## 🏗️ Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────┐
│                    TRANSAKSI PELANGGAN                       │
│                  (tblservice status = bayar)                 │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              TRIGGER: trg_after_service_bayar                │
│  • Hitung total transaksi                                    │
│  • Hitung total nominal                                      │
│  • Tentukan status member                                    │
│  • Update/Insert ke statistik_pelanggan                      │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              TABLE: statistik_pelanggan                      │
│  • id_statistik (PK)                                         │
│  • no_pelanggan (FK)                                         │
│  • total_transaksi, total_nominal                            │
│  • status_member, tanggal_terakhir_transaksi                 │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                   VIEW & DASHBOARD                           │
│  • view_statistik_pelanggan                                  │
│  • view_pelanggan_follow_up                                  │
│  • view_top_pelanggan                                        │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│              WHATSAPP AUTOMATION                             │
│  • Ucapan terima kasih                                       │
│  • Reminder follow-up                                        │
│  • Broadcast promosi                                         │
└─────────────────────────────────────────────────────────────┘
```

---

## 📦 Instalasi

### Langkah 1: Import Database

```bash
# Masuk ke MySQL
mysql -u root -p fitmotor_dbbengkel

# Import file SQL
source /path/to/database_statistik_pelanggan_otomatis.sql
```

### Langkah 2: Aktifkan Event Scheduler

```sql
-- Cek status event scheduler
SHOW VARIABLES LIKE 'event_scheduler';

-- Aktifkan event scheduler
SET GLOBAL event_scheduler = ON;

-- Tambahkan ke my.ini/my.cnf untuk permanen
[mysqld]
event_scheduler = ON
```

### Langkah 3: Refresh Statistik Awal

```sql
-- Jalankan stored procedure untuk generate statistik dari data existing
CALL sp_refresh_statistik_pelanggan();

-- Verifikasi hasil
SELECT COUNT(*) as total_pelanggan FROM statistik_pelanggan;
SELECT * FROM view_statistik_pelanggan LIMIT 10;
```

### Langkah 4: Copy File PHP

```bash
# Copy file dashboard
cp statistik_pelanggan_dashboard.php /path/to/_admincab/

# Copy class WhatsApp
cp class_whatsapp_automation.php /path/to/_admincab/

# Copy template
cp _template/_statistik_*.php /path/to/_admincab/_template/
```

---

## 🗄️ Struktur Database

### Tabel: `statistik_pelanggan`

| Kolom | Tipe | Deskripsi |
|-------|------|-----------|
| `id_statistik` | INT(11) AUTO_INCREMENT | Primary Key |
| `no_pelanggan` | VARCHAR(20) | Foreign Key ke tblpelanggan |
| `total_transaksi` | INT(11) | Jumlah total transaksi |
| `total_nominal` | DECIMAL(15,2) | Total nilai transaksi (Rp) |
| `jumlah_kunjungan` | INT(11) | Jumlah kunjungan |
| `rata_rata_transaksi` | DECIMAL(15,2) | Rata-rata per transaksi |
| `status_member` | ENUM | Bronze, Silver, Gold, Platinum |
| `tanggal_pertama_transaksi` | DATE | Tanggal transaksi pertama |
| `tanggal_terakhir_transaksi` | DATE | Tanggal transaksi terakhir |
| `lama_tidak_datang` | INT(11) | Hari sejak transaksi terakhir |
| `lama_menjadi_pelanggan` | INT(11) | Hari sejak transaksi pertama |
| `estimasi_datang_berikutnya` | DATE | Estimasi (30 hari dari terakhir) |
| `total_motor` | INT(11) | Jumlah motor yang diservice |

### Klasifikasi Status Member

| Status | Kriteria Total Nominal |
|--------|------------------------|
| **Bronze** | < Rp 2.000.000 |
| **Silver** | Rp 2.000.000 - Rp 4.999.999 |
| **Gold** | Rp 5.000.000 - Rp 9.999.999 |
| **Platinum** | ≥ Rp 10.000.000 |

---

## ⚙️ Cara Kerja Trigger

### Trigger: `trg_after_service_bayar`

**Kapan Dijalankan:**
- Setelah UPDATE pada tabel `tblservice`
- Hanya jika `status_servis` berubah menjadi `'bayar'`
- Dan `total_akhir` > 0

**Proses:**

1. **Hitung Statistik**
   ```sql
   SELECT 
       COUNT(*) AS total_transaksi,
       SUM(total_akhir) AS total_nominal,
       AVG(total_akhir) AS rata_rata,
       MIN(tanggal) AS tanggal_pertama,
       MAX(tanggal) AS tanggal_terakhir
   FROM tblservice
   WHERE no_pelanggan = NEW.no_pelanggan 
   AND status_servis = 'bayar'
   ```

2. **Tentukan Status Member**
   ```sql
   IF total_nominal < 2000000 THEN 'Bronze'
   ELSEIF total_nominal < 5000000 THEN 'Silver'
   ELSEIF total_nominal < 10000000 THEN 'Gold'
   ELSE 'Platinum'
   ```

3. **Update atau Insert**
   - Jika pelanggan sudah ada → UPDATE
   - Jika pelanggan baru → INSERT

4. **Update Kategori di tblpelanggan**
   ```sql
   UPDATE tblpelanggan
   SET kgrup = CASE status_member
       WHEN 'Bronze' THEN 'B'
       WHEN 'Silver' THEN 'S'
       WHEN 'Gold' THEN 'G'
       WHEN 'Platinum' THEN 'P'
   END
   ```

### Event Scheduler: `evt_update_lama_tidak_datang`

**Jadwal:** Setiap hari pukul 00:00

**Fungsi:** Update kolom `lama_tidak_datang` dan `lama_menjadi_pelanggan`

```sql
UPDATE statistik_pelanggan
SET 
    lama_tidak_datang = DATEDIFF(CURDATE(), tanggal_terakhir_transaksi),
    lama_menjadi_pelanggan = DATEDIFF(CURDATE(), tanggal_pertama_transaksi)
```

---

## 📊 View dan Query

### 1. view_statistik_pelanggan

**Fungsi:** Menampilkan semua statistik pelanggan dengan informasi lengkap

**Kolom Tambahan:**
- `status_pelanggan`: Aktif, Perlu Follow Up, Tidak Aktif, Hilang
- `tiap_30_hari`: Perhitungan interval 30 hari
- `badge_color`: Warna badge untuk UI

**Contoh Query:**
```sql
-- Lihat semua pelanggan
SELECT * FROM view_statistik_pelanggan;

-- Filter by status member
SELECT * FROM view_statistik_pelanggan 
WHERE status_member = 'Gold';

-- Pelanggan aktif (< 30 hari tidak datang)
SELECT * FROM view_statistik_pelanggan 
WHERE lama_tidak_datang_hari <= 30;
```

### 2. view_pelanggan_follow_up

**Fungsi:** Daftar pelanggan yang perlu di-follow up (> 30 hari tidak datang)

**Kolom Tambahan:**
- `prioritas_follow_up`: Urgent, High, Medium, Low
- `template_pesan_wa`: Template pesan WhatsApp siap pakai

**Contoh Query:**
```sql
-- Pelanggan prioritas urgent
SELECT * FROM view_pelanggan_follow_up 
WHERE prioritas_follow_up = 'Urgent';

-- Top 10 pelanggan Gold yang perlu follow up
SELECT * FROM view_pelanggan_follow_up 
WHERE status_member = 'Gold'
ORDER BY hari_tidak_datang DESC
LIMIT 10;
```

### 3. view_top_pelanggan

**Fungsi:** Ranking 100 pelanggan terbaik berdasarkan total nominal

**Contoh Query:**
```sql
-- Top 10 pelanggan
SELECT * FROM view_top_pelanggan LIMIT 10;

-- Top pelanggan per member status
SELECT status_member, COUNT(*) as jumlah
FROM view_top_pelanggan
GROUP BY status_member;
```

---

## 💻 Integrasi PHP

### Contoh 1: Tampilkan Statistik di Dashboard

```php
<?php
include "../config/koneksi.php";

// Get summary
$query = "SELECT 
    COUNT(*) as total_pelanggan,
    SUM(total_nominal) as total_pendapatan,
    AVG(rata_rata_transaksi) as avg_transaksi
FROM statistik_pelanggan";

$result = mysqli_query($koneksi, $query);
$summary = mysqli_fetch_array($result);

echo "Total Pelanggan: " . $summary['total_pelanggan'];
echo "Total Pendapatan: Rp " . number_format($summary['total_pendapatan']);
?>
```

### Contoh 2: Cek Status Member Pelanggan

```php
<?php
function getStatusMember($koneksi, $no_pelanggan) {
    $query = "SELECT status_member, total_nominal, total_transaksi
              FROM statistik_pelanggan
              WHERE no_pelanggan = '$no_pelanggan'";
    
    $result = mysqli_query($koneksi, $query);
    
    if($row = mysqli_fetch_array($result)) {
        return [
            'status' => $row['status_member'],
            'total' => $row['total_nominal'],
            'transaksi' => $row['total_transaksi']
        ];
    }
    
    return ['status' => 'Bronze', 'total' => 0, 'transaksi' => 0];
}

// Penggunaan
$member_info = getStatusMember($koneksi, 'AD 1234 AB');
echo "Status: " . $member_info['status'];
?>
```

### Contoh 3: Trigger Manual Refresh

```php
<?php
if(isset($_POST['btn_refresh'])) {
    // Refresh semua statistik
    mysqli_query($koneksi, "CALL sp_refresh_statistik_pelanggan()");
    echo "Statistik berhasil di-refresh!";
}
?>
```

---

## 📱 Otomasi WhatsApp

### Setup WhatsApp API

**Pilihan API:**
1. **Fonnte** (https://fonnte.com) - Recommended
2. **Wablas** (https://wablas.com)
3. **WooWA** (https://woowa.id)

**Konfigurasi:**
```php
<?php
// config_whatsapp.php
define('WA_API_KEY', 'your_api_key_here');
define('WA_API_URL', 'https://api.fonnte.com/send');
?>
```

### Contoh 1: Kirim Terima Kasih Otomatis

```php
<?php
include "class_whatsapp_automation.php";
include "config_whatsapp.php";

// Inisialisasi
$wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);

// Kirim setelah pembayaran
if(isset($_POST['btnbayar'])) {
    $no_service = $_POST['txtnosrv'];
    
    // Proses pembayaran...
    // ...
    
    // Kirim WhatsApp
    $result = $wa->sendTerimaKasih($no_service);
    
    if($result['success']) {
        echo "Pesan terima kasih berhasil dikirim!";
    } else {
        // Jika gagal, buka WhatsApp Web
        echo "<a href='{$result['wa_link']}' target='_blank'>Kirim Manual</a>";
    }
}
?>
```

### Contoh 2: Broadcast Follow Up Harian

```php
<?php
// cron_followup_harian.php
// Jalankan via cron job setiap hari

include "class_whatsapp_automation.php";
include "config_whatsapp.php";

$wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);

// Broadcast ke pelanggan yang > 30 hari tidak datang
$result = $wa->broadcastFollowUp();

// Log hasil
$log = date('Y-m-d H:i:s') . " - Broadcast: {$result['sent']} sent, {$result['failed']} failed\n";
file_put_contents('log_broadcast.txt', $log, FILE_APPEND);

echo "Broadcast selesai: {$result['sent']} terkirim, {$result['failed']} gagal";
?>
```

### Contoh 3: Kirim Manual dari Dashboard

```php
<?php
// statistik_pelanggan_send_wa.php
$no_pelanggan = $_GET['nopelanggan'];

$wa = new WhatsAppAutomation($koneksi);
$result = $wa->sendReminderFollowUp($no_pelanggan);

if($result['success']) {
    // Redirect ke WhatsApp Web
    header("Location: " . $result['wa_link']);
} else {
    echo "Error: " . $result['message'];
}
?>
```

---

## 🧪 Testing

### Test 1: Trigger Berfungsi

```sql
-- 1. Cek data sebelum
SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB';

-- 2. Update status service menjadi bayar
UPDATE tblservice 
SET status_servis = 'bayar', total_akhir = 500000
WHERE no_service = 'SV25000000001';

-- 3. Cek data setelah (harus otomatis update)
SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB';
```

### Test 2: Status Member Berubah

```sql
-- Simulasi transaksi hingga Gold
UPDATE tblservice 
SET status_servis = 'bayar', total_akhir = 6000000
WHERE no_service = 'SV25000000001';

-- Cek status member (harus jadi Gold)
SELECT no_pelanggan, status_member, total_nominal 
FROM statistik_pelanggan 
WHERE no_pelanggan = 'AD 1234 AB';
```

### Test 3: View Berfungsi

```sql
-- Test view statistik
SELECT * FROM view_statistik_pelanggan LIMIT 5;

-- Test view follow up
SELECT * FROM view_pelanggan_follow_up LIMIT 5;

-- Test view top pelanggan
SELECT * FROM view_top_pelanggan LIMIT 5;
```

### Test 4: WhatsApp Automation

```php
<?php
// test_whatsapp.php
include "class_whatsapp_automation.php";

$wa = new WhatsAppAutomation($koneksi);

// Test kirim terima kasih
$result = $wa->sendTerimaKasih('SV25000000001');
print_r($result);

// Test kirim reminder
$result = $wa->sendReminderFollowUp('AD 1234 AB');
print_r($result);
?>
```

---

## 🔧 Troubleshooting

### Problem 1: Trigger Tidak Jalan

**Gejala:** Statistik tidak update otomatis

**Solusi:**
```sql
-- Cek trigger ada
SHOW TRIGGERS LIKE 'tblservice';

-- Cek error log
SHOW ERRORS;

-- Re-create trigger
DROP TRIGGER IF EXISTS trg_after_service_bayar;
-- Lalu jalankan ulang CREATE TRIGGER
```

### Problem 2: Event Scheduler Tidak Aktif

**Gejala:** Lama tidak datang tidak update harian

**Solusi:**
```sql
-- Cek status
SHOW VARIABLES LIKE 'event_scheduler';

-- Aktifkan
SET GLOBAL event_scheduler = ON;

-- Cek event
SHOW EVENTS;
```

### Problem 3: View Kosong

**Gejala:** View tidak menampilkan data

**Solusi:**
```sql
-- Refresh statistik manual
CALL sp_refresh_statistik_pelanggan();

-- Cek data di tabel
SELECT COUNT(*) FROM statistik_pelanggan;

-- Re-create view
DROP VIEW IF EXISTS view_statistik_pelanggan;
-- Lalu jalankan ulang CREATE VIEW
```

### Problem 4: WhatsApp Tidak Terkirim

**Gejala:** Pesan WhatsApp gagal

**Solusi:**
1. Cek API Key valid
2. Cek format nomor telepon (harus 628xxx)
3. Cek saldo API
4. Gunakan mode manual (WhatsApp Web link)

```php
// Debug mode
$result = $wa->sendTerimaKasih($no_service);
echo "<pre>";
print_r($result);
echo "</pre>";
```

---

## 📈 Maintenance

### Backup Harian

```bash
# Backup tabel statistik
mysqldump -u root -p fitmotor_dbbengkel statistik_pelanggan > backup_statistik_$(date +%Y%m%d).sql
```

### Monitoring Performa

```sql
-- Cek ukuran tabel
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'fitmotor_dbbengkel'
AND table_name = 'statistik_pelanggan';

-- Cek jumlah record
SELECT COUNT(*) as total FROM statistik_pelanggan;

-- Cek last update
SELECT MAX(updated_at) as last_update FROM statistik_pelanggan;
```

### Cleanup Data Lama

```sql
-- Hapus pelanggan yang tidak pernah transaksi lagi (> 2 tahun)
DELETE FROM statistik_pelanggan
WHERE lama_tidak_datang > 730;
```

---

## 📞 Support

Jika ada pertanyaan atau masalah:
1. Cek dokumentasi ini terlebih dahulu
2. Lihat log error di MySQL
3. Test dengan data sample
4. Hubungi developer

---

**Dibuat:** 2 November 2025  
**Versi:** 1.0  
**Developer:** Fit Motor Development Team
