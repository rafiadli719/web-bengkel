# PROSES INPUT SERVIS KE STATISTIK PELANGGAN

## 📋 Daftar Isi
1. [Pengenalan](#pengenalan)
2. [Alur Proses Lengkap](#alur-proses-lengkap)
3. [Trigger Otomatis](#trigger-otomatis)
4. [Implementasi di Halaman Servis](#implementasi-di-halaman-servis)
5. [Notifikasi WhatsApp Otomatis](#notifikasi-whatsapp-otomatis)
6. [Testing](#testing)

---

## 🎯 Pengenalan

Sistem statistik pelanggan bekerja secara **OTOMATIS** menggunakan **MySQL Trigger**. Artinya, kasir/admin **TIDAK PERLU** melakukan input manual untuk update statistik. Semua proses berjalan di background saat transaksi selesai.

### Konsep Dasar:
```
Input Servis → Proses Service → Klik BAYAR → Trigger Jalan → Statistik Update Otomatis
```

**Tidak ada perubahan workflow kasir!** Kasir tetap input servis seperti biasa, sistem otomatis update statistik di background.

---

## 🔄 Alur Proses Lengkap

### Diagram Alur:

```
┌─────────────────────────────────────────────────────────────────┐
│  STEP 1: KASIR INPUT DATA SERVIS                                │
│  • Pilih pelanggan                                               │
│  • Input detail service (jasa, barang, mekanik)                  │
│  • Hitung total                                                  │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 2: KASIR KLIK TOMBOL "BAYAR"                              │
│  • Status servis berubah dari 'selesai' → 'bayar'               │
│  • Data disimpan ke tblservice                                   │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 3: TRIGGER OTOMATIS JALAN (trg_after_service_bayar)       │
│  • Deteksi perubahan status_servis = 'bayar'                    │
│  • Hitung total transaksi pelanggan                              │
│  • Hitung total nominal                                          │
│  • Tentukan status member (Bronze/Silver/Gold/Platinum)          │
│  • Update/Insert ke tabel statistik_pelanggan                    │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 4: STATISTIK PELANGGAN TERUPDATE                          │
│  • Total transaksi bertambah                                     │
│  • Total nominal bertambah                                       │
│  • Status member mungkin naik (Bronze → Silver → Gold)           │
│  • Tanggal terakhir transaksi update                             │
│  • Estimasi datang berikutnya dihitung (30 hari)                 │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────────┐
│  STEP 5: NOTIFIKASI WHATSAPP OTOMATIS (OPSIONAL)                │
│  • Kirim ucapan terima kasih                                     │
│  • Info garansi service 30 hari                                  │
│  • Info status member & benefit                                  │
│  • Reminder service berikutnya                                   │
└─────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ Trigger Otomatis

### Kapan Trigger Jalan?

Trigger `trg_after_service_bayar` akan otomatis jalan ketika:

1. ✅ Ada UPDATE pada tabel `tblservice`
2. ✅ Kolom `status_servis` berubah menjadi `'bayar'`
3. ✅ Kolom `total_akhir` > 0

**Contoh Kode yang Memicu Trigger:**

```php
// Ketika kasir klik tombol BAYAR
$query = "UPDATE tblservice 
          SET status_servis = 'bayar',
              bayar = '$bayar',
              kembali = '$kembali',
              total_akhir = '$total_akhir'
          WHERE no_service = '$no_service'";

mysqli_query($koneksi, $query);

// TRIGGER OTOMATIS JALAN DI SINI! (tidak perlu kode tambahan)
```

### Apa yang Dilakukan Trigger?

```sql
-- 1. Hitung statistik dari semua transaksi pelanggan
SELECT 
    COUNT(*) AS total_transaksi,
    SUM(total_akhir) AS total_nominal,
    AVG(total_akhir) AS rata_rata,
    MIN(tanggal) AS tanggal_pertama,
    MAX(tanggal) AS tanggal_terakhir,
    COUNT(DISTINCT no_polisi) AS total_motor
FROM tblservice
WHERE no_pelanggan = 'AD 1234 AB' 
AND status_servis = 'bayar';

-- 2. Tentukan status member
IF total_nominal < 2000000 THEN 'Bronze'
ELSEIF total_nominal < 5000000 THEN 'Silver'
ELSEIF total_nominal < 10000000 THEN 'Gold'
ELSE 'Platinum'

-- 3. Update atau Insert ke statistik_pelanggan
INSERT INTO statistik_pelanggan (...) VALUES (...)
ON DUPLICATE KEY UPDATE ...
```

### Klasifikasi Status Member:

| Total Nominal Transaksi | Status Member | Benefit |
|-------------------------|---------------|---------|
| < Rp 2.000.000 | 🥉 **Bronze** | Member standar |
| Rp 2.000.000 - Rp 4.999.999 | 🥈 **Silver** | Diskon 10%, Prioritas antrian |
| Rp 5.000.000 - Rp 9.999.999 | 🥇 **Gold** | Diskon 15%, Prioritas, Gratis cuci |
| ≥ Rp 10.000.000 | 💎 **Platinum** | Diskon 20%, VIP, Gratis cuci & oli, Jemput antar gratis |

---

## 💻 Implementasi di Halaman Servis

### File yang Perlu Dimodifikasi:

1. ✅ `servis-input-reguler.php`
2. ✅ `servis-input-reguler-rst.php`
3. ✅ `servis-input-reguler-jemput.php`
4. ✅ `servis-input-reguler-jemput-rst.php`

### Modifikasi yang Diperlukan:

#### **1. Tampilkan Status Member Pelanggan (Opsional)**

Tambahkan di bagian informasi pelanggan untuk menampilkan status member saat ini:

```php
<?php
// Get status member pelanggan
$query_member = "SELECT status_member, total_nominal, total_transaksi 
                 FROM statistik_pelanggan 
                 WHERE no_pelanggan = '$no_pelanggan'";
$result_member = mysqli_query($koneksi, $query_member);

if($row_member = mysqli_fetch_array($result_member)) {
    $status_member = $row_member['status_member'];
    $total_nominal = $row_member['total_nominal'];
    $total_transaksi = $row_member['total_transaksi'];
} else {
    $status_member = 'Bronze'; // Default untuk pelanggan baru
    $total_nominal = 0;
    $total_transaksi = 0;
}

// Tentukan warna badge
$badge_color = '';
switch($status_member) {
    case 'Bronze': $badge_color = '#CD7F32'; break;
    case 'Silver': $badge_color = '#C0C0C0'; break;
    case 'Gold': $badge_color = '#FFD700'; break;
    case 'Platinum': $badge_color = '#E5E4E2'; break;
}
?>

<!-- Tampilkan di UI -->
<div class="alert alert-info">
    <i class="fa fa-trophy"></i>
    <strong>Status Member:</strong> 
    <span style="background: <?php echo $badge_color; ?>; color: #fff; padding: 4px 12px; border-radius: 12px;">
        <?php echo $status_member; ?>
    </span>
    <br>
    <small>
        Total Transaksi: <?php echo $total_transaksi; ?>x | 
        Total Nominal: Rp <?php echo number_format($total_nominal, 0, ',', '.'); ?>
    </small>
</div>
```

#### **2. Notifikasi WhatsApp Setelah Pembayaran (Opsional)**

Tambahkan setelah proses pembayaran berhasil:

```php
<?php
// Setelah update status_servis = 'bayar'
if(isset($_POST['btnbayar'])) {
    $no_service = $_POST['txtnosrv'];
    
    // Proses pembayaran
    $query = "UPDATE tblservice 
              SET status_servis = 'bayar',
                  bayar = '$bayar',
                  kembali = '$kembali',
                  total_akhir = '$total_akhir'
              WHERE no_service = '$no_service'";
    
    mysqli_query($koneksi, $query);
    
    // TRIGGER OTOMATIS JALAN DI SINI!
    
    // Opsional: Kirim WhatsApp otomatis
    include "class_whatsapp_automation.php";
    $wa = new WhatsAppAutomation($koneksi);
    $result = $wa->sendTerimaKasih($no_service);
    
    if($result['success']) {
        // Jika ada API, pesan terkirim otomatis
        echo "<script>alert('Pembayaran berhasil! Pesan WhatsApp terkirim.');</script>";
    } else {
        // Jika tidak ada API, tampilkan link WhatsApp Web
        $wa_link = $result['wa_link'];
        echo "<script>
            if(confirm('Pembayaran berhasil! Kirim ucapan terima kasih via WhatsApp?')) {
                window.open('$wa_link', '_blank');
            }
        </script>";
    }
    
    echo "<script>window.location='servis-input-reguler.php?snoserv=$no_service&tab=payment';</script>";
}
?>
```

#### **3. Link ke Dashboard Statistik (Opsional)**

Tambahkan tombol untuk melihat statistik pelanggan:

```php
<a href="statistik_pelanggan_dashboard.php" class="btn btn-info" target="_blank">
    <i class="fa fa-bar-chart"></i> Lihat Statistik Pelanggan
</a>
```

---

## 📱 Notifikasi WhatsApp Otomatis

### Setup WhatsApp API (Opsional)

Jika ingin notifikasi WhatsApp otomatis terkirim tanpa manual, setup API:

#### **1. Daftar di WhatsApp Gateway**

Pilih salah satu:
- **Fonnte** (https://fonnte.com) - Recommended, mulai Rp 50.000/bulan
- **Wablas** (https://wablas.com) - Mulai Rp 100.000/bulan
- **WooWA** (https://woowa.id) - Mulai Rp 75.000/bulan

#### **2. Buat File Konfigurasi**

```php
<?php
// File: _admincab/config_whatsapp.php

// WhatsApp API Configuration
define('WA_API_ENABLED', true); // Set false untuk disable auto-send
define('WA_API_KEY', 'your_api_key_here'); // Ganti dengan API key Anda
define('WA_API_URL', 'https://api.fonnte.com/send'); // URL API

// WhatsApp Settings
define('WA_AUTO_SEND_AFTER_PAYMENT', true); // Auto kirim setelah bayar
define('WA_SEND_DELAY', 5); // Delay 5 detik sebelum kirim
?>
```

#### **3. Modifikasi Proses Pembayaran**

```php
<?php
// Di file servis-input-reguler.php (dan file servis lainnya)

if(isset($_POST['btnbayar'])) {
    // ... proses pembayaran ...
    
    // Load config WhatsApp
    include "config_whatsapp.php";
    
    if(WA_API_ENABLED && WA_AUTO_SEND_AFTER_PAYMENT) {
        // Delay sebelum kirim (opsional)
        sleep(WA_SEND_DELAY);
        
        // Kirim WhatsApp
        include "class_whatsapp_automation.php";
        $wa = new WhatsAppAutomation($koneksi, WA_API_KEY, WA_API_URL);
        $wa->sendTerimaKasih($no_service);
    }
    
    // Redirect
    echo "<script>window.location='...';</script>";
}
?>
```

### Template Pesan WhatsApp

Pesan yang dikirim otomatis:

```
🏍️ *Terima Kasih - Fit Motor* 🏍️

Halo *Budi Santoso*,

Terima kasih telah mempercayakan service motor Anda kepada kami!

📋 *Detail Transaksi:*
• No. Service: SV25000000139
• Tanggal: 02/11/2025
• Total: Rp 850.000
• Status Member: *Gold*

✅ *Garansi Service:*
Service Anda bergaransi 30 hari atau 1000 KM (mana yang tercapai lebih dulu)

📅 *Reminder Service Berikutnya:*
Estimasi: 02/12/2025
Kami akan mengingatkan Anda saat waktunya service!

🎁 *Benefit Member Gold:*
• Diskon 15% untuk service
• Prioritas antrian
• Gratis cuci motor

Jika ada keluhan atau pertanyaan, jangan ragu untuk menghubungi kami!

Salam hangat,
*Tim Fit Motor* 🔧
```

---

## 🧪 Testing

### Test 1: Trigger Berfungsi

**Langkah:**
1. Input servis baru untuk pelanggan (misal: AD 1234 AB)
2. Total transaksi: Rp 500.000
3. Klik tombol BAYAR
4. Cek statistik pelanggan

**Query Test:**
```sql
-- Cek statistik pelanggan
SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB';

-- Harusnya muncul data:
-- total_transaksi = 1
-- total_nominal = 500000
-- status_member = Bronze (karena < 2 juta)
```

### Test 2: Status Member Naik

**Langkah:**
1. Input servis lagi untuk pelanggan yang sama
2. Total transaksi: Rp 1.600.000
3. Klik BAYAR
4. Cek statistik

**Expected Result:**
```sql
SELECT * FROM statistik_pelanggan WHERE no_pelanggan = 'AD 1234 AB';

-- Harusnya:
-- total_transaksi = 2
-- total_nominal = 2100000 (500000 + 1600000)
-- status_member = Silver (karena >= 2 juta)
```

### Test 3: WhatsApp Terkirim

**Langkah:**
1. Setup API WhatsApp (atau gunakan mode manual)
2. Input servis dan bayar
3. Cek WhatsApp pelanggan

**Expected Result:**
- Jika API aktif: Pesan otomatis terkirim
- Jika tidak ada API: Muncul link WhatsApp Web

---

## 📊 Monitoring Statistik

### Query Monitoring Real-Time:

```sql
-- 1. Cek total pelanggan per status member
SELECT 
    status_member,
    COUNT(*) as jumlah_pelanggan,
    SUM(total_nominal) as total_pendapatan
FROM statistik_pelanggan
GROUP BY status_member;

-- 2. Pelanggan yang baru naik status hari ini
SELECT 
    no_pelanggan,
    namapelanggan,
    status_member,
    total_nominal
FROM view_statistik_pelanggan
WHERE DATE(updated_at) = CURDATE()
ORDER BY total_nominal DESC;

-- 3. Top 10 transaksi hari ini
SELECT 
    s.no_service,
    s.no_pelanggan,
    p.namapelanggan,
    s.total_akhir,
    sp.status_member
FROM tblservice s
INNER JOIN tblpelanggan p ON s.no_pelanggan = p.nopelanggan
LEFT JOIN statistik_pelanggan sp ON s.no_pelanggan = sp.no_pelanggan
WHERE DATE(s.tanggal) = CURDATE()
AND s.status_servis = 'bayar'
ORDER BY s.total_akhir DESC
LIMIT 10;
```

---

## 🎯 Keuntungan Sistem Otomatis

### Untuk Kasir/Admin:
✅ **Tidak ada pekerjaan tambahan** - Input servis seperti biasa  
✅ **Tidak perlu training khusus** - Workflow tetap sama  
✅ **Tidak ada resiko lupa input** - Semua otomatis  

### Untuk Manajemen:
✅ **Data real-time** - Statistik selalu update  
✅ **Akurat 100%** - Tidak ada human error  
✅ **Insight pelanggan** - Tahu siapa pelanggan setia  
✅ **Retention strategy** - Follow up pelanggan otomatis  

### Untuk Pelanggan:
✅ **Apresiasi langsung** - Terima ucapan terima kasih  
✅ **Info garansi jelas** - Tahu hak garansi service  
✅ **Benefit member** - Dapat diskon sesuai status  
✅ **Reminder service** - Tidak lupa jadwal service  

---

## 🔧 Troubleshooting

### Problem: Statistik tidak update setelah bayar

**Penyebab:**
- Trigger belum diinstall
- Event scheduler tidak aktif

**Solusi:**
```sql
-- Cek trigger ada
SHOW TRIGGERS LIKE 'tblservice';

-- Jika tidak ada, install ulang
source database_statistik_pelanggan_otomatis.sql

-- Aktifkan event scheduler
SET GLOBAL event_scheduler = ON;
```

### Problem: Status member tidak berubah

**Penyebab:**
- Total nominal belum mencapai threshold
- Data di tblservice status bukan 'bayar'

**Solusi:**
```sql
-- Cek total nominal pelanggan
SELECT 
    no_pelanggan,
    SUM(total_akhir) as total
FROM tblservice
WHERE no_pelanggan = 'AD 1234 AB'
AND status_servis = 'bayar';

-- Manual refresh statistik
CALL sp_refresh_statistik_pelanggan();
```

### Problem: WhatsApp tidak terkirim

**Penyebab:**
- API key tidak valid
- Nomor telepon tidak ada/salah format
- Saldo API habis

**Solusi:**
```php
// Test kirim manual
include "class_whatsapp_automation.php";
$wa = new WhatsAppAutomation($koneksi);
$result = $wa->sendTerimaKasih('SV25000000001');
print_r($result); // Lihat error message
```

---

## 📚 Referensi

- **File SQL:** `database_statistik_pelanggan_otomatis.sql`
- **Dashboard:** `_admincab/statistik_pelanggan_dashboard.php`
- **Class WhatsApp:** `_admincab/class_whatsapp_automation.php`
- **Dokumentasi Lengkap:** `DOKUMENTASI_SISTEM_STATISTIK_PELANGGAN.md`
- **Quick Start:** `QUICK_START_STATISTIK_PELANGGAN.txt`

---

## ✅ Checklist Implementasi

**Instalasi Database:**
- [ ] Import `database_statistik_pelanggan_otomatis.sql`
- [ ] Aktifkan event scheduler: `SET GLOBAL event_scheduler = ON;`
- [ ] Jalankan: `CALL sp_refresh_statistik_pelanggan();`
- [ ] Verifikasi trigger: `SHOW TRIGGERS LIKE 'tblservice';`

**Implementasi di Halaman Servis:**
- [ ] Tambahkan tampilan status member (opsional)
- [ ] Tambahkan notifikasi WhatsApp (opsional)
- [ ] Tambahkan link ke dashboard statistik (opsional)
- [ ] Test input servis dan bayar

**Setup WhatsApp (Opsional):**
- [ ] Daftar di WhatsApp Gateway
- [ ] Buat file `config_whatsapp.php`
- [ ] Set API key dan URL
- [ ] Test kirim pesan

**Testing:**
- [ ] Test trigger berfungsi
- [ ] Test status member naik
- [ ] Test WhatsApp terkirim
- [ ] Test dashboard statistik

---

**Sistem siap digunakan!** 🎉

Kasir/admin tidak perlu melakukan apapun yang berbeda. Input servis seperti biasa, sistem otomatis update statistik di background.

---

**Dibuat:** 2 November 2025  
**Versi:** 1.0  
**Developer:** Fit Motor Development Team
